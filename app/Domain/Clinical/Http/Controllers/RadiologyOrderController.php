<?php

namespace App\Domain\Clinical\Http\Controllers;

use App\Domain\Clinical\Http\Requests\ReviewOrderRequest;
use App\Domain\Clinical\Http\Requests\StoreRadiologyOrderRequest;
use App\Domain\Clinical\Http\Resources\RadiologyOrderResource;
use App\Domain\Clinical\Models\RadiologyOrder;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Domain\Shared\Models\Branch;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RadiologyOrderController extends Controller
{
    /**
     * List radiology orders.
     */
    public function index(Request $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;

        $query = RadiologyOrder::query()
            ->with(['orderingDoctor', 'radiologist', 'reviewingDoctor', 'patient'])
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

        if ($request->filled('modality')) {
            $query->where('modality', $request->input('modality'));
        }

        if ($request->boolean('pending_review')) {
            $query->whereIn('status', ['reported', 'performed'])->whereNull('reviewed_by_doctor_at');
        }

        $orders = $query->paginate($request->input('per_page', 20));

        $transformed = RadiologyOrderResource::collection($orders->items())->resolve();
        $orders->setCollection(collect($transformed));

        return ApiResponse::paginated($orders, 'Radiology orders retrieved successfully.');
    }

    /**
     * Retrieve single radiology order.
     */
    public function show(RadiologyOrder $radiologyOrder): JsonResponse
    {
        $radiologyOrder->load(['orderingDoctor', 'radiologist', 'reviewingDoctor', 'patient', 'ehrRecord']);

        return ApiResponse::success(
            new RadiologyOrderResource($radiologyOrder),
            'Radiology order retrieved successfully.'
        );
    }

    /**
     * Order diagnostic imaging directly from consultation.
     */
    public function store(StoreRadiologyOrderRequest $request): JsonResponse
    {
        $doctorId = $request->user()?->id ?? $request->input('ordering_doctor_id');
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;
        $branch = Branch::findOrFail($branchId);

        $orderNumber = 'RAD-' . date('Y') . '-' . strtoupper(Str::random(6));

        $order = RadiologyOrder::create([
            'id' => (string) Str::uuid(),
            'order_number' => $orderNumber,
            'organization_id' => $branch->organization_id,
            'branch_id' => $branch->id,
            'patient_id' => $request->input('patient_id'),
            'ehr_record_id' => $request->input('ehr_record_id'),
            'appointment_id' => $request->input('appointment_id'),
            'admission_id' => $request->input('admission_id'),
            'ordering_doctor_id' => $doctorId,
            'modality' => $request->input('modality'),
            'body_part' => $request->input('body_part'),
            'procedure_name' => $request->input('procedure_name'),
            'priority' => $request->input('priority', 'routine'),
            'clinical_indication' => $request->input('clinical_indication'),
            'transport_required' => $request->boolean('transport_required'),
            'is_pregnant_or_possible' => $request->boolean('is_pregnant_or_possible'),
            'status' => 'ordered',
            'ordered_at' => now(),
        ]);

        return ApiResponse::success(
            new RadiologyOrderResource($order->fresh(['orderingDoctor', 'patient'])),
            "Radiology study '{$order->procedure_name}' ordered successfully.",
            201
        );
    }

    /**
     * Radiologist reports findings and impression.
     */
    public function updateReport(RadiologyOrder $radiologyOrder, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'findings' => ['required', 'string'],
            'impression' => ['required', 'string'],
            'status' => ['nullable', 'string', 'in:performed,reported'],
        ]);

        $radiologistId = $request->user()?->id ?? $request->input('radiologist_id');

        $radiologyOrder->update([
            'findings' => $validated['findings'],
            'impression' => $validated['impression'],
            'radiologist_id' => $radiologistId,
            'status' => $validated['status'] ?? 'reported',
            'reported_at' => now(),
        ]);

        return ApiResponse::success(
            new RadiologyOrderResource($radiologyOrder->fresh(['orderingDoctor', 'radiologist', 'patient'])),
            'Radiology imaging report updated successfully.'
        );
    }

    /**
     * Clinician reviews and signs off on imaging report.
     */
    public function review(RadiologyOrder $radiologyOrder, ReviewOrderRequest $request): JsonResponse
    {
        $doctorId = $request->user()?->id ?? $request->input('doctor_id', $radiologyOrder->ordering_doctor_id);

        $radiologyOrder->markReviewed($doctorId, $request->input('notes'));

        return ApiResponse::success(
            new RadiologyOrderResource($radiologyOrder->fresh(['orderingDoctor', 'reviewingDoctor', 'patient'])),
            'Radiology study reviewed and signed off by physician.'
        );
    }
}
