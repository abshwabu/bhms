<?php

namespace App\Domain\IPD\Http\Controllers;

use App\Domain\IPD\Http\Requests\DischargePatientRequest;
use App\Domain\IPD\Http\Resources\AdmissionResource;
use App\Domain\IPD\Http\Resources\DischargeSummaryResource;
use App\Domain\IPD\Models\Admission;
use App\Domain\IPD\Models\DischargeSummary;
use App\Domain\IPD\Services\AdtService;
use App\Domain\IPD\Services\DischargeSummaryService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DischargeController extends Controller
{
    public function __construct(
        protected AdtService $adtService,
        protected DischargeSummaryService $dischargeService
    ) {
    }

    /**
     * Discharge patient from inpatient care.
     * Releases bed and generates auto-populated discharge summary.
     */
    public function discharge(DischargePatientRequest $request, Admission $admission): JsonResponse
    {
        try {
            $discharged = $this->adtService->dischargePatient(
                $admission,
                $request->validated(),
                $request->user()
            );

            return ApiResponse::success(
                new AdmissionResource($discharged),
                "Patient {$discharged->patient?->full_name} successfully discharged. Bed #{$discharged->bed?->bed_number} released."
            );
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                'DISCHARGE_FAILED',
                ['admission' => [$e->getMessage()]],
                422
            );
        }
    }

    /**
     * Retrieve discharge summary for an admission.
     */
    public function getSummary(Admission $admission): JsonResponse
    {
        $summary = DischargeSummary::with(['admission', 'patient', 'dischargingDoctor'])
            ->where('admission_id', $admission->id)
            ->first();

        if (!$summary) {
            // Generate auto-populated preview
            $summary = $this->dischargeService->generateSummary($admission, [], request()->user());
        }

        return ApiResponse::success(
            new DischargeSummaryResource($summary),
            'Discharge summary retrieved successfully.'
        );
    }

    /**
     * Update or complete discharge summary.
     */
    public function updateSummary(DischargePatientRequest $request, Admission $admission): JsonResponse
    {
        $summary = $this->dischargeService->generateSummary(
            $admission,
            $request->validated(),
            $request->user()
        );

        return ApiResponse::success(
            new DischargeSummaryResource($summary),
            'Discharge summary updated successfully.'
        );
    }

    /**
     * Finalize and sign discharge summary.
     */
    public function finalizeSummary(Admission $admission): JsonResponse
    {
        $summary = DischargeSummary::where('admission_id', $admission->id)->firstOrFail();

        $finalized = $this->dischargeService->finalize($summary, request()->user());

        return ApiResponse::success(
            new DischargeSummaryResource($finalized),
            'Discharge summary finalized and locked.'
        );
    }
}
