<?php

/**
 * Test Script: AI Response Cache Integration
 * 
 * Script untuk testing apakah integrasi ai_response_cache berhasil
 * untuk fitur Laporan Artefak dan Laporan Kuesioner
 * 
 * Usage: php test-ai-cache-integration.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "==============================================\n";
echo "  TEST: AI Response Cache Integration\n";
echo "==============================================\n\n";

// Test 1: Check if ai_response_cache table exists
echo "Test 1: Checking ai_response_cache table...\n";
try {
    $tableExists = DB::select("SHOW TABLES LIKE 'ai_response_cache'");
    if (count($tableExists) > 0) {
        echo "✅ PASS: ai_response_cache table exists\n\n";
    } else {
        echo "❌ FAIL: ai_response_cache table NOT found\n";
        echo "   Run migration: php artisan migrate\n\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Check total cache entries
echo "Test 2: Checking total cache entries...\n";
try {
    $totalEntries = DB::table('ai_response_cache')->count();
    echo "   Total entries: $totalEntries\n";
    
    if ($totalEntries > 0) {
        echo "✅ PASS: Cache entries found\n\n";
    } else {
        echo "⚠️  WARNING: No cache entries yet (This is normal for new installation)\n\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
}

// Test 3: Check cache entries by feature
echo "Test 3: Checking cache entries by feature...\n";
try {
    $features = ['triwulan', 'artefak', 'kuesioner'];
    $found = false;
    
    foreach ($features as $feature) {
        $count = DB::table('ai_response_cache')
            ->whereRaw("JSON_EXTRACT(context_metadata, '$.feature') = ?", [$feature])
            ->count();
        
        if ($count > 0) {
            echo "   ✅ Feature '$feature': $count entries\n";
            $found = true;
        } else {
            echo "   ⚠️  Feature '$feature': 0 entries (waiting for first request)\n";
        }
    }
    
    echo "\n";
    
    if ($found) {
        echo "✅ PASS: Some features have cache entries\n\n";
    } else {
        echo "⚠️  WARNING: No feature-specific entries yet\n";
        echo "   This is normal if you haven't used the AI Assistant features\n\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
}

// Test 4: Check recent cache activity
echo "Test 4: Checking recent cache activity (last 24 hours)...\n";
try {
    $recentActivity = DB::table('ai_response_cache')
        ->where('created_at', '>=', now()->subDay())
        ->select(
            DB::raw("JSON_EXTRACT(context_metadata, '$.feature') as feature"),
            'ai_provider',
            'ai_model',
            'usage_count',
            'response_time',
            'created_at'
        )
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    if ($recentActivity->count() > 0) {
        echo "   Recent cache entries:\n";
        foreach ($recentActivity as $entry) {
            $feature = trim($entry->feature ?? 'unknown', '"');
            $provider = $entry->ai_provider ?? 'N/A';
            $model = $entry->ai_model ?? 'N/A';
            $usage = $entry->usage_count ?? 0;
            $time = $entry->response_time ?? 0;
            $created = $entry->created_at ?? 'N/A';
            
            echo "   - [$feature] $provider/$model | Usage: $usage | Time: {$time}s | Created: $created\n";
        }
        echo "\n✅ PASS: Recent cache activity found\n\n";
    } else {
        echo "   ⚠️  No recent activity in last 24 hours\n";
        echo "   This is normal if you haven't used AI features recently\n\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
}

// Test 5: Check cache statistics
echo "Test 5: Calculating cache statistics...\n";
try {
    $stats = DB::table('ai_response_cache')
        ->select(
            DB::raw("JSON_EXTRACT(context_metadata, '$.feature') as feature"),
            DB::raw('COUNT(*) as total_entries'),
            DB::raw('SUM(usage_count) as total_usage'),
            DB::raw('AVG(response_time) as avg_response_time'),
            DB::raw('AVG(usage_count) as avg_reuse_count')
        )
        ->groupBy(DB::raw("JSON_EXTRACT(context_metadata, '$.feature')"))
        ->get();
    
    if ($stats->count() > 0) {
        echo "\n   Cache Statistics by Feature:\n";
        echo "   " . str_repeat("-", 80) . "\n";
        echo "   Feature      | Entries | Total Uses | Avg Response | Avg Reuse | Hit Rate\n";
        echo "   " . str_repeat("-", 80) . "\n";
        
        foreach ($stats as $stat) {
            $feature = str_pad(trim($stat->feature ?? 'unknown', '"'), 12);
            $entries = str_pad($stat->total_entries ?? 0, 7);
            $totalUses = str_pad($stat->total_usage ?? 0, 10);
            $avgTime = str_pad(number_format($stat->avg_response_time ?? 0, 2) . 's', 12);
            $avgReuse = str_pad(number_format($stat->avg_reuse_count ?? 0, 2), 9);
            
            // Calculate hit rate: (total_uses - entries) / total_uses * 100
            $hitRate = 0;
            if ($stat->total_usage > 0) {
                $hitRate = (($stat->total_usage - $stat->total_entries) / $stat->total_usage) * 100;
            }
            $hitRateStr = number_format($hitRate, 1) . '%';
            
            echo "   $feature | $entries | $totalUses | $avgTime | $avgReuse | $hitRateStr\n";
        }
        echo "   " . str_repeat("-", 80) . "\n";
        echo "\n✅ PASS: Cache statistics calculated\n\n";
    } else {
        echo "   ⚠️  No statistics available yet\n\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
}

// Test 6: Check AICacheService availability
echo "Test 6: Checking AICacheService...\n";
try {
    $cacheService = app(\App\Services\AICacheService::class);
    echo "✅ PASS: AICacheService can be instantiated\n\n";
} catch (\Exception $e) {
    echo "❌ FAIL: AICacheService error: " . $e->getMessage() . "\n\n";
}

// Test 7: Check Controller integration
echo "Test 7: Checking Controller integration...\n";
try {
    // Check if controllers exist and have aiPrompt method
    $controllers = [
        'LaporanArtefakController' => \App\Http\Controllers\GKM\LaporanArtefakController::class,
        'LaporanKuesioneController' => \App\Http\Controllers\GKM\LaporanKuesioneController::class,
    ];
    
    $allPassed = true;
    foreach ($controllers as $name => $class) {
        if (class_exists($class)) {
            if (method_exists($class, 'aiPrompt')) {
                echo "   ✅ $name: aiPrompt method exists\n";
            } else {
                echo "   ❌ $name: aiPrompt method NOT found\n";
                $allPassed = false;
            }
        } else {
            echo "   ❌ $name: Controller class NOT found\n";
            $allPassed = false;
        }
    }
    
    echo "\n";
    if ($allPassed) {
        echo "✅ PASS: All controllers have aiPrompt method\n\n";
    } else {
        echo "❌ FAIL: Some controllers are missing\n\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
}

// Final Summary
echo "==============================================\n";
echo "  TEST SUMMARY\n";
echo "==============================================\n\n";

$totalEntries = DB::table('ai_response_cache')->count();
$artefakEntries = DB::table('ai_response_cache')
    ->whereRaw("JSON_EXTRACT(context_metadata, '$.feature') = 'artefak'")
    ->count();
$kuesioneEntries = DB::table('ai_response_cache')
    ->whereRaw("JSON_EXTRACT(context_metadata, '$.feature') = 'kuesioner'")
    ->count();

echo "📊 Current Status:\n";
echo "   - Total cache entries: $totalEntries\n";
echo "   - Artefak entries: $artefakEntries\n";
echo "   - Kuesioner entries: $kuesioneEntries\n\n";

if ($artefakEntries > 0 && $kuesioneEntries > 0) {
    echo "✅ INTEGRATION SUCCESS!\n";
    echo "   Both Laporan Artefak and Laporan Kuesioner are connected to ai_response_cache\n\n";
} elseif ($artefakEntries > 0 || $kuesioneEntries > 0) {
    echo "⚠️  PARTIAL SUCCESS\n";
    echo "   One feature is connected, waiting for the other to receive first request\n\n";
} else {
    echo "⚠️  WAITING FOR FIRST REQUEST\n";
    echo "   Integration code is ready, but no cache entries yet\n";
    echo "   Try using the AI Assistant features to generate first cache entries\n\n";
}

echo "📚 Next Steps:\n";
echo "   1. Test Laporan Artefak AI Assistant with a sample prompt\n";
echo "   2. Test Laporan Kuesioner AI Assistant with a sample prompt\n";
echo "   3. Check Model Evaluation Dashboard: /gjm/model-evaluation\n";
echo "   4. Monitor cache hit rate and performance improvements\n\n";

echo "==============================================\n";
echo "  Test completed at " . now()->format('Y-m-d H:i:s') . "\n";
echo "==============================================\n\n";

exit(0);
