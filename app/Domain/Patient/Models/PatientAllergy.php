<?php

namespace App\Domain\Patient\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Models\User;
use App\Domain\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientAllergy extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'patient_allergies';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'patient_id',
        'allergen',
        'allergen_type',
        'reaction',
        'severity',
        'status',
        'diagnosed_at',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'diagnosed_at' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
