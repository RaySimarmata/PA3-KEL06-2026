<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestOllamaConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ollama:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test connection to Ollama server and models';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Ollama Connection Test ===');
        $this->newLine();

        $baseUrl = env('OLLAMA_BASE_URL', 'http://localhost:11434');
        $llmModel = env('OLLAMA_MODEL', 'llama3:8b-instruct');
        $embeddingModel = env('EMBEDDING_MODEL', 'nomic-embed-text');

        // Test 1: Check if Ollama is running
        $this->info('1. Testing Ollama server connection...');
        try {
            $response = Http::timeout(5)->get($baseUrl . '/api/tags');
            
            if ($response->successful()) {
                $this->info('   ✅ Ollama server is running');
                
                $data = $response->json();
                if (isset($data['models']) && count($data['models']) > 0) {
                    $this->info('   Available models:');
                    foreach ($data['models'] as $model) {
                        $name = $model['name'] ?? 'unknown';
                        $size = isset($model['size']) ? $this->formatBytes($model['size']) : 'unknown';
                        $this->line("   - {$name} ({$size})");
                    }
                } else {
                    $this->warn('   No models installed');
                }
            } else {
                $this->error('   ❌ Ollama server returned error: ' . $response->status());
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Cannot connect to Ollama server');
            $this->error('   Error: ' . $e->getMessage());
            $this->newLine();
            $this->comment('   Make sure Ollama is installed and running:');
            $this->comment('   - Download from: https://ollama.ai/download');
            $this->comment('   - Or run: ollama serve');
            return 1;
        }
        $this->newLine();

        // Test 2: Test LLM model
        $this->info('2. Testing LLM model...');
        $this->info("   Model: {$llmModel}");
        
        try {
            $response = Http::timeout(60)->post($baseUrl . '/v1/chat/completions', [
                'model' => $llmModel,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant.'
                    ],
                    [
                        'role' => 'user',
                        'content' => 'Say "Hello, I am working!" in one sentence.'
                    ]
                ],
                'max_tokens' => 50,
                'stream' => false,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? null;
                
                if ($content) {
                    $this->info('   ✅ LLM model is working');
                    $this->line('   Response: ' . $content);
                } else {
                    $this->error('   ❌ LLM returned empty response');
                }
            } else {
                $this->error('   ❌ LLM request failed: ' . $response->status());
                $this->error('   ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->error('   ❌ LLM test failed');
            $this->error('   Error: ' . $e->getMessage());
            $this->newLine();
            $this->comment('   Make sure the model is installed:');
            $this->comment("   Run: ollama pull {$llmModel}");
        }
        $this->newLine();

        // Test 3: Test Embedding model
        $this->info('3. Testing Embedding model...');
        $this->info("   Model: {$embeddingModel}");
        
        try {
            $response = Http::timeout(30)->post($baseUrl . '/api/embeddings', [
                'model' => $embeddingModel,
                'prompt' => 'Test embedding generation',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $embedding = $data['embedding'] ?? null;
                
                if ($embedding && is_array($embedding)) {
                    $dimensions = count($embedding);
                    $this->info('   ✅ Embedding model is working');
                    $this->line("   Dimensions: {$dimensions}");
                    $this->line('   Sample values: [' . implode(', ', array_slice($embedding, 0, 5)) . '...]');
                } else {
                    $this->error('   ❌ Embedding returned invalid response');
                }
            } else {
                $this->error('   ❌ Embedding request failed: ' . $response->status());
                $this->error('   ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Embedding test failed');
            $this->error('   Error: ' . $e->getMessage());
            $this->newLine();
            $this->comment('   Make sure the model is installed:');
            $this->comment("   Run: ollama pull {$embeddingModel}");
        }
        $this->newLine();

        // Summary
        $this->info('=== Configuration Summary ===');
        $this->table(
            ['Setting', 'Value'],
            [
                ['LLM Provider', env('LLM_PROVIDER', 'not set')],
                ['Ollama Base URL', $baseUrl],
                ['LLM Model', $llmModel],
                ['Embedding Provider', env('EMBEDDING_PROVIDER', 'not set')],
                ['Embedding Model', $embeddingModel],
                ['Vector DB Enabled', env('VECTOR_DB_ENABLED', false) ? 'Yes' : 'No'],
            ]
        );

        $this->newLine();
        $this->info('For more information, see: docs/RAG_SETUP.md');

        return 0;
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
