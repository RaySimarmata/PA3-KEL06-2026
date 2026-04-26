<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Unified AI Service - Support multiple AI providers
 * Priority: Groq (free) → OpenRouter (cheap) → Claude (premium)
 */
class UnifiedAIService
{
    private $provider;
    private $apiKey;
    private $baseUrl;
    private $model;
    
    // Cache settings
    private $cacheEnabled = true;
    private $cacheTTL = 86400; // 24 hours

    public function __construct()
    {
        $this->provider = env('LLM_PROVIDER', 'groq');
        $this->loadProviderConfig();
    }

    /**
     * Load configuration based on provider
     */
    private function loadProviderConfig(): void
    {
        switch ($this->provider) {
            case 'groq':
                $this->apiKey = env('LLM_API_KEY');
                $this->baseUrl = env('LLM_BASE_URL', 'https://api.groq.com/openai/v1');
                $this->model = env('LLM_MODEL', 'llama-3.3-70b-versatile');
                break;
                
            case 'openrouter':
                $this->apiKey = env('OPENROUTER_API_KEY');
                $this->baseUrl = 'https://openrouter.ai/api/v1';
                $this->model = env('OPENROUTER_MODEL', 'meta-llama/llama-3.1-70b-instruct');
                break;
                
            case 'together':
                $this->apiKey = env('TOGETHER_API_KEY');
                $this->baseUrl = 'https://api.together.xyz/v1';
                $this->model = env('TOGETHER_MODEL', 'meta-llama/Meta-Llama-3.1-70B-Instruct-Turbo');
                break;
                
            case 'claude':
                $this->apiKey = env('ANTHROPIC_API_KEY');
                $this->baseUrl = 'https://api.anthropic.com/v1';
                $this->model = env('ANTHROPIC_MODEL', 'claude-3-5-haiku-20241022');
                break;
                
            default:
                throw new \Exception("Unsupported AI provider: {$this->provider}");
        }

        if (empty($this->apiKey)) {
            Log::warning("API key not configured for provider: {$this->provider}");
        }
    }

    /**
     * Generate text from prompt with automatic fallback
     */
    public function generateText(string $prompt, array $options = []): array
    {
        $startTime = microtime(true);

        try {
            Log::info("=== AI Generation Started ===", [
                'provider' => $this->provider,
                'model' => $this->model,
                'prompt_length' => safe_strlen($prompt)
            ]);

            // Check cache
            $cacheKey = $this->getCacheKey($prompt, $options);
            if ($this->cacheEnabled && Cache::has($cacheKey)) {
                $cached = Cache::get($cacheKey);
                Log::info("AI cache hit", ['provider' => $this->provider]);
                return $cached;
            }

            // Try primary provider first
            try {
                $response = match($this->provider) {
                    'claude' => $this->callClaudeAPI($prompt, $options),
                    default => $this->callOpenAICompatibleAPI($prompt, $options)
                };

                $processingTime = round((microtime(true) - $startTime) * 1000, 2);

                $result = [
                    'success' => true,
                    'text' => $response['text'],
                    'provider' => $this->provider,
                    'model' => $this->model,
                    'token_count' => $response['token_count'] ?? null,
                    'processing_time_ms' => $processingTime,
                    'cached' => false,
                ];

                // Cache result
                if ($this->cacheEnabled) {
                    Cache::put($cacheKey, $result, $this->cacheTTL);
                }

                Log::info("AI generation completed", [
                    'provider' => $this->provider,
                    'text_length' => strlen(is_string($response['text']) ? $response['text'] : json_encode($response['text'])),
                    'processing_time_ms' => $processingTime
                ]);

                return $result;

            } catch (\Exception $primaryError) {
                // Check if it's a rate limit or quota error
                $errorMsg = $primaryError->getMessage();
                $isRateLimit = str_contains($errorMsg, 'Rate limit') || 
                               str_contains($errorMsg, 'rate_limit') ||
                               str_contains($errorMsg, '429') ||
                               str_contains($errorMsg, 'quota');

                Log::warning("Primary provider failed", [
                    'provider' => $this->provider,
                    'error' => $errorMsg,
                    'is_rate_limit' => $isRateLimit
                ]);

                if ($isRateLimit) {
                    // Try Gemini as fallback only if rate limited
                    return $this->fallbackToGemini($prompt, $options, $startTime);
                }

                // For other errors, return immediately without fallback
                throw $primaryError;
            }

        } catch (\Exception $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error("AI generation failed", [
                'provider' => $this->provider,
                'error' => $e->getMessage(),
                'processing_time_ms' => $processingTime
            ]);

            return [
                'success' => false,
                'text' => '',
                'error' => $e->getMessage(),
                'provider' => $this->provider,
                'processing_time_ms' => $processingTime,
            ];
        }
    }

