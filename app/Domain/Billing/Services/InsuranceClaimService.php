<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\InsuranceClaim;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Patient\Models\PatientInsurance;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InsuranceClaimService
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Submit an insurance claim for an invoice.
     */
    public function submitClaim(Invoice $invoice, array $data, string $userId): InsuranceClaim
    {
        return DB::transaction(function () use ($invoice, $data, $userId) {
            $insuranceId = $data['patient_insurance_id'] ?? null;
            $insurance = null;

            if ($insuranceId) {
                $insurance = PatientInsurance::findOrFail($insuranceId);
            } else {
                $insurance = PatientInsurance::where('patient_id', $invoice->patient_id)
                    ->where('status', 'active')
                    ->first();
            }

            if (!$insurance) {
                throw new DomainException("No active insurance policy found for patient.");
            }

            $claimNumber = 'CLM-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            $claimedCents = isset($data['claimed_amount_cents'])
                ? (int) $data['claimed_amount_cents']
                : $invoice->total_cents;

            $copayCents = (int) ($data['copay_amount_cents'] ?? $insurance->copay_amount_cents ?? 0);

            $claim = InsuranceClaim::create([
                'id' => (string) Str::uuid(),
                'claim_number' => $claimNumber,
                'organization_id' => $invoice->organization_id,
                'branch_id' => $invoice->branch_id,
                'invoice_id' => $invoice->id,
                'patient_insurance_id' => $insurance->id,
                'patient_id' => $invoice->patient_id,
                'provider_name' => $insurance->provider_name,
                'policy_number' => $insurance->policy_number,
                'pre_auth_number' => $data['pre_auth_number'] ?? null,
                'claimed_amount_cents' => $claimedCents,
                'approved_amount_cents' => 0,
                'copay_amount_cents' => $copayCents,
                'deductible_amount_cents' => (int) ($data['deductible_amount_cents'] ?? 0),
                'status' => 'submitted',
                'submission_date' => now()->toDateString(),
                'adjudication_notes' => $data['notes'] ?? null,
                'submitted_by' => $userId,
            ]);

            return $claim->load(['invoice', 'patient', 'insurancePolicy']);
        });
    }

    /**
     * Adjudicate claim response from insurer / TPA.
     */
    public function adjudicateClaim(InsuranceClaim $claim, array $data, string $userId): InsuranceClaim
    {
        return DB::transaction(function () use ($claim, $data, $userId) {
            $status = $data['status']; // approved, rejected, under_review
            $claim->status = $status;

            if ($status === 'approved') {
                $claim->approved_amount_cents = (int) ($data['approved_amount_cents'] ?? $claim->claimed_amount_cents);
                $claim->settlement_date = now()->toDateString();
            } elseif ($status === 'rejected') {
                $claim->rejection_reason = $data['rejection_reason'] ?? 'Denied by payer adjudication';
            }

            $claim->adjudication_notes = $data['adjudication_notes'] ?? $claim->adjudication_notes;
            $claim->save();

            return $claim;
        });
    }

    /**
     * Reconcile an approved insurance claim by crediting an insurance payment onto the invoice.
     */
    public function reconcileClaim(InsuranceClaim $claim, string $cashierId): InsuranceClaim
    {
        return DB::transaction(function () use ($claim, $cashierId) {
            if ($claim->status !== 'approved') {
                throw new DomainException("Only approved claims can be reconciled.");
            }

            if ($claim->approved_amount_cents <= 0) {
                throw new DomainException("Approved claim amount must be greater than zero to reconcile.");
            }

            // Record payment on invoice with payment_mode 'insurance'
            $this->paymentService->recordPayment($claim->invoice, [
                'payment_mode' => 'insurance',
                'amount_cents' => min($claim->approved_amount_cents, $claim->invoice->balance_cents),
                'transaction_reference' => $claim->claim_number,
                'notes' => "Reconciliation settlement for Claim #{$claim->claim_number} ({$claim->provider_name})",
            ], $cashierId);

            $claim->status = 'reconciled';
            $claim->settlement_date = now()->toDateString();
            $claim->save();

            return $claim->load(['invoice', 'patient']);
        });
    }
}
