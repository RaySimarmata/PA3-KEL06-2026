<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JadwalDosen;
use App\Services\ExternalAPIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DebugMonitoringPerkuliahanDosen extends Command
{
    protected $signature = 'debug:monitoring-perkuliahan-dosen {semester=1} {tahun=2025}';
    protected $description = 'Debug dosen mapping untuk Monitoring Perkuliahan';

    public function handle()
    {
        $semester = $this->argument('semester');
        $tahun = $this->argument('tahun');

        $this->info("=== DEBUG MONITORING PERKULIAHAN DOSEN ===");
        $this->info("Semester: {$semester}");
        $this->info("Tahun: {$tahun}");
        $this->newLine();

        // 1. Check Database
        $this->info("1. Checking JadwalDosen from Database...");
        
        $jadwalFromDB = JadwalDosen::with('dosen')
            ->where(function ($q) use ($semester) {
                $q->where('semester', $semester);
                if ($semester == '1') {
                    $q->orWhere('semester', 'Ganjil');
                }
                if ($semester == '2') {
                    $q->orWhere('semester', 'Genap');
                }
            })
            ->where(function ($q) use ($tahun) {
                $q->where('tahun_ajaran', $tahun)
                  ->orWhere('tahun_ajaran', 'LIKE', $tahun . '%');
            })
            ->limit(10)
            ->get();

        $this->info("Found {$jadwalFromDB->count()} jadwal from database");
        
        if ($jadwalFromDB->count() > 0) {
            $this->table(
                ['Kode MK', 'Pegawai ID', 'Nama Dosen', 'Semester', 'Tahun'],
                $jadwalFromDB->map(fn($j) => [
                    $j->kode_mk,
                    $j->pegawai_id,
                    $j->dosen->nama ?? '-',
                    $j->semester,
                    $j->tahun_ajaran
                ])
            );
        } else {
            $this->warn("⚠️ No jadwal found in database!");
        }

        $this->newLine();

        // 2. Build Mapping
        $this->info("2. Building Matkul-Dosen Mapping...");
        
        $matkulDosenMap = [];
        foreach ($jadwalFromDB as $jadwal) {
            $kodeMk = trim($jadwal->kode_mk ?? '');
            if (!$kodeMk || !$jadwal->dosen) continue;

            if (!isset($matkulDosenMap[$kodeMk])) {
                $matkulDosenMap[$kodeMk] = [];
            }

            $exists = collect($matkulDosenMap[$kodeMk])
                ->contains(fn($d) => $d['pegawai_id'] == $jadwal->pegawai_id);

            if (!$exists) {
                $matkulDosenMap[$kodeMk][] = [
                    'pegawai_id' => $jadwal->pegawai_id,
                    'nama' => $jadwal->dosen->nama ?? '-',
                ];
            }
        }

        $this->info("Built mapping for " . count($matkulDosenMap) . " matakuliah");
        
        if (count($matkulDosenMap) > 0) {
            $sample = array_slice($matkulDosenMap, 0, 5, true);
            foreach ($sample as $kodeMk => $dosenList) {
                $dosenNames = implode(', ', array_column($dosenList, 'nama'));
                $this->line("  {$kodeMk} => {$dosenNames}");
            }
        }

        $this->newLine();

        // 3. Check API (optional)
        $this->info("3. Checking API data...");
        try {
            $apiService = new ExternalAPIService();
            $dosenList = $apiService->getFilteredDosen();
            $this->info("API returned " . count($dosenList) . " dosen");
            
            if (count($dosenList) > 0) {
                $this->line("Sample dosen: " . ($dosenList[0]['nama'] ?? 'N/A'));
            }
        } catch (\Exception $e) {
            $this->error("API Error: " . $e->getMessage());
        }

        $this->newLine();

        // 4. Cache Info
        $this->info("4. Cache Information");
        $cacheKey = "matkul_dosen_map_perkuliahan_4_{$semester}_{$tahun}";
        $cached = Cache::has($cacheKey);
        $this->line("Cache key: {$cacheKey}");
        $this->line("Cached: " . ($cached ? 'YES ✓' : 'NO ✗'));

        if ($cached) {
            $this->warn("💡 Run 'php artisan cache:clear' to refresh");
        }

        $this->newLine();
        $this->info("=== DEBUG COMPLETE ===");
        
        return 0;
    }
}
