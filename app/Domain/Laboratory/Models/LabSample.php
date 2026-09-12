<?php

namespace App\Domain\Laboratory\Models;

use App\Domain\Clinical\Models\LabOrder;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LabSample extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'lab_samples';

    protected $fillable = [
        'barcode',
        'organization_id',
        'branch_id',
        'lab_order_id',
        'patient_id',
        'sample_type',
        'container_type',
        'status',
        'collection_site',
        'collected_at',
        'collected_by',
        'received_at',
        'received_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'received_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function labOrder(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class, 'lab_order_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function result(): HasOne
    {
        return $this->hasOne(LabResult::class, 'lab_sample_id');
    }

    /**
     * Mark sample as collected by phlebotomist.
     */
    public function markCollected(string $userId, ?string $site = null): self
    {
        $this->status = 'collected';
        $this->collected_at = now();
        $this->collected_by = $userId;
        if ($site) {
            $this->collection_site = $site;
        }
        $this->save();

        // Also update lab order sample_collected_at
        if ($this->labOrder) {
            $this->labOrder->update([
                'status' => 'sample_collected',
                'sample_collected_at' => now(),
            ]);
        }

        return $this;
    }

    /**
     * Receive sample in central laboratory (accessioning).
     */
    public function markReceived(string $userId): self
    {
        $this->status = 'received';
        $this->received_at = now();
        $this->received_by = $userId;
        $this->save();

        if ($this->labOrder) {
            $this->labOrder->update([
                'status' => 'in_progress',
            ]);
        }

        return $this;
    }

    /**
     * Reject compromised specimen (e.g. hemolyzed, clotted, insufficient volume).
     */
    public function markRejected(string $userId, string $reason): self
    {
        $this->status = 'rejected';
        $this->rejected_at = now();
        $this->rejected_by = $userId;
        $this->rejection_reason = $reason;
        $this->save();

        return $this;
    }
}
