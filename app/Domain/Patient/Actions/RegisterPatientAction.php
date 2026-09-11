<?php

namespace App\Domain\Patient\Actions;

use App\Domain\Patient\Events\PatientRegisteredEvent;
use App\Domain\Patient\Models\Patient;
use App\Domain\Patient\Models\PatientAllergy;
use App\Domain\Patient\Models\PatientHistory;
use App\Domain\Patient\Models\PatientInsurance;
use App\Domain\Patient\Models\PatientRelationship;
use App\Domain\Patient\Services\MrnGeneratorService;
use App\Domain\Shared\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterPatientAction
{
    public function __construct(protected MrnGeneratorService $mrnGenerator)
    {
    }

    /**
     * Execute patient registration workflow across walk-in, referral, or emergency.
     */
    public function execute(array $data, Branch $branch, ?string $userId = null): Patient
    {
        return DB::transaction(function () use ($data, $branch, $userId) {
            $registrationType = $data['registration_type'] ?? 'walk_in';

            // 1. Generate atomic, collision-free MRN
            $mrn = $this->mrnGenerator->generate($branch, $registrationType);

            // 2. Build patient record
            $patient = Patient::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'mrn' => $mrn,
                'registration_type' => $registrationType,
                'triage_level' => $data['triage_level'] ?? null,
                'referral_source' => $data['referral_source'] ?? null,

                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'] ?? ($registrationType === 'emergency' ? 'Unknown' : ''),
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'is_dob_estimated' => $data['is_dob_estimated'] ?? false,
                'gender' => $data['gender'] ?? 'unknown',
                'blood_group' => $data['blood_group'] ?? null,

                'national_id' => $data['national_id'] ?? null,
                'passport_number' => $data['passport_number'] ?? null,
                'phone' => $data['phone'] ?? null,
                'alternate_phone' => $data['alternate_phone'] ?? null,
                'email' => $data['email'] ?? null,
                'marital_status' => $data['marital_status'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'preferred_language' => $data['preferred_language'] ?? 'English',

                'address' => $data['address'] ?? [],
                'emergency_contact' => $data['emergency_contact'] ?? [],
                'notes' => $data['notes'] ?? null,

                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // 3. Create initial medical history if provided
            if (!empty($data['initial_history']) && is_array($data['initial_history'])) {
                foreach ($data['initial_history'] as $historyItem) {
                    PatientHistory::create([
                        'id' => (string) Str::uuid(),
                        'organization_id' => $branch->organization_id,
                        'branch_id' => $branch->id,
                        'patient_id' => $patient->id,
                        'category' => $historyItem['category'],
                        'condition_or_procedure' => $historyItem['condition_or_procedure'],
                        'icd10_code' => $historyItem['icd10_code'] ?? null,
                        'diagnosed_date' => $historyItem['diagnosed_date'] ?? null,
                        'status' => $historyItem['status'] ?? 'active',
                        'severity' => $historyItem['severity'] ?? null,
                        'notes' => $historyItem['notes'] ?? null,
                        'recorded_by' => $userId,
                    ]);
                }
            }

            // 4. Create initial allergies if provided
            if (!empty($data['initial_allergies']) && is_array($data['initial_allergies'])) {
                foreach ($data['initial_allergies'] as $allergyItem) {
                    PatientAllergy::create([
                        'id' => (string) Str::uuid(),
                        'organization_id' => $branch->organization_id,
                        'branch_id' => $branch->id,
                        'patient_id' => $patient->id,
                        'allergen' => $allergyItem['allergen'],
                        'allergen_type' => $allergyItem['allergen_type'] ?? 'drug',
                        'reaction' => $allergyItem['reaction'],
                        'severity' => $allergyItem['severity'],
                        'status' => 'active',
                        'diagnosed_at' => $allergyItem['diagnosed_at'] ?? now()->toDateString(),
                        'notes' => $allergyItem['notes'] ?? null,
                        'recorded_by' => $userId,
                    ]);
                }
            }

            // 5. Create initial insurance if provided
            if (!empty($data['initial_insurance']) && is_array($data['initial_insurance'])) {
                $ins = $data['initial_insurance'];
                if (!empty($ins['provider_name']) && !empty($ins['policy_number'])) {
                    PatientInsurance::create([
                        'id' => (string) Str::uuid(),
                        'organization_id' => $branch->organization_id,
                        'branch_id' => $branch->id,
                        'patient_id' => $patient->id,
                        'provider_name' => $ins['provider_name'],
                        'policy_number' => $ins['policy_number'],
                        'group_number' => $ins['group_number'] ?? null,
                        'coverage_type' => $ins['coverage_type'] ?? 'primary',
                        'coverage_percentage' => $ins['coverage_percentage'] ?? 100.00,
                        'copay_amount_cents' => $ins['copay_amount_cents'] ?? 0,
                        'valid_from' => $ins['valid_from'] ?? now()->toDateString(),
                        'valid_until' => $ins['valid_until'] ?? null,
                        'pre_auth_required' => $ins['pre_auth_required'] ?? false,
                        'status' => 'active',
                        'notes' => $ins['notes'] ?? null,
                    ]);
                }
            }

            // 6. Create initial dependent / family link if provided
            if (!empty($data['initial_relationship']) && is_array($data['initial_relationship'])) {
                $rel = $data['initial_relationship'];
                PatientRelationship::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $branch->organization_id,
                    'branch_id' => $branch->id,
                    'patient_id' => $patient->id,
                    'related_patient_id' => $rel['related_patient_id'] ?? null,
                    'relationship_type' => $rel['relationship_type'],
                    'is_guardian' => $rel['is_guardian'] ?? false,
                    'is_emergency_contact' => $rel['is_emergency_contact'] ?? false,
                    'is_billing_guarantor' => $rel['is_billing_guarantor'] ?? false,
                    'external_name' => $rel['external_name'] ?? null,
                    'external_phone' => $rel['external_phone'] ?? null,
                    'external_national_id' => $rel['external_national_id'] ?? null,
                    'notes' => $rel['notes'] ?? null,
                ]);
            }

            // Emit Domain Event
            event(new PatientRegisteredEvent($patient));

            return $patient->load(['medicalHistory', 'allergies', 'insurance', 'relationships']);
        });
    }
}
