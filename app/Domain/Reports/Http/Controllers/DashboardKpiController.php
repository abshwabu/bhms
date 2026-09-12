<?php

namespace App\Domain\Reports\Http\Controllers;

use App\Domain\Reports\Services\KpiAggregationService;
use App\Domain\Reports\Services\StandardReportService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardKpiController extends Controller
{
    public function __construct(
        protected StandardReportService $reportService,
        protected KpiAggregationService $aggregationService
    ) {}

    /**
     * Retrieve Admin Dashboard core KPIs: occupancy, revenue, patient flow.
     * Acceptance criterion: Loads in under 2 seconds for a hospital with 100k+ records.
     */
    public function index(Request $request): JsonResponse
    {
        $branchId = $request->header('X-Branch-ID')
            ?: $request->input('branch_id', '84d7387e-7b2e-4533-b3e8-139e52aecb8b');

        $preset = $request->input('preset', 'last_30_days');
        $customStart = $request->input('start_date');
        $customEnd = $request->input('end_date');

        $kpis = $this->reportService->getAdminDashboardKpis($branchId, $preset, $customStart, $customEnd);

        return ApiResponse::success($kpis, 'Admin dashboard KPIs retrieved.');
    }

    /**
     * Trigger immediate on-demand aggregation refresh for a branch.
     */
    public function refresh(Request $request): JsonResponse
    {
        $branchId = $request->header('X-Branch-ID')
            ?: $request->input('branch_id', '84d7387e-7b2e-4533-b3e8-139e52aecb8b');

        $date = $request->filled('date') ? Carbon::parse($request->input('date')) : Carbon::today();

        $kpi = $this->aggregationService->aggregateForDate($branchId, $date);
        $this->aggregationService->aggregateDepartmentsForDate($branchId, $date);
        $this->aggregationService->aggregateDoctorsForDate($branchId, $date);

        return ApiResponse::success($kpi, "KPI metrics refreshed for {$date->format('Y-m-d')}.");
    }
}
