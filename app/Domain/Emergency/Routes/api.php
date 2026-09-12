<?php

use App\Domain\Emergency\Http\Controllers\AmbulanceDispatchController;
use App\Domain\Emergency\Http\Controllers\EmergencyBedAllocationController;
use App\Domain\Emergency\Http\Controllers\EmergencyCaseController;
use App\Domain\Emergency\Http\Controllers\TriageController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/emergency')->middleware(['api'])->group(function () {
    // 1. Emergency Cases & Triage Queue
    Route::get('/cases', [EmergencyCaseController::class, 'index'])->name('emergency.cases.index');
    Route::post('/cases', [EmergencyCaseController::class, 'store'])->name('emergency.cases.store');
    Route::get('/cases/{emergencyCase}', [EmergencyCaseController::class, 'show'])->name('emergency.cases.show');
    Route::match(['put', 'patch'], '/cases/{emergencyCase}', [EmergencyCaseController::class, 'update'])->name('emergency.cases.update');

    // 2. Clinical Triage Assessment
    Route::post('/cases/{emergencyCase}/triage', [TriageController::class, 'store'])->name('emergency.triage.store');
    Route::get('/cases/{emergencyCase}/triage-history', [TriageController::class, 'history'])->name('emergency.triage.history');

    // 3. Ambulance Fleet & Real-Time Tracking
    Route::get('/ambulances', [AmbulanceDispatchController::class, 'index'])->name('emergency.ambulances.index');
    Route::get('/ambulances/overview', [AmbulanceDispatchController::class, 'fleetOverview'])->name('emergency.ambulances.overview');
    Route::post('/ambulances/{ambulance}/telemetry', [AmbulanceDispatchController::class, 'recordTelemetry'])->name('emergency.ambulances.telemetry');

    // 4. Ambulance Dispatch Operations
    Route::get('/dispatches', [AmbulanceDispatchController::class, 'dispatches'])->name('emergency.dispatches.index');
    Route::post('/dispatches', [AmbulanceDispatchController::class, 'store'])->name('emergency.dispatches.store');
    Route::post('/dispatches/{dispatch}/status', [AmbulanceDispatchController::class, 'updateStatus'])->name('emergency.dispatches.status');

    // 5. Emergency Bed Allocation & Priority Overrides
    Route::get('/beds/available', [EmergencyBedAllocationController::class, 'availableBeds'])->name('emergency.beds.available');
    Route::post('/cases/{emergencyCase}/allocate-bed', [EmergencyBedAllocationController::class, 'allocate'])->name('emergency.beds.allocate');
    Route::post('/cases/{emergencyCase}/release-bed', [EmergencyBedAllocationController::class, 'release'])->name('emergency.beds.release');
});
