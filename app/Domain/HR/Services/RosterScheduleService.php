<?php

namespace App\Domain\HR\Services;

use App\Domain\HR\Models\Shift;
use App\Domain\HR\Models\Staff;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class RosterScheduleService
{
    /**
     * Check if assigning a shift creates a double-booking conflict for the staff member.
     * Acceptance criterion: Roster conflicts (double-booked staff) are flagged before publishing.
     */
    public function checkConflicts(string $staffId, $startDatetime, $endDatetime, ?string $excludeShiftId = null): array
    {
        $start = Carbon::parse($startDatetime);
        $end = Carbon::parse($endDatetime);

        $conflicts = Shift::query()
            ->with(['staff', 'staff.role'])
            ->overlapping($staffId, $start, $end, $excludeShiftId)
            ->get();

        $formatted = [];
        foreach ($conflicts as $c) {
            $formatted[] = [
                'conflicting_shift_id' => $c->id,
                'shift_name' => $c->shift_name,
                'department' => $c->department,
                'shift_date' => $c->shift_date?->toDateString(),
                'start_datetime' => $c->start_datetime?->toIso8601String(),
                'end_datetime' => $c->end_datetime?->toIso8601String(),
                'staff_name' => $c->staff?->full_name,
                'conflict_reason' => "Double-booking: Already scheduled for '{$c->shift_name}' in {$c->department} ({$c->start_time} - {$c->end_time})",
            ];
        }

        return $formatted;
    }

    /**
     * Create a roster shift assignment.
     */
    public function createShift(array $data, ?string $userId = null): Shift
    {
        $staff = Staff::findOrFail($data['staff_id']);

        $shiftDate = Carbon::parse($data['shift_date'])->toDateString();
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];

        $startDatetime = Carbon::parse("{$shiftDate} {$startTime}");
        $endDatetime = Carbon::parse("{$shiftDate} {$endTime}");

        // If end time is earlier than start time, it crosses midnight (e.g. 20:00 to 08:00)
        if ($endDatetime->lessThanOrEqualTo($startDatetime)) {
            $endDatetime->addDay();
        }

        // Validate conflicts
        $conflicts = $this->checkConflicts($staff->id, $startDatetime, $endDatetime);
        if (!empty($conflicts) && !($data['allow_overlap'] ?? false)) {
            throw new DomainException("Roster Conflict: Staff member {$staff->full_name} is already booked on an overlapping shift ({$conflicts[0]['shift_name']} in {$conflicts[0]['department']}).");
        }

        return Shift::create([
            'organization_id' => $staff->organization_id,
            'branch_id' => $staff->branch_id,
            'staff_id' => $staff->id,
            'shift_name' => $data['shift_name'] ?? 'Duty Shift',
            'shift_type' => $data['shift_type'] ?? 'morning',
            'department' => $data['department'] ?? $staff->department,
            'shift_date' => $shiftDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'start_datetime' => $startDatetime,
            'end_datetime' => $endDatetime,
            'status' => 'scheduled',
            'is_published' => $data['is_published'] ?? false,
            'created_by' => $userId,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Publish drafted roster shifts after verifying zero conflicts.
     */
    public function publishRoster(array $shiftIds): array
    {
        $shifts = Shift::whereIn('id', $shiftIds)->get();
        $conflictReport = [];

        foreach ($shifts as $s) {
            $conflicts = $this->checkConflicts($s->staff_id, $s->start_datetime, $s->end_datetime, $s->id);
            if (!empty($conflicts)) {
                $conflictReport[] = [
                    'shift_id' => $s->id,
                    'staff_name' => $s->staff?->full_name,
                    'conflicts' => $conflicts,
                ];
            }
        }

        if (!empty($conflictReport)) {
            return [
                'published' => false,
                'message' => 'Publishing blocked: Roster conflicts detected. Double-booked staff must be resolved before publishing.',
                'conflicts' => $conflictReport,
            ];
        }

        Shift::whereIn('id', $shiftIds)->update(['is_published' => true]);

        return [
            'published' => true,
            'published_count' => count($shiftIds),
            'message' => 'Roster published successfully to staff portal.',
        ];
    }

    /**
     * Get weekly/monthly roster schedule calendar matrix.
     */
    public function getRosterGrid(?string $branchId, string $startDate, string $endDate, ?string $department = null): array
    {
        $query = Shift::query()
            ->with(['staff', 'staff.role'])
            ->whereBetween('shift_date', [Carbon::parse($startDate)->toDateString(), Carbon::parse($endDate)->toDateString()])
            ->orderBy('shift_date', 'asc')
            ->orderBy('start_time', 'asc');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($department) {
            $query->where('department', $department);
        }

        $shifts = $query->get();

        return $shifts->groupBy(fn($s) => $s->shift_date->toDateString())->toArray();
    }
}
