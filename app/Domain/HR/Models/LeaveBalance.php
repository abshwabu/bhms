<?php

namespace App\Domain\HR\Models;

use App\Domain\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory, HasUuids, BelongsToBranch;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'leave_balances';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'staff_id',
        'year',
        'leave_type',
        'allocated_days',
        'used_days',
        'pending_days',
        'remaining_days',
    ];

    protected $casts = [
        'year' => 'integer',
        'allocated_days' => 'integer',
        'used_days' => 'integer',
        'pending_days' => 'integer',
        'remaining_days' => 'integer',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
