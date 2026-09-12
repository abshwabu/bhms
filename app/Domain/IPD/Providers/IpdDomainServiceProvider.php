<?php

namespace App\Domain\IPD\Providers;

use Illuminate\Support\ServiceProvider;

class IpdDomainServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load IPD Domain API routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
