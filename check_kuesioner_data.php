<?php
/**
 * Check kuesioner data availability
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "========================================\n";
echo "CHECKING KUESIONER DATA\n";
echo "========================================\n\n";

// Check kuesioner_uploads table
$totalUploads = \App\Models\KuesioneUpload::count();
echo "Total kuesioner uploads: {$totalUploads}\n\n";

if ($totalUploads > 0) {
    echo "Sample uploads (first 10):\n";
    $uploads = \App\Models\KuesioneUpload::take(10)->get();
    foreach ($uploads as $idx => $upload) {
        echo ($idx + 1) . ". ID: {$upload->id}, Tingkat: {$upload->tingkat}, ";
        echo "MK: {$upload->nama_matakuliah}, Index: {$upload->index_kepuasan}, ";
        echo "Semester: {$upload->semester}\n";
    }
    echo "\n";
    
    // Check by semester
    echo "Breakdown by semester:\n";
    $bySemester = \App\Models\KuesioneUpload::selectRaw('semester, COUNT(*) as count')
        ->groupBy('semester')
        ->get();
    foreach ($bySemester as $row) {
        $semText = $row->semester == 1 ? 'GANJIL' : 'GENAP';
        echo "   - Semester {$row->semester} ({$semText}): {$row->count} uploads\n";
    }
    echo "\n";
    
    // Check by tingkat
    echo "Breakdown by tingkat:\n";
    $byTingkat = \App\Models\KuesioneUpload::selectRaw('tingkat, COUNT(*) as count')
        ->groupBy('tingkat')
        ->orderBy('tingkat')
        ->get();
    foreach ($byTingkat as $row) {
        echo "   - Tingkat {$row->tingkat}: {$row->count} uploads\n";
    }
    echo "\n";
}

// Check laporan_bulanan table
$totalLaporan = \App\Models\LaporanBulanan::count();
echo "Total laporan bulanan: {$totalLaporan}\n\n";

if ($totalLaporan > 0) {
    echo "Recent laporan (last 5):\n";
    $laporans = \App\Models\LaporanBulanan::latest()->take(5)->get();
    foreach ($laporans as $idx => $lap) {
        echo ($idx + 1) . ". ID: {$lap->id}, Periode: {$lap->periode}, ";
        echo "Status: {$lap->status}, ";
        echo "Word: " . ($lap->file_word ? "✅" : "❌") . "\n";
    }
    echo "\n";
}

// Check users with prodi
$usersWithProdi = \App\Models\User::whereNotNull('prodi_id')->count();
echo "Users with prodi: {$usersWithProdi}\n\n";

if ($usersWithProdi > 0) {
    echo "Sample users (first 5):\n";
    $users = \App\Models\User::whereNotNull('prodi_id')->with('prodi')->take(5)->get();
    foreach ($users as $idx => $user) {
        $prodiNama = $user->prodi ? $user->prodi->nama_prodi : 'N/A';
        echo ($idx + 1) . ". ID: {$user->id}, Name: {$user->name}, Prodi: {$prodiNama}\n";
    }
}

echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n\n";

if ($totalUploads > 0 && $totalLaporan > 0) {
    echo "✅ System has data - ready to test Word generation\n";
} elseif ($totalUploads > 0) {
    echo "⚠️  Kuesioner uploads exist but no laporan records\n";
    echo "   You need to create a laporan via the web interface first\n";
} else {
    echo "❌ No kuesioner data found\n";
    echo "   Please upload kuesioner data first\n";
}

echo "\n";
