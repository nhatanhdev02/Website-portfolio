<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks for Production Monitoring
|--------------------------------------------------------------------------
*/

// Health checks every 5 minutes
Schedule::command('admin:health:check --format=json')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::critical('Health check failed');
    });

// System metrics collection every 15 minutes with threshold checking
Schedule::command('admin:metrics:collect --check-thresholds')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Clean up old metrics data daily at 2 AM
Schedule::command('admin:metrics:collect --cleanup')
    ->dailyAt('02:00')
    ->withoutOverlapping();

// Clear admin cache stats daily at 3 AM
Schedule::command('admin:cache:clear-stats')
    ->dailyAt('03:00')
    ->withoutOverlapping();

// Database performance monitoring every hour
Schedule::command('admin:database:monitor')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// System monitoring for 1 hour every 6 hours (for detailed monitoring with health checks and alerting)
Schedule::command('admin:monitor:system --interval=300 --duration=3600 --output=log --health-checks --alert-on-issues')
    ->everySixHours()
    ->withoutOverlapping()
    ->runInBackground();
