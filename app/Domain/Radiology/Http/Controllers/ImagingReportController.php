<?php

namespace App\Domain\Radiology\Http\Controllers;

use App\Domain\Radiology\Http\Requests\AmendImagingReportRequest;
use App\Domain\Radiology\Http\Requests\StoreImagingReportRequest;
use App\Domain\Radiology\Http\Resources\ImagingReportResource;
use App\Domain\Radiology\Models\ImagingOrder;
use App\Domain\Radiology\Models\ImagingReport;
use App\Domain\Radiology\Services\ImagingReportService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImagingReportController extends Controller
{
    public function __construct(
        protected ImagingReportService $reportService
    ) {
    }

    /**
     * List diagnostic imaging reports.
     */
    public function index(Request $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;

        $query = ImagingReport::query()
            ->with(['order.orderingDoctor', 'patient', 'radiologist', 'finalizedByUser', 'files'])
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

        if ($request->filled('imaging_order_id')) {
            $query->where('imaging_order_id', $request->input('imaging_order_id'));
        }

        if ($request->boolean('critical_alert')) {
            $query->where('critical_alert', true);
        }

        $reports = $query->paginate($request->input('per_page', 20));

        $transformed = ImagingReportResource::collection($reports->items())->resolve();
        $reports->setCollection(collect($transformed));

        return ApiResponse::paginated($reports, 'Diagnostic imaging reports retrieved.');
    }

    /**
     * Save draft or finalize diagnostic radiology report.
     */
    public function store(StoreImagingReportRequest $request): JsonResponse
    {
        try {
            $order = ImagingOrder::findOrFail($request->input('imaging_order_id'));
            $radiologistId = $request->input('radiologist_id') ?? ($request->user()?->id ?? $order->ordering_doctor_id);

            $report = $this->reportService->saveReport($order, $request->validated(), $radiologistId);

            $statusText = $report->status === 'finalized' ? 'finalized and digitally signed' : 'saved as draft';

            return ApiResponse::success(
                new ImagingReportResource($report),
                "Radiology report {$report->report_number} {$statusText}.",
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'REPORT_SAVE_ERROR', [], 422);
        }
    }

    /**
     * Show single radiology report.
     */
    public function show(ImagingReport $imagingReport): JsonResponse
    {
        $imagingReport->load(['order.orderingDoctor', 'patient', 'radiologist', 'finalizedByUser', 'files']);

        return ApiResponse::success(
            new ImagingReportResource($imagingReport),
            'Imaging report retrieved.'
        );
    }

    /**
     * Finalize an existing draft report with digital signature.
     */
    public function finalize(ImagingReport $imagingReport, Request $request): JsonResponse
    {
        try {
            $radiologistId = $request->input('radiologist_id') ?? ($request->user()?->id ?? $imagingReport->radiologist_id);

            $finalized = $this->reportService->finalizeReport($imagingReport, $radiologistId);

            return ApiResponse::success(
                new ImagingReportResource($finalized->load(['order', 'patient', 'radiologist', 'finalizedByUser'])),
                "Radiology report {$finalized->report_number} successfully finalized and certified."
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'FINALIZATION_ERROR', [], 422);
        }
    }

    /**
     * Create an append-only versioned amendment for a finalized report.
     */
    public function amend(ImagingReport $imagingReport, AmendImagingReportRequest $request): JsonResponse
    {
        try {
            $userId = $request->user()?->id ?? 'system';
            $reason = $request->input('amendment_reason');

            $amended = $this->reportService->amendReport($imagingReport, $request->validated(), $reason, $userId);

            if ($request->boolean('finalize')) {
                $amended->finalizeReport($userId);
            }

            return ApiResponse::success(
                new ImagingReportResource($amended->load(['order', 'patient', 'radiologist'])),
                "Amended report {$amended->report_number} (Version {$amended->version}) created successfully.",
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'AMENDMENT_ERROR', [], 422);
        }
    }

    /**
     * Generate official printable diagnostic imaging report layout data.
     */
    public function print(ImagingReport $imagingReport): JsonResponse
    {
        $payload = $this->reportService->generatePrintPayload($imagingReport);

        return ApiResponse::success(
            $payload,
            'Printable radiology report payload generated.'
        );
    }
}
