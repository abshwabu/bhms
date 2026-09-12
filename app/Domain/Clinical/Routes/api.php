<?php

use App\Domain\Clinical\Http\Controllers\DiagnosisController;
use App\Domain\Clinical\Http\Controllers\DoctorDashboardController;
use App\Domain\Clinical\Http\Controllers\EhrController;
use App\Domain\Clinical\Http\Controllers\LabOrderController;
use App\Domain\Clinical\Http\Controllers\PrescriptionController;
use App\Domain\Clinical\Http\Controllers\RadiologyOrderController;
use App\Domain\Shared\Http\Middleware\BranchScopeMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Doctor & Clinical Domain API Routes
|--------------------------------------------------------------------------
| All routes under /api/v1/clinical
*/

Route::prefix('api/v1/clinical')->middleware(['api', 'auth:sanctum', BranchScopeMiddleware::class])->group(function () {

    // 1. Doctor's Personal Clinical Dashboard
    Route::get('/doctor/dashboard', [DoctorDashboardController::class, 'dashboard'])->name('clinical.doctor.dashboard');

    // 2. Electronic Health Records (EHR) & Longitudinal Timeline
    Route::prefix('ehr')->group(function () {
        Route::get('/', [EhrController::class, 'index'])->name('clinical.ehr.index');
        Route::post('/', [EhrController::class, 'store'])->name('clinical.ehr.store');
        Route::get('/{record}', [EhrController::class, 'show'])->name('clinical.ehr.show');
        Route::post('/{record}/finalize', [EhrController::class, 'finalize'])->name('clinical.ehr.finalize');
        Route::post('/{record}/amend', [EhrController::class, 'amend'])->name('clinical.ehr.amend');
    });

    Route::get('/patients/{patient}/ehr-timeline', [EhrController::class, 'patientTimeline'])->name('clinical.ehr.patient_timeline');

    // 3. Clinical Diagnoses & Standard ICD-10 Coding
    Route::prefix('diagnoses')->group(function () {
        Route::get('/', [DiagnosisController::class, 'index'])->name('clinical.diagnoses.index');
        Route::post('/', [DiagnosisController::class, 'store'])->name('clinical.diagnoses.store');
        Route::put('/{diagnosis}', [DiagnosisController::class, 'update'])->name('clinical.diagnoses.update');
        Route::delete('/{diagnosis}', [DiagnosisController::class, 'destroy'])->name('clinical.diagnoses.destroy');
    });

    Route::get('/icd10', [DiagnosisController::class, 'searchIcd10'])->name('clinical.icd10.search');
    Route::post('/icd10/validate', [DiagnosisController::class, 'validateIcd10'])->name('clinical.icd10.validate');

    // 4. E-Prescriptions & Clinical Decision Support (CDS)
    Route::prefix('prescriptions')->group(function () {
        Route::get('/', [PrescriptionController::class, 'index'])->name('clinical.prescriptions.index');
        Route::post('/', [PrescriptionController::class, 'store'])->name('clinical.prescriptions.store');
        Route::post('/check-interactions', [PrescriptionController::class, 'checkInteractions'])->name('clinical.prescriptions.check_interactions');
        Route::get('/{prescription}', [PrescriptionController::class, 'show'])->name('clinical.prescriptions.show');
        Route::post('/{prescription}/finalize', [PrescriptionController::class, 'finalize'])->name('clinical.prescriptions.finalize');
        Route::delete('/{prescription}', [PrescriptionController::class, 'destroy'])->name('clinical.prescriptions.destroy');
    });

    // 5. Diagnostic Order Entry: Laboratory
    Route::prefix('lab-orders')->group(function () {
        Route::get('/', [LabOrderController::class, 'index'])->name('clinical.lab_orders.index');
        Route::post('/', [LabOrderController::class, 'store'])->name('clinical.lab_orders.store');
        Route::get('/{labOrder}', [LabOrderController::class, 'show'])->name('clinical.lab_orders.show');
        Route::patch('/{labOrder}/results', [LabOrderController::class, 'updateResults'])->name('clinical.lab_orders.results');
        Route::post('/{labOrder}/review', [LabOrderController::class, 'review'])->name('clinical.lab_orders.review');
    });

    // 6. Diagnostic Order Entry: Radiology
    Route::prefix('radiology-orders')->group(function () {
        Route::get('/', [RadiologyOrderController::class, 'index'])->name('clinical.radiology_orders.index');
        Route::post('/', [RadiologyOrderController::class, 'store'])->name('clinical.radiology_orders.store');
        Route::get('/{radiologyOrder}', [RadiologyOrderController::class, 'show'])->name('clinical.radiology_orders.show');
        Route::patch('/{radiologyOrder}/report', [RadiologyOrderController::class, 'updateReport'])->name('clinical.radiology_orders.report');
        Route::post('/{radiologyOrder}/review', [RadiologyOrderController::class, 'review'])->name('clinical.radiology_orders.review');
    });
});
