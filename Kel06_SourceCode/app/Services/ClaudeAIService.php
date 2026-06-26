<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ClaudeAIService
 *
 * Memanggil Claude AI (Anthropic) langsung untuk membaca dokumen
 * dan menghasilkan summary/laporan seperti Claude.ai.
 *
 * Jika ANTHROPIC_API_KEY tidak tersedia, fallback ke Groq/LLM.
 */
class ClaudeAIService
{
    private string $apiKey;
    private string $model;
    private string $apiVersion;
    private bool   $useClaudeDirect;
    private ?float $lastResponseTime = null;

    // Fallback (Groq)
    private string $fallbackApiKey;
    private string $fallbackBaseUrl;
    private string $fallbackModel;

    public function __construct()
    {
        $this->apiKey      = config('services.anthropic.api_key') ?? env('ANTHROPIC_API_KEY', '') ?? '';
        $this->model       = config('services.anthropic.model') ?? env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022') ?? 'claude-3-5-sonnet-20241022';
        $this->apiVersion  = config('services.anthropic.version') ?? '2023-06-01';

        // Use Claude directly only if API key is set
        $this->useClaudeDirect = !empty($this->apiKey);

        // Fallback: Check provider and set appropriate config
        $provider = env('LLM_PROVIDER', 'groq') ?? 'groq';

        // Resolve fallback API key: if LLM_API_KEY is a placeholder, auto-switch to OpenRouter
        $rawLlmKey = config('services.llm.api_key') ?? env('LLM_API_KEY', '') ?? '';
        $isPlaceholder = str_contains($rawLlmKey, 'your_') || str_contains($rawLlmKey, '_here') || empty($rawLlmKey);

        if ($isPlaceholder) {
            // Auto-switch to OpenRouter which has a valid key configured
            $this->fallbackApiKey  = env('OPENROUTER_API_KEY', '') ?? '';
            $this->fallbackBaseUrl = 'https://openrouter.ai/api/v1';
            $this->fallbackModel   = env('OPENROUTER_MODEL', 'meta-llama/llama-3.2-3b-instruct:free') ?? 'meta-llama/llama-3.2-3b-instruct:free';
            $provider = 'openrouter'; // override for logging
        } elseif ($provider === 'openai') {
            $this->fallbackApiKey  = $rawLlmKey;
            $this->fallbackBaseUrl = config('services.llm.base_url') ?? env('LLM_BASE_URL', 'https://api.openai.com/v1') ?? 'https://api.openai.com/v1';
            $this->fallbackModel   = config('services.llm.model') ?? env('LLM_MODEL', 'gpt-3.5-turbo') ?? 'gpt-3.5-turbo';
        } else {
            $this->fallbackApiKey  = $rawLlmKey;
            $this->fallbackBaseUrl = config('services.llm.base_url') ?? env('LLM_BASE_URL', 'https://api.groq.com/openai/v1') ?? 'https://api.groq.com/openai/v1';
            $this->fallbackModel   = config('services.llm.model') ?? env('LLM_MODEL', 'llama-3.3-70b-versatile') ?? 'llama-3.3-70b-versatile';
        }

        Log::info('ClaudeAIService init', [
            'use_claude_direct' => $this->useClaudeDirect,
            'claude_model'      => $this->model,
            'fallback_provider' => $provider,
            'fallback_model'    => $this->fallbackModel,
        ]);
    }

    /**
     * Chat dengan AI — multi-turn conversation history.
     *
     * @param  string $systemPrompt  System/context instructions
     * @param  array  $messages      Array of ['role' => 'user'|'assistant', 'content' => string]
     * @param  int    $maxTokens     Maximum tokens in response
     * @return string|null           AI response text
     */
    public function chat(string $systemPrompt, array $messages, int $maxTokens = 4000): ?string
    {
        if ($this->useClaudeDirect) {
            return $this->callClaudeAPI($systemPrompt, $messages, $maxTokens);
        }

        return $this->callFallbackAPI($systemPrompt, $messages, $maxTokens);
    }

