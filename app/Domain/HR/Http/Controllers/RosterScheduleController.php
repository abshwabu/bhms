<?php

namespace App\Domain\HR\Http\Controllers;

use App\Domain\HR\Http\Resources\ShiftResource;
use App\Domain\HR\Models\Shift;
use App\Domain\HR\Services\RosterScheduleService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RosterScheduleController extends Controller
{
    public function __construct(
        protected RosterScheduleService $rosterService
    ) {}

    /**
     * List duty roster shifts.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Shift::query()
            ->with(['staff.role', 'creator'])
            ->orderBy('shift_date', 'asc')
            ->orderBy('start_time', 'asc');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->input('staff_id'));
        }

        if ($request->filled('department')) {
            $query->where('department', $request->input('department'));
        }

        if ($request->filled('start_date')) {
            $query->where('shift_date', '>=', Carbon::parse($request->input('start_date'))->toDateString());
        }

        if ($request->filled('end_date')) {
            $query->where('shift_date', '<=', Carbon::parse($request->input('end_date'))->toDateString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $shifts = $query->paginate($request->input('per_page', 50));

        return ApiResponse::paginated(
            $shifts->through(fn($s) => new ShiftResource($s)),
            'Roster shifts retrieved.'
        );
    }

    /**
     * Create a shift assignment with automated conflict / double-booking check.
     * Acceptance criterion: Roster conflicts (double-booked staff) are flagged before publishing.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'staff_id' => ['required', 'uuid', 'exists:staff,id'],
            'shift_name' => ['required', 'string', 'max:100'],
            'shift_type' => ['required', 'string', 'in:morning,evening,night,on_call,custom'],
            'department' => ['nullable', 'string', 'max:50'],
            'shift_date' => ['required', 'date'],
            'start_time' => ['required', 'string'],
            'end_time' => ['required', 'string'],
            'is_published' => ['nullable', 'boolean'],
            'allow_overlap' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $userId = $request->user()?->id ?? $request->input('created_by');

        try {
            $shift = $this->rosterService->createShift($validated, $userId);

            return ApiResponse::success(
                new ShiftResource($shift->load(['staff.role', 'creator'])),
                "Shift '{$shift->shift_name}' scheduled for {$shift->staff->full_name}.",
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'ROSTER_CONFLICT_ERROR', [], 422);
        }
    }

    /**
     * Check for double-booking conflicts without saving.
     */
    public function checkConflicts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'staff_id' => ['required', 'uuid', 'exists:staff,id'],
            'shift_date' => ['required', 'date'],
            'start_time' => ['required', 'string'],
            'end_time' => ['required', 'string'],
            'exclude_shift_id' => ['nullable', 'uuid'],
        ]);

        $shiftDate = Carbon::parse($validated['shift_date'])->toDateString();
        $start = Carbon::parse("{$shiftDate} {$validated['start_time']}");
        $end = Carbon::parse("{$shiftDate} {$validated['end_time']}");
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $conflicts = $this->rosterService->checkConflicts(
            $validated['staff_id'],
            $start,
            $end,
            $validated['exclude_shift_id'] ?? null
        );

        return ApiResponse::success([
            'has_conflict' => !empty($conflicts),
            'conflicts_count' => count($conflicts),
            'conflicts' => $conflicts,
        ], 'Conflict inspection completed.');
    }

    /**
     * Publish drafted roster shifts after conflict audit.
     */
    public function publish(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shift_ids' => ['required', 'array', 'min:1'],
            'shift_ids.*' => ['required', 'uuid', 'exists:shifts,id'],
        ]);

        $result = $this->rosterService->publishRoster($validated['shift_ids']);

        if (!$result['published']) {
            return ApiResponse::error($result['message'], 'ROSTER_PUBLISH_BLOCKED', $result['conflicts'], 422);
        }

        return ApiResponse::success($result, $result['message']);
    }

    /**
     * Grouped calendar grid.
     */
    public function grid(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'department' => ['nullable', 'string'],
            'branch_id' => ['nullable', 'uuid'],
        ]);

        $grid = $this->rosterService->getRosterGrid(
            $validated['branch_id'] ?? null,
            $validated['start_date'],
            $validated['end_date'],
            $validated['department'] ?? null
        );

        return ApiResponse::success($grid, 'Roster calendar grid retrieved.');
    }

    /**
     * Cancel a shift.
     */
    public function cancel(Shift $shift): JsonResponse
    {
        $shift->status = 'cancelled';
        $shift->save();

        return ApiResponse::success(
            new ShiftResource($shift),
            "Shift '{$shift->shift_name}' cancelled."
        );
    }
}
