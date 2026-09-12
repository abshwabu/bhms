<?php

namespace App\Domain\Emergency\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AmbulanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'vehicle_number' => $this->vehicle_number,
            'call_sign' => $this->call_sign,
            'ambulance_type' => $this->ambulance_type,
            'model' => $this->model,
            'plate_number' => $this->plate_number,
            'status' => $this->status,
            'is_available' => $this->is_available,
            'equipment' => $this->equipment ?? [],
            'current_latitude' => $this->current_latitude,
            'current_longitude' => $this->current_longitude,
            'has_live_gps' => $this->has_live_gps,
            'heading' => $this->heading,
            'speed_kmh' => $this->speed_kmh,
            'fuel_percentage' => $this->fuel_percentage,
            'last_telemetry_at' => $this->last_telemetry_at?->toIso8601String(),
            'assigned_driver_name' => $this->assigned_driver_name,
            'assigned_paramedic_name' => $this->assigned_paramedic_name,
            'notes' => $this->notes,
            'active_dispatch' => $this->whenLoaded('activeDispatch', fn() => new AmbulanceDispatchResource($this->activeDispatch)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
