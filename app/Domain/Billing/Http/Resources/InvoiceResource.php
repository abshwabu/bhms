<?php

namespace App\Domain\Billing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'patient_id' => $this->patient_id,
            'patient' => $this->whenLoaded('patient', function () {
                return [
                    'id' => $this->patient->id,
                    'mrn' => $this->patient->mrn,
                    'first_name' => $this->patient->first_name,
                    'last_name' => $this->patient->last_name,
                    'full_name' => $this->patient->full_name,
                    'gender' => $this->patient->gender,
                    'date_of_birth' => $this->patient->date_of_birth?->format('Y-m-d'),
                    'phone' => $this->patient->phone,
                    'email' => $this->patient->email,
                ];
            }),
            'appointment_id' => $this->appointment_id,
            'admission_id' => $this->admission_id,
            'doctor_id' => $this->doctor_id,
            'doctor' => $this->whenLoaded('doctor', function () {
                return [
                    'id' => $this->doctor->id,
                    'name' => $this->doctor->name,
                    'email' => $this->doctor->email,
                ];
            }),
            'department' => $this->department,
            'billing_type' => $this->billing_type,
            'status' => $this->status,

            // Cents
            'subtotal_cents' => $this->subtotal_cents,
            'discount_cents' => $this->discount_cents,
            'tax_cents' => $this->tax_cents,
            'total_cents' => $this->total_cents,
            'paid_cents' => $this->paid_cents,
            'balance_cents' => $this->balance_cents,

            // Dollar amounts
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total' => $this->total,
            'paid' => $this->paid,
            'balance' => $this->balance,

            'payment_terms' => $this->payment_terms,
            'due_date' => $this->due_date?->toDateString(),
            'notes' => $this->notes,

            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'discounts' => DiscountResource::collection($this->whenLoaded('discounts')),
            'refunds' => RefundResource::collection($this->whenLoaded('refunds')),
            'claims' => InsuranceClaimResource::collection($this->whenLoaded('claims')),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
