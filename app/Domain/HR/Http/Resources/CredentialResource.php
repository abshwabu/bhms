<?php

namespace App\Domain\HR\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CredentialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'staff_id' => $this->staff_id,
            'staff_name' => $this->staff?->full_name,
            'staff_employee_id' => $this->staff?->employee_id,
            'credential_type' => $this->credential_type,
            'title' => $this->title,
            'license_number' => $this->license_number,
            'issuing_authority' => $this->issuing_authority,
            'issue_date' => $this->issue_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'days_until_expiry' => $this->days_until_expiry,
            'is_expired' => $this->is_expired,
            'is_expiring_soon' => $this->is_expiring_soon,
            'verification_status' => $this->verification_status,
            'document_url' => $this->document_url,
            'verified_at' => $this->verified_at?->toIso8601String(),
            'notes' => $this->notes,
        ];
    }
}
