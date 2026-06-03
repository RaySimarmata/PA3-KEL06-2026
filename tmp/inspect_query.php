<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\HasilAnalisisMongo;
use App\Services\LaporanKuesioneService;
use App\Models\Prodi;

$prodi = Prodi::find(1);
echo "Prodi: " . ($prodi ? $prodi->kode_prodi : 'NOT FOUND') . "\n\n";

// Test 1: UAS Semester Genap only
echo "=== Test 1: Query only UAS + Semester 2 (genap) + tahun 2025 ===\n";
$q1 = HasilAnalisisMongo::query()
    ->where('prodi.kode', 'TRPL')
    ->where('tahun', '2025')
    ->where('semester', 2)
    ->where('jenis_kuesioner', 'UAS')
    ->get();
echo "Count (narrow): " . $q1->count() . "\n\n";

// Test 2: Current service logic reproduction
echo "=== Test 2: Current Service Query (Year + Semester + Jenis) ===\n";
$q2 = HasilAnalisisMongo::query();
$q2->where('prodi.kode', 'TRPL');
$q2->where('tahun', '2025');
$q2->where(function($sub) {
    $sub->where('semester', 2)
        ->orWhere('semester', 'like', '%genap%')
        ->orWhere('semester', 'like', '%gasal%')
        ->orWhere('semester', 'like', '%ganjil%');
});
$q2->where(function($sub) {
    $sub->where('jenis_ujian', 'like', '%UAS%')
        ->orWhere('jenis_kuesioner', 'like', '%UAS%')
        ->orWhere('nama_file', 'like', '%UAS%')
        ->orWhere('periode', 'like', '%UAS%');
});
$q2 = $q2->get();
echo "Count (service logic): " . $q2->count() . "\n\n";

// Test 3: No jenis ujian filter
echo "=== Test 3: Only Year + Semester (NO jenis filter) ===\n";
$q3 = HasilAnalisisMongo::query()
    ->where('prodi.kode', 'TRPL')
    ->where('tahun', '2025')
    ->where('semester', 2)
    ->get();
echo "Count (no jenis): " . $q3->count() . "\n\n";

// Test 4: Year + Semester (genap/gasal logic)
echo "=== Test 4: Year + Semester with OR logic ===\n";
$q4 = HasilAnalisisMongo::query()
    ->where('prodi.kode', 'TRPL')
    ->where('tahun', '2025')
    ->where(function($sub) {
        $sub->where('semester', 2)
            ->orWhere('semester', 'like', '%genap%')
            ->orWhere('semester', 'like', '%gasal%')
            ->orWhere('semester', 'like', '%ganjil%');
    })
    ->get();
echo "Count (with semester OR): " . $q4->count() . "\n\n";

// Test 5: No semester filter at all
echo "=== Test 5: Only Year 2025 ===\n";
$q5 = HasilAnalisisMongo::query()
    ->where('prodi.kode', 'TRPL')
    ->where('tahun', '2025')
    ->get();
echo "Count (year only): " . $q5->count() . "\n\n";
