<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaporanBulanan;

// Check report
$report = LaporanBulanan::where('status', 'processing')->first();
echo "Report ID: {$report->id}\n";
echo "Template ID: {$report->template_id}\n";
echo "Template Laporan ID: {$report->template_laporan_id}\n";
echo "Periode: {$report->periode}\n";
echo "Prodi ID: {$report->prodi_id}\n\n";

// Check what happens in service
$service = app(\App\Services\LaporanKuesioneService::class);

echo "Attempting to call generateLaporanWithPlaceholders with null template...\n";
try {
    $result = $service->generateLaporanWithPlaceholders($report->periode, $report->prodi_id, $report->template_id);
    echo "Result type: " . (is_array($result) ? 'array' : get_class($result)) . "\n";
    print_r($result);
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
