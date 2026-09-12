<?php

namespace App\Domain\Laboratory\Http\Controllers;

use App\Domain\Clinical\Models\LabOrder;
use App\Domain\Laboratory\Http\Requests\StoreLabSampleRequest;
use App\Domain\Laboratory\Http\Requests\UpdateSampleStatusRequest;
use App\Domain\Laboratory\Http\Resources\LabSampleResource;
use App\Domain\Laboratory\Models\LabSample;
use App\Domain\Laboratory\Services\BarcodeService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Domain\Shared\Models\Branch;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LabSampleController extends Controller
{
    public function __construct(
        protected BarcodeService $barcodeService
    ) {
    }

    /**
     * List lab samples with filtering and status breakdown.
     */
    public function index(Request $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;

        $query = LabSample::query()
            ->with(['labOrder.orderingDoctor', 'patient', 'collector', 'receiver'])
            ->orderBy('created_at', 'desc');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        if ($request->filled('barcode')) {
            $query->where('barcode', 'ILIKE', '%' . trim($request->input('barcode')) . '%');
        }

        $samples = $query->paginate($request->input('per_page', 20));

        $transformed = LabSampleResource::collection($samples->items())->resolve();
        $samples->setCollection(collect($transformed));

        return ApiResponse::paginated($samples, 'Laboratory samples retrieved successfully.');
    }

    /**
     * Accession / Create a new sample tube label for a test order with unique barcode.
     */
    public function store(StoreLabSampleRequest $request): JsonResponse
    {
        $order = LabOrder::findOrFail($request->input('lab_order_id'));
        $barcode = $this->barcodeService->generateUniqueBarcode();

        $sample = LabSample::create([
            'id' => (string) Str::uuid(),
            'barcode' => $barcode,
            'organization_id' => $order->organization_id,
            'branch_id' => $order->branch_id,
            'lab_order_id' => $order->id,
            'patient_id' => $order->patient_id,
            'sample_type' => $request->input('sample_type'),
            'container_type' => $request->input('container_type'),
            'status' => 'pending_collection',
            'collection_site' => $request->input('collection_site'),
            'notes' => $request->input('notes'),
        ]);

        $sample->load(['labOrder', 'patient']);

        return ApiResponse::success(
            new LabSampleResource($sample),
            "Sample created with unique scannable barcode: {$barcode}",
            201
        );
    }

    /**
     * Retrieve sample details and printable label metadata.
     */
    public function show(LabSample $sample): JsonResponse
    {
        $sample->load(['labOrder.orderingDoctor', 'patient', 'collector', 'receiver', 'result']);
        $labelData = $this->barcodeService->generateLabelData($sample);

        return ApiResponse::success([
            'sample' => new LabSampleResource($sample),
            'label' => $labelData,
        ], 'Sample details retrieved successfully.');
    }

    /**
     * Fast barcode scanner lookup endpoint.
     * Scans barcode string and resolves sample, order, and patient instantly.
     */
    public function scan(string $barcode): JsonResponse
    {
        $sample = LabSample::where('barcode', trim($barcode))
            ->with(['labOrder.orderingDoctor', 'patient', 'collector', 'receiver', 'result.items'])
            ->first();

        if (!$sample) {
            return ApiResponse::error("Sample with barcode '{$barcode}' not found.", 'BARCODE_NOT_FOUND', [], 404);
        }

        return ApiResponse::success(
            new LabSampleResource($sample),
            "Sample verified via barcode scan."
        );
    }

    /**
     * Update sample workflow status (collected, received in lab, rejected).
     */
    public function updateStatus(LabSample $sample, UpdateSampleStatusRequest $request): JsonResponse
    {
        $userId = $request->user()?->id ?? $request->input('user_id');
        $status = $request->input('status');

        switch ($status) {
            case 'collected':
                $sample->markCollected($userId, $request->input('collection_site'));
                break;
            case 'received':
                $sample->markReceived($userId);
                break;
            case 'rejected':
                $sample->markRejected($userId, $request->input('rejection_reason'));
                break;
            default:
                $sample->update(['status' => $status]);
                break;
        }

        return ApiResponse::success(
            new LabSampleResource($sample->fresh(['labOrder', 'patient', 'collector', 'receiver'])),
            "Sample status updated to '{$status}'."
        );
    }
}
