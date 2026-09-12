<?php

namespace App\Domain\IPD\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'ward_type' => $this->ward_type,
            'floor_number' => $this->floor_number,
            'capacity' => $this->capacity,
            'gender_restriction' => $this->gender_restriction,
            'daily_rate' => $this->daily_rate,
            'is_active' => $this->is_active,
            'total_beds' => $this->whenLoaded('beds', fn () => $this->beds->count()),
            'occupied_beds' => $this->whenLoaded('beds', fn () => $this->beds->where('status', 'occupied')->count()),
            'available_beds' => $this->whenLoaded('beds', fn () => $this->beds->where('status', 'available')->count()),
            'beds' => BedResource::collection($this->whenLoaded('beds')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
