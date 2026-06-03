<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\HasilAnalisisMongo;
use App\Models\Prodi;
use App\Models\Matakuliah;

$prodi = Prodi::find(1);
echo "Program: {$prodi->kode_prodi} ({$prodi->nama_prodi})\n\n";

// Test 1: See what periods are available
echo "=== Available periods in Mongo for TRPL ===\n";
$periods = HasilAnalisisMongo::where('prodi.kode', 'TRPL')
    ->distinct('tahun')
    ->pluck('tahun')
    ->toArray();
echo "Years: " . implode(', ', $periods) . "\n\n";

// Test 2: Count by jenis_kuesioner per semester
echo "=== 2025 Documents by Semester and Jenis ===\n";
$docs2025 = HasilAnalisisMongo::where('prodi.kode', 'TRPL')
    ->where('tahun', '2025')
    ->get();

$distribution = [];
foreach ($docs2025 as $doc) {
    $key = ($doc->semester ?? 'NULL') . ' - ' . ($doc->jenis_kuesioner ?? 'NULL');
    if (!isset($distribution[$key])) {
        $distribution[$key] = 0;
    }
    $distribution[$key]++;
}

foreach ($distribution as $key => $count) {
    echo "  $key: $count\n";
}

// Test 3: Count distinct courses per filter
echo "\n=== Distinct Courses (kode_mk) ===\n";
$courses_all = HasilAnalisisMongo::where('prodi.kode', 'TRPL')
    ->where('tahun', '2025')
    ->distinct('kode_mk')
    ->pluck('kode_mk')
    ->toArray();

$courses_uas_s2 = HasilAnalisisMongo::where('prodi.kode', 'TRPL')
    ->where('tahun', '2025')
    ->where('semester', 2)
    ->where('jenis_kuesioner', 'UAS')
    ->distinct('kode_mk')
    ->pluck('kode_mk')
    ->toArray();

echo "Total distinct courses in 2025: " . count($courses_all) . "\n";
echo "Courses in Genap UAS: " . count($courses_uas_s2) . "\n";
echo "Courses in Genap UAS: " . implode(', ', $courses_uas_s2) . "\n\n";

// Test 4: Avg responses per course
echo "=== Average responses per course (Genap UAS) ===\n";
$avg_per_course = $courses_uas_s2 ? count($courses_uas_s2) : 1;
$avg_responses = 765 / $avg_per_course;
echo "Total documents: 765\n";
echo "Courses: $avg_per_course\n";
echo "Average per course: " . number_format($avg_responses, 1) . "\n\n";

// Test 5: Check jenis_ujian vs jenis_kuesioner alignment
echo "=== Jenis Ujian vs Jenis Kuesioner Analysis ===\n";
$genap_uas = HasilAnalisisMongo::where('prodi.kode', 'TRPL')
    ->where('tahun', '2025')
    ->where('semester', 2)
    ->where('jenis_kuesioner', 'UAS')
    ->limit(10)
    ->get();

foreach ($genap_uas as $doc) {
    echo sprintf("jenis_kuesioner=%s, jenis_ujian=%s, kode_mk=%s\n",
        $doc->jenis_kuesioner ?? '[null]',
        $doc->jenis_ujian ?? '[null]',
        $doc->kode_mk ?? '[null]');
}
