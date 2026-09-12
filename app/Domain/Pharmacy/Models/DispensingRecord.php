<?php

namespace App\Domain\Pharmacy\Models;

use App\Domain\Clinical\Models\Prescription;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DispensingRecord extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'dispensing_records';

    protected $fillable = [
        'dispensation_number',
        'organization_id',
        'branch_id',
        'prescription_id',
        'patient_id',
        'pharmacist_id',
        'status',
        'has_interaction_warnings',
        'interaction_alerts',
        'pharmacist_notes',
        'counseling_notes',
        'dispensed_at',
    ];

    protected $casts = [
        'has_interaction_warnings' => 'boolean',
        'interaction_alerts' => 'array',
        'dispensed_at' => 'datetime',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function pharmacist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pharmacist_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DispensingRecordItem::class, 'dispensing_record_id');
    }
}
