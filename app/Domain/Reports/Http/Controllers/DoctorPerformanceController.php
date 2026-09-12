<?php

namespace App\Domain\Reports\Http\Controllers;

use App\Domain\Reports\Services\StandardReportService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorPerformanceController extends Controller
{
    public function __construct(
        protected StandardReportService $reportService
    ) {}

    /**
     * Doctor performance reports (patients seen, prescriptions, revenue, completion rate).
     */
    public function index(Request $request): JsonResponse
    {
        $branchId = $request->header('X-Branch-ID')
            ?: $request->input('branch_id', '84d7387e-7b2e-4533-b3e8-139e52aecb8b');

        $preset = $request->input('preset', 'last_30_days');
        $customStart = $request->input('start_date');
        $customEnd = $request->input('end_date');

        $data = $this->reportService->getDoctorPerformanceReport($branchId, $preset, $customStart, $customEnd);

        return ApiResponse::success($data, 'Doctor performance report retrieved.');
    }
}