    /**
     * Simple single-turn chat.
     *
     * @param  string $systemPrompt
     * @param  string $userMessage
     * @param  int    $maxTokens
     * @return string|null
     */
    public function ask(string $systemPrompt, string $userMessage, int $maxTokens = 4000): ?string
    {
        // Additional safety: Ensure curly braces are escaped to prevent template errors
        $safeUserMessage = str_replace(['{', '}'], ['{{', '}}'], $userMessage);

        return $this->chat($systemPrompt, [
            ['role' => 'user', 'content' => $safeUserMessage],
        ], $maxTokens);
    }

    /**
     * Baca & rangkum dokumen dari teks yang sudah di-ekstrak.
     * Seperti Claude.ai: baca dokumen → tampilkan rangkuman.
     *
     * @param  string $extractedText  Teks yang sudah diekstrak dari file
     * @param  string $systemContext  Context tentang jenis laporan
     * @param  string $instructions   Instruksi khusus (bisa dari user)
     * @param  int    $maxTokens
     * @return string|null
     */
    public function readAndSummarize(
        string $extractedText,
        string $systemContext,
        string $instructions = '',
        int $maxTokens = 4096
    ): ?string {
        $systemPrompt = $systemContext;

        // Additional safety: Ensure curly braces are escaped to prevent template errors
        $safeExtractedText = str_replace(['{', '}'], ['{{', '}}'], $extractedText);
        $safeInstructions = str_replace(['{', '}'], ['{{', '}}'], $instructions);

        // Truncate extracted text if too large (estimate ~4 chars per token)
        $maxContentLength = 15000; // ~3750 tokens for content, leaving room for system prompt and instructions
        if (strlen($safeExtractedText) > $maxContentLength) {
            $safeExtractedText = substr($safeExtractedText, 0, $maxContentLength) . "\n\n[DOKUMEN DIPOTONG KARENA TERLALU PANJANG - HANYA BAGIAN AWAL YANG DIPROSES]";
            Log::info('Document truncated for AI processing', [
                'original_length' => strlen($extractedText),
                'truncated_length' => strlen($safeExtractedText)
            ]);
        }

        $userMessage  = "PENTING: File sudah berhasil dibaca dan diekstrak. Berikut adalah KONTEN LENGKAP dari dokumen yang telah diupload:\n\n";
        $userMessage .= "=== MULAI KONTEN DOKUMEN ===\n";
        $userMessage .= $safeExtractedText . "\n";
        $userMessage .= "=== AKHIR KONTEN DOKUMEN ===\n\n";

        if (!empty($safeInstructions)) {
            $userMessage .= "Instruksi dari user:\n" . $safeInstructions . "\n\n";
        }

        $userMessage .= "Berdasarkan konten dokumen di atas yang SUDAH BERHASIL DIBACA, lakukan hal berikut:\n\n";
        $userMessage .= "1. Baca dan pahami isi dokumen secara lengkap\n";
        $userMessage .= "2. Buat **RINGKASAN DOKUMEN** — jelaskan secara komprehensif isi dan tujuan dokumen\n";
        $userMessage .= "3. Buat **POIN-POIN UTAMA** — daftar poin penting dari dokumen\n";
        $userMessage .= "4. Buat **DATA DAN TEMUAN** — data, angka, atau temuan penting yang ada\n";
        $userMessage .= "5. Buat **DRAFT LAPORAN** berdasarkan isi dokumen dengan format markdown heading (#)\n\n";
        $userMessage .= "CATATAN PENTING:\n";
        $userMessage .= "- Dokumen SUDAH berhasil dibaca dan kontennya ada di atas\n";
        $userMessage .= "- JANGAN katakan bahwa Anda tidak bisa membaca file atau tidak memiliki akses\n";
        $userMessage .= "- JANGAN gunakan kode periode seperti Q1, Q2, Q3, Q4 dalam judul laporan\n";
        $userMessage .= "- Gunakan nama periode lengkap seperti 'Triwulan I', 'Triwulan II', 'Triwulan III', atau 'Triwulan IV'\n";
        $userMessage .= "- Langsung proses konten dokumen yang sudah diberikan di atas\n";
        $userMessage .= "- Gunakan Bahasa Indonesia formal dan profesional\n";
        $userMessage .= "- Semua konten harus berdasarkan isi dokumen yang telah diberikan\n";

        $response = $this->chat($systemPrompt, [
            ['role' => 'user', 'content' => $userMessage],
        ], $maxTokens);

        // Validate response - jika AI masih mengatakan tidak bisa akses file, retry dengan prompt yang lebih tegas
        if ($response && $this->containsFileAccessError($response)) {
            Log::warning('AI returned file access error, retrying with stronger prompt');

            $retryMessage = "INSTRUKSI TEGAS:\n\n";
            $retryMessage .= "Konten dokumen SUDAH diberikan di bawah ini. Kamu HARUS memproses konten ini.\n";
            $retryMessage .= "JANGAN katakan tidak bisa mengakses file. File SUDAH diekstrak dan kontennya ADA DI SINI:\n\n";
            $retryMessage .= "=== KONTEN DOKUMEN ===\n";
            $retryMessage .= $safeExtractedText . "\n";
            $retryMessage .= "=== AKHIR KONTEN ===\n\n";

            if (!empty($safeInstructions)) {
                $retryMessage .= "Instruksi: " . $safeInstructions . "\n\n";
            }

            $retryMessage .= "Sekarang LANGSUNG buat:\n";
            $retryMessage .= "1. Ringkasan dokumen\n";
            $retryMessage .= "2. Poin-poin utama\n";
            $retryMessage .= "3. Draft laporan dengan format markdown\n\n";
            $retryMessage .= "PENTING: JANGAN gunakan kode periode seperti Q1, Q2, Q3, Q4. Gunakan 'Triwulan I', 'Triwulan II', 'Triwulan III', atau 'Triwulan IV'.\n\n";
            $retryMessage .= "Mulai sekarang dengan heading # RINGKASAN DOKUMEN";

            $response = $this->chat($systemPrompt, [
                ['role' => 'user', 'content' => $retryMessage],
            ], $maxTokens);
        }

        return $response;
    }

