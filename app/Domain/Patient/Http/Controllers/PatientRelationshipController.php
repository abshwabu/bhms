<?php

namespace App\Domain\Patient\Http\Controllers;

use App\Domain\Patient\Http\Requests\StorePatientRelationshipRequest;
use App\Domain\Patient\Http\Resources\PatientRelationshipResource;
use App\Domain\Patient\Models\Patient;
use App\Domain\Patient\Models\PatientRelationship;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PatientRelationshipController extends Controller
{
    /**
     * List all family links and dependents.
     */
    public function index(Patient $patient): JsonResponse
    {
        $this->authorize('view', $patient);

        $relationships = $patient->relationships()
            ->with('relatedPatient')
            ->get();

        return ApiResponse::success(
            PatientRelationshipResource::collection($relationships),
            'Patient family and dependent relationships retrieved successfully.'
        );
    }

    /**
     * Link a guardian, dependent, spouse, or child.
     */
    public function store(StorePatientRelationshipRequest $request, Patient $patient): JsonResponse
    {
        $this->authorize('update', $patient);

        $data = $request->validated();

        // Prevent linking patient to self
        if (!empty($data['related_patient_id']) && $data['related_patient_id'] === $patient->id) {
            return ApiResponse::error(
                'A patient cannot be linked as a dependent or guardian to themselves.',
                'INVALID_RELATIONSHIP',
                [],
                422
            );
        }

        $relationship = PatientRelationship::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $patient->organization_id,
            'branch_id' => $patient->branch_id,
            'patient_id' => $patient->id,
            'related_patient_id' => $data['related_patient_id'] ?? null,
            'relationship_type' => $data['relationship_type'],
            'is_guardian' => $data['is_guardian'] ?? false,
            'is_emergency_contact' => $data['is_emergency_contact'] ?? false,
            'is_billing_guarantor' => $data['is_billing_guarantor'] ?? false,
            'external_name' => $data['external_name'] ?? null,
            'external_phone' => $data['external_phone'] ?? null,
            'external_national_id' => $data['external_national_id'] ?? null,
            'external_address' => $data['external_address'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return ApiResponse::success(
            new PatientRelationshipResource($relationship->load('relatedPatient')),
            'Patient relationship linked successfully.',
            201
        );
    }

    /**
     * Remove a family link.
     */
    public function destroy(Patient $patient, PatientRelationship $relationship): JsonResponse
    {
        $this->authorize('update', $patient);

        if ($relationship->patient_id !== $patient->id) {
            return ApiResponse::error('Relationship record does not belong to this patient.', 'NOT_FOUND', [], 404);
        }

        $relationship->delete();

        return ApiResponse::success(null, 'Relationship unlinked successfully.');
    }
}
