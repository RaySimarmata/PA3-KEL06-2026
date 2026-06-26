<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;

class TestMonitoringPerkuliahan extends Command
{
    protected $signature = 'test:monitoring-perkuliahan {prodi_id=4} {semester=2} {ta=2020}';
    protected $description = 'Test API monitoring perkuliahan dengan weekly status';

    public function handle()
    {
        $prodiId = $this->argument('prodi_id');
        $semester = $this->argument('semester');
        $ta = $this->argument('ta');
        
        $apiService = new ExternalAPIService();

        $this->info("=== TEST MONITORING PERKULIAHAN ===");
        $this->info("Prodi ID: {$prodiId}, Semester: {$semester}, TA: {$ta}");
        $this->newLine();

        // 1. Get matakuliah
        $this->info("1. Mengambil data matakuliah...");
        $matkulData = $apiService->getMatkulByProdiSemTa($prodiId, $semester, $ta);
        
        if (empty($matkulData)) {
            $this->error("   ✗ Tidak ada data matakuliah!");
            return;
        }
        
        $this->info("   ✓ Ditemukan " . count($matkulData) . " matakuliah");
        $this->newLine();

        // 2. Test monitoring materi untuk sample matakuliah
        $this->info("2. Testing monitoring materi (5 pertama):");
        $this->newLine();
        
        $sampleMatkul = array_slice($matkulData, 0, 5);
        
        foreach ($sampleMatkul as $index => $matkul) {
            $kodeMk = $matkul['kode_mk'] ?? 'N/A';
            $namaMk = $matkul['nama_matkul'] ?? 'N/A';
            $kuliahId = $matkul['kuliah_id'] ?? null;
            
            $this->line("  [{$index}] {$kodeMk} - " . substr($namaMk, 0, 40));
            
            if (!$kuliahId) {
                $this->error("      ✗ No kuliah_id");
                continue;
            }
            
            try {
                $monitoring = $apiService->getMonitoringMateri($kuliahId, $ta, $semester);
                
                if ($monitoring && isset($monitoring['check_materi']['detail'])) {
                    $details = $monitoring['check_materi']['detail'];
                    $this->info("      ✓ Found " . count($details) . " sessions");
                    
                    // Process weekly status
                    $weeks = $this->processWeeklyStatus($monitoring);
                    
                    // Display weekly status
                    $statusStr = '';
                    foreach ($weeks as $status) {
                        if ($status == 1) {
                            $statusStr .= '✓ ';
                        } elseif ($status == 2) {
                            $statusStr .= '⚠ ';
                        } else {
                            $statusStr .= '✗ ';
                        }
                    }
                    
                    $this->line("      Weeks: {$statusStr}");
                    
                    // Count status
                    $ok = count(array_filter($weeks, fn($s) => $s == 1));
                    $warning = count(array_filter($weeks, fn($s) => $s == 2));
                    $error = count(array_filter($weeks, fn($s) => $s == 0));
                    
                    $this->line("      OK: {$ok}, Warning: {$warning}, Error: {$error}");
                } else {
                    $this->warn("      ⚠ No check_materi data");
                }
            } catch (\Exception $e) {
                $this->error("      ✗ Error: " . $e->getMessage());
            }
            
            $this->newLine();
        }
        
        $this->info('Test selesai!');
    }

    private function processWeeklyStatus($monitoring)
    {
        $weeks = array_fill(0, 16, 0);
        
        if (!$monitoring || !isset($monitoring['check_materi']['detail'])) {
            return $weeks;
        }
        
        $details = $monitoring['check_materi']['detail'];
        
        $weekData = [];
        foreach ($details as $detail) {
            $sesi = $detail['sesi'] ?? '';
            
            if (preg_match('/W(\d+)-S\d+/', $sesi, $matches)) {
                $weekNum = (int)$matches[1];
                
                if ($weekNum >= 1 && $weekNum <= 16) {
                    if (!isset($weekData[$weekNum])) {
                        $weekData[$weekNum] = [];
                    }
                    
                    $statusTeks = $detail['status_teks'] ?? 'KOSONG';
                    $statusFile = $detail['status_file'] ?? 'KOSONG';
                    
                    if ($statusTeks === 'OK' && strpos($statusFile, 'OK') !== false) {
                        $weekData[$weekNum][] = 1;
                    } elseif ($statusTeks === 'OK' || strpos($statusFile, 'OK') !== false) {
                        $weekData[$weekNum][] = 2;
                    } else {
                        $weekData[$weekNum][] = 0;
                    }
                }
            }
        }
        
        foreach ($weekData as $weekNum => $statuses) {
            if (!empty($statuses)) {
                if (in_array(1, $statuses)) {
                    $weeks[$weekNum - 1] = 1;
                } elseif (in_array(2, $statuses)) {
                    $weeks[$weekNum - 1] = 2;
                } else {
                    $weeks[$weekNum - 1] = 0;
                }
            }
        }
        
        return $weeks;
    }
}
