<?php

namespace App\Domain\Billing\Models;

use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'payments';

    protected $fillable = [
        'receipt_number',
        'organization_id',
        'branch_id',
        'invoice_id',
        'patient_id',
        'payment_mode',
        'amount_cents',
        'transaction_reference',
        'notes',
        'cashier_id',
        'status',
        'received_at',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'received_at' => 'datetime',
    ];

    protected $appends = [
        'amount',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;
    }
}
