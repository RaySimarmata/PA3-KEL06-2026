<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Services\ExternalAPIService;

class ClearMonitoringRPSCache extends Command
{
    protected $signature = 'cache:clear-monitoring-rps {semester=1} {ta=2020} {--all}';
    protected $description = 'Clear cache untuk Monitoring RPS';

    public function handle()
    {
        $semester = $this->argument('semester');
        $ta = $this->argument('ta');
        $clearAll = $this->option('all');
        
        $this->info('Clearing Monitoring RPS Cache...');
        $this->newLine();
        
        if ($clearAll) {
            // Clear semua cache
            $this->info('Clearing ALL cache...');
            Cache::flush();
            $this->info('✓ All cache cleared!');
        } else {
            // Clear cache spesifik untuk monitoring RPS
            $prodiIds = [1, 3, 4]; // TI, NM, TRPL
            $apiService = new ExternalAPIService();
            
            $totalCleared = 0;
            
            foreach ($prodiIds as $prodiId) {
                $prodiName = ['1' => 'TI', '3' => 'NM', '4' => 'TRPL'][$prodiId];
                $this->line("Clearing cache for {$prodiName} (prodi_id={$prodiId})...");
                
                // Clear main cache
                $cacheKey = "monitoring_rps_{$prodiId}_{$semester}_{$ta}";
                if (Cache::has($cacheKey)) {
                    Cache::forget($cacheKey);
                    $this->info("  ✓ Cleared: {$cacheKey}");
                    $totalCleared++;
                }
                
                // Clear matkul cache
                $matkulCacheKey = "matkul_{$prodiId}_{$semester}_{$ta}";
                if (Cache::has($matkulCacheKey)) {
                    Cache::forget($matkulCacheKey);
                    $this->info("  ✓ Cleared: {$matkulCacheKey}");
                    $totalCleared++;
                }
                
                // Clear monitoring per kuliah_id
                $matkulData = $apiService->getMatkulByProdiSemTa($prodiId, $semester, $ta);
                if (!empty($matkulData)) {
                    foreach ($matkulData as $matkul) {
                        $kuliahId = $matkul['kuliah_id'] ?? null;
                        if ($kuliahId) {
                            $monitoringKey = "monitoring_{$kuliahId}_{$semester}_{$ta}";
                            if (Cache::has($monitoringKey)) {
                                Cache::forget($monitoringKey);
                                $totalCleared++;
                            }
                        }
                    }
                    $this->info("  ✓ Cleared monitoring cache for " . count($matkulData) . " matakuliah");
                }
            }
            
            // Clear dosen cache
            if (Cache::has('dosen_filtered')) {
                Cache::forget('dosen_filtered');
                $this->info("✓ Cleared: dosen_filtered");
                $totalCleared++;
            }
            
            // Clear tahun ajaran cache
            if (Cache::has('tahun_ajaran_list')) {
                Cache::forget('tahun_ajaran_list');
                $this->info("✓ Cleared: tahun_ajaran_list");
                $totalCleared++;
            }
            
            // Clear jadwal dosen cache
            $dosenList = $apiService->getFilteredDosen();
            $dosenCleared = 0;
            foreach ($dosenList as $dosen) {
                $pegawaiId = $dosen['pegawai_id'] ?? null;
                if ($pegawaiId) {
                    $jadwalKey = "jadwal_{$pegawaiId}_{$semester}_{$ta}";
                    if (Cache::has($jadwalKey)) {
                        Cache::forget($jadwalKey);
                        $dosenCleared++;
                    }
                }
            }
            if ($dosenCleared > 0) {
                $this->info("✓ Cleared jadwal cache for {$dosenCleared} dosen");
                $totalCleared += $dosenCleared;
            }
            
            $this->newLine();
            $this->info("Total cache cleared: {$totalCleared}");
        }
        
        $this->newLine();
        $this->info('Cache clearing completed!');
        $this->newLine();
        $this->comment('Sekarang data akan fresh dari API saat halaman dibuka.');
    }
}
