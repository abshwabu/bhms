<?php

namespace App\Domain\Billing\Http\Controllers;

use App\Domain\Billing\Http\Resources\DiscountResource;
use App\Domain\Billing\Http\Resources\RefundResource;
use App\Domain\Billing\Models\Discount;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\Refund;
use App\Domain\Billing\Services\DiscountRefundApprovalService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscountRefundController extends Controller
{
    public function __construct(
        protected DiscountRefundApprovalService $approvalService
    ) {}

    /**
     * Request a discount on an invoice.
     * Auto-approves if <= threshold; enters pending_approval if > threshold.
     */
    public function requestDiscount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => ['required', 'uuid', 'exists:invoices,id'],
            'discount_type' => ['required', 'string', 'in:fixed,percentage'],
            'amount_cents' => ['required_if:discount_type,fixed', 'integer', 'min:1'],
            'percentage' => ['required_if:discount_type,percentage', 'numeric', 'min:0.01', 'max:100'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $userId = $request->user()?->id ?? $request->input('requested_by');
        if (!$userId) {
            return ApiResponse::error('User ID is required to request discount.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $invoice = Invoice::findOrFail($validated['invoice_id']);
            $discount = $this->approvalService->requestDiscount($invoice, $validated, $userId);

            $msg = $discount->requires_approval
                ? "Discount of \${$discount->amount} exceeds threshold (\$" . ($this->approvalService->getThresholdCents() / 100) . ") and has been submitted for supervisor approval."
                : "Discount of \${$discount->amount} approved and applied to invoice balance.";

            return ApiResponse::success(
                new DiscountResource($discount->load(['requester', 'approver'])),
                $msg,
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'DISCOUNT_ERROR', [], 422);
        }
    }

    /**
     * Supervisor approval of a pending discount.
     */
    public function approveDiscount(Request $request, Discount $discount): JsonResponse
    {
        $approverId = $request->user()?->id ?? $request->input('approved_by');
        if (!$approverId) {
            return ApiResponse::error('Approver user is required.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $approved = $this->approvalService->approveDiscount($discount, $approverId);

            return ApiResponse::success(
                new DiscountResource($approved->load(['requester', 'approver'])),
                "Discount has been approved and applied to invoice."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'DISCOUNT_APPROVAL_ERROR', [], 422);
        }
    }

    /**
     * Supervisor rejection of a pending discount.
     */
    public function rejectDiscount(Request $request, Discount $discount): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $rejectorId = $request->user()?->id ?? $request->input('rejected_by');
        if (!$rejectorId) {
            return ApiResponse::error('Rejector user is required.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $rejected = $this->approvalService->rejectDiscount($discount, $rejectorId, $validated['reason']);

            return ApiResponse::success(
                new DiscountResource($rejected->load(['requester', 'approver'])),
                "Discount request has been rejected."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'DISCOUNT_REJECTION_ERROR', [], 422);
        }
    }

    /**
     * List discounts awaiting second approval.
     */
    public function pendingDiscounts(Request $request): JsonResponse
    {
        $query = Discount::query()
            ->with(['invoice.patient', 'requester'])
            ->where('status', 'pending_approval')
            ->orderBy('created_at', 'desc');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        $discounts = $query->get();

        return ApiResponse::success(
            DiscountResource::collection($discounts),
            'Pending discounts requiring supervisor approval.'
        );
    }

    /**
     * Request a refund.
     * Auto-approves if <= threshold; requires approval if > threshold.
     */
    public function requestRefund(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => ['required', 'uuid', 'exists:invoices,id'],
            'payment_id' => ['nullable', 'uuid', 'exists:payments,id'],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'refund_mode' => ['required', 'string', 'in:cash,card,mobile_money,bank_transfer,credit_note'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $userId = $request->user()?->id ?? $request->input('requested_by');
        if (!$userId) {
            return ApiResponse::error('User ID is required to request refund.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $invoice = Invoice::findOrFail($validated['invoice_id']);
            $refund = $this->approvalService->requestRefund($invoice, $validated, $userId);

            $msg = $refund->requires_approval
                ? "Refund of \${$refund->amount} exceeds threshold (\$" . ($this->approvalService->getThresholdCents() / 100) . ") and requires second approval."
                : "Refund of \${$refund->amount} approved and processed.";

            return ApiResponse::success(
                new RefundResource($refund->load(['requester', 'approver', 'patient'])),
                $msg,
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'REFUND_ERROR', [], 422);
        }
    }

    /**
     * Approve pending refund.
     */
    public function approveRefund(Request $request, Refund $refund): JsonResponse
    {
        $approverId = $request->user()?->id ?? $request->input('approved_by');
        if (!$approverId) {
            return ApiResponse::error('Approver user is required.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $approved = $this->approvalService->approveRefund($refund, $approverId);

            return ApiResponse::success(
                new RefundResource($approved->load(['requester', 'approver', 'patient'])),
                "Refund has been approved and registered."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'REFUND_APPROVAL_ERROR', [], 422);
        }
    }

    /**
     * Reject pending refund.
     */
    public function rejectRefund(Request $request, Refund $refund): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $rejectorId = $request->user()?->id ?? $request->input('rejected_by');
        if (!$rejectorId) {
            return ApiResponse::error('Rejector user is required.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $rejected = $this->approvalService->rejectRefund($refund, $rejectorId, $validated['reason']);

            return ApiResponse::success(
                new RefundResource($rejected->load(['requester', 'approver', 'patient'])),
                "Refund request has been rejected."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'REFUND_REJECTION_ERROR', [], 422);
        }
    }

    /**
     * List refunds awaiting second approval.
     */
    public function pendingRefunds(Request $request): JsonResponse
    {
        $query = Refund::query()
            ->with(['invoice', 'patient', 'requester'])
            ->where('status', 'pending_approval')
            ->orderBy('created_at', 'desc');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        $refunds = $query->get();

        return ApiResponse::success(
            RefundResource::collection($refunds),
            'Pending refunds requiring supervisor approval.'
        );
    }
}
