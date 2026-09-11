<?php

namespace App\Domain\Patient\Http\Resources;

use App\Domain\Patient\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Patient
 */
class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $canViewClinical = $user && $user->can('viewMedicalHistory', $this->resource);

        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'mrn' => $this->mrn,
            'registration_type' => $this->registration_type,
            'triage_level' => $this->triage_level,
            'referral_source' => $this->referral_source,

            // Demographics
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'date_of_birth' => $this->date_of_birth ? $this->date_of_birth->format('Y-m-d') : null,
            'is_dob_estimated' => $this->is_dob_estimated,
            'age' => $this->age,
            'gender' => $this->gender,
            'blood_group' => $this->blood_group,

            // PII & Contact
            'national_id' => $this->national_id,
            'passport_number' => $this->passport_number,
            'phone' => $this->phone,
            'alternate_phone' => $this->alternate_phone,
            'email' => $this->email,
            'marital_status' => $this->marital_status,
            'occupation' => $this->occupation,
            'preferred_language' => $this->preferred_language,
            'address' => $this->address ?? [],
            'emergency_contact' => $this->emergency_contact ?? [],

            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'portal_user_id' => $this->portal_user_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Conditional Relational Embeds
            'medical_history' => $this->when($canViewClinical && $this->relationLoaded('medicalHistory'), function () {
                return PatientHistoryResource::collection($this->medicalHistory);
            }),
            'allergies' => $this->when($canViewClinical && $this->relationLoaded('allergies'), function () {
                return PatientAllergyResource::collection($this->allergies);
            }),
            'insurance' => $this->when($this->relationLoaded('insurance'), function () {
                return PatientInsuranceResource::collection($this->insurance);
            }),
            'relationships' => $this->when($this->relationLoaded('relationships'), function () {
                return PatientRelationshipResource::collection($this->relationships);
            }),
        ];
    }
}
