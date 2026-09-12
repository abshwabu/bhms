<?php

namespace App\Domain\Clinical\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiagnosisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'patient_id' => $this->patient_id,
            'ehr_record_id' => $this->ehr_record_id,
            'appointment_id' => $this->appointment_id,
            'admission_id' => $this->admission_id,
            'doctor_id' => $this->doctor_id,
            'doctor' => $this->whenLoaded('doctor', function () {
                return [
                    'id' => $this->doctor->id,
                    'name' => $this->doctor->name,
                ];
            }),
            'icd10_code' => $this->icd10_code,
            'icd10_title' => $this->icd10_title,
            'type' => $this->type,
            'severity' => $this->severity,
            'clinical_status' => $this->clinical_status,
            'verification_status' => $this->verification_status,
            'onset_date' => $this->onset_date?->format('Y-m-d'),
            'resolved_date' => $this->resolved_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
