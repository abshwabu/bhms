<?php

namespace App\Domain\HR\Http\Controllers;

use App\Domain\HR\Http\Resources\LeaveRequestResource;
use App\Domain\HR\Models\LeaveRequest;
use App\Domain\HR\Models\Staff;
use App\Domain\HR\Services\LeaveManagementService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveManagementController extends Controller
{
    public function __construct(
        protected LeaveManagementService $leaveService
    ) {}

    /**
     * List leave requests.
     */
    public function index(Request $request): JsonResponse
    {
        $query = LeaveRequest::query()
            ->with(['staff.role', 'approver'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->input('staff_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $leaves = $query->paginate($request->input('per_page', 25));

        return ApiResponse::paginated(
            $leaves->through(fn($l) => new LeaveRequestResource($l)),
            'Leave requests retrieved.'
        );
    }

    /**
     * Submit leave request with automated balance deduction.
     * Acceptance criterion: Leave requests follow an approval workflow with balance tracking.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'staff_id' => ['required', 'uuid', 'exists:staff,id'],
            'leave_type' => ['required', 'string', 'in:annual,sick,maternity,paternity,casual,study,unpaid'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $staff = Staff::findOrFail($validated['staff_id']);

        try {
            $leaveRequest = $this->leaveService->submitLeaveRequest($staff, $validated);

            return ApiResponse::success(
                new LeaveRequestResource($leaveRequest->load('staff')),
                "Leave request submitted for {$leaveRequest->total_days} days. Pending supervisor sign-off.",
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'LEAVE_REQUEST_ERROR', [], 422);
        }
    }

    /**
     * Supervisor approval.
     */
    public function approve(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $approverId = $request->user()?->id ?? $request->input('approved_by');
        if (!$approverId) {
            return ApiResponse::error('Approver user is required.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $approved = $this->leaveService->approveLeaveRequest($leaveRequest, $approverId);

            return ApiResponse::success(
                new LeaveRequestResource($approved),
                "Leave request for {$approved->staff->full_name} ({$approved->total_days} days) approved."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'LEAVE_APPROVAL_ERROR', [], 422);
        }
    }

    /**
     * Supervisor rejection (restores remaining leave balance).
     */
    public function reject(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $rejectorId = $request->user()?->id ?? $request->input('rejected_by');
        if (!$rejectorId) {
            return ApiResponse::error('Rejector user is required.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $rejected = $this->leaveService->rejectLeaveRequest($leaveRequest, $rejectorId, $validated['reason']);

            return ApiResponse::success(
                new LeaveRequestResource($rejected),
                "Leave request for {$rejected->staff->full_name} rejected. Balances restored."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'LEAVE_REJECTION_ERROR', [], 422);
        }
    }

    /**
     * Get staff member's annual leave balances.
     */
    public function balances(Request $request, Staff $staff): JsonResponse
    {
        $year = (int) $request->input('year', now()->year);
        $balances = $this->leaveService->getStaffLeaveBalances($staff, $year);

        return ApiResponse::success($balances, "Leave quotas for {$staff->full_name} ({$year}) retrieved.");
    }
}
