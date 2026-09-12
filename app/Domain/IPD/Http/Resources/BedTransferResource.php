<?php

namespace App\Domain\IPD\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BedTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'admission_id' => $this->admission_id,
            'patient_id' => $this->patient_id,
            'from_ward_id' => $this->from_ward_id,
            'from_bed_id' => $this->from_bed_id,
            'to_ward_id' => $this->to_ward_id,
            'to_bed_id' => $this->to_bed_id,
            'reason' => $this->reason,
            'transferred_by' => $this->transferred_by,
            'transferred_at' => $this->transferred_at?->toIso8601String(),
            'status' => $this->status,

            'from_ward' => $this->whenLoaded('fromWard', fn () => [
                'id' => $this->fromWard->id,
                'name' => $this->fromWard->name,
                'code' => $this->fromWard->code,
            ]),

            'from_bed' => $this->whenLoaded('fromBed', fn () => [
                'id' => $this->fromBed->id,
                'bed_number' => $this->fromBed->bed_number,
            ]),

            'to_ward' => $this->whenLoaded('toWard', fn () => [
                'id' => $this->toWard->id,
                'name' => $this->toWard->name,
                'code' => $this->toWard->code,
            ]),

            'to_bed' => $this->whenLoaded('toBed', fn () => [
                'id' => $this->toBed->id,
                'bed_number' => $this->toBed->bed_number,
            ]),

            'transferred_by_user' => $this->whenLoaded('transferredByUser', fn () => [
                'id' => $this->transferredByUser->id,
                'name' => $this->transferredByUser->name,
            ]),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
