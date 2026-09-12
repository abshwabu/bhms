<?php

namespace App\Domain\Billing\Http\Controllers;

use App\Domain\Billing\Http\Resources\PriceListResource;
use App\Domain\Billing\Models\PriceList;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PriceListController extends Controller
{
    /**
     * List price lists / procedure catalog.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PriceList::query()->orderBy('category')->orderBy('name');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('department')) {
            $query->where('department', $request->input('department'));
        }

        if ($request->has('is_package')) {
            $query->where('is_package', filter_var($request->input('is_package'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'ILIKE', "%{$term}%")
                  ->orWhere('code', 'ILIKE', "%{$term}%");
            });
        }

        $items = $query->get();

        return ApiResponse::success(
            PriceListResource::collection($items),
            'Price list catalog retrieved successfully.'
        );
    }

    /**
     * Create a new procedure or package in the price list.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'uuid'],
            'branch_id' => ['required', 'uuid'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:50'],
            'department' => ['required', 'string', 'max:100'],
            'unit_price_cents' => ['required', 'integer', 'min:0'],
            'is_package' => ['nullable', 'boolean'],
            'package_items' => ['nullable', 'array'],
            'description' => ['nullable', 'string'],
        ]);

        $item = PriceList::create([
            'id' => (string) Str::uuid(),
            'is_package' => $validated['is_package'] ?? false,
            'package_items' => $validated['package_items'] ?? [],
            'is_active' => true,
            ...$validated,
        ]);

        return ApiResponse::success(
            new PriceListResource($item),
            "Price list entry '{$item->name}' created successfully.",
            201
        );
    }

    /**
     * Show single price list item.
     */
    public function show(PriceList $priceList): JsonResponse
    {
        return ApiResponse::success(
            new PriceListResource($priceList),
            'Price item retrieved successfully.'
        );
    }

    /**
     * Update price list item.
     */
    public function update(Request $request, PriceList $priceList): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'category' => ['sometimes', 'string', 'max:50'],
            'department' => ['sometimes', 'string', 'max:100'],
            'unit_price_cents' => ['sometimes', 'integer', 'min:0'],
            'is_package' => ['sometimes', 'boolean'],
            'package_items' => ['sometimes', 'array'],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $priceList->update($validated);

        return ApiResponse::success(
            new PriceListResource($priceList),
            "Price item '{$priceList->name}' updated successfully."
        );
    }
}
