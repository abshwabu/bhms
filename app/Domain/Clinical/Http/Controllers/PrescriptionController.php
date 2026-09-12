<?php

namespace App\Domain\Clinical\Http\Controllers;

use App\Domain\Clinical\Http\Requests\CheckDrugInteractionsRequest;
use App\Domain\Clinical\Http\Requests\FinalizePrescriptionRequest;
use App\Domain\Clinical\Http\Requests\StorePrescriptionRequest;
use App\Domain\Clinical\Http\Resources\PrescriptionResource;
use App\Domain\Clinical\Models\Prescription;
use App\Domain\Clinical\Models\PrescriptionItem;
use App\Domain\Clinical\Services\DrugInteractionService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Domain\Shared\Models\Branch;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PrescriptionController extends Controller
{
    public function __construct(
        protected DrugInteractionService $interactionService
    ) {
    }

    /**
     * List prescriptions with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;

        $query = Prescription::query()
            ->with(['doctor', 'patient', 'items'])
            ->orderBy('prescribed_at', 'desc');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->input('doctor_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $prescriptions = $query->paginate($request->input('per_page', 15));

        $transformed = PrescriptionResource::collection($prescriptions->items())->resolve();
        $prescriptions->setCollection(collect($transformed));

        return ApiResponse::paginated($prescriptions, 'Prescriptions retrieved successfully.');
    }

    /**
     * Retrieve single prescription details.
     */
    public function show(Prescription $prescription): JsonResponse
    {
        $prescription->load(['doctor', 'patient', 'items', 'ehrRecord', 'overridingDoctor']);

        return ApiResponse::success(
            new PrescriptionResource($prescription),
            'Prescription retrieved successfully.'
        );
    }

    /**
     * Real-time Clinical Decision Support (CDS) drug interaction and allergy pre-check.
     */
    public function checkInteractions(CheckDrugInteractionsRequest $request): JsonResponse
    {
        $patientId = $request->input('patient_id');
        $items = $request->input('items', []);

        $alerts = $this->interactionService->check($patientId, $items);

        $hasHighSeverity = collect($alerts)->contains('severity', 'high');

        return ApiResponse::success([
            'alerts' => $alerts,
            'has_high_severity' => $hasHighSeverity,
            'can_finalize_without_override' => !$hasHighSeverity,
            'total_warnings' => count($alerts),
        ], 'Clinical decision support analysis completed.');
    }

    /**
     * Create prescription with items and clinical safety evaluation.
     */
    public function store(StorePrescriptionRequest $request): JsonResponse
    {
        $doctorId = $request->user()?->id ?? $request->input('doctor_id');
        $branchId = app()->bound('current_branch_id') ? app('current_branch_id') : null;
        $branch = Branch::findOrFail($branchId);

        $patientId = $request->input('patient_id');
        $itemsData = $request->input('items', []);
        $targetStatus = $request->input('status', 'draft');
        $overrideReason = $request->input('override_reason');

        // Evaluate Clinical Safety Alerts (Drug-Allergy & Drug-Drug)
        $safetyAlerts = $this->interactionService->check($patientId, $itemsData);
        $hasHighSeverity = collect($safetyAlerts)->contains('severity', 'high');

        // Safety Guard: Cannot finalize directly if high severity warnings exist without override reason
        if ($targetStatus === 'finalized' && $hasHighSeverity && empty(trim($overrideReason ?? ''))) {
            return ApiResponse::error(
                'Drug interaction or allergy warnings trigger before prescription can be finalized. Clinical override reason is mandatory.',
                'SAFETY_WARNINGS_DETECTED',
                [
                    'alerts' => $safetyAlerts,
                    'requires_override' => true,
                ],
                422
            );
        }

        $prescriptionNumber = 'RX-' . date('Y') . '-' . strtoupper(Str::random(6));

        $prescription = DB::transaction(function () use (
            $branch, $patientId, $doctorId, $request, $prescriptionNumber,
            $targetStatus, $safetyAlerts, $hasHighSeverity, $overrideReason, $itemsData
        ) {
            $isFinalized = ($targetStatus === 'finalized');

            $rx = Prescription::create([
                'id' => (string) Str::uuid(),
                'prescription_number' => $prescriptionNumber,
                'organization_id' => $branch->organization_id,
                'branch_id' => $branch->id,
                'patient_id' => $patientId,
                'ehr_record_id' => $request->input('ehr_record_id'),
                'appointment_id' => $request->input('appointment_id'),
                'admission_id' => $request->input('admission_id'),
                'doctor_id' => $doctorId,
                'status' => $targetStatus,
                'has_safety_warnings' => !empty($safetyAlerts),
                'safety_alerts' => $safetyAlerts,
                'override_reason' => $overrideReason,
                'overridden_by' => ($overrideReason ? $doctorId : null),
                'overridden_at' => ($overrideReason ? now() : null),
                'notes' => $request->input('notes'),
                'prescribed_at' => now(),
                'finalized_at' => ($isFinalized ? now() : null),
            ]);

            foreach ($itemsData as $item) {
                PrescriptionItem::create([
                    'id' => (string) Str::uuid(),
                    'prescription_id' => $rx->id,
                    'medication_name' => $item['medication_name'],
                    'generic_name' => $item['generic_name'] ?? null,
                    'form' => $item['form'] ?? 'tablet',
                    'dosage' => $item['dosage'],
                    'route' => $item['route'] ?? 'oral',
                    'frequency' => $item['frequency'],
                    'duration_days' => (int) $item['duration_days'],
                    'quantity' => (int) $item['quantity'],
                    'instructions' => $item['instructions'] ?? null,
                    'is_substitution_allowed' => $item['is_substitution_allowed'] ?? true,
                    'status' => 'pending',
                ]);
            }

            return $rx;
        });

        $prescription->load(['items', 'doctor', 'patient']);

        return ApiResponse::success(
            new PrescriptionResource($prescription),
            'E-Prescription created successfully.',
            201
        );
    }

    /**
     * Finalize prescription with safety alert override validation.
     */
    public function finalize(Prescription $prescription, FinalizePrescriptionRequest $request): JsonResponse
    {
        $userId = $request->user()?->id ?? $request->input('user_id', $prescription->doctor_id);
        $overrideReason = $request->input('override_reason');

        try {
            $prescription->finalize($overrideReason, $userId);

            return ApiResponse::success(
                new PrescriptionResource($prescription->fresh(['items', 'doctor', 'patient'])),
                'Prescription finalized successfully.'
            );
        } catch (DomainException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                'SAFETY_WARNINGS_DETECTED',
                [
                    'alerts' => $prescription->safety_alerts,
                    'requires_override' => $prescription->hasHighSeverityWarnings(),
                ],
                422
            );
        }
    }

    /**
     * Cancel a draft prescription.
     */
    public function destroy(Prescription $prescription): JsonResponse
    {
        if ($prescription->status === 'dispensed') {
            return ApiResponse::error('Cannot cancel already dispensed prescription.', 'CANCEL_FAILED', [], 422);
        }

        $prescription->status = 'cancelled';
        $prescription->save();
        $prescription->delete();

        return ApiResponse::success(null, 'Prescription cancelled.');
    }
}
