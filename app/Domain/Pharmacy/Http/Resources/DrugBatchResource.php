<?php

namespace App\Domain\Pharmacy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrugBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'drug_id' => $this->drug_id,
            'batch_number' => $this->batch_number,
            'manufacturing_date' => $this->manufacturing_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'quantity_received' => $this->quantity_received,
            'quantity_on_hand' => $this->quantity_on_hand,
            'unit_cost_cents' => $this->unit_cost_cents,
            'supplier_name' => $this->supplier_name,
            'status' => $this->status,
            'is_expired' => $this->is_expired,
            'is_near_expiry' => $this->is_near_expiry,
            'days_until_expiry' => $this->days_until_expiry,
            'drug' => new DrugResource($this->whenLoaded('drug')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
