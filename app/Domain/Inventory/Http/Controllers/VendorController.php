<?php

namespace App\Domain\Inventory\Http\Controllers;

use App\Domain\Inventory\Http\Resources\VendorResource;
use App\Domain\Inventory\Models\Vendor;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    /**
     * List vendors.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Vendor::query()->orderBy('name', 'asc');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'ILIKE', "%{$term}%")
                  ->orWhere('vendor_code', 'ILIKE', "%{$term}%")
                  ->orWhere('contact_name', 'ILIKE', "%{$term}%")
                  ->orWhere('email', 'ILIKE', "%{$term}%");
            });
        }

        $vendors = $query->paginate($request->input('per_page', 25));

        return ApiResponse::paginated(
            $vendors->through(fn($v) => new VendorResource($v)),
            'Vendors retrieved.'
        );
    }

    /**
     * Create vendor.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'vendor_code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'array'],
            'payment_terms' => ['nullable', 'string', 'in:immediate,net_15,net_30,net_60'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $vendor = Vendor::create($validated);

        return ApiResponse::success(
            new VendorResource($vendor),
            "Vendor '{$vendor->name}' created successfully.",
            201
        );
    }

    /**
     * Show vendor details.
     */
    public function show(Vendor $vendor): JsonResponse
    {
        return ApiResponse::success(
            new VendorResource($vendor),
            'Vendor details retrieved.'
        );
    }

    /**
     * Update vendor.
     */
    public function update(Request $request, Vendor $vendor): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'array'],
            'payment_terms' => ['nullable', 'string', 'in:immediate,net_15,net_30,net_60'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $vendor->update($validated);

        return ApiResponse::success(
            new VendorResource($vendor),
            "Vendor updated successfully."
        );
    }
}
