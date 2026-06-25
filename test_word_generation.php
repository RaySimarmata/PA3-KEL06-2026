<?php
/**
 * Test script untuk verify Word generation dari database
 * Menguji KuesioneWordGenerationService::generateWordDocument()
 * 
 * Run: php test_word_generation.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\LaporanBulanan;
use App\Services\KuesioneWordGenerationService;
use Illuminate\Support\Facades\Log;

echo "========================================\n";
echo "TEST: Word Generation from Database\n";
echo "========================================\n\n";

// Find the most recent laporan kuesioner
$laporan = LaporanBulanan::whereNotNull('periode')
    ->latest()
    ->first();

if (!$laporan) {
    echo "❌ ERROR: No laporan found in database\n";
    echo "   Please create a laporan first via the web interface.\n";
    exit(1);
}

echo "✅ Found laporan:\n";
echo "   - ID: {$laporan->id}\n";
echo "   - Periode: {$laporan->periode}\n";
echo "   - User ID: {$laporan->user_id}\n";
echo "   - Status: {$laporan->status}\n";
echo "   - File Word: " . ($laporan->file_word ?? 'Not generated yet') . "\n\n";

// Get user and prodi info
$user = \App\Models\User::find($laporan->user_id);
$prodiNama = $user && $user->prodi ? $user->prodi->nama_prodi : 'N/A';
$prodiKode = $user && $user->prodi ? $user->prodi->kode_prodi : null;

echo "✅ User info:\n";
echo "   - Prodi: {$prodiNama}\n";
echo "   - Prodi Kode: " . ($prodiKode ?? 'N/A') . "\n\n";

// Determine semester
$periode = $laporan->periode;
$year = (int) substr($periode, 0, 4);
$month = (int) substr($periode, 5, 2);
$semester = ($month <= 6) ? 2 : 1;
$semesterText = ($semester == 1) ? 'GANJIL' : 'GENAP';

echo "✅ Semester calculation:\n";
echo "   - Year: {$year}\n";
echo "   - Month: {$month}\n";
echo "   - Semester: {$semester} ({$semesterText})\n\n";

// Query kuesioner data
echo "Querying kuesioner_uploads table...\n";
$uploads = \App\Models\KuesioneUpload::query()
    ->where('semester', $semester)
    ->when($prodiKode, function($q) use ($prodiKode) {
        $q->whereHas('user', function($uq) use ($prodiKode) {
            $uq->whereHas('prodi', function($pq) use ($prodiKode) {
                $pq->where('kode_prodi', $prodiKode);
            });
        });
    })
    ->with('user.prodi')
    ->orderBy('tingkat', 'asc')
    ->orderBy('nama_matakuliah', 'asc')
    ->get();

echo "✅ Found {$uploads->count()} kuesioner uploads\n\n";

if ($uploads->count() === 0) {
    echo "⚠️  WARNING: No kuesioner data found for this periode!\n";
    echo "   The Word document will still be generated but with empty data.\n\n";
} else {
    // Show sample data
    echo "Sample data (first 5):\n";
    foreach ($uploads->take(5) as $idx => $upload) {
        echo "   " . ($idx + 1) . ". Tingkat {$upload->tingkat} - {$upload->nama_matakuliah} - Index: {$upload->index_kepuasan}\n";
    }
    echo "\n";
    
    // Group by tingkat
    $dataByTingkat = $uploads->groupBy(function($item) {
        $tingkat = $item->tingkat;
        if (is_numeric($tingkat)) {
            return (int)$tingkat;
        }
        return 1;
    });
    
    echo "Grouped by tingkat:\n";
    foreach ($dataByTingkat as $tingkat => $data) {
        echo "   - Tingkat {$tingkat}: {$data->count()} matakuliah\n";
    }
    echo "\n";
    
    // Calculate average index
    $avgIndex = round($uploads->avg('index_kepuasan'), 2);
    echo "Average Index Kepuasan: {$avgIndex}\n";
    echo "Status: " . ($avgIndex >= 2.8 ? "✅ Melampaui minimum" : "❌ Belum mencapai minimum") . "\n\n";
}

// Generate Word document
echo "========================================\n";
echo "GENERATING WORD DOCUMENT\n";
echo "========================================\n\n";

$wordService = new KuesioneWordGenerationService();

echo "Calling generateWordDocument()...\n";
$startTime = microtime(true);

try {
    $result = $wordService->generateWordDocument($laporan);
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    if ($result) {
        echo "✅ SUCCESS! Word document generated in {$duration} seconds\n\n";
        
        // Refresh laporan to get updated file_word
        $laporan->refresh();
        
        echo "File details:\n";
        echo "   - File Path: {$laporan->file_word}\n";
        
        $fullPath = storage_path('app/' . $laporan->file_word);
        if (file_exists($fullPath)) {
            $fileSize = filesize($fullPath);
            $fileSizeKB = round($fileSize / 1024, 2);
            echo "   - Full Path: {$fullPath}\n";
            echo "   - File Size: {$fileSizeKB} KB\n";
            echo "   - File exists: ✅ YES\n\n";
            
            echo "========================================\n";
            echo "TEST PASSED! ✅\n";
            echo "========================================\n\n";
            echo "Next steps:\n";
            echo "1. Download the file from: /gkm/laporan-kuesioner/download/{$laporan->id}?format=word\n";
            echo "2. Open in Microsoft Word\n";
            echo "3. Verify:\n";
            echo "   - No {{...}} placeholders visible\n";
            echo "   - All tingkat sections have data\n";
            echo "   - Tables are formatted correctly\n";
            echo "   - Index Kepuasan = {$avgIndex} (not 0.00)\n";
            echo "   - Kesimpulan mentions the correct index value\n\n";
        } else {
            echo "   - Full Path: {$fullPath}\n";
            echo "   - File exists: ❌ NO\n\n";
            echo "⚠️  WARNING: File was reported as generated but doesn't exist!\n";
        }
        
    } else {
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        echo "❌ FAILED! generateWordDocument() returned false in {$duration} seconds\n\n";
        echo "Check logs in storage/logs/laravel.log for details\n";
    }
    
} catch (\Exception $e) {
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    echo "❌ EXCEPTION after {$duration} seconds!\n";
    echo "Error: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n";
