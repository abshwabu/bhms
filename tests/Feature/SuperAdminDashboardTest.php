<?php

namespace Tests\Feature;

use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Domain\SuperAdmin\Models\FeatureFlag;
use App\Domain\SuperAdmin\Models\ImpersonationLog;
use App\Domain\SuperAdmin\Models\PlatformAnnouncement;
use App\Domain\SuperAdmin\Models\Subscription;
use App\Domain\SuperAdmin\Models\SupportTicket;
use App\Domain\SuperAdmin\Models\TenantFeatureFlag;
use App\Domain\SuperAdmin\Services\FeatureFlagService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $hospitalAdmin;
    protected Organization $hospitalA;
    protected Branch $branchA;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Hospital Tenant A
        $this->hospitalA = Organization::create([
            'name' => 'Mercy General Hospital',
            'code' => 'MGH01',
            'tax_number' => 'TX-998877',
            'plan_tier' => 'regional',
            'subscription_status' => 'active',
            'is_active' => true,
        ]);

        $this->branchA = Branch::create([
            'organization_id' => $this->hospitalA->id,
            'name' => 'Mercy Central Clinic',
            'code' => 'MGH01-MAIN',
            'is_active' => true,
        ]);

        // Hospital-level admin (NOT a platform vendor super admin)
        $this->hospitalAdmin = User::factory()->create([
            'organization_id' => $this->hospitalA->id,
            'default_branch_id' => $this->branchA->id,
            'name' => 'Dr. Hospital Administrator',
            'email' => 'admin@mercygeneral.org',
            'is_super_admin' => false,
        ]);

        // Platform Vendor Operator Super Admin
        $this->superAdmin = User::factory()->create([
            'name' => 'Vendor Platform Super Admin',
            'email' => 'superadmin@metrohms.com',
            'is_super_admin' => true,
        ]);

        // Seed default platform feature flags
        app(FeatureFlagService::class)->ensureDefaultFlagsExist();
    }

    /**
     * 1. Test Strict Vendor Operator Separation from Hospital-Level RBAC.
     */
    public function test_hospital_admin_cannot_access_super_admin_endpoints(): void
    {
        // Acting as Hospital Admin
        Sanctum::actingAs($this->hospitalAdmin);

        $response = $this->getJson('/api/v1/super-admin/tenants');

        $response->assertStatus(403)
            ->assertJsonPath('error', 'SUPER_ADMIN_REQUIRED');

        // Acting as Vendor Super Admin
        Sanctum::actingAs($this->superAdmin);

        $adminResponse = $this->getJson('/api/v1/super-admin/tenants');
        $adminResponse->assertStatus(200)
            ->assertJsonStructure(['data', 'analytics']);
    }

    /**
     * 2. Test Tenant Onboarding, Suspension, and Reactivation.
     */
    public function test_super_admin_can_onboard_suspend_and_reactivate_tenant(): void
    {
        Sanctum::actingAs($this->superAdmin);

        // 1. Onboard new hospital client
        $onboardData = [
            'name' => 'Crescent University Medical Center',
            'code' => 'CUMC',
            'tax_number' => 'TX-123456',
            'plan_tier' => 'enterprise',
            'admin_name' => 'Dean Robert Sterling',
            'admin_email' => 'r.sterling@crescent.edu',
            'admin_password' => 'SecurePass2026!',
            'branch_name' => 'Main Academic Campus',
            'billing_cycle' => 'annual',
            'currency' => 'USD',
        ];

        $onboardResponse = $this->postJson('/api/v1/super-admin/tenants', $onboardData);

        $onboardResponse->assertStatus(201)
            ->assertJsonPath('data.organization.name', 'Crescent University Medical Center')
            ->assertJsonPath('data.organization.plan_tier', 'enterprise')
            ->assertJsonPath('data.admin_user.email', 'r.sterling@crescent.edu');

        $newOrgId = $onboardResponse->json('data.organization.id');

        // 2. Suspend tenant
        $suspendResponse = $this->postJson("/api/v1/super-admin/tenants/{$newOrgId}/suspend", [
            'reason' => 'Delinquent subscription invoice over 60 days.',
        ]);

        $suspendResponse->assertStatus(200)
            ->assertJsonPath('data.subscription_status', 'suspended')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('organizations', [
            'id' => $newOrgId,
            'subscription_status' => 'suspended',
            'is_active' => false,
            'suspension_reason' => 'Delinquent subscription invoice over 60 days.',
        ]);

        // 3. Reactivate tenant
        $reactivateResponse = $this->postJson("/api/v1/super-admin/tenants/{$newOrgId}/reactivate");

        $reactivateResponse->assertStatus(200)
            ->assertJsonPath('data.subscription_status', 'active')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('organizations', [
            'id' => $newOrgId,
            'subscription_status' => 'active',
            'is_active' => true,
        ]);
    }

    /**
     * 3. Test Feature Flag Toggles per Tenant and Immediate Route Enforcement.
     */
    public function test_feature_flag_control_per_tenant_and_route_protection(): void
    {
        Sanctum::actingAs($this->superAdmin);

        // Check initial flags for hospital A (regional plan includes telegram_reporting)
        $flagsResponse = $this->getJson("/api/v1/super-admin/tenants/{$this->hospitalA->id}/feature-flags");
        $flagsResponse->assertStatus(200);

        // 1. Explicitly DISABLE telegram_reporting module for Hospital A
        $disableResponse = $this->postJson("/api/v1/super-admin/tenants/{$this->hospitalA->id}/feature-flags", [
            'feature_key' => 'telegram_reporting',
            'is_enabled' => false,
        ]);
        $disableResponse->assertStatus(200);

        // 2. Hospital A user tries to access Telegram routes -> MUST BE BLOCKED (403 MODULE_DISABLED)
        Sanctum::actingAs($this->hospitalAdmin);
        $blockedResponse = $this->getJson('/api/v1/telegram/channels');
        $blockedResponse->assertStatus(403)
            ->assertJsonPath('error', 'MODULE_DISABLED')
            ->assertJsonPath('feature', 'telegram_reporting');

        // 3. Super admin RE-ENABLES telegram_reporting for Hospital A
        Sanctum::actingAs($this->superAdmin);
        $enableResponse = $this->postJson("/api/v1/super-admin/tenants/{$this->hospitalA->id}/feature-flags", [
            'feature_key' => 'telegram_reporting',
            'is_enabled' => true,
        ]);
        $enableResponse->assertStatus(200);

        // 4. Hospital A user accesses Telegram routes again -> SUCCEEDS immediately without redeploy!
        Sanctum::actingAs($this->hospitalAdmin);
        $allowedResponse = $this->getJson('/api/v1/telegram/channels');
        $allowedResponse->assertStatus(200);
    }

    /**
     * 4. Test Support User Impersonation with Time Limit & Audit Logging.
     */
    public function test_support_user_impersonation_lifecycle_and_audit(): void
    {
        Sanctum::actingAs($this->superAdmin);

        // 1. Initiate impersonation of Hospital A
        $startResponse = $this->postJson("/api/v1/super-admin/tenants/{$this->hospitalA->id}/impersonate", [
            'reason' => 'Investigating billing invoice reconciliation discrepancies reported in ticket #902.',
        ]);

        $startResponse->assertStatus(200)
            ->assertJsonPath('data.target_user.email', $this->hospitalAdmin->email)
            ->assertJsonPath('data.hospital.name', 'Mercy General Hospital');

        $impersonationId = $startResponse->json('data.impersonation_id');
        $token = $startResponse->json('data.token');

        $this->assertNotEmpty($token);

        // Verify audit log created
        $log = ImpersonationLog::find($impersonationId);
        $this->assertNotNull($log);
        $this->assertTrue($log->is_active);
        $this->assertEquals($this->superAdmin->id, $log->super_admin_id);
        $this->assertEquals($this->hospitalAdmin->id, $log->target_user_id);
        $this->assertEquals($this->hospitalA->id, $log->organization_id);

        // 2. Terminate impersonation
        $stopResponse = $this->postJson('/api/v1/super-admin/impersonation/stop', [
            'impersonation_id' => $impersonationId,
        ]);

        $stopResponse->assertStatus(200);

        $freshLog = $log->fresh();
        $this->assertFalse($freshLog->is_active);
        $this->assertNotNull($freshLog->ended_at);
    }

    /**
     * 5. Test Subscription & Billing Oversight.
     */
    public function test_subscription_and_billing_oversight(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $subscription = Subscription::create([
            'organization_id' => $this->hospitalA->id,
            'plan_tier' => 'regional',
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'amount_cents' => 129900,
            'currency' => 'USD',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        // List subscriptions
        $listResponse = $this->getJson('/api/v1/super-admin/subscriptions');
        $listResponse->assertStatus(200)
            ->assertJsonPath('total', 1);

        // Upgrade subscription to enterprise
        $updateResponse = $this->putJson("/api/v1/super-admin/subscriptions/{$subscription->id}", [
            'plan_tier' => 'enterprise',
            'amount_cents' => 299900,
            'billing_cycle' => 'annual',
        ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.plan_tier', 'enterprise')
            ->assertJsonPath('data.billing_cycle', 'annual');

        $this->assertDatabaseHas('organizations', [
            'id' => $this->hospitalA->id,
            'plan_tier' => 'enterprise',
        ]);
    }

    /**
     * 6. Test Real-Time System Health Monitoring.
     */
    public function test_system_health_monitoring_returns_queue_and_uptime_data(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->getJson('/api/v1/super-admin/system/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'status',
                    'database' => ['status', 'latency_ms'],
                    'queues' => ['failed_jobs_count'],
                    'background_jobs' => ['failed_telegram_messages_count'],
                    'system_environment' => ['php_version', 'laravel_version'],
                ],
            ]);
    }

    /**
     * 7. Test Support Tickets Helpdesk and Platform Announcements.
     */
    public function test_support_tickets_and_platform_announcements(): void
    {
        Sanctum::actingAs($this->superAdmin);

        // 1. Create support ticket
        $ticketResponse = $this->postJson('/api/v1/super-admin/tickets', [
            'organization_id' => $this->hospitalA->id,
            'reporter_name' => 'Nurse Supervisor Clara',
            'reporter_email' => 'clara@mercygeneral.org',
            'subject' => 'ICU Bed status display lagging on mobile',
            'description' => 'The graphical ward view takes 4 seconds to refresh on ward tablets.',
            'category' => 'bug',
            'priority' => 'high',
        ]);

        $ticketResponse->assertStatus(201)
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.priority', 'high');

        $ticketId = $ticketResponse->json('data.id');

        // Resolve ticket
        $resolveResponse = $this->putJson("/api/v1/super-admin/tickets/{$ticketId}", [
            'status' => 'resolved',
            'resolution_notes' => 'Optimized web socket channel listener and cleared Redis cache.',
        ]);

        $resolveResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'resolved');
        $this->assertNotNull($resolveResponse->json('data.resolved_at'));

        // 2. Create Platform Broadcast Announcement
        $announcementResponse = $this->postJson('/api/v1/super-admin/announcements', [
            'title' => 'Scheduled Platform Upgrade Window: Saturday 02:00 UTC',
            'content' => 'System maintenance for database index optimization. Estimated downtime 10 minutes.',
            'severity' => 'maintenance',
            'target_plans' => ['*'],
            'is_active' => true,
        ]);

        $announcementResponse->assertStatus(201)
            ->assertJsonPath('data.severity', 'maintenance');

        // Verify hospital users can fetch active announcements
        Sanctum::actingAs($this->hospitalAdmin);
        $activeAnnouncements = $this->getJson('/api/v1/announcements/active');
        $activeAnnouncements->assertStatus(200)
            ->assertJsonPath('data.0.title', 'Scheduled Platform Upgrade Window: Saturday 02:00 UTC');
    }
}