    /**
     * Fallback to Gemini when primary provider fails
     */
    private function fallbackToGemini(string $prompt, array $options, float $startTime): array
    {
        try {
            $geminiService = app(\App\Services\GeminiAIService::class);
            
            Log::info("Attempting Gemini fallback");
            
            $geminiResult = $geminiService->generateText($prompt, $options);
            
            if ($geminiResult['success']) {
                $processingTime = round((microtime(true) - $startTime) * 1000, 2);
                
                $result = [
                    'success' => true,
                    'text' => $geminiResult['text'],
                    'provider' => 'gemini (fallback)',
                    'model' => $geminiResult['model'] ?? 'gemini-1.5-flash',
                    'token_count' => $geminiResult['token_count'] ?? null,
                    'processing_time_ms' => $processingTime,
                    'cached' => false,
                    'fallback_used' => true,
                ];

                // Cache the fallback result
                if ($this->cacheEnabled) {
                    $cacheKey = $this->getCacheKey($prompt, $options);
                    Cache::put($cacheKey, $result, $this->cacheTTL);
                }

                Log::info("Gemini fallback successful", [
                    'text_length' => strlen($geminiResult['text']),
                    'processing_time_ms' => $processingTime
                ]);

                return $result;
            } else {
                throw new \Exception("Gemini fallback failed: " . ($geminiResult['error'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            Log::error("Gemini fallback failed", [
                'error' => $e->getMessage()
            ]);

            // Return error from primary provider
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            return [
                'success' => false,
                'text' => '',
                'error' => 'Semua layanan AI sedang tidak tersedia. Primary provider rate limited dan Gemini fallback gagal.',
                'provider' => $this->provider . ' + gemini (both failed)',
                'processing_time_ms' => $processingTime,
            ];
        }
    }

    /**
     * Call OpenAI-compatible API (Groq, OpenRouter, Together)
     */
    private function callOpenAICompatibleAPI(string $prompt, array $options): array
    {
        $maxRetries = 3;
        $retryDelay = 1000; // milliseconds

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $headers = [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ];

                // OpenRouter specific headers
                if ($this->provider === 'openrouter') {
                    $headers['HTTP-Referer'] = env('APP_URL', 'http://localhost');
                    $headers['X-Title'] = 'GKM GJM System';
                }

                $payload = [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Anda adalah AI assistant yang membantu membuat laporan akademik dalam bahasa Indonesia. Berikan jawaban yang terstruktur, profesional, dan relevan.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => $options['temperature'] ?? 0.7,
                    'max_tokens' => $options['max_tokens'] ?? 2048,
                ];

                Log::info("Calling {$this->provider} API", [
                    'attempt' => $attempt,
                    'model' => $this->model
                ]);

                $response = Http::withHeaders($headers)
                    ->timeout(60)
                    ->post($this->baseUrl . '/chat/completions', $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (!isset($data['choices'][0]['message']['content'])) {
                        throw new \Exception("Invalid API response structure");
                    }

                    $text = $data['choices'][0]['message']['content'];
                    $tokenCount = $data['usage']['total_tokens'] ?? null;

                    return [
                        'text' => $text,
                        'token_count' => $tokenCount,
                    ];
                }

                // Handle errors
                $statusCode = $response->status();
                $errorBody = $response->body();

                if ($statusCode === 429 && $attempt < $maxRetries) {
                    Log::warning("Rate limit exceeded, retrying...", [
                        'attempt' => $attempt,
                        'retry_delay_ms' => $retryDelay
                    ]);
                    usleep($retryDelay * 1000);
                    $retryDelay *= 2;
                    continue;
                }

                throw new \Exception("API request failed: HTTP {$statusCode} - {$errorBody}");

            } catch (\Exception $e) {
                if ($attempt === $maxRetries) {
                    throw $e;
                }
                
                Log::warning("API call failed, retrying...", [
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);
                
                usleep($retryDelay * 1000);
                $retryDelay *= 2;
            }
        }

        throw new \Exception("Max retries exceeded");
    }

    /**
     * Call Claude API (different format)
     */
    private function callClaudeAPI(string $prompt, array $options): array
    {
        $maxRetries = 3;
        $retryDelay = 1000;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $payload = [
                    'model' => $this->model,
                    'max_tokens' => $options['max_tokens'] ?? 2048,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'system' => 'Anda adalah AI assistant yang membantu membuat laporan akademik dalam bahasa Indonesia. Berikan jawaban yang terstruktur, profesional, dan relevan.',
                ];

                $response = Http::withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ])->timeout(60)->post($this->baseUrl . '/messages', $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (!isset($data['content'][0]['text'])) {
                        throw new \Exception("Invalid Claude API response");
                    }

                    $text = $data['content'][0]['text'];
                    $tokenCount = $data['usage']['input_tokens'] + $data['usage']['output_tokens'];

                    return [
                        'text' => $text,
                        'token_count' => $tokenCount,
                    ];
                }

                $statusCode = $response->status();
                $errorBody = $response->body();

                if ($statusCode === 429 && $attempt < $maxRetries) {
                    usleep($retryDelay * 1000);
                    $retryDelay *= 2;
                    continue;
                }

                throw new \Exception("Claude API failed: HTTP {$statusCode} - {$errorBody}");

            } catch (\Exception $e) {
                if ($attempt === $maxRetries) {
                    throw $e;
                }
                usleep($retryDelay * 1000);
                $retryDelay *= 2;
            }
        }

