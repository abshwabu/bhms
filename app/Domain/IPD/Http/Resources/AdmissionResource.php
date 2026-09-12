<?php

namespace App\Domain\IPD\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'admission_number' => $this->admission_number,
            'patient_id' => $this->patient_id,
            'ward_id' => $this->ward_id,
            'bed_id' => $this->bed_id,
            'admitting_doctor_id' => $this->admitting_doctor_id,
            'attending_doctor_id' => $this->attending_doctor_id,
            'admitted_by' => $this->admitted_by,
            'admission_type' => $this->admission_type,
            'status' => $this->status,
            'admitted_at' => $this->admitted_at?->toIso8601String(),
            'discharged_at' => $this->discharged_at?->toIso8601String(),
            'discharged_by' => $this->discharged_by,
            'discharge_type' => $this->discharge_type,
            'admitting_diagnosis' => $this->admitting_diagnosis,
            'primary_diagnosis' => $this->primary_diagnosis,
            'secondary_diagnoses' => $this->secondary_diagnoses ?? [],
            'procedures_performed' => $this->procedures_performed ?? [],
            'chief_complaint' => $this->chief_complaint,
            'initial_vitals' => $this->initial_vitals ?? [],
            'length_of_stay_days' => $this->length_of_stay_days,
            'notes' => $this->notes,

            'patient' => $this->whenLoaded('patient', fn () => [
                'id' => $this->patient->id,
                'mrn' => $this->patient->mrn,
                'full_name' => $this->patient->full_name,
                'gender' => $this->patient->gender,
                'age' => $this->patient->age,
                'blood_group' => $this->patient->blood_group,
            ]),

            'ward' => $this->whenLoaded('ward', fn () => [
                'id' => $this->ward->id,
                'name' => $this->ward->name,
                'code' => $this->ward->code,
                'ward_type' => $this->ward->ward_type,
            ]),

            'bed' => $this->whenLoaded('bed', fn () => [
                'id' => $this->bed->id,
                'bed_number' => $this->bed->bed_number,
                'bed_type' => $this->bed->bed_type,
                'status' => $this->bed->status,
            ]),

            'admitting_doctor' => $this->whenLoaded('admittingDoctor', fn () => [
                'id' => $this->admittingDoctor->id,
                'name' => $this->admittingDoctor->name,
            ]),

            'admitted_by_user' => $this->whenLoaded('admittedByUser', fn () => [
                'id' => $this->admittedByUser->id,
                'name' => $this->admittedByUser->name,
            ]),

            'discharged_by_user' => $this->whenLoaded('dischargedByUser', fn () => [
                'id' => $this->dischargedByUser->id,
                'name' => $this->dischargedByUser->name,
            ]),

            'discharge_summary' => new DischargeSummaryResource($this->whenLoaded('dischargeSummary')),
            'transfers' => BedTransferResource::collection($this->whenLoaded('transfers')),
            'vitals_logs' => VitalsLogResource::collection($this->whenLoaded('vitalsLogs')),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
