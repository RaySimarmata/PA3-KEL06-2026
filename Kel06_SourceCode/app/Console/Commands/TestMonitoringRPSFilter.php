<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;
use Illuminate\Support\Facades\Cache;

class TestMonitoringRPSFilter extends Command
{
    protected $signature = 'test:monitoring-rps-filter {--clear-cache}';
    protected $description = 'Test Monitoring RPS filter untuk semester ganjil dan genap';

    public function handle()
    {
        if ($this->option('clear-cache')) {
            $this->info("Clearing all monitoring RPS cache...");
            Cache::flush();
            $this->info("✓ Cache cleared");
            $this->newLine();
        }

        $apiService = new ExternalAPIService();

        // Test cases
        $testCases = [
            ['prodi_id' => 4, 'semester' => 1, 'tahun' => '2024', 'label' => 'TRPL - Ganjil 2024'],
            ['prodi_id' => 4, 'semester' => 2, 'tahun' => '2024', 'label' => 'TRPL - Genap 2024'],
            ['prodi_id' => 1, 'semester' => 1, 'tahun' => '2024', 'label' => 'TI - Ganjil 2024'],
            ['prodi_id' => 1, 'semester' => 2, 'tahun' => '2024', 'label' => 'TI - Genap 2024'],
        ];

        foreach ($testCases as $test) {
            $this->info("=== Testing: {$test['label']} ===");
            
            // 1. Test Matakuliah
            $this->info("1. Fetching Matakuliah...");
            $matkulData = $apiService->getMatkulByProdiSemTa(
                $test['prodi_id'], 
                $test['semester'], 
                $test['tahun']
            );
            
            if (empty($matkulData)) {
                $this->error("  ❌ No matakuliah data");
            } else {
                $this->info("  ✓ Found " . count($matkulData) . " matakuliah");
                
                // Show sample
                $sample = array_slice($matkulData, 0, 3);
                foreach ($sample as $mk) {
                    $this->line("    - {$mk['kode_mk']}: {$mk['nama_matkul']}");
                }
            }
            
            // 2. Test Dosen
            $this->info("2. Fetching Dosen...");
            $dosenList = $apiService->getFilteredDosen();
            
            if (empty($dosenList)) {
                $this->error("  ❌ No dosen data");
            } else {
                $this->info("  ✓ Found " . count($dosenList) . " dosen");
            }
            
            // 3. Test Jadwal for ALL dosen (not just sample)
            $this->info("3. Testing Jadwal (processing all dosen)...");
            $dosenWithJadwal = 0;
            $totalJadwal = 0;
            $matkulDosenMap = [];
            
            foreach ($dosenList as $dosen) {
                $pegawaiId = $dosen['pegawai_id'] ?? null;
                
                if ($pegawaiId) {
                    $jadwalList = $apiService->getJadwalByDosen(
                        $pegawaiId, 
                        $test['semester'], 
                        $test['tahun']
                    );
                    
                    if (!empty($jadwalList)) {
                        $dosenWithJadwal++;
                        $totalJadwal += count($jadwalList);
                        
                        // Build mapping
                        foreach ($jadwalList as $jadwal) {
                            $kodeMk = $jadwal['kode_mk'] ?? null;
                            if ($kodeMk) {
                                if (!isset($matkulDosenMap[$kodeMk])) {
                                    $matkulDosenMap[$kodeMk] = [];
                                }
                                $matkulDosenMap[$kodeMk][] = $dosen['nama'] ?? 'Unknown';
                            }
                        }
                    }
                }
            }
            
            $this->info("  Summary: {$dosenWithJadwal}/" . count($dosenList) . " dosen have jadwal, total {$totalJadwal} jadwal");
            
            // Check mapping
            if (!empty($matkulData)) {
                $matkulWithDosen = 0;
                foreach ($matkulData as $mk) {
                    if (isset($matkulDosenMap[$mk['kode_mk']])) {
                        $matkulWithDosen++;
                    }
                }
                $this->info("  Mapping: {$matkulWithDosen}/" . count($matkulData) . " matakuliah have dosen");
            }
            
            $this->newLine();
        }

        $this->info("=== Test Complete ===");
        $this->newLine();
        
        $this->comment("Recommendations:");
        $this->comment("1. If no matakuliah data: Check API endpoint and parameters");
        $this->comment("2. If no jadwal data: Check semester/tahun parameters in API");
        $this->comment("3. Run with --clear-cache to test without cache");
        
        return 0;
    }
}
