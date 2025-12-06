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
| Define scheduled tasks here using the Schedule facade.
| Run `php artisan schedule:work` locally or set up cron on production:
| * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
|
*/

// Check for missing daily entries and vaccination reminders at 6:00 PM every day
Schedule::command('farm:check-daily-entries')
    ->dailyAt('18:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/daily-entries-check.log'));

// Optional: Morning reminder at 8:00 AM for overdue vaccinations only
Schedule::command('farm:check-daily-entries --date=' . now()->subDay()->format('Y-m-d'))
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/daily-entries-check.log'));

// Check inventory levels every morning at 7:00 AM
Schedule::command('farm:check-inventory')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/inventory-check.log'));

// Also check inventory on Monday and Thursday afternoons (before ordering)
Schedule::command('farm:check-inventory')
    ->days([1, 4]) // Monday and Thursday
    ->at('14:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/inventory-check.log'));
