<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| These tasks will run automatically when the scheduler is configured
| in the server's cron: * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
|
*/

// Daily database backup at 2:00 AM
Schedule::command('backup:database --compress --clean')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground();

// Daily security audit at 6:00 AM
Schedule::command('security:audit')
    ->dailyAt('06:00')
    ->withoutOverlapping();

// Incident detection every 15 minutes
Schedule::command('incidents:detect')
    ->everyFifteenMinutes()
    ->withoutOverlapping();
