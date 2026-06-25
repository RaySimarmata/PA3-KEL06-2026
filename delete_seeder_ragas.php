<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\RAGASEvaluationTest;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  DELETE SEEDER DATA - Keep Only Real MongoDB Data\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$total = RAGASEvaluationTest::count();
$seeder = RAGASEvaluationTest::whereNull('cache_key')->count();
$real = RAGASEvaluationTest::whereNotNull('cache_key')->count();

echo "Current Status:\n";
echo "  Total entries: {$total}\n";
echo "  Seeder data (no cache_key): {$seeder}\n";
echo "  Real data (has cache_key): {$real}\n\n";

if ($seeder > 0) {
    echo "Deleting {$seeder} seeder data entries...\n";
    $deleted = RAGASEvaluationTest::whereNull('cache_key')->delete();
    echo "✅ Deleted {$deleted} seeder entries\n\n";
    
    $remaining = RAGASEvaluationTest::count();
    echo "✅ Success! Remaining: {$remaining} real data from MongoDB\n";
} else {
    echo "✅ No seeder data found. All data is real from MongoDB!\n";
}

echo "\n";
