<?php

namespace App\Domain\SuperAdmin\Http\Middleware;

use App\Domain\SuperAdmin\Services\FeatureFlagService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantFeatureFlag
{
    public function __construct(
        protected FeatureFlagService $featureFlagService
    ) {}

    /**
     * Check if a specific module/feature flag is active for the current tenant.
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $user = $request->user();

        // Super admins bypass module feature flag restrictions
        if ($user && $user->is_super_admin) {
            return $next($request);
        }

        $organizationId = $user?->organization_id;

        if (!$organizationId) {
            return $next($request);
        }

        if (!$this->featureFlagService->isFeatureActiveForTenant($organizationId, $featureKey)) {
            return response()->json([
                'error' => 'MODULE_DISABLED',
                'feature' => $featureKey,
                'message' => "Module [{$featureKey}] is not enabled for your hospital subscription plan. Please contact your platform vendor to upgrade.",
            ], 403);
        }

        return $next($request);
    }
}
