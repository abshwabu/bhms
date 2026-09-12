<?php

namespace App\Domain\Radiology\Http\Controllers;

use App\Domain\Clinical\Models\RadiologyOrder;
use App\Domain\Radiology\Http\Requests\ScheduleImagingOrderRequest;
use App\Domain\Radiology\Http\Requests\StoreImagingOrderRequest;
use App\Domain\Radiology\Http\Resources\ImagingOrderResource;
use App\Domain\Radiology\Models\ImagingOrder;
use App\Domain\Radiology\Services\PacsIntegrationService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ImagingOrderController extends Controller
{
    public function __construct(
        protected PacsIntegrationService $pacsService
    ) {
    }

    /**
     * List imaging orders with filtering by modality, priority, and status.
     */
    public function index(Request $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;

        $query = ImagingOrder::query()
            ->with(['patient', 'orderingDoctor', 'technologist', 'latestReport', 'files'])
            ->orderBy('created_at', 'desc');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($request->filled('modality')) {
            $query->where('modality', $request->input('modality'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('accession_number', 'ILIKE', "%{$term}%")
                  ->orWhere('procedure_name', 'ILIKE', "%{$term}%")
                  ->orWhere('body_part', 'ILIKE', "%{$term}%")
                  ->orWhereHas('patient', function ($pq) use ($term) {
                      $pq->where('first_name', 'ILIKE', "%{$term}%")
                         ->orWhere('last_name', 'ILIKE', "%{$term}%")
                         ->orWhere('mrn', 'ILIKE', "%{$term}%");
                  });
            });
        }

        $orders = $query->paginate($request->input('per_page', 20));

        $transformed = ImagingOrderResource::collection($orders->items())->resolve();
        $orders->setCollection(collect($transformed));

        return ApiResponse::paginated($orders, 'Imaging orders retrieved successfully.');
    }

    /**
     * Intake a new imaging order with accession number & PACS Study UID.
     */
    public function store(StoreImagingOrderRequest $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;
        $userId = $request->user()?->id;

        $clinicalOrder = null;
        if ($request->filled('radiology_order_id')) {
            $clinicalOrder = RadiologyOrder::find($request->input('radiology_order_id'));
        }

        $accession = 'ACC-' . date('Y') . '-' . strtoupper(Str::random(8));
        $studyUid = $this->pacsService->generateStudyInstanceUid();

        $order = ImagingOrder::create([
            'id' => (string) Str::uuid(),
            'accession_number' => $accession,
            'organization_id' => $clinicalOrder?->organization_id ?? ($request->user()?->organization_id ?? 'b9ff561a-5396-4309-9b08-3e7b358310e8'),
            'branch_id' => $branchId ?? ($clinicalOrder?->branch_id ?? 'b9ff561a-5396-4309-9b08-3e7b358310e9'),
            'patient_id' => $request->input('patient_id'),
            'radiology_order_id' => $clinicalOrder?->id,
            'ordering_doctor_id' => $request->input('ordering_doctor_id') ?? ($clinicalOrder?->ordering_doctor_id ?? $userId),
            'technologist_id' => $request->input('technologist_id'),
            'modality' => $request->input('modality'),
            'procedure_code' => $request->input('procedure_code'),
            'procedure_name' => $request->input('procedure_name'),
            'body_part' => $request->input('body_part'),
            'priority' => $request->input('priority', 'routine'),
            'clinical_indication' => $request->input('clinical_indication'),
            'patient_preparation' => $request->input('patient_preparation'),
            'is_pregnant_or_possible' => $request->boolean('is_pregnant_or_possible'),
            'transport_mode' => $request->input('transport_mode', 'ambulatory'),
            'status' => $request->filled('scheduled_at') ? 'scheduled' : 'ordered',
            'scheduled_at' => $request->input('scheduled_at'),
            'scheduled_room' => $request->input('scheduled_room'),
            'dicom_study_uid' => $studyUid,
            'notes' => $request->input('notes'),
        ]);

        if ($clinicalOrder && $order->status === 'scheduled') {
            $clinicalOrder->update(['status' => 'scheduled']);
        }

        $order->load(['patient', 'orderingDoctor', 'technologist']);

        return ApiResponse::success(
            new ImagingOrderResource($order),
            "Imaging order created successfully with Accession {$accession}.",
            201
        );
    }

    /**
     * Show single imaging order with files and reports.
     */
    public function show(ImagingOrder $imagingOrder): JsonResponse
    {
        $imagingOrder->load(['patient', 'orderingDoctor', 'technologist', 'reports.radiologist', 'latestReport', 'files']);

        return ApiResponse::success(
            new ImagingOrderResource($imagingOrder),
            'Imaging order details retrieved.'
        );
    }

    /**
     * Schedule procedure date/time, room/suite, and technologist.
     */
    public function schedule(ImagingOrder $imagingOrder, ScheduleImagingOrderRequest $request): JsonResponse
    {
        try {
            $scheduledAt = Carbon::parse($request->input('scheduled_at'));
            $imagingOrder->schedule(
                $scheduledAt,
                $request->input('scheduled_room'),
                $request->input('technologist_id')
            );

            return ApiResponse::success(
                new ImagingOrderResource($imagingOrder->fresh(['patient', 'orderingDoctor', 'technologist'])),
                "Imaging study scheduled for {$scheduledAt->format('Y-m-d H:i')} in {$imagingOrder->scheduled_room}."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'SCHEDULING_ERROR', [], 422);
        }
    }

    /**
     * Mark acquisition in progress.
     */
    public function start(ImagingOrder $imagingOrder, Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        $imagingOrder->markInProgress($userId);

        return ApiResponse::success(
            new ImagingOrderResource($imagingOrder->fresh(['patient', 'orderingDoctor', 'technologist'])),
            "Imaging acquisition marked in progress."
        );
    }

    /**
     * Mark acquisition completed and ready for radiologist interpretation.
     */
    public function complete(ImagingOrder $imagingOrder): JsonResponse
    {
        $imagingOrder->markCompleted();

        return ApiResponse::success(
            new ImagingOrderResource($imagingOrder->fresh(['patient', 'orderingDoctor', 'technologist'])),
            "Imaging acquisition completed. Images available for radiologist interpretation."
        );
    }
}
