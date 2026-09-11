<?php

namespace App\Domain\Patient\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Models\User;
use App\Domain\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientHistory extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'patient_history';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'patient_id',
        'category',
        'condition_or_procedure',
        'icd10_code',
        'diagnosed_date',
        'status',
        'severity',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'diagnosed_date' => 'date',
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
