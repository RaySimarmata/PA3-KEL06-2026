<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaporanBulanan;

$report = LaporanBulanan::whereIn('status', ['processing', 'pending', 'completed'])->orderBy('id', 'desc')->first();
if (!$report) {
    echo "No recent reports found.\n";
    exit(1);
}

echo "ID: " . $report->id . "\n";
echo "Status: " . $report->status . "\n";
echo "Periode: " . $report->periode . "\n";
echo "Prodi ID: " . $report->prodi_id . "\n";
echo "Template ID: " . $report->template_id . "\n";
echo "File Word: " . ($report->file_word ?? '[null]') . "\n";

echo "\nIf file exists, verifying physical path...\n";
$filePath = $report->file_word ? storage_path('app/' . $report->file_word) : null;
if ($filePath) {
    echo "Resolved path: $filePath\n";
    echo "Exists: " . (file_exists($filePath) ? 'yes' : 'no') . "\n";
    echo "Readable: " . ($filePath && is_readable($filePath) ? 'yes' : 'no') . "\n";
    if (file_exists($filePath)) {
        echo "Size: " . filesize($filePath) . " bytes\n";
    }
}
