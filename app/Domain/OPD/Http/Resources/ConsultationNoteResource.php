<?php

namespace App\Domain\OPD\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'patient_id' => $this->patient_id,
            'version' => $this->version,
            'parent_note_id' => $this->parent_note_id,

            // Subjective
            'chief_complaint' => $this->chief_complaint,
            'history_of_presenting_illness' => $this->history_of_presenting_illness,
            'review_of_systems' => $this->review_of_systems,

            // Objective
            'vitals' => $this->vitals,
            'physical_examination' => $this->physical_examination,

            // Assessment
            'provisional_diagnosis' => $this->provisional_diagnosis,
            'differential_diagnoses' => $this->differential_diagnoses,
            'icd10_codes' => $this->icd10_codes,

            // Plan
            'treatment_plan' => $this->treatment_plan,
            'prescriptions_advice' => $this->prescriptions_advice,
            'orders_requested' => $this->orders_requested,
            'diet_and_lifestyle_advice' => $this->diet_and_lifestyle_advice,
            'follow_up_recommended_date' => $this->follow_up_recommended_date?->format('Y-m-d'),
            'follow_up_instructions' => $this->follow_up_instructions,

            // Immutability Ledger
            'is_signed_off' => $this->is_signed_off,
            'signed_off_at' => $this->signed_off_at?->toIso8601String(),
            'notes_status' => $this->notes_status,
            'amendment_reason' => $this->amendment_reason,

            'doctor' => $this->whenLoaded('doctor', function () {
                return [
                    'id' => $this->doctor->id,
                    'name' => $this->doctor->name,
                ];
            }),

            'patient' => $this->whenLoaded('patient', function () {
                return [
                    'id' => $this->patient->id,
                    'mrn' => $this->patient->mrn,
                    'full_name' => $this->patient->full_name,
                ];
            }),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
