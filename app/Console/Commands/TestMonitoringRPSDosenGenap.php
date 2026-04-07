<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;
use Illuminate\Support\Facades\Cache;

class TestMonitoringRPSDosenGenap extends Command
{
    protected $signature = 'test:monitoring-rps-dosen-genap {prodi_id=4} {ta=2020}';
    protected $description = 'Test apakah dosen pengampu muncul untuk semester Genap';

    public function handle()
    {
        $prodiId = $this->argument('prodi_id');
        $ta = $this->argument('ta');
        $selectedSemester = 2; // Genap
        
        $apiService = new ExternalAPIService();

        $this->info("=== TEST DOSEN PENGAMPU SEMESTER GENAP ===");
        $this->info("Prodi ID: {$prodiId}, TA: {$ta}, Semester: Genap (2)");
        $this->newLine();

        // Simulate controller logic
        $this->info("1. Mengambil data matakuliah...");
        $matkulData = $apiService->getMatkulByProdiSemTa($prodiId, $selectedSemester, $ta);
        
        if (empty($matkulData)) {
            $this->error("   ✗ Tidak ada data matakuliah!");
            return;
        }
        
        $this->info("   ✓ Ditemukan " . count($matkulData) . " matakuliah");
        $this->newLine();

        // Get filtered dosen
        $this->info("2. Mengambil dosen ter-filter...");
        $dosenApiList = $apiService->getFilteredDosen();
        $this->info("   ✓ Ditemukan " . count($dosenApiList) . " dosen");
        $this->newLine();

        // Build mapping: kode_mk => [dosen names]
        $this->info("3. Membangun mapping dosen ke matakuliah...");
        $matkulDosenMap = [];
        
        $dosenWithJadwal = 0;
        $processedDosen = 0;
        
        foreach ($dosenApiList as $index => $dosen) {
            $pegawaiId = $dosen['pegawai_id'] ?? null;
            $namaDosen = $dosen['nama'] ?? null;
            
            if ($pegawaiId && $namaDosen) {
                $processedDosen++;
                
                // Show progress every 10 dosen
                if ($processedDosen % 10 == 0) {
                    $this->line("   Processing dosen {$processedDosen}/" . count($dosenApiList) . "...");
                }
                
                try {
                    $jadwalList = $apiService->getJadwalByDosen($pegawaiId, $selectedSemester, $ta);
                    
                    if (!empty($jadwalList)) {
                        $dosenWithJadwal++;
                        
                        // Map each matkul to this dosen
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
                    }
                } catch (\Exception $e) {
                    $this->warn("   Error for dosen {$namaDosen}: " . $e->getMessage());
                }
            }
        }
        
        $this->info("   ✓ {$dosenWithJadwal} dosen memiliki jadwal");
        $this->info("   ✓ " . count($matkulDosenMap) . " matakuliah memiliki dosen");
        $this->newLine();

        // Process each matakuliah
        $this->info("4. Memproses setiap matakuliah...");
        $matkulList = [];
        
        foreach ($matkulData as $matkul) {
            $kuliahId = $matkul['kuliah_id'] ?? null;
            $kodeMk = $matkul['kode_mk'] ?? '-';
            
            if ($kuliahId) {
                try {
                    $monitoring = $apiService->getMonitoringMateri($kuliahId, $ta, $selectedSemester);
                    
                    // Get dosen pengampu from mapping
                    $dosenPengampu = '-';
                    if (isset($matkulDosenMap[$kodeMk]) && !empty($matkulDosenMap[$kodeMk])) {
                        $dosenPengampu = implode(', ', $matkulDosenMap[$kodeMk]);
                    }
                    
                    // Determine status RPS
                    $statusRPS = 'BELUM UPLOAD';
                    if ($monitoring) {
                        $statusRPS = $monitoring['status_file_silabus'] ?? 'BELUM UPLOAD';
                    }
                    
                    $matkulList[] = [
                        'kode_mk' => $kodeMk,
                        'nama_matkul' => $matkul['nama_matkul'] ?? '-',
                        'dosen_pengampu' => $dosenPengampu,
                        'status_rps' => $statusRPS,
                        'kuliah_id' => $kuliahId
                    ];
                } catch (\Exception $e) {
                    // Add with default
                    $dosenPengampu = '-';
                    if (isset($matkulDosenMap[$kodeMk]) && !empty($matkulDosenMap[$kodeMk])) {
                        $dosenPengampu = implode(', ', $matkulDosenMap[$kodeMk]);
                    }
                    
                    $matkulList[] = [
                        'kode_mk' => $kodeMk,
                        'nama_matkul' => $matkul['nama_matkul'] ?? '-',
                        'dosen_pengampu' => $dosenPengampu,
                        'status_rps' => 'ERROR',
                        'kuliah_id' => $kuliahId
                    ];
                }
            }
        }
        
        $this->newLine();
        
        // Display results
        $this->info("5. Hasil akhir (10 pertama):");
        $table = [];
        
        $withDosen = 0;
        $withoutDosen = 0;
        
        foreach (array_slice($matkulList, 0, 10) as $matkul) {
            $table[] = [
                $matkul['kode_mk'],
                substr($matkul['nama_matkul'], 0, 40),
                substr($matkul['dosen_pengampu'], 0, 50),
                $matkul['status_rps']
            ];
            
            if ($matkul['dosen_pengampu'] !== '-') {
                $withDosen++;
            } else {
                $withoutDosen++;
            }
        }
        
        $this->table(
            ['Kode MK', 'Nama Matakuliah', 'Dosen Pengampu', 'Status RPS'],
            $table
        );
        
        $this->newLine();
        
        // Count all
        $totalWithDosen = 0;
        $totalWithoutDosen = 0;
        
        foreach ($matkulList as $matkul) {
            if ($matkul['dosen_pengampu'] !== '-') {
                $totalWithDosen++;
            } else {
                $totalWithoutDosen++;
            }
        }
        
        // Summary
        $this->info("=== SUMMARY ===");
        $this->line("Total Matakuliah: " . count($matkulList));
        $this->info("✓ Dengan Dosen: {$totalWithDosen}");
        $this->warn("✗ Tanpa Dosen: {$totalWithoutDosen}");
        
        if ($totalWithoutDosen > 0) {
            $this->newLine();
            $this->warn("Matakuliah tanpa dosen:");
            foreach ($matkulList as $matkul) {
                if ($matkul['dosen_pengampu'] === '-') {
                    $this->line("  - {$matkul['kode_mk']}: {$matkul['nama_matkul']}");
                }
            }
        }
        
        $this->newLine();
        $this->info('Test selesai!');
    }
}
