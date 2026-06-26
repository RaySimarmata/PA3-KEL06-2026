<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\LaporanKuesioneService;

$service = app(LaporanKuesioneService::class);
$docs = $service->collectKuesioneData('UAS Semester Genap 25/26', 1);
$vals = [];
foreach ($docs as $d) {
    $key = $d->tingkat ?? 'NULL';
    $vals[$key] = ($vals[$key] ?? 0) + 1;
}
ksort($vals);
print_r($vals);
foreach ($docs->take(20) as $d) {
    echo sprintf("kode=%s tingkat=%s nama=%s\n", $d->kode_matakuliah, $d->tingkat, $d->nama_matakuliah);
}
