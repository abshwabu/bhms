<?php

namespace App\Domain\Emergency\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AmbulanceDispatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'ambulance_id' => $this->ambulance_id,
            'emergency_case_id' => $this->emergency_case_id,
            'dispatch_number' => $this->dispatch_number,
            'caller_name' => $this->caller_name,
            'caller_phone' => $this->caller_phone,
            'pickup_address' => $this->pickup_address,
            'pickup_latitude' => $this->pickup_latitude,
            'pickup_longitude' => $this->pickup_longitude,
            'destination_address' => $this->destination_address,
            'destination_latitude' => $this->destination_latitude,
            'destination_longitude' => $this->destination_longitude,
            'priority' => $this->priority,
            'nature_of_emergency' => $this->nature_of_emergency,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'patient_condition_notes' => $this->patient_condition_notes,
            'response_time_minutes' => $this->response_time_minutes,
            'dispatched_at' => $this->dispatched_at?->toIso8601String(),
            'en_route_scene_at' => $this->en_route_scene_at?->toIso8601String(),
            'arrived_scene_at' => $this->arrived_scene_at?->toIso8601String(),
            'departed_scene_at' => $this->departed_scene_at?->toIso8601String(),
            'arrived_hospital_at' => $this->arrived_hospital_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'ambulance' => $this->whenLoaded('ambulance', fn() => new AmbulanceResource($this->ambulance)),
            'dispatcher_name' => $this->dispatcher?->name,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
