<?php

/**
 * Check MongoDB ai_response_cache data
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AIResponseCacheMongo;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  MONGODB AI_RESPONSE_CACHE - DATA CHECK\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

try {
    // Check total entries
    $total = AIResponseCacheMongo::count();
    echo "✓ Total MongoDB Cache Entries: {$total}\n";
    echo "\n";

    if ($total == 0) {
        echo "⚠️  WARNING: No data in MongoDB ai_response_cache!\n\n";
        echo "This means:\n";
        echo "1. AI generation happened but cache was HIT (reused existing)\n";
        echo "2. OR MongoDB connection issue\n";
        echo "3. OR data being saved to different collection\n\n";
        
        echo "NEXT STEPS:\n";
        echo "→ Try generating a NEW unique prompt\n";
        echo "→ Check MongoDB connection in .env\n";
        echo "→ Check Laravel logs\n";
    } else {
        // Show recent entries
        echo "Recent Cache Entries (Last 10):\n";
        $recent = AIResponseCacheMongo::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        foreach ($recent as $entry) {
            $date = $entry->created_at->format('Y-m-d H:i:s');
            $feature = $entry->context_metadata['feature'] ?? 'unknown';
            $promptPreview = substr($entry->original_prompt, 0, 50) . '...';
            
            echo "\n[{$entry->id}] Feature: {$feature}\n";
            echo "    Prompt: {$promptPreview}\n";
            echo "    Provider: {$entry->ai_provider}\n";
            echo "    Usage Count: {$entry->usage_count}\n";
            echo "    Created: {$date}\n";
        }
        echo "\n";
        
        // Group by feature
        echo "Entries by Feature:\n";
        $allEntries = AIResponseCacheMongo::all();
        $byFeature = [];
        
        foreach ($allEntries as $entry) {
            $feature = $entry->context_metadata['feature'] ?? 'unknown';
            if (!isset($byFeature[$feature])) {
                $byFeature[$feature] = 0;
            }
            $byFeature[$feature]++;
        }
        
        foreach ($byFeature as $feature => $count) {
            echo "  - {$feature}: {$count} entries\n";
        }
        echo "\n";
        
        // Get statistics
        $stats = AIResponseCacheMongo::getStatistics();
        echo "Cache Statistics:\n";
        echo "  - Total entries: {$stats['total_entries']}\n";
        echo "  - Total usage: {$stats['total_usage']}\n";
        echo "  - Average usage per entry: {$stats['average_usage_per_entry']}\n";
        echo "  - Recently used (last 7 days): {$stats['recently_used_entries']}\n";
        echo "  - Cache hit potential: {$stats['cache_hit_potential']}%\n";
        echo "\n";
    }
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nThis might mean:\n";
    echo "1. MongoDB connection is not configured\n";
    echo "2. MongoDB driver is not installed\n";
    echo "3. Collection doesn't exist yet\n\n";
    
    echo "Check .env file:\n";
    echo "  DB_CONNECTION_MONGO=mongodb\n";
    echo "  DB_HOST_MONGO=127.0.0.1\n";
    echo "  DB_PORT_MONGO=27017\n";
    echo "  DB_DATABASE_MONGO=your_db_name\n\n";
    
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}

exit(0);
