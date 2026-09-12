<?php

namespace App\Domain\IPD\Models;

use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VitalsLog extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'vitals_logs';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'admission_id',
        'patient_id',
        'recorded_by',
        'recorded_at',
        'bp_systolic',
        'bp_diastolic',
        'heart_rate',
        'respiratory_rate',
        'temperature_c',
        'spo2',
        'blood_glucose_mg_dl',
        'pain_score',
        'consciousness_level',
        'urine_output_ml',
        'nursing_notes',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'bp_systolic' => 'integer',
        'bp_diastolic' => 'integer',
        'heart_rate' => 'integer',
        'respiratory_rate' => 'integer',
        'temperature_c' => 'decimal:1',
        'spo2' => 'decimal:1',
        'blood_glucose_mg_dl' => 'decimal:1',
        'pain_score' => 'integer',
        'urine_output_ml' => 'decimal:1',
    ];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
