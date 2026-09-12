<?php

namespace App\Domain\Radiology\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImagingOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'accession_number' => $this->accession_number,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'patient_id' => $this->patient_id,
            'patient' => $this->whenLoaded('patient', function () {
                return [
                    'id' => $this->patient->id,
                    'mrn' => $this->patient->mrn,
                    'name' => trim("{$this->patient->first_name} {$this->patient->last_name}"),
                    'gender' => $this->patient->gender,
                    'age' => $this->patient->date_of_birth ? $this->patient->date_of_birth->age : null,
                ];
            }),
            'radiology_order_id' => $this->radiology_order_id,
            'ordering_doctor_id' => $this->ordering_doctor_id,
            'ordering_doctor' => $this->whenLoaded('orderingDoctor', fn() => $this->orderingDoctor?->name),
            'technologist_id' => $this->technologist_id,
            'technologist' => $this->whenLoaded('technologist', fn() => $this->technologist?->name),
            'modality' => $this->modality,
            'procedure_code' => $this->procedure_code,
            'procedure_name' => $this->procedure_name,
            'body_part' => $this->body_part,
            'priority' => $this->priority,
            'clinical_indication' => $this->clinical_indication,
            'patient_preparation' => $this->patient_preparation,
            'is_pregnant_or_possible' => $this->is_pregnant_or_possible,
            'transport_mode' => $this->transport_mode,
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'scheduled_room' => $this->scheduled_room,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'dicom_study_uid' => $this->dicom_study_uid,
            'pacs_status' => $this->pacs_status,
            'notes' => $this->notes,
            'latest_report' => new ImagingReportResource($this->whenLoaded('latestReport')),
            'reports' => ImagingReportResource::collection($this->whenLoaded('reports')),
            'files' => ImagingFileResource::collection($this->whenLoaded('files')),
            'files_count' => $this->files_count ?? ($this->relationLoaded('files') ? $this->files->count() : 0),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
