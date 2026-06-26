<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;
use Illuminate\Support\Facades\Cache;

class PreloadMonitoringRPSFast extends Command
{
    protected $signature = 'preload:monitoring-rps-fast {prodi_id=4} {semester=1} {tahun=2020}';
    protected $description = 'Preload monitoring RPS cache (fast version - skip dosen)';

    public function handle()
    {
        $prodiId = $this->argument('prodi_id');
        $semester = $this->argument('semester');
        $tahunAjaran = $this->argument('tahun');
        
        $this->info("=== PRELOAD MONITORING RPS (FAST) ===");
        $this->info("Prodi ID: {$prodiId}");
        $this->info("Semester: " . ($semester == 1 ? 'Ganjil' : 'Genap'));
        $this->info("Tahun Ajaran: {$tahunAjaran}");
        $this->newLine();
        
        try {
            $apiService = new ExternalAPIService();
            
            // Step 1: Get matakuliah list
            $this->info("Step 1: Fetching matakuliah list...");
            $matkulData = Cache::remember(
                "matkul_{$prodiId}_{$semester}_{$tahunAjaran}",
                1800,
                function() use ($apiService, $prodiId, $semester, $tahunAjaran) {
                    return $apiService->getMatkulByProdiSemTa($prodiId, $semester, $tahunAjaran);
                }
            );
            
            if (empty($matkulData)) {
                $this->error("✗ No matakuliah data found!");
                return 1;
            }
            
            $this->info("✓ Found " . count($matkulData) . " matakuliah");
            $this->newLine();
            
            // Step 2: Build dosen mapping
            $this->info("Step 2: Building dosen mapping...");
            
            $dosenApiList = Cache::remember("dosen_all_ti_nm_trpl", 1800, function() use ($apiService) {
                // Ambil SEMUA dosen dengan prodi_id 1, 3, 4
                $allDosen = $apiService->getDosenByProdiIds([1, 3, 4]);
                // BATASI 150 DOSEN (tingkatkan dari 100)
                return array_slice($allDosen, 0, 150);
            });
            
            $this->info("✓ Found " . count($dosenApiList) . " dosen for prodi {$prodiId}");
            
            // Build dosen mapping
            $map = [];
            $maxDosen = 80; // TINGKATKAN dari 50 ke 80
            $dosenBar = $this->output->createProgressBar(min($maxDosen, count($dosenApiList)));
            $dosenBar->setFormat('Building mapping: %current%/%max% [%bar%] %percent:3s%%');
            $dosenBar->start();
            
            $processedCount = 0;
            
            foreach ($dosenApiList as $dosen) {
                if ($processedCount >= $maxDosen) {
                    break;
                }
                
                $pegawaiId = $dosen['pegawai_id'] ?? null;
                $namaDosen = $dosen['nama'] ?? null;
                
                if ($pegawaiId && $namaDosen) {
                    try {
                        $jadwalList = Cache::remember(
                            "jadwal_{$pegawaiId}_{$semester}_{$tahunAjaran}",
                            1800,
                            function() use ($apiService, $pegawaiId, $semester, $tahunAjaran) {
                                return $apiService->getJadwalByDosen($pegawaiId, $semester, $tahunAjaran);
                            }
                        );
                        
                        if (!empty($jadwalList)) {
                            foreach ($jadwalList as $jadwal) {
                                $kodeMk = $jadwal['kode_mk'] ?? null;
                                if ($kodeMk) {
                                    if (!isset($map[$kodeMk])) {
                                        $map[$kodeMk] = [];
                                    }
                                    if (!in_array($namaDosen, $map[$kodeMk])) {
                                        $map[$kodeMk][] = $namaDosen;
                                    }
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // Skip
                    }
                }
                
                $processedCount++;
                $dosenBar->advance();
            }
            
            $dosenBar->finish();
            $this->newLine();
            
            // Cache the mapping
            $matkulDosenMap = $map;
            Cache::put("matkul_dosen_map_{$prodiId}_{$semester}_{$tahunAjaran}", $map, 1800);
            
            $this->info("✓ Mapped " . count($matkulDosenMap) . " matakuliah to dosen");
            $this->newLine();
            
            // Step 3: Preload monitoring data for each matakuliah
            $this->info("Step 3: Preloading monitoring data...");
            $bar = $this->output->createProgressBar(count($matkulData));
            $bar->start();
            
            $matkulList = [];
            foreach ($matkulData as $matkul) {
                $kuliahId = $matkul['kuliah_id'] ?? null;
                $kodeMk = $matkul['kode_mk'] ?? '-';
                
                if ($kuliahId) {
                    try {
                        $monitoring = Cache::remember(
                            "monitoring_{$kuliahId}_{$semester}_{$tahunAjaran}",
                            1800,
                            function() use ($apiService, $kuliahId, $tahunAjaran, $semester) {
                                return $apiService->getMonitoringMateri($kuliahId, $tahunAjaran, $semester);
                            }
                        );
                        
                        $statusRPS = 'BELUM UPLOAD';
                        if ($monitoring) {
                            $statusRPS = $monitoring['status_file_silabus'] ?? 'BELUM UPLOAD';
                        }
                        
                        // Get dosen pengampu from mapping
                        $dosenPengampu = '-';
                        if (isset($matkulDosenMap[$kodeMk]) && !empty($matkulDosenMap[$kodeMk])) {
                            $dosenPengampu = implode(', ', $matkulDosenMap[$kodeMk]);
                        }
                        
                        $matkulList[] = [
                            'kode_mk' => $kodeMk,
                            'nama_matkul' => $matkul['nama_matkul'] ?? '-',
                            'dosen_pengampu' => $dosenPengampu,
                            'status_rps' => $statusRPS,
                            'kuliah_id' => $kuliahId
                        ];
                    } catch (\Exception $e) {
                        // Get dosen even if monitoring fails
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
                
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
            
            // Step 4: Cache the final result
            $cacheKey = "monitoring_rps_{$prodiId}_{$semester}_{$tahunAjaran}";
            Cache::put($cacheKey, $matkulList, 1800);
            
            $this->info("✓ Cached " . count($matkulList) . " matakuliah");
            $this->newLine();
            
            // Show statistics
            $sudahUpload = 0;
            $belumUpload = 0;
            $error = 0;
            
            foreach ($matkulList as $mk) {
                if ($mk['status_rps'] === 'SUDAH UPLOAD') {
                    $sudahUpload++;
                } elseif ($mk['status_rps'] === 'ERROR') {
                    $error++;
                } else {
                    $belumUpload++;
                }
            }
            
            $this->info("=== STATISTICS ===");
            $this->line("Total Matakuliah: " . count($matkulList));
            $this->line("Sudah Upload RPS: {$sudahUpload}");
            $this->line("Belum Upload RPS: {$belumUpload}");
            $this->line("Error: {$error}");
            $this->newLine();
            
            $this->info("=== PRELOAD COMPLETED ===");
            $this->info("Cache duration: 30 minutes");
            $this->info("Users can now access the page quickly!");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}
