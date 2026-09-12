<?php

namespace App\Domain\SuperAdmin\Http\Controllers;

use App\Domain\SuperAdmin\Services\ImpersonationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuperAdminImpersonationController extends Controller
{
    public function __construct(
        protected ImpersonationService $impersonationService
    ) {}

    /**
     * Start an impersonation session for a hospital client.
     */
    public function start(Request $request, string $organizationId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'target_user_id' => 'nullable|uuid',
        ]);

        $result = $this->impersonationService->startImpersonation(
            superAdmin: $request->user(),
            organizationId: $organizationId,
            reason: $validated['reason'],
            targetUserId: $validated['target_user_id'] ?? null,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return response()->json([
            'message' => "Impersonation session initiated for hospital [{$result['hospital']['name']}].",
            'data' => $result,
        ]);
    }

    /**
     * Stop an active impersonation session.
     */
    public function stop(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'impersonation_id' => 'required|uuid',
        ]);

        $log = $this->impersonationService->stopImpersonation($validated['impersonation_id']);

        return response()->json([
            'message' => 'Impersonation session terminated.',
            'data' => $log,
        ]);
    }

    /**
     * List all support impersonation audit logs.
     */
    public function logs(Request $request): JsonResponse
    {
        $logs = $this->impersonationService->listLogs($request->input('organization_id'));

        return response()->json($logs);
    }
}
