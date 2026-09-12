<?php

namespace Tests\Feature;

use App\Domain\Administration\Models\BackupLog;
use App\Domain\Administration\Models\HospitalService;
use App\Domain\Administration\Models\NotificationLog;
use App\Domain\Administration\Models\NotificationTemplate;
use App\Domain\Administration\Services\BackupService;
use App\Domain\Administration\Services\Notification\NotificationEngineService;
use App\Domain\Billing\Models\PriceList;
use App\Domain\OPD\Models\Department;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SystemAdministrationDomainTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;
    protected Branch $branchA;
    protected Branch $branchB;
    protected User $adminUser;
    protected User $branchAUser;
    protected User $branchBUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::create([
            'name' => 'Metro Health System Holding',
            'code' => 'MHS-SYS',
            'is_active' => true,
        ]);

        $this->branchA = Branch::create([
            'organization_id' => $this->org->id,
            'name' => 'Central City Hospital',
            'code' => 'CCH01',
            'is_active' => true,
        ]);

        $this->branchB = Branch::create([
            'organization_id' => $this->org->id,
            'name' => 'North Suburban Clinic',
            'code' => 'NSC02',
            'is_active' => true,
        ]);

        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($this->branchA->id);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web', 'branch_id' => $this->branchA->id]);

        $this->adminUser = User::factory()->create([
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branchA->id,
            'name' => 'Super Administrator',
            'email' => 'sysadmin@mhs.local',
        ]);
        $this->adminUser->assignRole($adminRole);
        $this->adminUser->branches()->attach([$this->branchA->id, $this->branchB->id]);

        $this->branchAUser = User::factory()->create([
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branchA->id,
            'name' => 'Nurse Branch A',
            'email' => 'nurse_a@mhs.local',
        ]);
        $this->branchAUser->branches()->attach($this->branchA->id);

        $this->branchBUser = User::factory()->create([
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branchB->id,
            'name' => 'Clerk Branch B',
            'email' => 'clerk_b@mhs.local',
        ]);
        $this->branchBUser->branches()->attach($this->branchB->id);
    }

    /**
     * Acceptance Criteria 1: Data is correctly isolated per branch/tenant with no cross-branch leakage.
     */
    public function test_tenant_data_is_strictly_isolated_per_branch_with_zero_cross_branch_leakage(): void
    {
        // 1. Create Patient in Branch A
        app()->instance('current_branch_id', $this->branchA->id);
        app()->instance('current_organization_id', $this->org->id);

        $patientA = Patient::create([
            'organization_id' => $this->org->id,
            'branch_id' => $this->branchA->id,
            'mrn' => 'MRN-BRANCH-A-001',
            'first_name' => 'Arthur',
            'last_name' => 'Dent',
            'date_of_birth' => '1980-01-01',
            'gender' => 'male',
            'phone' => '+1-555-0001',
        ]);

        // 2. Create Patient in Branch B
        app()->instance('current_branch_id', $this->branchB->id);
        $patientB = Patient::create([
            'organization_id' => $this->org->id,
            'branch_id' => $this->branchB->id,
            'mrn' => 'MRN-BRANCH-B-002',
            'first_name' => 'Ford',
            'last_name' => 'Prefect',
            'date_of_birth' => '1982-05-15',
            'gender' => 'male',
            'phone' => '+1-555-0002',
        ]);

        // 3. Verify Branch A Scope only returns Patient A
        app()->instance('current_branch_id', $this->branchA->id);
        $scopedBranchAPatients = Patient::all();
        $this->assertTrue($scopedBranchAPatients->contains('id', $patientA->id));
        $this->assertFalse($scopedBranchAPatients->contains('id', $patientB->id));

        // 4. Verify Branch B Scope only returns Patient B
        app()->instance('current_branch_id', $this->branchB->id);
        $scopedBranchBPatients = Patient::all();
        $this->assertTrue($scopedBranchBPatients->contains('id', $patientB->id));
        $this->assertFalse($scopedBranchBPatients->contains('id', $patientA->id));

        // 5. Test BranchScopeMiddleware denies unauthorized user accessing foreign branch
        Sanctum::actingAs($this->branchAUser);

        $unauthorizedResponse = $this->withHeader('X-Branch-ID', $this->branchB->id)
            ->getJson('/api/v1/patients');

        $unauthorizedResponse->assertStatus(403);
        $unauthorizedResponse->assertJsonFragment([
            'code' => 'BRANCH_UNAUTHORIZED',
        ]);
    }

    /**
     * Master Data Management: Departments, Services, Price Lists CRUD.
     */
    public function test_master_data_management_crud(): void
    {
        Sanctum::actingAs($this->adminUser);

        // 1. Department CRUD
        $deptRes = $this->postJson('/api/v1/admin/departments', [
            'branch_id' => $this->branchA->id,
            'name' => 'Cardiology Institute',
            'code' => 'CARD',
            'description' => 'Comprehensive cardiac care',
        ]);
        $deptRes->assertStatus(201);
        $deptId = $deptRes->json('data.id');

        // 2. Hospital Service Catalog CRUD
        $srvRes = $this->postJson('/api/v1/admin/services', [
            'branch_id' => $this->branchA->id,
            'department_id' => $deptId,
            'code' => 'SRV-CARD-ECHO',
            'name' => 'Transthoracic Echocardiogram',
            'category' => 'diagnostic',
            'duration_minutes' => 45,
            'base_price_cents' => 35000,
            'requires_doctor' => true,
        ]);
        $srvRes->assertStatus(201);
        $srvId = $srvRes->json('data.id');

        $getServicesRes = $this->getJson("/api/v1/admin/branches/{$this->branchA->id}/services");
        $getServicesRes->assertStatus(200);
        $this->assertCount(1, $getServicesRes->json('data'));

        // 3. Price List Catalog CRUD
        $priceRes = $this->postJson('/api/v1/admin/price-lists', [
            'branch_id' => $this->branchA->id,
            'code' => 'PRC-ECHO-STD',
            'name' => 'Standard Echocardiogram Fee',
            'category' => 'procedure',
            'department' => 'Cardiology',
            'unit_price_cents' => 35000,
        ]);
        $priceRes->assertStatus(201);
    }

    /**
     * Notification Engine: Multi-Channel Delivery with Template Interpolation.
     */
    public function test_notification_engine_template_interpolation_and_multi_channel_delivery(): void
    {
        Sanctum::actingAs($this->adminUser);

        $engine = app(NotificationEngineService::class);
        $engine->seedDefaultTemplates($this->org->id, $this->branchA->id);

        // 1. Send SMS Notification with Template Tokens
        $smsRes = $this->postJson('/api/v1/admin/notifications/send', [
            'channel' => 'sms',
            'recipient' => '+1-555-8888',
            'template_code' => 'appointment_confirmed',
            'variables' => [
                'patient_name' => 'Sarah Connor',
                'doctor_name' => 'Dr. Vance',
                'appointment_date' => 'Tomorrow 10:00 AM',
                'hospital_name' => 'Central City Hospital',
                'token_number' => 'A-102',
            ],
            'branch_id' => $this->branchA->id,
        ]);

        $smsRes->assertStatus(200);
        $this->assertEquals('sent', $smsRes->json('data.status'));
        $this->assertEquals('twilio_sms', $smsRes->json('data.provider'));
        $this->assertStringContainsString('Sarah Connor', $smsRes->json('data.body'));
        $this->assertStringContainsString('Dr. Vance', $smsRes->json('data.body'));

        // 2. Send Email Notification
        $emailRes = $this->postJson('/api/v1/admin/notifications/send', [
            'channel' => 'email',
            'recipient' => 'patient@mhs.local',
            'template_code' => 'invoice_generated',
            'variables' => [
                'patient_name' => 'Sarah Connor',
                'invoice_number' => 'INV-2026-0099',
                'currency' => 'USD',
                'total_amount' => '150.00',
                'balance_due' => '0.00',
                'hospital_name' => 'Central City Hospital',
            ],
            'branch_id' => $this->branchA->id,
        ]);

        $emailRes->assertStatus(200);
        $this->assertEquals('sent', $emailRes->json('data.status'));
        $this->assertEquals('smtp_mail', $emailRes->json('data.provider'));

        // 3. Send Push Notification
        $pushRes = $this->postJson('/api/v1/admin/notifications/send', [
            'channel' => 'push',
            'recipient' => 'fcm_device_token_sample_abc',
            'template_code' => 'critical_lab_alert',
            'variables' => [
                'patient_name' => 'John Doe',
                'patient_mrn' => 'MRN-999',
                'test_name' => 'Potassium (Serum)',
                'result_value' => '6.8',
                'units' => 'mmol/L',
            ],
            'branch_id' => $this->branchA->id,
        ]);

        $pushRes->assertStatus(200);
        $this->assertEquals('sent', $pushRes->json('data.status'));
        $this->assertEquals('fcm_push', $pushRes->json('data.provider'));
    }

    /**
     * Acceptance Criteria 2: Notification failures are retried and logged, not silently dropped.
     */
    public function test_notification_failures_are_logged_and_retried_never_silently_dropped(): void
    {
        Sanctum::actingAs($this->adminUser);

        // 1. Dispatch notification designed to fail (simulate carrier failure)
        $failRes = $this->postJson('/api/v1/admin/notifications/send', [
            'channel' => 'sms',
            'recipient' => '+1-555-invalid-destination',
            'body' => 'Test urgent clinical broadcast',
            'payload' => ['simulate_failure' => true],
            'branch_id' => $this->branchA->id,
        ]);

        $failRes->assertStatus(202);
        $logId = $failRes->json('data.id');

        // Verify NOT silently dropped: Stored in database with failure reason and retrying status
        $this->assertDatabaseHas('notification_logs', [
            'id' => $logId,
            'status' => 'retrying',
            'retry_count' => 1,
            'channel' => 'sms',
        ]);

        $log = NotificationLog::find($logId);
        $this->assertNotNull($log->error_message);
        $this->assertStringContainsString('Carrier unreachable', $log->error_message);

        // 2. Test manual retry recovering after destination fixed
        $log->update([
            'recipient' => '+1-555-7777',
            'payload' => ['simulate_failure' => false],
        ]);

        $retryRes = $this->postJson("/api/v1/admin/notifications/logs/{$logId}/retry");
        $retryRes->assertStatus(200);
        $this->assertEquals('sent', $retryRes->json('data.status'));
        $this->assertNull($retryRes->json('data.error_message'));
    }

    /**
     * Acceptance Criteria 3: Backup restore has been tested end-to-end at least once before go-live.
     */
    public function test_backup_restore_has_been_tested_end_to_end_before_go_live(): void
    {
        Sanctum::actingAs($this->adminUser);

        $backupService = app(BackupService::class);

        // 1. Create automated backup snapshot
        $backup = $backupService->createBackup('database', 'Nightly compliance backup snapshot.');
        $this->assertNotNull($backup);
        $this->assertFileExists($backup->file_path);
        $this->assertEquals('completed', $backup->status);
        $this->assertNotNull($backup->checksum_sha256);

        // 2. Verify SHA-256 Checksum
        $verification = $backupService->verifyBackup($backup);
        $this->assertTrue($verification['is_valid']);
        $this->assertEquals($backup->checksum_sha256, $verification['computed_checksum']);

        // 3. Execute End-to-End Disaster Recovery Restore Drill
        $drillResult = $backupService->testRestoreDrill($backup);
        $this->assertTrue($drillResult['restore_drill_passed']);
        $this->assertTrue($drillResult['checksum_verified']);
        $this->assertGreaterThan(0, $drillResult['verified_tables_count']);
        $this->assertNotNull($drillResult['notes']);

        // 4. Assert Backup Log record reflects restored/verified state
        $reloaded = $backup->fresh();
        $this->assertEquals('restored', $reloaded->status);
        $this->assertNotNull($reloaded->restored_at);

        // 5. Test API Endpoint confirms Go-Live readiness
        $apiScorecard = $this->getJson('/api/v1/admin/backups');
        $apiScorecard->assertStatus(200);
        $this->assertTrue($apiScorecard->json('data.go_live_ready'));
        $this->assertGreaterThanOrEqual(1, $apiScorecard->json('data.drills_tested_count'));

        // Cleanup test backup file from disk
        if (File::exists($backup->file_path)) {
            File::delete($backup->file_path);
        }
    }
}
