<?php

use App\Domain\Telegram\Http\Controllers\TelegramChannelController;
use App\Domain\Telegram\Http\Controllers\TelegramReportController;
use App\Domain\Telegram\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

// Public Telegram Webhook Endpoint
Route::post('api/v1/telegram/webhook', [TelegramWebhookController::class, 'handle'])->name('telegram.webhook.v1');
Route::post('api/telegram/webhook', [TelegramWebhookController::class, 'handle'])->name('telegram.webhook');

Route::prefix('api/v1/telegram')->middleware(['api', 'feature:telegram_reporting'])->group(function () {
    // 1. Channel Registry & Configuration
    Route::get('/channels', [TelegramChannelController::class, 'index'])->name('telegram.channels.index');
    Route::post('/channels', [TelegramChannelController::class, 'store'])->name('telegram.channels.store');
    Route::get('/channels/{id}', [TelegramChannelController::class, 'show'])->name('telegram.channels.show');
    Route::put('/channels/{id}', [TelegramChannelController::class, 'update'])->name('telegram.channels.update');
    Route::delete('/channels/{id}', [TelegramChannelController::class, 'destroy'])->name('telegram.channels.destroy');
    Route::post('/channels/{id}/test', [TelegramChannelController::class, 'testMessage'])->name('telegram.channels.test');

    // 2. Report Triggers
    Route::post('/reports/daily-digest', [TelegramReportController::class, 'triggerDailyDigest'])->name('telegram.reports.daily-digest');
    Route::post('/reports/shift-handover', [TelegramReportController::class, 'triggerShiftHandover'])->name('telegram.reports.shift-handover');
    Route::post('/reports/critical-alert', [TelegramReportController::class, 'triggerCriticalAlert'])->name('telegram.reports.critical-alert');
    Route::post('/reports/simulate-command', [TelegramReportController::class, 'simulateCommand'])->name('telegram.reports.simulate-command');

    // 3. Delivery Audit Logs & Manual Retry
    Route::get('/logs', [TelegramReportController::class, 'logs'])->name('telegram.logs.index');
    Route::post('/logs/{id}/retry', [TelegramReportController::class, 'retryMessage'])->name('telegram.logs.retry');
});
