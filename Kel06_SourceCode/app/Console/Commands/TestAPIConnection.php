<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestAPIConnection extends Command
{
    protected $signature = 'api:test-dosen';
    protected $description = 'Test connection to external Dosen API';

    public function handle()
    {
        $this->info('Testing API Connection...');
        $this->newLine();

        $apiUrl = env('API_BASE_URL') . '/library-api/dosen';
        $apiToken = env('API_TOKEN');

        if (!$apiToken || $apiToken === 'your_api_token_here') {
            $this->error('API_TOKEN not configured in .env file!');
            $this->info('Please set API_TOKEN in your .env file');
            return 1;
        }

        $this->info("API URL: {$apiUrl}");
        $this->info("Token: " . substr($apiToken, 0, 10) . "...");
        $this->newLine();

        try {
            $this->info('Sending request...');
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiToken,
                'Accept' => 'application/json',
            ])->timeout(10)->get($apiUrl);

            if ($response->successful()) {
                $data = $response->json();
                $count = is_array($data) ? count($data) : 0;
                
                $this->info("✓ Connection successful!");
                $this->info("✓ Status: {$response->status()}");
                $this->info("✓ Total dosen: {$count}");
                $this->newLine();

                if ($count > 0 && isset($data[0])) {
                    $this->info('Sample data (first record):');
                    $this->line(json_encode($data[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                } elseif ($count > 0) {
                    $this->info('Data structure:');
                    $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }

                return 0;
            } else {
                $this->error("✗ API returned error status: {$response->status()}");
                $this->error("Response: " . $response->body());
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("✗ Connection failed!");
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}
