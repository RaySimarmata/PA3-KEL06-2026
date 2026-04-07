<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;

class TestMonitoringRPSSemester extends Command
{
    protected $signature = 'test:monitoring-rps-semester {semester=1} {ta=2020}';
    protected $description = 'Test Monitoring RPS untuk semester tertentu (1=Ganjil, 2=Genap)';

    public function handle()
    {
        $semester = $this->argument('semester');
        $ta = $this->argument('ta');
        
        $apiService = new ExternalAPIService();

        $this->info("Testing Monitoring RPS untuk Semester " . ($semester == 1 ? 'Ganjil' : 'Genap') . " TA {$ta}");
        $this->newLine();

        // Test untuk TRPL (prodi_id = 4)
        $prodiId = 4;
        $this->info("1. Mengambil data matakuliah untuk TRPL (prodi_id={$prodiId})");
        $this->info("   Parameters: prodi_id={$prodiId}, sem_ta={$semester}, ta={$ta}");
        
        $matkulData = $apiService->getMatkulByProdiSemTa($prodiId, $semester, $ta);
        
        if (!empty($matkulData)) {
            $this->info("   ✓ Ditemukan " . count($matkulData) . " matakuliah");
            $this->newLine();
            
            // Test monitoring materi untuk 3 matakuliah pertama
            $this->info("2. Testing get-monitoring-materi untuk 3 matakuliah pertama:");
            $this->newLine();
            
            $counter = 1;
            foreach (array_slice($matkulData, 0, 3) as $matkul) {
                $kodeMk = $matkul['kode_mk'] ?? 'N/A';
                $namaMk = $matkul['nama_matkul'] ?? 'N/A';
                $kuliahId = $matkul['kuliah_id'] ?? null;
                
                $this->line("   {$counter}. {$kodeMk} - {$namaMk}");
                $this->line("      Kuliah ID: {$kuliahId}");
                
                if ($kuliahId) {
                    $monitoring = $apiService->getMonitoringMateri($kuliahId, $ta, $semester);
                    
                    if ($monitoring) {
                        $statusRPS = $monitoring['status_file_silabus'] ?? 'N/A';
                        $namaFile = $monitoring['nama_file_silabus'] ?? 'N/A';
                        
                        $this->line("      Status RPS: " . $statusRPS);
                        $this->line("      Nama File: " . $namaFile);
                        
                        // Show icon based on status
                        if ($statusRPS === 'SUDAH UPLOAD') {
                            $this->info("      ✓ SUDAH UPLOAD");
                        } else {
                            $this->warn("      ✗ BELUM UPLOAD");
                        }
                    } else {
                        $this->error("      ✗ Gagal mengambil data monitoring");
                    }
                } else {
                    $this->error("      ✗ Kuliah ID tidak tersedia");
                }
                
                $this->newLine();
                $counter++;
            }
        } else {
            $this->error("   ✗ Tidak ada data matakuliah");
            $this->newLine();
        }

        $this->newLine();
        $this->info('Test selesai!');
        $this->newLine();
        $this->comment('Untuk test semester genap, jalankan:');
        $this->comment('php artisan test:monitoring-rps-semester 2 2020');
    }
}
