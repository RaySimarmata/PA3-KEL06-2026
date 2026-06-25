<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== RESET RAGAS EVALUATION DATA ===\n\n";

// Step 1: Show current data
echo "📊 CURRENT DATA:\n";
echo "================\n";
$currentCount = \App\Models\RAGASEvaluationTest::count();
echo "Total records in ragas_evaluation_tests: {$currentCount}\n";

if ($currentCount > 0) {
    $avgFaith = \App\Models\RAGASEvaluationTest::avg('faithfulness') * 100;
    $avgHallu = \App\Models\RAGASEvaluationTest::avg('hallucination_rate') * 100;
    
    echo sprintf("Current Faithfulness:  %.2f%% %s\n", $avgFaith, $avgFaith >= 80 ? '✅' : '❌');
    echo sprintf("Current Hallucination: %.2f%% %s\n", $avgHallu, $avgHallu <= 15 ? '✅' : '❌');
}

echo "\n";

// Step 2: Delete all records
echo "🗑️  DELETING ALL RECORDS...\n";
$deleted = \App\Models\RAGASEvaluationTest::truncate();
echo "✅ All records deleted from ragas_evaluation_tests\n\n";

// Step 3: Check MongoDB cache
echo "📦 CHECKING MONGODB CACHE:\n";
echo "==========================\n";
$cacheCount = \App\Models\AIResponseCacheMongo::count();
echo "Total entries in MongoDB cache: {$cacheCount}\n";

if ($cacheCount > 0) {
    echo "\nCache entries by feature:\n";
    $byFeature = \App\Models\AIResponseCacheMongo::raw(function($collection) {
        return $collection->aggregate([
            [
                '$group' => [
                    '_id' => '$context_metadata.feature',
                    'count' => ['$sum' => 1]
                ]
            ],
            ['$sort' => ['count' => -1]]
        ]);
    });
    
    foreach ($byFeature as $item) {
        $feature = $item->_id ?? 'unknown';
        $count = $item->count;
        echo "  - {$feature}: {$count} entries\n";
    }
}

echo "\n";

// Step 4: Run sync command
echo "🔄 SYNCING FROM MONGODB CACHE...\n";
echo "=================================\n";
echo "Running: php artisan ragas:sync-from-cache\n\n";

// Call the sync command
\Illuminate\Support\Facades\Artisan::call('ragas:sync-from-cache');
$output = \Illuminate\Support\Facades\Artisan::output();
echo $output;

echo "\n";

// Step 5: Show new data
echo "📊 NEW DATA AFTER SYNC:\n";
echo "=======================\n";
$newCount = \App\Models\RAGASEvaluationTest::count();
echo "Total records: {$newCount}\n";

if ($newCount > 0) {
    echo "\nNew distribution by kategori:\n";
    $categories = \App\Models\RAGASEvaluationTest::evaluated()
        ->select('kategori', \DB::raw('count(*) as total'))
        ->groupBy('kategori')
        ->get();
    
    foreach ($categories as $cat) {
        echo "  - {$cat->kategori}: {$cat->total} tests\n";
    }
    
    echo "\n📈 NEW METRICS:\n";
    $avgFaith = \App\Models\RAGASEvaluationTest::avg('faithfulness') * 100;
    $avgHallu = \App\Models\RAGASEvaluationTest::avg('hallucination_rate') * 100;
    $avgPrec = \App\Models\RAGASEvaluationTest::avg('context_precision') * 100;
    $avgRecall = \App\Models\RAGASEvaluationTest::avg('context_recall') * 100;
    $avgF1 = \App\Models\RAGASEvaluationTest::avg('f1_score') * 100;
    $avgAnsRel = \App\Models\RAGASEvaluationTest::avg('answer_relevancy') * 100;
    $avgCtxRel = \App\Models\RAGASEvaluationTest::avg('context_relevancy') * 100;
    
    $ragasScore = (\App\Models\RAGASEvaluationTest::avg('faithfulness') + 
                   \App\Models\RAGASEvaluationTest::avg('answer_relevancy') + 
                   \App\Models\RAGASEvaluationTest::avg('context_recall') + 
                   \App\Models\RAGASEvaluationTest::avg('context_precision') + 
                   \App\Models\RAGASEvaluationTest::avg('context_relevancy')) / 5 * 100;
    
    echo sprintf("Faithfulness:      %.2f%% %s\n", $avgFaith, getBadge($avgFaith, false));
    echo sprintf("Hallucination:     %.2f%% %s\n", $avgHallu, getBadge($avgHallu, true));
    echo sprintf("Context Precision: %.2f%% %s\n", $avgPrec, getBadge($avgPrec, false));
    echo sprintf("Context Recall:    %.2f%% %s\n", $avgRecall, getBadge($avgRecall, false));
    echo sprintf("F1 Score:          %.2f%% %s\n", $avgF1, getBadge($avgF1, false));
    echo sprintf("Answer Relevancy:  %.2f%% %s\n", $avgAnsRel, getBadge($avgAnsRel, false));
    echo sprintf("Context Relevancy: %.2f%% %s\n", $avgCtxRel, getBadge($avgCtxRel, false));
    echo sprintf("\nRAGAS Score:       %.2f%% %s\n", $ragasScore, getBadge($ragasScore, false));
}

echo "\n";
echo "✅ RESET AND RESYNC COMPLETED!\n";
echo "\n";
echo "🌐 View results on web:\n";
echo "http://127.0.0.1:8000/gjm/evaluasi/ragas\n";
echo "\n";

function getBadge($value, $reverse = false) {
    if ($reverse) {
        // For hallucination, lower is better
        if ($value <= 10) return '🟢 EXCELLENT';
        if ($value <= 15) return '🔵 GOOD';
        if ($value <= 20) return '🟡 FAIR';
        return '🔴 POOR';
    } else {
        // For other metrics, higher is better
        if ($value >= 90) return '🟢 EXCELLENT';
        if ($value >= 80) return '🔵 GOOD';
        if ($value >= 70) return '🟡 FAIR';
        return '🔴 POOR';
    }
}
