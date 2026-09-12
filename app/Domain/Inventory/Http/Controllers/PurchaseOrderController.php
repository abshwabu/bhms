<?php

namespace App\Domain\Inventory\Http\Controllers;

use App\Domain\Inventory\Http\Resources\PurchaseOrderResource;
use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Services\PurchaseOrderService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderService $poService
    ) {}

    /**
     * List purchase orders.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PurchaseOrder::query()
            ->with(['vendor', 'items.inventoryItem', 'creator', 'approver'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->input('vendor_id'));
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('po_number', 'ILIKE', "%{$term}%")
                  ->orWhereHas('vendor', fn($vq) => $vq->where('name', 'ILIKE', "%{$term}%"));
            });
        }

        $pos = $query->paginate($request->input('per_page', 25));

        return ApiResponse::paginated(
            $pos->through(fn($p) => new PurchaseOrderResource($p)),
            'Purchase orders retrieved.'
        );
    }

    /**
     * Create draft purchase order.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'vendor_id' => ['required', 'uuid', 'exists:vendors,id'],
            'order_date' => ['nullable', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'tax_cents' => ['nullable', 'integer', 'min:0'],
            'shipping_cost_cents' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'terms_and_conditions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'uuid', 'exists:inventory_items,id'],
            'items.*.quantity_ordered' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost_cents' => ['nullable', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $userId = $request->user()?->id ?? $request->input('created_by');
        if (!$userId) {
            return ApiResponse::error('User is required to create PO.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $po = $this->poService->createPurchaseOrder($validated, $userId);

            return ApiResponse::success(
                new PurchaseOrderResource($po),
                "Purchase order #{$po->po_number} created in draft state.",
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'PO_CREATION_ERROR', [], 422);
        }
    }

    /**
     * Show purchase order details.
     */
    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->load(['vendor', 'items.inventoryItem', 'creator', 'submitter', 'approver']);

        return ApiResponse::success(
            new PurchaseOrderResource($purchaseOrder),
            'Purchase order details retrieved.'
        );
    }

    /**
     * Submit draft purchase order for supervisor approval.
     */
    public function submit(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $userId = $request->user()?->id ?? $request->input('submitted_by');
        if (!$userId) {
            return ApiResponse::error('User is required to submit PO.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $submitted = $this->poService->submitPurchaseOrder($purchaseOrder, $userId);

            return ApiResponse::success(
                new PurchaseOrderResource($submitted),
                "Purchase order #{$submitted->po_number} submitted for approval."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'PO_SUBMISSION_ERROR', [], 422);
        }
    }

    /**
     * Approve submitted purchase order.
     */
    public function approve(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $approverId = $request->user()?->id ?? $request->input('approved_by');
        if (!$approverId) {
            return ApiResponse::error('Approver user is required.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $approved = $this->poService->approvePurchaseOrder($purchaseOrder, $approverId);

            return ApiResponse::success(
                new PurchaseOrderResource($approved),
                "Purchase order #{$approved->po_number} approved. Ready to receive items."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'PO_APPROVAL_ERROR', [], 422);
        }
    }

    /**
     * Reject submitted purchase order.
     */
    public function reject(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $rejectorId = $request->user()?->id ?? $request->input('rejected_by');
        if (!$rejectorId) {
            return ApiResponse::error('Rejector user is required.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $rejected = $this->poService->rejectPurchaseOrder($purchaseOrder, $rejectorId, $validated['reason']);

            return ApiResponse::success(
                new PurchaseOrderResource($rejected),
                "Purchase order #{$rejected->po_number} rejected."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'PO_REJECTION_ERROR', [], 422);
        }
    }

    /**
     * Receive goods from an approved purchase order into stock.
     */
    public function receive(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'uuid', 'exists:purchase_order_items,id'],
            'items.*.quantity_received' => ['required', 'integer', 'min:1'],
        ]);

        $userId = $request->user()?->id ?? $request->input('received_by');
        if (!$userId) {
            return ApiResponse::error('User is required to receive items.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $updatedPo = $this->poService->receiveItems($purchaseOrder, $validated['items'], $userId);

            return ApiResponse::success(
                new PurchaseOrderResource($updatedPo),
                "Items received from PO #{$updatedPo->po_number}. Inventory stock updated."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'PO_RECEIPT_ERROR', [], 422);
        }
    }
}
