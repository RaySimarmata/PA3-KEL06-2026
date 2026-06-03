<?php
// Quick fix: Skip AI generation and create basic report
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaporanBulanan;
use App\Services\LaporanKuesioneService;
use App\Jobs\GenerateLaporanBulananJob;
use Illuminate\Support\Facades\Log;

// Find and fix the stuck report
$report = LaporanBulanan::where('status', 'processing')->first() ?? LaporanBulanan::find(47);

if (!$report) {
    echo "No report found.\n";
    exit(1);
}

echo "Fixing Report #" . $report->id . "\n";
echo "Current status: " . $report->status . "\n\n";

try {
    // Step 1: Collect and aggregate data (fast path)
    $service = app(LaporanKuesioneService::class);
    
    echo "Collecting kuesioner data...\n";
    $kuesioneList = $service->collectKuesioneData($report->periode, $report->prodi_id);
    $aggregatedData = $service->aggregateStatistik($kuesioneList);
    
    echo "Aggregated " . $aggregatedData['total_kuesioner'] . " questionnaires\n";
    echo "Total responden: " . $aggregatedData['total_responden'] . "\n";
    echo "Satisfaction index: " . number_format($aggregatedData['index_kepuasan_rata_rata'], 2) . "\n\n";
    
    // Step 2: Generate basic Word document (without AI)
    echo "Generating Word document (basic template)...\n";
    $job = new GenerateLaporanBulananJob($report->id);
    
    // Create a minimal hasilLaporan to bypass AI
    $hasilLaporan = [
        'content_type' => 'basic',
        'metadata' => [
            'periode' => $report->periode,
            'prodi' => $report->prodi->nama_prodi ?? 'Unknown',
            'generated_at' => date('Y-m-d H:i:s'),
        ],
        'statistik_utama' => [
            'total_kuesioner' => $aggregatedData['total_kuesioner'],
            'total_responden' => $aggregatedData['total_responden'],
            'index_kepuasan_rata_rata' => number_format($aggregatedData['index_kepuasan_rata_rata'], 2),
            'persen_kepuasan_rata_rata' => number_format($aggregatedData['persen_kepuasan_rata_rata'] ?? 0, 2),
        ],
    ];
    
    // Try to generate Word doc
    // Using reflection to access private method
    $reflection = new \ReflectionClass($job);
    $method = $reflection->getMethod('generateWordDocument');
    $method->setAccessible(true);
    
    $wordPath = $method->invoke($job, $report, $hasilLaporan);
    echo "Word document created: $wordPath\n\n";
    
    // Step 3: Update report status
    echo "Updating report status...\n";
    $report->update([
        'file_word' => $wordPath,
        'status' => 'completed',
        'total_kuesioner' => $aggregatedData['total_kuesioner'],
        'total_responden' => $aggregatedData['total_responden'],
        'index_kepuasan_rata_rata' => $aggregatedData['index_kepuasan_rata_rata'],
        'persen_kepuasan_rata_rata' => $aggregatedData['persen_kepuasan_rata_rata'] ?? 0,
        'hasil_laporan' => json_encode($hasilLaporan),
    ]);
    
    echo "✓ Report #" . $report->id . " is now COMPLETED\n";
    echo "  Status: " . $report->status . "\n";
    echo "  File: " . $report->file_word . "\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    
    // Mark as error
    $report->update([
        'status' => 'error',
        'error_message' => substr($e->getMessage(), 0, 500),
    ]);
    
    exit(1);
}

echo "\nReport ready for download!\n";
