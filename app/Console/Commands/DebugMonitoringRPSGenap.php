<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;
use Illuminate\Support\Facades\Cache;

class DebugMonitoringRPSGenap extends Command
{
    protected $signature = 'debug:monitoring-rps-genap {prodi_id=4} {ta=2020}';
    protected $description = 'Debug Monitoring RPS untuk Semester Genap';

    public function handle()
    {
        $prodiId = $this->argument('prodi_id');
        $ta = $this->argument('ta');
        $semTa = 2; // Genap
        
        $apiService = new ExternalAPIService();

        $this->info("=== DEBUG MONITORING RPS SEMESTER GENAP ===");
        $this->info("Prodi ID: {$prodiId}, TA: {$ta}, Semester: Genap (2)");
        $this->newLine();

        // 1. Get Matakuliah
        $this->info("1. Mengambil data matakuliah...");
        $matkulData = $apiService->getMatkulByProdiSemTa($prodiId, $semTa, $ta);
        
        if (empty($matkulData)) {
            $this->error("   ✗ Tidak ada data matakuliah!");
            return;
        }
        
        $this->info("   ✓ Ditemukan " . count($matkulData) . " matakuliah");
        $this->newLine();

        // 2. Test monitoring untuk semua matakuliah
        $this->info("2. Testing monitoring materi untuk setiap matakuliah:");
        $this->newLine();
        
        $sudahUpload = 0;
        $belumUpload = 0;
        $error = 0;
        
        $table = [];
        
        foreach ($matkulData as $index => $matkul) {
            $kodeMk = $matkul['kode_mk'] ?? 'N/A';
            $namaMk = $matkul['nama_matkul'] ?? 'N/A';
            $kuliahId = $matkul['kuliah_id'] ?? null;
            
            if (!$kuliahId) {
                $table[] = [
                    $kodeMk,
                    substr($namaMk, 0, 40),
                    'N/A',
                    'ERROR: No kuliah_id'
                ];
                $error++;
                continue;
            }
            
            // Test API monitoring
            try {
                $monitoring = $apiService->getMonitoringMateri($kuliahId, $ta, $semTa);
                
                if ($monitoring) {
                    $statusRPS = $monitoring['status_file_silabus'] ?? 'BELUM UPLOAD';
                    $namaFile = $monitoring['nama_file_silabus'] ?? '-';
                    
                    $table[] = [
                        $kodeMk,
                        substr($namaMk, 0, 40),
                        $kuliahId,
                        $statusRPS,
                        substr($namaFile, 0, 30)
                    ];
                    
                    if ($statusRPS === 'SUDAH UPLOAD') {
                        $sudahUpload++;
                    } else {
                        $belumUpload++;
                    }
                } else {
                    $table[] = [
                        $kodeMk,
                        substr($namaMk, 0, 40),
                        $kuliahId,
                        'ERROR: No response',
                        '-'
                    ];
                    $error++;
                }
            } catch (\Exception $e) {
                $table[] = [
                    $kodeMk,
                    substr($namaMk, 0, 40),
                    $kuliahId,
                    'ERROR: ' . substr($e->getMessage(), 0, 20),
                    '-'
                ];
                $error++;
            }
        }
        
        // Display table
        $this->table(
            ['Kode MK', 'Nama Matakuliah', 'Kuliah ID', 'Status RPS', 'Nama File'],
            $table
        );
        
        $this->newLine();
        
        // Summary
        $this->info("=== SUMMARY ===");
        $this->line("Total Matakuliah: " . count($matkulData));
        $this->info("✓ SUDAH UPLOAD: {$sudahUpload}");
        $this->warn("✗ BELUM UPLOAD: {$belumUpload}");
        if ($error > 0) {
            $this->error("⚠ ERROR: {$error}");
        }
        
        $this->newLine();
        
        // Check cache
        $this->info("3. Checking Cache:");
        $cacheKey = "monitoring_rps_{$prodiId}_{$semTa}_{$ta}";
        $this->line("   Cache Key: {$cacheKey}");
        
        if (Cache::has($cacheKey)) {
            $this->warn("   ⚠ Cache EXISTS - Data mungkin dari cache!");
            $cachedData = Cache::get($cacheKey);
            $this->line("   Cached items: " . count($cachedData));
            
            // Check status in cache
            $cachedSudah = 0;
            $cachedBelum = 0;
            foreach ($cachedData as $item) {
                if (($item['status_rps'] ?? '') === 'SUDAH UPLOAD') {
                    $cachedSudah++;
                } else {
                    $cachedBelum++;
                }
            }
            $this->line("   Cached SUDAH UPLOAD: {$cachedSudah}");
            $this->line("   Cached BELUM UPLOAD: {$cachedBelum}");
            
            $this->newLine();
            $this->comment("   Untuk clear cache:");
            $this->comment("   php artisan cache:clear-monitoring-rps 2 2020");
        } else {
            $this->info("   ✓ Cache TIDAK ADA - Data fresh dari API");
        }
        
        $this->newLine();
        
        // Recommendations
        if ($sudahUpload > 0 && Cache::has($cacheKey)) {
            $cachedData = Cache::get($cacheKey);
            $cachedSudah = 0;
            foreach ($cachedData as $item) {
                if (($item['status_rps'] ?? '') === 'SUDAH UPLOAD') {
                    $cachedSudah++;
                }
            }
            
            if ($cachedSudah < $sudahUpload) {
                $this->warn("⚠ MASALAH DITEMUKAN!");
                $this->warn("   API mengembalikan {$sudahUpload} SUDAH UPLOAD");
                $this->warn("   Tapi cache hanya {$cachedSudah} SUDAH UPLOAD");
                $this->newLine();
                $this->info("SOLUSI:");
                $this->line("1. Clear cache:");
                $this->comment("   php artisan cache:clear-monitoring-rps 2 2020");
                $this->line("2. Atau klik 'Refresh Data' di UI");
                $this->line("3. Hard refresh browser (Ctrl+Shift+R)");
            }
        }
        
        $this->newLine();
        $this->info('Debug selesai!');
    }
}
