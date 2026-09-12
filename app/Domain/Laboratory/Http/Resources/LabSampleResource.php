<?php

namespace App\Domain\Laboratory\Http\Resources;

use App\Domain\Laboratory\Services\BarcodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabSampleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $barcodeService = app(BarcodeService::class);

        return [
            'id' => $this->id,
            'barcode' => $this->barcode,
            'barcode_svg' => $barcodeService->generateBarcodeSvg($this->barcode),
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'lab_order_id' => $this->lab_order_id,
            'lab_order' => $this->whenLoaded('labOrder', function () {
                return [
                    'id' => $this->labOrder->id,
                    'order_number' => $this->labOrder->order_number,
                    'test_type' => $this->labOrder->test_type,
                    'priority' => $this->labOrder->priority,
                    'status' => $this->labOrder->status,
                ];
            }),
            'patient_id' => $this->patient_id,
            'patient' => $this->whenLoaded('patient', function () {
                return [
                    'id' => $this->patient->id,
                    'mrn' => $this->patient->mrn,
                    'name' => trim("{$this->patient->first_name} {$this->patient->last_name}"),
                    'gender' => $this->patient->gender,
                    'dob' => $this->patient->date_of_birth?->format('Y-m-d'),
                ];
            }),
            'sample_type' => $this->sample_type,
            'container_type' => $this->container_type,
            'status' => $this->status,
            'collection_site' => $this->collection_site,
            'collected_at' => $this->collected_at?->toIso8601String(),
            'collected_by' => $this->whenLoaded('collector', fn() => $this->collector?->name),
            'received_at' => $this->received_at?->toIso8601String(),
            'received_by' => $this->whenLoaded('receiver', fn() => $this->receiver?->name),
            'rejected_at' => $this->rejected_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
