<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaporanBulanan;

$report = LaporanBulanan::find(47);

echo "Report #47 Status Check:\n";
echo "========================\n";
echo "ID: " . $report->id . "\n";
echo "Status: " . $report->status . "\n";
echo "Periode: " . $report->periode . "\n";
echo "Prodi: " . ($report->prodi->nama_prodi ?? 'Unknown') . "\n\n";

echo "Statistics:\n";
echo "  Total Kuesioner: " . $report->total_kuesioner . "\n";
echo "  Total Responden: " . $report->total_responden . "\n";
echo "  Index Kepuasan: " . number_format($report->index_kepuasan_rata_rata, 2) . "\n";
echo "  Persen Kepuasan: " . number_format($report->persen_kepuasan_rata_rata, 2) . "%\n\n";

echo "File Path: " . $report->file_word . "\n";

// Check if file exists
$filePath = storage_path('app/' . $report->file_word);
if (file_exists($filePath)) {
    echo "File Size: " . filesize($filePath) . " bytes\n";
    echo "File Readable: " . (is_readable($filePath) ? 'YES' : 'NO') . "\n\n";
    echo "✓ File exists and ready for download!\n";
} else {
    echo "❌ File NOT found at: $filePath\n";
}
