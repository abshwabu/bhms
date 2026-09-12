<?php

namespace App\Domain\Clinical\Services;

use App\Domain\Clinical\Models\Diagnosis;
use App\Domain\Clinical\Models\EhrRecord;
use App\Domain\Clinical\Models\LabOrder;
use App\Domain\Clinical\Models\Prescription;
use App\Domain\Clinical\Models\RadiologyOrder;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\Branch;
use Illuminate\Support\Str;

class EhrService
{
    /**
     * Create a new EHR clinical record (draft or finalized).
     */
    public function createRecord(array $data, string $authorId, ?string $branchId = null): EhrRecord
    {
        $resolvedBranchId = $branchId ?? app('current_branch_id');
        $branch = Branch::findOrFail($resolvedBranchId);

        $status = $data['status'] ?? 'draft';
        $finalizedAt = ($status === 'finalized') ? now() : null;
        $finalizedBy = ($status === 'finalized') ? $authorId : null;

        $record = EhrRecord::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $branch->organization_id,
            'branch_id' => $branch->id,
            'patient_id' => $data['patient_id'],
            'encounter_type' => $data['encounter_type'] ?? 'opd_appointment',
            'encounter_id' => $data['encounter_id'] ?? null,
            'author_id' => $authorId,
            'record_type' => $data['record_type'] ?? 'consultation_note',
            'category' => $data['category'] ?? 'general',
            'title' => $data['title'],
            'clinical_notes' => $data['clinical_notes'] ?? [],
            'vitals' => $data['vitals'] ?? [],
            'status' => $status,
            'version' => 1,
            'is_amended' => false,
            'finalized_at' => $finalizedAt,
            'finalized_by' => $finalizedBy,
        ]);

        return $record->fresh(['author', 'finalizer']);
    }

    /**
     * Finalize an existing draft EHR record.
     */
    public function finalizeRecord(EhrRecord $record, string $userId): EhrRecord
    {
        return $record->finalize($userId);
    }

    /**
     * Amend a finalized EHR record using the append-only audit trail pattern.
     */
    public function amendRecord(EhrRecord $record, array $newData, string $amendmentReason, string $userId): EhrRecord
    {
        return $record->amend($newData, $amendmentReason, $userId);
    }

    /**
     * Build the full longitudinal clinical record timeline for a patient.
     */
    public function getPatientTimeline(string $patientId, array $filters = []): array
    {
        $patient = Patient::with(['allergies'])->findOrFail($patientId);

        // 1. EHR Clinical Records (consultations, progress notes)
        $ehrQuery = EhrRecord::where('patient_id', $patientId)
            ->with(['author', 'finalizer', 'amendedFrom'])
            ->orderBy('created_at', 'desc');

        if (!empty($filters['encounter_type'])) {
            $ehrQuery->where('encounter_type', $filters['encounter_type']);
        }
        if (!empty($filters['category'])) {
            $ehrQuery->where('category', $filters['category']);
        }

        $ehrRecords = $ehrQuery->get()->map(function ($rec) {
            return [
                'type' => 'ehr_record',
                'id' => $rec->id,
                'date' => $rec->created_at->toIso8601String(),
                'title' => $rec->title,
                'record_type' => $rec->record_type,
                'category' => $rec->category,
                'status' => $rec->status,
                'version' => $rec->version,
                'is_amended' => $rec->is_amended,
                'amended_from_id' => $rec->amended_from_id,
                'amendment_reason' => $rec->amendment_reason,
                'author' => $rec->author ? ['id' => $rec->author->id, 'name' => $rec->author->name] : null,
                'finalized_at' => $rec->finalized_at?->toIso8601String(),
                'clinical_notes' => $rec->clinical_notes,
                'vitals' => $rec->vitals,
            ];
        });

        // 2. Diagnoses (ICD-10 Coded)
        $diagnoses = Diagnosis::where('patient_id', $patientId)
            ->with('doctor')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($diag) {
                return [
                    'type' => 'diagnosis',
                    'id' => $diag->id,
                    'date' => $diag->created_at->toIso8601String(),
                    'icd10_code' => $diag->icd10_code,
                    'icd10_title' => $diag->icd10_title,
                    'diagnosis_type' => $diag->type,
                    'severity' => $diag->severity,
                    'clinical_status' => $diag->clinical_status,
                    'verification_status' => $diag->verification_status,
                    'doctor' => $diag->doctor ? ['id' => $diag->doctor->id, 'name' => $diag->doctor->name] : null,
                    'notes' => $diag->notes,
                ];
            });

        // 3. Prescriptions & Medication Orders
        $prescriptions = Prescription::where('patient_id', $patientId)
            ->with(['items', 'doctor'])
            ->orderBy('prescribed_at', 'desc')
            ->get()
            ->map(function ($rx) {
                return [
                    'type' => 'prescription',
                    'id' => $rx->id,
                    'date' => $rx->prescribed_at->toIso8601String(),
                    'prescription_number' => $rx->prescription_number,
                    'status' => $rx->status,
                    'doctor' => $rx->doctor ? ['id' => $rx->doctor->id, 'name' => $rx->doctor->name] : null,
                    'has_safety_warnings' => $rx->has_safety_warnings,
                    'safety_alerts' => $rx->safety_alerts,
                    'override_reason' => $rx->override_reason,
                    'items' => $rx->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'medication_name' => $item->medication_name,
                            'dosage' => $item->dosage,
                            'route' => $item->route,
                            'frequency' => $item->frequency,
                            'duration_days' => $item->duration_days,
                            'quantity' => $item->quantity,
                            'instructions' => $item->instructions,
                        ];
                    }),
                ];
            });

        // 4. Lab Orders & Diagnostic Results
        $labs = LabOrder::where('patient_id', $patientId)
            ->with(['orderingDoctor', 'reviewingDoctor'])
            ->orderBy('ordered_at', 'desc')
            ->get()
            ->map(function ($lab) {
                return [
                    'type' => 'lab_order',
                    'id' => $lab->id,
                    'date' => $lab->ordered_at->toIso8601String(),
                    'order_number' => $lab->order_number,
                    'test_type' => $lab->test_type,
                    'priority' => $lab->priority,
                    'status' => $lab->status,
                    'results_summary' => $lab->results_summary,
                    'structured_results' => $lab->structured_results,
                    'abnormal_flags' => $lab->abnormal_flags,
                    'ordering_doctor' => $lab->orderingDoctor ? ['id' => $lab->orderingDoctor->id, 'name' => $lab->orderingDoctor->name] : null,
                    'reviewed_by' => $lab->reviewingDoctor ? ['id' => $lab->reviewingDoctor->id, 'name' => $lab->reviewingDoctor->name] : null,
                    'reviewed_at' => $lab->reviewed_by_doctor_at?->toIso8601String(),
                ];
            });

        // 5. Radiology Orders & Imaging Reports
        $radiology = RadiologyOrder::where('patient_id', $patientId)
            ->with(['orderingDoctor', 'reviewingDoctor'])
            ->orderBy('ordered_at', 'desc')
            ->get()
            ->map(function ($rad) {
                return [
                    'type' => 'radiology_order',
                    'id' => $rad->id,
                    'date' => $rad->ordered_at->toIso8601String(),
                    'order_number' => $rad->order_number,
                    'modality' => $rad->modality,
                    'body_part' => $rad->body_part,
                    'procedure_name' => $rad->procedure_name,
                    'priority' => $rad->priority,
                    'status' => $rad->status,
                    'findings' => $rad->findings,
                    'impression' => $rad->impression,
                    'ordering_doctor' => $rad->orderingDoctor ? ['id' => $rad->orderingDoctor->id, 'name' => $rad->orderingDoctor->name] : null,
                    'reviewed_by' => $rad->reviewingDoctor ? ['id' => $rad->reviewingDoctor->id, 'name' => $rad->reviewingDoctor->name] : null,
                    'reviewed_at' => $rad->reviewed_by_doctor_at?->toIso8601String(),
                ];
            });

        // Merge all clinical events into a single sorted longitudinal timeline
        $timelineEvents = collect()
            ->merge($ehrRecords)
            ->merge($diagnoses)
            ->merge($prescriptions)
            ->merge($labs)
            ->merge($radiology)
            ->sortByDesc('date')
            ->values()
            ->all();

        return [
            'patient' => [
                'id' => $patient->id,
                'mrn' => $patient->mrn,
                'name' => trim("{$patient->first_name} {$patient->last_name}"),
                'gender' => $patient->gender,
                'date_of_birth' => $patient->date_of_birth?->format('Y-m-d'),
                'blood_group' => $patient->blood_group,
                'allergies' => $patient->allergies->map(fn($a) => [
                    'allergen' => $a->allergen,
                    'reaction' => $a->reaction,
                    'severity' => $a->severity,
                ]),
            ],
            'summary' => [
                'total_ehr_notes' => count($ehrRecords),
                'active_diagnoses_count' => $diagnoses->where('clinical_status', 'active')->count(),
                'prescriptions_count' => count($prescriptions),
                'lab_orders_count' => count($labs),
                'radiology_orders_count' => count($radiology),
            ],
            'timeline' => $timelineEvents,
        ];
    }
}
