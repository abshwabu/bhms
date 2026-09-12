<?php

namespace App\Domain\IPD\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bed extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'beds';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'ward_id',
        'bed_number',
        'bed_type',
        'status',
        'features',
        'daily_rate_override',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'daily_rate_override' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function currentAdmission(): HasOne
    {
        return $this->hasOne(Admission::class)->where('status', 'admitted');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available')->where('is_active', true);
    }
}
