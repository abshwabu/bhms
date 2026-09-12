<?php

namespace App\Domain\Emergency\Services;

use App\Domain\Emergency\Models\Ambulance;
use App\Domain\Emergency\Models\AmbulanceDispatch;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;

class AmbulanceDispatchService
{
    /**
     * Dispatch an ambulance to an emergency scene.
     */
    public function createDispatch(array $data, ?string $dispatcherUserId = null): AmbulanceDispatch
    {
        $ambulance = Ambulance::findOrFail($data['ambulance_id']);

        if (!$ambulance->is_available && empty($data['force_dispatch'])) {
            throw new DomainException("Ambulance {$ambulance->call_sign} ({$ambulance->vehicle_number}) is currently {$ambulance->status} and cannot be dispatched.");
        }

        $now = Carbon::now();
        $dispatchNumber = $this->generateDispatchNumber($ambulance->branch_id);

        $dispatch = AmbulanceDispatch::create([
            'organization_id' => $ambulance->organization_id,
            'branch_id' => $ambulance->branch_id,
            'ambulance_id' => $ambulance->id,
            'emergency_case_id' => $data['emergency_case_id'] ?? null,
            'dispatch_number' => $dispatchNumber,
            'caller_name' => $data['caller_name'] ?? null,
            'caller_phone' => $data['caller_phone'] ?? null,
            'pickup_address' => $data['pickup_address'],
            'pickup_latitude' => $data['pickup_latitude'] ?? null,
            'pickup_longitude' => $data['pickup_longitude'] ?? null,
            'destination_address' => $data['destination_address'] ?? 'Emergency Department, Main Hospital',
            'destination_latitude' => $data['destination_latitude'] ?? null,
            'destination_longitude' => $data['destination_longitude'] ?? null,
            'priority' => $data['priority'] ?? 'code_red',
            'nature_of_emergency' => $data['nature_of_emergency'],
            'status' => 'dispatched',
            'patient_condition_notes' => $data['patient_condition_notes'] ?? null,
            'dispatched_by' => $dispatcherUserId,
            'dispatched_at' => $now,
        ]);

        // Real-time ambulance status update
        $ambulance->update([
            'status' => 'dispatched',
        ]);

        return $dispatch->load(['ambulance', 'dispatcher']);
    }

    /**
     * Update dispatch status in near real-time with timestamp logging.
     * Acceptance criterion: Ambulance status (available/dispatched/en route/arrived) updates in near real-time.
     */
    public function updateStatus(AmbulanceDispatch $dispatch, string $newStatus, ?string $notes = null): AmbulanceDispatch
    {
        $validStatuses = [
            'dispatched',
            'en_route_scene',
            'at_scene',
            'en_route_hospital',
            'arrived_hospital',
            'completed',
            'cancelled',
        ];

        if (!in_array($newStatus, $validStatuses)) {
            throw new DomainException("Invalid ambulance dispatch status: {$newStatus}");
        }

        $now = Carbon::now();
        $updates = ['status' => $newStatus];

        if ($notes) {
            $updates['patient_condition_notes'] = $notes;
        }

        // Set lifecycle timestamp
        switch ($newStatus) {
            case 'en_route_scene':
                $updates['en_route_scene_at'] = $now;
                break;
            case 'at_scene':
                $updates['arrived_scene_at'] = $now;
                break;
            case 'en_route_hospital':
                $updates['departed_scene_at'] = $now;
                break;
            case 'arrived_hospital':
                $updates['arrived_hospital_at'] = $now;
                break;
            case 'completed':
                $updates['completed_at'] = $now;
                break;
            case 'cancelled':
                $updates['cancelled_at'] = $now;
                $updates['cancellation_reason'] = $notes;
                break;
        }

        $dispatch->update($updates);

        // Synchronize ambulance fleet status
        $ambulance = $dispatch->ambulance;
        if ($ambulance) {
            if (in_array($newStatus, ['completed', 'cancelled'])) {
                $ambulance->update(['status' => 'available']);
            } else {
                $ambulance->update(['status' => $newStatus]);
            }
        }

        return $dispatch->fresh(['ambulance', 'emergencyCase']);
    }

    /**
     * Record real-time GPS telemetry from mobile unit / in-vehicle tracker.
     */
    public function recordTelemetry(Ambulance $ambulance, array $telemetry): Ambulance
    {
        $ambulance->update([
            'current_latitude' => $telemetry['latitude'],
            'current_longitude' => $telemetry['longitude'],
            'speed_kmh' => $telemetry['speed_kmh'] ?? $ambulance->speed_kmh,
            'heading' => $telemetry['heading'] ?? $ambulance->heading,
            'fuel_percentage' => $telemetry['fuel_percentage'] ?? $ambulance->fuel_percentage,
            'last_telemetry_at' => Carbon::now(),
        ]);

        return $ambulance;
    }

    /**
     * Get real-time ambulance fleet status board.
     */
    public function getFleetOverview(?string $branchId = null): array
    {
        $query = Ambulance::query()->with('activeDispatch');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $ambulances = $query->get();

        return [
            'total_ambulances' => $ambulances->count(),
            'available_count' => $ambulances->where('status', 'available')->count(),
            'dispatched_count' => $ambulances->whereIn('status', ['dispatched', 'en_route_scene', 'at_scene', 'en_route_hospital'])->count(),
            'arrived_count' => $ambulances->where('status', 'arrived_hospital')->count(),
            'maintenance_count' => $ambulances->where('status', 'maintenance')->count(),
            'fleet' => $ambulances,
        ];
    }

    protected function generateDispatchNumber(string $branchId): string
    {
        $today = Carbon::today()->format('Ymd');
        $count = AmbulanceDispatch::where('branch_id', $branchId)
            ->whereDate('dispatched_at', Carbon::today())
            ->count() + 1;

        return sprintf('DISP-%s-%04d', $today, $count);
    }
}
