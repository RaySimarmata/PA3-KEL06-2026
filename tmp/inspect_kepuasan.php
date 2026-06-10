<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\HasilAnalisisMongo;
$sample = HasilAnalisisMongo::query()->limit(1)->get();
echo 'Count: ' . count($sample) . "\n";
if (count($sample) > 0) {
    $doc = $sample->first();
    echo json_encode(array_keys($doc->getAttributes())) . "\n";
}
$docs = HasilAnalisisMongo::query()->whereNotNull('persentase_kepuasan')->limit(5)->get();
echo 'With persentase_kepuasan: ' . count($docs) . "\n";
foreach ($docs as $doc) {
    echo 'tingkat=' . ($doc->tingkat ?? 'NULL') . ' persentase_kepuasan=' . ($doc->persentase_kepuasan ?? 'NULL') . ' index_kepuasan=' . ($doc->index_kepuasan ?? 'NULL') . "\n";
}
