<?php

namespace App\Domain\Clinical\Http\Controllers;

use App\Domain\Clinical\Http\Requests\AmendEhrRecordRequest;
use App\Domain\Clinical\Http\Requests\StoreEhrRecordRequest;
use App\Domain\Clinical\Http\Resources\EhrRecordResource;
use App\Domain\Clinical\Models\EhrRecord;
use App\Domain\Clinical\Services\EhrService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EhrController extends Controller
{
    public function __construct(
        protected EhrService $ehrService
    ) {
    }

    /**
     * List EHR records with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;

        $query = EhrRecord::query()
            ->with(['author', 'finalizer', 'patient'])
            ->orderBy('created_at', 'desc');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        if ($request->filled('encounter_type')) {
            $query->where('encounter_type', $request->input('encounter_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('author_id')) {
            $query->where('author_id', $request->input('author_id'));
        }

        $records = $query->paginate($request->input('per_page', 15));

        $transformed = EhrRecordResource::collection($records->items())->resolve();
        $records->setCollection(collect($transformed));

        return ApiResponse::paginated($records, 'EHR records retrieved successfully.');
    }

    /**
     * Retrieve single EHR record with version history chain.
     */
    public function show(EhrRecord $record): JsonResponse
    {
        $record->load(['author', 'finalizer', 'patient', 'amendedFrom', 'amendments', 'diagnoses', 'prescriptions.items']);

        return ApiResponse::success(
            new EhrRecordResource($record),
            'EHR record retrieved successfully.'
        );
    }

    /**
     * Create a new EHR record (consultation note, progress note, SOAP assessment).
     */
    public function store(StoreEhrRecordRequest $request): JsonResponse
    {
        $authorId = $request->user()?->id ?? $request->input('author_id');
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;

        $record = $this->ehrService->createRecord(
            $request->validated(),
            $authorId,
            $branchId
        );

        return ApiResponse::success(
            new EhrRecordResource($record),
            'EHR clinical record created successfully.',
            201
        );
    }

    /**
     * Finalize a draft EHR record.
     */
    public function finalize(EhrRecord $record, Request $request): JsonResponse
    {
        $userId = $request->user()?->id ?? $request->input('user_id');

        try {
            $finalized = $this->ehrService->finalizeRecord($record, $userId);

            return ApiResponse::success(
                new EhrRecordResource($finalized->fresh(['author', 'finalizer'])),
                'EHR record finalized and locked against direct edits.'
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'EHR_FINALIZATION_ERROR', [], 422);
        }
    }

    /**
     * Amend a finalized EHR record.
     * Preserves the original record immutable and creates an append-only new version.
     */
    public function amend(EhrRecord $record, AmendEhrRecordRequest $request): JsonResponse
    {
        $userId = $request->user()?->id ?? $request->input('user_id');

        try {
            $amendedVersion = $this->ehrService->amendRecord(
                $record,
                $request->validated(),
                $request->input('amendment_reason'),
                $userId
            );

            return ApiResponse::success(
                new EhrRecordResource($amendedVersion->fresh(['author', 'finalizer', 'amendedFrom'])),
                'EHR record successfully amended. New version recorded in immutable audit ledger.',
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'EHR_AMENDMENT_ERROR', [], 422);
        }
    }

    /**
     * Retrieve full longitudinal EHR timeline for a patient.
     */
    public function patientTimeline(string $patientId, Request $request): JsonResponse
    {
        $timelineData = $this->ehrService->getPatientTimeline($patientId, $request->all());

        return ApiResponse::success(
            $timelineData,
            'Longitudinal patient clinical timeline retrieved successfully.'
        );
    }
}
