<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;

class DebugDosenPengampu extends Command
{
    protected $signature = 'debug:dosen-pengampu {kode_mk?} {--prodi=4} {--semester=1} {--ta=2020}';
    protected $description = 'Debug dosen pengampu untuk mata kuliah tertentu';

    public function handle()
    {
        $kodeMk = $this->argument('kode_mk');
        $prodiId = $this->option('prodi');
        $semester = $this->option('semester');
        $ta = $this->option('ta');

        $apiService = new ExternalAPIService();

        $this->info("=== DEBUG DOSEN PENGAMPU ===");
        $this->info("Prodi ID: {$prodiId}");
        $this->info("Semester: {$semester}");
        $this->info("Tahun Ajaran: {$ta}");
        
        if ($kodeMk) {
            $this->info("Kode MK: {$kodeMk}");
        }
        $this->newLine();

        // 1. Ambil daftar mata kuliah
        $this->info("1. Mengambil daftar mata kuliah...");
        $matkulList = $apiService->getMatkulByProdiSemTa($prodiId, $semester, $ta);
        $this->info("Total mata kuliah: " . count($matkulList));
        
        if ($kodeMk) {
            $matkulList = array_filter($matkulList, function($mk) use ($kodeMk) {
                return ($mk['kode_mk'] ?? '') === $kodeMk;
            });
            $this->info("Mata kuliah dengan kode {$kodeMk}: " . count($matkulList));
        }
        
        $this->newLine();

        // 2. Ambil daftar dosen yang ter-filter
        $this->info("2. Mengambil daftar dosen ter-filter...");
        $dosenList = $apiService->getFilteredDosen();
        $this->info("Total dosen ter-filter: " . count($dosenList));
        $this->newLine();

        // 3. Untuk setiap dosen, cek jadwal mereka
        $this->info("3. Memeriksa jadwal setiap dosen...");
        $matkulDosenMap = [];
        $dosenWithJadwal = 0;
        
        foreach ($dosenList as $index => $dosen) {
            $pegawaiId = $dosen['pegawai_id'] ?? null;
            $namaDosen = $dosen['nama'] ?? 'Unknown';
            
            if (!$pegawaiId) {
                continue;
            }
            
            $this->info("  [{$index}] Checking dosen: {$namaDosen} (ID: {$pegawaiId})");
            
            try {
                $jadwalList = $apiService->getJadwalByDosen($pegawaiId, $semester, $ta);
                
                if (!empty($jadwalList)) {
                    $dosenWithJadwal++;
                    $this->info("    ✓ Memiliki " . count($jadwalList) . " jadwal");
                    
                    foreach ($jadwalList as $jadwal) {
                        $kodeMkJadwal = $jadwal['kode_mk'] ?? null;
                        $namaMk = $jadwal['nama_mk'] ?? '-';
                        
                        if ($kodeMkJadwal) {
                            if (!isset($matkulDosenMap[$kodeMkJadwal])) {
                                $matkulDosenMap[$kodeMkJadwal] = [];
                            }
                            if (!in_array($namaDosen, $matkulDosenMap[$kodeMkJadwal])) {
                                $matkulDosenMap[$kodeMkJadwal][] = $namaDosen;
                            }
                            
                            // Jika kita mencari kode MK tertentu, tampilkan detail
                            if ($kodeMk && $kodeMkJadwal === $kodeMk) {
                                $this->warn("    >>> FOUND! {$kodeMkJadwal} - {$namaMk}");
                            }
                        }
                    }
                } else {
                    $this->comment("    - Tidak ada jadwal");
                }
            } catch (\Exception $e) {
                $this->error("    ✗ Error: " . $e->getMessage());
            }
        }
        
        $this->newLine();
        $this->info("Dosen dengan jadwal: {$dosenWithJadwal} dari " . count($dosenList));
        $this->newLine();

        // 4. Tampilkan hasil mapping
        if ($kodeMk) {
            $this->info("4. Hasil untuk kode MK: {$kodeMk}");
            if (isset($matkulDosenMap[$kodeMk])) {
                $this->info("Dosen pengampu:");
                foreach ($matkulDosenMap[$kodeMk] as $dosen) {
                    $this->info("  - {$dosen}");
                }
            } else {
                $this->error("TIDAK ADA DOSEN PENGAMPU DITEMUKAN!");
                $this->newLine();
                
                // Cek apakah mata kuliah ada di daftar
                $mkExists = false;
                foreach ($matkulList as $mk) {
                    if (($mk['kode_mk'] ?? '') === $kodeMk) {
                        $mkExists = true;
                        $this->info("Mata kuliah ditemukan di API:");
                        $this->info("  Kode: " . ($mk['kode_mk'] ?? '-'));
                        $this->info("  Nama: " . ($mk['nama_matkul'] ?? '-'));
                        $this->info("  Kuliah ID: " . ($mk['kuliah_id'] ?? '-'));
                        break;
                    }
                }
                
                if (!$mkExists) {
                    $this->error("Mata kuliah {$kodeMk} TIDAK DITEMUKAN di API!");
                }
            }
        } else {
            $this->info("4. Ringkasan mapping (10 pertama):");
            $count = 0;
            foreach ($matkulDosenMap as $kodeMk => $dosenNames) {
                if ($count >= 10) break;
                $this->info("  {$kodeMk}: " . implode(', ', $dosenNames));
                $count++;
            }
            $this->info("Total mata kuliah dengan dosen: " . count($matkulDosenMap));
        }

        return 0;
    }
}
