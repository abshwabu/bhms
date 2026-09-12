<?php

namespace App\Domain\Compliance\Http\Middleware;

use App\Domain\Shared\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuditTrailMiddleware
{
    /**
     * Record request audit trail for sensitive API interactions.
     */
    public function handle(Request $request, Closure $next, ?string $action = null): Response
    {
        $response = $next($request);

        // Record audit logs for mutating requests or when explicitly flagged
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']) || $action === 'force_log') {
            try {
                $user = $request->user();
                $sanitizedPayload = $request->except([
                    'password',
                    'password_confirmation',
                    'token',
                    'signature_data',
                    'secret',
                ]);

                AuditLog::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $user?->organization_id,
                    'branch_id' => $user?->default_branch_id,
                    'user_id' => $user?->id,
                    'event' => strtolower($request->method()) . '_request',
                    'auditable_type' => 'HTTP_ENDPOINT',
                    'auditable_id' => (string) Str::uuid(),
                    'old_values' => null,
                    'new_values' => [
                        'method' => $request->method(),
                        'path' => $request->path(),
                        'payload' => $sanitizedPayload,
                        'status_code' => $response->getStatusCode(),
                    ],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                logger()->error('Failed to log in AuditTrailMiddleware: ' . $e->getMessage());
            }
        }

        return $response;
    }
}
