<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Models\Equipment;
use App\Domain\Inventory\Models\MaintenanceLog;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class EquipmentMaintenanceService
{
    /**
     * Generate sequential maintenance ticket / log number: MNT-2026-0001
     */
    public function generateLogNumber(): string
    {
        $year = Carbon::now()->format('Y');
        $prefix = "MNT-{$year}-";

        $count = MaintenanceLog::where('log_number', 'like', "{$prefix}%")->count();
        $seq = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$seq}";
    }

    /**
     * Register a new hospital biomedical/facility asset.
     */
    public function registerEquipment(array $data): Equipment
    {
        $frequency = (int) ($data['maintenance_frequency_days'] ?? 90);
        $purchaseDate = isset($data['purchase_date']) ? Carbon::parse($data['purchase_date']) : Carbon::today();
        $nextDate = isset($data['next_maintenance_date'])
            ? Carbon::parse($data['next_maintenance_date'])
            : $purchaseDate->copy()->addDays($frequency);

        return Equipment::create([
            'organization_id' => $data['organization_id'],
            'branch_id' => $data['branch_id'],
            'asset_tag' => $data['asset_tag'],
            'name' => $data['name'],
            'category' => $data['category'] ?? 'biomedical',
            'model_number' => $data['model_number'] ?? null,
            'serial_number' => $data['serial_number'] ?? null,
            'manufacturer' => $data['manufacturer'] ?? null,
            'vendor_id' => $data['vendor_id'] ?? null,
            'department' => $data['department'] ?? 'general',
            'room_location' => $data['room_location'] ?? null,
            'purchase_date' => $purchaseDate,
            'purchase_cost_cents' => (int) ($data['purchase_cost_cents'] ?? 0),
            'warranty_expiry_date' => $data['warranty_expiry_date'] ?? null,
            'status' => $data['status'] ?? 'operational',
            'criticality' => $data['criticality'] ?? 'medium',
            'maintenance_frequency_days' => $frequency,
            'last_maintenance_date' => $data['last_maintenance_date'] ?? null,
            'next_maintenance_date' => $nextDate,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Schedule a routine preventive maintenance or corrective repair.
     */
    public function scheduleMaintenance(Equipment $equipment, array $data, ?string $userId = null): MaintenanceLog
    {
        return DB::transaction(function () use ($equipment, $data, $userId) {
            $scheduledDate = Carbon::parse($data['scheduled_date']);
            $logNumber = $this->generateLogNumber();

            $log = MaintenanceLog::create([
                'organization_id' => $equipment->organization_id,
                'branch_id' => $equipment->branch_id,
                'equipment_id' => $equipment->id,
                'log_number' => $logNumber,
                'maintenance_type' => $data['maintenance_type'] ?? 'preventive',
                'status' => 'scheduled',
                'priority' => $data['priority'] ?? 'medium',
                'scheduled_date' => $scheduledDate,
                'technician_name' => $data['technician_name'] ?? null,
                'vendor_id' => $data['vendor_id'] ?? $equipment->vendor_id,
                'performed_by' => $userId,
                'cost_cents' => (int) ($data['cost_cents'] ?? 0),
                'findings' => $data['findings'] ?? null,
            ]);

            // Update next maintenance date and optionally status
            $equipment->next_maintenance_date = $scheduledDate;
            if (in_array($data['priority'] ?? 'medium', ['high', 'urgent']) || ($data['maintenance_type'] ?? '') === 'corrective_repair') {
                $equipment->status = 'under_maintenance';
            }
            $equipment->save();

            return $log->load(['equipment', 'vendor', 'performer']);
        });
    }

    /**
     * Mark a maintenance task as completed and advance the next maintenance cycle.
     */
    public function completeMaintenance(MaintenanceLog $log, array $data, string $userId): MaintenanceLog
    {
        if ($log->status === 'completed') {
            throw new DomainException("Maintenance log #{$log->log_number} has already been completed.");
        }

        return DB::transaction(function () use ($log, $data, $userId) {
            $completedDate = isset($data['completed_date']) ? Carbon::parse($data['completed_date']) : Carbon::today();
            $equipment = $log->equipment;

            $log->status = 'completed';
            $log->completed_date = $completedDate;
            $log->findings = $data['findings'] ?? $log->findings;
            $log->actions_taken = $data['actions_taken'] ?? null;
            $log->parts_replaced = $data['parts_replaced'] ?? null;
            $log->cost_cents = isset($data['cost_cents']) ? (int) $data['cost_cents'] : $log->cost_cents;
            $log->performed_by = $userId;

            $nextDate = isset($data['next_recommended_date'])
                ? Carbon::parse($data['next_recommended_date'])
                : $completedDate->copy()->addDays($equipment->maintenance_frequency_days ?: 90);

            $log->next_recommended_date = $nextDate;
            $log->save();

            // Update equipment attributes
            $equipment->last_maintenance_date = $completedDate;
            $equipment->next_maintenance_date = $nextDate;
            $equipment->status = 'operational';
            $equipment->save();

            return $log->load(['equipment', 'vendor', 'performer']);
        });
    }

    /**
     * Proactive alerts for equipment maintenance due, overdue, and expiring warranties.
     * Acceptance criterion: Maintenance schedules generate reminders/alerts ahead of due dates.
     */
    public function getMaintenanceAlerts(?string $branchId = null, int $upcomingDays = 14): array
    {
        $today = Carbon::today();
        $cutoff = $today->copy()->addDays($upcomingDays);

        $baseQuery = Equipment::query()->where('status', '!=', 'decommissioned');
        if ($branchId) {
            $baseQuery->where('branch_id', $branchId);
        }

        // Overdue maintenance
        $overdueEquipment = (clone $baseQuery)
            ->with('vendor')
            ->whereNotNull('next_maintenance_date')
            ->where('next_maintenance_date', '<', $today)
            ->orderBy('next_maintenance_date', 'asc')
            ->get();

        // Upcoming maintenance within specified days
        $upcomingEquipment = (clone $baseQuery)
            ->with('vendor')
            ->whereNotNull('next_maintenance_date')
            ->whereBetween('next_maintenance_date', [$today, $cutoff])
            ->orderBy('next_maintenance_date', 'asc')
            ->get();

        // Expiring warranties in next 30 days
        $expiringWarranties = (clone $baseQuery)
            ->with('vendor')
            ->whereNotNull('warranty_expiry_date')
            ->whereBetween('warranty_expiry_date', [$today, $today->copy()->addDays(30)])
            ->orderBy('warranty_expiry_date', 'asc')
            ->get();

        // Scheduled logs pending execution
        $logQuery = MaintenanceLog::query()
            ->with(['equipment', 'vendor'])
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->orderBy('scheduled_date', 'asc');

        if ($branchId) {
            $logQuery->where('branch_id', $branchId);
        }

        $pendingLogs = $logQuery->get();

        return [
            'overdue_equipment_count' => $overdueEquipment->count(),
            'overdue_equipment' => $overdueEquipment,
            'upcoming_equipment_count' => $upcomingEquipment->count(),
            'upcoming_equipment' => $upcomingEquipment,
            'expiring_warranties_count' => $expiringWarranties->count(),
            'expiring_warranties' => $expiringWarranties,
            'pending_maintenance_logs_count' => $pendingLogs->count(),
            'pending_maintenance_logs' => $pendingLogs,
        ];
    }
}
