<?php

/**
 * Test Script: Verify AI Evaluation Tracking Implementation
 * 
 * Purpose: Check if ai_evaluation_tests table is receiving data
 * Run: php test_evaluation_tracking.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AIEvaluationTest;
use App\Models\AIEvaluationResult;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  AI EVALUATION TRACKING - STATUS CHECK\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

try {
    // 1. Check total evaluation tests
    $totalTests = AIEvaluationTest::count();
    echo "✓ Total AI Evaluation Tests: {$totalTests}\n";
    echo "\n";

    // 2. Check by feature
    echo "Tests by Feature:\n";
    $features = ['triwulan', 'semester', 'vmts', 'artefak'];
    foreach ($features as $feature) {
        $count = AIEvaluationTest::where('feature', $feature)->count();
        echo "  - {$feature}: {$count} tests\n";
    }
    echo "\n";

    // 3. Show recent tests (last 10)
    echo "Recent Tests (Last 10):\n";
    $recentTests = AIEvaluationTest::orderBy('created_at', 'desc')
        ->limit(10)
        ->get(['id', 'test_name', 'feature', 'status', 'created_at']);

    if ($recentTests->count() > 0) {
        foreach ($recentTests as $test) {
            $date = $test->created_at->format('Y-m-d H:i:s');
            echo "  [{$test->id}] {$test->test_name}\n";
            echo "      Feature: {$test->feature} | Status: {$test->status} | Date: {$date}\n";
        }
    } else {
        echo "  No tests found.\n";
        echo "  → Please generate a laporan using AI Assistant to create test data.\n";
    }
    echo "\n";

    // 4. Check evaluation results
    $totalResults = AIEvaluationResult::count();
    echo "✓ Total AI Evaluation Results: {$totalResults}\n";
    
    if ($totalResults > 0) {
        echo "\nRecent Evaluation Results:\n";
        $recentResults = AIEvaluationResult::orderBy('evaluation_date', 'desc')
            ->limit(5)
            ->get(['id', 'evaluation_name', 'feature', 'total_ai_tests', 'evaluation_date']);
        
        foreach ($recentResults as $result) {
            $date = $result->evaluation_date->format('Y-m-d');
            echo "  [{$result->id}] {$result->evaluation_name}\n";
            echo "      Feature: {$result->feature} | Tests: {$result->total_ai_tests} | Date: {$date}\n";
        }
    }
    echo "\n";

    // 5. Tests by status
    echo "Tests by Status:\n";
    $statuses = ['pending', 'evaluated', 'failed'];
    foreach ($statuses as $status) {
        $count = AIEvaluationTest::where('status', $status)->count();
        echo "  - {$status}: {$count} tests\n";
    }
    echo "\n";

    // 6. Summary & Recommendations
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  SUMMARY\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    
    if ($totalTests == 0) {
        echo "\n⚠️  WARNING: No evaluation tests found!\n\n";
        echo "This means either:\n";
        echo "1. No AI generation has been done since the update\n";
        echo "2. The implementation is not working correctly\n\n";
        echo "NEXT STEPS:\n";
        echo "→ Generate a laporan (Triwulan/Semester/VMTS/Artefak)\n";
        echo "→ Run this script again to verify data is being saved\n";
        echo "→ Check Laravel logs for any errors\n";
    } else {
        echo "\n✓ Evaluation tracking is working!\n\n";
        echo "Data is being saved to ai_evaluation_tests table.\n";
        
        $pendingCount = AIEvaluationTest::where('status', 'pending')->count();
        if ($pendingCount > 0) {
            echo "\nOPTIONAL NEXT STEPS:\n";
            echo "→ You have {$pendingCount} pending tests\n";
            echo "→ These can be manually evaluated for better insights\n";
            echo "→ Or generate evaluation results using AIEvaluationService\n";
        }
        
        if ($totalResults == 0) {
            echo "\nRECOMMENDATION:\n";
            echo "→ Generate evaluation results to see aggregate metrics\n";
            echo "→ Use: AIEvaluationService::generateEvaluationResult()\n";
        }
    }
    
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}

exit(0);
