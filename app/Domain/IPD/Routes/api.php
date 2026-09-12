<?php

use App\Domain\IPD\Http\Controllers\AdmissionController;
use App\Domain\IPD\Http\Controllers\BedTransferController;
use App\Domain\IPD\Http\Controllers\DischargeController;
use App\Domain\IPD\Http\Controllers\IpdAnalyticsController;
use App\Domain\IPD\Http\Controllers\NursingController;
use App\Domain\IPD\Http\Controllers\WardBedController;
use App\Domain\Shared\Http\Middleware\BranchScopeMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| IPD (Inpatient) Domain API Routes
|--------------------------------------------------------------------------
| All routes under /api/v1/ipd
*/

Route::prefix('api/v1/ipd')->middleware(['api', 'auth:sanctum', BranchScopeMiddleware::class])->group(function () {

    // 1. Wards & Beds Management
    Route::prefix('wards')->group(function () {
        Route::get('/', [WardBedController::class, 'indexWards'])->name('ipd.wards.index');
        Route::post('/', [WardBedController::class, 'storeWard'])->name('ipd.wards.store');
    });

    Route::prefix('beds')->group(function () {
        Route::post('/', [WardBedController::class, 'storeBed'])->name('ipd.beds.store');
        Route::patch('/{bed}/status', [WardBedController::class, 'updateBedStatus'])->name('ipd.beds.update_status');
    });

    Route::get('/bed-map', [WardBedController::class, 'bedMap'])->name('ipd.bed_map');

    // 2. Admissions (ADT Workflow)
    Route::prefix('admissions')->group(function () {
        Route::get('/', [AdmissionController::class, 'index'])->name('ipd.admissions.index');
        Route::post('/', [AdmissionController::class, 'store'])->name('ipd.admissions.store');
        Route::get('/{admission}', [AdmissionController::class, 'show'])->name('ipd.admissions.show');

        // Transfers
        Route::post('/{admission}/transfer', [BedTransferController::class, 'store'])->name('ipd.admissions.transfer');

        // Discharge & Summary
        Route::post('/{admission}/discharge', [DischargeController::class, 'discharge'])->name('ipd.admissions.discharge');
        Route::get('/{admission}/discharge-summary', [DischargeController::class, 'getSummary'])->name('ipd.admissions.summary.get');
        Route::put('/{admission}/discharge-summary', [DischargeController::class, 'updateSummary'])->name('ipd.admissions.summary.update');
        Route::post('/{admission}/discharge-summary/finalize', [DischargeController::class, 'finalizeSummary'])->name('ipd.admissions.summary.finalize');

        // Nursing Station (Vitals & Medications)
        Route::post('/{admission}/vitals', [NursingController::class, 'logVitals'])->name('ipd.nursing.vitals');
        Route::post('/{admission}/medications', [NursingController::class, 'administerMedication'])->name('ipd.nursing.medications');
        Route::get('/{admission}/chart', [NursingController::class, 'chart'])->name('ipd.nursing.chart');
    });

    // 3. Bed Transfers Audit Ledger
    Route::get('/transfers', [BedTransferController::class, 'index'])->name('ipd.transfers.index');

    // 4. Inpatient Occupancy & ALOS Analytics
    Route::get('/analytics/occupancy', [IpdAnalyticsController::class, 'occupancyAnalytics'])->name('ipd.analytics.occupancy');
});
