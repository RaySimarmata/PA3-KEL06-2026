<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\HasilAnalisisMongo;

$docs = HasilAnalisisMongo::where('prodi.kode','TRPL')
    ->where('tahun','2025')
    ->where('semester',2)
    ->where('jenis_kuesioner','UAS')
    ->limit(50)
    ->get();

echo 'Count '.count($docs)."\n";
$vals = [];
foreach ($docs as $d) {
    $key = isset($d->tingkat) ? $d->tingkat : 'NULL';
    $vals[$key] = ($vals[$key] ?? 0) + 1;
}
print_r($vals);
foreach ($docs as $d) {
    echo 'id=' . ($d->_id ?? '') . ' sem=' . ($d->semester ?? '') . ' tingkat=' . ($d->tingkat ?? '') . ' kode=' . ($d->kode_mk ?? '') . ' judul=' . substr($d->judul_kuesioner ?? '', 0, 80) . "\n";
}
