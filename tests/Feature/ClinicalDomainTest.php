<?php

namespace Tests\Feature;

use App\Domain\Clinical\Models\Diagnosis;
use App\Domain\Clinical\Models\EhrRecord;
use App\Domain\Clinical\Models\Icd10Code;
use App\Domain\Clinical\Models\LabOrder;
use App\Domain\Clinical\Models\Prescription;
use App\Domain\Clinical\Models\RadiologyOrder;
use App\Domain\OPD\Models\Appointment;
use App\Domain\Patient\Models\Patient;
use App\Domain\Patient\Models\PatientAllergy;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClinicalDomainTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;
    protected Branch $branch;
    protected User $doctor;
    protected User $otherDoctor;
    protected Patient $patient;
    protected Patient $allergicPatient;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Organization & Branch
        $this->org = Organization::create([
            'id' => (string) Str::uuid(),
            'name' => 'Metro Health System',
            'code' => 'MHS-' . Str::random(4),
            'tax_number' => 'TAX-MHS-1122',
        ]);

        $this->branch = Branch::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'name' => 'Metro General Hospital',
            'code' => 'MAIN',
        ]);

        // 2. Setup Doctor Users
        $this->doctor = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Dr. Eleanor Vance',
            'email' => 'dr_vance_' . Str::random(5) . '@metrohealth.org',
            'password' => bcrypt('password123'),
        ]);

        $this->otherDoctor = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Dr. Marcus Brody',
            'email' => 'dr_brody_' . Str::random(5) . '@metrohealth.org',
            'password' => bcrypt('password123'),
        ]);

        // 3. Setup Standard Patients
        $this->patient = Patient::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'mrn' => 'MRN-2026-TEST-0001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '1985-05-15',
            'gender' => 'male',
            'blood_group' => 'O+',
        ]);

        $this->allergicPatient = Patient::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'mrn' => 'MRN-2026-TEST-0002',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'date_of_birth' => '1990-09-20',
            'gender' => 'female',
            'blood_group' => 'A+',
        ]);

        // Record Penicillin Allergy for Jane Smith
        PatientAllergy::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->allergicPatient->id,
            'allergen' => 'Penicillin',
            'allergen_type' => 'drug',
            'reaction' => 'Anaphylaxis and respiratory distress',
            'severity' => 'severe',
            'status' => 'active',
        ]);

        // 4. Seed Standard ICD-10 Master Codes
        Icd10Code::create([
            'id' => (string) Str::uuid(),
            'code' => 'E11.9',
            'description' => 'Type 2 diabetes mellitus without complications',
            'category' => 'Endocrine diseases',
            'chapter' => 'IV',
            'is_billable' => true,
            'is_active' => true,
        ]);

        Icd10Code::create([
            'id' => (string) Str::uuid(),
            'code' => 'I10',
            'description' => 'Essential (primary) hypertension',
            'category' => 'Circulatory diseases',
            'chapter' => 'IX',
            'is_billable' => true,
            'is_active' => true,
        ]);

        Icd10Code::create([
            'id' => (string) Str::uuid(),
            'code' => 'J45.909',
            'description' => 'Unspecified asthma, uncomplicated',
            'category' => 'Respiratory diseases',
            'chapter' => 'X',
            'is_billable' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Helper to authenticate doctor and set branch headers.
     */
    protected function actingAsDoctor(?User $doc = null)
    {
        $user = $doc ?? $this->doctor;
        Sanctum::actingAs($user);

        return $this->withHeaders([
            'X-Branch-ID' => $this->branch->id,
            'Accept' => 'application/json',
        ]);
    }

    // =========================================================================
    // 1. ICD-10 CODE VALIDATION TESTS
    // =========================================================================

    public function test_can_search_standard_icd10_codes(): void
    {
        $response = $this->actingAsDoctor()->getJson('/api/v1/clinical/icd10?search=diabetes');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.code', 'E11.9');
    }

    public function test_validates_standard_icd10_code_successfully(): void
    {
        $response = $this->actingAsDoctor()->postJson('/api/v1/clinical/icd10/validate', [
            'code' => 'E11.9',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.code', 'E11.9');
    }

    public function test_rejects_non_standard_or_unregistered_icd10_code(): void
    {
        $response = $this->actingAsDoctor()->postJson('/api/v1/clinical/icd10/validate', [
            'code' => 'INVALID-999',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'INVALID_ICD10_CODE');
    }

    public function test_doctor_can_record_diagnosis_with_valid_icd10_code(): void
    {
        $response = $this->actingAsDoctor()->postJson('/api/v1/clinical/diagnoses', [
            'patient_id' => $this->patient->id,
            'icd10_code' => 'E11.9',
            'type' => 'primary',
            'severity' => 'moderate',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
            'notes' => 'Confirmed on HbA1c testing.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.icd10_code', 'E11.9')
            ->assertJsonPath('data.icd10_title', 'Type 2 diabetes mellitus without complications');

        $this->assertDatabaseHas('diagnoses', [
            'patient_id' => $this->patient->id,
            'icd10_code' => 'E11.9',
        ]);
    }

    public function test_diagnosis_recording_fails_for_invalid_icd10_code(): void
    {
        $response = $this->actingAsDoctor()->postJson('/api/v1/clinical/diagnoses', [
            'patient_id' => $this->patient->id,
            'icd10_code' => 'FAKE.999',
            'type' => 'primary',
        ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // 2. EHR RECORDS: APPEND-ONLY & VERSIONING TESTS
    // =========================================================================

    public function test_doctor_can_create_draft_and_finalize_ehr_record(): void
    {
        // 1. Create Draft Note
        $createResponse = $this->actingAsDoctor()->postJson('/api/v1/clinical/ehr', [
            'patient_id' => $this->patient->id,
            'title' => 'Initial Consultation Note',
            'record_type' => 'consultation_note',
            'category' => 'general',
            'clinical_notes' => [
                'chief_complaint' => 'Headache and blurred vision',
                'subjective' => 'Patient has had headache for 3 days',
                'assessment' => 'Possible migraine vs hypertension',
                'plan' => 'Check blood pressure and prescribe analgesia',
            ],
            'vitals' => [
                'bp_systolic' => 142,
                'bp_diastolic' => 90,
                'heart_rate' => 80,
            ],
            'status' => 'draft',
        ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.version', 1);

        $ehrId = $createResponse->json('data.id');

        // 2. Finalize Note
        $finalizeResponse = $this->actingAsDoctor()->postJson("/api/v1/clinical/ehr/{$ehrId}/finalize");

        $finalizeResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'finalized')
            ->assertJsonPath('data.version', 1);

        $this->assertDatabaseHas('ehr_records', [
            'id' => $ehrId,
            'status' => 'finalized',
        ]);
    }

    public function test_finalized_ehr_record_is_immutable_against_direct_updates(): void
    {
        $ehr = EhrRecord::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'author_id' => $this->doctor->id,
            'title' => 'Locked Record',
            'clinical_notes' => ['plan' => 'Original Plan'],
            'status' => 'finalized',
            'version' => 1,
            'finalized_at' => now(),
            'finalized_by' => $this->doctor->id,
        ]);

        // Attempting to modify clinical content on finalized record throws DomainException
        $this->expectException(DomainException::class);
        $ehr->update([
            'clinical_notes' => ['plan' => 'Directly Overwritten Plan'],
        ]);
    }

    public function test_amending_finalized_ehr_record_creates_append_only_new_version(): void
    {
        $original = EhrRecord::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'author_id' => $this->doctor->id,
            'title' => 'Original Assessment Note',
            'clinical_notes' => ['assessment' => 'Asthma initial assessment'],
            'status' => 'finalized',
            'version' => 1,
            'finalized_at' => now(),
            'finalized_by' => $this->doctor->id,
        ]);

        // Amend the record
        $amendResponse = $this->actingAsDoctor()->postJson("/api/v1/clinical/ehr/{$original->id}/amend", [
            'amendment_reason' => 'Corrected peak flow measurement and updated inhaler frequency.',
            'clinical_notes' => ['assessment' => 'Amended Asthma assessment: moderate persistent with updated spirometry.'],
        ]);

        $amendResponse->assertStatus(201)
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.amended_from_id', $original->id)
            ->assertJsonPath('data.amendment_reason', 'Corrected peak flow measurement and updated inhaler frequency.');

        // Original record is marked amended but preserved intact in database
        $this->assertDatabaseHas('ehr_records', [
            'id' => $original->id,
            'status' => 'amended',
            'is_amended' => true,
        ]);

        // New version exists
        $this->assertDatabaseHas('ehr_records', [
            'amended_from_id' => $original->id,
            'version' => 2,
            'status' => 'finalized',
        ]);
    }

    public function test_retrieves_full_longitudinal_patient_clinical_timeline(): void
    {
        // Add note, diagnosis, and prescription for patient
        EhrRecord::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'author_id' => $this->doctor->id,
            'title' => 'General Consult Note',
            'status' => 'finalized',
            'version' => 1,
        ]);

        Diagnosis::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'icd10_code' => 'I10',
            'icd10_title' => 'Essential (primary) hypertension',
        ]);

        $response = $this->actingAsDoctor()->getJson("/api/v1/clinical/patients/{$this->patient->id}/ehr-timeline");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.patient.id', $this->patient->id)
            ->assertJsonStructure([
                'data' => [
                    'patient',
                    'summary',
                    'timeline',
                ]
            ]);

        $this->assertGreaterThanOrEqual(2, count($response->json('data.timeline')));
    }

    // =========================================================================
    // 3. CLINICAL DECISION SUPPORT & DRUG INTERACTION CHECKS
    // =========================================================================

    public function test_cds_detects_drug_allergy_contraindication(): void
    {
        // Jane Smith is documented allergic to Penicillin
        $response = $this->actingAsDoctor()->postJson('/api/v1/clinical/prescriptions/check-interactions', [
            'patient_id' => $this->allergicPatient->id,
            'items' => [
                [
                    'medication_name' => 'Amoxicillin 500mg',
                    'generic_name' => 'Amoxicillin',
                ]
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.has_high_severity', true)
            ->assertJsonPath('data.can_finalize_without_override', false)
            ->assertJsonPath('data.alerts.0.type', 'drug_allergy');
    }

    public function test_cds_detects_drug_drug_interaction_within_order(): void
    {
        // Warfarin + Aspirin is a high-severity bleeding risk
        $response = $this->actingAsDoctor()->postJson('/api/v1/clinical/prescriptions/check-interactions', [
            'patient_id' => $this->patient->id,
            'items' => [
                ['medication_name' => 'Warfarin 5mg', 'generic_name' => 'Warfarin sodium'],
                ['medication_name' => 'Aspirin 81mg', 'generic_name' => 'Acetylsalicylic acid'],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.has_high_severity', true)
            ->assertJsonPath('data.can_finalize_without_override', false);

        $alerts = $response->json('data.alerts');
        $this->assertTrue(collect($alerts)->contains('type', 'drug_drug'));
    }

    public function test_cds_detects_therapeutic_duplication(): void
    {
        // Ibuprofen + Naproxen (two NSAIDs)
        $response = $this->actingAsDoctor()->postJson('/api/v1/clinical/prescriptions/check-interactions', [
            'patient_id' => $this->patient->id,
            'items' => [
                ['medication_name' => 'Ibuprofen 400mg', 'generic_name' => 'Ibuprofen'],
                ['medication_name' => 'Naproxen 500mg', 'generic_name' => 'Naproxen'],
            ],
        ]);

        $response->assertStatus(200);
        $alerts = $response->json('data.alerts');
        $this->assertTrue(collect($alerts)->contains('type', 'therapeutic_duplication'));
    }

    // =========================================================================
    // 4. PRESCRIPTION FINALIZATION SAFETY OVERRIDE ENFORCEMENT
    // =========================================================================

    public function test_cannot_finalize_prescription_with_severe_warnings_without_override(): void
    {
        // Prescribing Amoxicillin to patient allergic to Penicillin with status 'finalized'
        $response = $this->actingAsDoctor()->postJson('/api/v1/clinical/prescriptions', [
            'patient_id' => $this->allergicPatient->id,
            'status' => 'finalized',
            'items' => [
                [
                    'medication_name' => 'Amoxicillin',
                    'dosage' => '500 mg',
                    'frequency' => 'TID',
                    'duration_days' => 7,
                    'quantity' => 21,
                ]
            ],
        ]);

        // Must reject with 422 SAFETY_WARNINGS_DETECTED
        $response->assertStatus(422)
            ->assertJsonPath('code', 'SAFETY_WARNINGS_DETECTED');
    }

    public function test_can_finalize_prescription_with_severe_warnings_when_clinical_override_provided(): void
    {
        $response = $this->actingAsDoctor()->postJson('/api/v1/clinical/prescriptions', [
            'patient_id' => $this->allergicPatient->id,
            'status' => 'finalized',
            'override_reason' => 'Patient desensitization protocol completed in ICU; continuous monitoring.',
            'items' => [
                [
                    'medication_name' => 'Amoxicillin',
                    'dosage' => '500 mg',
                    'frequency' => 'TID',
                    'duration_days' => 7,
                    'quantity' => 21,
                ]
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'finalized')
            ->assertJsonPath('data.has_safety_warnings', true)
            ->assertJsonPath('data.override_reason', 'Patient desensitization protocol completed in ICU; continuous monitoring.');
    }

    // =========================================================================
    // 5. DIAGNOSTIC ORDER ENTRY DIRECTLY FROM CONSULTATION
    // =========================================================================

    public function test_doctor_can_order_lab_test_and_review_results(): void
    {
        // 1. Place Lab Order
        $orderResponse = $this->actingAsDoctor()->postJson('/api/v1/clinical/lab-orders', [
            'patient_id' => $this->patient->id,
            'test_type' => 'Complete Blood Count (CBC)',
            'priority' => 'urgent',
            'clinical_indication' => 'Severe fatigue and pale conjunctiva',
        ]);

        $orderResponse->assertStatus(201)
            ->assertJsonPath('data.status', 'ordered')
            ->assertJsonPath('data.priority', 'urgent');

        $orderId = $orderResponse->json('data.id');

        // 2. Laboratory Reports Results
        $resultResponse = $this->actingAsDoctor()->patchJson("/api/v1/clinical/lab-orders/{$orderId}/results", [
            'results_summary' => 'Hemoglobin low at 8.2 g/dL (Normal: 12.0 - 15.5 g/dL). Microcytic anemia.',
            'abnormal_flags' => true,
            'status' => 'completed',
        ]);

        $resultResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.abnormal_flags', true);

        // 3. Clinician Reviews and Signs Off
        $reviewResponse = $this->actingAsDoctor()->postJson("/api/v1/clinical/lab-orders/{$orderId}/review", [
            'notes' => 'Reviewed. Noted severe iron deficiency anemia; initiating oral iron therapy.',
        ]);

        $reviewResponse->assertStatus(200)
            ->assertJsonPath('data.doctor_review_notes', 'Reviewed. Noted severe iron deficiency anemia; initiating oral iron therapy.');

        $this->assertNotNull(LabOrder::find($orderId)->reviewed_by_doctor_at);
    }

    public function test_doctor_can_order_radiology_study_and_review_report(): void
    {
        // 1. Place Radiology Order
        $orderResponse = $this->actingAsDoctor()->postJson('/api/v1/clinical/radiology-orders', [
            'patient_id' => $this->patient->id,
            'modality' => 'X-Ray',
            'body_part' => 'Chest',
            'procedure_name' => 'Chest X-Ray PA & Lateral',
            'priority' => 'routine',
            'clinical_indication' => 'Persistent cough for 2 weeks',
            'is_pregnant_or_possible' => false,
        ]);

        $orderResponse->assertStatus(201)
            ->assertJsonPath('data.status', 'ordered')
            ->assertJsonPath('data.modality', 'X-Ray');

        $orderId = $orderResponse->json('data.id');

        // 2. Radiologist Reports Findings
        $reportResponse = $this->actingAsDoctor()->patchJson("/api/v1/clinical/radiology-orders/{$orderId}/report", [
            'findings' => 'Bilateral lung fields clear. No effusion or consolidation.',
            'impression' => 'Normal chest radiograph.',
            'status' => 'reported',
        ]);

        $reportResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'reported');

        // 3. Clinician Reviews and Signs Off
        $reviewResponse = $this->actingAsDoctor()->postJson("/api/v1/clinical/radiology-orders/{$orderId}/review", [
            'notes' => 'Chest clear. Rule out pneumonia.',
        ]);

        $reviewResponse->assertStatus(200);
        $this->assertNotNull(RadiologyOrder::find($orderId)->reviewed_by_doctor_at);
    }

    // =========================================================================
    // 6. DOCTOR'S PERSONAL DASHBOARD TESTS
    // =========================================================================

    public function test_doctor_dashboard_returns_seen_patients_and_pending_reviews(): void
    {
        // 1. Create Today's appointment for this doctor
        Appointment::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_number' => 'APT-' . Str::random(6),
            'appointment_date' => Carbon::today(),
            'start_time' => '09:00:00',
            'end_time' => '09:30:00',
            'status' => 'completed', // Seen today!
        ]);

        // 2. Create a draft note for this doctor (Pending chart review!)
        EhrRecord::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'author_id' => $this->doctor->id,
            'title' => 'Pending Review Note',
            'status' => 'draft',
            'version' => 1,
        ]);

        $response = $this->actingAsDoctor()->getJson('/api/v1/clinical/doctor/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.metrics.patients_seen_today', 1)
            ->assertJsonPath('data.metrics.pending_chart_reviews', 1)
            ->assertJsonStructure([
                'data' => [
                    'doctor',
                    'metrics' => [
                        'patients_seen_today',
                        'scheduled_today',
                        'pending_chart_reviews',
                        'pending_diagnostic_reviews',
                        'active_prescriptions_today',
                    ],
                    'patient_roster',
                    'pending_chart_reviews',
                    'pending_diagnostic_reviews',
                ]
            ]);
    }
}
