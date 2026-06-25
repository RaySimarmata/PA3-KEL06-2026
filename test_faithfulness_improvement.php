<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TESTING FAITHFULNESS IMPROVEMENT ===\n\n";

// Get current RAGAS metrics BEFORE any new AI calls
echo "📊 CURRENT METRICS (Before Improvement):\n";
echo "=========================================\n";

$currentTests = \App\Models\RAGASEvaluationTest::evaluated()->get();

if ($currentTests->count() > 0) {
    $avgFaith = $currentTests->avg('faithfulness') * 100;
    $avgHallu = $currentTests->avg('hallucination_rate') * 100;
    $avgPrec = $currentTests->avg('context_precision') * 100;
    $avgRecall = $currentTests->avg('context_recall') * 100;
    $avgF1 = $currentTests->avg('f1_score') * 100;
    $avgAnsRel = $currentTests->avg('answer_relevancy') * 100;
    $avgCtxRel = $currentTests->avg('context_relevancy') * 100;
    
    $ragasScore = ($currentTests->avg('faithfulness') + 
                   $currentTests->avg('answer_relevancy') + 
                   $currentTests->avg('context_recall') + 
                   $currentTests->avg('context_precision') + 
                   $currentTests->avg('context_relevancy')) / 5 * 100;
    
    echo sprintf("Faithfulness:      %.2f%% %s\n", $avgFaith, $avgFaith >= 80 ? '✅' : '❌');
    echo sprintf("Hallucination:     %.2f%% %s\n", $avgHallu, $avgHallu <= 15 ? '✅' : '❌');
    echo sprintf("Context Precision: %.2f%% %s\n", $avgPrec, $avgPrec >= 80 ? '✅' : '⚠️');
    echo sprintf("Context Recall:    %.2f%% %s\n", $avgRecall, $avgRecall >= 80 ? '✅' : '⚠️');
    echo sprintf("F1 Score:          %.2f%% %s\n", $avgF1, $avgF1 >= 75 ? '✅' : '⚠️');
    echo sprintf("Answer Relevancy:  %.2f%% %s\n", $avgAnsRel, $avgAnsRel >= 75 ? '✅' : '⚠️');
    echo sprintf("Context Relevancy: %.2f%% %s\n", $avgCtxRel, $avgCtxRel >= 75 ? '✅' : '⚠️');
    echo sprintf("\nRAGAS Score:       %.2f%% %s\n", $ragasScore, $ragasScore >= 80 ? '✅' : '❌');
    
    echo "\n📈 BADGE STATUS:\n";
    echo "Faithfulness:  " . getBadgeStatus($avgFaith, false) . "\n";
    echo "Hallucination: " . getBadgeStatus($avgHallu, true) . "\n";
    
} else {
    echo "No test data found!\n";
}

echo "\n\n🔧 IMPROVEMENTS IMPLEMENTED:\n";
echo "============================\n";
echo "✅ Enhanced system prompt with anti-hallucination rules\n";
echo "✅ Lowered temperature from 0.7 to 0.3\n";
echo "✅ Added explicit grounding instructions\n";
echo "✅ Added factual accuracy requirements\n";

echo "\n\n📝 TESTING RECOMMENDATIONS:\n";
echo "===========================\n";
echo "1. Generate 5-10 new laporan using AI Assistant:\n";
echo "   - Visit GJM > Buat Laporan > Triwulan\n";
echo "   - Visit GKM > Laporan Kuesioner\n";
echo "   - Visit GJM > Buat Laporan > VMTS\n";
echo "\n";
echo "2. Sync RAGAS data from cache:\n";
echo "   php artisan ragas:sync-cache\n";
echo "\n";
echo "3. Check improvement on web:\n";
echo "   Visit: http://127.0.0.1:8000/gjm/evaluasi/ragas\n";
echo "\n";
echo "4. Compare metrics:\n";
echo "   php test_faithfulness_improvement.php\n";

echo "\n\n🎯 EXPECTED IMPROVEMENT:\n";
echo "========================\n";
echo "Faithfulness:  69.76% → 82-88% (+12-18%)\n";
echo "Hallucination: 30.24% → 12-18% (-12-18%)\n";
echo "RAGAS Score:   76.88% → 83-87% (+6-10%)\n";

echo "\n\n💡 ADDITIONAL TIPS:\n";
echo "===================\n";
echo "- Clear cache to ensure new AI responses: php artisan cache:clear\n";
echo "- Monitor AI responses for factual accuracy\n";
echo "- Test with prompts that have clear reference data\n";
echo "- Compare new laporan with old ones for quality\n";

echo "\n=== DONE ===\n";

function getBadgeStatus($value, $reverse = false) {
    if ($reverse) {
        // For hallucination, lower is better
        if ($value <= 10) return '🟢 EXCELLENT (Green)';
        if ($value <= 15) return '🔵 GOOD (Blue)';
        if ($value <= 20) return '🟡 FAIR (Yellow)';
        return '🔴 POOR (Red)';
    } else {
        // For other metrics, higher is better
        if ($value >= 90) return '🟢 EXCELLENT (Green)';
        if ($value >= 80) return '🔵 GOOD (Blue)';
        if ($value >= 70) return '🟡 FAIR (Yellow)';
        return '🔴 POOR (Red)';
    }
}
