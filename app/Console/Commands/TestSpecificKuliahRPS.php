<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;
use Illuminate\Support\Facades\Cache;

class TestSpecificKuliahRPS extends Command
{
    protected $signature = 'test:specific-kuliah-rps {kuliah_id} {ta=2020} {sem_ta=2}';
    protected $description = 'Test RPS status untuk kuliah_id tertentu';

    public function handle()
    {
        $kuliahId = $this->argument('kuliah_id');
        $ta = $this->argument('ta');
        $semTa = $this->argument('sem_ta');
        
        $apiService = new ExternalAPIService();

        $this->info("Testing RPS Status untuk kuliah_id: {$kuliahId}");
        $this->info("Parameters: ta={$ta}, sem_ta={$semTa}");
        $this->newLine();

        // 1. Test API langsung (tanpa cache)
        $this->info("1. Testing API get-monitoring-materi (tanpa cache)");
        $monitoring = $apiService->getMonitoringMateri($kuliahId, $ta, $semTa);
        
        if ($monitoring) {
            $this->info("   ✓ API Response berhasil");
            $this->line("   Raw Response:");
            $this->line("   " . json_encode($monitoring, JSON_PRETTY_PRINT));
            $this->newLine();
            
            $statusRPS = $monitoring['status_file_silabus'] ?? 'N/A';
            $namaFile = $monitoring['nama_file_silabus'] ?? 'N/A';
            
            $this->line("   Status RPS: " . $statusRPS);
            $this->line("   Nama File: " . $namaFile);
            
            if ($statusRPS === 'SUDAH UPLOAD') {
                $this->info("   ✓ Status: SUDAH UPLOAD (seharusnya tampil centang hijau di UI)");
            } else {
                $this->warn("   ✗ Status: BELUM UPLOAD (seharusnya tampil silang merah di UI)");
            }
        } else {
            $this->error("   ✗ API tidak mengembalikan data");
        }
        
        $this->newLine();
        
        // 2. Check cache
        $cacheKey = "monitoring_{$kuliahId}_{$semTa}_{$ta}";
        $this->info("2. Checking Cache");
        $this->line("   Cache Key: {$cacheKey}");
        
        if (Cache::has($cacheKey)) {
            $this->warn("   ⚠ Cache EXISTS - Data mungkin dari cache lama!");
            $cachedData = Cache::get($cacheKey);
            $this->line("   Cached Status: " . ($cachedData['status_file_silabus'] ?? 'N/A'));
            $this->newLine();
            
            $this->comment("   Untuk clear cache, jalankan:");
            $this->comment("   php artisan cache:forget {$cacheKey}");
            $this->comment("   Atau klik tombol 'Refresh Data' di UI");
        } else {
            $this->info("   ✓ Cache TIDAK ADA - Data akan fresh dari API");
        }
        
        $this->newLine();
        
        // 3. Rekomendasi
        $this->info("3. Troubleshooting");
        if ($monitoring && $monitoring['status_file_silabus'] === 'SUDAH UPLOAD') {
            $this->line("   API mengembalikan 'SUDAH UPLOAD' tapi UI menampilkan ✗?");
            $this->line("   Kemungkinan penyebab:");
            $this->line("   1. Data masih di-cache (cache lama)");
            $this->line("   2. Filter semester/tahun ajaran tidak sesuai");
            $this->line("   3. Browser cache");
            $this->newLine();
            $this->line("   Solusi:");
            $this->line("   1. Clear cache aplikasi:");
            $this->comment("      php artisan cache:clear");
            $this->line("   2. Atau clear cache spesifik:");
            $this->comment("      php artisan cache:forget {$cacheKey}");
            $this->line("   3. Klik 'Refresh Data' di UI");
            $this->line("   4. Hard refresh browser (Ctrl+Shift+R atau Cmd+Shift+R)");
        }
        
        $this->newLine();
        $this->info('Test selesai!');
    }
}
