<?php

namespace App\Domain\IPD\Models;

use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BedTransfer extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'bed_transfers';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'admission_id',
        'patient_id',
        'from_ward_id',
        'from_bed_id',
        'to_ward_id',
        'to_bed_id',
        'reason',
        'transferred_by',
        'transferred_at',
        'status',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function fromWard(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'from_ward_id');
    }

    public function fromBed(): BelongsTo
    {
        return $this->belongsTo(Bed::class, 'from_bed_id');
    }

    public function toWard(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'to_ward_id');
    }

    public function toBed(): BelongsTo
    {
        return $this->belongsTo(Bed::class, 'to_bed_id');
    }

    public function transferredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}
