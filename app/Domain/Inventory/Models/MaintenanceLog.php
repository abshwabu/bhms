<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceLog extends Model
{
    use HasFactory, HasUuids, BelongsToBranch;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'maintenance_logs';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'equipment_id',
        'log_number',
        'maintenance_type',
        'status',
        'priority',
        'scheduled_date',
        'completed_date',
        'technician_name',
        'vendor_id',
        'performed_by',
        'cost_cents',
        'findings',
        'actions_taken',
        'parts_replaced',
        'next_recommended_date',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'next_recommended_date' => 'date',
        'cost_cents' => 'integer',
        'parts_replaced' => 'array',
    ];

    protected $appends = [
        'cost',
        'is_overdue',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function getCostAttribute(): float
    {
        return $this->cost_cents / 100;
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->status !== 'scheduled' || !$this->scheduled_date) {
            return false;
        }
        return Carbon::parse($this->scheduled_date)->isPast() && !Carbon::parse($this->scheduled_date)->isToday();
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }
}
