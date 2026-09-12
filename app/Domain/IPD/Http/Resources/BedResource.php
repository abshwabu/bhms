<?php

namespace App\Domain\IPD\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ward_id' => $this->ward_id,
            'bed_number' => $this->bed_number,
            'bed_type' => $this->bed_type,
            'status' => $this->status,
            'features' => $this->features ?? [],
            'daily_rate_override' => $this->daily_rate_override,
            'is_active' => $this->is_active,
            'ward' => $this->whenLoaded('ward', fn () => [
                'id' => $this->ward->id,
                'name' => $this->ward->name,
                'code' => $this->ward->code,
                'ward_type' => $this->ward->ward_type,
            ]),
            'current_admission' => $this->whenLoaded('currentAdmission', fn () => [
                'id' => $this->currentAdmission->id,
                'admission_number' => $this->currentAdmission->admission_number,
                'patient_id' => $this->currentAdmission->patient_id,
                'patient_name' => $this->currentAdmission->patient?->full_name,
                'admitted_at' => $this->currentAdmission->admitted_at?->toIso8601String(),
                'admitting_diagnosis' => $this->currentAdmission->admitting_diagnosis,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
