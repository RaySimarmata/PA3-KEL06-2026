<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;

class TestMonitoringRPSAPI extends Command
{
    protected $signature = 'test:monitoring-rps-api';
    protected $description = 'Test Monitoring RPS API endpoints';

    public function handle()
    {
        $apiService = new ExternalAPIService();

        $this->info('Testing Monitoring RPS API...');
        $this->newLine();

        // Test 1: Get Matakuliah by Prodi Sem TA
        $this->info('1. Testing matkul-by-prodi-sem-ta API');
        $this->info('   Parameters: prodi_id=4, sem_ta=1, ta=2020');
        
        $matkulData = $apiService->getMatkulByProdiSemTa(4, 1, 2020);
        
        if (!empty($matkulData)) {
            $this->info('   ✓ Success! Found ' . count($matkulData) . ' matakuliah');
            $this->newLine();
            
            // Show first 3 items
            $this->info('   Sample data (first 3 items):');
            $counter = 1;
            foreach (array_slice($matkulData, 0, 3) as $matkul) {
                $this->line('   ' . $counter . '. Kode MK: ' . ($matkul['kode_mk'] ?? 'N/A'));
                $this->line('      Nama: ' . ($matkul['nama_matkul'] ?? 'N/A'));
                $this->line('      Dosen: ' . ($matkul['nama_dosen'] ?? 'N/A'));
                $this->line('      Kuliah ID: ' . ($matkul['kuliah_id'] ?? 'N/A'));
                $this->newLine();
                $counter++;
            }

            // Test 2: Get Monitoring Materi for first kuliah_id
            if (isset($matkulData[0]['kuliah_id'])) {
                $kuliahId = $matkulData[0]['kuliah_id'];
                $this->info('2. Testing get-monitoring-materi API');
                $this->info('   Parameters: kuliah_id=' . $kuliahId . ', ta=2020, sem_ta=1');
                
                $monitoring = $apiService->getMonitoringMateri($kuliahId, 2020, 1);
                
                if ($monitoring) {
                    $this->info('   ✓ Success!');
                    $this->line('   Status RPS: ' . ($monitoring['status_file_silabus'] ?? 'N/A'));
                    $this->newLine();
                } else {
                    $this->error('   ✗ Failed to get monitoring data');
                    $this->newLine();
                }
            }
        } else {
            $this->error('   ✗ No data returned from API');
            $this->newLine();
        }

        // Test 3: Get Tahun Ajaran
        $this->info('3. Testing tahun-ajaran API');
        $tahunAjaranList = $apiService->getTahunAjaran();
        
        if (!empty($tahunAjaranList)) {
            $this->info('   ✓ Success! Found ' . count($tahunAjaranList) . ' tahun ajaran');
            foreach ($tahunAjaranList as $ta) {
                $this->line('   - ' . ($ta['nm_thn_ajaran'] ?? 'N/A') . ' (ID: ' . ($ta['id_thn_ajaran'] ?? 'N/A') . ')');
            }
        } else {
            $this->error('   ✗ No tahun ajaran data');
        }

        $this->newLine();
        $this->info('Test completed!');
    }
}
