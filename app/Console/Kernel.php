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
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
    }
}