<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'po_number',
        'vendor_id',
        'status',
        'order_date',
        'expected_delivery_date',
        'subtotal_cents',
        'tax_cents',
        'shipping_cost_cents',
        'total_cents',
        'currency',
        'created_by',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'terms_and_conditions',
        'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'subtotal_cents' => 'integer',
        'tax_cents' => 'integer',
        'shipping_cost_cents' => 'integer',
        'total_cents' => 'integer',
    ];

    protected $appends = [
        'subtotal',
        'tax',
        'shipping_cost',
        'total',
        'can_receive',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getSubtotalAttribute(): float
    {
        return $this->subtotal_cents / 100;
    }

    public function getTaxAttribute(): float
    {
        return $this->tax_cents / 100;
    }

    public function getShippingCostAttribute(): float
    {
        return $this->shipping_cost_cents / 100;
    }

    public function getTotalAttribute(): float
    {
        return $this->total_cents / 100;
    }

    public function getCanReceiveAttribute(): bool
    {
        return in_array($this->status, ['approved', 'partially_received']);
    }

    /**
     * Recalculate financial totals from line items.
     */
    public function recalculateTotals(): self
    {
        $subtotal = 0;
        foreach ($this->items as $item) {
            $subtotal += $item->total_cost_cents;
        }

        $this->subtotal_cents = $subtotal;
        $this->total_cents = $this->subtotal_cents + $this->tax_cents + $this->shipping_cost_cents;
        $this->save();

        return $this;
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
