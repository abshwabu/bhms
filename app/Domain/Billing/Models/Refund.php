<?php

namespace App\Domain\Billing\Models;

use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'refunds';

    protected $fillable = [
        'refund_number',
        'organization_id',
        'branch_id',
        'invoice_id',
        'payment_id',
        'patient_id',
        'amount_cents',
        'refund_mode',
        'reason',
        'requires_approval',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'requires_approval' => 'boolean',
        'approved_at' => 'datetime',
    ];

    protected $appends = [
        'amount',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;
    }

    public function approve(string $approverId): self
    {
        if (in_array($this->status, ['approved', 'processed'], true)) {
            throw new DomainException("Refund is already approved.");
        }

        $this->status = 'approved';
        $this->approved_by = $approverId;
        $this->approved_at = now();
        $this->save();

        // Recalculate invoice net payments and balance
        $this->invoice->recalculateTotals();

        return $this;
    }

    public function reject(string $rejectorId, string $reason): self
    {
        if ($this->status !== 'pending_approval') {
            throw new DomainException("Cannot reject refund with status '{$this->status}'.");
        }

        $this->status = 'rejected';
        $this->approved_by = $rejectorId;
        $this->rejection_reason = $reason;
        $this->save();

        return $this;
    }
}
