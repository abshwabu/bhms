<?php

namespace App\Domain\Pharmacy\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Drug extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'drugs';

    protected $fillable = [
        'sku',
        'organization_id',
        'branch_id',
        'brand_name',
        'generic_name',
        'form',
        'strength',
        'unit_of_measure',
        'reorder_threshold',
        'target_stock_level',
        'unit_cost_cents',
        'unit_price_cents',
        'is_prescription_required',
        'is_controlled_substance',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'reorder_threshold' => 'integer',
        'target_stock_level' => 'integer',
        'unit_cost_cents' => 'integer',
        'unit_price_cents' => 'integer',
        'is_prescription_required' => 'boolean',
        'is_controlled_substance' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'total_stock_on_hand',
        'is_low_stock',
        'unit_price',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(DrugBatch::class, 'drug_id')->orderBy('expiry_date', 'asc');
    }

    public function activeBatches(): HasMany
    {
        return $this->hasMany(DrugBatch::class, 'drug_id')
            ->where('quantity_on_hand', '>', 0)
            ->where('expiry_date', '>', now()->toDateString())
            ->where('status', '!=', 'quarantined')
            ->orderBy('expiry_date', 'asc'); // FEFO Order
    }

    public function movements(): HasMany
    {
        return $this->hasMany(DrugStockMovement::class, 'drug_id')->orderBy('created_at', 'desc');
    }

    public function dispensedItems(): HasMany
    {
        return $this->hasMany(DispensingRecordItem::class, 'drug_id');
    }

    public function getTotalStockOnHandAttribute(): int
    {
        return (int) $this->batches()
            ->where('expiry_date', '>', now()->toDateString())
            ->where('status', '!=', 'quarantined')
            ->sum('quantity_on_hand');
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->total_stock_on_hand <= $this->reorder_threshold;
    }

    public function getUnitPriceAttribute(): float
    {
        return $this->unit_price_cents / 100;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
