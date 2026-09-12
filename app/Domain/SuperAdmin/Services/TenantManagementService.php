<?php

namespace App\Domain\SuperAdmin\Services;

use App\Domain\Billing\Models\Invoice;
use App\Domain\IPD\Models\Admission;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Domain\SuperAdmin\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TenantManagementService
{
    public function __construct(
        protected FeatureFlagService $featureFlagService
    ) {}

    /**
     * List all hospital tenants with aggregate metrics.
     */
    public function listTenants(?string $search = null, ?string $status = null, ?string $plan = null)
    {
        $query = Organization::with(['branches' => fn($q) => $q->select('id', 'organization_id', 'name', 'code', 'is_active')])
            ->withCount(['branches', 'users']);

        if ($search) {
            $s = '%' . $search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'ilike', $s)
                    ->orWhere('code', 'ilike', $s)
                    ->orWhere('tax_number', 'ilike', $s);
            });
        }

        if ($status) {
            $query->where('subscription_status', $status);
        }

        if ($plan) {
            $query->where('plan_tier', $plan);
        }

        return $query->orderBy('name', 'asc')->get()->map(function ($org) {
            return [
                'id' => $org->id,
                'name' => $org->name,
                'code' => $org->code,
                'tax_number' => $org->tax_number,
                'is_active' => $org->is_active,
                'plan_tier' => $org->plan_tier ?? 'community',
                'subscription_status' => $org->subscription_status ?? ($org->is_active ? 'active' : 'suspended'),
                'suspended_at' => $org->suspended_at,
                'suspension_reason' => $org->suspension_reason,
                'branches_count' => $org->branches_count,
                'users_count' => $org->users_count,
                'created_at' => $org->created_at,
            ];
        });
    }

    /**
     * Get detailed hospital client record including subscription and flags.
     */
    public function getTenantDetail(string $organizationId): array
    {
        $org = Organization::with(['branches', 'users' => fn($q) => $q->where('is_active', true)->limit(10)])
            ->withCount(['branches', 'users'])
            ->findOrFail($organizationId);

        $subscription = Subscription::where('organization_id', $organizationId)
            ->orderBy('created_at', 'desc')
            ->first();

        $flags = $this->featureFlagService->getTenantFlagsMatrix($organizationId);

        // Usage statistics for this tenant
        $admissionsCount = Schema::hasTable('admissions')
            ? Admission::where('organization_id', $organizationId)->count()
            : 0;

        $invoicesCount = Schema::hasTable('invoices')
            ? Invoice::where('organization_id', $organizationId)->count()
            : 0;

        return [
            'organization' => $org,
            'subscription' => $subscription,
            'feature_flags' => $flags,
            'stats' => [
                'branches_count' => $org->branches_count,
                'users_count' => $org->users_count,
                'admissions_count' => $admissionsCount,
                'invoices_count' => $invoicesCount,
            ],
        ];
    }

    /**
     * Onboard a new hospital client / tenant into the platform.
     */
    public function onboardTenant(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // 1. Create Organization
            $org = Organization::create([
                'name' => $data['name'],
                'code' => strtoupper(trim($data['code'])),
                'tax_number' => $data['tax_number'] ?? null,
                'plan_tier' => $data['plan_tier'] ?? 'regional',
                'subscription_status' => 'active',
                'is_active' => true,
                'settings' => [
                    'currency' => $data['currency'] ?? 'USD',
                    'timezone' => $data['timezone'] ?? 'UTC',
                    'contact_email' => $data['admin_email'],
                ],
            ]);

            // 2. Create Default Primary Branch
            $branch = Branch::create([
                'organization_id' => $org->id,
                'name' => $data['branch_name'] ?? ($data['name'] . ' (Main Campus)'),
                'code' => strtoupper(substr($org->code, 0, 4) . '-MAIN'),
                'is_active' => true,
            ]);

            // 3. Create Hospital Super/Initial Admin User
            $adminUser = User::create([
                'organization_id' => $org->id,
                'default_branch_id' => $branch->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password'] ?? 'WelcomeHospital2026!'),
                'is_active' => true,
                'is_super_admin' => false, // Client admin is NOT vendor operator
            ]);
            $adminUser->branches()->attach($branch->id);

            // 4. Create Initial Subscription
            $planAmounts = [
                'community' => 49900,
                'regional' => 129900,
                'enterprise' => 299900,
            ];
            $planTier = $data['plan_tier'] ?? $org->plan_tier ?? 'regional';
            $amountCents = $planAmounts[$planTier] ?? 129900;

            $subscription = Subscription::create([
                'organization_id' => $org->id,
                'plan_tier' => $planTier,
                'status' => 'active',
                'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                'amount_cents' => $amountCents,
                'currency' => $data['currency'] ?? 'USD',
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);

            // 5. Seed feature flags
            $this->featureFlagService->ensureDefaultFlagsExist();

            return [
                'organization' => $org,
                'branch' => $branch,
                'admin_user' => $adminUser,
                'subscription' => $subscription,
            ];
        });
    }

    /**
     * Suspend a hospital tenant immediately.
     */
    public function suspendTenant(string $organizationId, string $reason): Organization
    {
        $org = Organization::findOrFail($organizationId);

        $org->update([
            'is_active' => false,
            'subscription_status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);

        // Revoke all tokens for all users in this tenant to prevent active sessions
        $userIds = User::where('organization_id', $organizationId)->pluck('id');
        DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->whereIn('tokenable_id', $userIds)
            ->delete();

        return $org;
    }

    /**
     * Reactivate a suspended hospital tenant.
     */
    public function reactivateTenant(string $organizationId): Organization
    {
        $org = Organization::findOrFail($organizationId);

        $org->update([
            'is_active' => true,
            'subscription_status' => 'active',
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        return $org;
    }

    /**
     * Global usage analytics across all hospital clients.
     */
    public function getGlobalUsageAnalytics(): array
    {
        $totalTenants = Organization::count();
        $activeTenants = Organization::where('is_active', true)->count();
        $suspendedTenants = Organization::where('is_active', false)->count();

        $totalUsers = User::where('is_super_admin', false)->count();
        $totalBranches = Branch::count();

        $planDistribution = Organization::select('plan_tier', DB::raw('count(*) as count'))
            ->groupBy('plan_tier')
            ->pluck('count', 'plan_tier')
            ->toArray();

        $overdueSubscriptions = Subscription::where('status', 'past_due')->count();

        // Estimated storage/records metrics
        $totalAdmissions = Schema::hasTable('admissions') ? Admission::count() : 0;
        $totalInvoices = Schema::hasTable('invoices') ? Invoice::count() : 0;

        return [
            'total_hospitals' => $totalTenants,
            'active_hospitals' => $activeTenants,
            'suspended_hospitals' => $suspendedTenants,
            'total_branches' => $totalBranches,
            'total_users' => $totalUsers,
            'plan_distribution' => $planDistribution,
            'overdue_subscriptions' => $overdueSubscriptions,
            'cross_tenant_records' => [
                'total_admissions' => $totalAdmissions,
                'total_invoices' => $totalInvoices,
            ],
            'estimated_storage_mb' => max(15, round(($totalAdmissions * 0.05) + ($totalUsers * 0.02), 2)),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
