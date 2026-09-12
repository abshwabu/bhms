<?php

namespace App\Domain\HR\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'staff_id' => $this->staff_id,
            'staff_name' => $this->staff?->full_name,
            'staff_employee_id' => $this->staff?->employee_id,
            'staff_role' => $this->staff?->role?->name,
            'shift_name' => $this->shift_name,
            'shift_type' => $this->shift_type,
            'department' => $this->department,
            'shift_date' => $this->shift_date?->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'start_datetime' => $this->start_datetime?->toIso8601String(),
            'end_datetime' => $this->end_datetime?->toIso8601String(),
            'duration_hours' => $this->duration_hours,
            'status' => $this->status,
            'is_published' => $this->is_published,
            'notes' => $this->notes,
        ];
    }
}
