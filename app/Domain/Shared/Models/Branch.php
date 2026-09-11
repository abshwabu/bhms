<?php

namespace App\Domain\Shared\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Branch extends BaseModel
{
    protected $table = 'branches';

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'phone',
        'email',
        'address',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'address' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_branch_access')
            ->withPivot('is_default')
            ->withTimestamps();
    }
}
