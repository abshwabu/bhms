<?php

namespace App\Domain\IPD\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VitalsLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'admission_id' => $this->admission_id,
            'patient_id' => $this->patient_id,
            'recorded_by' => $this->recorded_by,
            'recorded_at' => $this->recorded_at?->toIso8601String(),
            'bp_systolic' => $this->bp_systolic,
            'bp_diastolic' => $this->bp_diastolic,
            'bp_display' => ($this->bp_systolic && $this->bp_diastolic) ? "{$this->bp_systolic}/{$this->bp_diastolic} mmHg" : null,
            'heart_rate' => $this->heart_rate,
            'respiratory_rate' => $this->respiratory_rate,
            'temperature_c' => $this->temperature_c,
            'spo2' => $this->spo2,
            'blood_glucose_mg_dl' => $this->blood_glucose_mg_dl,
            'pain_score' => $this->pain_score,
            'consciousness_level' => $this->consciousness_level,
            'urine_output_ml' => $this->urine_output_ml,
            'nursing_notes' => $this->nursing_notes,

            'nurse' => $this->whenLoaded('recordedByUser', fn () => [
                'id' => $this->recordedByUser->id,
                'name' => $this->recordedByUser->name,
            ]),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
