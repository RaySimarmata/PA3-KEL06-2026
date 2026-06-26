<?php

namespace App\Console\Commands;

use App\Services\ClaudeAIService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestAIConnection extends Command
{
    protected $signature = 'ai:test-connection {--provider=all}';
    protected $description = 'Test AI API connections and diagnose issues';

    public function handle()
    {
        $this->info('🔍 Testing AI API Connections...');
        $this->newLine();

        $provider = $this->option('provider');

        $results = [];

        // Test OpenAI
        if ($provider === 'all' || $provider === 'openai') {
            $results['OpenAI'] = $this->testOpenAI();
        }

        // Test OpenRouter
        if ($provider === 'all' || $provider === 'openrouter') {
            $results['OpenRouter'] = $this->testOpenRouter();
        }

        // Test Groq
        if ($provider === 'all' || $provider === 'groq') {
            $results['Groq'] = $this->testGroq();
        }

        // Test ClaudeAI Service
        if ($provider === 'all' || $provider === 'service') {
            $results['ClaudeAI Service'] = $this->testClaudeService();
        }

        // Summary
        $this->newLine();
        $this->info('📊 Summary:');
        $this->newLine();

        foreach ($results as $service => $result) {
            $status = $result['success'] ? '✅' : '❌';
            $this->line("{$status} {$service}: {$result['message']}");
            if (isset($result['time'])) {
                $this->line("   Response time: {$result['time']}ms");
            }
        }

        $this->newLine();

        // Recommendations
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        if ($successCount === 0) {
            $this->error('❌ All AI services failed!');
            $this->newLine();
            $this->warn('Recommendations:');
            $this->line('1. Check internet connection');
            $this->line('2. Check proxy/firewall settings');
            $this->line('3. Verify API keys in .env file');
            $this->line('4. Read TROUBLESHOOTING_AI_CONNECTION.md');
        } else {
            $this->info("✅ {$successCount} service(s) working!");
        }

        return 0;
    }

    private function testOpenAI(): array
    {
        $this->line('Testing OpenAI API...');

        $apiKey = env('OPENAI_API_KEY');
        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'No API key configured',
            ];
        }

        try {
            $start = microtime(true);

            $response = Http::withOptions([
                'verify' => false,
                'connect_timeout' => 10,
                'timeout' => 30,
            ])->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => 'Say "OK" if you can hear me.']
                ],
                'max_tokens' => 10,
            ]);

            $time = round((microtime(true) - $start) * 1000, 2);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'] ?? '';
                return [
                    'success' => true,
                    'message' => 'Connected successfully',
                    'time' => $time,
                    'response' => $content,
                ];
            }

            $status = $response->status();
            $body = $response->json();
            $error = $body['error']['message'] ?? $response->body();

            return [
                'success' => false,
                'message' => "HTTP {$status}: {$error}",
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function testOpenRouter(): array
    {
        $this->line('Testing OpenRouter API...');

        $apiKey = env('OPENROUTER_API_KEY', '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');

        try {
            $start = microtime(true);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => env('APP_URL', 'http://localhost'),
                'X-Title' => 'GKM GJM Test',
            ])->timeout(30)->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'meta-llama/llama-3.2-3b-instruct:free',
                'messages' => [
                    ['role' => 'user', 'content' => 'Say OK']
                ],
                'max_tokens' => 10,
            ]);

            $time = round((microtime(true) - $start) * 1000, 2);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'] ?? '';
                return [
                    'success' => true,
                    'message' => 'Connected successfully',
                    'time' => $time,
                    'response' => $content,
                ];
            }

            $status = $response->status();
            return [
                'success' => false,
                'message' => "HTTP {$status}",
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function testGroq(): array
    {
        $this->line('Testing Groq API...');

        $apiKey = env('LLM_API_KEY');
        if (empty($apiKey) || strpos($apiKey, 'your_') !== false) {
            return [
                'success' => false,
                'message' => 'No API key configured',
            ];
        }

        try {
            $start = microtime(true);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'llama-3.3-70b-versatile',
                'messages' => [
                    ['role' => 'user', 'content' => 'Say OK']
                ],
                'max_tokens' => 10,
            ]);

            $time = round((microtime(true) - $start) * 1000, 2);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'] ?? '';
                return [
                    'success' => true,
                    'message' => 'Connected successfully',
                    'time' => $time,
                    'response' => $content,
                ];
            }

            return [
                'success' => false,
                'message' => "HTTP {$response->status()}",
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function testClaudeService(): array
    {
        $this->line('Testing ClaudeAI Service...');

        try {
            $service = app(ClaudeAIService::class);

            $start = microtime(true);
            $response = $service->ask(
                'You are a helpful assistant',
                'Say OK if you can hear me',
                50
            );
            $time = round((microtime(true) - $start) * 1000, 2);

            if ($response) {
                return [
                    'success' => true,
                    'message' => 'Service working correctly',
                    'time' => $time,
                    'response' => substr($response, 0, 100),
                ];
            }

            return [
                'success' => false,
                'message' => 'Service returned null',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
