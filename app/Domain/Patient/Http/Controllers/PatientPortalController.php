<?php

namespace App\Domain\Patient\Http\Controllers;

use App\Domain\Patient\Http\Resources\PatientAllergyResource;
use App\Domain\Patient\Http\Resources\PatientHistoryResource;
use App\Domain\Patient\Http\Resources\PatientInsuranceResource;
use App\Domain\Patient\Http\Resources\PatientRelationshipResource;
use App\Domain\Patient\Http\Resources\PatientResource;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientPortalController extends Controller
{
    /**
     * Resolve the patient record associated with the authenticated portal user.
     */
    protected function resolvePatient(Request $request): Patient
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        $patient = Patient::where('portal_user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();

        if (!$patient) {
            abort(404, 'No patient clinical profile linked to this portal user account.');
        }

        return $patient;
    }

    /**
     * Get patient's own profile.
     */
    public function profile(Request $request): JsonResponse
    {
        $patient = $this->resolvePatient($request);

        return ApiResponse::success(
            new PatientResource($patient),
            'Patient portal profile retrieved successfully.'
        );
    }

    /**
     * View clinical records (history, allergies).
     */
    public function records(Request $request): JsonResponse
    {
        $patient = $this->resolvePatient($request);

        $history = $patient->medicalHistory()->orderBy('diagnosed_date', 'desc')->get();
        $allergies = $patient->allergies()->orderBy('severity', 'desc')->get();

        return ApiResponse::success([
            'patient_id' => $patient->id,
            'mrn' => $patient->mrn,
            'blood_group' => $patient->blood_group,
            'history' => PatientHistoryResource::collection($history),
            'allergies' => PatientAllergyResource::collection($allergies),
        ], 'Patient medical records retrieved successfully.');
    }

    /**
     * View insurance policies.
     */
    public function insurance(Request $request): JsonResponse
    {
        $patient = $this->resolvePatient($request);
        $insurance = $patient->insurance()->orderBy('coverage_type', 'asc')->get();

        return ApiResponse::success(
            PatientInsuranceResource::collection($insurance),
            'Patient insurance coverage retrieved successfully.'
        );
    }

    /**
     * View family / dependent links.
     */
    public function family(Request $request): JsonResponse
    {
        $patient = $this->resolvePatient($request);
        $relationships = $patient->relationships()->with('relatedPatient')->get();

        return ApiResponse::success(
            PatientRelationshipResource::collection($relationships),
            'Family and dependent links retrieved successfully.'
        );
    }

    /**
     * Appointments contract placeholder.
     */
    public function appointments(Request $request): JsonResponse
    {
        $patient = $this->resolvePatient($request);

        return ApiResponse::success([
            'patient_id' => $patient->id,
            'appointments' => [],
            'message' => 'Connected to Appointments domain module contract.',
        ], 'Appointments retrieved successfully.');
    }

    /**
     * Bills & Invoices contract placeholder.
     */
    public function bills(Request $request): JsonResponse
    {
        $patient = $this->resolvePatient($request);

        return ApiResponse::success([
            'patient_id' => $patient->id,
            'invoices' => [],
            'balance_cents' => 0,
            'message' => 'Connected to Billing & Finance domain module contract.',
        ], 'Billing statements retrieved successfully.');
    }
}
