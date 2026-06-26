<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(App\Services\LaporanKuesioneService::class);
$result = $service->collectKuesioneData('UAS Semester Genap 25/26', 1);

echo 'Collected count: ' . $result->count() . PHP_EOL;
foreach ($result as $doc) {
    echo 'Doc ID: ' . ($doc->id ?? $doc->_id ?? 'n/a') . ' | kode_mk: ' . ($doc->kode_matakuliah ?? '') . ' | jenis_ujian: ' . ($doc->jenis_kuesioner ?? '') . ' | semester: ' . ($doc->semester ?? '') . ' | tahun: ' . ($doc->tahun ?? '') . PHP_EOL;
}
