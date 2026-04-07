<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;
use Illuminate\Support\Facades\Cache;

class TestMonitoringRPSOptimized extends Command
{
    protected $signature = 'test:monitoring-rps-optimized {--clear-cache}';
    protected $description = 'Test optimized Monitoring RPS with caching';

    public function handle()
    {
        $this->info('Testing Monitoring RPS Optimization...');
        $this->newLine();

        $apiService = new ExternalAPIService();
        
        // Test parameters
        $prodiId = 4; // TRPL
        $semester = 1; // Ganjil
        $tahunAjaran = 2020;
        
        $cacheKey = "monitoring_rps_{$prodiId}_{$semester}_{$tahunAjaran}";
        
        // Clear cache if requested
        if ($this->option('clear-cache')) {
            Cache::forget($cacheKey);
            $this->warn('Cache cleared!');
            $this->newLine();
        }
        
        // Check cache status
        if (Cache::has($cacheKey)) {
            $this->info('✓ Cache exists for this query');
            $cachedData = Cache::get($cacheKey);
            $this->info('  Cached items: ' . count($cachedData));
        } else {
            $this->warn('✗ No cache found - will fetch from API');
        }
        
        $this->newLine();
        $this->info('Starting data fetch...');
        $startTime = microtime(true);
        
        // Simulate the controller logic
        $matkulList = Cache::remember($cacheKey, 600, function() use ($apiService, $prodiId, $semester, $tahunAjaran) {
            $this->info('  Fetching from API...');
            $matkulList = [];
            
            // Get matakuliah
            $this->info('  → Getting matakuliah list...');
            $matkulData = $apiService->getMatkulByProdiSemTa($prodiId, $semester, $tahunAjaran);
            $this->info('    Found ' . count($matkulData) . ' matakuliah');
            
            if (empty($matkulData)) {
                return [];
            }
            
            // Get dosen
            $this->info('  → Getting dosen list...');
            $dosenApiList = $apiService->getFilteredDosen();
            $this->info('    Found ' . count($dosenApiList) . ' dosen');
            
            // Build mapping
            $matkulDosenMap = [];
            $dosenChunks = array_chunk($dosenApiList, 10);
            
            $this->info('  → Processing dosen in ' . count($dosenChunks) . ' batches...');
            
            foreach ($dosenChunks as $chunkIndex => $dosenChunk) {
                $this->info('    Batch ' . ($chunkIndex + 1) . '/' . count($dosenChunks));
                
                foreach ($dosenChunk as $dosen) {
                    $pegawaiId = $dosen['pegawai_id'] ?? null;
                    $namaDosen = $dosen['nama'] ?? null;
                    
                    if ($pegawaiId && $namaDosen) {
                        try {
                            $jadwalList = $apiService->getJadwalByDosen($pegawaiId, $semester, $tahunAjaran);
                            
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
                            $this->warn('      Failed: ' . $namaDosen);
                            continue;
                        }
                    }
                }
                
                if ($chunkIndex < count($dosenChunks) - 1) {
                    usleep(100000);
                }
            }
            
            // Process matakuliah
            $this->info('  → Getting monitoring status...');
            foreach ($matkulData as $matkul) {
                $kuliahId = $matkul['kuliah_id'] ?? null;
                $kodeMk = $matkul['kode_mk'] ?? '-';
                
                if ($kuliahId) {
                    try {
                        $monitoring = $apiService->getMonitoringMateri($kuliahId, $tahunAjaran, $semester);
                        
                        $dosenPengampu = '-';
                        if (isset($matkulDosenMap[$kodeMk]) && !empty($matkulDosenMap[$kodeMk])) {
                            $dosenPengampu = implode(', ', $matkulDosenMap[$kodeMk]);
                        }
                        
                        $matkulList[] = [
                            'kode_mk' => $kodeMk,
                            'nama_matkul' => $matkul['nama_matkul'] ?? '-',
                            'dosen_pengampu' => $dosenPengampu,
                            'status_rps' => $monitoring['status_file_silabus'] ?? 'BELUM UPLOAD',
                            'kuliah_id' => $kuliahId
                        ];
                    } catch (\Exception $e) {
                        $this->warn('    Failed: ' . $kodeMk);
                        
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
            
            return $matkulList;
        });
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        $this->newLine();
        $this->info('✓ Data fetch completed!');
        $this->info('  Duration: ' . $duration . ' seconds');
        $this->info('  Total items: ' . count($matkulList));
        
        // Show sample data
        if (count($matkulList) > 0) {
            $this->newLine();
            $this->info('Sample data (first 3 items):');
            foreach (array_slice($matkulList, 0, 3) as $item) {
                $this->line('  - ' . $item['kode_mk'] . ': ' . $item['nama_matkul']);
                $this->line('    Dosen: ' . $item['dosen_pengampu']);
                $this->line('    Status: ' . $item['status_rps']);
            }
        }
        
        $this->newLine();
        $this->info('Test completed successfully!');
        
        return 0;
    }
}
