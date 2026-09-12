<?php

namespace App\Domain\Pharmacy\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DrugBatch extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'drug_batches';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'drug_id',
        'batch_number',
        'manufacturing_date',
        'expiry_date',
        'quantity_received',
        'quantity_on_hand',
        'unit_cost_cents',
        'supplier_name',
        'status',
    ];

    protected $casts = [
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'quantity_received' => 'integer',
        'quantity_on_hand' => 'integer',
        'unit_cost_cents' => 'integer',
    ];

    protected $appends = [
        'is_expired',
        'is_near_expiry',
        'days_until_expiry',
    ];

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class, 'drug_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(DrugStockMovement::class, 'drug_batch_id')->orderBy('created_at', 'desc');
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date->endOfDay()->isPast();
    }

    public function getIsNearExpiryAttribute(): bool
    {
        return !$this->is_expired && $this->expiry_date->diffInDays(now()) <= 60;
    }

    public function getDaysUntilExpiryAttribute(): int
    {
        if ($this->is_expired) {
            return -$this->expiry_date->diffInDays(now());
        }
        return (int) now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Decrement stock from this lot and record an audit movement trail.
     */
    public function decrementStock(
        int $quantity,
        string $movementType,
        ?string $reason = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $userId = null
    ): DrugStockMovement {
        if ($this->is_expired) {
            throw new DomainException("Cannot dispense or deduct from expired batch '{$this->batch_number}'. Expiry was {$this->expiry_date->toDateString()}.");
        }

        if ($this->quantity_on_hand < $quantity) {
            throw new DomainException("Insufficient stock in batch '{$this->batch_number}'. Available: {$this->quantity_on_hand}, Requested: {$quantity}.");
        }

        $before = $this->quantity_on_hand;
        $after = $before - $quantity;

        $this->quantity_on_hand = $after;
        if ($after === 0) {
            $this->status = 'depleted';
        }
        $this->save();

        return DrugStockMovement::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'drug_id' => $this->drug_id,
            'drug_batch_id' => $this->id,
            'movement_type' => $movementType,
            'quantity' => -$quantity,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reason' => $reason,
            'performed_by' => $userId,
        ]);
    }

    /**
     * Increment stock in this lot and record an audit movement trail.
     */
    public function incrementStock(
        int $quantity,
        string $movementType = 'intake',
        ?string $reason = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $userId = null
    ): DrugStockMovement {
        $before = $this->quantity_on_hand;
        $after = $before + $quantity;

        $this->quantity_on_hand = $after;
        if ($after > 0 && $this->status === 'depleted') {
            $this->status = 'active';
        }
        $this->save();

        return DrugStockMovement::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'drug_id' => $this->drug_id,
            'drug_batch_id' => $this->id,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reason' => $reason,
            'performed_by' => $userId,
        ]);
    }
}
