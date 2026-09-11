<?php

namespace App\Domain\OPD\Services;

use App\Domain\OPD\Models\Appointment;
use App\Domain\OPD\Models\DoctorSchedule;
use Carbon\Carbon;

class DoctorAvailabilityService
{
    /**
     * Retrieve available and booked slots for a given doctor and date.
     */
    public function getAvailableSlots(string $doctorId, string $date, ?string $branchId = null): array
    {
        $carbonDate = Carbon::parse($date);
        $dayOfWeek = $carbonDate->dayOfWeek; // 0 = Sunday, 1 = Monday ... 6 = Saturday

        // 1. Check for specific date override first (e.g. holiday / custom shift)
        $scheduleQuery = DoctorSchedule::where('doctor_id', $doctorId);
        if ($branchId) {
            $scheduleQuery->where('branch_id', $branchId);
        }

        $specificSchedule = (clone $scheduleQuery)
            ->where('schedule_type', 'specific_date')
            ->whereDate('specific_date', $date)
            ->first();

        $schedule = $specificSchedule;

        // 2. If no specific date schedule, look for recurring schedule
        if (!$schedule) {
            $schedule = (clone $scheduleQuery)
                ->where('schedule_type', 'recurring')
                ->where('day_of_week', $dayOfWeek)
                ->first();
        }

        // If no schedule exists or doctor is marked unavailable (e.g. on leave)
        if (!$schedule || !$schedule->is_available) {
            return [
                'doctor_id' => $doctorId,
                'date' => $date,
                'is_available' => false,
                'reason' => !$schedule ? 'No schedule configured for this day' : 'Doctor is on leave / unavailable',
                'slots' => [],
            ];
        }

        // 3. Generate base slots from schedule
        $generatedSlots = $schedule->generateTimeSlots($date);

        // 4. Fetch all existing active appointments for doctor on that date
        $bookedAppointments = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->whereNotIn('status', ['cancelled', 'rescheduled'])
            ->get();

        $slots = [];
        foreach ($generatedSlots as $slot) {
            $isBooked = $bookedAppointments->contains(function ($appt) use ($slot) {
                // Check if appointment start_time matches slot start_time
                return substr($appt->start_time, 0, 5) === substr($slot['start_time'], 0, 5);
            });

            $slots[] = [
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'display' => $slot['display'],
                'is_booked' => $isBooked,
                'is_available' => !$isBooked,
            ];
        }

        return [
            'doctor_id' => $doctorId,
            'date' => $date,
            'is_available' => true,
            'department_id' => $schedule->department_id,
            'room_number' => $schedule->room_number,
            'slot_duration_minutes' => $schedule->slot_duration_minutes,
            'slots' => $slots,
        ];
    }
}
