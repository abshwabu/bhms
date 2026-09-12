<?php

namespace App\Domain\SuperAdmin\Http\Controllers;

use App\Domain\SuperAdmin\Services\TenantManagementService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuperAdminTenantController extends Controller
{
    public function __construct(
        protected TenantManagementService $tenantService
    ) {}

    /**
     * List all hospital clients / tenants.
     */
    public function index(Request $request): JsonResponse
    {
        $tenants = $this->tenantService->listTenants(
            search: $request->input('search'),
            status: $request->input('status'),
            plan: $request->input('plan')
        );

        $analytics = $this->tenantService->getGlobalUsageAnalytics();

        return response()->json([
            'data' => $tenants,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Get full details of a specific tenant.
     */
    public function show(string $id): JsonResponse
    {
        $detail = $this->tenantService->getTenantDetail($id);

        return response()->json([
            'data' => $detail,
        ]);
    }

    /**
     * Onboard a new hospital tenant.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:180',
            'code' => 'required|string|max:20|unique:organizations,code',
            'tax_number' => 'nullable|string|max:50',
            'plan_tier' => 'required|string|in:community,regional,enterprise',
            'admin_name' => 'required|string|max:150',
            'admin_email' => 'required|email|max:150|unique:users,email',
            'admin_password' => 'nullable|string|min:8',
            'branch_name' => 'nullable|string|max:150',
            'currency' => 'nullable|string|max:10',
            'billing_cycle' => 'nullable|string|in:monthly,annual',
        ]);

        $result = $this->tenantService->onboardTenant($validated);

        return response()->json([
            'message' => 'Hospital client onboarded successfully.',
            'data' => $result,
        ], 201);
    }

    /**
     * Suspend a hospital client immediately.
     */
    public function suspend(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $org = $this->tenantService->suspendTenant($id, $validated['reason']);

        return response()->json([
            'message' => "Hospital [{$org->name}] has been suspended. All active sessions revoked.",
            'data' => $org,
        ]);
    }

    /**
     * Reactivate a suspended hospital client.
     */
    public function reactivate(string $id): JsonResponse
    {
        $org = $this->tenantService->reactivateTenant($id);

        return response()->json([
            'message' => "Hospital [{$org->name}] has been reactivated successfully.",
            'data' => $org,
        ]);
    }
}
