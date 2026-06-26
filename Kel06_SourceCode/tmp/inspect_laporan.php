<?php
require __DIR__ . '/../vendor/autoload.php';
use App\Models\LaporanBulanan;

$rows = LaporanBulanan::where('periode', 'like', '%UAS%Genap%25/26%')
    ->orWhere('periode', 'like', '%UAS% Semester Genap%')
    ->limit(10)
    ->get();

foreach ($rows as $lap) {
    echo 'ID: ' . $lap->id . PHP_EOL;
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
