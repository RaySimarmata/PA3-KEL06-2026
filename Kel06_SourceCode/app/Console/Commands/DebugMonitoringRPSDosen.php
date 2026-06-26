<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DebugMonitoringRPSDosen extends Command
{
    protected $signature = 'debug:monitoring-rps-dosen {semester=2} {tahun=2025}';
    protected $description = 'Debug dosen mapping untuk Monitoring RPS';

    public function handle()
    {
        $semester = $this->argument('semester');
        $tahun = $this->argument('tahun');

        $this->info("=== DEBUG MONITORING RPS DOSEN ===");
        $this->info("Semester: {$semester} (" . ($semester == 1 ? 'Ganjil' : 'Genap') . ")");
        $this->info("Tahun: {$tahun}");
        $this->newLine();

        // 1. Check Database - Exact Match
        $this->info("1. Checking JadwalDosen (Exact Match)...");
        
        $exactMatch = DB::table('jadwal_dosen as jd')
            ->leftJoin('dosenn as d', 'jd.pegawai_id', '=', 'd.pegawai_id')
            ->where('jd.semester', $semester)
            ->where('jd.tahun_ajaran', $tahun)
            ->select('jd.kode_mk', 'jd.pegawai_id', 'd.nama', 'jd.semester', 'jd.tahun_ajaran')
            ->limit(10)
            ->get();

        $this->info("Exact match found: {$exactMatch->count()} records");
        
        if ($exactMatch->count() > 0) {
            $this->table(
                ['Kode MK', 'Pegawai ID', 'Nama Dosen', 'Semester', 'Tahun'],
                $exactMatch->map(fn($j) => [
                    $j->kode_mk,
                    $j->pegawai_id,
                    $j->nama ?? '-',
                    $j->semester,
                    $j->tahun_ajaran
                ])
            );
        }

        $this->newLine();

        // 2. Check with Fallback (like controller logic)
        $this->info("2. Checking with Fallback Logic...");
        
        $withFallback = DB::table('jadwal_dosen as jd')
            ->leftJoin('dosenn as d', 'jd.pegawai_id', '=', 'd.pegawai_id')
            ->where(function ($q) use ($semester) {
                $q->where('jd.semester', $semester);
                
                if ($semester == '1') {
                    $q->orWhere('jd.semester', 'Ganjil')
                      ->orWhere('jd.semester', 'ganjil');
                }
                if ($semester == '2') {
                    $q->orWhere('jd.semester', 'Genap')
                      ->orWhere('jd.semester', 'genap');
                }
            })
            ->where(function ($q) use ($tahun) {
                $q->where('jd.tahun_ajaran', $tahun)
                  ->orWhere('jd.tahun_ajaran', 'LIKE', $tahun . '%');
            })
            ->select('jd.kode_mk', 'jd.pegawai_id', 'd.nama', 'jd.semester', 'jd.tahun_ajaran')
            ->limit(10)
            ->get();

        $this->info("With fallback found: {$withFallback->count()} records");
        
        if ($withFallback->count() > 0) {
            $this->table(
                ['Kode MK', 'Pegawai ID', 'Nama Dosen', 'Semester', 'Tahun'],
                $withFallback->map(fn($j) => [
                    $j->kode_mk,
                    $j->pegawai_id,
                    $j->nama ?? '-',
                    $j->semester,
                    $j->tahun_ajaran
                ])
            );
        } else {
            $this->warn("⚠️ No records found with fallback logic!");
        }

        $this->newLine();

        // 3. Check all available semesters
        $this->info("3. Checking All Available Semesters in DB...");
        
        $allSemesters = DB::table('jadwal_dosen')
            ->select('semester', DB::raw('COUNT(*) as count'))
            ->groupBy('semester')
            ->get();

        $this->table(
            ['Semester', 'Count'],
            $allSemesters->map(fn($s) => [$s->semester, $s->count])
        );

        $this->newLine();

        // 4. Check all available tahun ajaran
        $this->info("4. Checking All Available Tahun Ajaran in DB...");
        
        $allTahun = DB::table('jadwal_dosen')
            ->select('tahun_ajaran', DB::raw('COUNT(*) as count'))
            ->groupBy('tahun_ajaran')
            ->orderBy('tahun_ajaran', 'DESC')
            ->get();

        $this->table(
            ['Tahun Ajaran', 'Count'],
            $allTahun->map(fn($t) => [$t->tahun_ajaran, $t->count])
        );

        $this->newLine();

        // 5. Build mapping (like controller)
        $this->info("5. Building Matkul-Dosen Map...");
        
        $matkulDosenMap = [];
        foreach ($withFallback as $item) {
            $kodeMk = trim($item->kode_mk ?? '');
            if (!$kodeMk) continue;

            if (!isset($matkulDosenMap[$kodeMk])) {
                $matkulDosenMap[$kodeMk] = [];
            }

            $matkulDosenMap[$kodeMk][] = [
                'pegawai_id' => $item->pegawai_id,
                'nama' => $item->nama ?? '-',
            ];
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

        // 6. Cache Info
        $this->info("6. Cache Information");
        $cacheKey = "jadwal_dosen_rps_{$semester}_{$tahun}";
        $cached = Cache::has($cacheKey);
        $this->line("Cache key: {$cacheKey}");
        $this->line("Cached: " . ($cached ? 'YES ✓' : 'NO ✗'));

        if ($cached) {
            $this->warn("💡 Run 'php artisan cache:clear' to refresh");
        }

        $this->newLine();

        // 7. Recommendations
        $this->info("7. Recommendations");
        
        if ($withFallback->count() == 0) {
            $this->error("❌ No data found! You need to:");
            $this->line("   1. Run: php artisan sync:jadwal-dosen");
            $this->line("   2. Or manually insert data to jadwal_dosen table");
            $this->line("   3. Make sure semester format matches: '1', '2', 'Ganjil', or 'Genap'");
            $this->line("   4. Make sure tahun_ajaran format matches: '2025' or '2025/2026'");
        } else {
            $this->info("✅ Data found! If still not showing:");
            $this->line("   1. Clear cache: php artisan cache:clear");
            $this->line("   2. Click 'Refresh Data' button in UI");
            $this->line("   3. Check logs: storage/logs/laravel.log");
        }

        $this->newLine();
        $this->info("=== DEBUG COMPLETE ===");
        
        return 0;
    }
}
