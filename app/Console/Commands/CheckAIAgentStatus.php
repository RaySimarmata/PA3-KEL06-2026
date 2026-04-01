<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AIAgentService;
use Illuminate\Support\Facades\Http;

class CheckAIAgentStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:check-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check AI Agent status and configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("=== AI Agent Status Check ===\n");

        // Check configuration
        $this->checkConfiguration();

        // Check API connection
        $this->checkAPIConnection();

        // Test simple generation
        $this->testGeneration();

        return 0;
    }

    private function checkConfiguration()
    {
        $this->info("1. Configuration Check:");

        $enabled = config('services.llm.enabled', env('LLM_ENABLED', false));
        $provider = config('services.llm.provider', env('LLM_PROVIDER'));
        $apiKey = config('services.llm.api_key', env('LLM_API_KEY'));
        $baseUrl = config('services.llm.base_url', env('LLM_BASE_URL'));
        $model = config('services.llm.model', env('LLM_MODEL'));

        $this->line("   LLM_ENABLED: " . ($enabled ? '✓ true' : '✗ false'));
        $this->line("   LLM_PROVIDER: " . ($provider ?: '✗ not set'));
        $this->line("   LLM_API_KEY: " . ($apiKey ? '✓ set (' . substr($apiKey, 0, 10) . '...)' : '✗ not set'));
        $this->line("   LLM_BASE_URL: " . ($baseUrl ?: '✗ not set'));
        $this->line("   LLM_MODEL: " . ($model ?: '✗ not set'));

        if (!$enabled) {
            $this->error("\n   ⚠ AI Agent is DISABLED! Set LLM_ENABLED=true in .env");
        }

        if (!$apiKey) {
            $this->error("\n   ⚠ API Key is missing! Set LLM_API_KEY in .env");
        }

        $this->newLine();
    }

    private function checkAPIConnection()
    {
        $this->info("2. API Connection Check:");

        $apiKey = config('services.llm.api_key', env('LLM_API_KEY'));
        $baseUrl = config('services.llm.base_url', env('LLM_BASE_URL'));

        if (!$apiKey || !$baseUrl) {
            $this->error("   ✗ Cannot test connection - missing configuration");
            $this->newLine();
            return;
        }

        try {
            $this->line("   Testing connection to: {$baseUrl}");
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->get($baseUrl . '/models');

            if ($response->successful()) {
                $this->info("   ✓ API connection successful");
                
                $data = $response->json();
                if (isset($data['data']) && is_array($data['data'])) {
                    $this->line("   Available models: " . count($data['data']));
                }
            } else {
                $this->error("   ✗ API connection failed");
                $this->line("   Status: " . $response->status());
                $this->line("   Response: " . substr($response->body(), 0, 200));
            }

        } catch (\Exception $e) {
            $this->error("   ✗ Connection error: " . $e->getMessage());
        }

        $this->newLine();
    }

    private function testGeneration()
    {
        $this->info("3. Test AI Generation:");

        $enabled = config('services.llm.enabled', env('LLM_ENABLED', false));
        
        if (!$enabled) {
            $this->error("   ✗ Skipped - AI Agent is disabled");
            $this->newLine();
            return;
        }

        try {
            $this->line("   Generating test message...");
            
            $aiAgent = new AIAgentService();
            
            // Create mock dosen object
            $mockDosen = new \stdClass();
            $mockDosen->nama_lengkap = 'Dr. Test Dosen';
            $mockDosen->kontak_email = 'test@example.com';
            $mockDosen->kelas_wali = 'TRPL-3A';
            $mockDosen->matakuliah = collect([]);
            
            $dosenCollection = collect([$mockDosen]);
            
            // Create mock prodi
            $mockProdi = new \stdClass();
            $mockProdi->kode_prodi = 'TRPL';
            $mockProdi->nama_prodi = 'Teknologi Rekayasa Perangkat Lunak';
            
            $startTime = microtime(true);
            $message = $aiAgent->generateReminderMessage($dosenCollection, $mockProdi, 'rps');
            $endTime = microtime(true);
            
            $duration = round(($endTime - $startTime) * 1000, 2);
            
            $this->info("   ✓ Generation successful ({$duration}ms)");
            $this->line("   Message length: " . strlen($message) . " characters");
            $this->line("   Preview: " . substr($message, 0, 100) . "...");

        } catch (\Exception $e) {
            $this->error("   ✗ Generation failed: " . $e->getMessage());
        }

        $this->newLine();
    }
}
