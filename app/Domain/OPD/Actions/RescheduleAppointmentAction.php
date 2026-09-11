<?php

namespace App\Domain\OPD\Actions;

use App\Domain\OPD\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RescheduleAppointmentAction
{
    public function execute(Appointment $appointment, string $newDate, string $newStartTime, ?string $newEndTime = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $newDate, $newStartTime, $newEndTime) {
            if (in_array($appointment->status, ['completed', 'cancelled'])) {
                throw new InvalidArgumentException("Cannot reschedule an appointment that is already {$appointment->status}.");
            }

            $date = Carbon::parse($newDate)->toDateString();
            $startTime = substr($newStartTime, 0, 8);
            $endTime = $newEndTime 
                ? substr($newEndTime, 0, 8) 
                : Carbon::parse("{$date} {$startTime}")->addMinutes(15)->format('H:i:s');

            // Conflict check excluding current appointment
            $existingConflict = Appointment::where('doctor_id', $appointment->doctor_id)
                ->where('id', '!=', $appointment->id)
                ->whereDate('appointment_date', $date)
                ->whereNotIn('status', ['cancelled', 'rescheduled'])
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where(function ($sub) use ($startTime, $endTime) {
                        $sub->where('start_time', '<=', $startTime)
                            ->where('end_time', '>', $startTime);
                    })->orWhere(function ($sub) use ($startTime, $endTime) {
                        $sub->where('start_time', '<', $endTime)
                            ->where('end_time', '>=', $endTime);
                    });
                })
                ->lockForUpdate()
                ->first();

            if ($existingConflict) {
                throw new InvalidArgumentException(
                    "Doctor already has an appointment during this time window ({$startTime} - {$endTime}) on {$date}."
                );
            }

            $appointment->update([
                'appointment_date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => 'scheduled',
            ]);

            return $appointment->fresh(['patient', 'doctor', 'department']);
        });
    }
}
