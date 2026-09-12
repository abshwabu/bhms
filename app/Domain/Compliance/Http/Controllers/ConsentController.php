<?php

namespace App\Domain\Compliance\Http\Controllers;

use App\Domain\Compliance\Http\Requests\RevokeConsentRequest;
use App\Domain\Compliance\Http\Requests\StoreConsentRequest;
use App\Domain\Compliance\Models\PatientConsent;
use App\Domain\Compliance\Services\ConsentManagementService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConsentController extends Controller
{
    public function __construct(
        protected ConsentManagementService $consentService
    ) {}

    /**
     * List patient consents with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'patient_id',
            'consent_type',
            'status',
            'branch_id',
            'search',
            'per_page',
        ]);

        $consents = $this->consentService->listConsents($filters);

        return response()->json([
            'success' => true,
            'data' => $consents->items(),
            'meta' => [
                'current_page' => $consents->currentPage(),
                'last_page' => $consents->lastPage(),
                'per_page' => $consents->perPage(),
                'total' => $consents->total(),
            ],
        ]);
    }

    /**
     * Capture and store new patient consent.
     */
    public function store(StoreConsentRequest $request): JsonResponse
    {
        $consent = $this->consentService->createConsent($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Patient consent recorded successfully.',
            'data' => $consent,
        ], Response::HTTP_CREATED);
    }

    /**
     * View consent details.
     */
    public function show(string $id): JsonResponse
    {
        $consent = PatientConsent::with(['patient', 'witness', 'creator', 'branch'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $consent,
        ]);
    }

    /**
     * Revoke patient consent.
     */
    public function revoke(RevokeConsentRequest $request, string $id): JsonResponse
    {
        $consent = PatientConsent::findOrFail($id);
        $user = auth()->user();

        $revoked = $this->consentService->revokeConsent(
            $consent,
            $request->validated()['reason'],
            $user?->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Consent successfully revoked.',
            'data' => $revoked,
        ]);
    }

    /**
     * Get all consents for a specific patient.
     */
    public function getPatientConsents(string $patientId): JsonResponse
    {
        $data = $this->consentService->getPatientConsents($patientId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Verify if a patient has an active consent for a specific procedure or disclosure.
     */
    public function verify(Request $request, string $patientId): JsonResponse
    {
        $consentType = $request->query('consent_type');

        if (! $consentType) {
            return response()->json([
                'success' => false,
                'message' => 'The consent_type query parameter is required.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $verification = $this->consentService->verifyConsent($patientId, $consentType);

        return response()->json([
            'success' => true,
            'data' => $verification,
        ]);
    }
}
