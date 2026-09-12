<?php

namespace App\Domain\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_code' => $this->item_code,
            'name' => $this->name,
            'category' => $this->category,
            'sub_category' => $this->sub_category,
            'unit_of_measure' => $this->unit_of_measure,
            'current_stock' => $this->current_stock,
            'min_stock_level' => $this->min_stock_level,
            'max_stock_level' => $this->max_stock_level,
            'reorder_quantity' => $this->reorder_quantity,
            'unit_cost_cents' => $this->unit_cost_cents,
            'unit_cost' => $this->unit_cost,
            'stock_status' => $this->stock_status,
            'is_low_stock' => $this->is_low_stock,
            'is_out_of_stock' => $this->is_out_of_stock,
            'storage_location' => $this->storage_location,
            'is_active' => $this->is_active,
            'description' => $this->description,
            'default_vendor' => $this->defaultVendor ? [
                'id' => $this->defaultVendor->id,
                'name' => $this->defaultVendor->name,
                'email' => $this->defaultVendor->email,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
