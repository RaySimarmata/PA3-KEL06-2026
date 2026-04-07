<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;

class TestMonitoringStatus extends Command
{
    protected $signature = 'test:monitoring-status {kuliah_id} {--ta=2020} {--semester=1}';
    protected $description = 'Test monitoring status dari API';

    public function handle()
    {
        $kuliahId = $this->argument('kuliah_id');
        $ta = $this->option('ta');
        $semester = $this->option('semester');

        $this->info("=== TEST MONITORING STATUS ===");
        $this->info("Kuliah ID: {$kuliahId}");
        $this->info("Tahun Ajaran: {$ta}");
        $this->info("Semester: {$semester}");
        $this->newLine();

        $apiService = new ExternalAPIService();
        
        try {
            $monitoring = $apiService->getMonitoringMateri($kuliahId, $ta, $semester);
            
            if ($monitoring) {
                $this->info("Response dari API:");
                $this->newLine();
                
                // Display as JSON
                $this->line(json_encode($monitoring, JSON_PRETTY_PRINT));
                
                $this->newLine();
                $this->info("Status RPS:");
                $statusRps = $monitoring['status_file_silabus'] ?? 'NOT FOUND';
                
                if ($statusRps === 'SUDAH UPLOAD') {
                    $this->info("  ✓ SUDAH UPLOAD");
                } elseif ($statusRps === 'BELUM UPLOAD') {
                    $this->warn("  ✗ BELUM UPLOAD");
                } else {
                    $this->error("  ? {$statusRps}");
                }
            } else {
                $this->error("Tidak ada data monitoring!");
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }

        return 0;
    }
}
