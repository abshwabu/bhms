<?php

namespace App\Domain\Clinical\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EhrRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
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
            'author_id' => $this->author_id,
            'author' => $this->whenLoaded('author', function () {
                return [
                    'id' => $this->author->id,
                    'name' => $this->author->name,
                ];
            }),
            'encounter_type' => $this->encounter_type,
            'encounter_id' => $this->encounter_id,
            'record_type' => $this->record_type,
            'category' => $this->category,
            'title' => $this->title,
            'clinical_notes' => $this->clinical_notes,
            'vitals' => $this->vitals,
            'status' => $this->status,
            'version' => $this->version,
            'is_amended' => $this->is_amended,
            'amended_from_id' => $this->amended_from_id,
            'amendment_reason' => $this->amendment_reason,
            'finalized_at' => $this->finalized_at?->toIso8601String(),
            'finalizer' => $this->whenLoaded('finalizer', function () {
                return [
                    'id' => $this->finalizer->id,
                    'name' => $this->finalizer->name,
                ];
            }),
            'amendments' => EhrRecordResource::collection($this->whenLoaded('amendments')),
            'diagnoses' => DiagnosisResource::collection($this->whenLoaded('diagnoses')),
            'prescriptions' => PrescriptionResource::collection($this->whenLoaded('prescriptions')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
