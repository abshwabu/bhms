<?php

namespace App\Domain\OPD\Http\Controllers;

use App\Domain\OPD\Http\Requests\StoreDoctorScheduleRequest;
use App\Domain\OPD\Models\DoctorSchedule;
use App\Domain\OPD\Services\DoctorAvailabilityService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Domain\Shared\Models\Branch;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DoctorScheduleController extends Controller
{
    public function __construct(protected DoctorAvailabilityService $availabilityService)
    {
    }

    /**
     * List doctor schedules.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DoctorSchedule::with(['doctor', 'department']);

        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->input('doctor_id'));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        return ApiResponse::success($query->get(), 'Doctor schedules retrieved.');
    }

    /**
     * Create or update doctor schedule.
     */
    public function store(StoreDoctorScheduleRequest $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;
        $branch = Branch::findOrFail($branchId);

        $data = $request->validated();
        $schedule = DoctorSchedule::create(array_merge($data, [
            'id' => (string) Str::uuid(),
            'organization_id' => $branch->organization_id,
            'branch_id' => $branch->id,
        ]));

        return ApiResponse::success($schedule->load(['doctor', 'department']), 'Schedule created.', 201);
    }

    /**
     * Check doctor slot availability for a given date.
     */
    public function availability(Request $request): JsonResponse
    {
        $request->validate([
            'doctor_id' => ['required', 'uuid', 'exists:users,id'],
            'date' => ['required', 'date'],
        ]);

        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;

        $availability = $this->availabilityService->getAvailableSlots(
            $request->input('doctor_id'),
            $request->input('date'),
            $branchId
        );

        return ApiResponse::success($availability, 'Doctor availability retrieved.');
    }
}
