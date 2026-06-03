<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== SYNC JADWAL DOSEN ===\n\n";

// Check periode aktif
$periode = DB::table('periode_akademik')->where('is_active', 1)->first();

if (!$periode) {
    echo "ERROR: Tidak ada periode akademik aktif!\n";
    echo "Menggunakan default: 2025, Semester Ganjil (1)\n\n";
    $tahunAjaran = '2025';
    $semester = '1';
} else {
    $tahunAjaran = $periode->tahun_ajaran;
    $semester = $periode->semester;
    echo "Periode Aktif: {$tahunAjaran} - Semester {$semester}\n\n";
}

// Get API service
$apiService = new \App\Services\ExternalAPIService();

// Get all dosen
$dosenList = DB::table('dosenn')->select('pegawai_id', 'nama')->get();

echo "Total Dosen: " . $dosenList->count() . "\n";
echo "Memulai sync...\n\n";

$successCount = 0;
$failCount = 0;
$totalJadwal = 0;
$bar = 0;

foreach ($dosenList as $dosen) {
    $bar++;
    
    // Progress indicator every 10 dosen
    if ($bar % 10 == 0) {
        echo "Progress: {$bar}/{$dosenList->count()} dosen...\n";
    }
    
    try {
        $jadwalList = $apiService->getJadwalByDosen(
            $dosen->pegawai_id,
            $semester,
            $tahunAjaran
        );

        if (!empty($jadwalList)) {
            foreach ($jadwalList as $jadwal) {
                // Filter by semester and tahun
                if (($jadwal['semester'] ?? null) == $semester && 
                    ($jadwal['tahun_ajaran'] ?? null) == $tahunAjaran) {
                    
                    $kodeMk = trim($jadwal['kode_mk'] ?? '');
                    
                    if (!empty($kodeMk)) {
                        \App\Models\JadwalDosen::updateOrCreate(
                            [
                                'pegawai_id' => $dosen->pegawai_id,
                                'kode_mk' => $kodeMk,
                                'semester' => $semester,
                                'tahun_ajaran' => $tahunAjaran,
                            ],
                            [
                                'kuliah_id' => $jadwal['kuliah_id'] ?? null,
                                'kelas' => $jadwal['kelas'] ?? null,
                            ]
                        );
                        
                        $totalJadwal++;
                    }
                }
            }
            $successCount++;
        }
    } catch (\Exception $e) {
        $failCount++;
        echo "  ✗ Gagal sync dosen: {$dosen->nama} ({$dosen->pegawai_id})\n";
        echo "    Error: " . $e->getMessage() . "\n";
    }
}

echo "\n=== HASIL SYNC ===\n";
echo "Dosen berhasil: {$successCount}\n";
echo "Dosen gagal: {$failCount}\n";
echo "Total jadwal tersimpan: {$totalJadwal}\n";
echo "\n✓ Sync selesai!\n";

// Verify specific mata kuliah
echo "\n=== VERIFIKASI MATA KULIAH SPESIFIK ===\n";
$testCodes = ['4143103', '4143101', '4143107', '4142101', '4233202'];

foreach ($testCodes as $code) {
    $count = DB::table('jadwal_dosen')
        ->where('kode_mk', $code)
        ->where('semester', $semester)
        ->where('tahun_ajaran', $tahunAjaran)
        ->count();
    
    if ($count > 0) {
        $dosenNames = DB::table('jadwal_dosen as jd')
            ->leftJoin('dosenn as d', 'jd.pegawai_id', '=', 'd.pegawai_id')
            ->where('jd.kode_mk', $code)
            ->where('jd.semester', $semester)
            ->where('jd.tahun_ajaran', $tahunAjaran)
            ->select('d.nama')
            ->distinct()
            ->pluck('nama')
            ->take(3);
        
        echo "✓ {$code}: {$count} dosen - " . $dosenNames->implode(', ') . "\n";
    } else {
        echo "✗ {$code}: tidak ada data\n";
    }
}

echo "\nDone!\n";