        throw new \Exception("Max retries exceeded");
    }

    /**
     * Generate report sections
     */
    public function generateReportSections(string $context, array $sections, array $metadata = []): array
    {
        $results = [];
        
        Log::info("=== Generating Report Sections ===", [
            'provider' => $this->provider,
            'context_length' => safe_strlen($context),
            'sections_count' => count($sections)
        ]);

        foreach ($sections as $sectionName => $sectionPrompt) {
            $fullPrompt = $this->buildSectionPrompt($context, $sectionName, $sectionPrompt, $metadata);
            
            $result = $this->generateText($fullPrompt);
            
            if ($result['success']) {
                $results[$sectionName] = $result['text'];
            } else {
                $results[$sectionName] = "Error generating section: " . ($result['error'] ?? 'Unknown error');
            }

            // Small delay between requests
            usleep(100000); // 0.1 second
        }

        return [
            'success' => count($results) > 0,
            'sections' => $results,
            'provider' => $this->provider,
            'metadata' => [
                'total_sections' => count($sections),
                'successful_sections' => count(array_filter($results, fn($r) => !str_starts_with($r, 'Error'))),
            ],
        ];
    }

    /**
     * Build prompt for section
     */
    private function buildSectionPrompt(string $context, string $sectionName, string $sectionPrompt, array $metadata): string
    {
        $prompt = "Anda adalah AI assistant yang membantu membuat laporan akademik.\n\n";
        
        if (!empty($metadata['periode'])) {
            $prompt .= "PERIODE: {$metadata['periode']}\n";
        }
        
        if (!empty($metadata['tahun'])) {
            $prompt .= "TAHUN: {$metadata['tahun']}\n";
        }
        
        $prompt .= "\nKONTEKS DATA:\n{$context}\n\n";
        $prompt .= "TUGAS: Buat bagian '{$sectionName}' untuk laporan.\n";
        $prompt .= "INSTRUKSI: {$sectionPrompt}\n\n";
        $prompt .= "Gunakan bahasa Indonesia formal dan profesional.\n";
        $prompt .= "Berikan konten yang relevan dan terstruktur dengan baik.\n\n";
        $prompt .= "HASIL:";

        return $prompt;
    }

    /**
     * Get cache key
     */
    private function getCacheKey(string $prompt, array $options): string
    {
        $hash = md5($this->provider . $this->model . $prompt . json_encode($options));
        return "unified_ai_{$hash}";
    }

    /**
     * Get provider info
     */
    public function getProviderInfo(): array
    {
        return [
            'provider' => $this->provider,
            'model' => $this->model,
            'base_url' => $this->baseUrl,
            'api_key_configured' => !empty($this->apiKey),
            'cache_enabled' => $this->cacheEnabled,
            'cache_ttl_hours' => $this->cacheTTL / 3600,
        ];
    }

    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        Log::info("AI cache clear requested for provider: {$this->provider}");
    }
}
