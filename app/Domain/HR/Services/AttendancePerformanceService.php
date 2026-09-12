<?php

namespace App\Domain\HR\Services;

use App\Domain\Clinical\Models\EhrRecord;
use App\Domain\HR\Models\Attendance;
use App\Domain\HR\Models\Shift;
use App\Domain\HR\Models\Staff;
use App\Domain\OPD\Models\Appointment;
use Carbon\Carbon;
use DomainException;

class AttendancePerformanceService
{
    /**
     * Daily roll-call clock-in.
     */
    public function checkIn(Staff $staff, ?string $ip = null, ?Carbon $checkInTime = null): Attendance
    {
        $now = $checkInTime ?? Carbon::now();
        $today = $now->copy()->startOfDay();

        $existing = Attendance::where('staff_id', $staff->id)
            ->where('date', $today->toDateString())
            ->first();

        if ($existing && $existing->check_in_time) {
            throw new DomainException("Staff member {$staff->full_name} has already clocked in today at " . Carbon::parse($existing->check_in_time)->format('H:i'));
        }

        // Look for scheduled shift today
        $shift = Shift::where('staff_id', $staff->id)
            ->where('shift_date', $today->toDateString())
            ->first();

        $isPunctual = true;
        $minutesLate = 0;

        if ($shift) {
            $shiftStart = Carbon::parse("{$today->toDateString()} {$shift->start_time}");
            if ($now->greaterThan($shiftStart)) {
                $minutesLate = (int) $shiftStart->diffInMinutes($now);
                if ($minutesLate > 10) { // 10 minutes grace period
                    $isPunctual = false;
                }
            }
        }

        return Attendance::updateOrCreate(
            ['staff_id' => $staff->id, 'date' => $today->toDateString()],
            [
                'organization_id' => $staff->organization_id,
                'branch_id' => $staff->branch_id,
                'shift_id' => $shift?->id,
                'check_in_time' => $now,
                'status' => $isPunctual ? 'present' : 'late',
                'is_punctual' => $isPunctual,
                'minutes_late' => $minutesLate,
                'check_in_ip' => $ip,
            ]
        );
    }

    /**
     * Daily roll-call clock-out.
     */
    public function checkOut(Staff $staff, ?Carbon $checkOutTime = null): Attendance
    {
        $now = $checkOutTime ?? Carbon::now();
        $today = $now->copy()->startOfDay();

        $attendance = Attendance::where('staff_id', $staff->id)
            ->where('date', $today->toDateString())
            ->first();

        if (!$attendance || !$attendance->check_in_time) {
            throw new DomainException("Cannot clock out without an active check-in today.");
        }

        $checkIn = Carbon::parse($attendance->check_in_time);
        $totalMinutes = (int) $checkIn->diffInMinutes($now);

        $attendance->check_out_time = $now;
        $attendance->total_minutes_worked = $totalMinutes;
        $attendance->save();

        return $attendance;
    }

    /**
     * Calculate basic staff KPIs (punctuality, shifts completed, patients seen).
     */
    public function getStaffPerformance(Staff $staff, ?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfMonth();

        $attendances = Attendance::where('staff_id', $staff->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $totalDaysPresent = $attendances->whereIn('status', ['present', 'late', 'half_day'])->count();
        $punctualDays = $attendances->where('is_punctual', true)->count();
        $totalMinutesWorked = $attendances->sum('total_minutes_worked');
        $punctualityRate = $totalDaysPresent > 0 ? round(($punctualDays / $totalDaysPresent) * 100, 1) : 100.0;

        $shiftsCount = Shift::where('staff_id', $staff->id)
            ->whereBetween('shift_date', [$start->toDateString(), $end->toDateString()])
            ->count();

        // Patients seen count (if doctor/consultant linked to User)
        $patientsSeen = 0;
        if ($staff->user_id) {
            // Count from clinical EHR records or completed OPD appointments
            $patientsSeen = EhrRecord::where('doctor_id', $staff->user_id)
                ->whereBetween('created_at', [$start, $end])
                ->count();

            if ($patientsSeen === 0) {
                $patientsSeen = Appointment::where('doctor_id', $staff->user_id)
                    ->whereBetween('appointment_date', [$start->toDateString(), $end->toDateString()])
                    ->count();
            }
        }

        return [
            'staff_id' => $staff->id,
            'name' => $staff->full_name,
            'designation' => $staff->designation,
            'department' => $staff->department,
            'period' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
            'shifts_scheduled' => $shiftsCount,
            'days_present' => $totalDaysPresent,
            'total_hours_worked' => round($totalMinutesWorked / 60, 1),
            'punctuality_rate_percentage' => $punctualityRate,
            'patients_seen_count' => $patientsSeen,
        ];
    }

    /**
     * Optional Payroll integration calculation hook.
     */
    public function getPayrollPreview(?string $branchId, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $staffQuery = Staff::query()->where('status', 'active');
        if ($branchId) {
            $staffQuery->where('branch_id', $branchId);
        }

        $allStaff = $staffQuery->with('role')->get();
        $payrollList = [];

        foreach ($allStaff as $st) {
            $totalMinutes = (int) Attendance::where('staff_id', $st->id)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->sum('total_minutes_worked');

            $hours = round($totalMinutes / 60, 2);
            $grossPayCents = 0;

            if ($st->hourly_rate_cents > 0) {
                $grossPayCents = (int) round($hours * $st->hourly_rate_cents);
            } elseif ($st->monthly_salary_cents > 0) {
                $grossPayCents = $st->monthly_salary_cents;
            }

            $payrollList[] = [
                'staff_id' => $st->id,
                'employee_id' => $st->employee_id,
                'name' => $st->full_name,
                'role' => $st->role?->name,
                'department' => $st->department,
                'employment_type' => $st->employment_type,
                'hours_worked' => $hours,
                'hourly_rate' => $st->hourly_rate,
                'monthly_salary' => $st->monthly_salary,
                'gross_pay_cents' => $grossPayCents,
                'gross_pay' => $grossPayCents / 100,
            ];
        }

        return $payrollList;
    }
}
