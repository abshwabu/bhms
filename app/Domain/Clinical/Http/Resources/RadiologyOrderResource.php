<?php

namespace App\Domain\Clinical\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RadiologyOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
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
            'ehr_record_id' => $this->ehr_record_id,
            'appointment_id' => $this->appointment_id,
            'admission_id' => $this->admission_id,
            'ordering_doctor_id' => $this->ordering_doctor_id,
            'ordering_doctor' => $this->whenLoaded('orderingDoctor', function () {
                return [
                    'id' => $this->orderingDoctor->id,
                    'name' => $this->orderingDoctor->name,
                ];
            }),
            'modality' => $this->modality,
            'body_part' => $this->body_part,
            'procedure_name' => $this->procedure_name,
            'priority' => $this->priority,
            'clinical_indication' => $this->clinical_indication,
            'transport_required' => $this->transport_required,
            'is_pregnant_or_possible' => $this->is_pregnant_or_possible,
            'status' => $this->status,
            'ordered_at' => $this->ordered_at->toIso8601String(),
            'performed_at' => $this->performed_at?->toIso8601String(),
            'reported_at' => $this->reported_at?->toIso8601String(),
            'findings' => $this->findings,
            'impression' => $this->impression,
            'radiologist_id' => $this->radiologist_id,
            'radiologist' => $this->whenLoaded('radiologist', function () {
                return [
                    'id' => $this->radiologist->id,
                    'name' => $this->radiologist->name,
                ];
            }),
            'reviewed_by_doctor_id' => $this->reviewed_by_doctor_id,
            'reviewed_by_doctor' => $this->whenLoaded('reviewingDoctor', function () {
                return [
                    'id' => $this->reviewingDoctor->id,
                    'name' => $this->reviewingDoctor->name,
                ];
            }),
            'reviewed_by_doctor_at' => $this->reviewed_by_doctor_at?->toIso8601String(),
            'doctor_review_notes' => $this->doctor_review_notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
