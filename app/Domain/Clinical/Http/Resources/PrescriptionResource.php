<?php

namespace App\Domain\Clinical\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'prescription_number' => $this->prescription_number,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'patient_id' => $this->patient_id,
            'patient' => $this->whenLoaded('patient', function () {
                return [
                    'id' => $this->patient->id,
                    'mrn' => $this->patient->mrn,
                    'name' => trim("{$this->patient->first_name} {$this->patient->last_name}"),
                ];
            }),
            'doctor_id' => $this->doctor_id,
            'doctor' => $this->whenLoaded('doctor', function () {
                return [
                    'id' => $this->doctor->id,
                    'name' => $this->doctor->name,
                ];
            }),
            'ehr_record_id' => $this->ehr_record_id,
            'appointment_id' => $this->appointment_id,
            'admission_id' => $this->admission_id,
            'status' => $this->status,
            'has_safety_warnings' => $this->has_safety_warnings,
            'safety_alerts' => $this->safety_alerts,
            'override_reason' => $this->override_reason,
            'overridden_by' => $this->overridden_by,
            'overridden_at' => $this->overridden_at?->toIso8601String(),
            'notes' => $this->notes,
            'prescribed_at' => $this->prescribed_at->toIso8601String(),
            'finalized_at' => $this->finalized_at?->toIso8601String(),
            'items' => PrescriptionItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
