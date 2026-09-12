<?php

use App\Domain\Reports\Http\Controllers\CustomReportBuilderController;
use App\Domain\Reports\Http\Controllers\DashboardKpiController;
use App\Domain\Reports\Http\Controllers\DepartmentReportController;
use App\Domain\Reports\Http\Controllers\DoctorPerformanceController;
use App\Domain\Reports\Http\Controllers\ReportExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/reports')->middleware(['api'])->group(function () {
    // 1. Admin Dashboard Core KPIs (Occupancy, Revenue, Patient Flow)
    Route::get('/kpis', [DashboardKpiController::class, 'index'])->name('reports.kpis.index');
    Route::post('/kpis/refresh', [DashboardKpiController::class, 'refresh'])->name('reports.kpis.refresh');

    // 2. Department-Wise Reports
    Route::get('/departments', [DepartmentReportController::class, 'index'])->name('reports.departments.index');

    // 3. Doctor Performance Reports
    Route::get('/doctors', [DoctorPerformanceController::class, 'index'])->name('reports.doctors.index');

    // 4. Custom Report Builder
    Route::get('/builder/schema', [CustomReportBuilderController::class, 'schema'])->name('reports.builder.schema');
    Route::post('/builder/query', [CustomReportBuilderController::class, 'query'])->name('reports.builder.query');
    Route::get('/builder/saved', [CustomReportBuilderController::class, 'savedReports'])->name('reports.builder.saved');
    Route::post('/builder/saved', [CustomReportBuilderController::class, 'saveReport'])->name('reports.builder.save');

    // 5. Data Exports (Exact on-screen data match)
    Route::post('/export/custom', [ReportExportController::class, 'exportCustom'])->name('reports.export.custom');
    Route::post('/export/standard', [ReportExportController::class, 'exportStandard'])->name('reports.export.standard');
});
