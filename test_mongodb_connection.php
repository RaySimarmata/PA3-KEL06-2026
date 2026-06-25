<?php

/**
 * Test MongoDB Connection for Evaluasi AI Assistant
 * 
 * This script tests the MongoDB connection and verifies that the
 * AIResponseCacheMongo model can access data from MongoDB.
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\AIResponseCacheMongo;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     Test MongoDB Connection - Evaluasi AI Assistant         ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Test 1: Check MongoDB configuration
echo "Test 1: Checking MongoDB Configuration\n";
echo "──────────────────────────────────────────────────────────────\n";
$mongoUri = env('MONGODB_URI');
$mongoDb = env('MONGODB_DATABASE');
echo "MongoDB URI: " . ($mongoUri ? "✓ Configured" : "✗ Not configured") . "\n";
echo "MongoDB Database: " . ($mongoDb ? $mongoDb : "✗ Not configured") . "\n";
echo "\n";

// Test 2: Test MongoDB connection
echo "Test 2: Testing MongoDB Connection\n";
echo "──────────────────────────────────────────────────────────────\n";
try {
    DB::connection('mongodb')->getPdo();
    echo "✓ MongoDB connection successful!\n";
} catch (Exception $e) {
    echo "✗ MongoDB connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
echo "\n";

// Test 3: Count documents in ai_response_cache collection
echo "Test 3: Counting Documents in ai_response_cache Collection\n";
echo "──────────────────────────────────────────────────────────────\n";
try {
    $totalCount = AIResponseCacheMongo::count();
    echo "✓ Total documents in ai_response_cache: {$totalCount}\n";
    
    if ($totalCount === 0) {
        echo "⚠ Warning: No documents found in ai_response_cache collection.\n";
        echo "  This is expected if no AI requests have been cached yet.\n";
    }
} catch (Exception $e) {
    echo "✗ Error counting documents: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Count by feature
echo "Test 4: Counting Documents by Feature\n";
echo "──────────────────────────────────────────────────────────────\n";
try {
    $features = ['triwulan', 'semester', 'vmts'];
    
    foreach ($features as $feature) {
        $count = AIResponseCacheMongo::where('context_metadata.feature', $feature)->count();
        echo "  {$feature}: {$count} documents\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: Get recent documents
echo "Test 5: Fetching Recent Documents (last 7 days)\n";
echo "──────────────────────────────────────────────────────────────\n";
try {
    $recentDocs = AIResponseCacheMongo::where('created_at', '>=', now()->subDays(7))
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    echo "✓ Found {$recentDocs->count()} recent documents\n";
    
    if ($recentDocs->count() > 0) {
        echo "\nSample Documents:\n";
        foreach ($recentDocs as $doc) {
            echo "  - ID: {$doc->_id}\n";
            echo "    Feature: " . ($doc->context_metadata['feature'] ?? 'N/A') . "\n";
            echo "    Provider: {$doc->ai_provider}\n";
            echo "    Model: {$doc->ai_model}\n";
            echo "    Usage Count: {$doc->usage_count}\n";
            echo "    Created: {$doc->created_at->format('Y-m-d H:i:s')}\n";
            echo "\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 6: Test cache statistics method
echo "Test 6: Testing Cache Statistics Method\n";
echo "──────────────────────────────────────────────────────────────\n";
try {
    $stats = AIResponseCacheMongo::getStatistics();
    
    echo "✓ Cache Statistics:\n";
    echo "  Total Entries: {$stats['total_entries']}\n";
    echo "  Total Usage: {$stats['total_usage']}\n";
    echo "  Avg Usage per Entry: {$stats['average_usage_per_entry']}\n";
    echo "  Recently Used: {$stats['recently_used_entries']}\n";
    echo "  Cache Hit Potential: {$stats['cache_hit_potential']}%\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    Test Completed                            ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
