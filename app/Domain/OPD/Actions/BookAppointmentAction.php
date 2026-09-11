<?php

namespace App\Domain\OPD\Actions;

use App\Domain\OPD\Models\Appointment;
use App\Domain\Shared\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class BookAppointmentAction
{
    /**
     * Book an appointment with strict double-booking conflict prevention.
     */
    public function execute(array $data, Branch $branch, ?string $bookedByUserId = null): Appointment
    {
        return DB::transaction(function () use ($data, $branch, $bookedByUserId) {
            $doctorId = $data['doctor_id'];
            $date = Carbon::parse($data['appointment_date'])->toDateString();
            $startTime = substr($data['start_time'], 0, 8);
            
            // If end_time is not provided, default to 15 minutes after start_time
            $endTime = !empty($data['end_time']) 
                ? substr($data['end_time'], 0, 8) 
                : Carbon::parse("{$date} {$startTime}")->addMinutes(15)->format('H:i:s');

            // 1. Conflict Check: Strict double-booking prevention with lock
            $existingConflict = Appointment::where('doctor_id', $doctorId)
                ->whereDate('appointment_date', $date)
                ->whereNotIn('status', ['cancelled', 'rescheduled'])
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where(function ($sub) use ($startTime, $endTime) {
                        $sub->where('start_time', '<=', $startTime)
                            ->where('end_time', '>', $startTime);
                    })->orWhere(function ($sub) use ($startTime, $endTime) {
                        $sub->where('start_time', '<', $endTime)
                            ->where('end_time', '>=', $endTime);
                    })->orWhere(function ($sub) use ($startTime, $endTime) {
                        $sub->where('start_time', '>=', $startTime)
                            ->where('end_time', '<=', $endTime);
                    });
                })
                ->lockForUpdate()
                ->first();

            if ($existingConflict) {
                throw new InvalidArgumentException(
                    "Doctor is already booked for this time slot ({$startTime} - {$endTime}) on {$date}. Double booking is prohibited."
                );
            }

            // 2. Generate unique appointment number
            $year = date('Y');
            $branchCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $branch->code ?? 'HSP'));
            $countToday = Appointment::where('branch_id', $branch->id)
                ->whereDate('created_at', now()->toDateString())
                ->count() + 1;
            $appointmentNumber = sprintf('APT-%d-%s-%06d', $year, $branchCode, $countToday);

            // 3. Create appointment
            $appointment = Appointment::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'department_id' => $data['department_id'] ?? null,
                'patient_id' => $data['patient_id'],
                'doctor_id' => $doctorId,
                'parent_appointment_id' => $data['parent_appointment_id'] ?? null,
                'appointment_number' => $appointmentNumber,
                'appointment_date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'type' => $data['type'] ?? $data['appointment_type'] ?? 'in_person',
                'status' => 'scheduled',
                'reason_for_visit' => $data['reason_for_visit'] ?? null,
                'booked_by' => $bookedByUserId,
            ]);

            return $appointment->load(['patient', 'doctor', 'department', 'parentAppointment']);
        });
    }
}
