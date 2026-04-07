<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;

class ListMatakuliah extends Command
{
    protected $signature = 'list:matakuliah {--prodi=4} {--semester=1} {--ta=2020}';
    protected $description = 'List semua mata kuliah dari API';

    public function handle()
    {
        $prodiId = $this->option('prodi');
        $semester = $this->option('semester');
        $ta = $this->option('ta');

        $apiService = new ExternalAPIService();
        
        $this->info("Mengambil mata kuliah untuk:");
        $this->info("Prodi ID: {$prodiId}");
        $this->info("Semester: {$semester}");
        $this->info("Tahun Ajaran: {$ta}");
        $this->newLine();

        $matkulList = $apiService->getMatkulByProdiSemTa($prodiId, $semester, $ta);
        
        $this->info("Total mata kuliah: " . count($matkulList));
        $this->newLine();

        if (empty($matkulList)) {
            $this->warn("Tidak ada mata kuliah ditemukan!");
            return 0;
        }

        $this->table(
            ['No', 'Kode MK', 'Nama Mata Kuliah', 'Kuliah ID'],
            array_map(function($mk, $index) {
                return [
                    $index + 1,
                    $mk['kode_mk'] ?? '-',
                    $mk['nama_matkul'] ?? '-',
                    $mk['kuliah_id'] ?? '-'
                ];
            }, $matkulList, array_keys($matkulList))
        );

        return 0;
    }
}