    /**
     * Check if AI response contains file access error messages
     */
    private function containsFileAccessError(string $response): bool
    {
        $errorPatterns = [
            'tidak dapat melihat',
            'tidak dapat mengakses',
            'tidak memiliki kemampuan',
            'tidak bisa melihat',
            'tidak bisa mengakses',
            'model bahasa yang berjalan di server',
            'tidak memiliki akses',
            'mohon maaf, saya tidak dapat',
        ];

        $lowerResponse = strtolower($response);
        foreach ($errorPatterns as $pattern) {
            if (strpos($lowerResponse, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Follow-up chat — user memberikan instruksi baru berdasarkan draft sebelumnya.
     *
     * @param  string $systemPrompt
     * @param  string $previousAIResponse  Draft/response AI sebelumnya
     * @param  string $userFollowUp        Instruksi baru dari user
     * @param  string $fileContext         (opsional) isi file jika diupload ulang
     * @param  int    $maxTokens
     * @return string|null
     */
    public function followUp(
        string $systemPrompt,
        string $previousAIResponse,
        string $userFollowUp,
        string $fileContext = '',
        int $maxTokens = 4096
    ): ?string {
        $messages = [];

        // Additional safety: Ensure curly braces are escaped to prevent template errors
        $safeFileContext = str_replace(['{', '}'], ['{{', '}}'], $fileContext);
        $safeUserFollowUp = str_replace(['{', '}'], ['{{', '}}'], $userFollowUp);
        $safePreviousResponse = str_replace(['{', '}'], ['{{', '}}'], $previousAIResponse);

        // Build initial context message
        $contextMsg = '';
        if (!empty($safeFileContext)) {
            $contextMsg .= "Dokumen referensi:\n```\n" . $safeFileContext . "\n```\n\n";
        }
        $contextMsg .= "Buatkan draft laporan berdasarkan dokumen/instruksi yang diberikan.";

        $messages[] = ['role' => 'user',      'content' => $contextMsg];
        $messages[] = ['role' => 'assistant', 'content' => $safePreviousResponse];
        $messages[] = ['role' => 'user',      'content' => $safeUserFollowUp];

        return $this->chat($systemPrompt, $messages, $maxTokens);
    }

    // =========================================================================
    // PRIVATE — API CALLS
    // =========================================================================

    /**
     * Panggil Anthropic Claude Messages API langsung.
     */
    private function callClaudeAPI(string $systemPrompt, array $messages, int $maxTokens): ?string
    {
        try {
            Log::info('ClaudeAI: calling Anthropic API', [
                'model'      => $this->model,
                'max_tokens' => $maxTokens,
                'turns'      => count($messages),
            ]);

            $startTime = microtime(true);

            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => $this->apiVersion,
                'Content-Type'      => 'application/json',
            ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
                'model'      => $this->model,
                'max_tokens' => $maxTokens,
                'system'     => $systemPrompt,
                'messages'   => $messages,
            ]);

            $responseTime = microtime(true) - $startTime;

            if ($response->successful()) {
                $data = $response->json();
                // Claude response: content[0].text
                $text = $data['content'][0]['text'] ?? null;
                if ($text) {
                    Log::info('ClaudeAI: response received', [
                        'length' => strlen($text),
                        'response_time' => round($responseTime, 2) . 's'
                    ]);

                    // Store response time for metrics
                    $this->lastResponseTime = $responseTime;

                    return trim($text);
                }
            }

            Log::error('ClaudeAI: API returned error', [
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 800),
            ]);

            // If Claude fails (e.g. quota exceeded), fallback to Groq
            Log::warning('ClaudeAI: falling back to Groq');
            return $this->callFallbackAPI($systemPrompt, $messages, $maxTokens);

        } catch (\Exception $e) {
            Log::error('ClaudeAI: exception calling Anthropic', ['error' => $e->getMessage()]);
            // Fallback
            return $this->callFallbackAPI($systemPrompt, $messages, $maxTokens);
        }
    }

    /**
     * Fallback: panggil Groq (OpenAI-compatible) API.
     */
    private function callFallbackAPI(string $systemPrompt, array $messages, int $maxTokens): ?string
    {
        try {
            $provider = env('LLM_PROVIDER', 'groq');

            if ($provider === 'huggingface') {
                return $this->callHuggingFaceAPI($systemPrompt, $messages, $maxTokens);
            }

            if ($provider === 'openrouter') {
                return $this->callOpenRouterAPI($systemPrompt, $messages, $maxTokens);
            }

            if ($provider === 'openai') {
                return $this->callOpenAIAPI($systemPrompt, $messages, $maxTokens);
            }

            Log::info('ClaudeAI: using Groq fallback', [
                'model' => $this->fallbackModel,
            ]);

            // Convert messages to OpenAI format (add system as first message)
            $openAIMessages = [
                ['role' => 'system', 'content' => $systemPrompt],
            ];
            foreach ($messages as $m) {
                $openAIMessages[] = ['role' => $m['role'], 'content' => $m['content']];
            }

            $startTime = microtime(true);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->fallbackApiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(120)->post($this->fallbackBaseUrl . '/chat/completions', [
                'model'       => $this->fallbackModel,
                'messages'    => $openAIMessages,
                'temperature' => 0.7,
                'max_tokens'  => min($maxTokens, 8000),
            ]);

            $responseTime = microtime(true) - $startTime;

            if ($response->successful()) {
                $data    = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? null;
                if ($content) {
                    Log::info('ClaudeAI (Groq fallback): response received', [
                        'length' => strlen($content),
                        'response_time' => round($responseTime, 2) . 's'
                    ]);

                    // Store response time for metrics
                    $this->lastResponseTime = $responseTime;

                    return trim($content);
                }
            }

            // Check for rate limit or request too large error
            $statusCode = $response->status();
            $body = $response->body();

            if ($statusCode === 429 || $statusCode === 413) {
                Log::warning('ClaudeAI Groq: Rate limit or request too large', [
                    'status' => $statusCode,
                    'body'   => substr($body, 0, 500),
                ]);

                // Try OpenRouter as secondary fallback immediately
                $openrouterKey = env('OPENROUTER_API_KEY', '');
                if (!empty($openrouterKey)) {
                    Log::info('ClaudeAI: Trying OpenRouter as secondary fallback');
                    return $this->callOpenRouterAPI($systemPrompt, $messages, min($maxTokens, 2000)); // Reduce token limit
                }

                // If no OpenRouter key, return a helpful error
                throw new \Exception('Dokumen terlalu besar untuk diproses. Silakan gunakan dokumen yang lebih kecil atau coba lagi nanti.');
            }

            Log::error('ClaudeAI Groq fallback failed', [
                'status' => $statusCode,
                'body'   => substr($body, 0, 800),
            ]);

            return null;

        } catch (\Exception $e) {
            // If Groq fails, try OpenRouter before giving up
            $openrouterKey = env('OPENROUTER_API_KEY', '');
            if (!empty($openrouterKey) && !str_contains($e->getMessage(), 'OpenRouter')) {
                Log::info('ClaudeAI: Groq failed, trying OpenRouter as last resort');
                try {
                    return $this->callOpenRouterAPI($systemPrompt, $messages, $maxTokens);
                } catch (\Exception $openrouterError) {
                    Log::error('ClaudeAI: OpenRouter also failed', ['error' => $openrouterError->getMessage()]);
                }
            }

            Log::error('ClaudeAI Groq fallback exception', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Call OpenAI ChatGPT API
     */
    private function callOpenAIAPI(string $systemPrompt, array $messages, int $maxTokens): ?string
    {
        try {
            Log::info('ClaudeAI: using OpenAI ChatGPT API', [
                'model' => $this->fallbackModel,
            ]);

            // Convert messages to OpenAI format
            $openAIMessages = [
                ['role' => 'system', 'content' => $systemPrompt],
            ];
            foreach ($messages as $m) {
                $openAIMessages[] = ['role' => $m['role'], 'content' => $m['content']];
            }

            $startTime = microtime(true);

            $response = Http::withOptions([
                'verify' => false, // Disable SSL verification for development (NOT RECOMMENDED FOR PRODUCTION)
                'connect_timeout' => 30,
                'timeout' => 120,
            ])->withHeaders([
                'Authorization' => 'Bearer ' . $this->fallbackApiKey,
                'Content-Type'  => 'application/json',
            ])->post($this->fallbackBaseUrl . '/chat/completions', [
                'model' => $this->fallbackModel,
                'messages' => $openAIMessages,
                'temperature' => 0.7,
                'max_tokens' => min($maxTokens, 4000),
            ]);

            $responseTime = microtime(true) - $startTime;

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? null;
                if ($content) {
                    Log::info('ClaudeAI (OpenAI): response received', [
                        'length' => strlen($content),
                        'response_time' => round($responseTime, 2) . 's'
                    ]);

                    // Store response time for metrics
                    $this->lastResponseTime = $responseTime;

                    return trim($content);
                }
            }

            $statusCode = $response->status();
            $body = $response->body();

            // Check for common OpenAI errors
            if ($statusCode === 401) {
                Log::error('OpenAI: Invalid API key');
                throw new \Exception('API key OpenAI tidak valid. Silakan periksa konfigurasi OPENAI_API_KEY di file .env');
            }

            if ($statusCode === 429) {
                Log::error('OpenAI: Rate limit exceeded');
                throw new \Exception('Rate limit OpenAI tercapai. Silakan coba lagi dalam beberapa menit atau upgrade plan OpenAI Anda.');
            }

            if ($statusCode === 402) {
                Log::error('OpenAI: Insufficient quota');
                throw new \Exception('Quota OpenAI habis. Silakan top up balance atau upgrade plan OpenAI Anda.');
            }

            Log::error('ClaudeAI OpenAI failed', [
                'status' => $statusCode,
                'body'   => substr($body, 0, 800),
            ]);

            // Fallback to OpenRouter if OpenAI fails
            Log::warning('OpenAI failed, trying OpenRouter as backup');
            return $this->callOpenRouterAPI($systemPrompt, $messages, $maxTokens);

        } catch (\Exception $e) {
            Log::error('ClaudeAI OpenAI exception', ['error' => $e->getMessage()]);

            // If cURL error (DNS, connection issues), try OpenRouter backup
            if (strpos($e->getMessage(), 'cURL error') !== false ||
                strpos($e->getMessage(), 'Could not resolve host') !== false ||
                strpos($e->getMessage(), 'Connection') !== false) {
                Log::warning('OpenAI connection failed (DNS/Network issue), trying OpenRouter as backup');
                return $this->callOpenRouterAPI($systemPrompt, $messages, $maxTokens);
            }

            // If it's a configuration error, throw it
            if (strpos($e->getMessage(), 'API key') !== false ||
                strpos($e->getMessage(), 'quota') !== false ||
                strpos($e->getMessage(), 'rate limit') !== false) {
                throw $e;
            }

            // Otherwise try OpenRouter backup
            return $this->callOpenRouterAPI($systemPrompt, $messages, $maxTokens);
        }
    }

    /**
     * Call Hugging Face Inference API (FREE)
     */
    private function callHuggingFaceAPI(string $systemPrompt, array $messages, int $maxTokens): ?string
    {
        try {
            Log::info('ClaudeAI: using Hugging Face API', [
                'model' => $this->fallbackModel,
            ]);

            // Combine system prompt and messages into single text
            $fullPrompt = $systemPrompt . "\n\n";
            foreach ($messages as $msg) {
                $role = $msg['role'] === 'user' ? 'Human' : 'Assistant';
                $fullPrompt .= $role . ": " . $msg['content'] . "\n\n";
            }
            $fullPrompt .= "Assistant: ";

            // Correct Hugging Face API endpoint
            $apiUrl = "https://api-inference.huggingface.co/models/" . $this->fallbackModel;

            $startTime = microtime(true);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->fallbackApiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(120)->post($apiUrl, [
                'inputs' => $fullPrompt,
                'parameters' => [
                    'max_new_tokens' => min($maxTokens, 2000),
                    'temperature' => 0.7,
                    'do_sample' => true,
                    'return_full_text' => false,
                ],
                'options' => [
                    'wait_for_model' => true,
                ],
            ]);

            $responseTime = microtime(true) - $startTime;

            if ($response->successful()) {
                $data = $response->json();

                // Hugging Face returns array of results
                if (is_array($data) && isset($data[0]['generated_text'])) {
                    $content = trim($data[0]['generated_text']);
                    Log::info('ClaudeAI (Hugging Face): response received', [
                        'length' => strlen($content),
                        'response_time' => round($responseTime, 2) . 's'
                    ]);

                    // Store response time for metrics
                    $this->lastResponseTime = $responseTime;

                    return $content;
                }
            }

            $statusCode = $response->status();
            $body = $response->body();

            // If Hugging Face fails, try OpenRouter as backup
            if ($statusCode === 404 || $statusCode >= 500) {
                Log::warning('Hugging Face failed, trying OpenRouter backup');
                return $this->callOpenRouterAPI($systemPrompt, $messages, $maxTokens);
            }

            Log::error('ClaudeAI Hugging Face failed', [
                'status' => $statusCode,
                'body'   => substr($body, 0, 800),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('ClaudeAI Hugging Face exception', ['error' => $e->getMessage()]);
            // Try OpenRouter as backup
            return $this->callOpenRouterAPI($systemPrompt, $messages, $maxTokens);
        }
    }

    /**
     * Call OpenRouter API (FREE models available)
     */
    private function callOpenRouterAPI(string $systemPrompt, array $messages, int $maxTokens): ?string
    {
        try {
            Log::info('ClaudeAI: using OpenRouter API as backup');

            // Convert messages to OpenAI format
            $openAIMessages = [
                ['role' => 'system', 'content' => $systemPrompt],
            ];
            foreach ($messages as $m) {
                $openAIMessages[] = ['role' => $m['role'], 'content' => $m['content']];
            }

            $openRouterKey = env('OPENROUTER_API_KEY', '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');

            // Try multiple free models in order (updated with working models)
            $freeModels = [
                'meta-llama/llama-3.2-3b-instruct:free',
                'microsoft/phi-3-mini-128k-instruct:free',
                'google/gemma-2-9b-it:free',
                'meta-llama/llama-3.2-1b-instruct:free',
                'qwen/qwen-2-7b-instruct:free',
                'huggingface/zephyr-7b-beta:free',
            ];

            foreach ($freeModels as $model) {
                try {
                    Log::info("Trying OpenRouter model: {$model}");

                    $startTime = microtime(true);

                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $openRouterKey,
                        'Content-Type'  => 'application/json',
                        'HTTP-Referer' => env('APP_URL', 'http://localhost:8000'),
                        'X-Title' => 'GKM GJM System',
                    ])->timeout(60)->post('https://openrouter.ai/api/v1/chat/completions', [
                        'model' => $model,
                        'messages' => $openAIMessages,
                        'temperature' => 0.7,
                        'max_tokens' => min($maxTokens, 2000),
                    ]);

                    $responseTime = microtime(true) - $startTime;

                    if ($response->successful()) {
                        $data = $response->json();
                        $content = $data['choices'][0]['message']['content'] ?? null;
                        if ($content) {
                            Log::info("OpenRouter success with model: {$model}", [
                                'length' => strlen($content),
                                'response_time' => round($responseTime, 2) . 's'
                            ]);

                            // Store response time for metrics
                            $this->lastResponseTime = $responseTime;

                            return trim($content);
                        }
                    }

                    $statusCode = $response->status();
                    if ($statusCode === 429) {
                        Log::warning("Model {$model} rate limited, trying next...");
                        continue; // Try next model
                    }

                    Log::warning("Model {$model} failed with status {$statusCode}");

                } catch (\Exception $e) {
                    Log::warning("Model {$model} exception: " . $e->getMessage());
                    continue; // Try next model
                }
            }

            // If all models failed, return a simple fallback response
            Log::error('All OpenRouter models failed, using fallback response');

            // Generate a simple response based on the user message
            $userMessage = '';
            foreach ($messages as $msg) {
                if ($msg['role'] === 'user') {
                    $userMessage = $msg['content'];
                    break;
                }
            }

            return $this->generateFallbackResponse($userMessage);

        } catch (\Exception $e) {
            Log::error('ClaudeAI OpenRouter backup exception', ['error' => $e->getMessage()]);
            return $this->generateFallbackResponse('');
        }
    }

    /**
     * Generate a proper AI response when all APIs fail
     * This should NOT be a template response but should try alternative methods
     */
    private function generateFallbackResponse(string $userMessage): string
    {
        // Log the failure for debugging
        Log::error('All AI APIs failed, this should not happen in production', [
            'user_message_length' => strlen($userMessage),
            'timestamp' => now()
        ]);

        // Return a proper error message instead of fake AI response
        return "Maaf, semua layanan AI sedang tidak tersedia saat ini. Silakan:\n\n" .
               "1. Coba lagi dalam beberapa menit\n" .
               "2. Periksa koneksi internet Anda\n" .
               "3. Hubungi administrator sistem jika masalah berlanjut\n\n" .
               "**Catatan**: Sistem memerlukan koneksi ke layanan AI untuk menganalisis dokumen dan menghasilkan laporan yang akurat.";
    }

    /**
     * Analyze image content using Claude Vision API
     *
     * @param string $imageData Base64 encoded image data
     * @param string $mimeType Image MIME type
     * @param string $analysisPrompt Prompt for analysis
     * @return string Analysis result
     */
    public function analyzeImageContent(string $imageData, string $mimeType, string $analysisPrompt): string
    {
        if (!$this->useClaudeDirect) {
            // Fallback: Cannot analyze images without Claude API
            Log::warning('Image analysis requires Claude API, falling back to text-only');
            return json_encode([
                'is_valid' => true,
                'reason' => 'Image analysis not available, allowing by default',
                'confidence' => 0.5
            ]);
        }

        try {
            Log::info('Analyzing image content with Claude Vision');

            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => $this->apiVersion,
                'Content-Type'      => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model'      => $this->model,
                'max_tokens' => 1024,
                'messages'   => [[
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $mimeType,
                                'data' => $imageData
                            ]
                        ],
                        [
                            'type' => 'text',
                            'text' => $analysisPrompt
                        ]
                    ]
                ]],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['content'][0]['text'] ?? null;

                if ($text) {
                    Log::info('Image analysis completed', ['response_length' => strlen($text)]);
                    return trim($text);
                }
            }

            Log::error('Image analysis failed', [
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 500),
            ]);

