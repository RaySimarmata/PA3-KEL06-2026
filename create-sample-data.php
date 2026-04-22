<?php

// Script untuk membuat data sample minimal
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\LaporanGJM;
use App\Models\Ajaran;
use App\Models\Prodi;
use Carbon\Carbon;

try {
    echo "Creating sample data...\n";
    
    // Pastikan ada Ajaran
    $ajaran = Ajaran::first();
    if (!$ajaran) {
        $prodi = Prodi::first();
        if ($prodi) {
            $ajaran = Ajaran::create([
                'prodi_id' => $prodi->id,
                'tahun_ajaran' => '2024/2025',
                'semester' => 1,
                'tanggal_mulai' => Carbon::create(2024, 9, 1),
                'tanggal_akhir' => Carbon::create(2025, 1, 31),
                'status' => 'aktif',
            ]);
            echo "Ajaran created!\n";
        }
    }
    
    // Buat beberapa LaporanGJM sample
    if (LaporanGJM::count() == 0 && $ajaran) {
        for ($i = 1; $i <= 3; $i++) {
            LaporanGJM::create([
                'ajaran_id' => $ajaran->id,
                'periode_mulai' => Carbon::now()->subMonths($i),
                'periode_akhir' => Carbon::now()->subMonths($i-1),
                'jenis_laporan' => 'triwulan',
                'program_studi' => 'Teknik Informatika',
                'ringkasan_mutu_institusi' => 'Ringkasan mutu institusi periode ' . $i,
                'analisis_kepatuhan' => 'Analisis kepatuhan standar mutu',
                'temuan_utama' => 'Temuan utama dari evaluasi',
                'rekomendasi_perbaikan' => 'Rekomendasi untuk perbaikan',
                'rencana_tindakan' => 'Rencana tindakan perbaikan',
                'status_laporan' => rand(0, 1) ? 'completed' : 'draft',
                'jumlah_prodi_terlibat' => rand(3, 8),
                'jumlah_laporan_gkm_diterima' => rand(10, 25),
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
                'updated_at' => Carbon::now()->subDays(rand(1, 15)),
            ]);
        }
        echo "LaporanGJM sample data created!\n";
    }
    
    echo "Sample data creation completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}