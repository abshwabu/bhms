<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'inventory_items';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'item_code',
        'name',
        'category',
        'sub_category',
        'unit_of_measure',
        'current_stock',
        'min_stock_level',
        'max_stock_level',
        'reorder_quantity',
        'unit_cost_cents',
        'default_vendor_id',
        'storage_location',
        'is_active',
        'description',
    ];

    protected $casts = [
        'current_stock' => 'integer',
        'min_stock_level' => 'integer',
        'max_stock_level' => 'integer',
        'reorder_quantity' => 'integer',
        'unit_cost_cents' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'unit_cost',
        'stock_status',
        'is_low_stock',
        'is_out_of_stock',
    ];

    public function defaultVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'default_vendor_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'inventory_item_id')->orderBy('created_at', 'desc');
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'inventory_item_id');
    }

    public function getUnitCostAttribute(): float
    {
        return $this->unit_cost_cents / 100;
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->current_stock <= 0;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock > 0 && $this->current_stock <= $this->min_stock_level;
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->current_stock <= 0) {
            return 'out_of_stock';
        }
        if ($this->current_stock <= $this->min_stock_level) {
            return 'low_stock';
        }
        return 'adequate';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('current_stock', '<=', 'min_stock_level');
    }

    public function scopeMedical(Builder $query): Builder
    {
        return $query->where('category', 'medical');
    }

    public function scopeNonMedical(Builder $query): Builder
    {
        return $query->where('category', 'non_medical');
    }
}
