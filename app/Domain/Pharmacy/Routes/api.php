<?php

use App\Domain\Pharmacy\Http\Controllers\DispensingController;
use App\Domain\Pharmacy\Http\Controllers\DrugController;
use App\Domain\Pharmacy\Http\Controllers\StockMovementController;
use App\Domain\Shared\Http\Middleware\BranchScopeMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pharmacy Domain API Routes
|--------------------------------------------------------------------------
| All routes under /api/v1/pharmacy
*/

Route::prefix('api/v1/pharmacy')->middleware(['api', 'auth:sanctum', BranchScopeMiddleware::class])->group(function () {

    // 1. Drug Inventory, Alerts & Analytics
    Route::prefix('drugs')->group(function () {
        Route::get('/alerts/low-stock', [DrugController::class, 'lowStockAlerts'])->name('pharmacy.drugs.low_stock');
        Route::get('/alerts/expiring', [DrugController::class, 'expiringBatchesAlerts'])->name('pharmacy.drugs.expiring');
        Route::get('/metrics', [DrugController::class, 'metrics'])->name('pharmacy.drugs.metrics');
        Route::get('/', [DrugController::class, 'index'])->name('pharmacy.drugs.index');
        Route::post('/', [DrugController::class, 'store'])->name('pharmacy.drugs.store');
        Route::get('/{drug}', [DrugController::class, 'show'])->name('pharmacy.drugs.show');
        Route::match(['put', 'patch'], '/{drug}', [DrugController::class, 'update'])->name('pharmacy.drugs.update');
    });

    // 2. Prescription Dispensing Workflow & Safety
    Route::prefix('dispensing')->group(function () {
        Route::get('/queue', [DispensingController::class, 'queue'])->name('pharmacy.dispensing.queue');
        Route::get('/preview/{prescriptionId}', [DispensingController::class, 'preview'])->name('pharmacy.dispensing.preview');
        Route::post('/', [DispensingController::class, 'dispense'])->name('pharmacy.dispensing.dispense');
        Route::get('/history', [DispensingController::class, 'history'])->name('pharmacy.dispensing.history');
        Route::get('/{dispensingRecord}', [DispensingController::class, 'show'])->name('pharmacy.dispensing.show');
    });

    // 3. Stock Intake, Adjustments & Audit Movements
    Route::prefix('stock')->group(function () {
        Route::get('/movements', [StockMovementController::class, 'index'])->name('pharmacy.stock.movements');
        Route::post('/intake', [StockMovementController::class, 'intake'])->name('pharmacy.stock.intake');
        Route::post('/adjust', [StockMovementController::class, 'adjust'])->name('pharmacy.stock.adjust');
        Route::post('/quarantine', [StockMovementController::class, 'quarantine'])->name('pharmacy.stock.quarantine');
        Route::post('/write-off', [StockMovementController::class, 'writeOff'])->name('pharmacy.stock.write_off');
    });
});
