<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\SyncDosen;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        SyncDosen::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // contoh:
        // $schedule->command('sync:dosen')->daily();
        
        // Clean AI cache entries older than 30 days, run daily at 2 AM
        $schedule->command('ai:clean-cache --days=30')->dailyAt('02:00');
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
    }
}