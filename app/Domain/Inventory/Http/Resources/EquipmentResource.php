<?php

namespace App\Domain\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_tag' => $this->asset_tag,
            'name' => $this->name,
            'category' => $this->category,
            'model_number' => $this->model_number,
            'serial_number' => $this->serial_number,
            'manufacturer' => $this->manufacturer,
            'vendor_id' => $this->vendor_id,
            'vendor' => $this->vendor ? [
                'id' => $this->vendor->id,
                'name' => $this->vendor->name,
                'phone' => $this->vendor->phone,
            ] : null,
            'department' => $this->department,
            'room_location' => $this->room_location,
            'purchase_date' => $this->purchase_date?->toDateString(),
            'purchase_cost_cents' => $this->purchase_cost_cents,
            'purchase_cost' => $this->purchase_cost,
            'warranty_expiry_date' => $this->warranty_expiry_date?->toDateString(),
            'status' => $this->status,
            'criticality' => $this->criticality,
            'maintenance_frequency_days' => $this->maintenance_frequency_days,
            'last_maintenance_date' => $this->last_maintenance_date?->toDateString(),
            'next_maintenance_date' => $this->next_maintenance_date?->toDateString(),
            'days_until_next_maintenance' => $this->days_until_next_maintenance,
            'is_maintenance_overdue' => $this->is_maintenance_overdue,
            'is_maintenance_due_soon' => $this->is_maintenance_due_soon,
            'is_warranty_expired' => $this->is_warranty_expired,
            'notes' => $this->notes,
            'maintenance_logs_count' => $this->maintenanceLogs()->count(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
