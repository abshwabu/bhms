<?php

namespace App\Domain\Pharmacy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DispensingRecordItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dispensing_record_id' => $this->dispensing_record_id,
            'prescription_item_id' => $this->prescription_item_id,
            'drug_id' => $this->drug_id,
            'drug_batch_id' => $this->drug_batch_id,
            'quantity_dispensed' => $this->quantity_dispensed,
            'directions' => $this->directions,
            'unit_price_cents' => $this->unit_price_cents,
            'total_price_cents' => $this->total_price_cents,
            'total_price' => $this->total_price_cents / 100,
            'drug' => new DrugResource($this->whenLoaded('drug')),
            'batch' => new DrugBatchResource($this->whenLoaded('batch')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
