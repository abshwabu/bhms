<?php

namespace App\Domain\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'po_number' => $this->po_number,
            'vendor_id' => $this->vendor_id,
            'vendor' => $this->vendor ? [
                'id' => $this->vendor->id,
                'name' => $this->vendor->name,
                'vendor_code' => $this->vendor->vendor_code,
                'email' => $this->vendor->email,
                'phone' => $this->vendor->phone,
            ] : null,
            'status' => $this->status,
            'order_date' => $this->order_date?->toDateString(),
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'subtotal_cents' => $this->subtotal_cents,
            'subtotal' => $this->subtotal,
            'tax_cents' => $this->tax_cents,
            'tax' => $this->tax,
            'shipping_cost_cents' => $this->shipping_cost_cents,
            'shipping_cost' => $this->shipping_cost,
            'total_cents' => $this->total_cents,
            'total' => $this->total,
            'currency' => $this->currency,
            'can_receive' => $this->can_receive,
            'created_by' => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ],
            'submitted_by' => [
                'id' => $this->submitter?->id,
                'name' => $this->submitter?->name,
            ],
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'approved_by' => [
                'id' => $this->approver?->id,
                'name' => $this->approver?->name,
            ],
            'approved_at' => $this->approved_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'terms_and_conditions' => $this->terms_and_conditions,
            'notes' => $this->notes,
            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->items()->count(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
