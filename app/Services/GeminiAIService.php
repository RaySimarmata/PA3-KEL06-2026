<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class GeminiAIService
{
    private $apiKey;
    private $model;
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1';
    
    // Free tier limits
    private $dailyLimit = 1500;
    private $rpmLimit = 15; // requests per minute
    
    // Cache settings
    private $cacheEnabled = true;
    private $cacheTTL = 86400; // 24 hours

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->model = env('GEMINI_MODEL', 'gemini-1.5-flash');
        
        if (empty($this->apiKey)) {
            Log::warning("GEMINI_API_KEY not configured");
        }
    }

    /**
     * Generate text from prompt
     */
    public function generateText(string $prompt, array $options = []): array
    {
        $startTime = microtime(true);

        try {
            Log::info("=== Gemini AI Generation Started ===", [
                'prompt_length' => safe_strlen($prompt),
                'model' => $this->model
            ]);

            // Check quota
            if (!$this->checkQuota()) {
                return $this->handleQuotaExceeded($prompt);
            }

            // Check cache
            $cacheKey = $this->getCacheKey($prompt, $options);
            if ($this->cacheEnabled && Cache::has($cacheKey)) {
                $cached = Cache::get($cacheKey);
                Log::info("Gemini cache hit", [
                    'prompt_length' => safe_strlen($prompt)
                ]);
                return $cached;
            }

            // Rate limiting
            $this->enforceRateLimit();

            // Call API
            $response = $this->callGeminiAPI($prompt, $options);

            // Track usage
            $this->trackUsage();

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            $result = [
                'success' => true,
                'text' => $response['text'],
                'token_count' => $response['token_count'] ?? null,
                'processing_time_ms' => $processingTime,
                'model' => $this->model,
                'cached' => false,
            ];

            // Cache result
            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $result, $this->cacheTTL);
            }

            Log::info("Gemini generation completed", [
                'text_length' => strlen(is_string($response['text']) ? $response['text'] : json_encode($response['text'])),
                'token_count' => $response['token_count'] ?? 'unknown',
                'processing_time_ms' => $processingTime
            ]);

            return $result;

        } catch (\Exception $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error("Gemini generation failed", [
                'error' => $e->getMessage(),
                'processing_time_ms' => $processingTime
            ]);

            return [
                'success' => false,
                'text' => '',
                'error' => $e->getMessage(),
                'processing_time_ms' => $processingTime,
                'model' => $this->model,
            ];
        }
    }

    /**
     * Generate report sections from context
     */
    public function generateReportSections(string $context, array $sections, array $metadata = []): array
    {
        $results = [];
        
        Log::info("=== Generating Report Sections ===", [
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

            // Small delay to respect rate limits
            usleep(100000); // 0.1 second
        }

        return [
            'success' => count($results) > 0,
            'sections' => $results,
            'metadata' => [
                'total_sections' => count($sections),
                'successful_sections' => count(array_filter($results, fn($r) => !str_starts_with($r, 'Error'))),
            ],
        ];
    }

    /**
     * Generate summary from text chunks
     */
    public function generateSummary(array $chunks, int $maxLength = 500): array
    {
        $combinedText = implode("\n\n", array_column($chunks, 'text'));
        
        $prompt = "Buatlah ringkasan komprehensif dari teks berikut dalam bahasa Indonesia. ";
        $prompt .= "Ringkasan harus mencakup poin-poin utama dan tidak lebih dari {$maxLength} kata.\n\n";
        $prompt .= "TEKS:\n{$combinedText}\n\n";
        $prompt .= "RINGKASAN:";

        return $this->generateText($prompt);
    }

    /**
     * Call Gemini API
     */
    private function callGeminiAPI(string $prompt, array $options = []): array
    {
        $maxRetries = 3;
        $retryDelay = 1000; // milliseconds

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";

                $payload = [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => $options['temperature'] ?? 0.7,
                        'topK' => $options['topK'] ?? 40,
                        'topP' => $options['topP'] ?? 0.95,
                        'maxOutputTokens' => $options['maxOutputTokens'] ?? 2048,
                    ],
                ];

                Log::info("Calling Gemini API", [
                    'attempt' => $attempt,
                    'url' => $url,
                    'model' => $this->model
                ]);

                $response = Http::timeout(60)
                    ->post($url, $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                        throw new \Exception("Invalid API response structure");
                    }

                    $text = $data['candidates'][0]['content']['parts'][0]['text'];
                    $tokenCount = $data['usageMetadata']['totalTokenCount'] ?? null;

                    return [
                        'text' => $text,
                        'token_count' => $tokenCount,
                    ];
                }

                // Handle specific error codes
                $statusCode = $response->status();
                $errorBody = $response->body();

                if ($statusCode === 429) {
                    Log::warning("Rate limit exceeded, retrying...", [
                        'attempt' => $attempt,
                        'retry_delay_ms' => $retryDelay
                    ]);
                    
                    if ($attempt < $maxRetries) {
                        usleep($retryDelay * 1000);
                        $retryDelay *= 2; // Exponential backoff
                        continue;
                    }
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
     * Build prompt for report section
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
     * Check if quota is available
     */
    private function checkQuota(): bool
    {
        $today = Carbon::today()->toDateString();
        $usageKey = "gemini_usage_{$today}";
        
        $usage = Cache::get($usageKey, 0);
        
        if ($usage >= $this->dailyLimit) {
            Log::warning("Daily quota exceeded", [
                'usage' => $usage,
                'limit' => $this->dailyLimit
            ]);
            return false;
        }

        return true;
    }

    /**
     * Track API usage
     */
    private function trackUsage(): void
    {
        $today = Carbon::today()->toDateString();
        $usageKey = "gemini_usage_{$today}";
        
        $usage = Cache::get($usageKey, 0);
        $usage++;
        
        // Store until end of day
        $expiresAt = Carbon::tomorrow();
        Cache::put($usageKey, $usage, $expiresAt);

        // Check if approaching limit
        if ($usage >= $this->dailyLimit * 0.8) {
            Log::warning("Approaching daily quota limit", [
                'usage' => $usage,
                'limit' => $this->dailyLimit,
                'percentage' => round(($usage / $this->dailyLimit) * 100, 1)
            ]);
        }
    }

    /**
     * Enforce rate limiting (15 RPM)
     */
    private function enforceRateLimit(): void
    {
        $minute = Carbon::now()->format('Y-m-d H:i');
        $rateLimitKey = "gemini_rpm_{$minute}";
        
        $requests = Cache::get($rateLimitKey, 0);
        
        if ($requests >= $this->rpmLimit) {
            $waitTime = 60 - Carbon::now()->second;
            Log::info("Rate limit reached, waiting...", [
                'wait_seconds' => $waitTime
            ]);
            sleep($waitTime);
        }

        Cache::put($rateLimitKey, $requests + 1, 60);
    }

    /**
     * Handle quota exceeded
     */
    private function handleQuotaExceeded(string $prompt): array
    {
        // Try to get from cache
        $cacheKey = $this->getCacheKey($prompt, []);
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            $cached['cached'] = true;
            $cached['quota_exceeded'] = true;
            
            Log::info("Quota exceeded, returning cached result");
            return $cached;
        }

        return [
            'success' => false,
            'text' => '',
            'error' => 'Daily quota exceeded. Please try again tomorrow.',
            'quota_exceeded' => true,
        ];
    }

    /**
     * Get cache key
     */
    private function getCacheKey(string $prompt, array $options): string
    {
        $hash = md5($prompt . json_encode($options));
        return "gemini_response_{$hash}";
    }

    /**
     * Get usage statistics
     */
    public function getUsageStats(): array
    {
        $today = Carbon::today()->toDateString();
        $usageKey = "gemini_usage_{$today}";
        
        $usage = Cache::get($usageKey, 0);
        $remaining = max(0, $this->dailyLimit - $usage);
        $percentage = $this->dailyLimit > 0 ? round(($usage / $this->dailyLimit) * 100, 1) : 0;

        return [
            'date' => $today,
            'usage' => $usage,
            'limit' => $this->dailyLimit,
            'remaining' => $remaining,
            'percentage_used' => $percentage,
            'rpm_limit' => $this->rpmLimit,
            'cache_enabled' => $this->cacheEnabled,
            'cache_ttl_hours' => $this->cacheTTL / 3600,
        ];
    }

    /**
     * Parse Gemini response into structured sections
     */
    public function parseResponse(string $text): array
    {
        // Try to detect sections in the response
        $sections = [];
        $lines = explode("\n", $text);
        $currentSection = null;
        $currentContent = '';

        foreach ($lines as $line) {
            $line = trim($line);
            
            // Detect section headers (e.g., "## Latar Belakang" or "LATAR BELAKANG:")
            if (preg_match('/^#{1,3}\s+(.+)$/', $line, $matches) || 
                preg_match('/^([A-Z\s]+):$/', $line, $matches)) {
                
                // Save previous section
                if ($currentSection && !empty($currentContent)) {
                    $sections[$currentSection] = trim($currentContent);
                }
                
                // Start new section
                $currentSection = strtolower(str_replace(' ', '_', trim($matches[1])));
                $currentContent = '';
            } else {
                $currentContent .= $line . "\n";
            }
        }

        // Save last section
        if ($currentSection && !empty($currentContent)) {
            $sections[$currentSection] = trim($currentContent);
        }

        // If no sections detected, return full text
        if (empty($sections)) {
            $sections['content'] = trim($text);
        }

        return [
            'sections' => $sections,
            'section_count' => count($sections),
            'total_length' => strlen($text),
        ];
    }

    /**
     * Validate response completeness
     */
    public function validateResponse(array $sections, array $requiredSections): array
    {
        $missing = [];
        $incomplete = [];

        foreach ($requiredSections as $section) {
            if (!isset($sections[$section])) {
                $missing[] = $section;
            } elseif (safe_strlen($sections[$section]) < 50) {
                $incomplete[] = $section;
            }
        }

        return [
            'valid' => empty($missing) && empty($incomplete),
            'missing_sections' => $missing,
            'incomplete_sections' => $incomplete,
        ];
    }

    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        Log::info("Gemini cache clear requested");
        // Would need cache tagging or manual tracking
    }
}
