<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('hms:telegram-daily-digest')->dailyAt('08:00');
Schedule::command('hms:telegram-shift-handover morning')->dailyAt('07:00');
Schedule::command('hms:telegram-shift-handover evening')->dailyAt('15:00');
Schedule::command('hms:telegram-shift-handover night')->dailyAt('23:00');
Schedule::command('hms:telegram-retry-failed')->everyFiveMinutes();

