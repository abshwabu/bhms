<?php

namespace App\Domain\HR\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'staff_role_id' => $this->staff_role_id,
            'employee_id' => $this->employee_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'department' => $this->department,
            'designation' => $this->designation,
            'employment_type' => $this->employment_type,
            'joining_date' => $this->joining_date?->toDateString(),
            'status' => $this->status,
            'hourly_rate_cents' => $this->hourly_rate_cents,
            'hourly_rate' => $this->hourly_rate,
            'monthly_salary_cents' => $this->monthly_salary_cents,
            'monthly_salary' => $this->monthly_salary,
            'has_expiring_credentials' => $this->has_expiring_credentials,
            'role' => $this->role ? [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'code' => $this->role->code,
                'department' => $this->role->department,
                'is_medical' => $this->role->is_medical,
            ] : null,
            'credentials' => CredentialResource::collection($this->whenLoaded('credentials')),
            'credentials_count' => $this->credentials()->count(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
