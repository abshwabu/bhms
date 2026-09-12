<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory, HasUuids, BelongsToBranch;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'inventory_movements';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'inventory_item_id',
        'movement_type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'unit_cost_cents',
        'total_cost_cents',
        'reference_type',
        'reference_id',
        'department',
        'performed_by',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'unit_cost_cents' => 'integer',
        'total_cost_cents' => 'integer',
    ];

    protected $appends = [
        'unit_cost',
        'total_cost',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function getUnitCostAttribute(): ?float
    {
        return $this->unit_cost_cents ? $this->unit_cost_cents / 100 : null;
    }

    public function getTotalCostAttribute(): ?float
    {
        return $this->total_cost_cents ? $this->total_cost_cents / 100 : null;
    }
}
