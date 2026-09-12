<?php

namespace App\Domain\Inventory\Http\Controllers;

use App\Domain\Inventory\Http\Resources\InventoryItemResource;
use App\Domain\Inventory\Http\Resources\InventoryMovementResource;
use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Services\InventoryStockService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    public function __construct(
        protected InventoryStockService $stockService
    ) {}

    /**
     * List inventory items with filtering & search.
     */
    public function index(Request $request): JsonResponse
    {
        $query = InventoryItem::query()
            ->with('defaultVendor')
            ->orderBy('name', 'asc');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('stock_status')) {
            $st = $request->input('stock_status');
            if ($st === 'out_of_stock') {
                $query->where('current_stock', '<=', 0);
            } elseif ($st === 'low_stock') {
                $query->whereColumn('current_stock', '<=', 'min_stock_level')->where('current_stock', '>', 0);
            } elseif ($st === 'adequate') {
                $query->whereColumn('current_stock', '>', 'min_stock_level');
            }
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'ILIKE', "%{$term}%")
                  ->orWhere('item_code', 'ILIKE', "%{$term}%")
                  ->orWhere('storage_location', 'ILIKE', "%{$term}%");
            });
        }

        $items = $query->paginate($request->input('per_page', 25));

        return ApiResponse::paginated(
            $items->through(fn($i) => new InventoryItemResource($i)),
            'Inventory items retrieved.'
        );
    }

    /**
     * Create a new inventory catalog item.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'item_code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'in:medical,non_medical'],
            'sub_category' => ['nullable', 'string', 'max:50'],
            'unit_of_measure' => ['required', 'string', 'max:30'],
            'current_stock' => ['nullable', 'integer', 'min:0'],
            'min_stock_level' => ['nullable', 'integer', 'min:0'],
            'max_stock_level' => ['nullable', 'integer', 'min:0'],
            'reorder_quantity' => ['nullable', 'integer', 'min:1'],
            'unit_cost_cents' => ['nullable', 'integer', 'min:0'],
            'default_vendor_id' => ['nullable', 'uuid', 'exists:vendors,id'],
            'storage_location' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $item = InventoryItem::create($validated);

        return ApiResponse::success(
            new InventoryItemResource($item->load('defaultVendor')),
            "Inventory item '{$item->name}' created successfully.",
            201
        );
    }

    /**
     * Show single inventory item details with movement history.
     */
    public function show(InventoryItem $inventoryItem): JsonResponse
    {
        $inventoryItem->load(['defaultVendor', 'movements.performer']);

        return ApiResponse::success(
            new InventoryItemResource($inventoryItem),
            'Inventory item details retrieved.'
        );
    }

    /**
     * Update inventory item attributes.
     */
    public function update(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'category' => ['sometimes', 'required', 'string', 'in:medical,non_medical'],
            'sub_category' => ['nullable', 'string', 'max:50'],
            'unit_of_measure' => ['sometimes', 'required', 'string', 'max:30'],
            'min_stock_level' => ['nullable', 'integer', 'min:0'],
            'max_stock_level' => ['nullable', 'integer', 'min:0'],
            'reorder_quantity' => ['nullable', 'integer', 'min:1'],
            'unit_cost_cents' => ['nullable', 'integer', 'min:0'],
            'default_vendor_id' => ['nullable', 'uuid', 'exists:vendors,id'],
            'storage_location' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $inventoryItem->update($validated);

        return ApiResponse::success(
            new InventoryItemResource($inventoryItem->load('defaultVendor')),
            "Inventory item updated successfully."
        );
    }

    /**
     * Perform manual stock count adjustment (addition or reduction).
     */
    public function adjust(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'not_in:0'],
            'type' => ['required', 'string', 'in:adjustment_addition,adjustment_reduction'],
            'department' => ['nullable', 'string', 'max:100'],
            'notes' => ['required', 'string', 'max:255'],
        ]);

        $userId = $request->user()?->id ?? $request->input('performed_by');
        if (!$userId) {
            return ApiResponse::error('User is required to adjust stock.', 'UNAUTHENTICATED', [], 422);
        }

        $qtyChange = $validated['type'] === 'adjustment_reduction'
            ? -abs($validated['quantity'])
            : abs($validated['quantity']);

        try {
            $movement = $this->stockService->adjustStock(
                $inventoryItem,
                $qtyChange,
                $validated['type'],
                $userId,
                $validated['department'] ?? null,
                $validated['notes']
            );

            return ApiResponse::success(
                new InventoryMovementResource($movement->load('performer')),
                "Stock adjusted successfully. New balance: {$movement->quantity_after} {$inventoryItem->unit_of_measure}."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'STOCK_ADJUSTMENT_ERROR', [], 422);
        }
    }

    /**
     * Record clinical or ward department consumption of supplies.
     */
    public function consume(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'department' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $userId = $request->user()?->id ?? $request->input('performed_by');
        if (!$userId) {
            return ApiResponse::error('User is required to log consumption.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $movement = $this->stockService->consumeStock(
                $inventoryItem,
                $validated['quantity'],
                $validated['department'],
                $userId,
                $validated['notes'] ?? null
            );

            return ApiResponse::success(
                new InventoryMovementResource($movement->load('performer')),
                "Consumption logged. Remaining stock: {$movement->quantity_after} {$inventoryItem->unit_of_measure}."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'STOCK_CONSUMPTION_ERROR', [], 422);
        }
    }

    /**
     * Reconcile stock count against recorded movements.
     */
    public function reconcile(InventoryItem $inventoryItem): JsonResponse
    {
        $report = $this->stockService->reconcileStock($inventoryItem);

        return ApiResponse::success($report, 'Stock reconciliation report generated.');
    }

    /**
     * Get list of items at or below reorder threshold.
     */
    public function lowStockAlerts(Request $request): JsonResponse
    {
        $alerts = $this->stockService->getLowStockAlerts($request->input('branch_id'));

        return ApiResponse::success($alerts, 'Low stock and reorder alerts retrieved.');
    }

    /**
     * High-level inventory dashboard KPI summary.
     */
    public function summary(Request $request): JsonResponse
    {
        $summary = $this->stockService->getInventorySummary($request->input('branch_id'));

        return ApiResponse::success($summary, 'Inventory executive summary generated.');
    }
}
