<?php

namespace App\Domain\Pharmacy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrugStockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'drug_id' => $this->drug_id,
            'drug_batch_id' => $this->drug_batch_id,
            'movement_type' => $this->movement_type,
            'quantity' => $this->quantity,
            'quantity_before' => $this->quantity_before,
            'quantity_after' => $this->quantity_after,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'reason' => $this->reason,
            'performed_by' => $this->performed_by,
            'performer' => [
                'id' => $this->performer?->id,
                'name' => $this->performer?->name,
            ],
            'drug' => [
                'id' => $this->drug?->id,
                'sku' => $this->drug?->sku,
                'brand_name' => $this->drug?->brand_name,
                'generic_name' => $this->drug?->generic_name,
            ],
            'batch' => [
                'id' => $this->batch?->id,
                'batch_number' => $this->batch?->batch_number,
                'expiry_date' => $this->batch?->expiry_date?->toDateString(),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
