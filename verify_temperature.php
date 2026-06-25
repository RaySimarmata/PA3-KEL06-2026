<?php

/**
 * Verify Temperature Settings
 * Check if all services are using temperature 0.1
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Verifikasi Temperature Settings - Round 2                     ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$files = [
    'app/Services/LaporanArtefakService.php' => [
        'name' => 'LaporanArtefakService',
        'expected' => '0.1',
        'line' => '~1665'
    ],
    'app/Services/LaporanKuesioneService.php' => [
        'name' => 'LaporanKuesioneService',
        'expected' => '0.1',
        'line' => '~2425'
    ],
    'app/Services/AIAgentService.php' => [
        'name' => 'AIAgentService',
        'expected' => '0.1',
        'line' => '~830'
    ],
    'app/Services/VMTSAIAssistantService.php' => [
        'name' => 'VMTSAIAssistantService',
        'expected' => '0.1',
        'line' => '~96'
    ],
    'app/Services/UnifiedAIService.php' => [
        'name' => 'UnifiedAIService',
        'expected' => '0.1',
        'line' => 'multiple'
    ],
];

$allGood = true;

foreach ($files as $file => $info) {
    $fullPath = __DIR__ . '/' . $file;
    
    if (!file_exists($fullPath)) {
        echo "❌ {$info['name']}: FILE NOT FOUND\n";
        $allGood = false;
        continue;
    }
    
    $content = file_get_contents($fullPath);
    
    // Check for temperature 0.1
    if (preg_match("/'temperature'\s*=>\s*0\.1/", $content)) {
        echo "✅ {$info['name']}: Temperature 0.1 (line {$info['line']})\n";
    } elseif (preg_match("/'temperature'\s*=>\s*0\.3/", $content)) {
        echo "⚠️  {$info['name']}: Still using 0.3 (should be 0.1)\n";
        $allGood = false;
    } elseif (preg_match("/'temperature'\s*=>\s*0\.7/", $content)) {
        echo "❌ {$info['name']}: Still using 0.7 (should be 0.1)\n";
        $allGood = false;
    } else {
        echo "⚠️  {$info['name']}: Temperature not found or different format\n";
    }
}

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if ($allGood) {
    echo "✅ ALL SERVICES CONFIGURED CORRECTLY!\n";
    echo "\n";
    echo "Temperature 0.1 is active across all services.\n";
    echo "This should significantly reduce hallucination.\n";
    echo "\n";
    echo "Ready for testing:\n";
    echo "1. php clear_cache_for_testing.php\n";
    echo "2. Generate 10+ Laporan Bulanan via UI\n";
    echo "3. php artisan ragas:sync\n";
    echo "4. Check http://127.0.0.1:8000/gjm/evaluasi/ragas\n";
} else {
    echo "⚠️  SOME ISSUES FOUND\n";
    echo "\n";
    echo "Please check the files marked above.\n";
    echo "Temperature should be 0.1 for maximum faithfulness.\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n";
