<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\Payment;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    /**
     * Record a payment transaction (partial or full) against an invoice.
     * Accurately updates paid amount, balance, and status.
     */
    public function recordPayment(Invoice $invoice, array $data, string $cashierId): Payment
    {
        return DB::transaction(function () use ($invoice, $data, $cashierId) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);

            if ($invoice->status === 'paid') {
                throw new DomainException("Invoice {$invoice->invoice_number} is already fully paid.");
            }

            if ($invoice->status === 'cancelled') {
                throw new DomainException("Cannot record payment on a cancelled invoice.");
            }

            $amountCents = (int) $data['amount_cents'];
            if ($amountCents <= 0) {
                throw new DomainException("Payment amount must be greater than zero.");
            }

            if ($amountCents > $invoice->balance_cents) {
                throw new DomainException(
                    "Payment of {$amountCents} cents exceeds the outstanding balance of {$invoice->balance_cents} cents."
                );
            }

            $receiptNumber = 'REC-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            $payment = Payment::create([
                'id' => (string) Str::uuid(),
                'receipt_number' => $receiptNumber,
                'organization_id' => $invoice->organization_id,
                'branch_id' => $invoice->branch_id,
                'invoice_id' => $invoice->id,
                'patient_id' => $invoice->patient_id,
                'payment_mode' => $data['payment_mode'] ?? 'cash',
                'amount_cents' => $amountCents,
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'cashier_id' => $cashierId,
                'status' => 'completed',
                'received_at' => now(),
            ]);

            // Reconcile invoice balances and status
            $invoice->recalculateTotals();

            return $payment->load(['invoice', 'patient', 'cashier']);
        });
    }

    /**
     * Reverse a recorded payment.
     */
    public function reversePayment(Payment $payment, string $reason, string $userId): Payment
    {
        return DB::transaction(function () use ($payment, $reason, $userId) {
            if ($payment->status === 'reversed') {
                throw new DomainException("Payment is already reversed.");
            }

            $payment->status = 'reversed';
            $payment->notes = ($payment->notes ? $payment->notes . ' | ' : '') . "Reversed by user {$userId}: {$reason}";
            $payment->save();

            // Reconcile invoice
            $payment->invoice->recalculateTotals();

            return $payment;
        });
    }
}
