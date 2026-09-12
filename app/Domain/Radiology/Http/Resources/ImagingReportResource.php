<?php

namespace App\Domain\Radiology\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImagingReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_number' => $this->report_number,
            'imaging_order_id' => $this->imaging_order_id,
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
            'radiologist_id' => $this->radiologist_id,
            'radiologist' => $this->whenLoaded('radiologist', fn() => $this->radiologist?->name),
            'status' => $this->status,
            'clinical_indication' => $this->clinical_indication,
            'technique' => $this->technique,
            'comparison' => $this->comparison,
            'findings' => $this->findings,
            'impression' => $this->impression,
            'recommendations' => $this->recommendations,
            'critical_alert' => $this->critical_alert,
            'critical_alert_communicated_at' => $this->critical_alert_communicated_at?->toIso8601String(),
            'critical_alert_communicated_to' => $this->critical_alert_communicated_to,
            'digital_signature_hash' => $this->digital_signature_hash,
            'finalized_at' => $this->finalized_at?->toIso8601String(),
            'finalized_by' => $this->whenLoaded('finalizedByUser', fn() => $this->finalizedByUser?->name),
            'version' => $this->version,
            'is_amended' => $this->is_amended,
            'amended_from_id' => $this->amended_from_id,
            'amendment_reason' => $this->amendment_reason,
            'files' => ImagingFileResource::collection($this->whenLoaded('files')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
