<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaporanBulanan;
use App\Jobs\GenerateLaporanBulananJob;

// Find the stuck report
$report = LaporanBulanan::where('status', 'processing')->first() ?? LaporanBulanan::find(47);

if (!$report) {
    echo "No report found.\n";
    exit(1);
}

echo "Report ID: {$report->id}, Status: {$report->status}, Template: {$report->template_id}\n\n";

// Test just the collection part
try {
    echo "Step 1: collectKuesioneData\n";
    $service = app(\App\Services\LaporanKuesioneService::class);
    $kuesioneList = $service->collectKuesioneData($report->periode, $report->prodi_id);
    echo "  Result type: " . get_class($kuesioneList) . "\n";
    echo "  Count: " . $kuesioneList->count() . "\n";
    
    if ($kuesioneList->isEmpty()) {
        echo "  ERROR: No kuesioner data collected!\n";
        exit(1);
    }
    
    echo "\nStep 2: aggregateStatistik\n";
    $aggregatedData = $service->aggregateStatistik($kuesioneList);
    echo "  Result type: " . (is_array($aggregatedData) ? 'array' : get_class($aggregatedData)) . "\n";
    echo "  Keys: " . implode(', ', array_keys($aggregatedData)) . "\n";
    
    echo "\nStep 3: buildContext\n";
    $prodi = $report->prodi;
    $context = $service->buildContext($aggregatedData, $report->periode, $prodi);
    echo "  Result type: " . gettype($context) . "\n";
    echo "  Length: " . strlen($context) . "\n";
    
    echo "\nStep 4: Checking service method mode\n";
    $usePlaceholders = env('TEMPLATE_USE_PLACEHOLDERS', true);
    echo "  Use placeholders: " . ($usePlaceholders ? 'yes' : 'no') . "\n";
    
    if ($usePlaceholders) {
        echo "\nStep 5: generateLaporanWithPlaceholders (will timeout if API hangs)\n";
        // Don't actually call this - it will hang
        echo "  (Skipping to avoid timeout)\n";
    }
    
    echo "\nStep 6: Word generation\n";
    echo "  Method: generateWordDocumentTemplateProcessor\n";
    
} catch (\Exception $e) {
    echo "ERROR at step: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "File: " . $e->getFile() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
