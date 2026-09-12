<?php

namespace App\Domain\Emergency\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmergencyCaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'case_number' => $this->case_number,
            'patient_id' => $this->patient_id,
            'display_patient_name' => $this->display_patient_name,
            'patient_temp_name' => $this->patient_temp_name,
            'patient_gender' => $this->patient_gender,
            'patient_estimated_age' => $this->patient_estimated_age,
            'arrival_mode' => $this->arrival_mode,
            'ambulance_dispatch_id' => $this->ambulance_dispatch_id,
            'arrival_datetime' => $this->arrival_datetime?->toIso8601String(),
            'chief_complaint' => $this->chief_complaint,
            'initial_triage_esi' => $this->initial_triage_esi,
            'current_esi_level' => $this->current_esi_level,
            'esi_severity_label' => $this->esi_severity_label,
            'priority_score' => $this->priority_score,
            'status' => $this->status,
            'assigned_doctor_id' => $this->assigned_doctor_id,
            'doctor_name' => $this->doctor?->name,
            'assigned_nurse_id' => $this->assigned_nurse_id,
            'nurse_name' => $this->nurse?->name,
            'assigned_bed_id' => $this->assigned_bed_id,
            'bed_number' => $this->bed?->bed_number,
            'ward_name' => $this->bed?->ward?->name,
            'bed_assigned_at' => $this->bed_assigned_at?->toIso8601String(),
            'wait_time_minutes' => $this->wait_time_minutes,
            'disposition' => $this->disposition,
            'disposition_notes' => $this->disposition_notes,
            'disposition_at' => $this->disposition_at?->toIso8601String(),
            'latest_triage_record' => $this->whenLoaded('latestTriageRecord', fn() => new TriageRecordResource($this->latestTriageRecord)),
            'triage_records' => $this->whenLoaded('triageRecords', fn() => TriageRecordResource::collection($this->triageRecords)),
            'bed_allocations' => $this->whenLoaded('bedAllocations', fn() => EmergencyBedAllocationResource::collection($this->bedAllocations)),
            'ambulance_dispatch' => $this->whenLoaded('ambulanceDispatch', fn() => new AmbulanceDispatchResource($this->ambulanceDispatch)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
