<?php

use App\Domain\Administration\Http\Controllers\BackupManagementController;
use App\Domain\Administration\Http\Controllers\BranchManagementController;
use App\Domain\Administration\Http\Controllers\MasterDataManagementController;
use App\Domain\Administration\Http\Controllers\NotificationManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/admin')
    ->middleware(['auth:sanctum', 'enforce.tls'])
    ->group(function () {
        // 1. Multi-Branch & Multi-Tenant Management
        Route::get('/branches', [BranchManagementController::class, 'index']);
        Route::post('/branches', [BranchManagementController::class, 'store']);
        Route::get('/branches/{id}', [BranchManagementController::class, 'show']);
        Route::put('/branches/{id}', [BranchManagementController::class, 'update']);
        Route::delete('/branches/{id}', [BranchManagementController::class, 'destroy']);
        Route::post('/branches/{branchId}/users', [BranchManagementController::class, 'assignUser']);

        // 2. Master Data Management (Departments, Services, Price Lists)
        // Departments
        Route::get('/branches/{branchId}/departments', [MasterDataManagementController::class, 'getDepartments']);
        Route::post('/departments', [MasterDataManagementController::class, 'storeDepartment']);
        Route::put('/departments/{id}', [MasterDataManagementController::class, 'updateDepartment']);
        Route::delete('/departments/{id}', [MasterDataManagementController::class, 'destroyDepartment']);

        // Hospital Services Catalog
        Route::get('/branches/{branchId}/services', [MasterDataManagementController::class, 'getServices']);
        Route::post('/services', [MasterDataManagementController::class, 'storeService']);
        Route::put('/services/{id}', [MasterDataManagementController::class, 'updateService']);
        Route::delete('/services/{id}', [MasterDataManagementController::class, 'destroyService']);

        // Price Lists
        Route::get('/branches/{branchId}/price-lists', [MasterDataManagementController::class, 'getPriceLists']);
        Route::post('/price-lists', [MasterDataManagementController::class, 'storePriceList']);
        Route::put('/price-lists/{id}', [MasterDataManagementController::class, 'updatePriceList']);
        Route::delete('/price-lists/{id}', [MasterDataManagementController::class, 'destroyPriceList']);

        // 3. Notification Engine (SMS, Email, Push)
        Route::get('/notification-templates', [NotificationManagementController::class, 'getTemplates']);
        Route::post('/notification-templates', [NotificationManagementController::class, 'storeTemplate']);
        Route::put('/notification-templates/{id}', [NotificationManagementController::class, 'updateTemplate']);
        Route::delete('/notification-templates/{id}', [NotificationManagementController::class, 'destroyTemplate']);

        Route::post('/notifications/send', [NotificationManagementController::class, 'send']);
        Route::get('/notifications/logs', [NotificationManagementController::class, 'getLogs']);
        Route::post('/notifications/logs/{id}/retry', [NotificationManagementController::class, 'retry']);
        Route::post('/notifications/retry-all', [NotificationManagementController::class, 'retryAll']);

        // 4. Backup & Disaster Recovery
        Route::get('/backups', [BackupManagementController::class, 'index']);
        Route::post('/backups', [BackupManagementController::class, 'store']);
        Route::post('/backups/{id}/verify', [BackupManagementController::class, 'verify']);
        Route::post('/backups/{id}/restore-drill', [BackupManagementController::class, 'testRestoreDrill']);
    });
