<?php

namespace App\Domain\Pharmacy\Http\Controllers;

use App\Domain\Pharmacy\Http\Resources\DrugBatchResource;
use App\Domain\Pharmacy\Http\Resources\DrugStockMovementResource;
use App\Domain\Pharmacy\Models\DrugStockMovement;
use App\Domain\Pharmacy\Services\PharmacyInventoryService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function __construct(
        protected PharmacyInventoryService $inventoryService
    ) {}

    /**
     * List stock movement audit records.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DrugStockMovement::query()
            ->with(['drug', 'batch', 'performer']);

        if ($request->filled('drug_id')) {
            $query->where('drug_id', $request->input('drug_id'));
        }

        if ($request->filled('drug_batch_id')) {
            $query->where('drug_batch_id', $request->input('drug_batch_id'));
        }

        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->input('movement_type'));
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 25));

        return ApiResponse::success(
            DrugStockMovementResource::collection($movements),
            'Stock movements retrieved successfully.'
        );
    }

    /**
     * Intake new stock shipment or batch.
     */
    public function intake(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'drug_id' => ['required', 'uuid', 'exists:drugs,id'],
            'batch_number' => ['required', 'string', 'max:100'],
            'manufacturing_date' => ['nullable', 'date'],
            'expiry_date' => ['required', 'date', 'after:today'],
            'quantity_received' => ['required', 'integer', 'min:1'],
            'unit_cost_cents' => ['nullable', 'integer', 'min:0'],
            'supplier_name' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
        ]);

        $userId = $request->user()?->id ?? $request->input('performed_by');
        if (!$userId) {
            return ApiResponse::error('User ID is required for stock intake.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $batch = $this->inventoryService->intakeStock($validated, $userId);

            return ApiResponse::success(
                new DrugBatchResource($batch),
                "Stock intake recorded successfully for batch '{$batch->batch_number}'.",
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'STOCK_INTAKE_ERROR', [], 422);
        }
    }

    /**
     * Adjust stock quantity (+ or -).
     */
    public function adjust(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'drug_batch_id' => ['required', 'uuid', 'exists:drug_batches,id'],
            'quantity_delta' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $userId = $request->user()?->id ?? $request->input('performed_by');
        if (!$userId) {
            return ApiResponse::error('User ID is required for stock adjustment.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $movement = $this->inventoryService->adjustStock(
                $validated['drug_batch_id'],
                $validated['quantity_delta'],
                $validated['reason'],
                $userId
            );

            return ApiResponse::success(
                new DrugStockMovementResource($movement->load(['drug', 'batch'])),
                'Stock adjustment logged successfully.'
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'STOCK_ADJUSTMENT_ERROR', [], 422);
        }
    }

    /**
     * Quarantine a batch to immediately take it out of dispensing circulation.
     */
    public function quarantine(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'drug_batch_id' => ['required', 'uuid', 'exists:drug_batches,id'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $userId = $request->user()?->id ?? $request->input('performed_by');
        if (!$userId) {
            return ApiResponse::error('User ID is required for quarantining batch.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $batch = $this->inventoryService->quarantineBatch(
                $validated['drug_batch_id'],
                $validated['reason'],
                $userId
            );

            return ApiResponse::success(
                new DrugBatchResource($batch->load('drug')),
                "Batch '{$batch->batch_number}' is now quarantined."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'BATCH_QUARANTINE_ERROR', [], 422);
        }
    }

    /**
     * Write off stock as waste/damaged.
     */
    public function writeOff(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'drug_batch_id' => ['required', 'uuid', 'exists:drug_batches,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $userId = $request->user()?->id ?? $request->input('performed_by');
        if (!$userId) {
            return ApiResponse::error('User ID is required for write off.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $movement = $this->inventoryService->writeOffStock(
                $validated['drug_batch_id'],
                $validated['quantity'],
                $validated['reason'],
                $userId
            );

            return ApiResponse::success(
                new DrugStockMovementResource($movement->load(['drug', 'batch'])),
                'Stock write-off recorded successfully.'
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'STOCK_WRITE_OFF_ERROR', [], 422);
        }
    }
}
