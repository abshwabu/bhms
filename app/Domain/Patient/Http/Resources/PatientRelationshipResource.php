<?php

namespace App\Domain\Patient\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientRelationshipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'related_patient_id' => $this->related_patient_id,
            'relationship_type' => $this->relationship_type,
            'is_guardian' => $this->is_guardian,
            'is_emergency_contact' => $this->is_emergency_contact,
            'is_billing_guarantor' => $this->is_billing_guarantor,
            'display_name' => $this->display_name,
            'phone' => $this->phone,
            'external_name' => $this->external_name,
            'external_phone' => $this->external_phone,
            'external_national_id' => $this->external_national_id,
            'external_address' => $this->external_address,
            'notes' => $this->notes,
            'related_patient' => $this->when($this->relationLoaded('relatedPatient') && $this->relatedPatient, function () {
                return [
                    'id' => $this->relatedPatient->id,
                    'mrn' => $this->relatedPatient->mrn,
                    'full_name' => $this->relatedPatient->full_name,
                    'gender' => $this->relatedPatient->gender,
                    'date_of_birth' => $this->relatedPatient->date_of_birth?->format('Y-m-d'),
                    'phone' => $this->relatedPatient->phone,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
