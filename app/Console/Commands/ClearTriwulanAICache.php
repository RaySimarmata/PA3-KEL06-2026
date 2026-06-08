<?php

namespace App\Console\Commands;

use App\Models\AIResponseCacheMongo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ClearTriwulanAICache extends Command
{
    protected $signature = 'ai:clear-triwulan-cache';
    protected $description = 'Clear AI cache specifically for Laporan Triwulan feature';

    public function handle()
    {
        $this->info('🗑️  Clearing AI Cache for Laporan Triwulan...');
        $this->newLine();

        try {
            // Delete all cache entries with feature = 'triwulan'
            $deleted = AIResponseCacheMongo::where('context_metadata.feature', 'triwulan')->delete();

            $this->info("✅ Successfully cleared {$deleted} cache entries for Laporan Triwulan");
            
            Log::info('AI Cache cleared for Laporan Triwulan', [
                'deleted_count' => $deleted,
                'timestamp' => now()
            ]);

            $this->newLine();
            $this->line('💡 Tip: Sekarang coba generate laporan triwulan lagi untuk mendapatkan hasil yang fresh!');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Failed to clear cache: ' . $e->getMessage());
            Log::error('Failed to clear Triwulan AI cache', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
