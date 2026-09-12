<?php

namespace App\Domain\Laboratory\Http\Controllers;

use App\Domain\Clinical\Models\LabOrder;
use App\Domain\Laboratory\Http\Requests\AmendLabReportRequest;
use App\Domain\Laboratory\Http\Requests\SignLabReportRequest;
use App\Domain\Laboratory\Http\Requests\StoreLabResultRequest;
use App\Domain\Laboratory\Http\Resources\LabResultResource;
use App\Domain\Laboratory\Models\LabResult;
use App\Domain\Laboratory\Models\LabSample;
use App\Domain\Laboratory\Services\LabReportService;
use App\Domain\Laboratory\Services\LabResultEvaluationService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use DomainException;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LabResultController extends Controller
{
    public function __construct(
        protected LabResultEvaluationService $evaluationService,
        protected LabReportService $reportService
    ) {
    }

    /**
     * List lab results with filtering for abnormal, critical, or status states.
     */
    public function index(Request $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;

        $query = LabResult::query()
            ->with(['labOrder.orderingDoctor', 'labTest', 'sample', 'patient', 'technician', 'pathologist', 'items'])
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

        if ($request->filled('lab_order_id')) {
            $query->where('lab_order_id', $request->input('lab_order_id'));
        }

        if ($request->boolean('has_abnormal_values')) {
            $query->where('has_abnormal_values', true);
        }

        if ($request->boolean('has_critical_values')) {
            $query->where('has_critical_values', true);
        }

        $results = $query->paginate($request->input('per_page', 20));

        $transformed = LabResultResource::collection($results->items())->resolve();
        $results->setCollection(collect($transformed));

        return ApiResponse::paginated($results, 'Laboratory results retrieved successfully.');
    }

    /**
     * Enter or update diagnostic test parameter results with automatic flag evaluation.
     */
    public function store(StoreLabResultRequest $request): JsonResponse
    {
        $order = LabOrder::findOrFail($request->input('lab_order_id'));
        $userId = $request->user()?->id;

        $sample = null;
        if ($request->filled('lab_sample_id')) {
            $sample = LabSample::find($request->input('lab_sample_id'));
        } elseif ($order->samples()->exists()) {
            $sample = $order->samples()->latest()->first();
        }

        $reportNumber = 'REP-' . date('Y') . '-' . strtoupper(Str::random(6));

        // Find existing draft/preliminary or create new
        $result = LabResult::firstOrNew(
            [
                'lab_order_id' => $order->id,
                'status' => 'preliminary',
            ],
            [
                'id' => (string) Str::uuid(),
                'report_number' => $reportNumber,
                'organization_id' => $order->organization_id,
                'branch_id' => $order->branch_id,
                'patient_id' => $order->patient_id,
            ]
        );

        if (!$result->exists) {
            $result->id = (string) Str::uuid();
            $result->report_number = $reportNumber;
            $result->organization_id = $order->organization_id;
            $result->branch_id = $order->branch_id;
            $result->patient_id = $order->patient_id;
        }

        $result->lab_test_id = $request->input('lab_test_id') ?? $result->lab_test_id;
        $result->lab_sample_id = $sample?->id ?? $result->lab_sample_id;
        $result->technician_id = $userId ?? $result->technician_id;
        $result->status = $request->input('status', 'preliminary');
        $result->clinical_remarks = $request->input('clinical_remarks', $result->clinical_remarks);
        $result->methodology = $request->input('methodology', 'Automated Clinical Chemistry / Hematology Analyzer');
        $result->save();

        // Evaluate parameter items against reference ranges
        $parameters = $request->input('parameters', []);
        $this->evaluationService->evaluateAndSaveItems($result, $parameters);

        // Update sample status if present
        if ($sample && $sample->status === 'received') {
            $sample->update(['status' => 'processing']);
        }

        // Update order status
        if ($order->status === 'pending' || $order->status === 'in_progress') {
            $order->update(['status' => 'in_progress']);
        }

        $result->load(['labOrder.orderingDoctor', 'labTest', 'sample', 'patient', 'technician', 'pathologist', 'items']);

        return ApiResponse::success(
            new LabResultResource($result),
            'Laboratory result entry saved and parameter flags evaluated successfully.',
            201
        );
    }

    /**
     * Show single lab result with items and flag status.
     */
    public function show(LabResult $labResult): JsonResponse
    {
        $labResult->load(['labOrder.orderingDoctor', 'labTest', 'sample', 'patient', 'technician', 'pathologist', 'items']);

        return ApiResponse::success(
            new LabResultResource($labResult),
            'Laboratory result details retrieved successfully.'
        );
    }

    /**
     * Digitally sign and seal the laboratory report by Pathologist.
     * Enforces report immutability.
     */
    public function sign(LabResult $labResult, SignLabReportRequest $request): JsonResponse
    {
        try {
            $pathologistId = $request->input('pathologist_id') ?? $request->user()?->id;

            if (!$pathologistId) {
                return ApiResponse::error('Pathologist identification is required to digitally sign the report.', 'SIGNING_FAILED', [], 422);
            }

            if ($request->filled('clinical_remarks')) {
                $labResult->clinical_remarks = $request->input('clinical_remarks');
                $labResult->save();
            }

            $signedResult = $this->reportService->signReport($labResult, $pathologistId);

            $signedResult->load(['labOrder.orderingDoctor', 'labTest', 'sample', 'patient', 'technician', 'pathologist', 'items']);

            return ApiResponse::success(
                new LabResultResource($signedResult),
                "Laboratory report {$signedResult->report_number} successfully signed and cryptographically certified."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'REPORT_SIGNING_ERROR', [], 422);
        }
    }

    /**
     * Create an append-only versioned amendment for a signed report.
     */
    public function amend(LabResult $labResult, AmendLabReportRequest $request): JsonResponse
    {
        try {
            $userId = $request->user()?->id ?? 'system';
            $reason = $request->input('amendment_reason');
            $newItems = $request->input('parameters', []);

            $amendedResult = $this->reportService->amendReport($labResult, $newItems, $reason, $userId);

            return ApiResponse::success(
                new LabResultResource($amendedResult),
                "Amended laboratory report {$amendedResult->report_number} created successfully as Version {$amendedResult->version}.",
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'AMENDMENT_ERROR', [], 422);
        }
    }

    /**
     * Generate structured printable payload for lab report preview and printing.
     */
    public function print(LabResult $labResult): JsonResponse
    {
        $payload = $this->reportService->generatePrintPayload($labResult);

        return ApiResponse::success(
            $payload,
            'Printable laboratory report payload generated.'
        );
    }
}
