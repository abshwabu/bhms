<?php

namespace App\Domain\Patient\Http\Controllers;

use App\Domain\Patient\Http\Requests\StorePatientInsuranceRequest;
use App\Domain\Patient\Http\Resources\PatientInsuranceResource;
use App\Domain\Patient\Models\Patient;
use App\Domain\Patient\Models\PatientInsurance;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PatientInsuranceController extends Controller
{
    /**
     * List all insurance policies attached to a patient.
     */
    public function index(Patient $patient): JsonResponse
    {
        $this->authorize('view', $patient);

        $insurance = $patient->insurance()
            ->with('verifier')
            ->orderBy('coverage_type', 'asc')
            ->get();

        return ApiResponse::success(
            PatientInsuranceResource::collection($insurance),
            'Patient insurance policies retrieved successfully.'
        );
    }

    /**
     * Add an insurance policy.
     */
    public function store(StorePatientInsuranceRequest $request, Patient $patient): JsonResponse
    {
        $this->authorize('update', $patient);

        $data = $request->validated();
        $insurance = PatientInsurance::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $patient->organization_id,
            'branch_id' => $patient->branch_id,
            'patient_id' => $patient->id,
            'provider_name' => $data['provider_name'],
            'policy_number' => $data['policy_number'],
            'group_number' => $data['group_number'] ?? null,
            'coverage_type' => $data['coverage_type'] ?? 'primary',
            'coverage_percentage' => $data['coverage_percentage'] ?? 100.00,
            'copay_amount_cents' => $data['copay_amount_cents'] ?? 0,
            'valid_from' => $data['valid_from'],
            'valid_until' => $data['valid_until'] ?? null,
            'pre_auth_required' => $data['pre_auth_required'] ?? false,
            'status' => $data['status'] ?? 'active',
            'notes' => $data['notes'] ?? null,
        ]);

        return ApiResponse::success(
            new PatientInsuranceResource($insurance),
            'Insurance policy added successfully.',
            201
        );
    }

    /**
     * Verify an insurance policy.
     */
    public function verify(Patient $patient, PatientInsurance $insurance): JsonResponse
    {
        $this->authorize('update', $patient);

        $insurance->update([
            'status' => 'active',
            'verified_at' => now(),
            'verified_by' => request()->user()?->id,
        ]);

        return ApiResponse::success(
            new PatientInsuranceResource($insurance),
            'Insurance policy verified successfully.'
        );
    }

    /**
     * Remove an insurance policy.
     */
    public function destroy(Patient $patient, PatientInsurance $insurance): JsonResponse
    {
        $this->authorize('update', $patient);

        if ($insurance->patient_id !== $patient->id) {
            return ApiResponse::error('Insurance record does not belong to this patient.', 'NOT_FOUND', [], 404);
        }

        $insurance->delete();

        return ApiResponse::success(null, 'Insurance policy removed successfully.');
    }
}
