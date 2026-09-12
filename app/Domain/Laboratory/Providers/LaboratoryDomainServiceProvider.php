<?php

namespace App\Domain\Laboratory\Providers;

use App\Domain\Laboratory\Services\BarcodeService;
use App\Domain\Laboratory\Services\Hl7AstmIntegrationService;
use App\Domain\Laboratory\Services\LabReportService;
use App\Domain\Laboratory\Services\LabResultEvaluationService;
use Illuminate\Support\ServiceProvider;

class LaboratoryDomainServiceProvider extends ServiceProvider
{
    /**
     * Register Laboratory Information System (LIS) domain services.
     */
    public function register(): void
    {
        $this->app->singleton(BarcodeService::class);
        $this->app->singleton(LabResultEvaluationService::class);
        $this->app->singleton(LabReportService::class);
        $this->app->singleton(Hl7AstmIntegrationService::class);
    }

    /**
     * Bootstrap Laboratory Domain API routes.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
