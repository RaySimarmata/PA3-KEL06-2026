<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;

class QuickTestDosenGenap extends Command
{
    protected $signature = 'quick:test-dosen-genap';
    protected $description = 'Quick test untuk cek dosen pengampu semester Genap';

    public function handle()
    {
        $this->info("=== QUICK TEST DOSEN PENGAMPU GENAP ===");
        $this->newLine();
        
        $apiService = new ExternalAPIService();
        
        // Test parameters
        $prodiId = 4; // TRPL
        $semester = 2; // Genap
        $ta = 2020;
        
        // 1. Get sample matakuliah
        $this->info("1. Mengambil sample matakuliah...");
        $matkulData = $apiService->getMatkulByProdiSemTa($prodiId, $semester, $ta);
        
        if (empty($matkulData)) {
            $this->error("   ✗ Tidak ada data matakuliah!");
            return;
        }
        
        $sampleMatkul = array_slice($matkulData, 0, 5);
        $this->info("   ✓ Sample: " . count($sampleMatkul) . " matakuliah");
        $this->newLine();
        
        // 2. Get dosen
        $this->info("2. Mengambil dosen ter-filter...");
        $dosenList = $apiService->getFilteredDosen();
        $this->info("   ✓ Total: " . count($dosenList) . " dosen");
        $this->newLine();
        
        // 3. Build mapping untuk sample
        $this->info("3. Mapping dosen ke matakuliah...");
        $matkulDosenMap = [];
        
        foreach ($dosenList as $dosen) {
            $pegawaiId = $dosen['pegawai_id'] ?? null;
            $namaDosen = $dosen['nama'] ?? null;
            
            if ($pegawaiId && $namaDosen) {
                try {
                    $jadwalList = $apiService->getJadwalByDosen($pegawaiId, $semester, $ta);
                    
                    foreach ($jadwalList as $jadwal) {
                        $kodeMk = $jadwal['kode_mk'] ?? null;
                        if ($kodeMk) {
                            if (!isset($matkulDosenMap[$kodeMk])) {
                                $matkulDosenMap[$kodeMk] = [];
                            }
                            if (!in_array($namaDosen, $matkulDosenMap[$kodeMk])) {
                                $matkulDosenMap[$kodeMk][] = $namaDosen;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Skip
                }
            }
        }
        
        $this->info("   ✓ Mapping selesai");
        $this->newLine();
        
        // 4. Display results
        $this->info("4. Hasil untuk 5 matakuliah pertama:");
        $this->newLine();
        
        $table = [];
        foreach ($sampleMatkul as $matkul) {
            $kodeMk = $matkul['kode_mk'] ?? '-';
            $namaMk = $matkul['nama_matkul'] ?? '-';
            
            $dosenPengampu = '-';
            if (isset($matkulDosenMap[$kodeMk]) && !empty($matkulDosenMap[$kodeMk])) {
                $dosenPengampu = implode(', ', $matkulDosenMap[$kodeMk]);
            }
            
            $table[] = [
                $kodeMk,
                substr($namaMk, 0, 40),
                substr($dosenPengampu, 0, 60)
            ];
        }
        
        $this->table(
            ['Kode MK', 'Nama Matakuliah', 'Dosen Pengampu'],
            $table
        );
        
        $this->newLine();
        
        // Summary
        $withDosen = 0;
        $withoutDosen = 0;
        
        foreach ($sampleMatkul as $matkul) {
            $kodeMk = $matkul['kode_mk'] ?? '-';
            if (isset($matkulDosenMap[$kodeMk]) && !empty($matkulDosenMap[$kodeMk])) {
                $withDosen++;
            } else {
                $withoutDosen++;
            }
        }
        
        $this->info("=== SUMMARY ===");
        $this->line("Sample: 5 matakuliah");
        $this->info("✓ Dengan Dosen: {$withDosen}");
        
        if ($withoutDosen > 0) {
            $this->warn("✗ Tanpa Dosen: {$withoutDosen}");
        } else {
            $this->info("✓ Semua matakuliah memiliki dosen!");
        }
        
        $this->newLine();
        
        if ($withDosen > 0) {
            $this->info("✓ BERHASIL! Dosen pengampu muncul.");
            $this->line("  Sekarang buka browser dan refresh halaman (Ctrl+Shift+R)");
        } else {
            $this->error("✗ GAGAL! Dosen pengampu tidak muncul.");
            $this->line("  Cek log: tail -f storage/logs/laravel.log");
        }
    }
}
