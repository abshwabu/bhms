<?php

namespace App\Domain\Patient\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientRelationship extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'patient_relationships';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'patient_id',
        'related_patient_id',
        'relationship_type',
        'is_guardian',
        'is_emergency_contact',
        'is_billing_guarantor',
        'external_name',
        'external_phone',
        'external_national_id',
        'external_address',
        'notes',
    ];

    protected $casts = [
        'is_guardian' => 'boolean',
        'is_emergency_contact' => 'boolean',
        'is_billing_guarantor' => 'boolean',
        'external_address' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function relatedPatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'related_patient_id');
    }

    /**
     * Get display name of relative (from linked patient or external field).
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->relatedPatient) {
            return $this->relatedPatient->full_name;
        }

        return $this->external_name ?? 'Unknown Relative';
    }

    /**
     * Get phone of relative.
     */
    public function getPhoneAttribute(): ?string
    {
        if ($this->relatedPatient) {
            return $this->relatedPatient->phone;
        }

        return $this->external_phone;
    }
}
