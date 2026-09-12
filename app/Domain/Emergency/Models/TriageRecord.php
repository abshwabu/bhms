<?php

namespace App\Domain\Emergency\Models;

use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriageRecord extends Model
{
    use HasFactory, HasUuids, BelongsToBranch;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'triage_records';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'emergency_case_id',
        'triaged_by',
        'triaged_at',
        'esi_level',
        'severity_label',
        'triage_category',
        'vital_signs',
        'is_danger_zone_vitals',
        'red_flags',
        'assessment_notes',
        'reassessment_interval_minutes',
        'reassessment_due_at',
    ];

    protected $casts = [
        'triaged_at' => 'datetime',
        'reassessment_due_at' => 'datetime',
        'vital_signs' => 'array',
        'red_flags' => 'array',
        'is_danger_zone_vitals' => 'boolean',
        'esi_level' => 'integer',
        'reassessment_interval_minutes' => 'integer',
    ];

    public function emergencyCase(): BelongsTo
    {
        return $this->belongsTo(EmergencyCase::class, 'emergency_case_id');
    }

    public function triageNurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triaged_by');
    }

    public function triagedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triaged_by');
    }
}
