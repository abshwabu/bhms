<?php

namespace App\Domain\Radiology\Services;

use App\Domain\Radiology\Models\ImagingOrder;
use App\Domain\Radiology\Models\ImagingReport;
use App\Domain\Shared\Models\Branch;
use DomainException;
use Illuminate\Support\Str;

class ImagingReportService
{
    /**
     * Save draft or update existing report.
     */
    public function saveReport(ImagingOrder $order, array $data, string $radiologistId): ImagingReport
    {
        $reportNumber = 'RAD-REP-' . date('Y') . '-' . strtoupper(Str::random(6));

        $report = ImagingReport::firstOrNew(
            [
                'imaging_order_id' => $order->id,
                'status' => 'draft',
            ],
            [
                'id' => (string) Str::uuid(),
                'report_number' => $reportNumber,
                'organization_id' => $order->organization_id,
                'branch_id' => $order->branch_id,
                'patient_id' => $order->patient_id,
            ]
        );

        if (!$report->exists) {
            $report->id = (string) Str::uuid();
            $report->report_number = $reportNumber;
            $report->organization_id = $order->organization_id;
            $report->branch_id = $order->branch_id;
            $report->patient_id = $order->patient_id;
        }

        $report->radiologist_id = $radiologistId;
        $report->clinical_indication = $data['clinical_indication'] ?? ($order->clinical_indication ?? $report->clinical_indication);
        $report->technique = $data['technique'] ?? $report->technique;
        $report->comparison = $data['comparison'] ?? $report->comparison;
        $report->findings = $data['findings'] ?? $report->findings;
        $report->impression = $data['impression'] ?? ($report->impression ?: 'Pending interpretation.');
        $report->recommendations = $data['recommendations'] ?? $report->recommendations;
        $report->critical_alert = $data['critical_alert'] ?? false;
        $report->critical_alert_communicated_at = ($data['critical_alert'] ?? false) ? ($data['critical_alert_communicated_at'] ?? now()) : null;
        $report->critical_alert_communicated_to = ($data['critical_alert'] ?? false) ? ($data['critical_alert_communicated_to'] ?? ($order->orderingDoctor?->name ?? 'Attending Clinician')) : null;

        $report->save();

        if ($data['finalize'] ?? false) {
            $report->finalizeReport($radiologistId);
        }

        return $report->fresh(['order', 'patient', 'radiologist', 'files']);
    }

    /**
     * Finalize an existing draft report.
     */
    public function finalizeReport(ImagingReport $report, string $radiologistId): ImagingReport
    {
        return $report->finalizeReport($radiologistId);
    }

    /**
     * Create an append-only versioned amendment for a finalized report.
     */
    public function amendReport(ImagingReport $report, array $data, string $reason, string $userId): ImagingReport
    {
        return $report->amend($data, $reason, $userId);
    }

    /**
     * Build official printable Diagnostic Imaging Report payload.
     */
    public function generatePrintPayload(ImagingReport $report): array
    {
        $report->load(['patient', 'order.orderingDoctor', 'order.technologist', 'radiologist', 'finalizedByUser', 'files']);
        $branch = Branch::find($report->branch_id);

        return [
            'report_number' => $report->report_number,
            'version' => $report->version,
            'is_amended' => $report->is_amended,
            'amendment_reason' => $report->amendment_reason,
            'status' => $report->status,
            'digital_signature_hash' => $report->digital_signature_hash,
            'finalized_at' => $report->finalized_at?->format('Y-m-d H:i'),
            'facility' => [
                'name' => $branch?->name ?? 'Metro Central Hospital',
                'department' => 'Department of Diagnostic & Interventional Radiology',
                'address' => $branch?->address ?? [],
                'phone' => $branch?->phone ?? '+1-555-0100',
            ],
            'patient' => [
                'name' => $report->patient ? trim("{$report->patient->first_name} {$report->patient->last_name}") : 'Unknown',
                'mrn' => $report->patient?->mrn ?? 'N/A',
                'gender' => $report->patient?->gender ?? '',
                'dob' => $report->patient?->date_of_birth?->format('Y-m-d') ?? '',
                'age' => $report->patient?->date_of_birth ? $report->patient->date_of_birth->age : 'N/A',
            ],
            'order' => [
                'accession_number' => $report->order?->accession_number ?? 'N/A',
                'modality' => $report->order?->modality ?? 'Radiology',
                'procedure_name' => $report->order?->procedure_name ?? 'Diagnostic Imaging',
                'body_part' => $report->order?->body_part ?? '',
                'priority' => $report->order?->priority ?? 'routine',
                'ordering_doctor' => $report->order?->orderingDoctor?->name ?? 'Attending Clinician',
                'technologist' => $report->order?->technologist?->name ?? 'Radiologic Technologist, RT(R)',
                'scheduled_at' => $report->order?->scheduled_at?->format('Y-m-d H:i') ?? 'N/A',
                'completed_at' => $report->order?->completed_at?->format('Y-m-d H:i') ?? 'N/A',
            ],
            'clinical_indication' => $report->clinical_indication,
            'technique' => $report->technique,
            'comparison' => $report->comparison,
            'findings' => $report->findings,
            'impression' => $report->impression,
            'recommendations' => $report->recommendations,
            'critical_alert' => $report->critical_alert,
            'critical_alert_communicated_at' => $report->critical_alert_communicated_at?->format('Y-m-d H:i'),
            'critical_alert_communicated_to' => $report->critical_alert_communicated_to,
            'radiologist' => [
                'name' => $report->radiologist?->name ?? 'Consultant Radiologist, MD',
                'qualification' => 'Board Certified Diagnostic Radiologist',
            ],
            'attached_images_count' => $report->files->count(),
        ];
    }
}
