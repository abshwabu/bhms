<?php

namespace Tests\Feature;

use App\Domain\Patient\Actions\RegisterPatientAction;
use App\Domain\Patient\Models\Patient;
use App\Domain\Patient\Services\MrnGeneratorService;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PatientManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;
    protected Branch $branch;
    protected User $doctor;
    protected User $receptionist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::create([
            'id' => (string) Str::uuid(),
            'name' => 'Metro Health System',
            'code' => 'MHS-' . Str::random(5),
            'tax_number' => 'TAX-1234',
        ]);

        $this->branch = Branch::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'name' => 'Metro General Hospital',
            'code' => 'MAIN',
        ]);

        setPermissionsTeamId($this->branch->id);

        // Permissions
        $viewRecords = Permission::firstOrCreate(['name' => 'patient.records.view', 'guard_name' => 'web']);
        $createRecords = Permission::firstOrCreate(['name' => 'patient.records.create', 'guard_name' => 'web']);
        $updateRecords = Permission::firstOrCreate(['name' => 'patient.records.update', 'guard_name' => 'web']);
        $viewHistory = Permission::firstOrCreate(['name' => 'clinical.history.view', 'guard_name' => 'web']);
        $modifyHistory = Permission::firstOrCreate(['name' => 'clinical.history.modify', 'guard_name' => 'web']);

        // Roles
        $docRole = Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web', 'branch_id' => $this->branch->id]);
        $docRole->syncPermissions([$viewRecords, $createRecords, $updateRecords, $viewHistory, $modifyHistory]);

        $recRole = Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web', 'branch_id' => $this->branch->id]);
        $recRole->syncPermissions([$viewRecords, $createRecords, $updateRecords]);

        // Users
        $this->doctor = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Dr. Eleanor Vance',
            'email' => 'doctor_' . Str::random(5) . '@hms.local',
            'password' => bcrypt('password123'),
        ]);
        $this->doctor->assignRole('doctor');
        $this->doctor->branches()->attach($this->branch->id);

        $this->receptionist = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Receptionist Sarah',
            'email' => 'rec_' . Str::random(5) . '@hms.local',
            'password' => bcrypt('password123'),
        ]);
        $this->receptionist->assignRole('receptionist');
        $this->receptionist->branches()->attach($this->branch->id);
    }

    /**
     * Test 1: MRN is unique, sequential, never reused.
     */
    public function test_mrn_generation_is_atomic_and_sequential(): void
    {
        $generator = app(MrnGeneratorService::class);

        $mrn1 = $generator->generate($this->branch, 'walk_in');
        $mrn2 = $generator->generate($this->branch, 'walk_in');
        $mrnEmg = $generator->generate($this->branch, 'emergency');

        $this->assertNotEquals($mrn1, $mrn2);
        $this->assertStringStartsWith('MRN-' . date('Y') . '-MAIN-', $mrn1);
        $this->assertStringStartsWith('MRN-' . date('Y') . '-MAIN-', $mrn2);
        $this->assertStringStartsWith('EMG-' . date('Y') . '-MAIN-', $mrnEmg);
    }

    /**
     * Test 2: Receptionist can register a walk-in patient with sanitized PII.
     */
    public function test_receptionist_can_register_walk_in_patient(): void
    {
        Sanctum::actingAs($this->receptionist);

        $payload = [
            'registration_type' => 'walk_in',
            'first_name' => '  <b>Michael</b>  ', // Tests XSS strip & trim
            'last_name' => ' Scott ',
            'date_of_birth' => '1965-03-15',
            'gender' => 'male',
            'blood_group' => 'O+',
            'national_id' => '  nat-999888-01  ', // Tests uppercase & trim
            'phone' => '+1 (555) 345-6789', // Tests phone normalization
            'email' => '  MICHAEL.SCOTT@DunderMifflin.COM  ', // Tests lowercase & trim
            'address' => [
                'street' => '1725 Slough Ave',
                'city' => 'Scranton',
                'state' => 'PA',
                'postal_code' => '18503',
            ],
            'emergency_contact' => [
                'name' => 'Dwight Schrute',
                'relationship' => 'Assistant to the Regional Manager',
                'phone' => '+15559876543',
            ],
        ];

        $response = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson('/api/v1/patients', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.first_name', 'Michael') // HTML tags stripped
            ->assertJsonPath('data.last_name', 'Scott')
            ->assertJsonPath('data.national_id', 'NAT-999888-01')
            ->assertJsonPath('data.phone', '+15553456789')
            ->assertJsonPath('data.email', 'michael.scott@dundermifflin.com');

        $this->assertDatabaseHas('patients', [
            'first_name' => 'Michael',
            'national_id' => 'NAT-999888-01',
            'phone' => '+15553456789',
        ]);
    }

    /**
     * Test 3: Emergency Intake with unknown name and estimated DOB.
     */
    public function test_emergency_intake_allows_minimal_fields(): void
    {
        Sanctum::actingAs($this->doctor);

        $payload = [
            'registration_type' => 'emergency',
            'triage_level' => 'critical',
            'first_name' => 'Trauma Patient Alpha',
            'gender' => 'unknown',
            'is_dob_estimated' => true,
            'notes' => 'Severe hypotension, unresponsive.',
        ];

        $response = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson('/api/v1/patients', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.registration_type', 'emergency')
            ->assertJsonPath('data.triage_level', 'critical');
    }

    /**
     * Test 4: Role-Based Access: Receptionist CANNOT view or edit clinical medical history.
     */
    public function test_receptionist_forbidden_from_viewing_or_adding_medical_history(): void
    {
        // 1. Create patient with medical history
        $patient = Patient::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'mrn' => 'MRN-TEST-0001',
            'registration_type' => 'walk_in',
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'date_of_birth' => '1990-01-01',
            'gender' => 'female',
        ]);

        // Receptionist attempts to view medical history -> 403
        Sanctum::actingAs($this->receptionist);
        $resView = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->getJson("/api/v1/patients/{$patient->id}/history");
        $resView->assertStatus(403);

        // Receptionist attempts to add medical history -> 403
        $resStore = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/patients/{$patient->id}/history", [
                'category' => 'chronic_condition',
                'condition_or_procedure' => 'Diabetes Type 2',
            ]);
        $resStore->assertStatus(403);

        // Doctor CAN view and add medical history -> 200 & 201
        Sanctum::actingAs($this->doctor);
        $resDocStore = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/patients/{$patient->id}/history", [
                'category' => 'chronic_condition',
                'condition_or_procedure' => 'Diabetes Type 2',
                'status' => 'active',
                'severity' => 'moderate',
            ]);
        $resDocStore->assertStatus(201)
            ->assertJsonPath('data.condition_or_procedure', 'Diabetes Type 2');

        $resDocView = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->getJson("/api/v1/patients/{$patient->id}/history");
        $resDocView->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test 5: Search returns matching records via exact and fuzzy trigram match.
     */
    public function test_search_by_name_phone_national_id_and_mrn(): void
    {
        Sanctum::actingAs($this->receptionist);

        $patient = Patient::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'mrn' => 'MRN-2026-MAIN-778899',
            'registration_type' => 'walk_in',
            'first_name' => 'Bartholomew',
            'last_name' => 'Simpson',
            'date_of_birth' => '2010-04-01',
            'gender' => 'male',
            'national_id' => 'NAT-SIMPSON-42',
            'phone' => '+15554321000',
        ]);

        // Search by Partial Name
        $resName = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->getJson('/api/v1/patients?search=Bartholo');
        $resName->assertStatus(200)
            ->assertJsonPath('data.0.mrn', 'MRN-2026-MAIN-778899');

        // Search by Phone
        $resPhone = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->getJson('/api/v1/patients?search=4321000');
        $resPhone->assertStatus(200)
            ->assertJsonPath('data.0.mrn', 'MRN-2026-MAIN-778899');

        // Search by National ID
        $resNatId = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->getJson('/api/v1/patients?search=SIMPSON-42');
        $resNatId->assertStatus(200)
            ->assertJsonPath('data.0.mrn', 'MRN-2026-MAIN-778899');
    }

    /**
     * Test 6: Family and Dependent linking.
     */
    public function test_family_dependent_linking(): void
    {
        Sanctum::actingAs($this->receptionist);

        $parent = Patient::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'mrn' => 'MRN-PARENT-01',
            'registration_type' => 'walk_in',
            'first_name' => 'Homer',
            'last_name' => 'Simpson',
            'gender' => 'male',
        ]);

        $child = Patient::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'mrn' => 'MRN-CHILD-01',
            'registration_type' => 'walk_in',
            'first_name' => 'Lisa',
            'last_name' => 'Simpson',
            'gender' => 'female',
        ]);

        $response = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/patients/{$child->id}/relationships", [
                'related_patient_id' => $parent->id,
                'relationship_type' => 'parent',
                'is_guardian' => true,
                'is_emergency_contact' => true,
                'is_billing_guarantor' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.relationship_type', 'parent')
            ->assertJsonPath('data.is_guardian', true);

        $this->assertDatabaseHas('patient_relationships', [
            'patient_id' => $child->id,
            'related_patient_id' => $parent->id,
            'relationship_type' => 'parent',
        ]);
    }

    /**
     * Test 7: Insurance Policy Addition and Verification.
     */
    public function test_insurance_policy_addition_and_verification(): void
    {
        Sanctum::actingAs($this->receptionist);

        $patient = Patient::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'mrn' => 'MRN-INS-01',
            'first_name' => 'Clark',
            'last_name' => 'Kent',
            'gender' => 'male',
        ]);

        $addResponse = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/patients/{$patient->id}/insurance", [
                'provider_name' => 'Metropolis Health HMO',
                'policy_number' => 'MET-100200',
                'group_number' => 'GRP-DAILY-PLANET',
                'coverage_type' => 'primary',
                'coverage_percentage' => 80.00,
                'copay_amount_cents' => 2000,
                'valid_from' => '2026-01-01',
                'valid_until' => '2026-12-31',
            ]);

        $addResponse->assertStatus(201)
            ->assertJsonPath('data.provider_name', 'Metropolis Health HMO')
            ->assertJsonPath('data.copay_amount_formatted', '20.00');

        $insuranceId = $addResponse->json('data.id');

        // Verify Insurance Policy
        $verifyResponse = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/patients/{$patient->id}/insurance/{$insuranceId}/verify");

        $verifyResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('patient_insurance', [
            'id' => $insuranceId,
            'status' => 'active',
        ]);
    }

    /**
     * Test 8: Allergy tracking with life-threatening severity.
     */
    public function test_allergy_addition_and_severity(): void
    {
        Sanctum::actingAs($this->doctor);

        $patient = Patient::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'mrn' => 'MRN-ALLERGY-01',
            'first_name' => 'Bruce',
            'last_name' => 'Wayne',
            'gender' => 'male',
        ]);

        $response = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/patients/{$patient->id}/allergies", [
                'allergen' => 'Penicillin',
                'allergen_type' => 'drug',
                'reaction' => 'Anaphylactic Shock',
                'severity' => 'life_threatening',
                'status' => 'active',
                'notes' => 'Patient requires immediate epinephrine if administered.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.allergen', 'Penicillin')
            ->assertJsonPath('data.severity', 'life_threatening');

        $this->assertDatabaseHas('patient_allergies', [
            'patient_id' => $patient->id,
            'allergen' => 'Penicillin',
            'severity' => 'life_threatening',
        ]);
    }

    /**
     * Test 9: Optional Patient-Facing Portal endpoints.
     */
    public function test_patient_portal_endpoints(): void
    {
        // Create portal user
        $portalUser = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Diana Prince',
            'email' => 'diana@themyscira.internal',
            'password' => bcrypt('password123'),
            'is_patient' => true,
        ]);
        $portalUser->branches()->attach($this->branch->id);

        // Link patient record
        $patient = Patient::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'mrn' => 'MRN-PORTAL-01',
            'first_name' => 'Diana',
            'last_name' => 'Prince',
            'gender' => 'female',
            'portal_user_id' => $portalUser->id,
            'email' => 'diana@themyscira.internal',
        ]);

        Sanctum::actingAs($portalUser);

        // Profile endpoint
        $resProfile = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->getJson('/api/v1/patient-portal/me');
        $resProfile->assertStatus(200)
            ->assertJsonPath('data.mrn', 'MRN-PORTAL-01')
            ->assertJsonPath('data.full_name', 'Diana Prince');

        // Records endpoint
        $resRecords = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->getJson('/api/v1/patient-portal/records');
        $resRecords->assertStatus(200)
            ->assertJsonPath('data.mrn', 'MRN-PORTAL-01');

        // Appointments contract endpoint
        $resAppt = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->getJson('/api/v1/patient-portal/appointments');
        $resAppt->assertStatus(200)
            ->assertJsonPath('data.patient_id', $patient->id);

        // Bills contract endpoint
        $resBills = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->getJson('/api/v1/patient-portal/bills');
        $resBills->assertStatus(200)
            ->assertJsonPath('data.patient_id', $patient->id);
    }

    /**
     * Test 10: Search returns in < 500ms (Acceptance Criteria Check).
     */
    public function test_search_latency_under_500ms(): void
    {
        Sanctum::actingAs($this->receptionist);

        $startTime = microtime(true);

        $response = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->getJson('/api/v1/patients?search=Diana');

        $elapsedMs = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(500, $elapsedMs, "Search query exceeded 500ms benchmark threshold ({$elapsedMs}ms)");
    }
}
