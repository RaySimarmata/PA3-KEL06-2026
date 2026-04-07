<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;

class TestDosenJadwalMapping extends Command
{
    protected $signature = 'test:dosen-jadwal-mapping';
    protected $description = 'Test dosen and jadwal mapping for Monitoring RPS';

    public function handle()
    {
        $apiService = new ExternalAPIService();

        $this->info('Testing Dosen-Jadwal Mapping...');
        $this->newLine();

        // Get matakuliah for TRPL
        $this->info('1. Getting matakuliah for TRPL (prodi_id=4, sem_ta=1, ta=2020)');
        $matkulData = $apiService->getMatkulByProdiSemTa(4, 1, 2020);
        
        if (empty($matkulData)) {
            $this->error('   ✗ No matakuliah data');
            return;
        }
        
        $this->info('   ✓ Found ' . count($matkulData) . ' matakuliah');
        $this->newLine();

        // Get first 3 matkul kode_mk
        $sampleKodeMk = array_slice(array_column($matkulData, 'kode_mk'), 0, 3);
        $this->info('   Sample kode_mk: ' . implode(', ', $sampleKodeMk));
        $this->newLine();

        // Get all dosen
        $this->info('2. Getting all dosen from API');
        $dosenList = $apiService->getDosen();
        
        if (empty($dosenList)) {
            $this->error('   ✗ No dosen data');
            return;
        }
        
        $this->info('   ✓ Found ' . count($dosenList) . ' dosen');
        $this->newLine();

        // Build mapping
        $this->info('3. Building kode_mk => dosen mapping');
        $matkulDosenMap = [];
        $dosenWithJadwal = 0;
        
        foreach ($dosenList as $index => $dosen) {
            $pegawaiId = $dosen['id'] ?? null;
            $namaDosen = $dosen['nama'] ?? null;
            
            if (!$pegawaiId || !$namaDosen) {
                continue;
            }
            
            // Get jadwal for this dosen
            $jadwalList = $apiService->getJadwalByDosen($pegawaiId, 1, 2020);
            
            if (!empty($jadwalList)) {
                $dosenWithJadwal++;
                
                // Show first dosen with jadwal
                if ($dosenWithJadwal == 1) {
                    $this->info('   First dosen with jadwal:');
                    $this->line('   - Pegawai ID: ' . $pegawaiId);
                    $this->line('   - Nama: ' . $namaDosen);
                    $this->line('   - Jadwal count: ' . count($jadwalList));
                    $this->line('   - Sample kode_mk from jadwal: ' . implode(', ', array_slice(array_column($jadwalList, 'kode_mk'), 0, 3)));
                    $this->newLine();
                }
                
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
            
            // Progress indicator
            if (($index + 1) % 10 == 0) {
                $this->line('   Processed ' . ($index + 1) . '/' . count($dosenList) . ' dosen...');
            }
        }
        
        $this->newLine();
        $this->info('   ✓ Mapping complete!');
        $this->line('   - Dosen with jadwal: ' . $dosenWithJadwal);
        $this->line('   - Unique kode_mk mapped: ' . count($matkulDosenMap));
        $this->newLine();

        // Check mapping for sample kode_mk
        $this->info('4. Checking mapping for sample kode_mk');
        foreach ($sampleKodeMk as $kodeMk) {
            if (isset($matkulDosenMap[$kodeMk])) {
                $this->line('   ✓ ' . $kodeMk . ': ' . implode(', ', $matkulDosenMap[$kodeMk]));
            } else {
                $this->line('   ✗ ' . $kodeMk . ': No dosen found');
            }
        }

        $this->newLine();
        $this->info('Test completed!');
    }
}
