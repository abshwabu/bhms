<?php

namespace App\Domain\Clinical\Models;

use App\Domain\IPD\Models\Admission;
use App\Domain\OPD\Models\Appointment;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyOrder extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'radiology_orders';

    protected $fillable = [
        'order_number',
        'organization_id',
        'branch_id',
        'patient_id',
        'ehr_record_id',
        'appointment_id',
        'admission_id',
        'ordering_doctor_id',
        'modality',
        'body_part',
        'procedure_name',
        'priority',
        'clinical_indication',
        'transport_required',
        'is_pregnant_or_possible',
        'status',
        'ordered_at',
        'performed_at',
        'reported_at',
        'findings',
        'impression',
        'radiologist_id',
        'reviewed_by_doctor_id',
        'reviewed_by_doctor_at',
        'doctor_review_notes',
    ];

    protected $casts = [
        'transport_required' => 'boolean',
        'is_pregnant_or_possible' => 'boolean',
        'ordered_at' => 'datetime',
        'performed_at' => 'datetime',
        'reported_at' => 'datetime',
        'reviewed_by_doctor_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function orderingDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordering_doctor_id');
    }

    public function radiologist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'radiologist_id');
    }

    public function reviewingDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_doctor_id');
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

    public function markReviewed(string $doctorId, ?string $notes = null): self
    {
        $this->reviewed_by_doctor_id = $doctorId;
        $this->reviewed_by_doctor_at = now();
        $this->doctor_review_notes = $notes;
        $this->save();

        return $this;
    }
}
