<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;

class DebugMonitoringRPS extends Command
{
    protected $signature = 'debug:monitoring-rps';
    protected $description = 'Debug Monitoring RPS dosen mapping';

    public function handle()
    {
        $apiService = new ExternalAPIService();

        $this->info('Debugging Monitoring RPS...');
        $this->newLine();

        // 1. Get matakuliah for TRPL
        $this->info('1. Matakuliah from matkul-by-prodi-sem-ta (prodi_id=4, sem_ta=1, ta=2020)');
        $matkulData = $apiService->getMatkulByProdiSemTa(4, 1, 2020);
        
        // Find "Pembentukan Karakter Del"
        $pembentukan = null;
        foreach ($matkulData as $mk) {
            if (stripos($mk['nama_matkul'] ?? '', 'Pembentukan Karakter') !== false) {
                $pembentukan = $mk;
                break;
            }
        }
        
        if ($pembentukan) {
            $this->info('   Found: ' . ($pembentukan['nama_matkul'] ?? 'N/A'));
            $this->line('   Kode MK: ' . ($pembentukan['kode_mk'] ?? 'N/A'));
            $this->line('   Kuliah ID: ' . ($pembentukan['kuliah_id'] ?? 'N/A'));
        } else {
            $this->error('   "Pembentukan Karakter Del" not found!');
        }
        
        $this->newLine();

        // 2. Get filtered dosen
        $this->info('2. Filtered Dosen (TI, TK, TRPL + Tenaga Pengajar)');
        $dosenList = $apiService->getFilteredDosen();
        $this->info('   Total: ' . count($dosenList) . ' dosen');
        
        // Find Arnaldo
        $arnaldo = null;
        foreach ($dosenList as $dosen) {
            if (stripos($dosen['nama'] ?? '', 'Arnaldo') !== false) {
                $arnaldo = $dosen;
                break;
            }
        }
        
        if ($arnaldo) {
            $this->info('   Found Arnaldo:');
            $this->line('   - Nama: ' . ($arnaldo['nama'] ?? 'N/A'));
            $this->line('   - Pegawai ID: ' . ($arnaldo['pegawai_id'] ?? 'N/A'));
            $this->line('   - Prodi: ' . ($arnaldo['prodi'] ?? 'N/A'));
            $this->line('   - Jabatan: ' . ($arnaldo['jabatan_akademik_desc'] ?? 'N/A'));
            
            $this->newLine();
            
            // 3. Get jadwal for Arnaldo
            $pegawaiId = $arnaldo['pegawai_id'] ?? null;
            if ($pegawaiId) {
                $this->info('3. Jadwal for Arnaldo (pegawai_id=' . $pegawaiId . ', sem_ta=1, ta=2020)');
                $jadwal = $apiService->getJadwalByDosen($pegawaiId, 1, 2020);
                $this->info('   Total jadwal: ' . count($jadwal));
                
                // Find "Pembentukan Karakter Del" in jadwal
                $found = false;
                foreach ($jadwal as $j) {
                    if (stripos($j['nama_mk'] ?? '', 'Pembentukan Karakter') !== false) {
                        $this->info('   Found in jadwal:');
                        $this->line('   - Kode MK: ' . ($j['kode_mk'] ?? 'N/A'));
                        $this->line('   - Nama MK: ' . ($j['nama_mk'] ?? 'N/A'));
                        $found = true;
                    }
                }
                
                if (!$found) {
                    $this->error('   "Pembentukan Karakter Del" NOT found in Arnaldo jadwal!');
                    $this->line('   Sample jadwal (first 3):');
                    foreach (array_slice($jadwal, 0, 3) as $j) {
                        $this->line('   - ' . ($j['kode_mk'] ?? 'N/A') . ': ' . ($j['nama_mk'] ?? 'N/A'));
                    }
                }
            }
        } else {
            $this->error('   Arnaldo not found in filtered dosen!');
            $this->line('   Sample dosen (first 5):');
            foreach (array_slice($dosenList, 0, 5) as $d) {
                $this->line('   - ' . ($d['nama'] ?? 'N/A') . ' (Pegawai ID: ' . ($d['pegawai_id'] ?? 'N/A') . ')');
            }
        }

        $this->newLine();
        $this->info('Debug completed!');
    }
}
