<?php

namespace App\Domain\OPD\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Models\User;
use App\Domain\Shared\Traits\BelongsToBranch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSchedule extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'doctor_schedules';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'department_id',
        'doctor_id',
        'schedule_type',
        'day_of_week',
        'specific_date',
        'start_time',
        'end_time',
        'slot_duration_minutes',
        'max_patients',
        'is_available',
        'room_number',
        'notes',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'specific_date' => 'date',
        'slot_duration_minutes' => 'integer',
        'max_patients' => 'integer',
        'is_available' => 'boolean',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Generate discrete time slots between start_time and end_time.
     * e.g. 09:00, 09:15, 09:30, 09:45...
     */
    public function generateTimeSlots(string $date): array
    {
        if (!$this->is_available) {
            return [];
        }

        $slots = [];
        $slotMinutes = $this->slot_duration_minutes ?: 15;

        $startTime = Carbon::parse("{$date} {$this->start_time}");
        $endTime = Carbon::parse("{$date} {$this->end_time}");

        $current = $startTime->copy();

        while ($current->copy()->addMinutes($slotMinutes)->lte($endTime)) {
            $slotEnd = $current->copy()->addMinutes($slotMinutes);
            $slots[] = [
                'start_time' => $current->format('H:i'),
                'end_time' => $slotEnd->format('H:i'),
                'display' => $current->format('h:i A') . ' - ' . $slotEnd->format('h:i A'),
            ];
            $current->addMinutes($slotMinutes);
        }

        return $slots;
    }
}
