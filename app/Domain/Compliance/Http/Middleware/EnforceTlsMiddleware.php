<?php

namespace App\Domain\Compliance\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTlsMiddleware
{
    /**
     * Handle an incoming request and enforce TLS / transport security headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Enforce HTTPS in production environments
        if (app()->environment('production') && ! $request->secure()) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insecure connection rejected: TLS/HTTPS is strictly enforced for health information under HIPAA §164.312(e)(1).',
                ], Response::HTTP_UPGRADE_REQUIRED);
            }

            return redirect()->secure($request->getRequestUri(), 301);
        }

        $response = $next($request);

        // Security headers compliance (HIPAA technical safeguards for transmission & confidentiality)
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}
