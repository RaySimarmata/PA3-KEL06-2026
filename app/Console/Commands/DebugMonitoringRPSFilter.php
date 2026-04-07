<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DebugMonitoringRPSFilter extends Command
{
    protected $signature = 'debug:monitoring-rps-filter {prodi_id} {semester} {tahun_ajaran}';
    protected $description = 'Debug Monitoring RPS filter untuk semester ganjil dan genap';

    public function handle()
    {
        $prodiId = $this->argument('prodi_id');
        $semester = $this->argument('semester');
        $tahunAjaran = $this->argument('tahun_ajaran');

        $this->info("=== DEBUG MONITORING RPS FILTER ===");
        $this->info("Prodi ID: {$prodiId}");
        $this->info("Semester: {$semester} (" . ($semester == 1 ? 'Ganjil' : 'Genap') . ")");
        $this->info("Tahun Ajaran: {$tahunAjaran}");
        $this->newLine();

        $apiService = new ExternalAPIService();

        // 1. Test Matakuliah API
        $this->info("1. Testing Matakuliah API...");
        $matkulData = $apiService->getMatkulByProdiSemTa($prodiId, $semester, $tahunAjaran);
        
        if (empty($matkulData)) {
            $this->error("❌ Tidak ada data matakuliah dari API!");
            $this->warn("Kemungkinan penyebab:");
            $this->warn("- API tidak memiliki data untuk kombinasi prodi/semester/tahun ini");
            $this->warn("- Token API expired atau tidak valid");
            $this->warn("- Parameter yang dikirim tidak sesuai format API");
            return 1;
        }
        
        $this->info("✓ Ditemukan " . count($matkulData) . " matakuliah");
        $this->table(
            ['Kode MK', 'Nama Matakuliah', 'Kuliah ID'],
            array_slice(array_map(function($mk) {
                return [
                    $mk['kode_mk'] ?? '-',
                    substr($mk['nama_matkul'] ?? '-', 0, 40),
                    $mk['kuliah_id'] ?? '-'
                ];
            }, $matkulData), 0, 5)
        );
        $this->newLine();

        // 2. Test Dosen API
        $this->info("2. Testing Dosen API...");
        $dosenList = $apiService->getFilteredDosen();
        
        if (empty($dosenList)) {
            $this->error("❌ Tidak ada data dosen dari API!");
            return 1;
        }
        
        $dosenCount = count($dosenList);
        $this->info("✓ Ditemukan {$dosenCount} dosen");
        $this->newLine();

        // 3. Test Jadwal Dosen untuk SEMUA dosen
        $this->info("3. Testing Jadwal Dosen (processing ALL {$dosenCount} dosen)...");
        $this->info("   This may take a while...");
        $dosenWithJadwal = 0;
        $matkulDosenMap = [];
        $totalJadwal = 0;
        
        $progressBar = $this->output->createProgressBar(count($dosenList));
        $progressBar->start();
        
        foreach ($dosenList as $dosen) {
            $pegawaiId = $dosen['pegawai_id'] ?? null;
            $namaDosen = $dosen['nama'] ?? null;
            
            if ($pegawaiId && $namaDosen) {
                $jadwalList = $apiService->getJadwalByDosen($pegawaiId, $semester, $tahunAjaran);
                
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
                            if (!in_array($namaDosen, $matkulDosenMap[$kodeMk])) {
                                $matkulDosenMap[$kodeMk][] = $namaDosen;
                            }
                        }
                    }
                }
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        $this->newLine();
        
        $this->info("Dosen dengan jadwal: {$dosenWithJadwal}/" . count($dosenList));
        $this->info("Total jadwal ditemukan: {$totalJadwal}");
        $this->newLine();

        // 4. Check mapping matkul -> dosen
        $this->info("4. Checking Matkul-Dosen Mapping...");
        $matkulWithDosen = 0;
        $matkulWithoutDosen = 0;
        
        foreach ($matkulData as $matkul) {
            $kodeMk = $matkul['kode_mk'] ?? null;
            if ($kodeMk && isset($matkulDosenMap[$kodeMk])) {
                $matkulWithDosen++;
            } else {
                $matkulWithoutDosen++;
            }
        }
        
        $this->info("Matakuliah dengan dosen: {$matkulWithDosen}");
        $this->warn("Matakuliah tanpa dosen: {$matkulWithoutDosen}");
        
        if ($matkulWithoutDosen > 0) {
            $this->newLine();
            $this->warn("⚠ Masalah ditemukan: Ada matakuliah yang tidak memiliki dosen pengampu");
            $this->warn("Kemungkinan penyebab:");
            $this->warn("- Dosen tidak memiliki jadwal untuk semester/tahun ini di API");
            $this->warn("- Mapping kode_mk antara API matakuliah dan jadwal tidak cocok");
            $this->newLine();
            
            // Show sample matkul without dosen
            $this->info("Sample matakuliah tanpa dosen:");
            $count = 0;
            foreach ($matkulData as $matkul) {
                $kodeMk = $matkul['kode_mk'] ?? null;
                if ($kodeMk && !isset($matkulDosenMap[$kodeMk])) {
                    $this->line("  - {$kodeMk}: {$matkul['nama_matkul']}");
                    $count++;
                    if ($count >= 5) break;
                }
            }
        } else {
            $this->newLine();
            $this->info("✓ Semua matakuliah memiliki dosen pengampu!");
        }
        
        $this->newLine();

        // 5. Sample mapping result
        if (!empty($matkulDosenMap)) {
            $this->info("5. Sample Mapping Result:");
            $sampleMapping = array_slice($matkulDosenMap, 0, 5, true);
            foreach ($sampleMapping as $kodeMk => $dosenList) {
                $this->info("  {$kodeMk}: " . implode(', ', $dosenList));
            }
        }

        $this->newLine();
        $this->info("=== DEBUG SELESAI ===");
        
        return 0;
    }
}
