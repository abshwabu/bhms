<?php

namespace App\Domain\IPD\Http\Controllers;

use App\Domain\IPD\Http\Requests\AdmitPatientRequest;
use App\Domain\IPD\Http\Resources\AdmissionResource;
use App\Domain\IPD\Models\Admission;
use App\Domain\IPD\Services\AdtService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Domain\Shared\Models\Branch;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Spatie\QueryBuilder\QueryBuilder;

class AdmissionController extends Controller
{
    public function __construct(
        protected AdtService $adtService
    ) {
    }

    /**
     * List inpatient admissions.
     */
    public function index(Request $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;

        $query = QueryBuilder::for(Admission::class)
            ->where('branch_id', $branchId)
            ->with(['patient', 'ward', 'bed', 'admittingDoctor'])
            ->defaultSort('-admitted_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('ward_id')) {
            $query->where('ward_id', $request->input('ward_id'));
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        $admissions = $query->paginate($request->input('per_page', 20));

        return ApiResponse::paginated(
            $admissions,
            AdmissionResource::class,
            'Inpatient admissions retrieved successfully.'
        );
    }

    /**
     * Admit a patient into inpatient care.
     * Rejects if bed is already assigned to an active admission.
     */
    public function store(AdmitPatientRequest $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;
        $branch = Branch::findOrFail($branchId);

        try {
            $admission = $this->adtService->admitPatient(
                $request->validated(),
                $branch,
                $request->user()
            );

            return ApiResponse::success(
                new AdmissionResource($admission),
                "Patient successfully admitted under reference {$admission->admission_number}.",
                201
            );
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                'BED_OCCUPIED_OR_UNAVAILABLE',
                ['bed_id' => [$e->getMessage()]],
                422
            );
        }
    }

    /**
     * Retrieve single admission record with complete chart context.
     */
    public function show(Admission $admission): JsonResponse
    {
        $admission->load([
            'patient',
            'ward',
            'bed',
            'admittingDoctor',
            'attendingDoctor',
            'admittedByUser',
            'dischargedByUser',
            'transfers.fromWard',
            'transfers.fromBed',
            'transfers.toWard',
            'transfers.toBed',
            'transfers.transferredByUser',
            'dischargeSummary',
            'vitalsLogs.recordedByUser',
        ]);

        return ApiResponse::success(
            new AdmissionResource($admission),
            'Inpatient admission details retrieved.'
        );
    }
}
