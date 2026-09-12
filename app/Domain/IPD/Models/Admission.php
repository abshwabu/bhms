<?php

namespace App\Domain\IPD\Models;

use App\Domain\OPD\Models\Appointment;
use App\Domain\Patient\Models\Patient;
use App\Domain\Patient\Models\PatientInsurance;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Admission extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'admissions';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'admission_number',
        'patient_id',
        'appointment_id',
        'ward_id',
        'bed_id',
        'admitting_doctor_id',
        'attending_doctor_id',
        'admitted_by',
        'admission_type',
        'status',
        'admitted_at',
        'discharged_at',
        'discharged_by',
        'discharge_type',
        'admitting_diagnosis',
        'primary_diagnosis',
        'secondary_diagnoses',
        'procedures_performed',
        'chief_complaint',
        'initial_vitals',
        'insurance_policy_id',
        'notes',
    ];

    protected $casts = [
        'admitted_at' => 'datetime',
        'discharged_at' => 'datetime',
        'secondary_diagnoses' => 'array',
        'procedures_performed' => 'array',
        'initial_vitals' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    public function admittingDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admitting_doctor_id');
    }

    public function attendingDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attending_doctor_id');
    }

    public function admittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admitted_by');
    }

    public function dischargedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discharged_by');
    }

    public function insurancePolicy(): BelongsTo
    {
        return $this->belongsTo(PatientInsurance::class, 'insurance_policy_id');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(BedTransfer::class)->orderBy('transferred_at', 'desc');
    }

    public function vitalsLogs(): HasMany
    {
        return $this->hasMany(VitalsLog::class)->orderBy('recorded_at', 'desc');
    }

    public function medications(): HasMany
    {
        return $this->hasMany(MedicationAdministration::class)->orderBy('administered_at', 'desc');
    }

    public function dischargeSummary(): HasOne
    {
        return $this->hasOne(DischargeSummary::class);
    }

    /**
     * Compute Length of Stay (LOS) in fractional or integer days.
     */
    public function getLengthOfStayDaysAttribute(): float
    {
        $end = $this->discharged_at ?: Carbon::now();
        $start = $this->admitted_at;

        if (!$start) {
            return 0.0;
        }

        $hours = $start->diffInHours($end);
        return round(max(1.0, $hours / 24), 1);
    }
}
