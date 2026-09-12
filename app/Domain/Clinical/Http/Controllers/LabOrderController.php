<?php

namespace App\Domain\Clinical\Http\Controllers;

use App\Domain\Clinical\Http\Requests\ReviewOrderRequest;
use App\Domain\Clinical\Http\Requests\StoreLabOrderRequest;
use App\Domain\Clinical\Http\Resources\LabOrderResource;
use App\Domain\Clinical\Models\LabOrder;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Domain\Shared\Models\Branch;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LabOrderController extends Controller
{
    /**
     * List lab orders.
     */
    public function index(Request $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;

        $query = LabOrder::query()
            ->with(['orderingDoctor', 'reviewingDoctor', 'patient'])
            ->orderBy('ordered_at', 'desc');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        if ($request->filled('ordering_doctor_id')) {
            $query->where('ordering_doctor_id', $request->input('ordering_doctor_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->boolean('abnormal_only')) {
            $query->where('abnormal_flags', true);
        }

        if ($request->boolean('pending_review')) {
            $query->where('status', 'completed')->whereNull('reviewed_by_doctor_at');
        }

        $orders = $query->paginate($request->input('per_page', 20));

        $transformed = LabOrderResource::collection($orders->items())->resolve();
        $orders->setCollection(collect($transformed));

        return ApiResponse::paginated($orders, 'Laboratory orders retrieved successfully.');
    }

    /**
     * Retrieve single lab order.
     */
    public function show(LabOrder $labOrder): JsonResponse
    {
        $labOrder->load(['orderingDoctor', 'reviewingDoctor', 'patient', 'ehrRecord']);

        return ApiResponse::success(
            new LabOrderResource($labOrder),
            'Laboratory order retrieved successfully.'
        );
    }

    /**
     * Order laboratory tests directly from a clinical consultation.
     */
    public function store(StoreLabOrderRequest $request): JsonResponse
    {
        $doctorId = $request->user()?->id ?? $request->input('ordering_doctor_id');
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;
        $branch = Branch::findOrFail($branchId);

        $orderNumber = 'LAB-' . date('Y') . '-' . strtoupper(Str::random(6));

        $order = LabOrder::create([
            'id' => (string) Str::uuid(),
            'order_number' => $orderNumber,
            'organization_id' => $branch->organization_id,
            'branch_id' => $branch->id,
            'patient_id' => $request->input('patient_id'),
            'ehr_record_id' => $request->input('ehr_record_id'),
            'appointment_id' => $request->input('appointment_id'),
            'admission_id' => $request->input('admission_id'),
            'ordering_doctor_id' => $doctorId,
            'test_type' => $request->input('test_type'),
            'test_code' => $request->input('test_code'),
            'priority' => $request->input('priority', 'routine'),
            'clinical_indication' => $request->input('clinical_indication'),
            'special_instructions' => $request->input('special_instructions'),
            'status' => 'ordered',
            'ordered_at' => now(),
        ]);

        return ApiResponse::success(
            new LabOrderResource($order->fresh(['orderingDoctor', 'patient'])),
            "Lab test '{$order->test_type}' ordered successfully.",
            201
        );
    }

    /**
     * Update laboratory test results (by laboratory staff or automated analyzer).
     */
    public function updateResults(LabOrder $labOrder, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'results_summary' => ['required', 'string'],
            'structured_results' => ['nullable', 'array'],
            'abnormal_flags' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'in:sample_collected,in_progress,completed'],
        ]);

        $labOrder->update([
            'results_summary' => $validated['results_summary'],
            'structured_results' => $validated['structured_results'] ?? [],
            'abnormal_flags' => $validated['abnormal_flags'] ?? false,
            'status' => $validated['status'] ?? 'completed',
            'completed_at' => now(),
        ]);

        return ApiResponse::success(
            new LabOrderResource($labOrder->fresh(['orderingDoctor', 'patient'])),
            'Laboratory results updated successfully.'
        );
    }

    /**
     * Clinician reviews and signs off on completed lab results.
     */
    public function review(LabOrder $labOrder, ReviewOrderRequest $request): JsonResponse
    {
        $doctorId = $request->user()?->id ?? $request->input('doctor_id', $labOrder->ordering_doctor_id);

        $labOrder->markReviewed($doctorId, $request->input('notes'));

        return ApiResponse::success(
            new LabOrderResource($labOrder->fresh(['orderingDoctor', 'reviewingDoctor', 'patient'])),
            'Laboratory result reviewed and signed off by physician.'
        );
    }
}
