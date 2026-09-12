<?php

namespace App\Domain\Laboratory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabResultItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lab_result_id' => $this->lab_result_id,
            'parameter_name' => $this->parameter_name,
            'measured_value' => $this->measured_value,
            'numeric_value' => $this->numeric_value,
            'unit' => $this->unit,
            'reference_low' => $this->reference_low,
            'reference_high' => $this->reference_high,
            'reference_range_display' => ($this->reference_low !== null && $this->reference_high !== null)
                ? "{$this->reference_low} - {$this->reference_high} {$this->unit}"
                : 'Normal',
            'flag' => $this->flag,
            'is_abnormal' => $this->isAbnormal(),
            'is_critical' => $this->isCritical(),
            'notes' => $this->notes,
        ];
    }
}
