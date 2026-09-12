<?php

namespace App\Domain\Billing\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;

class PriceList extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'price_lists';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'code',
        'name',
        'category',
        'department',
        'unit_price_cents',
        'is_package',
        'package_items',
        'is_active',
        'description',
    ];

    protected $casts = [
        'unit_price_cents' => 'integer',
        'is_package' => 'boolean',
        'package_items' => 'array',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'unit_price',
    ];

    public function getUnitPriceAttribute(): float
    {
        return $this->unit_price_cents / 100;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
