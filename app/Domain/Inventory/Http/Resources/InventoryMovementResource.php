<?php

namespace App\Domain\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inventory_item_id' => $this->inventory_item_id,
            'movement_type' => $this->movement_type,
            'quantity' => $this->quantity,
            'quantity_before' => $this->quantity_before,
            'quantity_after' => $this->quantity_after,
            'unit_cost_cents' => $this->unit_cost_cents,
            'unit_cost' => $this->unit_cost,
            'total_cost_cents' => $this->total_cost_cents,
            'total_cost' => $this->total_cost,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'department' => $this->department,
            'performed_by' => [
                'id' => $this->performer?->id,
                'name' => $this->performer?->name,
            ],
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
