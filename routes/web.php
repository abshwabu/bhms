<?php

use App\Domain\Marketing\Http\Controllers\MarketingLandingController;
use Illuminate\Support\Facades\Route;

// Public Marketing Landing Page & Discovery
Route::get('/', [MarketingLandingController::class, 'index'])->name('landing');
Route::get('/sitemap.xml', [MarketingLandingController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [MarketingLandingController::class, 'robots'])->name('robots');

// Authenticated Hospital Application Portal
Route::get('/app', function () {
    return view('patients');
})->name('app');
