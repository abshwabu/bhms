<?php

namespace App\Domain\IPD\Http\Controllers;

use App\Domain\IPD\Services\BedManagementService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class IpdAnalyticsController extends Controller
{
    public function __construct(
        protected BedManagementService $bedService
    ) {
    }

    /**
     * Get real-time bed occupancy %, Average Length of Stay (ALOS), and inpatient census.
     */
    public function occupancyAnalytics(): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;

        $analytics = $this->bedService->getOccupancyAnalytics($branchId);

        return ApiResponse::success(
            $analytics,
            'Inpatient occupancy and ALOS analytics retrieved successfully.'
        );
    }
}
