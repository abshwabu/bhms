<?php

namespace App\Domain\Radiology\Providers;

use App\Domain\Radiology\Services\ImagingReportService;
use App\Domain\Radiology\Services\ImagingStorageService;
use App\Domain\Radiology\Services\PacsIntegrationService;
use Illuminate\Support\ServiceProvider;

class RadiologyDomainServiceProvider extends ServiceProvider
{
    /**
     * Register Radiology Information System (RIS) domain services.
     */
    public function register(): void
    {
        $this->app->singleton(ImagingStorageService::class);
        $this->app->singleton(PacsIntegrationService::class);
        $this->app->singleton(ImagingReportService::class);
    }

    /**
     * Bootstrap Radiology Domain API routes.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
