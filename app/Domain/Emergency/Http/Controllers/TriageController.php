<?php

namespace App\Domain\Emergency\Http\Controllers;

use App\Domain\Emergency\Http\Resources\EmergencyCaseResource;
use App\Domain\Emergency\Http\Resources\TriageRecordResource;
use App\Domain\Emergency\Models\EmergencyCase;
use App\Domain\Emergency\Services\TriageQueueService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TriageController extends Controller
{
    public function __construct(
        protected TriageQueueService $triageService
    ) {}

    /**
     * Perform clinical triage assessment on an emergency case.
     * Acceptance criterion: Triage severity level determines queue priority automatically.
     */
    public function store(Request $request, EmergencyCase $emergencyCase): JsonResponse
    {
        $validated = $request->validate([
            'esi_level' => ['nullable', 'integer', 'min:1', 'max:5'],
            'triage_category' => ['nullable', 'string', 'in:resuscitation,cardiac,trauma,respiratory,neurological,pediatric,burns,general'],
            'vital_signs' => ['nullable', 'array'],
            'vital_signs.heart_rate' => ['nullable', 'numeric'],
            'vital_signs.bp_systolic' => ['nullable', 'numeric'],
            'vital_signs.bp_diastolic' => ['nullable', 'numeric'],
            'vital_signs.respiratory_rate' => ['nullable', 'numeric'],
            'vital_signs.spo2' => ['nullable', 'numeric'],
            'vital_signs.temperature' => ['nullable', 'numeric'],
            'vital_signs.gcs' => ['nullable', 'integer', 'min:3', 'max:15'],
            'vital_signs.pain_score' => ['nullable', 'integer', 'min:0', 'max:10'],
            'vital_signs.blood_glucose' => ['nullable', 'numeric'],
            'red_flags' => ['nullable', 'array'],
            'assessment_notes' => ['required', 'string'],
        ]);

        $triagedByUserId = $request->user()?->id ?? $emergencyCase->assigned_nurse_id ?? $emergencyCase->assigned_doctor_id;
        if (!$triagedByUserId) {
            return ApiResponse::error('Triage clinician user is required.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $triageRecord = $this->triageService->performTriage($emergencyCase, $validated, $triagedByUserId);

            return ApiResponse::success([
                'triage' => new TriageRecordResource($triageRecord),
                'case' => new EmergencyCaseResource($emergencyCase->fresh(['latestTriageRecord', 'doctor', 'nurse', 'bed.ward'])),
            ], "Triage completed. Severity classified as ESI Level {$triageRecord->esi_level} ({$triageRecord->severity_label}). Queue priority updated.", 201);
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'TRIAGE_ASSESSMENT_ERROR', [], 422);
        }
    }

    /**
     * View complete triage assessment history for a case.
     */
    public function history(EmergencyCase $emergencyCase): JsonResponse
    {
        $records = $emergencyCase->triageRecords()->with('triageNurse')->get();

        return ApiResponse::success(
            TriageRecordResource::collection($records),
            "Triage assessment history for {$emergencyCase->case_number} retrieved."
        );
    }
}
