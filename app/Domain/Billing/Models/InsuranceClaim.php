<?php

namespace App\Domain\Billing\Models;

use App\Domain\Patient\Models\Patient;
use App\Domain\Patient\Models\PatientInsurance;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceClaim extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'insurance_claims';

    protected $fillable = [
        'claim_number',
        'organization_id',
        'branch_id',
        'invoice_id',
        'patient_insurance_id',
        'patient_id',
        'provider_name',
        'policy_number',
        'pre_auth_number',
        'claimed_amount_cents',
        'approved_amount_cents',
        'copay_amount_cents',
        'deductible_amount_cents',
        'status',
        'submission_date',
        'settlement_date',
        'adjudication_notes',
        'rejection_reason',
        'submitted_by',
    ];

    protected $casts = [
        'claimed_amount_cents' => 'integer',
        'approved_amount_cents' => 'integer',
        'copay_amount_cents' => 'integer',
        'deductible_amount_cents' => 'integer',
        'submission_date' => 'date',
        'settlement_date' => 'date',
    ];

    protected $appends = [
        'claimed_amount',
        'approved_amount',
        'copay_amount',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function insurancePolicy(): BelongsTo
    {
        return $this->belongsTo(PatientInsurance::class, 'patient_insurance_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function getClaimedAmountAttribute(): float
    {
        return $this->claimed_amount_cents / 100;
    }

    public function getApprovedAmountAttribute(): float
    {
        return $this->approved_amount_cents / 100;
    }

    public function getCopayAmountAttribute(): float
    {
        return $this->copay_amount_cents / 100;
    }
}
