<?php

namespace App\Domain\Laboratory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReferenceRangeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lab_test_id' => $this->lab_test_id,
            'parameter_name' => $this->parameter_name,
            'unit' => $this->unit,
            'gender' => $this->gender,
            'age_min_years' => $this->age_min_years,
            'age_max_years' => $this->age_max_years,
            'normal_low' => $this->normal_low,
            'normal_high' => $this->normal_high,
            'critical_low' => $this->critical_low,
            'critical_high' => $this->critical_high,
            'qualitative_normal' => $this->qualitative_normal,
        ];
    }
}
