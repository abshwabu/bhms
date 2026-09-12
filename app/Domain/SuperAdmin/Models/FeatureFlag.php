<?php

namespace App\Domain\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeatureFlag extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'feature_flags';

    protected $fillable = [
        'key',
        'name',
        'category',
        'description',
        'is_globally_enabled',
        'default_enabled_plans',
    ];

    protected $casts = [
        'is_globally_enabled' => 'boolean',
        'default_enabled_plans' => 'array',
    ];

    public function tenantOverrides(): HasMany
    {
        return $this->hasMany(TenantFeatureFlag::class, 'feature_flag_id');
    }
}
