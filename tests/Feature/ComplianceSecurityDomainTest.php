<?php

namespace Tests\Feature;

use App\Domain\Compliance\Models\HipaaComplianceCheck;
use App\Domain\Compliance\Models\PatientConsent;
use App\Domain\Compliance\Services\HipaaComplianceService;
use App\Domain\Compliance\Services\RolePermissionService;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\AuditLog;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ComplianceSecurityDomainTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;
    protected Branch $branch;
    protected User $adminUser;
    protected User $staffUser;
    protected User $unauthorizedUser;
    protected Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::create([
            'name' => 'Metro General Health System',
            'code' => 'MGHS',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'organization_id' => $this->org->id,
            'name' => 'Central Hospital',
            'code' => 'CH01',
            'is_active' => true,
        ]);

        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($this->branch->id);
        }

        // Ensure permissions exist
        app(RolePermissionService::class)->ensureDefaultPermissionsExist();

        // 1. Admin user with 'admin' role
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web', 'branch_id' => $this->branch->id]);
        $this->adminUser = User::factory()->create([
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Chief Compliance Officer',
            'email' => 'compliance_admin@mghs.org',
        ]);
        $this->adminUser->assignRole($adminRole);

        // 2. Staff user with specific compliance permissions
        $complianceRole = Role::firstOrCreate(['name' => 'compliance_officer', 'guard_name' => 'web', 'branch_id' => $this->branch->id]);
        $complianceRole->syncPermissions([
            'compliance.audit.view',
            'compliance.consents.manage',
            'compliance.hipaa.audit',
        ]);
        $this->staffUser = User::factory()->create([
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Auditor Jane',
            'email' => 'auditor_jane@mghs.org',
        ]);
        $this->staffUser->assignRole($complianceRole);

        // 3. Unauthorized user with NO permissions
        $noPermRole = Role::firstOrCreate(['name' => 'guest_staff', 'guard_name' => 'web', 'branch_id' => $this->branch->id]);
        $this->unauthorizedUser = User::factory()->create([
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Bob Unauthorized',
            'email' => 'bob@mghs.org',
        ]);
        $this->unauthorizedUser->assignRole($noPermRole);

        // Test Patient
        $this->patient = Patient::create([
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'mrn' => 'MRN-COMP-001',
            'first_name' => 'Alice',
            'last_name' => 'Wonderland',
            'date_of_birth' => '1992-04-10',
            'gender' => 'female',
            'phone' => '+1-555-0987',
            'national_id' => 'NAT-998811',
            'passport_number' => 'PASS-SEC-9988',
        ]);
    }

    /**
     * Acceptance Criteria 1: Unauthenticated access returns 401.
     */
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/compliance/roles');
        $response->assertStatus(401);
    }

    /**
     * Acceptance Criteria 2: Missing required role/permission returns 403 Forbidden.
     */
    public function test_unauthorized_user_without_permission_receives_403_forbidden(): void
    {
        Sanctum::actingAs($this->unauthorizedUser);

        // Bob lacks 'compliance.roles.manage'
        $response = $this->getJson('/api/v1/compliance/roles');
        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'required_permission' => 'compliance.roles.manage',
        ]);
    }

    /**
     * Acceptance Criteria 3: Authorized user with permission successfully accesses endpoint.
     */
    public function test_user_with_permission_can_access_protected_endpoint(): void
    {
        Sanctum::actingAs($this->staffUser);

        // Staff user has 'compliance.audit.view'
        $response = $this->getJson('/api/v1/compliance/audit-logs');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'meta' => ['current_page', 'total'],
        ]);
    }

    /**
     * Acceptance Criteria 4: Administrator bypasses granular permission check.
     */
    public function test_administrator_bypasses_permission_checks(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Admin does not explicitly have compliance.roles.manage permission attached to role,
        // but bypasses check because they have 'admin' role.
        $response = $this->getJson('/api/v1/compliance/roles');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
        ]);
    }

    /**
     * Acceptance Criteria 5: Role management CRUD and assigning roles to user.
     */
    public function test_role_management_crud_and_permission_assignment(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Create new role
        $createRes = $this->postJson('/api/v1/compliance/roles', [
            'name' => 'pharmacist_lead',
            'branch_id' => $this->branch->id,
            'permissions' => ['pharmacy.inventory.view', 'pharmacy.dispense'],
        ]);
        $createRes->assertStatus(201);
        $roleId = $createRes->json('data.id');

        // Update role
        $updateRes = $this->putJson("/api/v1/compliance/roles/{$roleId}", [
            'permissions' => ['pharmacy.inventory.view', 'pharmacy.inventory.manage', 'pharmacy.dispense'],
        ]);
        $updateRes->assertStatus(200);
        $this->assertCount(3, $updateRes->json('data.permissions'));

        // Assign role to unauthorized user
        $assignRes = $this->postJson("/api/v1/compliance/users/{$this->unauthorizedUser->id}/roles", [
            'roles' => ['pharmacist_lead'],
        ]);
        $assignRes->assertStatus(200);
        $this->assertTrue($this->unauthorizedUser->fresh()->hasRole('pharmacist_lead'));

        // Delete role
        $deleteRes = $this->deleteJson("/api/v1/compliance/roles/{$roleId}");
        $deleteRes->assertStatus(200);
    }

    /**
     * Acceptance Criteria 6: Sensitive data change is logged with who, what, when, and before/after values.
     */
    public function test_audit_trail_captures_create_update_delete_with_old_and_new_values(): void
    {
        Sanctum::actingAs($this->staffUser);

        // 1. Initial creation of Patient was done in setUp
        $creationLog = AuditLog::where('auditable_type', Patient::class)
            ->where('auditable_id', $this->patient->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($creationLog);
        $this->assertEquals('Alice', $creationLog->new_values['first_name']);

        // 2. Update Patient data
        $this->patient->update([
            'first_name' => 'Alicia',
            'phone' => '+1-555-9999',
        ]);

        $updateLog = AuditLog::where('auditable_type', Patient::class)
            ->where('auditable_id', $this->patient->id)
            ->where('event', 'updated')
            ->latest('created_at')
            ->first();

        $this->assertNotNull($updateLog);
        $this->assertEquals($this->staffUser->id, $updateLog->user_id);
        $this->assertEquals('Alice', $updateLog->old_values['first_name']);
        $this->assertEquals('Alicia', $updateLog->new_values['first_name']);
        $this->assertEquals('+1-555-0987', $updateLog->old_values['phone']);
        $this->assertEquals('+1-555-9999', $updateLog->new_values['phone']);

        // 3. Delete Patient (soft delete)
        $this->patient->delete();

        $deleteLog = AuditLog::where('auditable_type', Patient::class)
            ->where('auditable_id', $this->patient->id)
            ->where('event', 'deleted')
            ->latest('created_at')
            ->first();

        $this->assertNotNull($deleteLog);
        $this->assertEquals('Alicia', $deleteLog->old_values['first_name']);
    }

    /**
     * Acceptance Criteria 7: Audit logs query filtering and statistics.
     */
    public function test_audit_logs_query_filtering_and_statistics_endpoints(): void
    {
        Sanctum::actingAs($this->staffUser);

        // Query with filter
        $response = $this->getJson('/api/v1/compliance/audit-logs?event=created');
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Stats endpoint
        $statsRes = $this->getJson('/api/v1/compliance/audit-logs/stats');
        $statsRes->assertStatus(200);
        $statsRes->assertJsonStructure([
            'success',
            'data' => [
                'total_logs',
                'today_logs',
                'by_event',
                'by_model',
                'compliance_coverage',
            ],
        ]);
    }

    /**
     * Acceptance Criteria 8: PII fields are unreadable in raw database dumps without the encryption key.
     */
    public function test_pii_column_level_encryption_unreadable_in_raw_database_dump(): void
    {
        Sanctum::actingAs($this->staffUser);

        $secretNationalId = 'NAT-TOP-SECRET-7788';
        $secretPhone = '+1-800-SECRET';
        $secretSignature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...';
        $secretClinicalNotes = 'Patient has confidential medical history disclosure.';

        $consent = PatientConsent::create([
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'consent_type' => 'surgical_procedure',
            'title' => 'Cardiac Angioplasty Informed Consent',
            'purpose' => 'Procedure consent and telemetry monitoring',
            'status' => 'granted',
            'granted_at' => Carbon::now(),
            'signature_data' => $secretSignature,
            'patient_national_id' => $secretNationalId,
            'contact_phone' => $secretPhone,
            'sensitive_notes' => $secretClinicalNotes,
            'created_by' => $this->staffUser->id,
        ]);

        // Direct raw PostgreSQL query bypassing Eloquent decryption
        $rawRow = DB::table('patient_consents')->where('id', $consent->id)->first();

        // 1. Raw DB values MUST NOT contain plaintext
        $this->assertStringNotContainsString($secretNationalId, $rawRow->patient_national_id);
        $this->assertStringNotContainsString($secretPhone, $rawRow->contact_phone);
        $this->assertStringNotContainsString($secretClinicalNotes, $rawRow->sensitive_notes);
        $this->assertStringNotContainsString($secretSignature, $rawRow->signature_data);

        // 2. Raw DB values MUST be encrypted ciphertext strings
        $this->assertNotEmpty($rawRow->patient_national_id);
        $this->assertNotEmpty($rawRow->contact_phone);

        // 3. Eloquent Model transparently decrypts the ciphertext back to original plaintext
        $reloaded = PatientConsent::find($consent->id);
        $this->assertEquals($secretNationalId, $reloaded->patient_national_id);
        $this->assertEquals($secretPhone, $reloaded->contact_phone);
        $this->assertEquals($secretClinicalNotes, $reloaded->sensitive_notes);
        $this->assertEquals($secretSignature, $reloaded->signature_data);
    }

    /**
     * Acceptance Criteria 9: Patient consent capture, verification, and revocation workflow.
     */
    public function test_patient_consent_capture_verification_and_revocation_workflow(): void
    {
        Sanctum::actingAs($this->staffUser);

        // 1. Capture Consent
        $res = $this->postJson('/api/v1/compliance/consents', [
            'patient_id' => $this->patient->id,
            'consent_type' => 'data_sharing',
            'title' => 'EHR Data Sharing Authorization',
            'purpose' => 'Transmission of clinical summaries to referral specialist',
            'granted_at' => Carbon::now()->toDateTimeString(),
            'expires_at' => Carbon::now()->addYear()->toDateTimeString(),
            'signature_data' => 'digital_signature_hash_xyz',
            'witness_name' => 'Dr. Eleanor Vance',
        ]);
        $res->assertStatus(201);
        $consentId = $res->json('data.id');

        // 2. Verify Consent (Active)
        $verifyRes = $this->getJson("/api/v1/compliance/patients/{$this->patient->id}/verify?consent_type=data_sharing");
        $verifyRes->assertStatus(200);
        $this->assertTrue($verifyRes->json('data.has_valid_consent'));

        // 3. Revoke Consent
        $revokeRes = $this->postJson("/api/v1/compliance/consents/{$consentId}/revoke", [
            'reason' => 'Patient withdrew authorization for third-party record transmission.',
        ]);
        $revokeRes->assertStatus(200);
        $this->assertEquals('revoked', $revokeRes->json('data.status'));
        $this->assertNotNull($revokeRes->json('data.revoked_at'));

        // 4. Verify Consent (Now Inactive)
        $verifyAfterRevoke = $this->getJson("/api/v1/compliance/patients/{$this->patient->id}/verify?consent_type=data_sharing");
        $verifyAfterRevoke->assertStatus(200);
        $this->assertFalse($verifyAfterRevoke->json('data.has_valid_consent'));
    }

    /**
     * Acceptance Criteria 10: Automated HIPAA safeguard evaluation across 5 domains.
     */
    public function test_hipaa_compliance_safeguard_evaluation_and_scorecard(): void
    {
        Sanctum::actingAs($this->staffUser);

        $evalRes = $this->postJson('/api/v1/compliance/hipaa/evaluate');
        $evalRes->assertStatus(200);
        $evalRes->assertJsonStructure([
            'success',
            'data' => [
                'overall_status',
                'compliance_score',
                'total_checks',
                'compliant_checks',
                'checks',
            ],
        ]);

        $this->assertGreaterThanOrEqual(5, $evalRes->json('data.total_checks'));

        // Verify safeguard codes in database
        $this->assertDatabaseHas('hipaa_compliance_checks', [
            'safeguard_code' => '164.312(a)(1)',
            'check_category' => 'access_control',
        ]);
        $this->assertDatabaseHas('hipaa_compliance_checks', [
            'safeguard_code' => '164.312(b)',
            'check_category' => 'audit_controls',
        ]);
        $this->assertDatabaseHas('hipaa_compliance_checks', [
            'safeguard_code' => '164.312(c)(1)',
            'check_category' => 'integrity',
        ]);
        $this->assertDatabaseHas('hipaa_compliance_checks', [
            'safeguard_code' => '164.312(e)(1)',
            'check_category' => 'transmission_security',
        ]);
        $this->assertDatabaseHas('hipaa_compliance_checks', [
            'safeguard_code' => '164.502',
            'check_category' => 'consent_privacy',
        ]);
    }

    /**
     * Acceptance Criteria 11: TLS and transport security headers enforced.
     */
    public function test_tls_transport_security_headers_enforced(): void
    {
        Sanctum::actingAs($this->adminUser);

        $res = $this->getJson('/api/v1/compliance/roles');
        $res->assertStatus(200);

        // Assert critical HIPAA transport security headers
        $res->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        $res->assertHeader('X-Content-Type-Options', 'nosniff');
        $res->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
