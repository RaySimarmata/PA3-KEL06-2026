<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestGroqConnection extends Command
{
    protected $signature = 'test:groq';
    protected $description = 'Test Groq API connection';

    public function handle()
    {
        $this->info('Testing Groq API connection...');
        
        $apiKey = env('LLM_API_KEY');
        $baseUrl = env('LLM_BASE_URL', 'https://api.groq.com/openai/v1');
        $model = env('LLM_MODEL', 'llama-3.3-70b-versatile');

        $this->info("API Key: " . (empty($apiKey) ? 'NOT SET' : substr($apiKey, 0, 10) . '...'));
        $this->info("Base URL: $baseUrl");
        $this->info("Model: $model");

        try {
            $this->info("\nSending test request...");
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($baseUrl . '/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant.'
                    ],
                    [
                        'role' => 'user',
                        'content' => 'Say "Hello, connection successful!" in one sentence.'
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 50,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $message = $data['choices'][0]['message']['content'] ?? null;

                if ($message) {
                    $this->info("\n✓ SUCCESS!");
                    $this->info("AI Response: " . $message);
                    $this->info("\nFull response data:");
                    $this->line(json_encode($data, JSON_PRETTY_PRINT));
                    return 0;
                } else {
                    $this->error("\n✗ FAILED: Response has no message content");
                    $this->line("Response data: " . json_encode($data, JSON_PRETTY_PRINT));
                    return 1;
                }
            } else {
                $this->error("\n✗ FAILED: HTTP " . $response->status());
                $this->error("Response body: " . $response->body());
                return 1;
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->error("\n✗ CONNECTION ERROR");
            $this->error("Message: " . $e->getMessage());
            $this->error("\nPossible causes:");
            $this->error("- No internet connection");
            $this->error("- Firewall blocking the request");
            $this->error("- Invalid base URL");
            return 1;
        } catch (\Exception $e) {
            $this->error("\n✗ EXCEPTION");
            $this->error("Type: " . get_class($e));
            $this->error("Message: " . $e->getMessage());
            $this->error("\nStack trace:");
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}
