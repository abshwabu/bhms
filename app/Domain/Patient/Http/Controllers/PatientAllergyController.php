<?php

namespace App\Domain\Patient\Http\Controllers;

use App\Domain\Patient\Http\Requests\StorePatientAllergyRequest;
use App\Domain\Patient\Http\Resources\PatientAllergyResource;
use App\Domain\Patient\Models\Patient;
use App\Domain\Patient\Models\PatientAllergy;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PatientAllergyController extends Controller
{
    /**
     * List known patient allergies.
     * Enforces clinical authorization.
     */
    public function index(Patient $patient): JsonResponse
    {
        $this->authorize('viewMedicalHistory', $patient);

        $allergies = $patient->allergies()
            ->with('recorder')
            ->orderBy('severity', 'desc')
            ->get();

        return ApiResponse::success(
            PatientAllergyResource::collection($allergies),
            'Patient allergies retrieved successfully.'
        );
    }

    /**
     * Record a new allergy.
     * Enforces clinical authorization.
     */
    public function store(StorePatientAllergyRequest $request, Patient $patient): JsonResponse
    {
        $this->authorize('modifyMedicalHistory', $patient);

        $data = $request->validated();
        $allergy = PatientAllergy::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $patient->organization_id,
            'branch_id' => $patient->branch_id,
            'patient_id' => $patient->id,
            'allergen' => $data['allergen'],
            'allergen_type' => $data['allergen_type'],
            'reaction' => $data['reaction'],
            'severity' => $data['severity'],
            'status' => $data['status'] ?? 'active',
            'diagnosed_at' => $data['diagnosed_at'] ?? now()->toDateString(),
            'notes' => $data['notes'] ?? null,
            'recorded_by' => $request->user()?->id,
        ]);

        return ApiResponse::success(
            new PatientAllergyResource($allergy),
            'Allergy recorded successfully.',
            201
        );
    }

    /**
     * Archive/delete an allergy record.
     */
    public function destroy(Patient $patient, PatientAllergy $allergy): JsonResponse
    {
        $this->authorize('modifyMedicalHistory', $patient);

        if ($allergy->patient_id !== $patient->id) {
            return ApiResponse::error('Allergy record does not belong to this patient.', 'NOT_FOUND', [], 404);
        }

        $allergy->delete();

        return ApiResponse::success(null, 'Allergy record removed successfully.');
    }
}
