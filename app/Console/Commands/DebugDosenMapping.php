<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;
use Illuminate\Support\Facades\Cache;

class DebugDosenMapping extends Command
{
    protected $signature = 'debug:dosen-mapping {pegawai_id} {semester=1} {tahun=2020}';
    protected $description = 'Debug kenapa dosen tertentu tidak muncul di mapping';

    public function handle()
    {
        $pegawaiId = $this->argument('pegawai_id');
        $semester = $this->argument('semester');
        $tahun = $this->argument('tahun');

        $this->info("=== DEBUG DOSEN MAPPING ===");
        $this->info("Pegawai ID: {$pegawaiId}");
        $this->info("Semester: {$semester}");
        $this->info("Tahun: {$tahun}");
        $this->newLine();

        $apiService = new ExternalAPIService();

        // 1. Check if dosen exists
        $this->info("1. Checking if dosen exists...");
        $allDosen = $apiService->getDosenByProdiIds([1, 3, 4]);
        
        $targetDosen = null;
        foreach ($allDosen as $dosen) {
            if (($dosen['pegawai_id'] ?? null) == $pegawaiId) {
                $targetDosen = $dosen;
                break;
            }
        }

        if (!$targetDosen) {
            $this->error("   ✗ Dosen dengan pegawai_id {$pegawaiId} TIDAK DITEMUKAN!");
            $this->warn("   Kemungkinan:");
            $this->warn("   - Dosen tidak memiliki prodi_id 1, 3, atau 4");
            $this->warn("   - Dosen tidak ada di API");
            $this->newLine();
            
            // Try to find in all dosen
            $this->info("   Mencari di semua dosen...");
            $allDosenUnfiltered = $apiService->getDosen();
            foreach ($allDosenUnfiltered as $dosen) {
                if (($dosen['pegawai_id'] ?? null) == $pegawaiId) {
                    $this->info("   ✓ Dosen ditemukan di semua dosen!");
                    $this->table(
                        ['Field', 'Value'],
                        [
                            ['Nama', $dosen['nama'] ?? '-'],
                            ['Pegawai ID', $dosen['pegawai_id'] ?? '-'],
                            ['Prodi ID', $dosen['prodi_id'] ?? '-'],
                            ['Prodi', $dosen['prodi'] ?? '-'],
                        ]
                    );
                    $this->warn("   ⚠️  Dosen ini memiliki prodi_id: " . ($dosen['prodi_id'] ?? 'null'));
                    $this->warn("   Filter hanya mengambil prodi_id 1, 3, 4");
                    return 1;
                }
            }
            
            $this->error("   ✗ Dosen tidak ditemukan sama sekali di API!");
            return 1;
        }

        $this->info("   ✓ Dosen ditemukan!");
        $this->table(
            ['Field', 'Value'],
            [
                ['Nama', $targetDosen['nama'] ?? '-'],
                ['Pegawai ID', $targetDosen['pegawai_id'] ?? '-'],
                ['Prodi ID', $targetDosen['prodi_id'] ?? '-'],
                ['Prodi', $targetDosen['prodi'] ?? '-'],
            ]
        );
        $this->newLine();

        // 2. Check position in filtered list
        $this->info("2. Checking position in filtered list...");
        $prodiId = $targetDosen['prodi_id'] ?? null;
        
        if ($prodiId) {
            $filteredDosen = array_filter($allDosen, function($dosen) use ($prodiId) {
                return isset($dosen['prodi_id']) && $dosen['prodi_id'] == $prodiId;
            });
            
            $position = 0;
            foreach (array_values($filteredDosen) as $index => $dosen) {
                if (($dosen['pegawai_id'] ?? null) == $pegawaiId) {
                    $position = $index + 1;
                    break;
                }
            }
            
            $this->info("   Position: {$position} / " . count($filteredDosen));
            
            if ($position > 50) {
                $this->warn("   ⚠️  Dosen berada di posisi {$position}, melebihi limit 50!");
                $this->warn("   Dosen ini tidak akan diproses karena batasan limit.");
            } else {
                $this->info("   ✓ Dosen dalam limit 50 pertama");
            }
        }
        $this->newLine();

        // 3. Get jadwal for this dosen
        $this->info("3. Getting jadwal for this dosen...");
        $jadwalList = $apiService->getJadwalByDosen($pegawaiId, $semester, $tahun);
        
        if (empty($jadwalList)) {
            $this->warn("   ⚠️  Tidak ada jadwal untuk dosen ini!");
            $this->warn("   Semester: {$semester}, Tahun: {$tahun}");
            $this->newLine();
            
            // Try other semesters
            $this->info("   Mencoba semester lain...");
            $otherSemester = $semester == 1 ? 2 : 1;
            $jadwalOther = $apiService->getJadwalByDosen($pegawaiId, $otherSemester, $tahun);
            
            if (!empty($jadwalOther)) {
                $this->info("   ✓ Ditemukan jadwal di semester {$otherSemester}!");
                $this->table(
                    ['Kode MK', 'Nama MK', 'SKS'],
                    array_map(function($j) {
                        return [
                            $j['kode_mk'] ?? '-',
                            $j['nama_mk'] ?? '-',
                            $j['sks'] ?? '-'
                        ];
                    }, array_slice($jadwalOther, 0, 5))
                );
            } else {
                $this->warn("   ⚠️  Tidak ada jadwal di semester {$otherSemester} juga");
            }
            
            return 1;
        }

        $this->info("   ✓ Ditemukan " . count($jadwalList) . " jadwal");
        $this->table(
            ['Kode MK', 'Nama MK', 'SKS'],
            array_map(function($j) {
                return [
                    $j['kode_mk'] ?? '-',
                    $j['nama_mk'] ?? '-',
                    $j['sks'] ?? '-'
                ];
            }, $jadwalList)
        );
        $this->newLine();

        // 4. Check if matkul exists in monitoring
        $this->info("4. Checking if matkul exists in monitoring...");
        $matkulData = $apiService->getMatkulByProdiSemTa($prodiId, $semester, $tahun);
        
        $this->info("   Total matakuliah di prodi {$prodiId}: " . count($matkulData));
        
        $foundInMonitoring = [];
        foreach ($jadwalList as $jadwal) {
            $kodeMk = $jadwal['kode_mk'] ?? null;
            if ($kodeMk) {
                foreach ($matkulData as $mk) {
                    if (($mk['kode_mk'] ?? null) == $kodeMk) {
                        $foundInMonitoring[] = [
                            'kode' => $kodeMk,
                            'nama' => $mk['nama_matkul'] ?? '-',
                            'kuliah_id' => $mk['kuliah_id'] ?? '-'
                        ];
                    }
                }
            }
        }
        
        if (empty($foundInMonitoring)) {
            $this->warn("   ⚠️  Tidak ada matakuliah yang cocok di monitoring!");
            $this->warn("   Kemungkinan:");
            $this->warn("   - Matakuliah tidak ada di semester/tahun ini");
            $this->warn("   - Kode matakuliah berbeda");
        } else {
            $this->info("   ✓ Ditemukan " . count($foundInMonitoring) . " matakuliah yang cocok:");
            $this->table(
                ['Kode MK', 'Nama MK', 'Kuliah ID'],
                $foundInMonitoring
            );
        }
        $this->newLine();

        // 5. Check cache
        $this->info("5. Checking cache...");
        $cacheKey = "matkul_dosen_map_{$prodiId}_{$semester}_{$tahun}";
        $cachedMap = Cache::get($cacheKey);
        
        if ($cachedMap) {
            $this->info("   ✓ Cache ditemukan");
            
            $dosenName = $targetDosen['nama'] ?? null;
            $foundInCache = [];
            
            foreach ($jadwalList as $jadwal) {
                $kodeMk = $jadwal['kode_mk'] ?? null;
                if ($kodeMk && isset($cachedMap[$kodeMk])) {
                    if (in_array($dosenName, $cachedMap[$kodeMk])) {
                        $foundInCache[] = $kodeMk;
                    }
                }
            }
            
            if (empty($foundInCache)) {
                $this->warn("   ⚠️  Dosen tidak ditemukan di cache mapping!");
                $this->warn("   Kemungkinan:");
                $this->warn("   - Cache dibuat sebelum dosen ini diproses");
                $this->warn("   - Dosen melebihi limit 50");
                $this->warn("   - Dosen melebihi limit 30 untuk mapping");
            } else {
                $this->info("   ✓ Dosen ditemukan di cache untuk matkul:");
                foreach ($foundInCache as $kode) {
                    $this->line("     - {$kode}");
                }
            }
        } else {
            $this->warn("   ⚠️  Cache tidak ditemukan");
            $this->info("   Jalankan: php artisan preload:monitoring-rps-fast {$prodiId} {$semester} {$tahun}");
        }
        $this->newLine();

        // Summary
        $this->info("=== SUMMARY ===");
        $this->line("Dosen: " . ($targetDosen['nama'] ?? '-'));
        $this->line("Prodi ID: " . ($targetDosen['prodi_id'] ?? '-'));
        $this->line("Jadwal: " . count($jadwalList) . " matakuliah");
        $this->line("Cocok di monitoring: " . count($foundInMonitoring) . " matakuliah");
        
        if (count($foundInMonitoring) > 0 && empty($foundInCache ?? [])) {
            $this->newLine();
            $this->warn("REKOMENDASI:");
            $this->warn("1. Clear cache: php artisan cache:clear");
            $this->warn("2. Preload ulang: php artisan preload:monitoring-rps-fast {$prodiId} {$semester} {$tahun}");
            $this->warn("3. Atau tingkatkan limit dosen di MonitoringRPSController.php");
        }

        return 0;
    }
}