            // Return default allow response
            return json_encode([
                'is_valid' => true,
                'reason' => 'Analysis failed, allowing by default',
                'confidence' => 0.5
            ]);

        } catch (\Exception $e) {
            Log::error('Image analysis exception', ['error' => $e->getMessage()]);

            return json_encode([
                'is_valid' => true,
                'reason' => 'Analysis exception, allowing by default',
                'confidence' => 0.5
            ]);
        }
    }

    /**
     * Apakah menggunakan Claude langsung (Anthropic API)?
     */
    public function isUsingClaude(): bool
    {
        return $this->useClaudeDirect;
    }

    /**
     * Info model yang digunakan.
     */
    public function getModelInfo(): string
    {
        if ($this->useClaudeDirect) {
            return 'Claude (' . $this->model . ')';
        }

        $provider = env('LLM_PROVIDER', 'groq');
        return match($provider) {
            'openai' => 'OpenAI ChatGPT (' . $this->fallbackModel . ')',
            'huggingface' => 'Hugging Face (' . $this->fallbackModel . ')',
            'openrouter' => 'OpenRouter (' . $this->fallbackModel . ')',
            default => 'Groq (' . $this->fallbackModel . ')'
        };
    }

    /**
     * Chat dengan AI dengan dukungan Vision (gambar)
     * Hanya tersedia jika menggunakan Claude API langsung
     *
     * @param  string $systemPrompt  System/context instructions
     * @param  array  $messages      Array of ['role' => 'user'|'assistant', 'content' => string]
     * @param  array  $images        Array of image data ['filename', 'data' (base64), 'mime_type']
     * @param  int    $maxTokens     Maximum tokens in response
     * @return string|null           AI response text
     */
    public function chatWithVision(string $systemPrompt, array $messages, array $images, int $maxTokens = 4000): ?string
    {
        if (!$this->useClaudeDirect) {
            Log::warning('Vision API requires Claude direct API, falling back to text-only');
            return $this->callFallbackAPI($systemPrompt, $messages, $maxTokens);
        }

        return $this->callClaudeVisionAPI($systemPrompt, $messages, $images, $maxTokens);
    }

    /**
     * Call Claude Vision API dengan gambar
     */
    private function callClaudeVisionAPI(string $systemPrompt, array $messages, array $images, int $maxTokens): ?string
    {
        try {
            // Prepare messages with images
            $claudeMessages = [];

            foreach ($messages as $msg) {
                $content = [];

                // Add text content
                $content[] = [
                    'type' => 'text',
                    'text' => $msg['content']
                ];

                // Add images to the last user message
                if ($msg['role'] === 'user' && !empty($images)) {
                    foreach ($images as $image) {
                        $content[] = [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $image['mime_type'],
                                'data' => $image['data']
                            ]
                        ];
                    }
                    // Clear images after adding to prevent duplication
                    $images = [];
                }

                $claudeMessages[] = [
                    'role' => $msg['role'],
                    'content' => $content
                ];
            }

            $payload = [
                'model' => $this->model,
                'max_tokens' => $maxTokens,
                'system' => $systemPrompt,
                'messages' => $claudeMessages,
            ];

            Log::info('Calling Claude Vision API', [
                'model' => $this->model,
                'messages_count' => count($claudeMessages),
                'max_tokens' => $maxTokens,
                'system_length' => strlen($systemPrompt)
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->apiVersion,
            ])->timeout(120)->post('https://api.anthropic.com/v1/messages', $payload);

            if (!$response->successful()) {
                Log::error('Claude Vision API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            $data = $response->json();

            if (!isset($data['content'][0]['text'])) {
                Log::error('Unexpected Claude Vision API response format', ['data' => $data]);
                return null;
            }

            $result = $data['content'][0]['text'];

            Log::info('Claude Vision API success', [
                'response_length' => strlen($result),
                'usage' => $data['usage'] ?? null
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Claude Vision API exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Get last response time in seconds
     *
     * @return float|null
     */
    public function getLastResponseTime(): ?float
    {
        return $this->lastResponseTime;
    }
}
