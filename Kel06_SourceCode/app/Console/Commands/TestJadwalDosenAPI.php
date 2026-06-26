<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;

class TestJadwalDosenAPI extends Command
{
    protected $signature = 'test:jadwal-dosen {pegawai_id?} {--sem_ta=1} {--ta=2020}';
    protected $description = 'Test API connection for jadwal dosen endpoint';

    public function handle()
    {
        $this->info('Testing Jadwal Dosen API Connection...');
        $this->info('=====================================');
        
        $apiService = app(ExternalAPIService::class);
        
        // Get parameters
        $pegawaiId = $this->argument('pegawai_id') ?? 262;
        $semTa = $this->option('sem_ta');
        $ta = $this->option('ta');
        
        $this->info("Pegawai ID: {$pegawaiId}");
        $this->info("Semester: " . ($semTa == 1 ? 'Ganjil' : 'Genap') . " ({$semTa})");
        $this->info("Tahun Ajaran: {$ta}");
        $this->newLine();
        
        // Test raw API call first
        $this->info('Testing raw API call...');
        $apiUrl = env('API_BASE_URL') . '/library-api/get-jadwal-by-dosen';
        $params = [
            'pegawai_id' => $pegawaiId,
            'sem_ta' => $semTa,
            'ta' => $ta
        ];
        
        $this->info("URL: {$apiUrl}");
        $this->info("Params: " . json_encode($params));
        $this->newLine();
        
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . env('API_TOKEN'),
            'Accept' => 'application/json',
        ])->get($apiUrl, $params);
        
        $this->info("Status: " . $response->status());
        $this->info("Response Body:");
        $this->line(json_encode($response->json(), JSON_PRETTY_PRINT));
        $this->newLine();
        
        // Test jadwal endpoint
        $this->info('Fetching jadwal data via service...');
        $jadwal = $apiService->getJadwalByDosen($pegawaiId, $semTa, $ta);
        
        if ($jadwal === null) {
            $this->error('Failed to fetch jadwal data from API');
            return 1;
        }
        
        if (empty($jadwal)) {
            $this->warn('No jadwal data found for this dosen');
            $this->info('Check the logs for more details: storage/logs/laravel.log');
            return 0;
        }
        
        $this->info('Jadwal data retrieved successfully!');
        $this->info('Total mata kuliah: ' . count($jadwal));
        $this->newLine();
        
        // Display jadwal in table
        $headers = ['Kode MK', 'Nama MK', 'SKS', 'Semester', 'Tahun Ajaran'];
        $rows = [];
        
        foreach ($jadwal as $mk) {
            $rows[] = [
                $mk['kode_mk'],
                $mk['nama_mk'],
                $mk['sks'],
                $mk['semester'],
                $mk['tahun_ajaran']
            ];
        }
        
        $this->table($headers, $rows);
        
        $this->newLine();
        $this->info('✓ API connection test completed successfully!');
        
        return 0;
    }
}
