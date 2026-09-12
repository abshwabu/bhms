<?php

namespace App\Domain\Emergency\Models;

use App\Domain\IPD\Models\Bed;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyBedAllocation extends Model
{
    use HasFactory, HasUuids, BelongsToBranch;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'emergency_bed_allocations';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'emergency_case_id',
        'bed_id',
        'is_override',
        'override_reason',
        'priority_tier',
        'allocated_by',
        'allocated_at',
        'released_at',
        'release_notes',
    ];

    protected $casts = [
        'is_override' => 'boolean',
        'allocated_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function emergencyCase(): BelongsTo
    {
        return $this->belongsTo(EmergencyCase::class, 'emergency_case_id');
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class, 'bed_id');
    }

    public function allocatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }
}
