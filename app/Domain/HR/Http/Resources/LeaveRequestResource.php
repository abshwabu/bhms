<?php

namespace App\Domain\HR\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'staff_id' => $this->staff_id,
            'staff_name' => $this->staff?->full_name,
            'staff_employee_id' => $this->staff?->employee_id,
            'staff_department' => $this->staff?->department,
            'leave_type' => $this->leave_type,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'total_days' => $this->total_days,
            'reason' => $this->reason,
            'status' => $this->status,
            'approved_by' => [
                'id' => $this->approver?->id,
                'name' => $this->approver?->name,
            ],
            'approved_at' => $this->approved_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
