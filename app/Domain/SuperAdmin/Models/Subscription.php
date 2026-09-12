<?php

namespace App\Domain\SuperAdmin\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Models\Organization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends BaseModel
{
    use SoftDeletes;

    protected $table = 'subscriptions';

    protected $fillable = [
        'organization_id',
        'plan_tier',
        'status',
        'billing_cycle',
        'amount_cents',
        'currency',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'payment_method_last4',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
