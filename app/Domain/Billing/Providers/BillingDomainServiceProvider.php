<?php

namespace App\Domain\Billing\Providers;

use App\Domain\Billing\Services\BillingCalculationService;
use App\Domain\Billing\Services\DiscountRefundApprovalService;
use App\Domain\Billing\Services\InsuranceClaimService;
use App\Domain\Billing\Services\InvoiceService;
use App\Domain\Billing\Services\PaymentService;
use App\Domain\Billing\Services\RevenueReportService;
use Illuminate\Support\ServiceProvider;

class BillingDomainServiceProvider extends ServiceProvider
{
    /**
     * Register Billing & Finance domain services.
     */
    public function register(): void
    {
        $this->app->singleton(BillingCalculationService::class);
        $this->app->singleton(DiscountRefundApprovalService::class);
        $this->app->singleton(PaymentService::class);
        $this->app->singleton(InvoiceService::class);
        $this->app->singleton(InsuranceClaimService::class);
        $this->app->singleton(RevenueReportService::class);
    }

    /**
     * Bootstrap Billing Domain API routes.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
