<?php

namespace App\Domain\Billing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceClaimResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'claim_number' => $this->claim_number,
            'invoice_id' => $this->invoice_id,
            'patient_insurance_id' => $this->patient_insurance_id,
            'patient_id' => $this->patient_id,
            'patient_name' => $this->patient ? trim("{$this->patient->first_name} {$this->patient->last_name}") : null,
            'patient_mrn' => $this->patient?->mrn,
            'provider_name' => $this->provider_name,
            'policy_number' => $this->policy_number,
            'pre_auth_number' => $this->pre_auth_number,
            'claimed_amount_cents' => $this->claimed_amount_cents,
            'claimed_amount' => $this->claimed_amount,
            'approved_amount_cents' => $this->approved_amount_cents,
            'approved_amount' => $this->approved_amount,
            'copay_amount_cents' => $this->copay_amount_cents,
            'copay_amount' => $this->copay_amount,
            'deductible_amount_cents' => $this->deductible_amount_cents,
            'status' => $this->status,
            'submission_date' => $this->submission_date?->toDateString(),
            'settlement_date' => $this->settlement_date?->toDateString(),
            'adjudication_notes' => $this->adjudication_notes,
            'rejection_reason' => $this->rejection_reason,
            'submitted_by' => [
                'id' => $this->submitter?->id,
                'name' => $this->submitter?->name,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
