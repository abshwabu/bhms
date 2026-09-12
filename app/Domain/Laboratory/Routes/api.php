<?php

use App\Domain\Laboratory\Http\Controllers\LabEquipmentController;
use App\Domain\Laboratory\Http\Controllers\LabResultController;
use App\Domain\Laboratory\Http\Controllers\LabSampleController;
use App\Domain\Laboratory\Http\Controllers\LabTestController;
use App\Domain\Shared\Http\Middleware\BranchScopeMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Laboratory Information System (LIS) Domain API Routes
|--------------------------------------------------------------------------
| All routes under /api/v1/laboratory
*/

Route::prefix('api/v1/laboratory')->middleware(['api', 'auth:sanctum', BranchScopeMiddleware::class])->group(function () {

    // 1. Diagnostic Test Catalog & Reference Ranges
    Route::prefix('tests')->group(function () {
        Route::get('/', [LabTestController::class, 'index'])->name('laboratory.tests.index');
        Route::post('/', [LabTestController::class, 'store'])->name('laboratory.tests.store');
        Route::get('/{labTest}', [LabTestController::class, 'show'])->name('laboratory.tests.show');
        Route::post('/{labTest}/reference-ranges', [LabTestController::class, 'addReferenceRange'])->name('laboratory.tests.ranges.store');
    });

    Route::get('/reference-ranges', [LabTestController::class, 'referenceRanges'])->name('laboratory.reference_ranges.index');

    // 2. Sample Management & Barcode Tracking
    Route::prefix('samples')->group(function () {
        Route::get('/', [LabSampleController::class, 'index'])->name('laboratory.samples.index');
        Route::post('/', [LabSampleController::class, 'store'])->name('laboratory.samples.store');
        Route::get('/scan/{barcode}', [LabSampleController::class, 'scan'])->name('laboratory.samples.scan');
        Route::get('/{sample}', [LabSampleController::class, 'show'])->name('laboratory.samples.show');
        Route::patch('/{sample}/status', [LabSampleController::class, 'updateStatus'])->name('laboratory.samples.update_status');
    });

    // 3. Result Entry, Auto-Flagging, Digital Signatures & Amendments
    Route::prefix('results')->group(function () {
        Route::get('/', [LabResultController::class, 'index'])->name('laboratory.results.index');
        Route::post('/', [LabResultController::class, 'store'])->name('laboratory.results.store');
        Route::get('/{labResult}', [LabResultController::class, 'show'])->name('laboratory.results.show');
        Route::post('/{labResult}/sign', [LabResultController::class, 'sign'])->name('laboratory.results.sign');
        Route::post('/{labResult}/amend', [LabResultController::class, 'amend'])->name('laboratory.results.amend');
        Route::get('/{labResult}/print', [LabResultController::class, 'print'])->name('laboratory.results.print');
    });

    // 4. Equipment & Instrument Integration Hooks (HL7 v2.x / ASTM E1394)
    Route::prefix('equipment')->group(function () {
        Route::post('/hl7', [LabEquipmentController::class, 'receiveHl7'])->name('laboratory.equipment.hl7');
        Route::post('/astm', [LabEquipmentController::class, 'receiveAstm'])->name('laboratory.equipment.astm');
    });
});
