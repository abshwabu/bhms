<?php

use App\Domain\SuperAdmin\Http\Controllers\SuperAdminAnnouncementController;
use App\Domain\SuperAdmin\Http\Controllers\SuperAdminAuditLogController;
use App\Domain\SuperAdmin\Http\Controllers\SuperAdminFeatureFlagController;
use App\Domain\SuperAdmin\Http\Controllers\SuperAdminImpersonationController;
use App\Domain\SuperAdmin\Http\Controllers\SuperAdminSubscriptionController;
use App\Domain\SuperAdmin\Http\Controllers\SuperAdminSupportTicketController;
use App\Domain\SuperAdmin\Http\Controllers\SuperAdminSystemHealthController;
use App\Domain\SuperAdmin\Http\Controllers\SuperAdminTenantController;
use Illuminate\Support\Facades\Route;

// Tenant broadcast feed (all authenticated hospital users can check for platform announcements)
Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::get('/announcements/active', [SuperAdminAnnouncementController::class, 'activeAnnouncements'])->name('announcements.active');
});

// Strictly Protected Super Admin Operator Control Plane
Route::prefix('api/v1/super-admin')
    ->middleware(['api', 'auth:sanctum', 'super_admin'])
    ->group(function () {
        // 1. Tenant / Hospital Client Management
        Route::get('/tenants', [SuperAdminTenantController::class, 'index'])->name('superadmin.tenants.index');
        Route::post('/tenants', [SuperAdminTenantController::class, 'store'])->name('superadmin.tenants.store');
        Route::get('/tenants/{id}', [SuperAdminTenantController::class, 'show'])->name('superadmin.tenants.show');
        Route::post('/tenants/{id}/suspend', [SuperAdminTenantController::class, 'suspend'])->name('superadmin.tenants.suspend');
        Route::post('/tenants/{id}/reactivate', [SuperAdminTenantController::class, 'reactivate'])->name('superadmin.tenants.reactivate');

        // 2. Feature Flags & Module Matrix Control
        Route::get('/feature-flags', [SuperAdminFeatureFlagController::class, 'index'])->name('superadmin.feature-flags.index');
        Route::get('/tenants/{id}/feature-flags', [SuperAdminFeatureFlagController::class, 'getTenantFlags'])->name('superadmin.tenants.flags');
        Route::post('/tenants/{id}/feature-flags', [SuperAdminFeatureFlagController::class, 'setTenantFlag'])->name('superadmin.tenants.flags.set');
        Route::post('/feature-flags/{id}/toggle-global', [SuperAdminFeatureFlagController::class, 'toggleGlobal'])->name('superadmin.feature-flags.toggle-global');

        // 3. Subscription & Billing Oversight
        Route::get('/subscriptions', [SuperAdminSubscriptionController::class, 'index'])->name('superadmin.subscriptions.index');
        Route::put('/subscriptions/{id}', [SuperAdminSubscriptionController::class, 'update'])->name('superadmin.subscriptions.update');

        // 4. Global Support User Impersonation
        Route::post('/tenants/{id}/impersonate', [SuperAdminImpersonationController::class, 'start'])->name('superadmin.impersonate.start');
        Route::post('/impersonation/stop', [SuperAdminImpersonationController::class, 'stop'])->name('superadmin.impersonate.stop');
        Route::get('/impersonation/logs', [SuperAdminImpersonationController::class, 'logs'])->name('superadmin.impersonate.logs');

        // 5. System Health Monitoring & Queue Inspector
        Route::get('/system/health', [SuperAdminSystemHealthController::class, 'health'])->name('superadmin.system.health');
        Route::post('/system/failed-jobs/{id}/retry', [SuperAdminSystemHealthController::class, 'retryJob'])->name('superadmin.system.jobs.retry');
        Route::post('/system/failed-jobs/flush', [SuperAdminSystemHealthController::class, 'flushJobs'])->name('superadmin.system.jobs.flush');

        // 6. Support Tickets / Client Issue Log
        Route::get('/tickets', [SuperAdminSupportTicketController::class, 'index'])->name('superadmin.tickets.index');
        Route::post('/tickets', [SuperAdminSupportTicketController::class, 'store'])->name('superadmin.tickets.store');
        Route::put('/tickets/{id}', [SuperAdminSupportTicketController::class, 'update'])->name('superadmin.tickets.update');

        // 7. Global Cross-Tenant Audit Log Ledger
        Route::get('/audit-logs', [SuperAdminAuditLogController::class, 'index'])->name('superadmin.audit-logs.index');

        // 8. Platform Announcements / Maintenance Broadcasts
        Route::get('/announcements', [SuperAdminAnnouncementController::class, 'index'])->name('superadmin.announcements.index');
        Route::post('/announcements', [SuperAdminAnnouncementController::class, 'store'])->name('superadmin.announcements.store');
        Route::put('/announcements/{id}', [SuperAdminAnnouncementController::class, 'update'])->name('superadmin.announcements.update');
        Route::delete('/announcements/{id}', [SuperAdminAnnouncementController::class, 'destroy'])->name('superadmin.announcements.destroy');
    });
