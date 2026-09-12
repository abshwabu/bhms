<?php

namespace App\Domain\Emergency\Http\Controllers;

use App\Domain\Emergency\Http\Resources\EmergencyCaseResource;
use App\Domain\Emergency\Models\EmergencyCase;
use App\Domain\Emergency\Services\TriageQueueService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmergencyCaseController extends Controller
{
    public function __construct(
        protected TriageQueueService $triageService
    ) {}

    /**
     * Active emergency triage queue.
     * Acceptance criterion: Triage severity level determines queue priority automatically.
     */
    public function index(Request $request): JsonResponse
    {
        $query = EmergencyCase::query()
            ->with([
                'patient',
                'latestTriageRecord.triageNurse',
                'bed.ward',
                'doctor',
                'nurse',
                'ambulanceDispatch.ambulance',
            ]);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        } else {
            // Default to active ER cases
            $query->whereNotIn('status', ['discharged', 'transferred', 'deceased']);
        }

        if ($request->filled('esi_level')) {
            $query->where('current_esi_level', (int) $request->input('esi_level'));
        }

        // Automated Queue Sorting: ESI 1 first, then 2, 3, 4, 5, followed by arrival time
        $cases = $query
            ->orderBy('current_esi_level', 'asc')
            ->orderBy('priority_score', 'desc')
            ->orderBy('arrival_datetime', 'asc')
            ->paginate($request->input('per_page', 50));

        return ApiResponse::paginated(
            $cases->through(fn($c) => new EmergencyCaseResource($c)),
            'Emergency triage queue retrieved.'
        );
    }

    /**
     * Case intake registration.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'patient_id' => ['nullable', 'uuid', 'exists:patients,id'],
            'patient_temp_name' => ['nullable', 'string', 'max:100'],
            'patient_gender' => ['nullable', 'string', 'in:male,female,other,unknown'],
            'patient_estimated_age' => ['nullable', 'integer', 'min:0', 'max:130'],
            'arrival_mode' => ['nullable', 'string', 'in:ambulance,walk_in,helicopter,police'],
            'ambulance_dispatch_id' => ['nullable', 'uuid', 'exists:ambulance_dispatches,id'],
            'arrival_datetime' => ['nullable', 'date'],
            'chief_complaint' => ['required', 'string'],
            'initial_triage_esi' => ['nullable', 'integer', 'min:1', 'max:5'],
            'assigned_doctor_id' => ['nullable', 'uuid', 'exists:users,id'],
            'assigned_nurse_id' => ['nullable', 'uuid', 'exists:users,id'],
        ]);

        $userId = $request->user()?->id;

        try {
            $case = $this->triageService->createEmergencyCase($validated, $userId);

            return ApiResponse::success(
                new EmergencyCaseResource($case->load(['patient', 'doctor', 'nurse', 'bed.ward'])),
                "Emergency case {$case->case_number} registered successfully.",
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'EMERGENCY_CASE_ERROR', [], 422);
        }
    }

    /**
     * View single emergency case details.
     */
    public function show(EmergencyCase $emergencyCase): JsonResponse
    {
        $emergencyCase->load([
            'patient',
            'latestTriageRecord.triageNurse',
            'triageRecords.triageNurse',
            'bed.ward',
            'bedAllocations.allocatedByUser',
            'bedAllocations.bed.ward',
            'ambulanceDispatch.ambulance',
            'doctor',
            'nurse',
        ]);

        return ApiResponse::success(
            new EmergencyCaseResource($emergencyCase),
            "Emergency case {$emergencyCase->case_number} details retrieved."
        );
    }

    /**
     * Update case status, assign doctor, or record clinical disposition.
     */
    public function update(Request $request, EmergencyCase $emergencyCase): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:registered,triaged,in_treatment,bed_assigned,admitted_ipd,discharged,transferred,deceased'],
            'assigned_doctor_id' => ['nullable', 'uuid', 'exists:users,id'],
            'assigned_nurse_id' => ['nullable', 'uuid', 'exists:users,id'],
            'chief_complaint' => ['nullable', 'string'],
            'disposition' => ['nullable', 'string', 'in:admit_ipd,discharge_home,transfer_tertiary,ama,morgue'],
            'disposition_notes' => ['nullable', 'string'],
        ]);

        if (!empty($validated['disposition']) && empty($emergencyCase->disposition_at)) {
            $validated['disposition_at'] = Carbon::now();
        }

        $emergencyCase->update($validated);

        return ApiResponse::success(
            new EmergencyCaseResource($emergencyCase->load(['patient', 'doctor', 'nurse', 'bed.ward'])),
            "Emergency case {$emergencyCase->case_number} updated."
        );
    }
}
