<?php

namespace App\Domain\Shared\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends BaseModel
{
    protected $table = 'organizations';

    protected $fillable = [
        'name',
        'code',
        'tax_number',
        'settings',
        'is_active',
        'plan_tier',
        'subscription_status',
        'suspended_at',
        'suspension_reason',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'suspended_at' => 'datetime',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
