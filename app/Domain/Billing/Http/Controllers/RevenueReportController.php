<?php

namespace App\Domain\Billing\Http\Controllers;

use App\Domain\Billing\Services\RevenueReportService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RevenueReportController extends Controller
{
    public function __construct(
        protected RevenueReportService $reportService
    ) {}

    /**
     * Revenue report itemized and aggregated by clinical/hospital department.
     */
    public function departments(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $data = $this->reportService->getDepartmentRevenue($branchId, $startDate, $endDate);

        return ApiResponse::success($data, 'Department revenue report generated.');
    }

    /**
     * Revenue report itemized and aggregated by attending / ordering physician.
     */
    public function doctors(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $data = $this->reportService->getDoctorRevenue($branchId, $startDate, $endDate);

        return ApiResponse::success($data, 'Physician revenue report generated.');
    }

    /**
     * Payment collections broken down by payment mode.
     */
    public function paymentModes(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $data = $this->reportService->getPaymentModeCollections($branchId, $startDate, $endDate);

        return ApiResponse::success($data, 'Payment mode collections report generated.');
    }

    /**
     * Executive billing summary KPIs.
     */
    public function summary(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $data = $this->reportService->getExecutiveSummary($branchId, $startDate, $endDate);

        return ApiResponse::success($data, 'Billing executive summary generated.');
    }
}
