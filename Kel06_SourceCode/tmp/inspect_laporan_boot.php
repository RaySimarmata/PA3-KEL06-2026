<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$status = $kernel->bootstrap();

use App\Models\LaporanBulanan;
use App\Models\Prodi;

$rows = LaporanBulanan::where('periode', 'like', '%UAS%Semester%Genap%')
    ->orWhere('periode', 'like', '%UAS%Genap%')
    ->limit(10)
    ->get();

foreach ($rows as $lap) {
    $prodi = Prodi::find($lap->prodi_id);
    echo 'ID: ' . $lap->id . PHP_EOL;
    echo 'PRODI_ID: ' . $lap->prodi_id . PHP_EOL;
    echo 'PRODI_KODE: ' . ($prodi->kode_prodi ?? 'N/A') . PHP_EOL;
    echo 'PERIODE: ' . $lap->periode . PHP_EOL;
    echo 'BULAN: ' . $lap->bulan . PHP_EOL;
    echo 'TAHUN: ' . $lap->tahun . PHP_EOL;
    echo 'STATUS: ' . $lap->status . PHP_EOL;
    echo 'FILE_WORD: ' . $lap->file_word . PHP_EOL;
    echo 'TOTAL: ' . $lap->total_kuesioner . PHP_EOL;
    echo 'RESPON: ' . $lap->total_responden . PHP_EOL;
    echo 'INDEX: ' . $lap->index_kepuasan_rata_rata . PHP_EOL;
    echo 'PERSEN: ' . $lap->persen_kepuasan_rata_rata . PHP_EOL;
    echo '---' . PHP_EOL;
}
