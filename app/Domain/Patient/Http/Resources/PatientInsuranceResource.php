<?php

namespace App\Domain\Patient\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientInsuranceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'provider_name' => $this->provider_name,
            'policy_number' => $this->policy_number,
            'group_number' => $this->group_number,
            'coverage_type' => $this->coverage_type,
            'coverage_percentage' => $this->coverage_percentage,
            'copay_amount_cents' => $this->copay_amount_cents,
            'copay_amount_formatted' => number_format($this->copay_amount_cents / 100, 2),
            'valid_from' => $this->valid_from ? $this->valid_from->format('Y-m-d') : null,
            'valid_until' => $this->valid_until ? $this->valid_until->format('Y-m-d') : null,
            'pre_auth_required' => $this->pre_auth_required,
            'status' => $this->status,
            'card_image_path' => $this->card_image_path,
            'notes' => $this->notes,
            'verified_at' => $this->verified_at?->toIso8601String(),
            'verified_by' => $this->verifier ? [
                'id' => $this->verifier->id,
                'name' => $this->verifier->name,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
