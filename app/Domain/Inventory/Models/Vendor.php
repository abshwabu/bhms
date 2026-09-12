<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'vendors';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'vendor_code',
        'name',
        'contact_name',
        'email',
        'phone',
        'tax_id',
        'address',
        'payment_terms',
        'rating',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'address' => 'array',
        'rating' => 'float',
        'is_active' => 'boolean',
    ];

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'vendor_id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'default_vendor_id');
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class, 'vendor_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
