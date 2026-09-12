<?php

namespace App\Domain\Pharmacy\Http\Controllers;

use App\Domain\Pharmacy\Http\Resources\DrugResource;
use App\Domain\Pharmacy\Models\Drug;
use App\Domain\Pharmacy\Services\PharmacyAlertService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DrugController extends Controller
{
    public function __construct(
        protected PharmacyAlertService $alertService
    ) {}

    /**
     * List inventory drugs with optional filtering and search.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Drug::query()->with('activeBatches');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('brand_name', 'ILIKE', "%{$term}%")
                  ->orWhere('generic_name', 'ILIKE', "%{$term}%")
                  ->orWhere('sku', 'ILIKE', "%{$term}%");
            });
        }

        if ($request->filled('form')) {
            $query->where('form', $request->input('form'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $drugs = $query->orderBy('brand_name')->get();

        // Optional filter by low stock in memory
        if ($request->boolean('low_stock_only')) {
            $drugs = $drugs->filter(fn($d) => $d->is_low_stock)->values();
        }

        return ApiResponse::success(
            DrugResource::collection($drugs),
            'Drugs catalog retrieved successfully.'
        );
    }

    /**
     * Show single drug catalog entry with all its batches.
     */
    public function show(Drug $drug): JsonResponse
    {
        $drug->load('batches');

        return ApiResponse::success(
            new DrugResource($drug),
            'Drug details retrieved successfully.'
        );
    }

    /**
     * Store new SKU in catalog.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'uuid'],
            'branch_id' => ['required', 'uuid'],
            'sku' => ['required', 'string', 'max:50', 'unique:drugs,sku'],
            'brand_name' => ['required', 'string', 'max:150'],
            'generic_name' => ['required', 'string', 'max:150'],
            'form' => ['required', 'string', 'max:50'],
            'strength' => ['required', 'string', 'max:100'],
            'unit_of_measure' => ['required', 'string', 'max:50'],
            'reorder_threshold' => ['nullable', 'integer', 'min:0'],
            'target_stock_level' => ['nullable', 'integer', 'min:1'],
            'unit_cost_cents' => ['nullable', 'integer', 'min:0'],
            'unit_price_cents' => ['nullable', 'integer', 'min:0'],
            'is_prescription_required' => ['nullable', 'boolean'],
            'is_controlled_substance' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $drug = Drug::create([
            'id' => (string) Str::uuid(),
            'reorder_threshold' => $validated['reorder_threshold'] ?? 50,
            'target_stock_level' => $validated['target_stock_level'] ?? 200,
            'unit_cost_cents' => $validated['unit_cost_cents'] ?? 0,
            'unit_price_cents' => $validated['unit_price_cents'] ?? 0,
            'is_prescription_required' => $validated['is_prescription_required'] ?? true,
            'is_controlled_substance' => $validated['is_controlled_substance'] ?? false,
            'is_active' => true,
            ...$validated,
        ]);

        return ApiResponse::success(
            new DrugResource($drug),
            "Drug SKU '{$drug->sku}' registered successfully.",
            201
        );
    }

    /**
     * Update an existing drug SKU.
     */
    public function update(Request $request, Drug $drug): JsonResponse
    {
        $validated = $request->validate([
            'brand_name' => ['sometimes', 'string', 'max:150'],
            'generic_name' => ['sometimes', 'string', 'max:150'],
            'form' => ['sometimes', 'string', 'max:50'],
            'strength' => ['sometimes', 'string', 'max:100'],
            'unit_of_measure' => ['sometimes', 'string', 'max:50'],
            'reorder_threshold' => ['sometimes', 'integer', 'min:0'],
            'target_stock_level' => ['sometimes', 'integer', 'min:1'],
            'unit_cost_cents' => ['sometimes', 'integer', 'min:0'],
            'unit_price_cents' => ['sometimes', 'integer', 'min:0'],
            'is_prescription_required' => ['sometimes', 'boolean'],
            'is_controlled_substance' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $drug->update($validated);

        return ApiResponse::success(
            new DrugResource($drug),
            "Drug SKU '{$drug->sku}' updated successfully."
        );
    }

    /**
     * Retrieve configurable low-stock and out-of-stock alerts.
     */
    public function lowStockAlerts(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id');
        $alerts = $this->alertService->getLowStockAlerts($branchId);

        return ApiResponse::success(
            $alerts,
            'Low-stock inventory alerts retrieved.'
        );
    }

    /**
     * Retrieve expiring and expired batch alerts.
     */
    public function expiringBatchesAlerts(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id');
        $days = (int) $request->input('days', 60);

        $alerts = $this->alertService->getExpiringBatchesAlerts($branchId, $days);

        return ApiResponse::success(
            $alerts,
            'Expiring and expired batch alerts retrieved.'
        );
    }

    /**
     * Retrieve aggregate inventory metrics for dashboard widgets.
     */
    public function metrics(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id');
        $metrics = $this->alertService->getInventoryMetrics($branchId);

        return ApiResponse::success(
            $metrics,
            'Inventory metrics retrieved.'
        );
    }
}
