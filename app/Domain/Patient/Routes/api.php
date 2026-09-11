<?php

use App\Domain\Patient\Http\Controllers\PatientAllergyController;
use App\Domain\Patient\Http\Controllers\PatientController;
use App\Domain\Patient\Http\Controllers\PatientInsuranceController;
use App\Domain\Patient\Http\Controllers\PatientMedicalHistoryController;
use App\Domain\Patient\Http\Controllers\PatientPortalController;
use App\Domain\Patient\Http\Controllers\PatientRelationshipController;
use App\Domain\Shared\Http\Middleware\BranchScopeMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Patient Domain API Routes
|--------------------------------------------------------------------------
| All routes under /api/v1/patients and /api/v1/patient-portal
*/

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum', BranchScopeMiddleware::class])->group(function () {

    // 1. Staff & Clinician Patient Registry Routes
    Route::prefix('patients')->group(function () {
        Route::get('/', [PatientController::class, 'index'])->name('patients.index');
        Route::post('/', [PatientController::class, 'store'])->name('patients.store');
        Route::get('/{patient}', [PatientController::class, 'show'])->name('patients.show');
        Route::put('/{patient}', [PatientController::class, 'update'])->name('patients.update');
        Route::patch('/{patient}', [PatientController::class, 'update'])->name('patients.patch');
        Route::delete('/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');

        // Clinical Medical History
        Route::prefix('{patient}/history')->group(function () {
            Route::get('/', [PatientMedicalHistoryController::class, 'index'])->name('patients.history.index');
            Route::post('/', [PatientMedicalHistoryController::class, 'store'])->name('patients.history.store');
            Route::delete('/{history}', [PatientMedicalHistoryController::class, 'destroy'])->name('patients.history.destroy');
        });

        // Known Allergies
        Route::prefix('{patient}/allergies')->group(function () {
            Route::get('/', [PatientAllergyController::class, 'index'])->name('patients.allergies.index');
            Route::post('/', [PatientAllergyController::class, 'store'])->name('patients.allergies.store');
            Route::delete('/{allergy}', [PatientAllergyController::class, 'destroy'])->name('patients.allergies.destroy');
        });

        // Insurance Coverage
        Route::prefix('{patient}/insurance')->group(function () {
            Route::get('/', [PatientInsuranceController::class, 'index'])->name('patients.insurance.index');
            Route::post('/', [PatientInsuranceController::class, 'store'])->name('patients.insurance.store');
            Route::post('/{insurance}/verify', [PatientInsuranceController::class, 'verify'])->name('patients.insurance.verify');
            Route::delete('/{insurance}', [PatientInsuranceController::class, 'destroy'])->name('patients.insurance.destroy');
        });

        // Family / Dependent Linking
        Route::prefix('{patient}/relationships')->group(function () {
            Route::get('/', [PatientRelationshipController::class, 'index'])->name('patients.relationships.index');
            Route::post('/', [PatientRelationshipController::class, 'store'])->name('patients.relationships.store');
            Route::delete('/{relationship}', [PatientRelationshipController::class, 'destroy'])->name('patients.relationships.destroy');
        });
    });

    // 2. Patient-Facing Self-Service Portal Routes
    Route::prefix('patient-portal')->group(function () {
        Route::get('/me', [PatientPortalController::class, 'profile'])->name('portal.profile');
        Route::get('/records', [PatientPortalController::class, 'records'])->name('portal.records');
        Route::get('/insurance', [PatientPortalController::class, 'insurance'])->name('portal.insurance');
        Route::get('/family', [PatientPortalController::class, 'family'])->name('portal.family');
        Route::get('/appointments', [PatientPortalController::class, 'appointments'])->name('portal.appointments');
        Route::get('/bills', [PatientPortalController::class, 'bills'])->name('portal.bills');
    });
});
