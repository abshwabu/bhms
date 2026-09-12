<?php

namespace App\Domain\SuperAdmin\Http\Controllers;

use App\Domain\SuperAdmin\Models\FeatureFlag;
use App\Domain\SuperAdmin\Services\FeatureFlagService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuperAdminFeatureFlagController extends Controller
{
    public function __construct(
        protected FeatureFlagService $featureFlagService
    ) {}

    /**
     * List all platform feature flags.
     */
    public function index(): JsonResponse
    {
        $this->featureFlagService->ensureDefaultFlagsExist();
        $flags = FeatureFlag::orderBy('category')->orderBy('name')->get();

        return response()->json([
            'data' => $flags,
        ]);
    }

    /**
     * Get feature flag matrix for a specific tenant.
     */
    public function getTenantFlags(string $organizationId): JsonResponse
    {
        $matrix = $this->featureFlagService->getTenantFlagsMatrix($organizationId);

        return response()->json([
            'organization_id' => $organizationId,
            'data' => $matrix,
        ]);
    }

    /**
     * Toggle a feature flag for a specific hospital tenant.
     */
    public function setTenantFlag(Request $request, string $organizationId): JsonResponse
    {
        $validated = $request->validate([
            'feature_key' => 'required|string',
            'is_enabled' => 'required|boolean',
            'custom_config' => 'nullable|array',
        ]);

        $override = $this->featureFlagService->setTenantFeatureFlag(
            organizationId: $organizationId,
            featureKey: $validated['feature_key'],
            isEnabled: $validated['is_enabled'],
            customConfig: $validated['custom_config'] ?? []
        );

        return response()->json([
            'message' => "Feature flag [{$validated['feature_key']}] updated successfully for tenant.",
            'data' => $override,
        ]);
    }

    /**
     * Toggle global enablement of a feature flag across the entire platform.
     */
    public function toggleGlobal(string $id): JsonResponse
    {
        $flag = FeatureFlag::findOrFail($id);
        $flag->update(['is_globally_enabled' => !$flag->is_globally_enabled]);

        return response()->json([
            'message' => "Global feature flag [{$flag->key}] status updated.",
            'data' => $flag,
        ]);
    }
}
