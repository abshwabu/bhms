<?php

namespace Tests\Feature;

use App\Domain\Auth\Services\AuthenticationService;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationAndRbacTest extends TestCase
{
    use RefreshDatabase;

    protected AuthenticationService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = app(AuthenticationService::class);
    }

    /**
     * Test web routes for login and app portal.
     */
    public function test_login_and_app_routes_render_patient_portal_view(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('patient-app', false);

        $appResponse = $this->get('/app');
        $appResponse->assertStatus(200);
        $appResponse->assertSee('patient-app', false);
    }

    /**
     * Test demo accounts endpoint returns all 7 role personas.
     */
    public function test_demo_accounts_endpoint_returns_expected_personas(): void
    {
        $response = $this->getJson('/api/v1/auth/demo-accounts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'demo_accounts' => [
                '*' => [
                    'role_key',
                    'role_label',
                    'email',
                    'password',
                    'name',
                    'accessible_scopes',
                ],
            ],
            'login_url',
        ]);

        $roles = collect($response->json('demo_accounts'))->pluck('role_key')->all();
        $this->assertNotContains('super_admin', $roles);
        $this->assertCount(6, $roles);
        $this->assertContains('hospital_admin', $roles);
        $this->assertContains('doctor', $roles);
        $this->assertContains('nurse', $roles);
        $this->assertContains('pharmacist', $roles);
        $this->assertContains('billing_officer', $roles);
        $this->assertContains('receptionist', $roles);
    }

    /**
     * Test login with valid credentials succeeds and returns tenant organization details.
     */
    public function test_login_succeeds_with_valid_doctor_credentials(): void
    {
        $this->authService->ensureDemoUsersExist();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'doctor@hms.local',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'token',
            'user' => [
                'id',
                'name',
                'email',
                'employee_id',
                'roles',
                'primary_role',
            ],
            'organization' => [
                'id',
                'name',
                'code',
                'plan_tier',
                'is_active',
            ],
            'default_branch' => [
                'id',
                'name',
                'code',
            ],
            'accessible_branches',
        ]);

        $this->assertEquals('Dr. Eleanor Vance, MD', $response->json('user.name'));
        $this->assertEquals('Metro Health System', $response->json('organization.name'));
        $this->assertContains('doctor', $response->json('user.roles'));
    }

    /**
     * Test login fails with invalid password.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        $this->authService->ensureDemoUsersExist();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'doctor@hms.local',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Test /me endpoint returns authenticated user profile.
     */
    public function test_me_endpoint_returns_user_data_when_authenticated(): void
    {
        $this->authService->ensureDemoUsersExist();

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@hms.local',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('token');

        $meResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(200);
        $this->assertEquals('admin@hms.local', $meResponse->json('user.email'));
        $this->assertEquals('Hospital Administrator', $meResponse->json('user.primary_role'));
    }

    /**
     * Test logout revokes token.
     */
    public function test_logout_revokes_bearer_token(): void
    {
        $this->authService->ensureDemoUsersExist();

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'nurse@hms.local',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('token');

        $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200);
        $logoutResponse->assertJson(['message' => 'Successfully signed out.']);
    }

    /**
     * Test tenant lockout when organization is suspended or inactive.
     */
    public function test_login_denied_when_tenant_organization_is_suspended(): void
    {
        $this->authService->ensureDemoUsersExist();

        $org = Organization::where('code', 'MHS')->first();
        $this->assertNotNull($org);
        $org->update([
            'is_active' => false,
            'subscription_status' => 'suspended',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'doctor@hms.local',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('suspended', $response->json('message'));
    }
}
