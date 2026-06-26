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
        // Use centralized LLM configuration from config/services.php
        $this->provider = config('services.llm.provider');
        $this->apiKey = config('services.llm.api_key');
        $this->baseUrl = config('services.llm.base_url');
        $this->model = config('services.llm.model');
    }

    /**
     * Load configuration based on provider (DEPRECATED - now using config/services.php)
     * Kept for backward compatibility but no longer used
     */
    private function loadProviderConfig(): void
    {
        // This method is deprecated and no longer used
        // Configuration is now loaded directly from config/services.php in constructor
        return;
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
     * Generate chat response with conversation history support
     * This method uses the Chat Completions API format with messages array
     *
     * @param array $messages Array of messages with 'role' and 'content'
     *                        Example: [
     *                          ['role' => 'system', 'content' => 'You are a helpful assistant'],
     *                          ['role' => 'user', 'content' => 'Hello'],
     *                          ['role' => 'assistant', 'content' => 'Hi! How can I help?'],
     *                          ['role' => 'user', 'content' => 'Tell me more']
     *                        ]
     * @param array $options Additional options (temperature, max_tokens, etc.)
     * @return array Response with success, text, provider, model, etc.
     */
    public function generateChat(array $messages, array $options = []): array
    {
        $startTime = microtime(true);

        try {
            Log::info("=== AI Chat Generation Started ===", [
                'provider' => $this->provider,
                'model' => $this->model,
                'messages_count' => count($messages),
                'conversation_length' => count(array_filter($messages, fn($m) => ($m['role'] ?? '') !== 'system'))
            ]);

            // Validate messages format
            if (empty($messages)) {
                throw new \Exception("Messages array cannot be empty");
            }

            foreach ($messages as $msg) {
                if (!isset($msg['role']) || !isset($msg['content'])) {
                    throw new \Exception("Each message must have 'role' and 'content' keys");
                }
            }

            // Estimate total tokens and trim if necessary
            $messages = $this->trimMessagesIfNeeded($messages, $options);

            // Skip cache for conversation (to avoid conflicts in multi-turn chat)
            // Only cache if it's a single message (no conversation history)
            $hasConversationHistory = count(array_filter($messages, fn($m) => ($m['role'] ?? '') !== 'system')) > 1;

            $cacheKey = null;
            if (!$hasConversationHistory && $this->cacheEnabled) {
                $cacheKey = $this->getChatCacheKey($messages, $options);
                if (Cache::has($cacheKey)) {
                    $cached = Cache::get($cacheKey);
                    Log::info("AI chat cache hit", [
                        'provider' => $this->provider,
                        'messages_count' => count($messages)
                    ]);
                    return $cached;
                }
            }

            // Try primary provider first
            try {
                $response = match($this->provider) {
                    'claude' => $this->callClaudeChatAPI($messages, $options),
                    default => $this->callOpenAICompatibleChatAPI($messages, $options)
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
                    'messages_count' => count($messages),
                ];

                // Cache result only for single messages (not conversations)
                if ($cacheKey && !$hasConversationHistory) {
                    Cache::put($cacheKey, $result, $this->cacheTTL);
                }

                Log::info("AI chat generation completed", [
                    'provider' => $this->provider,
                    'text_length' => strlen($response['text']),
                    'processing_time_ms' => $processingTime,
                    'messages_count' => count($messages)
                ]);

                return $result;

            } catch (\Exception $primaryError) {
                // Check if it's a rate limit or quota error
                $errorMsg = $primaryError->getMessage();
                $isRateLimit = str_contains($errorMsg, 'Rate limit') ||
                               str_contains($errorMsg, 'rate_limit') ||
                               str_contains($errorMsg, '429') ||
                               str_contains($errorMsg, 'quota');

                Log::warning("Primary chat provider failed", [
                    'provider' => $this->provider,
                    'error' => $errorMsg,
                    'is_rate_limit' => $isRateLimit
                ]);

                if ($isRateLimit) {
                    // Try Gemini as fallback
                    return $this->fallbackToGeminiChat($messages, $options, $startTime);
                }

                throw $primaryError;
            }

        } catch (\Exception $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error("AI chat generation failed", [
                'provider' => $this->provider,
                'error' => $e->getMessage(),
                'processing_time_ms' => $processingTime,
                'messages_count' => count($messages)
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
     * Estimate tokens in messages and trim if necessary
     * Rough estimation: 1 token ≈ 4 characters
     *
     * @param array $messages Messages array
     * @param array $options Options with max_tokens
     * @return array Trimmed messages if needed
     */
    private function trimMessagesIfNeeded(array $messages, array $options): array
    {
        // Get model's context window (default to conservative estimate)
        $contextWindow = $this->getModelContextWindow();
        $maxOutputTokens = $options['max_tokens'] ?? 8192;

        // Reserve tokens for output and safety margin
        $maxInputTokens = $contextWindow - $maxOutputTokens - 500; // 500 token safety margin

        // Estimate current tokens
        $estimatedTokens = 0;
        foreach ($messages as $msg) {
            $content = $msg['content'] ?? '';
            $estimatedTokens += (int) (strlen($content) / 4);
        }

        Log::info("Token estimation", [
            'estimated_input_tokens' => $estimatedTokens,
            'max_input_tokens' => $maxInputTokens,
            'context_window' => $contextWindow,
            'messages_count' => count($messages)
        ]);

        // If within limits, return as is
        if ($estimatedTokens <= $maxInputTokens) {
            return $messages;
        }

        // Need to trim - keep system message and most recent messages
        Log::warning("Messages exceed token limit, trimming...", [
            'estimated_tokens' => $estimatedTokens,
            'max_tokens' => $maxInputTokens,
            'messages_count' => count($messages)
        ]);

        $trimmedMessages = [];
        $currentTokens = 0;

        // Always keep system message (first message)
        if (!empty($messages) && ($messages[0]['role'] ?? '') === 'system') {
            $systemMsg = array_shift($messages);
            $systemTokens = (int) (strlen($systemMsg['content']) / 4);
            $trimmedMessages[] = $systemMsg;
            $currentTokens += $systemTokens;
        }

        // Process remaining messages in reverse (keep most recent)
        $reversedMessages = array_reverse($messages);
        $keptMessages = [];

        foreach ($reversedMessages as $msg) {
            $content = $msg['content'] ?? '';
            $messageTokens = (int) (strlen($content) / 4);

            if ($currentTokens + $messageTokens > $maxInputTokens) {
                break;
            }

            $keptMessages[] = $msg;
            $currentTokens += $messageTokens;
        }

        // Reverse back to chronological order and add to trimmed messages
        $keptMessages = array_reverse($keptMessages);
        $trimmedMessages = array_merge($trimmedMessages, $keptMessages);

        Log::info("Messages trimmed", [
            'original_count' => count($messages) + 1, // +1 for system message
            'trimmed_count' => count($trimmedMessages),
            'estimated_tokens' => $currentTokens
        ]);

        return $trimmedMessages;
    }

    /**
     * Get model's context window size
     *
     * @return int Context window in tokens
     */
    private function getModelContextWindow(): int
    {
        // Context windows for different models
        $contextWindows = [
            // OpenAI models
            'gpt-4o' => 128000,
            'gpt-4o-mini' => 128000,
            'gpt-4-turbo' => 128000,
            'gpt-4' => 8192,
            'gpt-3.5-turbo' => 16385,

            // Groq models
            'llama-3.3-70b-versatile' => 8192,
            'llama-3.1-70b-versatile' => 131072,
            'llama-3.1-8b-instant' => 131072,
            'mixtral-8x7b-32768' => 32768,

            // OpenRouter models
            'meta-llama/llama-3.1-70b-instruct' => 131072,
            'meta-llama/llama-3.1-8b-instruct' => 131072,

            // Together models
            'meta-llama/Meta-Llama-3.1-70B-Instruct-Turbo' => 131072,

            // Claude models
            'claude-3-5-haiku-20241022' => 200000,
            'claude-3-5-sonnet-20241022' => 200000,

            // Gemini models
            'gemini-1.5-flash' => 1000000,
            'gemini-1.5-pro' => 2000000,
        ];

        return $contextWindows[$this->model] ?? 8192; // Conservative default
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
     * Call OpenAI-compatible Chat API with messages array (Groq, OpenRouter, Together)
     * This is the proper way to use Chat Completions API with conversation history
     */
    private function callOpenAICompatibleChatAPI(array $messages, array $options): array
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
                    'messages' => $messages,
                    'temperature' => $options['temperature'] ?? 0.7,
                    'max_tokens' => $options['max_tokens'] ?? 8192, // Increased from 4096
                ];

                Log::info("Calling {$this->provider} Chat API", [
                    'attempt' => $attempt,
                    'model' => $this->model,
                    'messages_count' => count($messages)
                ]);

                $response = Http::withHeaders($headers)
                    ->timeout(120) // Longer timeout for chat
                    ->post($this->baseUrl . '/chat/completions', $payload);

                if ($response->successful()) {
                    $data = $response->json();

                    if (!isset($data['choices'][0]['message']['content'])) {
                        throw new \Exception("Invalid API response structure");
                    }

                    $text = $data['choices'][0]['message']['content'];
                    $tokenCount = $data['usage']['total_tokens'] ?? null;

                    Log::info("{$this->provider} Chat API success", [
                        'response_length' => strlen($text),
                        'token_count' => $tokenCount
                    ]);

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

                Log::warning("Chat API call failed, retrying...", [
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
     * Call Claude Chat API with messages array
     */
    private function callClaudeChatAPI(array $messages, array $options): array
    {
        $maxRetries = 3;
        $retryDelay = 1000;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                // Extract system message if present
                $systemMessage = '';
                $chatMessages = [];

                foreach ($messages as $msg) {
                    if ($msg['role'] === 'system') {
                        $systemMessage = $msg['content'];
                    } else {
                        $chatMessages[] = $msg;
                    }
                }

                $payload = [
                    'model' => $this->model,
                    'max_tokens' => $options['max_tokens'] ?? 4096,
                    'messages' => $chatMessages,
                ];

                if (!empty($systemMessage)) {
                    $payload['system'] = $systemMessage;
                }

                $response = Http::withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ])->timeout(120)->post($this->baseUrl . '/messages', $payload);

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
     * Fallback to Gemini for chat when primary provider fails
     */
    private function fallbackToGeminiChat(array $messages, array $options, float $startTime): array
    {
        try {
            $geminiService = app(\App\Services\GeminiAIService::class);

            Log::info("Attempting Gemini fallback for chat");

            // Convert messages to single prompt for Gemini
            $prompt = $this->convertMessagesToPrompt($messages);

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

                Log::info("Gemini chat fallback successful", [
                    'text_length' => strlen($geminiResult['text']),
                    'processing_time_ms' => $processingTime
                ]);

                return $result;
            } else {
                throw new \Exception("Gemini fallback failed: " . ($geminiResult['error'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            Log::error("Gemini chat fallback failed", [
                'error' => $e->getMessage()
            ]);

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
     * Convert messages array to single prompt string (for fallback)
     */
    private function convertMessagesToPrompt(array $messages): string
    {
        $prompt = '';

        foreach ($messages as $msg) {
            $role = strtoupper($msg['role']);
            $content = $msg['content'];

            if ($role === 'SYSTEM') {
                $prompt .= "SYSTEM INSTRUCTIONS:\n{$content}\n\n";
            } elseif ($role === 'USER') {
                $prompt .= "USER: {$content}\n\n";
            } elseif ($role === 'ASSISTANT') {
                $prompt .= "ASSISTANT: {$content}\n\n";
            }
        }

        return $prompt;
    }

    /**
     * Get cache key for chat messages
     */
    private function getChatCacheKey(array $messages, array $options): string
    {
        $messagesHash = md5(json_encode($messages));
        $optionsHash = md5(json_encode($options));
        $hash = md5($this->provider . $this->model . $messagesHash . $optionsHash);
        return "unified_ai_chat_{$hash}";
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
