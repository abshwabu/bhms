<?php

namespace App\Domain\Compliance\Providers;

use App\Domain\Compliance\Services\AuditTrailService;
use App\Domain\Compliance\Services\ConsentManagementService;
use App\Domain\Compliance\Services\HipaaComplianceService;
use App\Domain\Compliance\Services\RolePermissionService;
use Illuminate\Support\ServiceProvider;

class ComplianceDomainServiceProvider extends ServiceProvider
{
    /**
     * Register Compliance & Security services.
     */
    public function register(): void
    {
        $this->app->singleton(RolePermissionService::class);
        $this->app->singleton(AuditTrailService::class);
        $this->app->singleton(ConsentManagementService::class);
        $this->app->singleton(HipaaComplianceService::class);
    }

    /**
     * Bootstrap Compliance & Security routes and observers.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
