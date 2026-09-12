<?php

namespace App\Domain\IPD\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DischargeSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'admission_id' => $this->admission_id,
            'patient_id' => $this->patient_id,
            'discharging_doctor_id' => $this->discharging_doctor_id,

            // Auto-populated stay records
            'admission_date' => $this->admission_date?->toIso8601String(),
            'discharge_date' => $this->discharge_date?->toIso8601String(),
            'primary_diagnosis' => $this->primary_diagnosis,
            'secondary_diagnoses' => $this->secondary_diagnoses ?? [],
            'procedures_performed' => $this->procedures_performed ?? [],
            'medications_at_discharge' => $this->medications_at_discharge ?? [],

            'hospital_course_summary' => $this->hospital_course_summary,
            'discharge_condition' => $this->discharge_condition,
            'discharge_type' => $this->discharge_type,
            'follow_up_instructions' => $this->follow_up_instructions,
            'follow_up_date' => $this->follow_up_date?->format('Y-m-d'),
            'is_finalized' => $this->is_finalized,
            'finalized_at' => $this->finalized_at?->toIso8601String(),

            'discharging_doctor' => $this->whenLoaded('dischargingDoctor', fn () => [
                'id' => $this->dischargingDoctor->id,
                'name' => $this->dischargingDoctor->name,
            ]),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
