<?php

namespace App\Domain\Laboratory\Models;

use App\Domain\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabTest extends BaseModel
{
    protected $table = 'lab_tests';

    protected $fillable = [
        'code',
        'name',
        'category',
        'specimen_type',
        'container_type',
        'turn_around_time_minutes',
        'price_cents',
        'is_active',
    ];

    protected $casts = [
        'turn_around_time_minutes' => 'integer',
        'price_cents' => 'integer',
        'is_active' => 'boolean',
    ];

    public function referenceRanges(): HasMany
    {
        return $this->hasMany(ReferenceRange::class, 'lab_test_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(LabResult::class, 'lab_test_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
