<?php

namespace App\Domain\Emergency\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TriageRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'emergency_case_id' => $this->emergency_case_id,
            'triaged_by' => $this->triaged_by,
            'triaged_by_name' => $this->triageNurse?->name,
            'triaged_at' => $this->triaged_at?->toIso8601String(),
            'esi_level' => $this->esi_level,
            'severity_label' => $this->severity_label,
            'triage_category' => $this->triage_category,
            'vital_signs' => $this->vital_signs ?? [],
            'is_danger_zone_vitals' => $this->is_danger_zone_vitals,
            'red_flags' => $this->red_flags ?? [],
            'assessment_notes' => $this->assessment_notes,
            'reassessment_interval_minutes' => $this->reassessment_interval_minutes,
            'reassessment_due_at' => $this->reassessment_due_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
