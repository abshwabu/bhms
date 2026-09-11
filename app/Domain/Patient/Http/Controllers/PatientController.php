<?php

namespace App\Domain\Patient\Http\Controllers;

use App\Domain\Patient\Actions\RegisterPatientAction;
use App\Domain\Patient\Actions\UpdatePatientAction;
use App\Domain\Patient\Http\Requests\RegisterPatientRequest;
use App\Domain\Patient\Http\Requests\UpdatePatientRequest;
use App\Domain\Patient\Http\Resources\PatientResource;
use App\Domain\Patient\Models\Patient;
use App\Domain\Patient\Services\PatientSearchService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Domain\Shared\Models\Branch;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function __construct(
        protected PatientSearchService $searchService,
        protected RegisterPatientAction $registerAction,
        protected UpdatePatientAction $updateAction
    ) {
    }

    /**
     * Search and list patients.
     * Accessible by authorized hospital staff.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Patient::class);

        $patients = $this->searchService->search($request->all());

        // Transform paginated items via PatientResource
        $transformedItems = PatientResource::collection($patients->items())->resolve();
        $patients->setCollection(collect($transformedItems));

        return ApiResponse::paginated($patients, 'Patients retrieved successfully.');
    }

    /**
     * Register a new patient (walk-in, referral, or emergency intake).
     */
    public function store(RegisterPatientRequest $request): JsonResponse
    {
        $this->authorize('create', Patient::class);

        // Branch is determined by active scope or header
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;
        $branch = Branch::findOrFail($branchId);

        $patient = $this->registerAction->execute(
            $request->validated(),
            $branch,
            $request->user()?->id
        );

        return ApiResponse::success(
            new PatientResource($patient),
            'Patient registered successfully with unique MRN: ' . $patient->mrn,
            201
        );
    }

    /**
     * Retrieve single patient profile.
     */
    public function show(Patient $patient): JsonResponse
    {
        $this->authorize('view', $patient);

        $patient->load([
            'insurance',
            'relationships.relatedPatient',
            'medicalHistory.recorder',
            'allergies.recorder',
        ]);

        return ApiResponse::success(
            new PatientResource($patient),
            'Patient profile retrieved successfully.'
        );
    }

    /**
     * Update patient demographics and PII.
     */
    public function update(UpdatePatientRequest $request, Patient $patient): JsonResponse
    {
        $this->authorize('update', $patient);

        $updatedPatient = $this->updateAction->execute(
            $patient,
            $request->validated(),
            $request->user()?->id
        );

        return ApiResponse::success(
            new PatientResource($updatedPatient),
            'Patient details updated successfully.'
        );
    }

    /**
     * Soft delete patient.
     */
    public function destroy(Patient $patient): JsonResponse
    {
        $this->authorize('delete', $patient);

        $patient->delete();

        return ApiResponse::success(
            null,
            'Patient record archived successfully.',
            200
        );
    }
}
