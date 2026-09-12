<?php

namespace App\Domain\HR\Models;

use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shift extends Model
{
    use HasFactory, HasUuids, BelongsToBranch;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'shifts';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'staff_id',
        'shift_name',
        'shift_type',
        'department',
        'shift_date',
        'start_time',
        'end_time',
        'start_datetime',
        'end_datetime',
        'status',
        'is_published',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'is_published' => 'boolean',
    ];

    protected $appends = [
        'duration_hours',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(Attendance::class, 'shift_id');
    }

    public function getDurationHoursAttribute(): float
    {
        if (!$this->start_datetime || !$this->end_datetime) {
            return 8.0;
        }
        return round(Carbon::parse($this->start_datetime)->diffInMinutes(Carbon::parse($this->end_datetime)) / 60, 1);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Conflict query: find overlapping active shifts for a specific staff member.
     */
    public function scopeOverlapping(Builder $query, string $staffId, $startDatetime, $endDatetime, ?string $excludeShiftId = null): Builder
    {
        $q = $query->where('staff_id', $staffId)
            ->where('status', '!=', 'cancelled')
            ->where('start_datetime', '<', $endDatetime)
            ->where('end_datetime', '>', $startDatetime);

        if ($excludeShiftId) {
            $q->where('id', '!=', $excludeShiftId);
        }

        return $q;
    }
}
