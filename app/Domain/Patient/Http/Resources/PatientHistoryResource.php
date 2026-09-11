<?php

namespace App\Domain\Patient\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'category' => $this->category,
            'condition_or_procedure' => $this->condition_or_procedure,
            'icd10_code' => $this->icd10_code,
            'diagnosed_date' => $this->diagnosed_date ? $this->diagnosed_date->format('Y-m-d') : null,
            'status' => $this->status,
            'severity' => $this->severity,
            'notes' => $this->notes,
            'recorded_by' => $this->recorder ? [
                'id' => $this->recorder->id,
                'name' => $this->recorder->name,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
