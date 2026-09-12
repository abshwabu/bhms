<?php

use App\Domain\Billing\Http\Controllers\DiscountRefundController;
use App\Domain\Billing\Http\Controllers\InsuranceClaimController;
use App\Domain\Billing\Http\Controllers\InvoiceController;
use App\Domain\Billing\Http\Controllers\PaymentController;
use App\Domain\Billing\Http\Controllers\PriceListController;
use App\Domain\Billing\Http\Controllers\RevenueReportController;
use App\Domain\Shared\Http\Middleware\BranchScopeMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Billing & Finance Domain API Routes
|--------------------------------------------------------------------------
| All routes under /api/v1/billing
*/

Route::prefix('api/v1/billing')->middleware(['api', 'auth:sanctum', BranchScopeMiddleware::class])->group(function () {

    // 1. Invoices & Itemized Line Charges
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('billing.invoices.index');
        Route::post('/', [InvoiceController::class, 'store'])->name('billing.invoices.store');
        Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('billing.invoices.show');
        Route::post('/{invoice}/items', [InvoiceController::class, 'addItem'])->name('billing.invoices.add_item');
        Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('billing.invoices.cancel');
        Route::get('/{invoice}/receipt', [InvoiceController::class, 'receipt'])->name('billing.invoices.receipt');
    });

    // 2. Payments & Receipts
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('billing.payments.index');
        Route::post('/', [PaymentController::class, 'store'])->name('billing.payments.store');
        Route::get('/{payment}', [PaymentController::class, 'show'])->name('billing.payments.show');
        Route::post('/{payment}/reverse', [PaymentController::class, 'reverse'])->name('billing.payments.reverse');
    });

    // 3. Price Lists & Master Catalog Configuration
    Route::prefix('price-lists')->group(function () {
        Route::get('/', [PriceListController::class, 'index'])->name('billing.price_lists.index');
        Route::post('/', [PriceListController::class, 'store'])->name('billing.price_lists.store');
        Route::get('/{priceList}', [PriceListController::class, 'show'])->name('billing.price_lists.show');
        Route::match(['put', 'patch'], '/{priceList}', [PriceListController::class, 'update'])->name('billing.price_lists.update');
    });

    // 4. Discounts & Approvals
    Route::prefix('discounts')->group(function () {
        Route::get('/pending', [DiscountRefundController::class, 'pendingDiscounts'])->name('billing.discounts.pending');
        Route::post('/', [DiscountRefundController::class, 'requestDiscount'])->name('billing.discounts.store');
        Route::post('/{discount}/approve', [DiscountRefundController::class, 'approveDiscount'])->name('billing.discounts.approve');
        Route::post('/{discount}/reject', [DiscountRefundController::class, 'rejectDiscount'])->name('billing.discounts.reject');
    });

    // 5. Refunds & Approvals
    Route::prefix('refunds')->group(function () {
        Route::get('/pending', [DiscountRefundController::class, 'pendingRefunds'])->name('billing.refunds.pending');
        Route::post('/', [DiscountRefundController::class, 'requestRefund'])->name('billing.refunds.store');
        Route::post('/{refund}/approve', [DiscountRefundController::class, 'approveRefund'])->name('billing.refunds.approve');
        Route::post('/{refund}/reject', [DiscountRefundController::class, 'rejectRefund'])->name('billing.refunds.reject');
    });

    // 6. Insurance & TPA Claims
    Route::prefix('claims')->group(function () {
        Route::get('/', [InsuranceClaimController::class, 'index'])->name('billing.claims.index');
        Route::post('/', [InsuranceClaimController::class, 'store'])->name('billing.claims.store');
        Route::get('/{insuranceClaim}', [InsuranceClaimController::class, 'show'])->name('billing.claims.show');
        Route::post('/{insuranceClaim}/adjudicate', [InsuranceClaimController::class, 'adjudicate'])->name('billing.claims.adjudicate');
        Route::post('/{insuranceClaim}/reconcile', [InsuranceClaimController::class, 'reconcile'])->name('billing.claims.reconcile');
    });

    // 7. Revenue & Collection Analytics
    Route::prefix('reports')->group(function () {
        Route::get('/departments', [RevenueReportController::class, 'departments'])->name('billing.reports.departments');
        Route::get('/doctors', [RevenueReportController::class, 'doctors'])->name('billing.reports.doctors');
        Route::get('/payment-modes', [RevenueReportController::class, 'paymentModes'])->name('billing.reports.payment_modes');
        Route::get('/summary', [RevenueReportController::class, 'summary'])->name('billing.reports.summary');
    });
});
