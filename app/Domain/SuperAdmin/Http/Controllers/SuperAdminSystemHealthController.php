<?php

namespace App\Domain\SuperAdmin\Http\Controllers;

use App\Domain\SuperAdmin\Services\SystemHealthService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SuperAdminSystemHealthController extends Controller
{
    public function __construct(
        protected SystemHealthService $healthService
    ) {}

    /**
     * Get real-time system health overview.
     */
    public function health(): JsonResponse
    {
        $overview = $this->healthService->getHealthOverview();

        return response()->json([
            'data' => $overview,
        ]);
    }

    /**
     * Retry a specific failed queue job.
     */
    public function retryJob(string|int $id): JsonResponse
    {
        $result = $this->healthService->retryFailedJob($id);

        return response()->json($result);
    }

    /**
     * Flush all failed queue jobs.
     */
    public function flushJobs(): JsonResponse
    {
        $result = $this->healthService->flushFailedJobs();

        return response()->json($result);
    }
}
