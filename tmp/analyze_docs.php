<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\HasilAnalisisMongo;

$docs = HasilAnalisisMongo::query()
    ->where('prodi.kode', 'TRPL')
    ->where('tahun', '2025')
    ->where('semester', 2)
    ->get();

echo "Total docs with TRPL + tahun 2025 + semester 2: " . $docs->count() . "\n\n";

$jenis_counts = [];
$periode_counts = [];
foreach ($docs as $doc) {
    $jenis = $doc->jenis_kuesioner ?? '[null]';
    $periode = substr($doc->judul_kuesioner ?? '', 0, 60);
    
    if (!isset($jenis_counts[$jenis])) {
        $jenis_counts[$jenis] = 0;
    }
    $jenis_counts[$jenis]++;
    
    if (!isset($periode_counts[$periode])) {
        $periode_counts[$periode] = 0;
    }
    $periode_counts[$periode]++;
}

echo "Jenis Kuesioner distribution:\n";
foreach ($jenis_counts as $jenis => $count) {
    echo "  $jenis: $count\n";
}

echo "\nTop 10 judul_kuesioner patterns:\n";
arsort($periode_counts);
foreach (array_slice($periode_counts, 0, 10) as $periode => $count) {
    echo "  [$count] " . substr($periode, 0, 80) . "\n";
}
