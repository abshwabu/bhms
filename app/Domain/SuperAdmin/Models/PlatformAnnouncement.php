<?php

namespace App\Domain\SuperAdmin\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlatformAnnouncement extends BaseModel
{
    use SoftDeletes;

    protected $table = 'platform_announcements';

    protected $fillable = [
        'title',
        'content',
        'severity',
        'target_plans',
        'is_active',
        'starts_at',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'target_plans' => 'array',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
