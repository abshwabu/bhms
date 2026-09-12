<?php

namespace App\Domain\Compliance\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceRbacPermissionMiddleware
{
    /**
     * Handle an incoming request and verify role/permission.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (function_exists('setPermissionsTeamId') && $user->default_branch_id) {
            setPermissionsTeamId($user->default_branch_id);
        }

        // System administrators bypass permission checks
        if ($user->hasRole('admin') || $user->hasRole('super_admin') || ($user->is_super_admin ?? false)) {
            return $next($request);
        }

        // Support pipe-delimited multiple permissions: permissionA|permissionB (logical OR)
        $permissions = explode('|', $permission);
        $hasPermission = false;

        foreach ($permissions as $perm) {
            $perm = trim($perm);
            try {
                if ($user->hasPermissionTo($perm) || $user->can($perm) || $user->hasRole($perm)) {
                    $hasPermission = true;
                    break;
                }
            } catch (\Throwable $e) {
                // If permission does not exist in Spatie cache/guard, continue checking others
            }
        }

        if (! $hasPermission) {
            return response()->json([
                'success' => false,
                'message' => "Access denied: Missing required permission [{$permission}].",
                'required_permission' => $permission,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
