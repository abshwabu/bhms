<?php

namespace App\Domain\Clinical\Models;

use App\Domain\IPD\Models\Admission;
use App\Domain\OPD\Models\Appointment;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'prescriptions';

    protected $fillable = [
        'prescription_number',
        'organization_id',
        'branch_id',
        'patient_id',
        'ehr_record_id',
        'appointment_id',
        'admission_id',
        'doctor_id',
        'status',
        'has_safety_warnings',
        'safety_alerts',
        'override_reason',
        'overridden_by',
        'overridden_at',
        'notes',
        'prescribed_at',
        'finalized_at',
    ];

    protected $casts = [
        'has_safety_warnings' => 'boolean',
        'safety_alerts' => 'array',
        'prescribed_at' => 'datetime',
        'finalized_at' => 'datetime',
        'overridden_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function overridingDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
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

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class, 'prescription_id');
    }

    /**
     * Check if prescription has high-severity contraindication alerts.
     */
    public function hasHighSeverityWarnings(): bool
    {
        $alerts = $this->safety_alerts ?? [];
        foreach ($alerts as $alert) {
            if (($alert['severity'] ?? '') === 'high') {
                return true;
            }
        }
        return false;
    }

    /**
     * Finalize prescription.
     * Enforces clinical safety rule: High severity alerts require explicit clinical justification.
     */
    public function finalize(?string $overrideReason, string $userId): self
    {
        if ($this->status === 'finalized') {
            throw new DomainException("Prescription is already finalized.");
        }

        if ($this->hasHighSeverityWarnings() && empty(trim($overrideReason ?? ''))) {
            throw new DomainException(
                "Cannot finalize prescription: Severe drug interactions or allergy warnings detected. A clinical justification override reason is mandatory."
            );
        }

        if (!empty($overrideReason)) {
            $this->override_reason = $overrideReason;
            $this->overridden_by = $userId;
            $this->overridden_at = now();
        }

        $this->status = 'finalized';
        $this->finalized_at = now();
        $this->save();

        return $this;
    }
}
