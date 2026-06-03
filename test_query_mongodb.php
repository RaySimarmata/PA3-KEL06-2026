<?php

/**
 * Test MongoDB Query untuk Model Evaluation
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  TESTING MONGODB QUERIES FOR MODEL EVALUATION\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

try {
    // Test 1: Query ALL data (no filter)
    echo "Test 1: Query ALL data (period=all, feature=all)\n";
    echo "──────────────────────────────────────────────────────────────\n";
    
    $totalAll = DB::table('ai_response_cache')->count();
    echo "Total entries (no filter): {$totalAll}\n";
    echo "\n";

    // Test 2: Query with feature filter (triwulan)
    echo "Test 2: Query with feature filter (triwulan)\n";
    echo "──────────────────────────────────────────────────────────────\n";
    
    // Method 1: Using whereJsonContains (might not work on all MongoDB drivers)
    try {
        $triwulanCount = DB::table('ai_response_cache')
            ->whereJsonContains('context_metadata->feature', 'triwulan')
            ->count();
        echo "✓ whereJsonContains method: {$triwulanCount} entries\n";
    } catch (\Exception $e) {
        echo "❌ whereJsonContains method failed: {$e->getMessage()}\n";
    }
    
    // Method 2: Using where with dot notation (MongoDB native)
    try {
        $triwulanCount2 = DB::table('ai_response_cache')
            ->where('context_metadata.feature', 'triwulan')
            ->count();
        echo "✓ Dot notation method: {$triwulanCount2} entries\n";
    } catch (\Exception $e) {
        echo "❌ Dot notation method failed: {$e->getMessage()}\n";
    }
    
    echo "\n";

    // Test 3: Query with date filter (7 days)
    echo "Test 3: Query with date filter (last 7 days)\n";
    echo "──────────────────────────────────────────────────────────────\n";
    
    $dateFilter = Carbon::now()->subDays(7);
    $recent7Days = DB::table('ai_response_cache')
        ->where('created_at', '>=', $dateFilter)
        ->count();
    echo "Entries in last 7 days: {$recent7Days}\n";
    echo "\n";

    // Test 4: Query with both filters
    echo "Test 4: Query with feature=triwulan AND last 7 days\n";
    echo "──────────────────────────────────────────────────────────────\n";
    
    try {
        $filtered = DB::table('ai_response_cache')
            ->where('created_at', '>=', $dateFilter)
            ->where('context_metadata.feature', 'triwulan')
            ->count();
        echo "✓ Filtered result: {$filtered} entries\n";
    } catch (\Exception $e) {
        echo "❌ Query failed: {$e->getMessage()}\n";
    }
    
    echo "\n";

    // Test 5: Show actual data structure
    echo "Test 5: Sample data structure\n";
    echo "──────────────────────────────────────────────────────────────\n";
    
    $sample = DB::table('ai_response_cache')
        ->orderBy('created_at', 'desc')
        ->first();
    
    if ($sample) {
        echo "Sample entry structure:\n";
        echo "  - ID: " . ($sample->_id ?? 'N/A') . "\n";
        echo "  - Feature: " . ($sample->context_metadata['feature'] ?? 'N/A') . "\n";
        echo "  - Provider: " . ($sample->ai_provider ?? 'N/A') . "\n";
        echo "  - Created: " . ($sample->created_at ?? 'N/A') . "\n";
        
        if (is_object($sample->context_metadata) || is_array($sample->context_metadata)) {
            echo "  - Context Metadata: " . json_encode($sample->context_metadata, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "  - Context Metadata Type: " . gettype($sample->context_metadata) . "\n";
        }
    }
    echo "\n";

    // Test 6: Group by feature
    echo "Test 6: Count by feature\n";
    echo "──────────────────────────────────────────────────────────────\n";
    
    $allData = DB::table('ai_response_cache')->get();
    $byFeature = [];
    
    foreach ($allData as $entry) {
        $feature = 'unknown';
        
        if (isset($entry->context_metadata)) {
            if (is_object($entry->context_metadata)) {
                $feature = $entry->context_metadata->feature ?? 'unknown';
            } elseif (is_array($entry->context_metadata)) {
                $feature = $entry->context_metadata['feature'] ?? 'unknown';
            }
        }
        
        if (!isset($byFeature[$feature])) {
            $byFeature[$feature] = 0;
        }
        $byFeature[$feature]++;
    }
    
    foreach ($byFeature as $feature => $count) {
        echo "  - {$feature}: {$count} entries\n";
    }
    echo "\n";

    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  RECOMMENDATIONS\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    
    if ($totalAll > 0) {
        echo "✓ MongoDB has data!\n\n";
        
        echo "If Model Evaluation still shows 'Belum Ada Data':\n";
        echo "1. Check browser console (F12) for JavaScript errors\n";
        echo "2. Try different period filter (7days, 30days, all)\n";
        echo "3. Try different feature filter (all, triwulan, semester)\n";
        echo "4. Clear browser cache and reload page\n";
        echo "5. Check Laravel logs for query errors\n";
    } else {
        echo "⚠️  MongoDB is empty!\n\n";
        echo "Generate a new laporan with AI Assistant to populate data.\n";
    }
    
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}

exit(0);
