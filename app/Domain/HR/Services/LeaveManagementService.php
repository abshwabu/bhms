<?php

namespace App\Domain\HR\Services;

use App\Domain\HR\Models\LeaveBalance;
use App\Domain\HR\Models\LeaveRequest;
use App\Domain\HR\Models\Staff;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class LeaveManagementService
{
    /**
     * Standard annual leave allocations per type.
     */
    protected array $defaultEntitlements = [
        'annual' => 20,
        'sick' => 10,
        'casual' => 5,
        'study' => 5,
        'maternity' => 90,
        'paternity' => 10,
        'unpaid' => 0,
    ];

    /**
     * Initialize annual leave quotas for a staff member.
     */
    public function initializeYearlyBalances(Staff $staff, int $year): void
    {
        foreach ($this->defaultEntitlements as $type => $days) {
            LeaveBalance::firstOrCreate(
                [
                    'staff_id' => $staff->id,
                    'year' => $year,
                    'leave_type' => $type,
                ],
                [
                    'organization_id' => $staff->organization_id,
                    'branch_id' => $staff->branch_id,
                    'allocated_days' => $days,
                    'used_days' => 0,
                    'pending_days' => 0,
                    'remaining_days' => $days,
                ]
            );
        }
    }

    /**
     * Retrieve or initialize leave balance record.
     */
    public function getOrCreateBalance(Staff $staff, string $leaveType, int $year): LeaveBalance
    {
        $allocated = $this->defaultEntitlements[$leaveType] ?? 10;

        return LeaveBalance::firstOrCreate(
            [
                'staff_id' => $staff->id,
                'year' => $year,
                'leave_type' => $leaveType,
            ],
            [
                'organization_id' => $staff->organization_id,
                'branch_id' => $staff->branch_id,
                'allocated_days' => $allocated,
                'used_days' => 0,
                'pending_days' => 0,
                'remaining_days' => $allocated,
            ]
        );
    }

    /**
     * Submit leave request with automated balance checks.
     * Acceptance criterion: Leave requests follow an approval workflow with balance tracking.
     */
    public function submitLeaveRequest(Staff $staff, array $data): LeaveRequest
    {
        return DB::transaction(function () use ($staff, $data) {
            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);

            if ($endDate->lessThan($startDate)) {
                throw new DomainException("Leave end date cannot be earlier than start date.");
            }

            $totalDays = (int) ($startDate->diffInDays($endDate) + 1);
            $year = (int) $startDate->format('Y');
            $leaveType = $data['leave_type'];

            // Balance check (except for unpaid leave)
            if ($leaveType !== 'unpaid') {
                $balance = $this->getOrCreateBalance($staff, $leaveType, $year);

                if ($balance->remaining_days < $totalDays) {
                    throw new DomainException("Insufficient {$leaveType} leave balance. Requested: {$totalDays} days, Available: {$balance->remaining_days} days.");
                }

                // Place days in pending state
                $balance->remaining_days -= $totalDays;
                $balance->pending_days += $totalDays;
                $balance->save();
            }

            return LeaveRequest::create([
                'organization_id' => $staff->organization_id,
                'branch_id' => $staff->branch_id,
                'staff_id' => $staff->id,
                'leave_type' => $leaveType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $totalDays,
                'reason' => $data['reason'],
                'status' => 'pending',
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? $staff->phone,
            ]);
        });
    }

    /**
     * Supervisor approval of leave request.
     */
    public function approveLeaveRequest(LeaveRequest $request, string $approverId): LeaveRequest
    {
        if ($request->status !== 'pending') {
            throw new DomainException("Only pending leave requests can be approved. Current status: {$request->status}");
        }

        return DB::transaction(function () use ($request, $approverId) {
            $year = (int) $request->start_date->format('Y');

            if ($request->leave_type !== 'unpaid') {
                $balance = $this->getOrCreateBalance($request->staff, $request->leave_type, $year);
                $balance->pending_days = max(0, $balance->pending_days - $request->total_days);
                $balance->used_days += $request->total_days;
                $balance->save();
            }

            $request->status = 'approved';
            $request->approved_by = $approverId;
            $request->approved_at = Carbon::now();
            $request->rejection_reason = null;
            $request->save();

            return $request->load(['staff', 'approver']);
        });
    }

    /**
     * Supervisor rejection of leave request (restores balances).
     */
    public function rejectLeaveRequest(LeaveRequest $request, string $rejectorId, string $reason): LeaveRequest
    {
        if ($request->status !== 'pending') {
            throw new DomainException("Only pending leave requests can be rejected. Current status: {$request->status}");
        }

        return DB::transaction(function () use ($request, $rejectorId, $reason) {
            $year = (int) $request->start_date->format('Y');

            if ($request->leave_type !== 'unpaid') {
                $balance = $this->getOrCreateBalance($request->staff, $request->leave_type, $year);
                $balance->pending_days = max(0, $balance->pending_days - $request->total_days);
                $balance->remaining_days += $request->total_days;
                $balance->save();
            }

            $request->status = 'rejected';
            $request->approved_by = $rejectorId;
            $request->approved_at = Carbon::now();
            $request->rejection_reason = $reason;
            $request->save();

            return $request->load(['staff', 'approver']);
        });
    }

    /**
     * Get annual breakdown of leave quotas for a staff member.
     */
    public function getStaffLeaveBalances(Staff $staff, ?int $year = null): array
    {
        $yr = $year ?: (int) Carbon::now()->format('Y');
        $this->initializeYearlyBalances($staff, $yr);

        return LeaveBalance::where('staff_id', $staff->id)
            ->where('year', $yr)
            ->get()
            ->toArray();
    }
}
