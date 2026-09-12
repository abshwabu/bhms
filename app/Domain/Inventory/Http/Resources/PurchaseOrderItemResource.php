<?php

namespace App\Domain\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_order_id' => $this->purchase_order_id,
            'inventory_item_id' => $this->inventory_item_id,
            'item_code' => $this->inventoryItem?->item_code,
            'item_name' => $this->inventoryItem?->name,
            'unit_of_measure' => $this->inventoryItem?->unit_of_measure,
            'quantity_ordered' => $this->quantity_ordered,
            'quantity_received' => $this->quantity_received,
            'remaining_quantity' => $this->remaining_quantity,
            'is_fully_received' => $this->is_fully_received,
            'unit_cost_cents' => $this->unit_cost_cents,
            'unit_cost' => $this->unit_cost,
            'total_cost_cents' => $this->total_cost_cents,
            'total_cost' => $this->total_cost,
            'notes' => $this->notes,
        ];
    }
}
