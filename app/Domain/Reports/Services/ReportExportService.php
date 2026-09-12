<?php

namespace App\Domain\Reports\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public function __construct(
        protected CustomReportBuilderService $builderService,
        protected StandardReportService $standardService
    ) {}

    /**
     * Export custom builder query data to CSV.
     * Acceptance criterion: Exports match on-screen data exactly.
     */
    public function exportCustomReportCsv(array $params, string $filename = 'custom-report.csv'): StreamedResponse
    {
        $result = $this->builderService->executeQuery($params, true);
        $columns = $result['columns'];
        $columnDefs = $result['column_definitions'];
        $records = $result['data'];

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($columns, $columnDefs, $records) {
            $handle = fopen('php://output', 'w');
            
            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Write CSV Header Row with human labels
            $headerLabels = array_map(function ($col) use ($columnDefs) {
                return $columnDefs[$col]['label'] ?? ucwords(str_replace('_', ' ', $col));
            }, $columns);
            fputcsv($handle, $headerLabels);

            // Write Data Rows matching exact format
            foreach ($records as $row) {
                $line = [];
                foreach ($columns as $col) {
                    $val = is_object($row) ? ($row->$col ?? '') : ($row[$col] ?? '');

                    // Format monetary values if field ends with _cents
                    if (str_ends_with($col, '_cents') && is_numeric($val)) {
                        $val = number_format($val / 100, 2);
                    } elseif (is_array($val)) {
                        $val = json_encode($val);
                    }

                    $line[] = (string) $val;
                }
                fputcsv($handle, $line);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export standard report to CSV matching on-screen structure.
     */
    public function exportStandardReportCsv(string $reportType, array $data, string $filename): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($reportType, $data) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($reportType === 'departments') {
                fputcsv($handle, ['Department Name', 'Total Patients', 'OPD Consultations', 'Admissions', 'Discharges', 'Lab Orders', 'Radiology Orders', 'Occupied Beds', 'Total Beds', 'Avg Occupancy (%)', 'Total Revenue ($)']);
                foreach ($data['departments'] ?? [] as $d) {
                    fputcsv($handle, [
                        $d['department_name'],
                        $d['total_patients'],
                        $d['opd_consultations'],
                        $d['admissions'],
                        $d['discharges'],
                        $d['lab_orders'],
                        $d['radiology_orders'],
                        $d['occupied_beds'],
                        $d['total_beds'],
                        $d['average_occupancy_rate'] . '%',
                        number_format($d['total_revenue'], 2),
                    ]);
                }
            } elseif ($reportType === 'doctors') {
                fputcsv($handle, ['Doctor Name', 'Specialty', 'Department', 'Patients Seen', 'Scheduled Appts', 'Completed Appts', 'Completion Rate (%)', 'Prescriptions', 'Lab Orders', 'Radiology Orders', 'Revenue Generated ($)']);
                foreach ($data['doctors'] ?? [] as $doc) {
                    fputcsv($handle, [
                        $doc['doctor_name'],
                        $doc['specialty'],
                        $doc['department'],
                        $doc['patients_seen'],
                        $doc['appointments_scheduled'],
                        $doc['appointments_completed'],
                        $doc['completion_rate_percentage'] . '%',
                        $doc['prescriptions_written'],
                        $doc['lab_orders_placed'],
                        $doc['radiology_orders_placed'],
                        number_format($doc['total_revenue_generated'], 2),
                    ]);
                }
            } elseif ($reportType === 'kpis') {
                fputcsv($handle, ['Metric Category', 'Metric Indicator', 'Value / Summary']);
                $kpis = $data['kpis'] ?? [];
                fputcsv($handle, ['Occupancy', 'Current Occupancy Rate', ($kpis['current_occupancy']['rate_percentage'] ?? 0) . '%']);
                fputcsv($handle, ['Occupancy', 'Occupied Beds / Total Beds', ($kpis['current_occupancy']['occupied_beds'] ?? 0) . ' / ' . ($kpis['current_occupancy']['total_beds'] ?? 0)]);
                fputcsv($handle, ['Revenue', 'Total Invoiced ($)', number_format($kpis['revenue']['total_invoiced'] ?? 0, 2)]);
                fputcsv($handle, ['Revenue', 'Total Collected ($)', number_format($kpis['revenue']['total_collected'] ?? 0, 2)]);
                fputcsv($handle, ['Revenue', 'Collection Rate (%)', ($kpis['revenue']['collection_rate_percentage'] ?? 0) . '%']);
                fputcsv($handle, ['Patient Flow', 'Total Encounters', $kpis['patient_flow']['total_encounters'] ?? 0]);
                fputcsv($handle, ['Patient Flow', 'OPD Visits', $kpis['patient_flow']['total_opd_visits'] ?? 0]);
                fputcsv($handle, ['Patient Flow', 'Inpatient Admissions', $kpis['patient_flow']['total_admissions'] ?? 0]);
                fputcsv($handle, ['Patient Flow', 'Emergency Cases', $kpis['patient_flow']['total_emergency_cases'] ?? 0]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
