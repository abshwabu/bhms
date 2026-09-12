<?php

namespace App\Domain\IPD\Http\Controllers;

use App\Domain\IPD\Http\Requests\AdministerMedicationRequest;
use App\Domain\IPD\Http\Requests\LogVitalsRequest;
use App\Domain\IPD\Http\Resources\VitalsLogResource;
use App\Domain\IPD\Models\Admission;
use App\Domain\IPD\Services\NursingStationService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class NursingController extends Controller
{
    public function __construct(
        protected NursingStationService $nursingService
    ) {
    }

    /**
     * Record inpatient vitals entry during nursing rounds.
     */
    public function logVitals(LogVitalsRequest $request, Admission $admission): JsonResponse
    {
        $vitals = $this->nursingService->logVitals(
            $admission,
            $request->validated(),
            $request->user()
        );

        return ApiResponse::success(
            new VitalsLogResource($vitals->load('recordedByUser')),
            'Patient vitals recorded successfully.',
            201
        );
    }

    /**
     * Record a medication administration event during nursing rounds.
     */
    public function administerMedication(AdministerMedicationRequest $request, Admission $admission): JsonResponse
    {
        $med = $this->nursingService->recordMedication(
            $admission,
            $request->validated(),
            $request->user()
        );

        return ApiResponse::success(
            $med->load('administeredByUser'),
            "Medication '{$med->medication_name}' administration recorded.",
            201
        );
    }

    /**
     * Retrieve complete nursing chart (vitals trends & medication history).
     */
    public function chart(Admission $admission): JsonResponse
    {
        $chart = $this->nursingService->getInpatientChart($admission);

        return ApiResponse::success(
            $chart,
            'Inpatient nursing chart retrieved successfully.'
        );
    }
}
