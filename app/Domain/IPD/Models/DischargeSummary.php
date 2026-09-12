<?php

namespace App\Domain\IPD\Models;

use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DischargeSummary extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'discharge_summaries';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'admission_id',
        'patient_id',
        'discharging_doctor_id',
        'admission_date',
        'discharge_date',
        'primary_diagnosis',
        'secondary_diagnoses',
        'procedures_performed',
        'medications_at_discharge',
        'hospital_course_summary',
        'discharge_condition',
        'discharge_type',
        'follow_up_instructions',
        'follow_up_date',
        'is_finalized',
        'finalized_at',
        'finalized_by',
    ];

    protected $casts = [
        'admission_date' => 'datetime',
        'discharge_date' => 'datetime',
        'secondary_diagnoses' => 'array',
        'procedures_performed' => 'array',
        'medications_at_discharge' => 'array',
        'follow_up_date' => 'date',
        'is_finalized' => 'boolean',
        'finalized_at' => 'datetime',
    ];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function dischargingDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discharging_doctor_id');
    }

    public function finalizedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }
}
