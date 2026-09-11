<?php

namespace App\Domain\OPD\Providers;

use Illuminate\Support\ServiceProvider;

class OpdDomainServiceProvider extends ServiceProvider
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
        // Load OPD Domain API routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
