<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\LaporanKuesioneService;

$service = app(LaporanKuesioneService::class);
$docs = $service->collectKuesioneData('UAS Semester Genap 25/26', 1);
echo 'Collected: ' . $docs->count() . "\n";
$agg = $service->aggregateStatistik($docs);
echo 'Course rows: ' . count($agg['kuesioner_data']) . "\n";
echo 'Top 10 rows:'."\n";
foreach (array_slice($agg['kuesioner_data'], 0, 10) as $row) {
    echo $row['kode_matakuliah'] . ' | ' . $row['tingkat'] . ' | ' . $row['responden'] . ' | ' . $row['index_kepuasan'] . "\n";
}
