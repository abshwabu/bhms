<?php

namespace App\Domain\Patient\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Models\User;
use App\Domain\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientInsurance extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'patient_insurance';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'patient_id',
        'provider_name',
        'policy_number',
        'group_number',
        'coverage_type',
        'coverage_percentage',
        'copay_amount_cents',
        'valid_from',
        'valid_until',
        'pre_auth_required',
        'status',
        'card_image_path',
        'notes',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'coverage_percentage' => 'float',
        'copay_amount_cents' => 'integer',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'pre_auth_required' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
