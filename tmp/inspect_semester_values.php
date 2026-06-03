<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\HasilAnalisisMongo;

$docs = HasilAnalisisMongo::where('prodi.kode','TRPL')->where('tahun','2025')->get(['semester']);
$vals = [];
foreach ($docs as $d) {
    $key = $d->semester ?? 'NULL';
    $vals[$key] = ($vals[$key] ?? 0) + 1;
}
arsort($vals);
print_r($vals);
