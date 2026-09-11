<?php

namespace App\Domain\OPD\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_number' => $this->appointment_number,
            'appointment_date' => $this->appointment_date ? $this->appointment_date->format('Y-m-d') : null,
            'start_time' => substr($this->start_time, 0, 5),
            'end_time' => substr($this->end_time, 0, 5),
            'time_range' => substr($this->start_time, 0, 5) . ' - ' . substr($this->end_time, 0, 5),
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'department_id' => $this->department_id,
            'type' => $this->type,
            'appointment_type' => $this->type,
            'status' => $this->status,
            'reason_for_visit' => $this->reason_for_visit,
            'cancellation_reason' => $this->cancellation_reason,
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'parent_appointment_id' => $this->parent_appointment_id,

            'patient' => $this->whenLoaded('patient', function () {
                return [
                    'id' => $this->patient->id,
                    'mrn' => $this->patient->mrn,
                    'full_name' => $this->patient->full_name,
                    'phone' => $this->patient->phone,
                    'gender' => $this->patient->gender,
                    'age' => $this->patient->age,
                ];
            }),

            'doctor' => $this->whenLoaded('doctor', function () {
                return [
                    'id' => $this->doctor->id,
                    'name' => $this->doctor->name,
                    'email' => $this->doctor->email,
                ];
            }),

            'department' => $this->whenLoaded('department', function () {
                return [
                    'id' => $this->department->id,
                    'name' => $this->department->name,
                    'code' => $this->department->code,
                ];
            }),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
