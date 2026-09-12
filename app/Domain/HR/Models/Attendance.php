<?php

namespace App\Domain\HR\Models;

use App\Domain\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory, HasUuids, BelongsToBranch;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'attendance';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'staff_id',
        'shift_id',
        'date',
        'check_in_time',
        'check_out_time',
        'total_minutes_worked',
        'status',
        'is_punctual',
        'minutes_late',
        'check_in_ip',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'total_minutes_worked' => 'integer',
        'is_punctual' => 'boolean',
        'minutes_late' => 'integer',
    ];

    protected $appends = [
        'hours_worked',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function getHoursWorkedAttribute(): float
    {
        return round($this->total_minutes_worked / 60, 2);
    }
}
