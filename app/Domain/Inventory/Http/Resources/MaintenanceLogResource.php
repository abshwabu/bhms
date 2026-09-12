<?php

namespace App\Domain\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'log_number' => $this->log_number,
            'equipment_id' => $this->equipment_id,
            'equipment' => $this->equipment ? [
                'id' => $this->equipment->id,
                'asset_tag' => $this->equipment->asset_tag,
                'name' => $this->equipment->name,
                'department' => $this->equipment->department,
                'room_location' => $this->equipment->room_location,
            ] : null,
            'maintenance_type' => $this->maintenance_type,
            'status' => $this->status,
            'priority' => $this->priority,
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'completed_date' => $this->completed_date?->toDateString(),
            'technician_name' => $this->technician_name,
            'vendor_id' => $this->vendor_id,
            'vendor' => $this->vendor ? [
                'id' => $this->vendor->id,
                'name' => $this->vendor->name,
            ] : null,
            'performed_by' => [
                'id' => $this->performer?->id,
                'name' => $this->performer?->name,
            ],
            'cost_cents' => $this->cost_cents,
            'cost' => $this->cost,
            'findings' => $this->findings,
            'actions_taken' => $this->actions_taken,
            'parts_replaced' => $this->parts_replaced,
            'next_recommended_date' => $this->next_recommended_date?->toDateString(),
            'is_overdue' => $this->is_overdue,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
