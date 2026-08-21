<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes & Scheduled Tasks
|--------------------------------------------------------------------------
|
| This file is the entry point for all console commands and scheduled
| tasks. Laravel's scheduler runs commands defined here based on their
| frequency settings.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Below you may define your application's scheduled tasks. These tasks
| will be run automatically by Laravel's scheduler.
|
*/

// Process daily attendance - runs every day at 00:05
// This creates absent (Alpha) records for employees who didn't check in yesterday
Schedule::command('attendance:process-daily')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/attendance-processor.log'))
    ->runInBackground()
    ->emailOutputOnFailure(config('app.admin_email'));
