<?php

namespace App\Domain\Auth\Http\Controllers;

use App\Domain\Auth\Services\AuthenticationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        protected AuthenticationService $authService
    ) {}

    /**
     * Authenticate user and return session context.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->authService->login($validated['email'], $validated['password']);

        // Log into web session for browser cookies
        $user = \App\Models\User::where('email', strtolower(trim($validated['email'])))->first();
        if ($user) {
            Auth::login($user, true);
        }

        return response()->json([
            'success' => true,
            'message' => "Welcome back, {$result['user']['name']}!",
            'token' => $result['token'] ?? null,
            'user' => $result['user'] ?? null,
            'organization' => $result['organization'] ?? null,
            'default_branch' => $result['default_branch'] ?? null,
            'accessible_branches' => $result['accessible_branches'] ?? [],
            'data' => $result,
        ]);
    }

    /**
     * Terminate current session.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user('sanctum') ?: Auth::guard('sanctum')->user() ?: Auth::guard('web')->user();

        $token = ($user && method_exists($user, 'currentAccessToken')) ? $user->currentAccessToken() : null;
        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully signed out.',
        ]);
    }

    /**
     * Get current authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user('sanctum') ?: Auth::guard('sanctum')->user() ?: Auth::guard('web')->user();

        if (!$user) {
            return response()->json([
                'authenticated' => false,
                'user' => null,
                'data' => null,
            ], 401);
        }

        $branchId = $request->header('X-Branch-ID') ?: $user->default_branch_id;
        $formatted = $this->authService->formatUserData($user, null, $branchId);

        return response()->json([
            'authenticated' => true,
            'user' => $formatted['user'] ?? null,
            'organization' => $formatted['organization'] ?? null,
            'default_branch' => $formatted['default_branch'] ?? null,
            'accessible_branches' => $formatted['accessible_branches'] ?? [],
            'data' => $formatted,
        ]);
    }

    /**
     * Get quick demo role switcher accounts.
     */
    public function demoAccounts(): JsonResponse
    {
        $accounts = $this->authService->getDemoAccounts();

        return response()->json([
            'success' => true,
            'demo_accounts' => $accounts,
            'data' => $accounts,
            'login_url' => route('login'),
        ]);
    }
}
