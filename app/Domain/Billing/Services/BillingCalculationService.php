<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoiceItem;
use DomainException;

class BillingCalculationService
{
    /**
     * Compute item charges in integer cents to ensure exact financial reconciliation
     * with zero floating-point rounding drift.
     */
    public function computeItemLine(int $quantity, int $unitPriceCents, int $discountCents = 0): array
    {
        if ($quantity <= 0) {
            throw new DomainException("Item quantity must be greater than zero.");
        }

        if ($unitPriceCents < 0) {
            throw new DomainException("Unit price cents cannot be negative.");
        }

        $subtotal = $quantity * $unitPriceCents;
        $discount = min($discountCents, $subtotal);
        $total = max(0, $subtotal - $discount);

        return [
            'quantity' => $quantity,
            'unit_price_cents' => $unitPriceCents,
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'total_cents' => $total,
        ];
    }

    /**
     * Reconcile invoice financial balances against itemized charges and payments.
     */
    public function reconcile(Invoice $invoice): Invoice
    {
        return $invoice->recalculateTotals();
    }
}
