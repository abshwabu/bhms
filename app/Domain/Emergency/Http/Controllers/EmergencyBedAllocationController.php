<?php

namespace App\Domain\Emergency\Http\Controllers;

use App\Domain\Emergency\Http\Resources\EmergencyBedAllocationResource;
use App\Domain\Emergency\Http\Resources\EmergencyCaseResource;
use App\Domain\Emergency\Models\EmergencyCase;
use App\Domain\Emergency\Services\EmergencyBedAllocationService;
use App\Domain\IPD\Models\Bed;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmergencyBedAllocationController extends Controller
{
    public function __construct(
        protected EmergencyBedAllocationService $bedService
    ) {}

    /**
     * Allocate bed with priority override support over standard queue.
     * Acceptance criterion: Emergency bed allocation can override standard bed queue with a logged justification.
     */
    public function allocate(Request $request, EmergencyCase $emergencyCase): JsonResponse
    {
        $validated = $request->validate([
            'bed_id' => ['required', 'uuid', 'exists:beds,id'],
            'is_override' => ['nullable', 'boolean'],
            'override_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $allocatedByUserId = $request->user()?->id ?? $emergencyCase->assigned_doctor_id ?? $emergencyCase->assigned_nurse_id;
        if (!$allocatedByUserId) {
            return ApiResponse::error('Allocating clinician user is required.', 'UNAUTHENTICATED', [], 422);
        }

        $isOverride = (bool) ($validated['is_override'] ?? false);
        $reason = $validated['override_reason'] ?? null;

        try {
            $allocation = $this->bedService->allocateBed(
                $emergencyCase,
                $validated['bed_id'],
                $allocatedByUserId,
                $isOverride,
                $reason
            );

            $msg = $isOverride
                ? "Priority Bed Override Executed: Bed {$allocation->bed->bed_number} allocated to {$emergencyCase->case_number} with logged justification."
                : "Bed {$allocation->bed->bed_number} allocated to {$emergencyCase->case_number}.";

            return ApiResponse::success([
                'allocation' => new EmergencyBedAllocationResource($allocation),
                'case' => new EmergencyCaseResource($emergencyCase->fresh(['bed.ward', 'doctor', 'nurse', 'latestTriageRecord'])),
            ], $msg, 201);
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'BED_ALLOCATION_ERROR', [], 422);
        }
    }

    /**
     * Release allocated bed.
     */
    public function release(Request $request, EmergencyCase $emergencyCase): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $this->bedService->releaseBed($emergencyCase, $validated['notes'] ?? null);

        return ApiResponse::success(
            new EmergencyCaseResource($emergencyCase->fresh(['bed', 'doctor', 'nurse'])),
            "Emergency bed released for case {$emergencyCase->case_number}."
        );
    }

    /**
     * List emergency & acute care beds.
     */
    public function availableBeds(Request $request): JsonResponse
    {
        $query = Bed::query()
            ->with('ward')
            ->where('is_active', true);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        $beds = $query->orderBy('bed_number', 'asc')->get();

        return ApiResponse::success(
            $beds->map(fn($b) => [
                'id' => $b->id,
                'bed_number' => $b->bed_number,
                'bed_type' => $b->bed_type,
                'status' => $b->status,
                'ward_id' => $b->ward_id,
                'ward_name' => $b->ward?->name,
                'is_available' => $b->status === 'available',
                'features' => $b->features ?? [],
            ]),
            'Emergency and acute care beds retrieved.'
        );
    }
}
