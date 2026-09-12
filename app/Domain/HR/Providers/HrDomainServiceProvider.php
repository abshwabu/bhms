<?php

namespace App\Domain\HR\Providers;

use App\Domain\HR\Services\AttendancePerformanceService;
use App\Domain\HR\Services\CredentialService;
use App\Domain\HR\Services\LeaveManagementService;
use App\Domain\HR\Services\RosterScheduleService;
use Illuminate\Support\ServiceProvider;

class HrDomainServiceProvider extends ServiceProvider
{
    /**
     * Register HR & Staff Management domain services.
     */
    public function register(): void
    {
        $this->app->singleton(RosterScheduleService::class);
        $this->app->singleton(CredentialService::class);
        $this->app->singleton(LeaveManagementService::class);
        $this->app->singleton(AttendancePerformanceService::class);
    }

    /**
     * Bootstrap HR Domain API routes.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
