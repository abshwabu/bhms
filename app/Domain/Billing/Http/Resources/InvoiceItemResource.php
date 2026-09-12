<?php

namespace App\Domain\Billing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'item_type' => $this->item_type,
            'item_code' => $this->item_code,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price_cents' => $this->unit_price_cents,
            'unit_price' => $this->unit_price,
            'subtotal_cents' => $this->subtotal_cents,
            'subtotal' => $this->subtotal,
            'discount_cents' => $this->discount_cents,
            'discount' => $this->discount,
            'total_cents' => $this->total_cents,
            'total' => $this->total,
            'department' => $this->department,
            'doctor_id' => $this->doctor_id,
            'doctor_name' => $this->doctor?->name,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
