<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaporanBulanan;
use Illuminate\Support\Facades\Artisan;

// Temporarily modify env
putenv('TEMPLATE_USE_PLACEHOLDERS=false');
putenv('VECTOR_DB_ENABLED=false');

$report = LaporanBulanan::find(47);
echo "Testing Simple RAG generation for Report #47\n";
echo "Periode: {$report->periode}\n";
echo "Prodi: {$report->prodi_id}\n\n";

// Reset status first
$report->update(['status' => 'pending', 'file_word' => null, 'error_message' => null]);
echo "Reset status to pending\n\n";

try {
    // Test service collection and aggregation (the fast parts)
    $service = app(\App\Services\LaporanKuesioneService::class);
    echo "Step 1: Collecting kuesioner data...\n";
    $kuesioneList = $service->collectKuesioneData($report->periode, $report->prodi_id);
    echo "  ✓ Collected " . $kuesioneList->count() . " documents\n\n";
    
    echo "Step 2: Aggregating statistics...\n";
    $aggregatedData = $service->aggregateStatistik($kuesioneList);
    echo "  ✓ Total kuesioner: " . ($aggregatedData['total_kuesioner'] ?? 0) . "\n";
    echo "  ✓ Total responden: " . ($aggregatedData['total_responden'] ?? 0) . "\n";
    echo "  ✓ Index kepuasan: " . ($aggregatedData['index_kepuasan_rata_rata'] ?? 0) . "\n\n";
    
    echo "Step 3: Calling Simple RAG generation (should be faster)...\n";
    // This is the Simple RAG method
    $result = $service->generateLaporan($report->periode, $report->prodi_id, $report->template_id);
    
    echo "  ✓ Generated result\n";
    echo "  ✓ Result type: " . (is_array($result) ? 'array' : get_class($result)) . "\n";
    if (is_array($result)) {
        echo "  ✓ Result keys: " . implode(', ', array_keys($result)) . "\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "This is expected if the method doesn't exist.\n";
    echo "\nAvailable generation methods:\n";
    echo "  - generateLaporan()\n";
    echo "  - generateLaporanAdvanced()\n";
    echo "  - generateLaporanWithTemplateStructure()\n";
    echo "  - generateLaporanWithPlaceholders() [HANGS]\n";
}
