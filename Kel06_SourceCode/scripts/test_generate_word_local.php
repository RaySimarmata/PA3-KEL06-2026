<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaporanBulanan;
use App\Models\TemplateLaporan;
use App\Models\User;

$tpl = TemplateLaporan::where('nama_template', 'like', '%Kusioner%')->first();
if (!$tpl) {
    echo "No kuesioner template found.\n";
    exit(1);
}

$user = User::first();
if (!$user) {
    echo "No user found in DB.\n";
    exit(1);
}

$laporan = LaporanBulanan::create([
    'periode' => date('Y-m'),
    'bulan' => date('F'),
    'tahun' => date('Y'),
    'user_id' => $user->id,
    'periode_akademik_id' => null,
    'template_id' => $tpl->id,
    'judul_laporan' => 'TEST - Generate Word Local',
    'status' => 'pending',
    'tipe_laporan' => 'UTS'
]);

$hasil = [
    'PENDAHULUAN_TUJUAN' => "Survei ini bertujuan untuk mengevaluasi mata kuliah pada semester GANJIL 2025/2026 di lingkungan program studi X Fakultas Vokasi Institut Teknologi Del.",
    'PENDAHULUAN_WAKTU' => "Penyebaran kuesioner evaluasi mata kuliah dilaksanakan pada bulan Maret 2026. Penyebaran kuesioner dibagi menjadi 2 tahap...",
    'PENDAHULUAN_RUANG_LINGKUP' => "Yang menjadi responden pada survei ini adalah mahasiswa yang mengambil mata kuliah prodi, mata kuliah fakultas dan mata kuliah institut...",
    'HASIL_KUESIONER_TINGKAT_I' => "Pada tingkat I terdapat 1 matakuliah dengan detail sebagai berikut:\n\nTabel 2. Matakuliah Mahasiswa Tingkat I\n\n| Kode Matakuliah | Nama Matakuliah | Dosen Pengampu | Indeks Kepuasan |\n|-----------------|-----------------|----------------|------------------|\n| 4142101 | Algoritma dan Struktur Data | Ray Simarmata | 3.1462 |\n\nRata Indeks Kepuasan: 3.1462",
    'MASUKAN_SARAN_TINGKAT_I' => "Adapun masukan/saran untuk perbaikan mata kuliah ini dapat dilihat pada Tabel 3:\n\nTabel 3. Masukan/saran setiap Matakuliah\n\n| Kode Matakuliah | Nama Matakuliah | Dosen Pengampu | Masukan/Saran |\n|-----------------|-----------------|----------------|----------------|\n| 4142101 | Algoritma dan Struktur Data | Ray Simarmata | 1. Perlu peningkatan interaksi kelas. |",
    'HASIL_KUESIONER_TINGKAT_II' => "Tidak ada data kuesioner untuk Tingkat II",
    'MASUKAN_SARAN_TINGKAT_II' => "",
    'HASIL_KUESIONER_TINGKAT_III' => "Tidak ada data kuesioner untuk Tingkat III",
    'MASUKAN_SARAN_TINGKAT_III' => "",
    'HASIL_KUESIONER_TINGKAT_IV' => "Tidak ada data kuesioner untuk Tingkat IV",
    'MASUKAN_SARAN_TINGKAT_IV' => "",
    'KESIMPULAN' => "- Adanya matakuliah yang dihitung berdasarkan kode matakuliah...\n\n- Indeks Kepuasan semua matakuliah di prodi adalah 3.15.",
    'SARAN_REKOMENDASI' => ""
];

$laporan->hasil_laporan = $hasil;
$laporan->save();

$service = app(\App\Services\KuesioneWordGenerationService::class);
try {
    $ok = $service->generateWordDocument($laporan);
    if ($ok) {
        $laporan = $laporan->fresh();
        echo "Word generated: " . ($laporan->file_word ?? 'no-path') . "\n";
        echo "Full path: " . storage_path('app/' . ($laporan->file_word ?? '')) . "\n";
        exit(0);
    } else {
        echo "generateWordDocument returned falsy.\n";
        exit(2);
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(3);
}
