<?php

namespace App\Domain\IPD\Models;

use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationAdministration extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'medication_administrations';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'admission_id',
        'patient_id',
        'administered_by',
        'medication_name',
        'dosage',
        'route',
        'scheduled_time',
        'administered_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'scheduled_time' => 'datetime',
        'administered_at' => 'datetime',
    ];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function administeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by');
    }
}
