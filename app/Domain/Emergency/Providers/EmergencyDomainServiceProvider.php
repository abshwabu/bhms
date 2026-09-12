<?php

namespace App\Domain\Emergency\Providers;

use App\Domain\Emergency\Services\AmbulanceDispatchService;
use App\Domain\Emergency\Services\EmergencyBedAllocationService;
use App\Domain\Emergency\Services\TriageQueueService;
use Illuminate\Support\ServiceProvider;

class EmergencyDomainServiceProvider extends ServiceProvider
{
    /**
     * Register Emergency & Ambulance domain services.
     */
    public function register(): void
    {
        $this->app->singleton(TriageQueueService::class);
        $this->app->singleton(AmbulanceDispatchService::class);
        $this->app->singleton(EmergencyBedAllocationService::class);
    }

    /**
     * Bootstrap Emergency Domain API routes.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
