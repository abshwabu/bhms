<?php

namespace App\Domain\HR\Http\Controllers;

use App\Domain\HR\Http\Resources\AttendanceResource;
use App\Domain\HR\Models\Attendance;
use App\Domain\HR\Models\Staff;
use App\Domain\HR\Services\AttendancePerformanceService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendancePerformanceService $attendanceService
    ) {}

    /**
     * List attendance records.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Attendance::query()
            ->with(['staff.role', 'shift'])
            ->orderBy('date', 'desc')
            ->orderBy('check_in_time', 'desc');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->input('staff_id'));
        }

        if ($request->filled('date')) {
            $query->where('date', Carbon::parse($request->input('date'))->toDateString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $records = $query->paginate($request->input('per_page', 50));

        return ApiResponse::paginated(
            $records->through(fn($a) => new AttendanceResource($a)),
            'Attendance logs retrieved.'
        );
    }

    /**
     * Clock in.
     */
    public function checkIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'staff_id' => ['required', 'uuid', 'exists:staff,id'],
            'check_in_time' => ['nullable', 'date'],
        ]);

        $staff = Staff::findOrFail($validated['staff_id']);
        $checkInTime = !empty($validated['check_in_time']) ? Carbon::parse($validated['check_in_time']) : null;

        try {
            $attendance = $this->attendanceService->checkIn($staff, $request->ip(), $checkInTime);

            return ApiResponse::success(
                new AttendanceResource($attendance->load('staff')),
                "Clock-in registered for {$staff->full_name} at " . Carbon::parse($attendance->check_in_time)->format('H:i') . ($attendance->is_punctual ? ' (Punctual)' : " (Late by {$attendance->minutes_late} mins)"),
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'ATTENDANCE_CHECKIN_ERROR', [], 422);
        }
    }

    /**
     * Clock out.
     */
    public function checkOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'staff_id' => ['required', 'uuid', 'exists:staff,id'],
            'check_out_time' => ['nullable', 'date'],
        ]);

        $staff = Staff::findOrFail($validated['staff_id']);
        $checkOutTime = !empty($validated['check_out_time']) ? Carbon::parse($validated['check_out_time']) : null;

        try {
            $attendance = $this->attendanceService->checkOut($staff, $checkOutTime);

            return ApiResponse::success(
                new AttendanceResource($attendance->load('staff')),
                "Clock-out registered for {$staff->full_name}. Total hours worked: {$attendance->hours_worked} hrs."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'ATTENDANCE_CHECKOUT_ERROR', [], 422);
        }
    }

    /**
     * Daily roll-call status overview for front desk / HR.
     */
    public function todayStatus(Request $request): JsonResponse
    {
        $today = Carbon::today()->toDateString();
        $branchId = $request->input('branch_id');

        $staffQuery = Staff::query()->where('status', 'active');
        if ($branchId) {
            $staffQuery->where('branch_id', $branchId);
        }
        $totalActiveStaff = $staffQuery->count();

        $attQuery = Attendance::query()->where('date', $today);
        if ($branchId) {
            $attQuery->where('branch_id', $branchId);
        }

        $presentCount = (clone $attQuery)->whereIn('status', ['present', 'late', 'half_day'])->count();
        $punctualCount = (clone $attQuery)->where('is_punctual', true)->count();
        $lateCount = (clone $attQuery)->where('status', 'late')->count();
        $absentCount = max(0, $totalActiveStaff - $presentCount);

        return ApiResponse::success([
            'date' => $today,
            'total_active_staff' => $totalActiveStaff,
            'present_count' => $presentCount,
            'punctual_count' => $punctualCount,
            'late_count' => $lateCount,
            'absent_count' => $absentCount,
            'turnout_percentage' => $totalActiveStaff > 0 ? round(($presentCount / $totalActiveStaff) * 100, 1) : 0,
        ], 'Daily attendance summary retrieved.');
    }
}
