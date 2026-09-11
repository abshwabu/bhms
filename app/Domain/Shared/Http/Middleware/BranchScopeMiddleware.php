<?php

namespace App\Domain\Shared\Http\Middleware;

use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Domain\Shared\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class BranchScopeMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Set or propagate correlation ID
        if (!$request->hasHeader('X-Correlation-ID')) {
            $request->headers->set('X-Correlation-ID', (string) Str::uuid());
        }

        $branchId = $request->header('X-Branch-ID');
        $user = $request->user();

        // If no header provided, try fallback to user's default branch or primary branch
        if (!$branchId && $user) {
            $branchId = $user->default_branch_id;
        }

        if ($branchId) {
            $branch = Branch::find($branchId);

            if (!$branch || !$branch->is_active) {
                return ApiResponse::error(
                    'Invalid or inactive branch specified.',
                    'BRANCH_NOT_FOUND',
                    [],
                    404
                );
            }

            // If user is authenticated and not super_admin, check branch authorization
            if ($user && method_exists($user, 'hasRole') && !$user->hasRole('super_admin')) {
                $hasAccess = $user->branches()->where('branches.id', $branchId)->exists();
                if (!$hasAccess && $user->default_branch_id !== $branchId) {
                    return ApiResponse::error(
                        'User is not authorized for the requested branch.',
                        'BRANCH_UNAUTHORIZED',
                        [],
                        403
                    );
                }
            }

            // Bind current branch and organization to container
            app()->instance('current_branch_id', $branch->id);
            app()->instance('current_organization_id', $branch->organization_id);

            // Set Spatie Permissions Team Context to Branch ID
            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($branch->id);
            }
        }

        $response = $next($request);

        // Append Correlation ID to outgoing response headers
        $response->headers->set('X-Correlation-ID', $request->header('X-Correlation-ID'));

        return $response;
    }
}
