<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;

class TestMonitoringRPSNoCache extends Command
{
    protected $signature = 'test:monitoring-rps-nocache {semester=2} {ta=2020} {prodi_id=4}';
    protected $description = 'Test Monitoring RPS tanpa cache untuk debugging';

    public function handle()
    {
        $semester = $this->argument('semester');
        $ta = $this->argument('ta');
        $prodiId = $this->argument('prodi_id');
        
        $apiService = new ExternalAPIService();

        $this->info("=== TEST MONITORING RPS (NO CACHE) ===");
        $this->info("Prodi ID: {$prodiId}, TA: {$ta}, Semester: " . ($semester == 1 ? 'Ganjil' : 'Genap'));
        $this->newLine();

        // 1. Get Matakuliah (fresh from API)
        $this->info("1. Mengambil data matakuliah dari API...");
        $matkulData = $apiService->getMatkulByProdiSemTa($prodiId, $semester, $ta);
        
        if (empty($matkulData)) {
            $this->error("   ✗ Tidak ada data matakuliah!");
            $this->newLine();
            $this->comment("Kemungkinan penyebab:");
            $this->comment("1. API tidak mengembalikan data untuk semester ini");
            $this->comment("2. Token API expired");
            $this->comment("3. Parameter tidak valid");
            return;
        }
        
        $this->info("   ✓ Ditemukan " . count($matkulData) . " matakuliah");
        $this->newLine();

        // 2. Test monitoring untuk 10 matakuliah pertama
        $this->info("2. Testing monitoring materi (10 matakuliah pertama):");
        $this->newLine();
        
        $results = [];
        $sudahUpload = 0;
        $belumUpload = 0;
        $errors = 0;
        
        foreach (array_slice($matkulData, 0, 10) as $matkul) {
            $kodeMk = $matkul['kode_mk'] ?? 'N/A';
            $namaMk = $matkul['nama_matkul'] ?? 'N/A';
            $kuliahId = $matkul['kuliah_id'] ?? null;
            
            if (!$kuliahId) {
                $results[] = [
                    $kodeMk,
                    substr($namaMk, 0, 35),
                    'N/A',
                    'ERROR',
                    'No kuliah_id'
                ];
                $errors++;
                continue;
            }
            
            // Call API directly (no cache)
            try {
                $monitoring = $apiService->getMonitoringMateri($kuliahId, $ta, $semester);
                
                if ($monitoring) {
                    $statusRPS = $monitoring['status_file_silabus'] ?? 'BELUM UPLOAD';
                    $namaFile = $monitoring['nama_file_silabus'] ?? '-';
                    
                    $results[] = [
                        $kodeMk,
                        substr($namaMk, 0, 35),
                        $kuliahId,
                        $statusRPS,
                        substr($namaFile, 0, 25)
                    ];
                    
                    if ($statusRPS === 'SUDAH UPLOAD') {
                        $sudahUpload++;
                    } else {
                        $belumUpload++;
                    }
                } else {
                    $results[] = [
                        $kodeMk,
                        substr($namaMk, 0, 35),
                        $kuliahId,
                        'ERROR',
                        'No response from API'
                    ];
                    $errors++;
                }
            } catch (\Exception $e) {
                $results[] = [
                    $kodeMk,
                    substr($namaMk, 0, 35),
                    $kuliahId,
                    'ERROR',
                    substr($e->getMessage(), 0, 25)
                ];
                $errors++;
            }
        }
        
        // Display results
        $this->table(
            ['Kode MK', 'Nama Matakuliah', 'Kuliah ID', 'Status RPS', 'Nama File / Error'],
            $results
        );
        
        $this->newLine();
        
        // Summary
        $this->info("=== SUMMARY (10 matakuliah pertama) ===");
        $this->info("✓ SUDAH UPLOAD: {$sudahUpload}");
        $this->warn("✗ BELUM UPLOAD: {$belumUpload}");
        if ($errors > 0) {
            $this->error("⚠ ERROR: {$errors}");
        }
        
        $this->newLine();
        
        // Analysis
        if ($sudahUpload > 0) {
            $this->info("✓ API mengembalikan data SUDAH UPLOAD dengan benar!");
            $this->newLine();
            $this->line("Jika UI menampilkan semua ✗ (BELUM UPLOAD), masalahnya adalah:");
            $this->line("1. Cache lama masih digunakan");
            $this->line("2. Browser cache");
            $this->newLine();
            $this->info("SOLUSI:");
            $this->comment("1. Clear cache server:");
            $this->comment("   php artisan cache:clear-monitoring-rps {$semester} {$ta}");
            $this->comment("2. Atau klik 'Refresh Data' di UI");
            $this->comment("3. Hard refresh browser (Ctrl+Shift+R atau Cmd+Shift+R)");
        } else if ($belumUpload > 0 && $errors == 0) {
            $this->warn("⚠ Semua matakuliah menunjukkan BELUM UPLOAD");
            $this->line("Ini bisa normal jika memang belum ada yang upload RPS");
            $this->line("Atau periksa apakah parameter semester/ta sudah benar");
        } else if ($errors > 0) {
            $this->error("⚠ Ada error saat mengambil data dari API");
            $this->newLine();
            $this->comment("Periksa:");
            $this->comment("1. Token API: php artisan check:api-token");
            $this->comment("2. Koneksi API: php artisan test:api-connection");
            $this->comment("3. Log error: tail -f storage/logs/laravel.log");
        }
        
        $this->newLine();
        $this->info('Test selesai!');
    }
}
