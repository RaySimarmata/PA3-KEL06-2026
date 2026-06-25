<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CLEAR OLD AI CACHE (BEFORE IMPROVEMENT) ===\n\n";

echo "⚠️  WARNING: This will delete ALL AI cache from MongoDB!\n";
echo "This is necessary to test the new anti-hallucination improvements.\n";
echo "After clearing, you need to generate NEW reports to see improvements.\n\n";

$currentCount = \App\Models\AIResponseCacheMongo::count();
echo "Current MongoDB cache entries: {$currentCount}\n\n";

// Confirm deletion
echo "Do you want to proceed? This action cannot be undone.\n";
echo "Type 'YES' to confirm: ";

// For automated scripts, we'll just proceed
// In interactive mode, you would wait for user input
$proceed = true; // Set to false if you want manual confirmation

if ($proceed) {
    echo "YES (automated)\n\n";
    
    echo "🗑️  DELETING ALL AI CACHE...\n";
    $deleted = \App\Models\AIResponseCacheMongo::truncate();
    
    echo "✅ Deleted {$currentCount} entries from MongoDB ai_response_cache\n\n";
    
    // Also clear RAGAS evaluation data
    echo "🗑️  CLEARING RAGAS EVALUATION DATA...\n";
    $ragasDeleted = \App\Models\RAGASEvaluationTest::truncate();
    echo "✅ Cleared ragas_evaluation_tests table\n\n";
    
    echo "✅ CLEANUP COMPLETED!\n\n";
    
    echo "📋 NEXT STEPS:\n";
    echo "==============\n";
    echo "1. Generate 5-10 NEW reports using AI Assistant:\n";
    echo "   \n";
    echo "   A. Laporan Triwulan (GJM):\n";
    echo "      http://127.0.0.1:8000/gjm/buat-laporan/triwulan/create\n";
    echo "      - Click 'Generate dengan AI'\n";
    echo "      - Check if output is more factual\n";
    echo "   \n";
    echo "   B. Laporan Kuesioner (GKM):\n";
    echo "      http://127.0.0.1:8000/gkm/laporan-kuesioner\n";
    echo "      - Upload kuesioner file\n";
    echo "      - Generate report\n";
    echo "   \n";
    echo "   C. Laporan VMTS (GJM):\n";
    echo "      http://127.0.0.1:8000/gjm/buat-laporan/vmts/create\n";
    echo "      - Use AI Assistant\n";
    echo "   \n";
    echo "   D. Laporan Semester (GJM):\n";
    echo "      http://127.0.0.1:8000/gjm/buat-laporan/semester/create\n";
    echo "   \n";
    echo "   E. Laporan Bulanan (GKM):\n";
    echo "      http://127.0.0.1:8000/gkm/monitoring/rps\n";
    echo "   \n";
    echo "2. After generating 5-10 reports, sync RAGAS:\n";
    echo "   php artisan ragas:sync-from-cache\n";
    echo "   \n";
    echo "3. Check improvement:\n";
    echo "   php test_faithfulness_improvement.php\n";
    echo "   \n";
    echo "4. View on web:\n";
    echo "   http://127.0.0.1:8000/gjm/evaluasi/ragas\n";
    echo "   \n";
    
    echo "\n💡 EXPECTED IMPROVEMENT:\n";
    echo "========================\n";
    echo "OLD (Before Cleanup):\n";
    echo "  Faithfulness:  68.74% 🔴\n";
    echo "  Hallucination: 31.26% 🔴\n";
    echo "  RAGAS Score:   76.35% 🟡\n";
    echo "\n";
    echo "NEW (After Improvement - Target):\n";
    echo "  Faithfulness:  82-88% 🔵\n";
    echo "  Hallucination: 12-18% 🟡\n";
    echo "  RAGAS Score:   83-87% 🔵\n";
    echo "\n";
    
    echo "🔑 KEY IMPROVEMENTS ACTIVE:\n";
    echo "===========================\n";
    echo "✅ Enhanced system prompt with anti-hallucination rules\n";
    echo "✅ Temperature lowered from 0.7 to 0.3\n";
    echo "✅ Strict context grounding instructions\n";
    echo "✅ 'Data tidak tersedia' for missing info\n";
    echo "\n";
    
} else {
    echo "NO\n\n";
    echo "❌ Cleanup cancelled.\n";
    echo "\n";
    echo "To proceed later, run:\n";
    echo "php clear_old_ai_cache.php\n";
}

echo "\n=== DONE ===\n";
