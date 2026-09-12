<?php

namespace App\Domain\Billing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'category' => $this->category,
            'department' => $this->department,
            'unit_price_cents' => $this->unit_price_cents,
            'unit_price' => $this->unit_price,
            'is_package' => $this->is_package,
            'package_items' => $this->package_items,
            'is_active' => $this->is_active,
            'description' => $this->description,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
