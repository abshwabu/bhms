<?php

namespace App\Domain\Pharmacy\Http\Resources;

use App\Domain\Clinical\Http\Resources\PrescriptionResource;
use App\Domain\Patient\Http\Resources\PatientResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DispensingRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dispensation_number' => $this->dispensation_number,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'prescription_id' => $this->prescription_id,
            'patient_id' => $this->patient_id,
            'pharmacist_id' => $this->pharmacist_id,
            'status' => $this->status,
            'has_interaction_warnings' => $this->has_interaction_warnings,
            'interaction_alerts' => $this->interaction_alerts,
            'pharmacist_notes' => $this->pharmacist_notes,
            'counseling_notes' => $this->counseling_notes,
            'patient' => $this->whenLoaded('patient', function () {
                return [
                    'id' => $this->patient->id,
                    'mrn' => $this->patient->mrn,
                    'first_name' => $this->patient->first_name,
                    'last_name' => $this->patient->last_name,
                    'full_name' => $this->patient->full_name,
                    'gender' => $this->patient->gender,
                    'date_of_birth' => $this->patient->date_of_birth?->format('Y-m-d'),
                ];
            }),
            'pharmacist' => [
                'id' => $this->pharmacist?->id,
                'name' => $this->pharmacist?->name,
                'email' => $this->pharmacist?->email,
            ],
            'prescription' => new PrescriptionResource($this->whenLoaded('prescription')),
            'items' => DispensingRecordItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
