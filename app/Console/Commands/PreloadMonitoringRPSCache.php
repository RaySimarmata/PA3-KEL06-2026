<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;
use Illuminate\Support\Facades\Cache;

class PreloadMonitoringRPSCache extends Command
{
    protected $signature = 'cache:preload-monitoring-rps {--prodi=4} {--semester=1} {--ta=2020}';
    protected $description = 'Preload monitoring RPS cache untuk performa maksimal';

    public function handle()
    {
        $prodiId = $this->option('prodi');
        $semester = $this->option('semester');
        $ta = $this->option('ta');

        $this->info("=== PRELOAD MONITORING RPS CACHE ===");
        $this->info("Prodi ID: {$prodiId}");
        $this->info("Semester: {$semester}");
        $this->info("Tahun Ajaran: {$ta}");
        $this->newLine();

        $apiService = new ExternalAPIService();
        $startTime = microtime(true);

        // 1. Cache tahun ajaran
        $this->info("1. Caching tahun ajaran...");
        Cache::forget('tahun_ajaran_list');
        $tahunAjaran = Cache::remember('tahun_ajaran_list', 600, function() use ($apiService) {
            return $apiService->getTahunAjaran();
        });
        $this->info("   ✓ Cached " . count($tahunAjaran) . " tahun ajaran");

        // 2. Cache mata kuliah
        $this->info("2. Caching mata kuliah...");
        $cacheKey = "matkul_{$prodiId}_{$semester}_{$ta}";
        Cache::forget($cacheKey);
        $matkulList = Cache::remember($cacheKey, 600, function() use ($apiService, $prodiId, $semester, $ta) {
            return $apiService->getMatkulByProdiSemTa($prodiId, $semester, $ta);
        });
        $this->info("   ✓ Cached " . count($matkulList) . " mata kuliah");

        // 3. Cache dosen
        $this->info("3. Caching dosen...");
        Cache::forget('dosen_filtered');
        $dosenList = Cache::remember('dosen_filtered', 600, function() use ($apiService) {
            return $apiService->getFilteredDosen();
        });
        $this->info("   ✓ Cached " . count($dosenList) . " dosen");

        // 4. Cache jadwal per dosen
        $this->info("4. Caching jadwal dosen...");
        $bar = $this->output->createProgressBar(count($dosenList));
        $bar->start();
        
        $cachedJadwal = 0;
        foreach ($dosenList as $dosen) {
            $pegawaiId = $dosen['pegawai_id'] ?? null;
            
            if ($pegawaiId) {
                $jadwalKey = "jadwal_{$pegawaiId}_{$semester}_{$ta}";
                Cache::forget($jadwalKey);
                
                try {
                    Cache::remember($jadwalKey, 600, function() use ($apiService, $pegawaiId, $semester, $ta) {
                        return $apiService->getJadwalByDosen($pegawaiId, $semester, $ta);
                    });
                    $cachedJadwal++;
                } catch (\Exception $e) {
                    // Skip if error
                }
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("   ✓ Cached jadwal untuk {$cachedJadwal} dosen");

        // 5. Cache monitoring per matkul
        $this->info("5. Caching monitoring materi...");
        $bar = $this->output->createProgressBar(count($matkulList));
        $bar->start();
        
        $cachedMonitoring = 0;
        foreach ($matkulList as $matkul) {
            $kuliahId = $matkul['kuliah_id'] ?? null;
            
            if ($kuliahId) {
                $monitoringKey = "monitoring_{$kuliahId}_{$semester}_{$ta}";
                Cache::forget($monitoringKey);
                
                try {
                    Cache::remember($monitoringKey, 600, function() use ($apiService, $kuliahId, $ta, $semester) {
                        return $apiService->getMonitoringMateri($kuliahId, $ta, $semester);
                    });
                    $cachedMonitoring++;
                } catch (\Exception $e) {
                    // Skip if error
                }
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("   ✓ Cached monitoring untuk {$cachedMonitoring} mata kuliah");

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $this->newLine();
        $this->info("=== PRELOAD SELESAI ===");
        $this->info("Total waktu: {$duration} detik");
        $this->info("Cache akan expired dalam 10 menit");
        $this->newLine();
        $this->comment("Sekarang halaman Monitoring RPS akan super cepat! ⚡");

        return 0;
    }
}
