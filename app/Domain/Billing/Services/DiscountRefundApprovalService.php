<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\Discount;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\Refund;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DiscountRefundApprovalService
{
    // Configurable threshold in cents above which a second approval is strictly required
    // Default 5000 cents ($50.00)
    protected int $approvalThresholdCents = 5000;

    public function setThresholdCents(int $thresholdCents): self
    {
        $this->approvalThresholdCents = $thresholdCents;
        return $this;
    }

    public function getThresholdCents(): int
    {
        return $this->approvalThresholdCents;
    }

    /**
     * Request a discount on an invoice.
     * If amount > threshold, requires a second approval.
     */
    public function requestDiscount(Invoice $invoice, array $data, string $userId): Discount
    {
        return DB::transaction(function () use ($invoice, $data, $userId) {
            $discountType = $data['discount_type'] ?? 'fixed';
            $amountCents = 0;

            if ($discountType === 'percentage') {
                $percentage = (float) $data['percentage'];
                if ($percentage <= 0 || $percentage > 100) {
                    throw new DomainException("Discount percentage must be between 1 and 100.");
                }
                // Calculate integer cents without rounding drift
                $amountCents = (int) round(($invoice->subtotal_cents * $percentage) / 100);
            } else {
                $amountCents = (int) $data['amount_cents'];
            }

            if ($amountCents <= 0) {
                throw new DomainException("Discount amount must be greater than zero.");
            }

            if ($amountCents > $invoice->subtotal_cents) {
                throw new DomainException("Discount cannot exceed the invoice subtotal ({$invoice->subtotal_cents} cents).");
            }

            $requiresApproval = $amountCents > $this->approvalThresholdCents;
            $status = $requiresApproval ? 'pending_approval' : 'approved';

            $discount = Discount::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $invoice->organization_id,
                'branch_id' => $invoice->branch_id,
                'invoice_id' => $invoice->id,
                'discount_type' => $discountType,
                'percentage' => $discountType === 'percentage' ? ($data['percentage'] ?? null) : null,
                'amount_cents' => $amountCents,
                'reason' => $data['reason'] ?? 'Discretionary clinical discount',
                'requires_approval' => $requiresApproval,
                'status' => $status,
                'requested_by' => $userId,
                'approved_by' => $requiresApproval ? null : $userId,
                'approved_at' => $requiresApproval ? null : now(),
            ]);

            // Reconcile invoice if auto-approved
            if ($status === 'approved') {
                $invoice->recalculateTotals();
            }

            return $discount;
        });
    }

    /**
     * Approve a pending discount.
     */
    public function approveDiscount(Discount $discount, string $approverId): Discount
    {
        return DB::transaction(function () use ($discount, $approverId) {
            return $discount->approve($approverId);
        });
    }

    /**
     * Reject a pending discount.
     */
    public function rejectDiscount(Discount $discount, string $rejectorId, string $reason): Discount
    {
        return DB::transaction(function () use ($discount, $rejectorId, $reason) {
            return $discount->reject($rejectorId, $reason);
        });
    }

    /**
     * Request a refund on an invoice or payment.
     * If amount > threshold, requires a second approval.
     */
    public function requestRefund(Invoice $invoice, array $data, string $userId): Refund
    {
        return DB::transaction(function () use ($invoice, $data, $userId) {
            $amountCents = (int) $data['amount_cents'];

            if ($amountCents <= 0) {
                throw new DomainException("Refund amount must be greater than zero.");
            }

            if ($amountCents > $invoice->paid_cents) {
                throw new DomainException("Refund cannot exceed the total net amount paid on the invoice ({$invoice->paid_cents} cents).");
            }

            $requiresApproval = $amountCents > $this->approvalThresholdCents;
            $status = $requiresApproval ? 'pending_approval' : 'approved';

            $refundNumber = 'RFD-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            $refund = Refund::create([
                'id' => (string) Str::uuid(),
                'refund_number' => $refundNumber,
                'organization_id' => $invoice->organization_id,
                'branch_id' => $invoice->branch_id,
                'invoice_id' => $invoice->id,
                'payment_id' => $data['payment_id'] ?? null,
                'patient_id' => $invoice->patient_id,
                'amount_cents' => $amountCents,
                'refund_mode' => $data['refund_mode'] ?? 'cash',
                'reason' => $data['reason'] ?? 'Patient requested refund',
                'requires_approval' => $requiresApproval,
                'status' => $status,
                'requested_by' => $userId,
                'approved_by' => $requiresApproval ? null : $userId,
                'approved_at' => $requiresApproval ? null : now(),
            ]);

            // Reconcile invoice if auto-approved
            if ($status === 'approved') {
                $invoice->recalculateTotals();
            }

            return $refund;
        });
    }

    /**
     * Approve a pending refund.
     */
    public function approveRefund(Refund $refund, string $approverId): Refund
    {
        return DB::transaction(function () use ($refund, $approverId) {
            return $refund->approve($approverId);
        });
    }

    /**
     * Reject a pending refund.
     */
    public function rejectRefund(Refund $refund, string $rejectorId, string $reason): Refund
    {
        return DB::transaction(function () use ($refund, $rejectorId, $reason) {
            return $refund->reject($rejectorId, $reason);
        });
    }
}
