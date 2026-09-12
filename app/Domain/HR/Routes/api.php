<?php

use App\Domain\HR\Http\Controllers\AttendanceController;
use App\Domain\HR\Http\Controllers\LeaveManagementController;
use App\Domain\HR\Http\Controllers\RosterScheduleController;
use App\Domain\HR\Http\Controllers\StaffProfileController;
use App\Domain\Shared\Http\Middleware\BranchScopeMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| HR & Staff Management Domain API Routes
|--------------------------------------------------------------------------
| All routes under /api/v1/hr
*/

Route::prefix('api/v1/hr')->middleware(['api', 'auth:sanctum', BranchScopeMiddleware::class])->group(function () {

    // 1. Staff Profiles & Credentials
    Route::prefix('staff')->group(function () {
        Route::get('/roles', [StaffProfileController::class, 'listRoles'])->name('hr.staff.roles');
        Route::get('/', [StaffProfileController::class, 'index'])->name('hr.staff.index');
        Route::post('/', [StaffProfileController::class, 'store'])->name('hr.staff.store');
        Route::get('/{staff}', [StaffProfileController::class, 'show'])->name('hr.staff.show');
        Route::match(['put', 'patch'], '/{staff}', [StaffProfileController::class, 'update'])->name('hr.staff.update');
        Route::post('/{staff}/credentials', [StaffProfileController::class, 'recordCredential'])->name('hr.staff.record_credential');
        Route::get('/{staff}/performance', [StaffProfileController::class, 'performance'])->name('hr.staff.performance');
        Route::get('/{staff}/leave-balances', [LeaveManagementController::class, 'balances'])->name('hr.staff.leave_balances');
    });

    // 2. Credential Expiry Alerts
    Route::get('/credentials/alerts', [StaffProfileController::class, 'credentialAlerts'])->name('hr.credentials.alerts');

    // 3. Shift Roster & Conflict Management
    Route::prefix('shifts')->group(function () {
        Route::get('/', [RosterScheduleController::class, 'index'])->name('hr.shifts.index');
        Route::post('/', [RosterScheduleController::class, 'store'])->name('hr.shifts.store');
        Route::post('/check-conflicts', [RosterScheduleController::class, 'checkConflicts'])->name('hr.shifts.check_conflicts');
        Route::post('/publish', [RosterScheduleController::class, 'publish'])->name('hr.shifts.publish');
        Route::get('/grid', [RosterScheduleController::class, 'grid'])->name('hr.shifts.grid');
        Route::post('/{shift}/cancel', [RosterScheduleController::class, 'cancel'])->name('hr.shifts.cancel');
    });

    // 4. Attendance & Roll-Call Tracking
    Route::prefix('attendance')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('hr.attendance.index');
        Route::post('/check-in', [AttendanceController::class, 'checkIn'])->name('hr.attendance.check_in');
        Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('hr.attendance.check_out');
        Route::get('/today-status', [AttendanceController::class, 'todayStatus'])->name('hr.attendance.today_status');
    });

    // 5. Leave Requests & Approval Workflow
    Route::prefix('leave-requests')->group(function () {
        Route::get('/', [LeaveManagementController::class, 'index'])->name('hr.leave_requests.index');
        Route::post('/', [LeaveManagementController::class, 'store'])->name('hr.leave_requests.store');
        Route::post('/{leaveRequest}/approve', [LeaveManagementController::class, 'approve'])->name('hr.leave_requests.approve');
        Route::post('/{leaveRequest}/reject', [LeaveManagementController::class, 'reject'])->name('hr.leave_requests.reject');
    });

    // 6. Optional Payroll Preview Hook
    Route::get('/payroll/preview', [StaffProfileController::class, 'payrollPreview'])->name('hr.payroll.preview');
});
