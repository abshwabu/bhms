<?php

namespace App\Domain\Pharmacy\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrugStockMovement extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'drug_stock_movements';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'drug_id',
        'drug_batch_id',
        'movement_type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference_type',
        'reference_id',
        'reason',
        'performed_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
    ];

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class, 'drug_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DrugBatch::class, 'drug_batch_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
