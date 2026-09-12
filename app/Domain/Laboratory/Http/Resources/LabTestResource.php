<?php

namespace App\Domain\Laboratory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabTestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'category' => $this->category,
            'specimen_type' => $this->specimen_type,
            'container_type' => $this->container_type,
            'turn_around_time_minutes' => $this->turn_around_time_minutes,
            'price_cents' => $this->price_cents,
            'is_active' => $this->is_active,
            'reference_ranges' => ReferenceRangeResource::collection($this->whenLoaded('referenceRanges')),
        ];
    }
}
