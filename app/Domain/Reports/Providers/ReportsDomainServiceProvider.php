<?php

namespace App\Domain\Reports\Providers;

use App\Domain\Reports\Console\Commands\AggregateHospitalKpisCommand;
use App\Domain\Reports\Services\CustomReportBuilderService;
use App\Domain\Reports\Services\KpiAggregationService;
use App\Domain\Reports\Services\ReportExportService;
use App\Domain\Reports\Services\StandardReportService;
use Illuminate\Support\ServiceProvider;

class ReportsDomainServiceProvider extends ServiceProvider
{
    /**
     * Register Reports & Analytics services.
     */
    public function register(): void
    {
        $this->app->singleton(KpiAggregationService::class);
        $this->app->singleton(StandardReportService::class);
        $this->app->singleton(CustomReportBuilderService::class);
        $this->app->singleton(ReportExportService::class);
    }

    /**
     * Bootstrap Reports & Analytics routes and console commands.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                AggregateHospitalKpisCommand::class,
            ]);
        }
    }
}
