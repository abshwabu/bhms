<?php

namespace App\Domain\Pharmacy\Providers;

use App\Domain\Pharmacy\Services\DispensingService;
use App\Domain\Pharmacy\Services\PharmacyAlertService;
use App\Domain\Pharmacy\Services\PharmacyInventoryService;
use Illuminate\Support\ServiceProvider;

class PharmacyDomainServiceProvider extends ServiceProvider
{
    /**
     * Register Pharmacy domain services.
     */
    public function register(): void
    {
        $this->app->singleton(PharmacyAlertService::class);
        $this->app->singleton(PharmacyInventoryService::class);
        $this->app->singleton(DispensingService::class);
    }

    /**
     * Bootstrap Pharmacy Domain API routes.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
