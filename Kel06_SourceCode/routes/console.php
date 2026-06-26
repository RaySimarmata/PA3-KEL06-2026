<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule untuk mengirim reminder otomatis setiap menit
Schedule::command('reminders:send')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Schedule untuk auto-refresh API token setiap 45 menit
Schedule::call(function () {
    $tokenManager = new \App\Services\APITokenManager();
    $tokenManager->refreshToken();
})->cron('*/45 * * * *') // Every 45 minutes
    ->name('api-token-refresh')
    ->onSuccess(function () {
        \Log::info('API token auto-refreshed successfully');
    })
    ->onFailure(function () {
        \Log::error('Failed to auto-refresh API token');
    });

// Schedule untuk preload monitoring RPS cache setiap 9 menit
Schedule::command('cache:preload-monitoring-rps --prodi=4 --semester=1 --ta=2020')
    ->cron('*/9 * * * *') // Every 9 minutes
    ->name('preload-monitoring-rps-cache')
    ->onSuccess(function () {
        \Log::info('Monitoring RPS cache preloaded successfully');
    })
    ->onFailure(function () {
        \Log::error('Failed to preload monitoring RPS cache');
    });
