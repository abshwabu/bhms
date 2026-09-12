<?php

namespace App\Domain\Compliance\Http\Controllers;

use App\Domain\Compliance\Services\AuditTrailService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditLogController extends Controller
{
    public function __construct(
        protected AuditTrailService $auditTrailService
    ) {}

    /**
     * List and filter immutable audit trails.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'auditable_type',
            'auditable_id',
            'user_id',
            'event',
            'branch_id',
            'date_from',
            'date_to',
            'search',
            'per_page',
        ]);

        $logs = $this->auditTrailService->getLogs($filters);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Get aggregate statistics for the compliance auditing dashboard.
     */
    public function stats(): JsonResponse
    {
        $stats = $this->auditTrailService->getStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Show a single audit trail record with full before/after diffs.
     */
    public function show(string $id): JsonResponse
    {
        $log = $this->auditTrailService->getLogById($id);

        if (! $log) {
            return response()->json([
                'success' => false,
                'message' => 'Audit log record not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $log,
        ]);
    }
}
