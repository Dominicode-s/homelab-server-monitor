<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Scheduled tasks
Artisan::command('sentinel:evaluate-alerts', function (): void {
    $this->info('Evaluating alerts...');
})->purpose('Evaluate alert rules against current metrics')->everyFiveMinutes();

Artisan::command('sentinel:cleanup-metrics', function (): void {
    $this->info('Cleaning up old metrics...');
})->purpose('Remove telemetry data past retention period')->daily();

Artisan::command('sentinel:check-offline', function (): void {
    $this->info('Checking for offline servers...');
})->everyMinute();
