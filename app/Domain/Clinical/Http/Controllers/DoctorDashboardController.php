<?php

namespace App\Domain\Clinical\Http\Controllers;

use App\Domain\Clinical\Services\DoctorDashboardService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorDashboardController extends Controller
{
    public function __construct(
        protected DoctorDashboardService $dashboardService
    ) {
    }

    /**
     * Doctor's personal clinical dashboard:
     * - Patients seen today
     * - Today's scheduled queue / roster
     * - Pending chart reviews (drafts & amendments)
     * - Pending diagnostic reviews (completed labs & radiology)
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $doctorId = $request->input('doctor_id', $user?->id);

        if (!$doctorId) {
            return ApiResponse::error('Doctor ID is required.', 'DOCTOR_REQUIRED', [], 400);
        }

        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : $request->header('X-Branch-ID');

        $data = $this->dashboardService->getDashboardData($doctorId, $branchId);

        return ApiResponse::success(
            $data,
            "Doctor clinical dashboard retrieved successfully."
        );
    }
}
