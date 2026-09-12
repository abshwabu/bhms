<?php

namespace App\Domain\Billing\Models;

use App\Domain\IPD\Models\Admission;
use App\Domain\OPD\Models\Appointment;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'invoices';

    protected $fillable = [
        'invoice_number',
        'organization_id',
        'branch_id',
        'patient_id',
        'appointment_id',
        'admission_id',
        'doctor_id',
        'department',
        'billing_type',
        'status',
        'subtotal_cents',
        'discount_cents',
        'tax_cents',
        'total_cents',
        'paid_cents',
        'balance_cents',
        'payment_terms',
        'due_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'subtotal_cents' => 'integer',
        'discount_cents' => 'integer',
        'tax_cents' => 'integer',
        'total_cents' => 'integer',
        'paid_cents' => 'integer',
        'balance_cents' => 'integer',
        'due_date' => 'date',
    ];

    protected $appends = [
        'subtotal',
        'discount',
        'tax',
        'total',
        'paid',
        'balance',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class, 'admission_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id')->orderBy('received_at', 'desc');
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class, 'invoice_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'invoice_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class, 'invoice_id');
    }

    public function getSubtotalAttribute(): float
    {
        return $this->subtotal_cents / 100;
    }

    public function getDiscountAttribute(): float
    {
        return $this->discount_cents / 100;
    }

    public function getTaxAttribute(): float
    {
        return $this->tax_cents / 100;
    }

    public function getTotalAttribute(): float
    {
        return $this->total_cents / 100;
    }

    public function getPaidAttribute(): float
    {
        return $this->paid_cents / 100;
    }

    public function getBalanceAttribute(): float
    {
        return $this->balance_cents / 100;
    }

    /**
     * Exact integer cents financial reconciliation.
     * Guaranteed zero rounding drift across all line charges, discounts, and payments.
     */
    public function recalculateTotals(): self
    {
        $items = $this->items()->get();

        if ($items->isNotEmpty()) {
            $subtotal = 0;
            $itemsDiscount = 0;
            foreach ($items as $item) {
                $subtotal += $item->subtotal_cents;
                $itemsDiscount += $item->discount_cents;
            }
        } else {
            $subtotal = $this->subtotal_cents;
            $itemsDiscount = 0;
        }

        // Only approved invoice-level discounts are subtracted
        $approvedInvoiceDiscounts = (int) $this->discounts()
            ->where('status', 'approved')
            ->sum('amount_cents');

        $totalDiscount = $itemsDiscount + $approvedInvoiceDiscounts;
        $totalCents = max(0, $subtotal - $totalDiscount + $this->tax_cents);

        $paidCents = (int) $this->payments()
            ->where('status', 'completed')
            ->sum('amount_cents');

        $refundedCents = (int) $this->refunds()
            ->whereIn('status', ['approved', 'processed'])
            ->sum('amount_cents');

        $netPaidCents = max(0, $paidCents - $refundedCents);
        $balanceCents = max(0, $totalCents - $netPaidCents);

        $this->subtotal_cents = $subtotal;
        $this->discount_cents = $totalDiscount;
        $this->total_cents = $totalCents;
        $this->paid_cents = $netPaidCents;
        $this->balance_cents = $balanceCents;

        if ($this->status !== 'cancelled') {
            if ($totalCents > 0 && $netPaidCents >= $totalCents) {
                $this->status = 'paid';
            } elseif ($netPaidCents > 0 && $netPaidCents < $totalCents) {
                $this->status = 'partially_paid';
            } elseif ($refundedCents > 0 && $netPaidCents === 0 && $paidCents > 0) {
                $this->status = 'refunded';
            } else {
                $this->status = 'unpaid';
            }
        }

        $this->save();

        return $this;
    }
}
