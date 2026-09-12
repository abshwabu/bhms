<?php

namespace App\Domain\Emergency\Models;

use App\Domain\IPD\Models\Bed;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmergencyCase extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToBranch;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'emergency_cases';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'case_number',
        'patient_id',
        'patient_temp_name',
        'patient_gender',
        'patient_estimated_age',
        'arrival_mode',
        'ambulance_dispatch_id',
        'arrival_datetime',
        'chief_complaint',
        'initial_triage_esi',
        'current_esi_level',
        'priority_score',
        'status',
        'assigned_doctor_id',
        'assigned_nurse_id',
        'assigned_bed_id',
        'bed_assigned_at',
        'disposition',
        'disposition_notes',
        'disposition_at',
    ];

    protected $casts = [
        'arrival_datetime' => 'datetime',
        'bed_assigned_at' => 'datetime',
        'disposition_at' => 'datetime',
        'initial_triage_esi' => 'integer',
        'current_esi_level' => 'integer',
        'priority_score' => 'integer',
    ];

    protected $appends = [
        'display_patient_name',
        'esi_severity_label',
        'wait_time_minutes',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function ambulanceDispatch(): BelongsTo
    {
        return $this->belongsTo(AmbulanceDispatch::class, 'ambulance_dispatch_id');
    }

    public function triageRecords(): HasMany
    {
        return $this->hasMany(TriageRecord::class, 'emergency_case_id')->orderBy('triaged_at', 'desc');
    }

    public function latestTriageRecord(): HasOne
    {
        return $this->hasOne(TriageRecord::class, 'emergency_case_id')->orderByDesc('triaged_at');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_doctor_id');
    }

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_nurse_id');
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class, 'assigned_bed_id');
    }

    public function bedAllocations(): HasMany
    {
        return $this->hasMany(EmergencyBedAllocation::class, 'emergency_case_id')->orderBy('allocated_at', 'desc');
    }

    public function getDisplayPatientNameAttribute(): string
    {
        if ($this->patient) {
            return $this->patient->full_name;
        }
        return $this->patient_temp_name ?: 'Unidentified Patient (John/Jane Doe)';
    }

    public function getEsiSeverityLabelAttribute(): string
    {
        return match ($this->current_esi_level) {
            1 => 'ESI 1 - Resuscitation (Immediate)',
            2 => 'ESI 2 - Emergent',
            3 => 'ESI 3 - Urgent',
            4 => 'ESI 4 - Less Urgent',
            5 => 'ESI 5 - Non-Urgent',
            default => 'ESI ' . $this->current_esi_level,
        };
    }

    public function getWaitTimeMinutesAttribute(): int
    {
        $end = $this->bed_assigned_at ?? Carbon::now();
        return (int) Carbon::parse($this->arrival_datetime)->diffInMinutes($end);
    }

    /**
     * Triage priority queue scope:
     * Acceptance criterion: Triage severity level determines queue priority automatically.
     * Higher priority (ESI 1) sorted first, followed by earliest arrival timestamp.
     */
    public function scopeActiveTriageQueue(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['discharged', 'transferred', 'deceased', 'admitted_ipd'])
            ->orderBy('current_esi_level', 'asc') // ESI 1 (critical) before ESI 2, 3, etc.
            ->orderBy('priority_score', 'desc')
            ->orderBy('arrival_datetime', 'asc');
    }

    public function scopePendingBed(Builder $query): Builder
    {
        return $query->whereNull('assigned_bed_id')
            ->whereNotIn('status', ['discharged', 'transferred', 'deceased']);
    }
}
