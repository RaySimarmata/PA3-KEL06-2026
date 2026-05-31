<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UnifiedAIService;
use Illuminate\Support\Facades\Log;

class TestAIConnection extends Command
{
    protected $signature = 'test:ai-connection {--provider=groq}';
    protected $description = 'Test AI service connection and functionality';

    public function handle()
    {
        $provider = $this->option('provider');
        
        $this->info("Testing AI Connection...");
        $this->info("Provider: {$provider}");
        $this->newLine();

        try {
            // Test 1: Simple text generation
            $this->info("Test 1: Simple Text Generation");
            $aiService = app(UnifiedAIService::class);
            
            $startTime = microtime(true);
            $result = $aiService->generateText("Halo, ini adalah test koneksi. Balas dengan 'OK' jika kamu menerima pesan ini.", [
                'max_tokens' => 100,
                'temperature' => 0.7
            ]);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($result['success']) {
                $this->info("✓ Success!");
                $this->line("Response: " . substr($result['text'], 0, 200));
                $this->line("Provider: {$result['provider']}");
                $this->line("Model: {$result['model']}");
                $this->line("Duration: {$duration}ms");
                $this->newLine();
            } else {
                $this->error("✗ Failed!");
                $this->error("Error: " . ($result['error'] ?? 'Unknown error'));
                return 1;
            }

            // Test 2: Chat with conversation history
            $this->info("Test 2: Chat with Conversation History");
            $messages = [
                ['role' => 'system', 'content' => 'Kamu adalah AI assistant yang membantu testing.'],
                ['role' => 'user', 'content' => 'Halo, siapa namamu?'],
                ['role' => 'assistant', 'content' => 'Halo! Saya adalah AI assistant.'],
                ['role' => 'user', 'content' => 'Apa yang bisa kamu lakukan?']
            ];

            $startTime = microtime(true);
            $result = $aiService->generateChat($messages, [
                'max_tokens' => 200,
                'temperature' => 0.7
            ]);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($result['success']) {
                $this->info("✓ Success!");
                $this->line("Response: " . substr($result['text'], 0, 200));
                $this->line("Provider: {$result['provider']}");
                $this->line("Model: {$result['model']}");
                $this->line("Duration: {$duration}ms");
                $this->newLine();
            } else {
                $this->error("✗ Failed!");
                $this->error("Error: " . ($result['error'] ?? 'Unknown error'));
                return 1;
            }

            // Test 3: Indonesian language
            $this->info("Test 3: Indonesian Language Support");
            $startTime = microtime(true);
            $result = $aiService->generateText("Jelaskan dalam bahasa Indonesia apa itu Gugus Jaminan Mutu dalam 2 kalimat.", [
                'max_tokens' => 150,
                'temperature' => 0.7
            ]);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($result['success']) {
                $this->info("✓ Success!");
                $this->line("Response: " . $result['text']);
                $this->line("Duration: {$duration}ms");
                $this->newLine();
            } else {
                $this->error("✗ Failed!");
                $this->error("Error: " . ($result['error'] ?? 'Unknown error'));
                return 1;
            }

            // Summary
            $this->info("=== All Tests Passed! ===");
            $this->info("AI service is working correctly.");
            
            return 0;

        } catch (\Exception $e) {
            $this->error("✗ Test Failed!");
            $this->error("Exception: " . $e->getMessage());
            $this->newLine();
            $this->error("Stack trace:");
            $this->line($e->getTraceAsString());
            
            return 1;
        }
    }
}
