<?php

namespace App\Domain\Clinical\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'prescription_id' => $this->prescription_id,
            'medication_name' => $this->medication_name,
            'generic_name' => $this->generic_name,
            'form' => $this->form,
            'dosage' => $this->dosage,
            'route' => $this->route,
            'frequency' => $this->frequency,
            'duration_days' => $this->duration_days,
            'quantity' => $this->quantity,
            'instructions' => $this->instructions,
            'is_substitution_allowed' => $this->is_substitution_allowed,
            'status' => $this->status,
        ];
    }
}
