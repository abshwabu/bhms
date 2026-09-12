<?php

namespace App\Domain\Reports\Http\Controllers;

use App\Domain\Reports\Services\ReportExportService;
use App\Domain\Reports\Services\StandardReportService;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function __construct(
        protected ReportExportService $exportService,
        protected StandardReportService $standardService
    ) {}

    /**
     * Export custom report query results to CSV.
     * Acceptance criterion: Exports match on-screen data exactly.
     */
    public function exportCustom(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'entity' => ['required', 'string'],
            'branch_id' => ['nullable', 'uuid'],
            'fields' => ['nullable', 'array'],
            'fields.*' => ['string'],
            'filters' => ['nullable', 'array'],
            'filters.*.field' => ['required', 'string'],
            'filters.*.operator' => ['required', 'string'],
            'filters.*.value' => ['nullable'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'sort_field' => ['nullable', 'string'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc,ASC,DESC'],
            'group_by' => ['nullable', 'string'],
            'aggregation' => ['nullable', 'string'],
            'aggregation_field' => ['nullable', 'string'],
            'filename' => ['nullable', 'string'],
        ]);

        if (empty($validated['branch_id'])) {
            $validated['branch_id'] = $request->header('X-Branch-ID');
        }

        $filename = ($validated['filename'] ?? 'custom-report-' . $validated['entity'] . '-' . date('Ymd')) . '.csv';

        return $this->exportService->exportCustomReportCsv($validated, $filename);
    }

    /**
     * Export standard report (KPIs, Departments, Doctors) to CSV.
     * Acceptance criterion: Exports match on-screen data exactly.
     */
    public function exportStandard(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'report_type' => ['required', 'string', 'in:kpis,departments,doctors'],
            'preset' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'branch_id' => ['nullable', 'uuid'],
        ]);

        $branchId = $validated['branch_id']
            ?: $request->header('X-Branch-ID')
            ?: '84d7387e-7b2e-4533-b3e8-139e52aecb8b';

        $preset = $validated['preset'] ?? 'last_30_days';
        $start = $validated['start_date'] ?? null;
        $end = $validated['end_date'] ?? null;
        $type = $validated['report_type'];

        $filename = "hms-{$type}-report-" . date('Ymd') . '.csv';

        if ($type === 'departments') {
            $data = $this->standardService->getDepartmentReport($branchId, $preset, $start, $end);
        } elseif ($type === 'doctors') {
            $data = $this->standardService->getDoctorPerformanceReport($branchId, $preset, $start, $end);
        } else {
            $data = $this->standardService->getAdminDashboardKpis($branchId, $preset, $start, $end);
        }

        return $this->exportService->exportStandardReportCsv($type, $data, $filename);
    }
}
