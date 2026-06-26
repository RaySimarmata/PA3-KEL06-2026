<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;
use Illuminate\Support\Facades\Log;

class TestMonitoringMateriAPI extends Command
{
    protected $signature = 'test:monitoring-materi {kuliah_id=950} {ta=2020} {sem_ta=2}';
    protected $description = 'Test monitoring materi API endpoint';

    public function handle()
    {
        $kuliahId = $this->argument('kuliah_id');
        $ta = $this->argument('ta');
        $semTa = $this->argument('sem_ta');
        
        $this->info("Testing Monitoring Materi API");
        $this->info("Kuliah ID: {$kuliahId}");
        $this->info("Tahun Ajaran: {$ta}");
        $this->info("Semester: " . ($semTa == 1 ? 'Ganjil' : 'Genap'));
        $this->newLine();
        
        try {
            $apiService = new ExternalAPIService();
            
            $this->info("Fetching data from API...");
            $data = $apiService->getMonitoringMateri($kuliahId, $ta, $semTa);
            
            if (!$data) {
                $this->error("Failed to fetch data from API");
                return 1;
            }
            
            $this->info("✓ Data fetched successfully");
            $this->newLine();
            
            // Display check_materi data
            if (isset($data['check_materi'])) {
                $checkMateri = $data['check_materi'];
                
                $this->info("=== CHECK MATERI SUMMARY ===");
                if (isset($checkMateri['summary'])) {
                    $summary = $checkMateri['summary'];
                    $this->line("Total Sesi: " . ($summary['total_sesi'] ?? 'N/A'));
                    $this->line("Persentase Teks: " . ($summary['persentase_teks'] ?? 'N/A'));
                    $this->line("Persentase File: " . ($summary['persentase_file'] ?? 'N/A'));
                }
                $this->newLine();
                
                // Display detail per week
                if (isset($checkMateri['detail']) && is_array($checkMateri['detail'])) {
                    $this->info("=== DETAIL PER SESI ===");
                    
                    $weekData = [];
                    
                    foreach ($checkMateri['detail'] as $sesi) {
                        $sesiName = $sesi['sesi'] ?? 'Unknown';
                        $judul = $sesi['judul'] ?? '-';
                        $statusTeks = $sesi['status_teks'] ?? 'KOSONG';
                        $statusFile = $sesi['status_file'] ?? 'KOSONG';
                        
                        // Extract week number
                        if (preg_match('/W(\d+)-S\d+/', $sesiName, $matches)) {
                            $weekNum = (int)$matches[1];
                            
                            if (!isset($weekData[$weekNum])) {
                                $weekData[$weekNum] = [];
                            }
                            
                            $weekData[$weekNum][] = [
                                'sesi' => $sesiName,
                                'judul' => $judul,
                                'status_teks' => $statusTeks,
                                'status_file' => $statusFile
                            ];
                        }
                    }
                    
                    // Display grouped by week
                    ksort($weekData);
                    
                    foreach ($weekData as $weekNum => $sessions) {
                        $this->line("Week {$weekNum}:");
                        
                        foreach ($sessions as $session) {
                            $icon = $this->getStatusIcon($session['status_teks'], $session['status_file']);
                            $this->line("  {$icon} {$session['sesi']}: {$session['judul']}");
                            $this->line("     Teks: {$session['status_teks']} | File: {$session['status_file']}");
                        }
                        
                        $this->newLine();
                    }
                    
                    // Show week summary (W1-W16)
                    $this->info("=== WEEK SUMMARY (W1-W16) ===");
                    $weekSummary = $this->calculateWeekSummary($checkMateri['detail']);
                    
                    $line = "";
                    for ($i = 1; $i <= 16; $i++) {
                        $status = $weekSummary[$i - 1] ?? null;
                        $icon = $this->getWeekIcon($status);
                        $line .= "W{$i}:{$icon} ";
                        
                        if ($i % 8 == 0) {
                            $this->line($line);
                            $line = "";
                        }
                    }
                    if ($line) {
                        $this->line($line);
                    }
                    
                    $this->newLine();
                    $this->info("Legend:");
                    $this->line("  ✓ = Both teks and file OK");
                    $this->line("  ⚠ = One OK, one empty (partial)");
                    $this->line("  ✗ = Both empty");
                    $this->line("  - = No data");
                    
                } else {
                    $this->warn("No detail data found in check_materi");
                }
            } else {
                $this->warn("check_materi not found in response");
            }
            
            $this->newLine();
            $this->info("Test completed successfully!");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
    }
    
    private function getStatusIcon($statusTeks, $statusFile)
    {
        if ($statusTeks === 'OK' && strpos($statusFile, 'OK') !== false) {
            return '✓'; // Green check
        } elseif ($statusTeks === 'KOSONG' && $statusFile === 'KOSONG') {
            return '✗'; // Red X
        } else {
            return '⚠'; // Yellow hazard
        }
    }
    
    private function getWeekIcon($status)
    {
        if ($status === 1) {
            return '✓';
        } elseif ($status === 0) {
            return '✗';
        } elseif ($status === 2) {
            return '⚠';
        } else {
            return '-';
        }
    }
    
    private function calculateWeekSummary($detailMateri)
    {
        $weeks = array_fill(0, 16, null);
        
        // Group by week number
        $weekData = [];
        foreach ($detailMateri as $sesi) {
            // Extract week number from sesi (e.g., "W1-S1" -> 1, "W10-S1" -> 10)
            if (preg_match('/W(\d+)-S\d+/', $sesi['sesi'], $matches)) {
                $weekNum = (int)$matches[1];
                
                if ($weekNum >= 1 && $weekNum <= 16) {
                    // Initialize week if not exists
                    if (!isset($weekData[$weekNum])) {
                        $weekData[$weekNum] = [
                            'has_ok' => false,
                            'has_partial' => false,
                            'has_empty' => false
                        ];
                    }
                    
                    $statusTeks = $sesi['status_teks'] ?? 'KOSONG';
                    $statusFile = $sesi['status_file'] ?? 'KOSONG';
                    
                    // Determine status for this sesi
                    if ($statusTeks === 'OK' && strpos($statusFile, 'OK') !== false) {
                        // Both OK - green check
                        $weekData[$weekNum]['has_ok'] = true;
                    } elseif ($statusTeks === 'KOSONG' && $statusFile === 'KOSONG') {
                        // Both empty - red X
                        $weekData[$weekNum]['has_empty'] = true;
                    } else {
                        // One OK, one empty - yellow hazard
                        $weekData[$weekNum]['has_partial'] = true;
                    }
                }
            }
        }
        
        // Determine final status for each week (W1-W16)
        for ($i = 1; $i <= 16; $i++) {
            if (isset($weekData[$i])) {
                // Priority: if any sesi is empty -> red X
                // if any sesi is partial -> yellow hazard
                // if all sesi are OK -> green check
                if ($weekData[$i]['has_empty']) {
                    $weeks[$i - 1] = 0; // Red X
                } elseif ($weekData[$i]['has_partial']) {
                    $weeks[$i - 1] = 2; // Yellow hazard
                } elseif ($weekData[$i]['has_ok']) {
                    $weeks[$i - 1] = 1; // Green check
                }
            }
            // else remains null (no data)
        }
        
        return $weeks;
    }
}
