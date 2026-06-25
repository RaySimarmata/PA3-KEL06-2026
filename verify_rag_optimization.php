<?php
/**
 * Script untuk membuktikan RAG Configuration Optimization
 * Slide 7 - Verifikasi otomatis
 */

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║   RAG CONFIGURATION OPTIMIZATION - VERIFICATION SCRIPT      ║\n";
echo "║   Membuktikan Slide 7: RAG Configuration Optimization       ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Bootstrap Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\UnifiedAIService;
use App\Services\AICacheService;
use App\Services\RAGRetrievalService;
use App\Http\Controllers\GJM\ModelEvaluationController;

echo "🔍 VERIFYING PARAMETER CHANGES...\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// ============================================================
// 1. CACHE ENABLED VERIFICATION
// ============================================================
echo "1️⃣  CACHE ENABLED: No → Yes\n";
echo "───────────────────────────────────────────────────────\n";

try {
    $aiService = app(UnifiedAIService::class);
    $reflection = new ReflectionClass($aiService);
    $cacheEnabledProperty = $reflection->getProperty('cacheEnabled');
    $cacheEnabledProperty->setAccessible(true);
    $cacheEnabled = $cacheEnabledProperty->getValue($aiService);
    
    echo "📌 Source: app/Services/UnifiedAIService.php (Line 21)\n";
    echo "   Variable: private \$cacheEnabled\n";
    echo "   Value: " . ($cacheEnabled ? 'true ✅' : 'false ❌') . "\n";
    
    if ($cacheEnabled) {
        echo "   ✅ VERIFIED: Cache is ENABLED\n";
    } else {
        echo "   ❌ WARNING: Cache is DISABLED\n";
    }
} catch (Exception $e) {
    echo "   ⚠️  Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================
// 2. SIMILARITY THRESHOLD VERIFICATION
// ============================================================
echo "2️⃣  SIMILARITY THRESHOLD: 0 → 85\n";
echo "───────────────────────────────────────────────────────\n";

try {
    // Check AI Cache Service threshold
    $cacheService = app(AICacheService::class);
    $reflection = new ReflectionClass($cacheService);
    $thresholdProperty = $reflection->getProperty('defaultSimilarityThreshold');
    $thresholdProperty->setAccessible(true);
    $threshold = $thresholdProperty->getValue($cacheService);
    
    echo "📌 Source: app/Services/AICacheService.php (Line 11)\n";
    echo "   Variable: private \$defaultSimilarityThreshold\n";
    echo "   Value: " . $threshold . "\n";
    
    if ($threshold == 0.85) {
        echo "   ✅ VERIFIED: AI Cache threshold is 0.85 (85%)\n";
    } else {
        echo "   ⚠️  Current value: {$threshold} (expected 0.85)\n";
    }
    
    // Check RAG Retrieval threshold (different from cache)
    echo "\n📌 Additional: RAG Retrieval Threshold\n";
    echo "   Source: .env → RAG_SIMILARITY_THRESHOLD\n";
    $ragThreshold = env('RAG_SIMILARITY_THRESHOLD', 0.3);
    echo "   Value: {$ragThreshold} (30%)\n";
    echo "   Note: This is for document retrieval, NOT cache matching\n";
    
} catch (Exception $e) {
    echo "   ⚠️  Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================
// 3. PROMPT ENGINEERING VERIFICATION
// ============================================================
echo "3️⃣  PROMPT ENGINEERING: Basic → Advanced\n";
echo "───────────────────────────────────────────────────────\n";

try {
    $controller = new ModelEvaluationController();
    $reflection = new ReflectionClass($controller);
    
    // Get BEFORE parameters
    $beforeMethod = $reflection->getMethod('getActualBeforeParameters');
    $beforeMethod->setAccessible(true);
    $beforeParams = $beforeMethod->invoke($controller);
    
    // Get AFTER parameters
    $afterMethod = $reflection->getMethod('getActualAfterParameters');
    $afterMethod->setAccessible(true);
    $afterParams = $afterMethod->invoke($controller);
    
    echo "📌 Source: app/Http/Controllers/GJM/ModelEvaluationController.php\n\n";
    
    echo "   BEFORE (Line 565):\n";
    echo "   'prompt_engineering' => '{$beforeParams['prompt_engineering']}'\n";
    echo "   Status: " . ($beforeParams['prompt_engineering'] === 'basic' ? '✅ Confirmed BASIC' : '⚠️  Unexpected') . "\n\n";
    
    echo "   AFTER (Line 581):\n";
    echo "   'prompt_engineering' => '{$afterParams['prompt_engineering']}'\n";
    echo "   Status: " . ($afterParams['prompt_engineering'] === 'advanced with context' ? '✅ Confirmed ADVANCED' : '⚠️  Unexpected') . "\n\n";
    
    if ($beforeParams['prompt_engineering'] === 'basic' && 
        $afterParams['prompt_engineering'] === 'advanced with context') {
        echo "   ✅ VERIFIED: Prompt Engineering upgraded from Basic to Advanced\n";
    }
    
} catch (Exception $e) {
    echo "   ⚠️  Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================
// 4. ANTI-HALLUCINATION VERIFICATION
// ============================================================
echo "4️⃣  ANTI-HALLUCINATION: No → Yes\n";
echo "───────────────────────────────────────────────────────\n";

try {
    $controller = new ModelEvaluationController();
    $reflection = new ReflectionClass($controller);
    
    // Get BEFORE parameters
    $beforeMethod = $reflection->getMethod('getActualBeforeParameters');
    $beforeMethod->setAccessible(true);
    $beforeParams = $beforeMethod->invoke($controller);
    
    // Get AFTER parameters
    $afterMethod = $reflection->getMethod('getActualAfterParameters');
    $afterMethod->setAccessible(true);
    $afterParams = $afterMethod->invoke($controller);
    
    echo "📌 Source: app/Http/Controllers/GJM/ModelEvaluationController.php\n\n";
    
    echo "   BEFORE (Line 567):\n";
    echo "   'anti_hallucination' => " . ($beforeParams['anti_hallucination'] ? 'true' : 'false') . "\n";
    echo "   Status: " . (!$beforeParams['anti_hallucination'] ? '✅ Confirmed DISABLED' : '⚠️  Unexpected') . "\n\n";
    
    echo "   AFTER (Line 583):\n";
    echo "   'anti_hallucination' => " . ($afterParams['anti_hallucination'] ? 'true' : 'false') . "\n";
    echo "   Status: " . ($afterParams['anti_hallucination'] ? '✅ Confirmed ENABLED' : '⚠️  Unexpected') . "\n\n";
    
    if (!$beforeParams['anti_hallucination'] && $afterParams['anti_hallucination']) {
        echo "   ✅ VERIFIED: Anti-Hallucination measures implemented\n";
    }
    
} catch (Exception $e) {
    echo "   ⚠️  Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================
// SUMMARY
// ============================================================
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    VERIFICATION SUMMARY                     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$results = [
    'cache_enabled' => $cacheEnabled ?? false,
    'similarity_threshold' => ($threshold ?? 0) == 0.85,
    'prompt_engineering' => isset($beforeParams, $afterParams) && 
                           $beforeParams['prompt_engineering'] === 'basic' &&
                           $afterParams['prompt_engineering'] === 'advanced with context',
    'anti_hallucination' => isset($beforeParams, $afterParams) &&
                           !$beforeParams['anti_hallucination'] &&
                           $afterParams['anti_hallucination']
];

echo "┌──────────────────────────────────────────────────────┐\n";
echo "│ Parameter             │ Before  │ After  │ Status    │\n";
echo "├──────────────────────────────────────────────────────┤\n";
printf("│ %-21s │ %-7s │ %-6s │ %-9s │\n", 
    "Cache Enabled", 
    "No", 
    "Yes", 
    $results['cache_enabled'] ? "✅ PASS" : "❌ FAIL"
);
printf("│ %-21s │ %-7s │ %-6s │ %-9s │\n", 
    "Similarity Threshold", 
    "0", 
    "85", 
    $results['similarity_threshold'] ? "✅ PASS" : "❌ FAIL"
);
printf("│ %-21s │ %-7s │ %-6s │ %-9s │\n", 
    "Prompt Engineering", 
    "Basic", 
    "Adv.", 
    $results['prompt_engineering'] ? "✅ PASS" : "❌ FAIL"
);
printf("│ %-21s │ %-7s │ %-6s │ %-9s │\n", 
    "Anti Hallucination", 
    "No", 
    "Yes", 
    $results['anti_hallucination'] ? "✅ PASS" : "❌ FAIL"
);
echo "└──────────────────────────────────────────────────────┘\n\n";

$passCount = count(array_filter($results));
$totalCount = count($results);

echo "📊 VERIFICATION RESULTS: {$passCount}/{$totalCount} PASSED\n\n";

if ($passCount === $totalCount) {
    echo "✅ ✅ ✅ SLIDE 7 IS 100% ACCURATE ✅ ✅ ✅\n";
    echo "All parameter changes are correctly implemented in the code.\n";
} else {
    echo "⚠️  WARNING: Some parameters don't match the slide.\n";
    echo "Please review the failed items above.\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "📄 Full documentation: RAG_CONFIG_OPTIMIZATION_PROOF.md\n";
echo "═══════════════════════════════════════════════════════════════\n";
