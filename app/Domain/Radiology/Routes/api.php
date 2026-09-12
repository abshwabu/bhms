<?php

use App\Domain\Radiology\Http\Controllers\ImagingFileController;
use App\Domain\Radiology\Http\Controllers\ImagingOrderController;
use App\Domain\Radiology\Http\Controllers\ImagingReportController;
use App\Domain\Shared\Http\Middleware\BranchScopeMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Radiology Information System (RIS) Domain API Routes
|--------------------------------------------------------------------------
| All routes under /api/v1/radiology
*/

Route::prefix('api/v1/radiology')->middleware(['api', 'auth:sanctum', BranchScopeMiddleware::class])->group(function () {

    // 1. Imaging Order Intake, Scheduling & Worklist
    Route::prefix('orders')->group(function () {
        Route::get('/', [ImagingOrderController::class, 'index'])->name('radiology.orders.index');
        Route::post('/', [ImagingOrderController::class, 'store'])->name('radiology.orders.store');
        Route::get('/{imagingOrder}', [ImagingOrderController::class, 'show'])->name('radiology.orders.show');
        Route::post('/{imagingOrder}/schedule', [ImagingOrderController::class, 'schedule'])->name('radiology.orders.schedule');
        Route::post('/{imagingOrder}/start', [ImagingOrderController::class, 'start'])->name('radiology.orders.start');
        Route::post('/{imagingOrder}/complete', [ImagingOrderController::class, 'complete'])->name('radiology.orders.complete');
    });

    // 2. Radiologist Diagnostic Reports (Draft, Finalize, Amendments & Printing)
    Route::prefix('reports')->group(function () {
        Route::get('/', [ImagingReportController::class, 'index'])->name('radiology.reports.index');
        Route::post('/', [ImagingReportController::class, 'store'])->name('radiology.reports.store');
        Route::get('/{imagingReport}', [ImagingReportController::class, 'show'])->name('radiology.reports.show');
        Route::post('/{imagingReport}/finalize', [ImagingReportController::class, 'finalize'])->name('radiology.reports.finalize');
        Route::post('/{imagingReport}/amend', [ImagingReportController::class, 'amend'])->name('radiology.reports.amend');
        Route::get('/{imagingReport}/print', [ImagingReportController::class, 'print'])->name('radiology.reports.print');
    });

    // 3. Image Storage, Chunked Uploads, PACS & Viewing Streams
    Route::prefix('files')->group(function () {
        Route::get('/', [ImagingFileController::class, 'index'])->name('radiology.files.index');
        Route::post('/', [ImagingFileController::class, 'store'])->name('radiology.files.store');
        Route::post('/chunk/init', [ImagingFileController::class, 'initChunkedUpload'])->name('radiology.files.chunk_init');
        Route::post('/chunk/upload', [ImagingFileController::class, 'uploadChunk'])->name('radiology.files.chunk_upload');
        Route::post('/chunk/finalize', [ImagingFileController::class, 'finalizeChunkedUpload'])->name('radiology.files.chunk_finalize');
        Route::get('/{imagingFile}/stream', [ImagingFileController::class, 'stream'])->name('radiology.files.stream');
        Route::get('/{imagingFile}/download', [ImagingFileController::class, 'download'])->name('radiology.files.download');
    });
});
