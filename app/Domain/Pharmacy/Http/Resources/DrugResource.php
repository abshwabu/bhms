<?php

namespace App\Domain\Pharmacy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrugResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'brand_name' => $this->brand_name,
            'generic_name' => $this->generic_name,
            'form' => $this->form,
            'strength' => $this->strength,
            'unit_of_measure' => $this->unit_of_measure,
            'reorder_threshold' => $this->reorder_threshold,
            'target_stock_level' => $this->target_stock_level,
            'unit_cost_cents' => $this->unit_cost_cents,
            'unit_price_cents' => $this->unit_price_cents,
            'unit_price' => $this->unit_price,
            'is_prescription_required' => $this->is_prescription_required,
            'is_controlled_substance' => $this->is_controlled_substance,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'total_stock_on_hand' => $this->total_stock_on_hand,
            'is_low_stock' => $this->is_low_stock,
            'batches' => DrugBatchResource::collection($this->whenLoaded('batches')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
