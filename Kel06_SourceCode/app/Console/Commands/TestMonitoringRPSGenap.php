<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;
use Illuminate\Support\Facades\Cache;

class TestMonitoringRPSGenap extends Command
{
    protected $signature = 'test:monitoring-rps-genap {prodi_id=4}';
    protected $description = 'Test Monitoring RPS for semester genap (sem_ta=2)';

    public function handle()
    {
        $prodiId = $this->argument('prodi_id');
        $semester = 2; // Genap
        $tahunAjaran = 2020;
        
        $this->info("=== TEST MONITORING RPS - SEMESTER GENAP ===");
        $this->info("Prodi ID: {$prodiId}");
        $this->info("Semester: Genap (2)");
        $this->info("Tahun Ajaran: {$tahunAjaran}");
        $this->newLine();
        
        try {
            $apiService = new ExternalAPIService();
            
            // Test 1: Check cache
            $this->info("Test 1: Checking cache...");
            $cacheKey = "monitoring_rps_{$prodiId}_{$semester}_{$tahunAjaran}";
            $cacheExists = Cache::has($cacheKey);
            
            if ($cacheExists) {
                $this->info("✓ Cache exists for key: {$cacheKey}");
                $cachedData = Cache::get($cacheKey);
                $this->info("✓ Cached data count: " . count($cachedData));
                
                // Show sample data
                if (count($cachedData) > 0) {
                    $this->newLine();
                    $this->info("Sample data (first 3):");
                    foreach (array_slice($cachedData, 0, 3) as $matkul) {
                        $this->line("  - {$matkul['kode_mk']}: {$matkul['nama_matkul']}");
                        $this->line("    Dosen: {$matkul['dosen_pengampu']}");
                        $this->line("    Status RPS: {$matkul['status_rps']}");
                    }
                }
            } else {
                $this->warn("✗ Cache does not exist. Run preload command first:");
                $this->line("  php -d max_execution_time=300 artisan preload:monitoring-rps-fast {$prodiId} {$semester} {$tahunAjaran}");
            }
            
            $this->newLine();
            
            // Test 2: Check dosen cache
            $this->info("Test 2: Checking dosen cache...");
            $dosenCacheKey = "dosen_all_ti_nm_trpl";
            $dosenCacheExists = Cache::has($dosenCacheKey);
            
            if ($dosenCacheExists) {
                $dosenData = Cache::get($dosenCacheKey);
                $this->info("✓ Dosen cache exists");
                $this->info("✓ Total dosen: " . count($dosenData));
            } else {
                $this->warn("✗ Dosen cache does not exist");
            }
            
            $this->newLine();
            
            // Test 3: Check mapping cache
            $this->info("Test 3: Checking matkul-dosen mapping cache...");
            $mappingCacheKey = "matkul_dosen_map_{$prodiId}_{$semester}_{$tahunAjaran}";
            $mappingCacheExists = Cache::has($mappingCacheKey);
            
            if ($mappingCacheExists) {
                $mappingData = Cache::get($mappingCacheKey);
                $this->info("✓ Mapping cache exists");
                $this->info("✓ Total matkul mapped: " . count($mappingData));
                
                // Show sample mapping
                if (count($mappingData) > 0) {
                    $this->newLine();
                    $this->info("Sample mapping (first 3):");
                    $count = 0;
                    foreach ($mappingData as $kodeMk => $dosenList) {
                        if ($count >= 3) break;
                        $this->line("  - {$kodeMk}: " . implode(', ', $dosenList));
                        $count++;
                    }
                }
            } else {
                $this->warn("✗ Mapping cache does not exist");
            }
            
            $this->newLine();
            
            // Test 4: Direct API call
            $this->info("Test 4: Testing direct API call...");
            $matkulData = $apiService->getMatkulByProdiSemTa($prodiId, $semester, $tahunAjaran);
            
            if (!empty($matkulData)) {
                $this->info("✓ API call successful");
                $this->info("✓ Total matakuliah from API: " . count($matkulData));
                
                // Show sample
                if (count($matkulData) > 0) {
                    $this->newLine();
                    $this->info("Sample matakuliah from API (first 3):");
                    foreach (array_slice($matkulData, 0, 3) as $matkul) {
                        $this->line("  - {$matkul['kode_mk']}: {$matkul['nama_matkul']}");
                    }
                }
            } else {
                $this->error("✗ No data from API");
            }
            
            $this->newLine();
            $this->info("=== TEST COMPLETED ===");
            
            // Summary
            $this->newLine();
            $this->info("Summary:");
            $this->line("  Cache Status: " . ($cacheExists ? "✓ Ready" : "✗ Not Ready"));
            $this->line("  Dosen Cache: " . ($dosenCacheExists ? "✓ Ready" : "✗ Not Ready"));
            $this->line("  Mapping Cache: " . ($mappingCacheExists ? "✓ Ready" : "✗ Not Ready"));
            $this->line("  API Connection: " . (!empty($matkulData) ? "✓ Working" : "✗ Failed"));
            
            if ($cacheExists && $dosenCacheExists && $mappingCacheExists) {
                $this->newLine();
                $this->info("✓ All systems ready! Page should load quickly (<1s)");
            } else {
                $this->newLine();
                $this->warn("⚠ Cache not ready. Run preload command:");
                $this->line("  php -d max_execution_time=300 artisan preload:monitoring-rps-fast {$prodiId} {$semester} {$tahunAjaran}");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
            return 1;
        }
    }
}
