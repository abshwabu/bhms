<?php

namespace App\Domain\SuperAdmin\Models;

use App\Domain\Shared\Models\Organization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantFeatureFlag extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'tenant_feature_flags';

    protected $fillable = [
        'organization_id',
        'feature_flag_id',
        'is_enabled',
        'custom_config',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'custom_config' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function featureFlag(): BelongsTo
    {
        return $this->belongsTo(FeatureFlag::class, 'feature_flag_id');
    }
}
