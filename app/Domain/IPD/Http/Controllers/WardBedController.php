<?php

namespace App\Domain\IPD\Http\Controllers;

use App\Domain\IPD\Http\Requests\StoreBedRequest;
use App\Domain\IPD\Http\Requests\StoreWardRequest;
use App\Domain\IPD\Http\Resources\BedResource;
use App\Domain\IPD\Http\Resources\WardResource;
use App\Domain\IPD\Models\Bed;
use App\Domain\IPD\Models\Ward;
use App\Domain\IPD\Services\BedManagementService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Domain\Shared\Models\Branch;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WardBedController extends Controller
{
    public function __construct(
        protected BedManagementService $bedService
    ) {
    }

    /**
     * List all active wards with bed occupancy counts.
     */
    public function indexWards(): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;

        $wards = Ward::with(['beds' => fn ($q) => $q->where('is_active', true)])
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        return ApiResponse::success(
            WardResource::collection($wards),
            'Inpatient wards retrieved successfully.'
        );
    }

    /**
     * Create a new inpatient ward.
     */
    public function storeWard(StoreWardRequest $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;
        $branch = Branch::findOrFail($branchId);

        $ward = Ward::create(array_merge($request->validated(), [
            'id' => (string) Str::uuid(),
            'organization_id' => $branch->organization_id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]));

        return ApiResponse::success(
            new WardResource($ward),
            "Ward '{$ward->name}' created successfully.",
            201
        );
    }

    /**
     * Add a bed to a ward.
     */
    public function storeBed(StoreBedRequest $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;
        $branch = Branch::findOrFail($branchId);

        $bed = Bed::create(array_merge($request->validated(), [
            'id' => (string) Str::uuid(),
            'organization_id' => $branch->organization_id,
            'branch_id' => $branch->id,
            'status' => 'available',
            'is_active' => true,
        ]));

        return ApiResponse::success(
            new BedResource($bed->load('ward')),
            "Bed '{$bed->bed_number}' created successfully.",
            201
        );
    }

    /**
     * Visual Ward Bed Map with real-time patient allocations.
     */
    public function bedMap(Request $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;

        $map = $this->bedService->getBedMap($branchId, $request->input('ward_id'));

        return ApiResponse::success($map, 'Visual bed map retrieved successfully.');
    }

    /**
     * Update bed operational status (e.g. cleaning -> available, maintenance).
     */
    public function updateBedStatus(Request $request, Bed $bed): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['available', 'occupied', 'cleaning', 'maintenance', 'reserved'])],
        ]);

        $bed->update(['status' => $validated['status']]);

        return ApiResponse::success(
            new BedResource($bed->fresh(['ward', 'currentAdmission.patient'])),
            "Bed #{$bed->bed_number} status updated to {$validated['status']}."
        );
    }
}
