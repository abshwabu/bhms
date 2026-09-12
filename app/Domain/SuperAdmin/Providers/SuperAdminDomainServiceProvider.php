<?php

namespace App\Domain\SuperAdmin\Providers;

use App\Domain\SuperAdmin\Services\FeatureFlagService;
use App\Domain\SuperAdmin\Services\ImpersonationService;
use App\Domain\SuperAdmin\Services\SystemHealthService;
use App\Domain\SuperAdmin\Services\TenantManagementService;
use Illuminate\Support\ServiceProvider;

class SuperAdminDomainServiceProvider extends ServiceProvider
{
    /**
     * Register Super Admin platform services.
     */
    public function register(): void
    {
        $this->app->singleton(FeatureFlagService::class);
        $this->app->singleton(TenantManagementService::class);
        $this->app->singleton(ImpersonationService::class);
        $this->app->singleton(SystemHealthService::class);
    }

    /**
     * Bootstrap routes and commands.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
