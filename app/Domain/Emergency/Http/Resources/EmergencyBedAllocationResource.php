<?php

namespace App\Domain\Emergency\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmergencyBedAllocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'emergency_case_id' => $this->emergency_case_id,
            'bed_id' => $this->bed_id,
            'bed_number' => $this->bed?->bed_number,
            'ward_name' => $this->bed?->ward?->name,
            'is_override' => $this->is_override,
            'override_reason' => $this->override_reason,
            'priority_tier' => $this->priority_tier,
            'allocated_by' => $this->allocated_by,
            'allocated_by_name' => $this->allocatedByUser?->name,
            'allocated_at' => $this->allocated_at?->toIso8601String(),
            'released_at' => $this->released_at?->toIso8601String(),
            'release_notes' => $this->release_notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
