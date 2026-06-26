<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearDashboardCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-dashboard {--filter-only} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear GJM dashboard cache';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            Cache::flush();
            $this->info('✓ Semua cache berhasil dihapus!');
            return;
        }

        if ($this->option('filter-only')) {
            Cache::forget('filter_list_tahun');
            Cache::forget('filter_list_prodi');
            $this->info('✓ Filter list cache berhasil dihapus!');
            return;
        }

        // Default: clear dashboard cache only
        Cache::forget('filter_list_tahun');
        Cache::forget('filter_list_prodi');

        try {
            Cache::tags('dashboard_gjm')->flush();
            $this->info('✓ Dashboard cache berhasil dihapus! (Redis)');
        } catch (\Exception $e) {
            $this->warn('⚠ Cache driver tidak support tagging. Silakan clear manual atau ubah driver ke Redis.');
            $this->info('  Dashboard data akan auto-expire dalam 60 menit');
        }
    }
}
