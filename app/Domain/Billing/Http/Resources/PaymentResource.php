<?php

namespace App\Domain\Billing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'invoice_id' => $this->invoice_id,
            'patient_id' => $this->patient_id,
            'payment_mode' => $this->payment_mode,
            'amount_cents' => $this->amount_cents,
            'amount' => $this->amount,
            'transaction_reference' => $this->transaction_reference,
            'notes' => $this->notes,
            'status' => $this->status,
            'received_at' => $this->received_at?->toIso8601String(),
            'cashier' => [
                'id' => $this->cashier?->id,
                'name' => $this->cashier?->name,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
