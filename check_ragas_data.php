<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING RAGAS EVALUATION DATA ===\n\n";

// Check total records
$total = \App\Models\RAGASEvaluationTest::count();
echo "Total records in ragas_evaluation_tests: {$total}\n";

// Check evaluated records (has_been_evaluated = true)
$evaluated = \App\Models\RAGASEvaluationTest::evaluated()->count();
echo "Evaluated records: {$evaluated}\n\n";

if ($evaluated > 0) {
    echo "=== DATA SAMPLE ===\n";
    $tests = \App\Models\RAGASEvaluationTest::evaluated()->take(3)->get();
    
    foreach ($tests as $test) {
        echo "ID: {$test->id}\n";
        echo "Question: " . substr($test->question, 0, 50) . "...\n";
        echo "Kategori: {$test->kategori}\n";
        echo "Faithfulness: {$test->faithfulness}\n";
        echo "Hallucination: {$test->hallucination_rate}\n";
        echo "Cache Key: " . ($test->cache_key ? 'YES' : 'NO') . "\n";
        echo "---\n";
    }
    
    echo "\n=== KATEGORI DISTRIBUTION ===\n";
    $categories = \App\Models\RAGASEvaluationTest::evaluated()
        ->select('kategori', \DB::raw('count(*) as total'))
        ->groupBy('kategori')
        ->get();
    
    foreach ($categories as $cat) {
        echo "{$cat->kategori}: {$cat->total} tests\n";
    }
    
    echo "\n=== AVERAGE METRICS ===\n";
    $avg = \App\Models\RAGASEvaluationTest::evaluated()
        ->selectRaw('
            AVG(faithfulness) as avg_faith,
            AVG(hallucination_rate) as avg_hallu,
            AVG(context_precision) as avg_prec,
            AVG(context_recall) as avg_recall,
            AVG(f1_score) as avg_f1,
            AVG(answer_relevancy) as avg_ans_rel,
            AVG(context_relevancy) as avg_ctx_rel
        ')
        ->first();
    
    echo "Faithfulness: " . round($avg->avg_faith * 100, 2) . "%\n";
    echo "Hallucination: " . round($avg->avg_hallu * 100, 2) . "%\n";
    echo "Context Precision: " . round($avg->avg_prec * 100, 2) . "%\n";
    echo "Context Recall: " . round($avg->avg_recall * 100, 2) . "%\n";
    echo "F1 Score: " . round($avg->avg_f1 * 100, 2) . "%\n";
    echo "Answer Relevancy: " . round($avg->avg_ans_rel * 100, 2) . "%\n";
    echo "Context Relevancy: " . round($avg->avg_ctx_rel * 100, 2) . "%\n";
    
    $ragasScore = ($avg->avg_faith + $avg->avg_ans_rel + $avg->avg_recall + $avg->avg_prec + $avg->avg_ctx_rel) / 5 * 100;
    echo "\nRAGAS Score: " . round($ragasScore, 2) . "%\n";
    
    echo "\n=== DATA SOURCE CHECK ===\n";
    $withCache = \App\Models\RAGASEvaluationTest::evaluated()->whereNotNull('cache_key')->count();
    $withoutCache = \App\Models\RAGASEvaluationTest::evaluated()->whereNull('cache_key')->count();
    
    echo "With cache_key (from MongoDB): {$withCache}\n";
    echo "Without cache_key (seeder): {$withoutCache}\n";
} else {
    echo "No evaluated data found!\n";
}

echo "\n=== KNOWLEDGE BASE COMPOSITION ===\n";
$templateCount = \DB::table('template_laporan')->where('is_active', true)->count();
$chunksCount = \DB::table('document_chunks')->count();
$gjmCount = \DB::table('laporan_gjm')->count();
$gkmCount = \DB::table('laporan_gkm')->count();
$kuesioneCount = \DB::table('kuesioner_uploads')->count();
$rpsMonCount = \DB::table('rps_monitoring_snapshots')->count();
$perkuliahanMonCount = \DB::table('perkuliahan_monitoring_snapshots')->count();

echo "Template Laporan: {$templateCount}\n";
echo "Document Chunks: {$chunksCount}\n";
echo "Laporan GJM: {$gjmCount}\n";
echo "Laporan GKM: {$gkmCount}\n";
echo "Kuesioner: {$kuesioneCount}\n";
echo "RPS Monitoring: {$rpsMonCount}\n";
echo "Perkuliahan Monitoring: {$perkuliahanMonCount}\n";

$totalRealData = $templateCount + $chunksCount + $gjmCount + $gkmCount + $kuesioneCount + $rpsMonCount + $perkuliahanMonCount;
echo "\nTotal Real Documents: {$totalRealData}\n";

echo "\n=== DONE ===\n";
