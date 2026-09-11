<?php

namespace App\Domain\OPD\Models;

use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Models\User;
use App\Domain\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueToken extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'queue_tokens';

    public $timestamps = true;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'department_id',
        'patient_id',
        'doctor_id',
        'appointment_id',
        'token_date',
        'token_number',
        'token_code',
        'status',
        'priority',
        'counter_room',
        'called_at',
        'consultation_started_at',
        'completed_at',
    ];

    protected $casts = [
        'token_date' => 'date',
        'token_number' => 'integer',
        'called_at' => 'datetime',
        'consultation_started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    // Scopes for Queue Display & Waiting Room
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('token_date', now()->toDateString());
    }

    public function scopeActiveWaiting(Builder $query): Builder
    {
        return $query->whereIn('status', ['waiting', 'called', 'in_consultation']);
    }

    public function scopeForDepartment(Builder $query, string $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }
}
