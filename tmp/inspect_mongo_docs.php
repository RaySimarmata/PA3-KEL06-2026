<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\HasilAnalisisMongo;

$docs = HasilAnalisisMongo::query()
    ->limit(20)
    ->get();

foreach ($docs as $doc) {
    echo 'ID: ' . ($doc->_id ?? $doc->id ?? 'n/a') . PHP_EOL;
    echo 'jenis_ujian: ' . ($doc->jenis_ujian ?? '') . PHP_EOL;
    echo 'jenis_kuesioner: ' . ($doc->jenis_kuesioner ?? '') . PHP_EOL;
    echo 'semester: ' . ($doc->semester ?? '') . PHP_EOL;
    echo 'tahun: ' . ($doc->tahun ?? '') . PHP_EOL;
    echo 'periode: ' . ($doc->periode ?? '') . PHP_EOL;
    echo 'nama_matakuliah: ' . ($doc->nama_matakuliah ?? '') . PHP_EOL;
    echo 'kode_mk: ' . ($doc->kode_mk ?? '') . PHP_EOL;
    echo 'judul_kuesioner: ' . ($doc->judul_kuesioner ?? '') . PHP_EOL;
    echo '---' . PHP_EOL;
}
