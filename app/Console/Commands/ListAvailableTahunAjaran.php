<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;

class ListAvailableTahunAjaran extends Command
{
    protected $signature = 'list:tahun-ajaran {prodi_id?}';
    protected $description = 'List tahun ajaran yang tersedia dengan data matakuliah';

    public function handle()
    {
        $prodiId = $this->argument('prodi_id') ?? 4; // Default TRPL
        
        $prodiNames = [
            1 => 'TI (Teknologi Informasi)',
            3 => 'NM (Teknologi Komputer)',
            4 => 'TRPL (Teknologi Rekayasa Perangkat Lunak)'
        ];
        
        $this->info("=== TAHUN AJARAN TERSEDIA ===");
        $this->info("Prodi: " . ($prodiNames[$prodiId] ?? "ID {$prodiId}"));
        $this->newLine();

        $apiService = new ExternalAPIService();
        
        // Test tahun ajaran dari 2020-2024
        $tahunList = ['2020', '2021', '2022', '2023', '2024'];
        $semesterList = [1 => 'Ganjil', 2 => 'Genap'];
        
        $results = [];
        
        $this->info("Checking availability...");
        $progressBar = $this->output->createProgressBar(count($tahunList) * 2);
        $progressBar->start();
        
        foreach ($tahunList as $tahun) {
            foreach ($semesterList as $semId => $semName) {
                $matkulData = $apiService->getMatkulByProdiSemTa($prodiId, $semId, $tahun);
                $count = count($matkulData);
                
                if ($count > 0) {
                    $results[] = [
                        'tahun' => $tahun,
                        'semester' => $semName,
                        'count' => $count,
                        'status' => '✓'
                    ];
                }
                
                $progressBar->advance();
            }
        }
        
        $progressBar->finish();
        $this->newLine();
        $this->newLine();
        
        if (empty($results)) {
            $this->error("Tidak ada data matakuliah ditemukan untuk prodi ini.");
            return 1;
        }
        
        $this->info("Data tersedia untuk:");
        $this->table(
            ['Status', 'Tahun Ajaran', 'Semester', 'Jumlah Matakuliah'],
            array_map(function($r) {
                return [$r['status'], $r['tahun'], $r['semester'], $r['count']];
            }, $results)
        );
        
        $this->newLine();
        $this->comment("Gunakan kombinasi tahun dan semester di atas untuk filter Monitoring RPS.");
        
        return 0;
    }
}
