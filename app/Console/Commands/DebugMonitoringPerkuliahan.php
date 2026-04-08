<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;
use Illuminate\Support\Facades\Log;

class DebugMonitoringPerkuliahan extends Command
{
    protected $signature = 'debug:monitoring-perkuliahan {prodi_id=4} {semester=1} {tahun=2024}';
    protected $description = 'Debug monitoring perkuliahan data flow';

    public function handle()
    {
        $prodiId = $this->argument('prodi_id');
        $semester = $this->argument('semester');
        $tahunAjaran = $this->argument('tahun');
        
        $this->info("=== DEBUG MONITORING PERKULIAHAN ===");
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
                $this->warn("Possible reasons:");
                $this->line("  - Invalid prodi_id, semester, or tahun_ajaran");
                $this->line("  - No courses registered for this period");
                $this->line("  - API connection issue");
                return 1;
            }
            
            $this->info("✓ Found " . count($matkulData) . " matakuliah");
            $this->newLine();
            
            // Show first 5 matakuliah
            $this->info("Sample matakuliah (first 5):");
            foreach (array_slice($matkulData, 0, 5) as $mk) {
                $this->line("  - [{$mk['kode_mk']}] {$mk['nama_matkul']} (kuliah_id: {$mk['kuliah_id']})");
            }
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
            
            $bar = $this->output->createProgressBar(count($dosenList));
            $bar->start();
            
            foreach ($dosenList as $dosen) {
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
            }
            
            $bar->finish();
            $this->newLine();
            $this->info("✓ Processed {$dosenProcessed} dosen with jadwal");
            $this->info("✓ Mapped " . count($matkulDosenMap) . " matakuliah to dosen");
            $this->newLine();
            
            // Step 4: Test monitoring API for first matakuliah
            $this->info("Step 4: Testing monitoring API...");
            $firstMk = $matkulData[0];
            $kuliahId = $firstMk['kuliah_id'];
            $kodeMk = $firstMk['kode_mk'];
            $namaMk = $firstMk['nama_matkul'];
            
            $this->line("Testing with: [{$kodeMk}] {$namaMk}");
            $this->line("Kuliah ID: {$kuliahId}");
            
            $monitoring = $apiService->getMonitoringMateri($kuliahId, $tahunAjaran, $semester);
            
            if (!$monitoring) {
                $this->error("✗ Failed to get monitoring data");
                $this->warn("API might not have data for this kuliah_id");
            } else {
                $this->info("✓ Monitoring data retrieved");
                
                if (isset($monitoring['check_materi'])) {
                    $this->info("✓ check_materi found");
                    
                    if (isset($monitoring['check_materi']['summary'])) {
                        $summary = $monitoring['check_materi']['summary'];
                        $this->line("  Total Sesi: " . ($summary['total_sesi'] ?? 'N/A'));
                        $this->line("  Persentase Teks: " . ($summary['persentase_teks'] ?? 'N/A'));
                        $this->line("  Persentase File: " . ($summary['persentase_file'] ?? 'N/A'));
                    }
                    
                    if (isset($monitoring['check_materi']['detail'])) {
                        $detailCount = count($monitoring['check_materi']['detail']);
                        $this->line("  Detail entries: {$detailCount}");
                        
                        // Show first 3 details
                        foreach (array_slice($monitoring['check_materi']['detail'], 0, 3) as $detail) {
                            $sesi = $detail['sesi'] ?? 'N/A';
                            $statusTeks = $detail['status_teks'] ?? 'N/A';
                            $statusFile = $detail['status_file'] ?? 'N/A';
                            $this->line("    {$sesi}: Teks={$statusTeks}, File={$statusFile}");
                        }
                    } else {
                        $this->warn("✗ check_materi.detail not found");
                    }
                } else {
                    $this->warn("✗ check_materi not found in response");
                    $this->line("Response keys: " . implode(', ', array_keys($monitoring)));
                }
            }
            
            $this->newLine();
            
            // Step 5: Show final data structure
            $this->info("Step 5: Final data structure preview");
            
            $materiTeori = [];
            foreach (array_slice($matkulData, 0, 3) as $matkul) {
                $kuliahId = $matkul['kuliah_id'] ?? null;
                $kodeMk = $matkul['kode_mk'] ?? '-';
                $namaMk = $matkul['nama_matkul'] ?? '-';
                
                // Get dosen pengampu
                $dosenPengampu = '-';
                if (isset($matkulDosenMap[$kodeMk]) && !empty($matkulDosenMap[$kodeMk])) {
                    $dosenPengampu = implode(', ', $matkulDosenMap[$kodeMk]);
                }
                
                $this->line("Matakuliah: [{$kodeMk}] {$namaMk}");
                $this->line("  Dosen: {$dosenPengampu}");
                $this->line("  Kuliah ID: {$kuliahId}");
                $this->newLine();
            }
            
            $this->info("=== DEBUG COMPLETED ===");
            $this->info("If you see data above, the system should work.");
            $this->info("If 'No matakuliah data found', try different parameters.");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
    }
}
