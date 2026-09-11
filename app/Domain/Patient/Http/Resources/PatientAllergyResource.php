<?php

namespace App\Domain\Patient\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientAllergyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'allergen' => $this->allergen,
            'allergen_type' => $this->allergen_type,
            'reaction' => $this->reaction,
            'severity' => $this->severity,
            'status' => $this->status,
            'diagnosed_at' => $this->diagnosed_at ? $this->diagnosed_at->format('Y-m-d') : null,
            'notes' => $this->notes,
            'recorded_by' => $this->recorder ? [
                'id' => $this->recorder->id,
                'name' => $this->recorder->name,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
