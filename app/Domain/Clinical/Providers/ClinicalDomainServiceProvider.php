<?php

namespace App\Domain\Clinical\Providers;

use App\Domain\Clinical\Services\DoctorDashboardService;
use App\Domain\Clinical\Services\DrugInteractionService;
use App\Domain\Clinical\Services\EhrService;
use App\Domain\Clinical\Services\Icd10ValidationService;
use Illuminate\Support\ServiceProvider;

class ClinicalDomainServiceProvider extends ServiceProvider
{
    /**
     * Register Clinical Domain services.
     */
    public function register(): void
    {
        $this->app->singleton(DrugInteractionService::class);
        $this->app->singleton(Icd10ValidationService::class);
        $this->app->singleton(EhrService::class);
        $this->app->singleton(DoctorDashboardService::class);
    }

    /**
     * Bootstrap Clinical Domain API routes and services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
