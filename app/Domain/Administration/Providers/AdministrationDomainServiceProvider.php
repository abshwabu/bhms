<?php

namespace App\Domain\Administration\Providers;

use App\Domain\Administration\Console\Commands\DatabaseBackupCommand;
use App\Domain\Administration\Console\Commands\DatabaseRestoreTestCommand;
use App\Domain\Administration\Services\BackupService;
use App\Domain\Administration\Services\BranchManagementService;
use App\Domain\Administration\Services\MasterDataService;
use App\Domain\Administration\Services\Notification\NotificationEngineService;
use App\Domain\Administration\Services\Notification\Providers\EmailNotificationProvider;
use App\Domain\Administration\Services\Notification\Providers\PushNotificationProvider;
use App\Domain\Administration\Services\Notification\Providers\SmsNotificationProvider;
use Illuminate\Support\ServiceProvider;

class AdministrationDomainServiceProvider extends ServiceProvider
{
    /**
     * Register System Administration services.
     */
    public function register(): void
    {
        $this->app->singleton(BranchManagementService::class);
        $this->app->singleton(MasterDataService::class);
        $this->app->singleton(BackupService::class);

        $this->app->singleton(SmsNotificationProvider::class);
        $this->app->singleton(EmailNotificationProvider::class);
        $this->app->singleton(PushNotificationProvider::class);

        $this->app->singleton(NotificationEngineService::class, function ($app) {
            return new NotificationEngineService(
                $app->make(SmsNotificationProvider::class),
                $app->make(EmailNotificationProvider::class),
                $app->make(PushNotificationProvider::class)
            );
        });
    }

    /**
     * Bootstrap routes and commands.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                DatabaseBackupCommand::class,
                DatabaseRestoreTestCommand::class,
            ]);
        }
    }
}
