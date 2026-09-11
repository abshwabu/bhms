<?php

namespace App\Domain\Patient\Providers;

use App\Domain\Patient\Models\Patient;
use App\Domain\Patient\Policies\PatientPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PatientDomainServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the domain.
     */
    protected array $policies = [
        Patient::class => PatientPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind repositories or services if required
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Register Domain API Routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
