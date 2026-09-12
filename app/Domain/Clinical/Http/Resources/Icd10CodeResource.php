<?php

namespace App\Domain\Clinical\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Icd10CodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'description' => $this->description,
            'category' => $this->category,
            'chapter' => $this->chapter,
            'is_billable' => $this->is_billable,
            'is_active' => $this->is_active,
        ];
    }
}
