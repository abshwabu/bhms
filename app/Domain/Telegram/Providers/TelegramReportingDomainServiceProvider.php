<?php

namespace App\Domain\Telegram\Providers;

use App\Domain\Telegram\Console\Commands\RetryFailedTelegramMessagesCommand;
use App\Domain\Telegram\Console\Commands\SendDailyTelegramDigestCommand;
use App\Domain\Telegram\Console\Commands\SendShiftHandoverReportCommand;
use App\Domain\Telegram\Services\TelegramAlertDispatcherService;
use App\Domain\Telegram\Services\TelegramApiService;
use App\Domain\Telegram\Services\TelegramCommandParserService;
use App\Domain\Telegram\Services\TelegramReportGeneratorService;
use Illuminate\Support\ServiceProvider;

class TelegramReportingDomainServiceProvider extends ServiceProvider
{
    /**
     * Register Telegram Reporting services.
     */
    public function register(): void
    {
        $this->app->singleton(TelegramApiService::class);
        $this->app->singleton(TelegramReportGeneratorService::class);
        $this->app->singleton(TelegramCommandParserService::class);
        $this->app->singleton(TelegramAlertDispatcherService::class);
    }

    /**
     * Bootstrap routes and console commands.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SendDailyTelegramDigestCommand::class,
                SendShiftHandoverReportCommand::class,
                RetryFailedTelegramMessagesCommand::class,
            ]);
        }
    }
}
