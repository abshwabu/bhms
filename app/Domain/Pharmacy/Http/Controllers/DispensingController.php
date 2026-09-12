<?php

namespace App\Domain\Pharmacy\Http\Controllers;

use App\Domain\Clinical\Http\Resources\PrescriptionResource;
use App\Domain\Clinical\Models\Prescription;
use App\Domain\Pharmacy\Http\Resources\DispensingRecordResource;
use App\Domain\Pharmacy\Models\DispensingRecord;
use App\Domain\Pharmacy\Services\DispensingService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DispensingController extends Controller
{
    public function __construct(
        protected DispensingService $dispensingService
    ) {}

    /**
     * Prescriptions awaiting dispensing queue (finalized or partially dispensed).
     */
    public function queue(Request $request): JsonResponse
    {
        $query = Prescription::query()
            ->with(['patient.allergies', 'doctor', 'items'])
            ->whereIn('status', ['finalized', 'partial']);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('prescription_number', 'ILIKE', "%{$term}%")
                  ->orWhereHas('patient', function ($pq) use ($term) {
                      $pq->where('first_name', 'ILIKE', "%{$term}%")
                         ->orWhere('last_name', 'ILIKE', "%{$term}%")
                         ->orWhere('mrn', 'ILIKE', "%{$term}%");
                  });
            });
        }

        $prescriptions = $query->orderBy('created_at', 'asc')->get();

        return ApiResponse::success(
            PrescriptionResource::collection($prescriptions),
            'Prescription dispensing worklist retrieved.'
        );
    }

    /**
     * Preview FEFO batch allocation and secondary drug safety checks for a prescription.
     */
    public function preview(string $prescriptionId): JsonResponse
    {
        try {
            $preview = $this->dispensingService->previewDispensation($prescriptionId);

            return ApiResponse::success(
                $preview,
                'Dispensation preview calculated with FEFO allocation plan and safety checks.'
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'DISPENSING_ERROR', [], 422);
        }
    }

    /**
     * Execute dispensing: allocate FEFO batches, decrement stock, and log audit movement.
     */
    public function dispense(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prescription_id' => ['required', 'uuid', 'exists:prescriptions,id'],
            'pharmacist_notes' => ['nullable', 'string'],
            'counseling_notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.prescription_item_id' => ['required_with:items', 'uuid'],
            'items.*.drug_id' => ['nullable', 'uuid', 'exists:drugs,id'],
            'items.*.batch_id' => ['nullable', 'uuid', 'exists:drug_batches,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $pharmacistId = $request->user()?->id ?? $request->input('pharmacist_id');
        if (!$pharmacistId) {
            return ApiResponse::error('Pharmacist user is required.', 'UNAUTHENTICATED', [], 422);
        }

        try {
            $record = $this->dispensingService->dispense($validated, $pharmacistId);

            return ApiResponse::success(
                new DispensingRecordResource($record),
                "Prescription dispensed successfully under Dispensation #{$record->dispensation_number}.",
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'DISPENSING_ERROR', [], 422);
        }
    }

    /**
     * Dispensation history logs.
     */
    public function history(Request $request): JsonResponse
    {
        $query = DispensingRecord::query()
            ->with(['patient', 'pharmacist', 'prescription', 'items.drug', 'items.batch']);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('dispensation_number', 'ILIKE', "%{$term}%")
                  ->orWhereHas('patient', function ($pq) use ($term) {
                      $pq->where('first_name', 'ILIKE', "%{$term}%")
                         ->orWhere('last_name', 'ILIKE', "%{$term}%")
                         ->orWhere('mrn', 'ILIKE', "%{$term}%");
                  });
            });
        }

        $records = $query->orderBy('dispensed_at', 'desc')->get();

        return ApiResponse::success(
            DispensingRecordResource::collection($records),
            'Dispensing records retrieved successfully.'
        );
    }

    /**
     * Single dispensing record details.
     */
    public function show(DispensingRecord $dispensingRecord): JsonResponse
    {
        $dispensingRecord->load(['patient', 'pharmacist', 'prescription.doctor', 'items.drug', 'items.batch']);

        return ApiResponse::success(
            new DispensingRecordResource($dispensingRecord),
            'Dispensing record retrieved successfully.'
        );
    }
}
