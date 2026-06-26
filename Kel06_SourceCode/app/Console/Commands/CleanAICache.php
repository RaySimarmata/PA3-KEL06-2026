<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AICacheService;

class CleanAICache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:clean-cache {--days=30 : Number of days to keep cache entries} {--stats : Show cache statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean old AI response cache entries and show statistics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cacheService = app(AICacheService::class);
        
        // Show statistics if requested
        if ($this->option('stats')) {
            $this->showStatistics($cacheService);
        }
        
        // Clean old entries
        $days = (int) $this->option('days');
        $cacheService->setMaxCacheAge($days);
        
        $this->info("Cleaning AI cache entries older than {$days} days...");
        
        $deleted = $cacheService->cleanOldEntries();
        
        if ($deleted > 0) {
            $this->info("✅ Cleaned {$deleted} old cache entries.");
        } else {
            $this->info("ℹ️  No old cache entries found to clean.");
        }
        
        // Show updated statistics
        $this->showStatistics($cacheService);
        
        return 0;
    }
    
    /**
     * Show cache statistics
     */
    private function showStatistics(AICacheService $cacheService): void
    {
        $stats = $cacheService->getStatistics();
        
        $this->info("\n📊 AI Cache Statistics:");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Entries', number_format($stats['total_entries'])],
                ['Total Usage', number_format($stats['total_usage'])],
                ['Average Usage per Entry', $stats['average_usage_per_entry']],
                ['Recently Used (7 days)', number_format($stats['recently_used_entries'])],
                ['Cache Hit Potential', $stats['cache_hit_potential'] . '%'],
            ]
        );
        
        if ($stats['total_entries'] > 0) {
            $this->info("💡 Cache hit potential shows how much API usage could be saved by reusing cached responses.");
        }
    }
}