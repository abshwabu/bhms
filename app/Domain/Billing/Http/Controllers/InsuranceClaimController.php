<?php

namespace App\Domain\Billing\Http\Controllers;

use App\Domain\Billing\Http\Resources\InsuranceClaimResource;
use App\Domain\Billing\Models\InsuranceClaim;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Services\InsuranceClaimService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsuranceClaimController extends Controller
{
    public function __construct(
        protected InsuranceClaimService $claimService
    ) {}

    /**
     * List submitted insurance and TPA claims.
     */
    public function index(Request $request): JsonResponse
    {
        $query = InsuranceClaim::query()
            ->with(['invoice', 'patient', 'insurancePolicy', 'submitter'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('provider_name')) {
            $query->where('provider_name', 'ILIKE', '%' . trim($request->input('provider_name')) . '%');
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('claim_number', 'ILIKE', "%{$term}%")
                  ->orWhere('policy_number', 'ILIKE', "%{$term}%")
                  ->orWhere('provider_name', 'ILIKE', "%{$term}%");
            });
        }

        $claims = $query->paginate($request->input('per_page', 25));

        return ApiResponse::paginated(
            $claims->through(fn($c) => new InsuranceClaimResource($c)),
            'Insurance claims retrieved successfully.'
        );
    }

    /**
     * Submit an insurance claim for an invoice.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => ['required', 'uuid', 'exists:invoices,id'],
            'patient_insurance_id' => ['nullable', 'uuid', 'exists:patient_insurance,id'],
            'pre_auth_number' => ['nullable', 'string', 'max:100'],
            'claimed_amount_cents' => ['nullable', 'integer', 'min:1'],
            'copay_amount_cents' => ['nullable', 'integer', 'min:0'],
            'deductible_amount_cents' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $userId = $request->user()?->id ?? $request->input('submitted_by');
        if (!$userId) {
            return ApiResponse::error('User ID is required to submit claim.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $invoice = Invoice::findOrFail($validated['invoice_id']);
            $claim = $this->claimService->submitClaim($invoice, $validated, $userId);

            return ApiResponse::success(
                new InsuranceClaimResource($claim),
                "Insurance claim #{$claim->claim_number} submitted successfully to {$claim->provider_name}.",
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'CLAIM_SUBMISSION_ERROR', [], 422);
        }
    }

    /**
     * Show single claim.
     */
    public function show(InsuranceClaim $insuranceClaim): JsonResponse
    {
        $insuranceClaim->load(['invoice.items', 'patient', 'insurancePolicy', 'submitter']);

        return ApiResponse::success(
            new InsuranceClaimResource($insuranceClaim),
            'Claim details retrieved.'
        );
    }

    /**
     * Adjudicate insurance claim response.
     */
    public function adjudicate(Request $request, InsuranceClaim $insuranceClaim): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:approved,rejected,under_review'],
            'approved_amount_cents' => ['required_if:status,approved', 'integer', 'min:0'],
            'adjudication_notes' => ['nullable', 'string'],
            'rejection_reason' => ['required_if:status,rejected', 'string', 'max:255'],
        ]);

        $userId = $request->user()?->id ?? $request->input('adjudicated_by');
        if (!$userId) {
            return ApiResponse::error('User ID is required to adjudicate claim.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $claim = $this->claimService->adjudicateClaim($insuranceClaim, $validated, $userId);

            return ApiResponse::success(
                new InsuranceClaimResource($claim),
                "Claim #{$claim->claim_number} status updated to '{$claim->status}'."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'CLAIM_ADJUDICATION_ERROR', [], 422);
        }
    }

    /**
     * Reconcile claim payout by crediting insurance payment to the invoice.
     */
    public function reconcile(Request $request, InsuranceClaim $insuranceClaim): JsonResponse
    {
        $cashierId = $request->user()?->id ?? $request->input('cashier_id');
        if (!$cashierId) {
            return ApiResponse::error('Cashier user is required to reconcile claim.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $claim = $this->claimService->reconcileClaim($insuranceClaim, $cashierId);

            return ApiResponse::success(
                new InsuranceClaimResource($claim),
                "Claim #{$claim->claim_number} reconciled. Payment credited to invoice."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'CLAIM_RECONCILIATION_ERROR', [], 422);
        }
    }
}
