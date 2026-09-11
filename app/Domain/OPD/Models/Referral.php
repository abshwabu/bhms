<?php

namespace App\Domain\OPD\Models;

use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Models\User;
use App\Domain\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'referrals';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'patient_id',
        'referring_doctor_id',
        'consultation_note_id',
        'referral_type',
        'from_department_id',
        'to_department_id',
        'to_doctor_id',
        'external_facility_name',
        'external_specialist_name',
        'external_contact',
        'priority',
        'reason_for_referral',
        'clinical_summary',
        'status',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function referringDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referring_doctor_id');
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function toDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_doctor_id');
    }

    public function consultationNote(): BelongsTo
    {
        return $this->belongsTo(ConsultationNote::class, 'consultation_note_id');
    }
}
