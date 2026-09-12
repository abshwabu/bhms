<?php

namespace App\Domain\Laboratory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_number' => $this->report_number,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'lab_order_id' => $this->lab_order_id,
            'lab_order' => $this->whenLoaded('labOrder', function () {
                return [
                    'id' => $this->labOrder->id,
                    'order_number' => $this->labOrder->order_number,
                    'test_type' => $this->labOrder->test_type,
                    'priority' => $this->labOrder->priority,
                    'clinical_indication' => $this->labOrder->clinical_indication,
                    'ordering_doctor' => $this->labOrder->orderingDoctor?->name,
                ];
            }),
            'lab_test_id' => $this->lab_test_id,
            'lab_test' => new LabTestResource($this->whenLoaded('labTest')),
            'lab_sample_id' => $this->lab_sample_id,
            'sample' => new LabSampleResource($this->whenLoaded('sample')),
            'patient_id' => $this->patient_id,
            'patient' => $this->whenLoaded('patient', function () {
                return [
                    'id' => $this->patient->id,
                    'mrn' => $this->patient->mrn,
                    'name' => trim("{$this->patient->first_name} {$this->patient->last_name}"),
                    'gender' => $this->patient->gender,
                    'age' => $this->patient->date_of_birth ? $this->patient->date_of_birth->age : null,
                ];
            }),
            'technician_id' => $this->technician_id,
            'technician' => $this->whenLoaded('technician', fn() => $this->technician?->name),
            'pathologist_id' => $this->pathologist_id,
            'pathologist' => $this->whenLoaded('pathologist', fn() => $this->pathologist?->name),
            'status' => $this->status,
            'has_abnormal_values' => $this->has_abnormal_values,
            'has_critical_values' => $this->has_critical_values,
            'doctor_notified_at' => $this->doctor_notified_at?->toIso8601String(),
            'clinical_remarks' => $this->clinical_remarks,
            'methodology' => $this->methodology,
            'digital_signature_hash' => $this->digital_signature_hash,
            'signed_at' => $this->signed_at?->toIso8601String(),
            'version' => $this->version,
            'is_amended' => $this->is_amended,
            'amended_from_id' => $this->amended_from_id,
            'amendment_reason' => $this->amendment_reason,
            'analyzer_device_id' => $this->analyzer_device_id,
            'items' => LabResultItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
