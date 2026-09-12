<?php

namespace App\Domain\Clinical\Models;

use App\Domain\IPD\Models\Admission;
use App\Domain\OPD\Models\Appointment;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Diagnosis extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'diagnoses';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'patient_id',
        'ehr_record_id',
        'appointment_id',
        'admission_id',
        'doctor_id',
        'icd10_code',
        'icd10_title',
        'type',
        'severity',
        'clinical_status',
        'verification_status',
        'onset_date',
        'resolved_date',
        'notes',
    ];

    protected $casts = [
        'onset_date' => 'date',
        'resolved_date' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function ehrRecord(): BelongsTo
    {
        return $this->belongsTo(EhrRecord::class, 'ehr_record_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class, 'admission_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
