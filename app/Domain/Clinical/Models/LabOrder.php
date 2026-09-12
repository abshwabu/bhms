<?php

namespace App\Domain\Clinical\Models;

use App\Domain\IPD\Models\Admission;
use App\Domain\OPD\Models\Appointment;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabOrder extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'lab_orders';

    protected $fillable = [
        'order_number',
        'organization_id',
        'branch_id',
        'patient_id',
        'ehr_record_id',
        'appointment_id',
        'admission_id',
        'ordering_doctor_id',
        'test_type',
        'test_code',
        'priority',
        'clinical_indication',
        'special_instructions',
        'status',
        'ordered_at',
        'sample_collected_at',
        'completed_at',
        'results_summary',
        'structured_results',
        'abnormal_flags',
        'reviewed_by_doctor_id',
        'reviewed_by_doctor_at',
        'doctor_review_notes',
    ];

    protected $casts = [
        'structured_results' => 'array',
        'abnormal_flags' => 'boolean',
        'ordered_at' => 'datetime',
        'sample_collected_at' => 'datetime',
        'completed_at' => 'datetime',
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
