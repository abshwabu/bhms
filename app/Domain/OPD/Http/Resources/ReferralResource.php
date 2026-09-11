<?php

namespace App\Domain\OPD\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReferralResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'referral_type' => $this->referral_type,
            'priority' => $this->priority,
            'reason_for_referral' => $this->reason_for_referral,
            'clinical_summary' => $this->clinical_summary,
            'status' => $this->status,

            'external_facility_name' => $this->external_facility_name,
            'external_specialist_name' => $this->external_specialist_name,
            'external_contact' => $this->external_contact,

            'patient' => $this->whenLoaded('patient', function () {
                return [
                    'id' => $this->patient->id,
                    'mrn' => $this->patient->mrn,
                    'full_name' => $this->patient->full_name,
                ];
            }),

            'referring_doctor' => $this->whenLoaded('referringDoctor', function () {
                return [
                    'id' => $this->referringDoctor->id,
                    'name' => $this->referringDoctor->name,
                ];
            }),

            'from_department' => $this->whenLoaded('fromDepartment', function () {
                return [
                    'id' => $this->fromDepartment->id,
                    'name' => $this->fromDepartment->name,
                ];
            }),

            'to_department' => $this->whenLoaded('toDepartment', function () {
                return [
                    'id' => $this->toDepartment->id,
                    'name' => $this->toDepartment->name,
                ];
            }),

            'to_doctor' => $this->whenLoaded('toDoctor', function () {
                return [
                    'id' => $this->toDoctor->id,
                    'name' => $this->toDoctor->name,
                ];
            }),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
