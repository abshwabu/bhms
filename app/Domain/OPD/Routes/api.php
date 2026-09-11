<?php

use App\Domain\OPD\Http\Controllers\AppointmentController;
use App\Domain\OPD\Http\Controllers\DepartmentController;
use App\Domain\OPD\Http\Controllers\DoctorScheduleController;
use App\Domain\OPD\Http\Controllers\QueueTokenController;
use App\Domain\OPD\Http\Controllers\ReferralController;
use App\Domain\OPD\Http\Controllers\SoapNoteController;
use App\Domain\Shared\Http\Middleware\BranchScopeMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Appointment & OPD Domain API Routes
|--------------------------------------------------------------------------
| All routes under /api/v1/opd
*/

Route::prefix('api/v1/opd')->middleware(['api', 'auth:sanctum', BranchScopeMiddleware::class])->group(function () {

    // Clinical Departments
    Route::prefix('departments')->group(function () {
        Route::get('/', [DepartmentController::class, 'index'])->name('opd.departments.index');
        Route::post('/', [DepartmentController::class, 'store'])->name('opd.departments.store');
    });

    // Doctor Schedules & Availability
    Route::prefix('schedules')->group(function () {
        Route::get('/', [DoctorScheduleController::class, 'index'])->name('opd.schedules.index');
        Route::post('/', [DoctorScheduleController::class, 'store'])->name('opd.schedules.store');
        Route::get('/availability', [DoctorScheduleController::class, 'availability'])->name('opd.schedules.availability');
    });

    // Appointments (Booking, Rescheduling, Cancellation, Check-in)
    Route::prefix('appointments')->group(function () {
        Route::get('/', [AppointmentController::class, 'index'])->name('opd.appointments.index');
        Route::post('/', [AppointmentController::class, 'store'])->name('opd.appointments.store');
        Route::get('/{appointment}', [AppointmentController::class, 'show'])->name('opd.appointments.show');
        Route::post('/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('opd.appointments.reschedule');
        Route::post('/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('opd.appointments.cancel');
        Route::post('/{appointment}/check-in', [AppointmentController::class, 'checkIn'])->name('opd.appointments.check_in');
    });

    // Queue Management (Tokens, Calling next, TV Display)
    Route::prefix('queue')->group(function () {
        Route::get('/tokens', [QueueTokenController::class, 'index'])->name('opd.queue.tokens.index');
        Route::post('/tokens', [QueueTokenController::class, 'store'])->name('opd.queue.tokens.store');
        Route::post('/call-next', [QueueTokenController::class, 'callNext'])->name('opd.queue.call_next');
        Route::patch('/tokens/{token}/status', [QueueTokenController::class, 'updateStatus'])->name('opd.queue.tokens.update_status');
        Route::get('/display', [QueueTokenController::class, 'display'])->name('opd.queue.display');
    });

    // Consultation Notes (SOAP format, Sign-off locking, Versioned amendments)
    Route::prefix('soap-notes')->group(function () {
        Route::get('/', [SoapNoteController::class, 'index'])->name('opd.soap.index');
        Route::post('/', [SoapNoteController::class, 'store'])->name('opd.soap.store');
        Route::get('/{note}', [SoapNoteController::class, 'show'])->name('opd.soap.show');
        Route::put('/{note}', [SoapNoteController::class, 'update'])->name('opd.soap.update');
        Route::post('/{note}/sign-off', [SoapNoteController::class, 'signOff'])->name('opd.soap.sign_off');
        Route::post('/{note}/amend', [SoapNoteController::class, 'amend'])->name('opd.soap.amend');
    });

    // Referrals (Internal department & External facility)
    Route::prefix('referrals')->group(function () {
        Route::get('/', [ReferralController::class, 'index'])->name('opd.referrals.index');
        Route::post('/', [ReferralController::class, 'store'])->name('opd.referrals.store');
        Route::get('/{referral}', [ReferralController::class, 'show'])->name('opd.referrals.show');
        Route::patch('/{referral}/status', [ReferralController::class, 'updateStatus'])->name('opd.referrals.update_status');
    });
});
