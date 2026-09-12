<?php

namespace App\Domain\Reports\Services;

use App\Domain\Reports\Models\DailyHospitalKpi;
use App\Domain\Reports\Models\DepartmentDailyMetric;
use App\Domain\Reports\Models\DoctorDailyMetric;
use Carbon\Carbon;

class StandardReportService
{
    public function __construct(
        protected KpiAggregationService $aggregationService
    ) {}

    /**
     * Resolve start and end dates from preset or custom input.
     */
    public function resolveDateRange(string $preset = 'last_30_days', ?string $customStart = null, ?string $customEnd = null): array
    {
        $today = Carbon::today();

        if ($preset === 'custom' && $customStart && $customEnd) {
            $startDate = Carbon::parse($customStart)->startOfDay();
            $endDate = Carbon::parse($customEnd)->endOfDay();
        } elseif ($preset === 'today') {
            $startDate = $today->copy()->startOfDay();
            $endDate = $today->copy()->endOfDay();
        } elseif ($preset === 'last_7_days') {
            $startDate = $today->copy()->subDays(6)->startOfDay();
            $endDate = $today->copy()->endOfDay();
        } elseif ($preset === 'this_month') {
            $startDate = $today->copy()->startOfMonth();
            $endDate = $today->copy()->endOfDay();
        } elseif ($preset === 'last_month') {
            $startDate = $today->copy()->subMonth()->startOfMonth();
            $endDate = $today->copy()->subMonth()->endOfMonth();
        } elseif ($preset === 'this_year') {
            $startDate = $today->copy()->startOfYear();
            $endDate = $today->copy()->endOfDay();
        } else { // default 'last_30_days'
            $startDate = $today->copy()->subDays(29)->startOfDay();
            $endDate = $today->copy()->endOfDay();
        }

        return [$startDate, $endDate];
    }

