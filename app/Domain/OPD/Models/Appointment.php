<?php

namespace App\Domain\OPD\Models;

use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Models\User;
use App\Domain\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'appointments';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'department_id',
        'patient_id',
        'doctor_id',
        'parent_appointment_id',
        'appointment_number',
        'appointment_date',
        'start_time',
        'end_time',
        'type',
        'status',
        'reason_for_visit',
        'cancellation_reason',
        'cancelled_at',
        'cancelled_by',
        'checked_in_at',
        'booked_by',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'cancelled_at' => 'datetime',
        'checked_in_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function parentAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'parent_appointment_id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(Appointment::class, 'parent_appointment_id');
    }

    public function consultationNote(): HasOne
    {
        return $this->hasOne(ConsultationNote::class, 'appointment_id');
    }

    public function queueToken(): HasOne
    {
        return $this->hasOne(QueueToken::class, 'appointment_id');
    }

    public function bookedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['cancelled', 'rescheduled']);
    }

    public function scopeForDoctor(Builder $query, string $doctorId): Builder
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('appointment_date', $date);
    }
}
