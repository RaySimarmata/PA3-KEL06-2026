<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaporanBulanan;
use App\Jobs\GenerateLaporanBulananJob;
use Illuminate\Support\Facades\Log;

// Find the stuck report
$report = LaporanBulanan::where('status', 'processing')->first();

if (!$report) {
    echo "No processing report found. Looking for latest...\n";
    $report = LaporanBulanan::latest()->first();
}

if (!$report) {
    echo "No reports found at all.\n";
    exit(1);
}

echo "Testing report ID {$report->id}\n";
echo "Current status: {$report->status}\n";
echo "Periode: {$report->periode}\n";
echo "Template: {$report->template_laporan_id}\n\n";

try {
    echo "Attempting to manually run GenerateLaporanBulananJob...\n";
    $job = new GenerateLaporanBulananJob($report);
    $job->handle(app(\App\Services\LaporanKuesioneService::class));
    
    echo "Job completed!\n";
    
    $report->refresh();
    echo "New status: {$report->status}\n";
    echo "File word: {$report->file_word}\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
