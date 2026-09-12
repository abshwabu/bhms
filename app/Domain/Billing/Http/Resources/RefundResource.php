<?php

namespace App\Domain\Billing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'refund_number' => $this->refund_number,
            'invoice_id' => $this->invoice_id,
            'payment_id' => $this->payment_id,
            'patient_id' => $this->patient_id,
            'amount_cents' => $this->amount_cents,
            'amount' => $this->amount,
            'refund_mode' => $this->refund_mode,
            'reason' => $this->reason,
            'requires_approval' => $this->requires_approval,
            'status' => $this->status,
            'requested_by' => [
                'id' => $this->requester?->id,
                'name' => $this->requester?->name,
            ],
            'approved_by' => [
                'id' => $this->approver?->id,
                'name' => $this->approver?->name,
            ],
            'approved_at' => $this->approved_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
