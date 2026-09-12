<?php

use App\Domain\Compliance\Http\Controllers\AuditLogController;
use App\Domain\Compliance\Http\Controllers\ConsentController;
use App\Domain\Compliance\Http\Controllers\HipaaComplianceController;
use App\Domain\Compliance\Http\Controllers\RolePermissionController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/compliance')
    ->middleware(['auth:sanctum', 'enforce.tls'])
    ->group(function () {
        // 1. Role & Permission Management (RBAC)
        Route::middleware('rbac:compliance.roles.manage')->group(function () {
            Route::get('/roles', [RolePermissionController::class, 'index']);
            Route::post('/roles', [RolePermissionController::class, 'store']);
            Route::put('/roles/{id}', [RolePermissionController::class, 'update']);
            Route::delete('/roles/{id}', [RolePermissionController::class, 'destroy']);
            Route::get('/permissions', [RolePermissionController::class, 'permissions']);
            Route::post('/users/{id}/roles', [RolePermissionController::class, 'assignUserRoles']);
            Route::get('/users/{id}/permissions', [RolePermissionController::class, 'getUserPermissions']);
        });

        // 2. Audit Trails & Logs
        Route::middleware('rbac:compliance.audit.view')->group(function () {
            Route::get('/audit-logs', [AuditLogController::class, 'index']);
            Route::get('/audit-logs/stats', [AuditLogController::class, 'stats']);
            Route::get('/audit-logs/{id}', [AuditLogController::class, 'show']);
        });

        // 3. Patient Consent Management
        Route::middleware('rbac:compliance.consents.manage|patient.records.view')->group(function () {
            Route::get('/consents', [ConsentController::class, 'index']);
            Route::post('/consents', [ConsentController::class, 'store']);
            Route::get('/consents/{id}', [ConsentController::class, 'show']);
            Route::post('/consents/{id}/revoke', [ConsentController::class, 'revoke']);
            Route::get('/patients/{patientId}/consents', [ConsentController::class, 'getPatientConsents']);
            Route::get('/patients/{patientId}/verify', [ConsentController::class, 'verify']);
        });

        // 4. HIPAA Compliance Checklist & Automated Safeguard Audits
        Route::middleware('rbac:compliance.hipaa.audit')->group(function () {
            Route::get('/hipaa/checklist', [HipaaComplianceController::class, 'checklist']);
            Route::post('/hipaa/evaluate', [HipaaComplianceController::class, 'evaluate']);
        });
    });
