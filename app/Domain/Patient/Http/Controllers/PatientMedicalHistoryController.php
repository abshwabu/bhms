<?php

namespace App\Domain\Patient\Http\Controllers;

use App\Domain\Patient\Http\Requests\StorePatientHistoryRequest;
use App\Domain\Patient\Http\Resources\PatientHistoryResource;
use App\Domain\Patient\Models\Patient;
use App\Domain\Patient\Models\PatientHistory;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PatientMedicalHistoryController extends Controller
{
    /**
     * List clinical history for a patient.
     * Enforces doctor/nurse/clinical staff authorization.
     */
    public function index(Patient $patient): JsonResponse
    {
        $this->authorize('viewMedicalHistory', $patient);

        $history = $patient->medicalHistory()
            ->with('recorder')
            ->orderBy('diagnosed_date', 'desc')
            ->get();

        return ApiResponse::success(
            PatientHistoryResource::collection($history),
            'Patient medical history retrieved successfully.'
        );
    }

    /**
     * Record a new clinical condition, past illness, or surgery.
     * Enforces clinical authorization.
     */
    public function store(StorePatientHistoryRequest $request, Patient $patient): JsonResponse
    {
        $this->authorize('modifyMedicalHistory', $patient);

        $data = $request->validated();
        $history = PatientHistory::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $patient->organization_id,
            'branch_id' => $patient->branch_id,
            'patient_id' => $patient->id,
            'category' => $data['category'],
            'condition_or_procedure' => $data['condition_or_procedure'],
            'icd10_code' => $data['icd10_code'] ?? null,
            'diagnosed_date' => $data['diagnosed_date'] ?? null,
            'status' => $data['status'] ?? 'active',
            'severity' => $data['severity'] ?? null,
            'notes' => $data['notes'] ?? null,
            'recorded_by' => $request->user()?->id,
        ]);

        return ApiResponse::success(
            new PatientHistoryResource($history),
            'Medical history recorded successfully.',
            201
        );
    }

    /**
     * Archive/delete a medical history entry.
     */
    public function destroy(Patient $patient, PatientHistory $history): JsonResponse
    {
        $this->authorize('modifyMedicalHistory', $patient);

        if ($history->patient_id !== $patient->id) {
            return ApiResponse::error('Record does not belong to this patient.', 'NOT_FOUND', [], 404);
        }

        $history->delete();

        return ApiResponse::success(null, 'Medical history entry removed successfully.');
    }
}
