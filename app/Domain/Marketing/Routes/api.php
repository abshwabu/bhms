<?php

use App\Domain\Marketing\Http\Controllers\MarketingLandingController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api'])->group(function () {
    Route::post('/leads', [MarketingLandingController::class, 'storeLead'])->name('leads.store');
    Route::get('/leads', [MarketingLandingController::class, 'listLeads'])->name('leads.index');
});
