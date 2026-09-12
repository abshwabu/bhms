<?php

namespace App\Domain\Laboratory\Services;

use App\Domain\Laboratory\Models\LabResult;
use App\Domain\Laboratory\Models\LabResultItem;
use App\Domain\Shared\Models\Branch;
use DomainException;
use Illuminate\Support\Str;

class LabReportService
{
    /**
     * Digitally sign and seal the laboratory diagnostic report.
     * Enforces that signed reports are locked from further edits.
     */
    public function signReport(LabResult $result, string $pathologistId): LabResult
    {
        return $result->signReport($pathologistId);
    }

    /**
     * Create an append-only versioned amendment for an existing signed report.
     * Preserves original report immutable while creating a new version.
     */
    public function amendReport(LabResult $originalResult, array $newItems, string $amendmentReason, string $userId): LabResult
    {
        if ($originalResult->status !== 'signed') {
            throw new DomainException("Only signed laboratory reports can be amended.");
        }

        // Mark original as amended
        $originalResult->is_amended = true;
        $originalResult->status = 'amended';
        $originalResult->save();

        $newVersionNumber = $originalResult->version + 1;
        $newReportNumber = $originalResult->report_number . "-REV{$newVersionNumber}";

        $amendedResult = LabResult::create([
            'id' => (string) Str::uuid(),
            'report_number' => $newReportNumber,
            'organization_id' => $originalResult->organization_id,
            'branch_id' => $originalResult->branch_id,
            'lab_order_id' => $originalResult->lab_order_id,
            'lab_test_id' => $originalResult->lab_test_id,
            'lab_sample_id' => $originalResult->lab_sample_id,
            'patient_id' => $originalResult->patient_id,
            'technician_id' => $userId,
            'status' => 'verified',
            'has_abnormal_values' => false,
            'has_critical_values' => false,
            'version' => $newVersionNumber,
            'is_amended' => false,
            'amended_from_id' => $originalResult->id,
            'amendment_reason' => $amendmentReason,
            'clinical_remarks' => "Amended Report: {$amendmentReason}",
        ]);

        // Copy and update items for the amended version
        $evaluator = app(LabResultEvaluationService::class);
        $evaluator->evaluateAndSaveItems($amendedResult, $newItems);

        return $amendedResult->fresh(['items', 'sample', 'labTest', 'patient']);
    }

    /**
     * Generate structured data payload for official PDF/Print report layout.
     */
    public function generatePrintPayload(LabResult $result): array
    {
        $result->load(['patient', 'labOrder.orderingDoctor', 'technician', 'pathologist', 'items', 'labTest', 'sample']);
        $branch = Branch::find($result->branch_id);

        return [
            'report_number' => $result->report_number,
            'version' => $result->version,
            'is_amended' => $result->is_amended,
            'amendment_reason' => $result->amendment_reason,
            'status' => $result->status,
            'digital_signature_hash' => $result->digital_signature_hash,
            'signed_at' => $result->signed_at?->format('Y-m-d H:i'),
            'facility' => [
                'name' => $branch?->name ?? 'Metro Central Hospital',
                'department' => 'Department of Pathology & Clinical Laboratory',
                'address' => $branch?->address ?? [],
                'phone' => $branch?->phone ?? '+1-555-0100',
            ],
            'patient' => [
                'name' => $result->patient ? trim("{$result->patient->first_name} {$result->patient->last_name}") : 'Unknown',
                'mrn' => $result->patient?->mrn ?? 'N/A',
                'gender' => $result->patient?->gender ?? '',
                'dob' => $result->patient?->date_of_birth?->format('Y-m-d') ?? '',
                'age' => $result->patient?->date_of_birth ? $result->patient->date_of_birth->age : 'N/A',
            ],
            'order' => [
                'order_number' => $result->labOrder?->order_number ?? 'N/A',
                'ordered_at' => $result->labOrder?->ordered_at?->format('Y-m-d H:i') ?? '',
                'ordering_doctor' => $result->labOrder?->orderingDoctor?->name ?? 'Attending Clinician',
                'sample_barcode' => $result->sample?->barcode ?? 'N/A',
                'sample_type' => $result->sample?->sample_type ?? ($result->labTest?->specimen_type ?? 'Blood'),
                'collected_at' => $result->sample?->collected_at?->format('Y-m-d H:i') ?? 'N/A',
            ],
            'test' => [
                'name' => $result->labTest?->name ?? ($result->labOrder?->test_type ?? 'Diagnostic Test'),
                'code' => $result->labTest?->code ?? 'LAB',
                'category' => $result->labTest?->category ?? 'Clinical Pathology',
                'methodology' => $result->methodology ?? 'Automated Clinical Analyzer',
            ],
            'parameters' => $result->items->map(function (LabResultItem $item) {
                return [
                    'parameter_name' => $item->parameter_name,
                    'measured_value' => $item->measured_value,
                    'unit' => $item->unit,
                    'reference_range' => ($item->reference_low !== null && $item->reference_high !== null)
                        ? "{$item->reference_low} - {$item->reference_high}"
                        : 'Normal',
                    'flag' => $item->flag,
                    'is_abnormal' => $item->isAbnormal(),
                    'is_critical' => $item->isCritical(),
                ];
            })->all(),
            'signatories' => [
                'technician' => $result->technician?->name ?? 'Certified Medical Technologist',
                'pathologist' => $result->pathologist?->name ?? 'Consultant Pathologist, MD',
            ],
            'has_abnormal' => $result->has_abnormal_values,
            'has_critical' => $result->has_critical_values,
            'clinical_remarks' => $result->clinical_remarks,
        ];
    }
}
