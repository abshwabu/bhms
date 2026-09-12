<?php

namespace App\Domain\Billing\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends BaseModel
{
    protected $table = 'invoice_items';

    protected $fillable = [
        'invoice_id',
        'item_type',
        'item_code',
        'description',
        'quantity',
        'unit_price_cents',
        'subtotal_cents',
        'discount_cents',
        'total_cents',
        'doctor_id',
        'department',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_cents' => 'integer',
        'subtotal_cents' => 'integer',
        'discount_cents' => 'integer',
        'total_cents' => 'integer',
    ];

    protected $appends = [
        'unit_price',
        'subtotal',
        'discount',
        'total',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function getUnitPriceAttribute(): float
    {
        return $this->unit_price_cents / 100;
    }

    public function getSubtotalAttribute(): float
    {
        return $this->subtotal_cents / 100;
    }

    public function getDiscountAttribute(): float
    {
        return $this->discount_cents / 100;
    }

    public function getTotalAttribute(): float
    {
        return $this->total_cents / 100;
    }
}