    /**
     * Admin Dashboard Core KPIs: Occupancy, Revenue, Patient Flow.
     * Acceptance criterion: Dashboard loads in under 2 seconds for a hospital with 100k+ records.
     */
    public function getAdminDashboardKpis(
        string $branchId,
        string $dateRangePreset = 'last_30_days',
        ?string $customStart = null,
        ?string $customEnd = null
    ): array {
        [$startDate, $endDate] = $this->resolveDateRange($dateRangePreset, $customStart, $customEnd);

        // Ensure today's metric exists in the pre-aggregated table
        $todayStr = Carbon::today()->format('Y-m-d');
        if (!DailyHospitalKpi::where('branch_id', $branchId)->where('report_date', $todayStr)->exists()) {
            $this->aggregationService->aggregateForDate($branchId, Carbon::today());
        }

        // Fast indexed range query from pre-aggregated table
        $kpiRecords = DailyHospitalKpi::query()
            ->where('branch_id', $branchId)
            ->whereBetween('report_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('report_date', 'asc')
            ->get();

        // If no records in range (e.g. fresh branch), aggregate current day
        if ($kpiRecords->isEmpty()) {
            $currentDayKpi = $this->aggregationService->aggregateForDate($branchId, Carbon::today());
            $kpiRecords = collect([$currentDayKpi]);
        }

        $latestKpi = $kpiRecords->last();

        // Aggregated totals across period
        $totalInvoicedCents = $kpiRecords->sum('total_invoiced_cents');
        $totalCollectedCents = $kpiRecords->sum('total_collected_cents');
        $totalOutstandingCents = $kpiRecords->sum('total_outstanding_cents');
        $totalOpdVisits = $kpiRecords->sum('total_opd_visits');
        $totalAdmissions = $kpiRecords->sum('total_admissions');
        $totalDischarges = $kpiRecords->sum('total_discharges');
        $totalEmergencyCases = $kpiRecords->sum('total_emergency_cases');
        $resusCases = $kpiRecords->sum('emergency_resus_cases');
        $totalPrescriptions = $kpiRecords->sum('total_prescriptions');
        $totalLabOrders = $kpiRecords->sum('total_lab_orders');
        $totalRadiologyOrders = $kpiRecords->sum('total_radiology_orders');

        $avgOccupancy = round($kpiRecords->avg('occupancy_rate_percentage') ?? 0, 1);
        $avgAlos = round($kpiRecords->avg('average_length_of_stay_days') ?? 0, 1);
        $collectionRate = ($totalInvoicedCents > 0)
            ? round(($totalCollectedCents / $totalInvoicedCents) * 100, 1)
            : 100.0;

        // Daily trend time-series for chart visualizers
        $trends = $kpiRecords->map(function ($kpi) {
            return [
                'date' => $kpi->report_date->format('Y-m-d'),
                'label' => $kpi->report_date->format('M d'),
                'occupancy_rate' => (float) $kpi->occupancy_rate_percentage,
                'total_beds' => $kpi->total_beds,
                'occupied_beds' => $kpi->occupied_beds,
                'revenue_invoiced' => round($kpi->total_invoiced_cents / 100, 2),
                'revenue_collected' => round($kpi->total_collected_cents / 100, 2),
                'opd_visits' => $kpi->total_opd_visits,
                'admissions' => $kpi->total_admissions,
                'discharges' => $kpi->total_discharges,
                'emergency_cases' => $kpi->total_emergency_cases,
            ];
        })->values();

        // Department revenue breakdown rollup
        $deptRollup = [];
        foreach ($kpiRecords as $kpi) {
            foreach ($kpi->department_breakdown ?? [] as $deptName => $data) {
                if (!isset($deptRollup[$deptName])) {
                    $deptRollup[$deptName] = ['total_cents' => 0, 'invoice_count' => 0];
                }
                $deptRollup[$deptName]['total_cents'] += ($data['total_cents'] ?? 0);
                $deptRollup[$deptName]['invoice_count'] += ($data['invoice_count'] ?? 0);
            }
        }
        $departmentRevenue = collect($deptRollup)->map(function ($val, $key) {
            return [
                'department' => $key,
                'revenue' => round($val['total_cents'] / 100, 2),
                'invoice_count' => $val['invoice_count'],
            ];
        })->sortByDesc('revenue')->values()->all();

        // Payment mode breakdown rollup
        $payRollup = [];
        foreach ($kpiRecords as $kpi) {
            foreach ($kpi->payment_mode_breakdown ?? [] as $mode => $data) {
                if (!isset($payRollup[$mode])) {
                    $payRollup[$mode] = ['total_cents' => 0, 'payment_count' => 0];
                }
                $payRollup[$mode]['total_cents'] += ($data['total_cents'] ?? 0);
                $payRollup[$mode]['payment_count'] += ($data['payment_count'] ?? 0);
            }
        }
        $paymentModeDistribution = collect($payRollup)->map(function ($val, $key) {
            return [
                'mode' => $key,
                'amount' => round($val['total_cents'] / 100, 2),
                'count' => $val['payment_count'],
            ];
        })->sortByDesc('amount')->values()->all();

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'preset' => $dateRangePreset,
                'days_count' => $startDate->diffInDays($endDate) + 1,
            ],
            'kpis' => [
                'current_occupancy' => [
                    'rate_percentage' => (float) $latestKpi->occupancy_rate_percentage,
                    'average_rate_period' => $avgOccupancy,
                    'occupied_beds' => $latestKpi->occupied_beds,
                    'total_beds' => $latestKpi->total_beds,
                    'active_inpatients' => $latestKpi->active_inpatients,
                    'average_length_of_stay_days' => $avgAlos,
                ],
                'revenue' => [
                    'total_invoiced' => round($totalInvoicedCents / 100, 2),
                    'total_collected' => round($totalCollectedCents / 100, 2),
                    'total_outstanding' => round($totalOutstandingCents / 100, 2),
                    'collection_rate_percentage' => $collectionRate,
                ],
                'patient_flow' => [
                    'total_registered_patients' => $latestKpi->total_registered_patients,
                    'total_opd_visits' => $totalOpdVisits,
                    'total_admissions' => $totalAdmissions,
                    'total_discharges' => $totalDischarges,
                    'total_emergency_cases' => $totalEmergencyCases,
                    'emergency_resus_cases' => $resusCases,
                    'total_encounters' => $totalOpdVisits + $totalAdmissions + $totalEmergencyCases,
                ],
                'clinical_throughput' => [
                    'total_prescriptions' => $totalPrescriptions,
                    'total_lab_orders' => $totalLabOrders,
                    'total_radiology_orders' => $totalRadiologyOrders,
                ],
            ],
            'trends' => $trends,
            'department_revenue' => $departmentRevenue,
            'payment_modes' => $paymentModeDistribution,
        ];
    }

    /**
     * Department-wise summary and comparison report.
     */
    public function getDepartmentReport(
        string $branchId,
        string $dateRangePreset = 'last_30_days',
        ?string $customStart = null,
        ?string $customEnd = null
    ): array {
        [$startDate, $endDate] = $this->resolveDateRange($dateRangePreset, $customStart, $customEnd);

        // Ensure today's department metrics exist
        $todayStr = Carbon::today()->format('Y-m-d');
        if (!DepartmentDailyMetric::where('branch_id', $branchId)->where('report_date', $todayStr)->exists()) {
            $this->aggregationService->aggregateDepartmentsForDate($branchId, Carbon::today());
        }

        $metrics = DepartmentDailyMetric::query()
            ->where('branch_id', $branchId)
            ->whereBetween('report_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        $departments = $metrics->groupBy('department_name')->map(function ($rows, $deptName) {
            $totalRevCents = $rows->sum('revenue_cents');
            $totalPatients = $rows->sum('patient_count');
            $opdConsults = $rows->sum('opd_consultations_count');
            $admissions = $rows->sum('admissions_count');
            $discharges = $rows->sum('discharges_count');
            $labOrders = $rows->sum('lab_orders_count');
            $radOrders = $rows->sum('radiology_orders_count');
            $avgOccupancy = round($rows->avg('occupancy_rate') ?? 0, 1);
            $avgLos = round($rows->avg('average_length_of_stay_days') ?? 0, 1);
            $latestOccupiedBeds = $rows->sortByDesc('report_date')->first()->occupied_beds ?? 0;
            $latestTotalBeds = $rows->sortByDesc('report_date')->first()->total_beds ?? 0;

            return [
                'department_name' => $deptName,
                'total_patients' => $totalPatients,
                'opd_consultations' => $opdConsults,
                'admissions' => $admissions,
                'discharges' => $discharges,
                'lab_orders' => $labOrders,
                'radiology_orders' => $radOrders,
                'occupied_beds' => $latestOccupiedBeds,
                'total_beds' => $latestTotalBeds,
                'average_occupancy_rate' => $avgOccupancy,
                'average_alos_days' => $avgLos,
                'total_revenue' => round($totalRevCents / 100, 2),
            ];
        })->sortByDesc('total_revenue')->values()->all();

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'preset' => $dateRangePreset,
            ],
            'total_departments' => count($departments),
            'departments' => $departments,
        ];
    }

    /**
     * Doctor performance and clinical activity report.
     */
    public function getDoctorPerformanceReport(
        string $branchId,
        string $dateRangePreset = 'last_30_days',
        ?string $customStart = null,
        ?string $customEnd = null
    ): array {
        [$startDate, $endDate] = $this->resolveDateRange($dateRangePreset, $customStart, $customEnd);

        // Ensure today's doctor metrics exist
        $todayStr = Carbon::today()->format('Y-m-d');
        if (!DoctorDailyMetric::where('branch_id', $branchId)->where('report_date', $todayStr)->exists()) {
            $this->aggregationService->aggregateDoctorsForDate($branchId, Carbon::today());
        }

        $metrics = DoctorDailyMetric::query()
            ->where('branch_id', $branchId)
            ->whereBetween('report_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        $doctors = $metrics->groupBy('doctor_id')->map(function ($rows, $doctorId) {
            $first = $rows->first();
            $scheduled = $rows->sum('appointments_scheduled');
            $completed = $rows->sum('appointments_completed');
            $cancelled = $rows->sum('appointments_cancelled');
            $patientsSeen = $rows->sum('patients_seen_count');
            $prescriptions = $rows->sum('prescriptions_written_count');
            $labOrders = $rows->sum('lab_orders_placed_count');
            $radOrders = $rows->sum('radiology_orders_placed_count');
            $admissions = $rows->sum('inpatient_admissions_count');
            $revCents = $rows->sum('revenue_generated_cents');
            $avgMinutes = round($rows->avg('average_consultation_minutes') ?? 15, 1);

            $completionRate = ($scheduled > 0) ? round(($completed / $scheduled) * 100, 1) : 100.0;

            return [
                'doctor_id' => $doctorId,
                'doctor_name' => $first->doctor_name,
                'specialty' => $first->specialty,
                'department' => $first->department_name,
                'patients_seen' => $patientsSeen,
                'appointments_scheduled' => $scheduled,
                'appointments_completed' => $completed,
                'appointments_cancelled' => $cancelled,
                'completion_rate_percentage' => $completionRate,
                'prescriptions_written' => $prescriptions,
                'lab_orders_placed' => $labOrders,
                'radiology_orders_placed' => $radOrders,
                'inpatient_admissions' => $admissions,
                'total_revenue_generated' => round($revCents / 100, 2),
                'average_consultation_minutes' => $avgMinutes,
            ];
        })->sortByDesc('patients_seen')->values()->all();

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'preset' => $dateRangePreset,
            ],
            'total_doctors' => count($doctors),
            'doctors' => $doctors,
        ];
    }
}
