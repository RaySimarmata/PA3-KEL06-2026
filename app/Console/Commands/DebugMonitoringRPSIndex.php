<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DebugMonitoringRPSIndex extends Command
{
    protected $signature = 'debug:monitoring-rps-index {prodi_id=4} {semester=1} {tahun=2020}';
    protected $description = 'Debug monitoring RPS index page data flow';

    public function handle()
    {
        $prodiId = $this->argument('prodi_id');
        $semester = $this->argument('semester');
        $tahunAjaran = $this->argument('tahun');
        
        $this->info("=== DEBUG MONITORING RPS INDEX ===");
        $this->info("Prodi ID: {$prodiId}");
        $this->info("Semester: " . ($semester == 1 ? 'Ganjil' : 'Genap'));
        $this->info("Tahun Ajaran: {$tahunAjaran}");
        $this->newLine();
        
        try {
            $apiService = new ExternalAPIService();
            
            // Step 1: Get matakuliah list
            $this->info("Step 1: Fetching matakuliah list...");
            $matkulData = $apiService->getMatkulByProdiSemTa($prodiId, $semester, $tahunAjaran);
            
            if (empty($matkulData)) {
                $this->error("✗ No matakuliah data found!");
                return 1;
            }
            
            $this->info("✓ Found " . count($matkulData) . " matakuliah");
            $this->newLine();
            
            // Step 2: Get dosen list
            $this->info("Step 2: Fetching dosen list...");
            $allDosen = $apiService->getFilteredDosen();
            
            if (empty($allDosen)) {
                $this->error("✗ No dosen data found!");
                return 1;
            }
            
            $this->info("✓ Found " . count($allDosen) . " total dosen");
            
            // Filter by prodi
            $dosenList = array_filter($allDosen, function($dosen) use ($prodiId) {
                return isset($dosen['prodi_id']) && $dosen['prodi_id'] == $prodiId;
            });
            
            $this->info("✓ Filtered to " . count($dosenList) . " dosen for prodi_id {$prodiId}");
            $this->newLine();
            
            // Step 3: Build dosen-matakuliah mapping
            $this->info("Step 3: Building dosen-matakuliah mapping...");
            $matkulDosenMap = [];
            $dosenProcessed = 0;
            
            $bar = $this->output->createProgressBar(min(count($dosenList), 5)); // Only process first 5 for speed
            $bar->start();
            
            $count = 0;
            foreach ($dosenList as $dosen) {
                if ($count >= 5) break; // Limit for debugging
                
                $pegawaiId = $dosen['pegawai_id'] ?? null;
                $namaDosen = $dosen['nama'] ?? null;
                
                if ($pegawaiId && $namaDosen) {
                    try {
                        $jadwalList = $apiService->getJadwalByDosen($pegawaiId, $semester, $tahunAjaran);
                        
                        if (!empty($jadwalList)) {
                            $dosenProcessed++;
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
                        // Skip
                    }
                }
                
                $bar->advance();
                $count++;
            }
            
            $bar->finish();
            $this->newLine();
            $this->info("✓ Processed {$dosenProcessed} dosen with jadwal (limited to 5 for debug)");
            $this->info("✓ Mapped " . count($matkulDosenMap) . " matakuliah to dosen");
            $this->newLine();
            
            // Step 4: Process first 3 matakuliah with RPS status
            $this->info("Step 4: Processing matakuliah with RPS status...");
            $matkulList = [];
            
            foreach (array_slice($matkulData, 0, 3) as $matkul) {
                $kuliahId = $matkul['kuliah_id'] ?? null;
                $kodeMk = $matkul['kode_mk'] ?? '-';
                $namaMk = $matkul['nama_matkul'] ?? '-';
                
                // Get dosen pengampu
                $dosenPengampu = '-';
                if (isset($matkulDosenMap[$kodeMk]) && !empty($matkulDosenMap[$kodeMk])) {
                    $dosenPengampu = implode(', ', $matkulDosenMap[$kodeMk]);
                }
                
                // Get RPS status
                $statusRPS = 'BELUM UPLOAD';
                if ($kuliahId) {
                    try {
                        $monitoring = $apiService->getMonitoringMateri($kuliahId, $tahunAjaran, $semester);
                        
                        if ($monitoring && isset($monitoring['status_file_silabus'])) {
                            $statusRPS = $monitoring['status_file_silabus'];
                        }
                    } catch (\Exception $e) {
                        $statusRPS = 'ERROR';
                    }
                }
                
                $matkulList[] = [
                    'kode_mk' => $kodeMk,
                    'nama_matkul' => $namaMk,
                    'dosen_pengampu' => $dosenPengampu,
                    'status_rps' => $statusRPS
                ];
                
                $icon = $statusRPS === 'SUDAH UPLOAD' ? '✓' : '✗';
                $this->line("{$icon} [{$kodeMk}] {$namaMk}");
                $this->line("   Dosen: {$dosenPengampu}");
                $this->line("   RPS: {$statusRPS}");
                $this->newLine();
            }
            
            // Step 5: Show what would be returned to view
            $this->info("Step 5: Data structure for view");
            $this->line("Total matakuliah to display: " . count($matkulList));
            $this->line("Filter applied: YES");
            $this->line("Semester: " . ($semester == 1 ? 'Ganjil' : 'Genap'));
            $this->line("Tahun Ajaran: {$tahunAjaran}");
            $this->newLine();
            
            $this->info("=== DEBUG COMPLETED ===");
            $this->info("If you see data above, the controller should work.");
            $this->info("Check browser console and Laravel logs for more details.");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
    }
}
