<?php

namespace App\Domain\SuperAdmin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Enforce that only platform operator super admins can access super admin endpoints.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->is_super_admin) {
            return response()->json([
                'error' => 'SUPER_ADMIN_REQUIRED',
                'message' => 'Access restricted exclusively to platform operator super administrators. Hospital-level administrators cannot access this tier.',
            ], 403);
        }

        return $next($request);
    }
}
