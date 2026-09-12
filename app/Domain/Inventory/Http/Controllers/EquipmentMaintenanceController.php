<?php

namespace App\Domain\Inventory\Http\Controllers;

use App\Domain\Inventory\Http\Resources\EquipmentResource;
use App\Domain\Inventory\Http\Resources\MaintenanceLogResource;
use App\Domain\Inventory\Models\Equipment;
use App\Domain\Inventory\Models\MaintenanceLog;
use App\Domain\Inventory\Services\EquipmentMaintenanceService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EquipmentMaintenanceController extends Controller
{
    public function __construct(
        protected EquipmentMaintenanceService $maintenanceService
    ) {}

    /**
     * List hospital equipment and assets.
     */
    public function indexEquipment(Request $request): JsonResponse
    {
        $query = Equipment::query()
            ->with(['vendor', 'maintenanceLogs'])
            ->orderBy('name', 'asc');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('department')) {
            $query->where('department', $request->input('department'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('overdue') && filter_var($request->input('overdue'), FILTER_VALIDATE_BOOLEAN)) {
            $query->maintenanceOverdue();
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'ILIKE', "%{$term}%")
                  ->orWhere('asset_tag', 'ILIKE', "%{$term}%")
                  ->orWhere('model_number', 'ILIKE', "%{$term}%")
                  ->orWhere('serial_number', 'ILIKE', "%{$term}%");
            });
        }

        $equipments = $query->paginate($request->input('per_page', 25));

        return ApiResponse::paginated(
            $equipments->through(fn($e) => new EquipmentResource($e)),
            'Equipment retrieved.'
        );
    }

    /**
     * Register hospital equipment asset.
     */
    public function storeEquipment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'asset_tag' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'in:biomedical,laboratory,radiology,surgical,it_hardware,facility'],
            'model_number' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'vendor_id' => ['nullable', 'uuid', 'exists:vendors,id'],
            'department' => ['required', 'string', 'max:100'],
            'room_location' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost_cents' => ['nullable', 'integer', 'min:0'],
            'warranty_expiry_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:operational,under_maintenance,out_of_order,decommissioned'],
            'criticality' => ['nullable', 'string', 'in:critical,high,medium,low'],
            'maintenance_frequency_days' => ['nullable', 'integer', 'min:1'],
            'next_maintenance_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $equipment = $this->maintenanceService->registerEquipment($validated);

        return ApiResponse::success(
            new EquipmentResource($equipment->load('vendor')),
            "Equipment '{$equipment->name}' registered successfully.",
            201
        );
    }

    /**
     * Show equipment asset and its maintenance history.
     */
    public function showEquipment(Equipment $equipment): JsonResponse
    {
        $equipment->load(['vendor', 'maintenanceLogs.vendor', 'maintenanceLogs.performer']);

        return ApiResponse::success(
            new EquipmentResource($equipment),
            'Equipment details retrieved.'
        );
    }

    /**
     * Update equipment asset.
     */
    public function updateEquipment(Request $request, Equipment $equipment): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'category' => ['sometimes', 'required', 'string'],
            'model_number' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'vendor_id' => ['nullable', 'uuid', 'exists:vendors,id'],
            'department' => ['sometimes', 'required', 'string', 'max:100'],
            'room_location' => ['nullable', 'string', 'max:100'],
            'warranty_expiry_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:operational,under_maintenance,out_of_order,decommissioned'],
            'criticality' => ['nullable', 'string', 'in:critical,high,medium,low'],
            'maintenance_frequency_days' => ['nullable', 'integer', 'min:1'],
            'next_maintenance_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $equipment->update($validated);

        return ApiResponse::success(
            new EquipmentResource($equipment->load('vendor')),
            "Equipment updated successfully."
        );
    }

    /**
     * Schedule a maintenance event (preventive, repair, calibration).
     */
    public function scheduleMaintenance(Request $request, Equipment $equipment): JsonResponse
    {
        $validated = $request->validate([
            'scheduled_date' => ['required', 'date'],
            'maintenance_type' => ['required', 'string', 'in:preventive,corrective_repair,calibration,safety_inspection'],
            'priority' => ['required', 'string', 'in:low,medium,high,urgent'],
            'technician_name' => ['nullable', 'string', 'max:100'],
            'vendor_id' => ['nullable', 'uuid', 'exists:vendors,id'],
            'cost_cents' => ['nullable', 'integer', 'min:0'],
            'findings' => ['nullable', 'string'],
        ]);

        $userId = $request->user()?->id ?? $request->input('scheduled_by');

        try {
            $log = $this->maintenanceService->scheduleMaintenance($equipment, $validated, $userId);

            return ApiResponse::success(
                new MaintenanceLogResource($log),
                "Maintenance event scheduled for {$log->scheduled_date}.",
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'MAINTENANCE_SCHEDULING_ERROR', [], 422);
        }
    }

    /**
     * Complete a maintenance log ticket and advance next maintenance cycle.
     */
    public function completeMaintenance(Request $request, MaintenanceLog $maintenanceLog): JsonResponse
    {
        $validated = $request->validate([
            'completed_date' => ['nullable', 'date'],
            'findings' => ['nullable', 'string'],
            'actions_taken' => ['required', 'string'],
            'parts_replaced' => ['nullable', 'array'],
            'cost_cents' => ['nullable', 'integer', 'min:0'],
            'next_recommended_date' => ['nullable', 'date'],
        ]);

        $userId = $request->user()?->id ?? $request->input('performed_by');
        if (!$userId) {
            return ApiResponse::error('User is required to complete maintenance.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $completed = $this->maintenanceService->completeMaintenance($maintenanceLog, $validated, $userId);

            return ApiResponse::success(
                new MaintenanceLogResource($completed),
                "Maintenance #{$completed->log_number} completed. Next maintenance scheduled for {$completed->next_recommended_date}."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'MAINTENANCE_COMPLETION_ERROR', [], 422);
        }
    }

    /**
     * List maintenance schedules and history logs.
     */
    public function listMaintenanceLogs(Request $request): JsonResponse
    {
        $query = MaintenanceLog::query()
            ->with(['equipment', 'vendor', 'performer'])
            ->orderBy('scheduled_date', 'desc');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('equipment_id')) {
            $query->where('equipment_id', $request->input('equipment_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('maintenance_type')) {
            $query->where('maintenance_type', $request->input('maintenance_type'));
        }

        $logs = $query->paginate($request->input('per_page', 25));

        return ApiResponse::paginated(
            $logs->through(fn($l) => new MaintenanceLogResource($l)),
            'Maintenance logs retrieved.'
        );
    }

    /**
     * Proactive alerts for equipment maintenance due, overdue, and expiring warranties.
     */
    public function maintenanceAlerts(Request $request): JsonResponse
    {
        $alerts = $this->maintenanceService->getMaintenanceAlerts(
            $request->input('branch_id'),
            (int) $request->input('upcoming_days', 14)
        );

        return ApiResponse::success($alerts, 'Maintenance and warranty alerts retrieved.');
    }
}
