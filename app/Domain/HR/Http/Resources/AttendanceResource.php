<?php

namespace App\Domain\HR\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'staff_id' => $this->staff_id,
            'staff_name' => $this->staff?->full_name,
            'staff_employee_id' => $this->staff?->employee_id,
            'date' => $this->date?->toDateString(),
            'check_in_time' => $this->check_in_time?->toIso8601String(),
            'check_out_time' => $this->check_out_time?->toIso8601String(),
            'total_minutes_worked' => $this->total_minutes_worked,
            'hours_worked' => $this->hours_worked,
            'status' => $this->status,
            'is_punctual' => $this->is_punctual,
            'minutes_late' => $this->minutes_late,
            'notes' => $this->notes,
        ];
    }
}
