<?php

namespace App\Services;

use App\Models\AIResponseCache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AICacheService
{
    private float $defaultSimilarityThreshold = 0.85;
    private int $maxCacheAge = 30; // days
    
    /**
     * Check if there's a cached response for the given prompt and context
     */
    public function getCachedResponse(string $prompt, array $context = []): ?array
    {
        try {
            // Generate cache key for exact match
            $cacheKey = AIResponseCache::generateCacheKey($prompt, $context);
            
            // Try exact match first
            $cached = AIResponseCache::where('cache_key', $cacheKey)
                ->where('created_at', '>', Carbon::now()->subDays($this->maxCacheAge))
                ->first();

            if ($cached) {
                $cached->incrementUsage();
                
                Log::info('AI Cache: Exact match found', [
                    'cache_id' => $cached->id,
                    'usage_count' => $cached->usage_count,
                    'original_created' => $cached->created_at,
                    'prompt_preview' => substr($prompt, 0, 100) . '...'
                ]);

                return [
                    'success' => true,
                    'text' => $cached->ai_response,
                    'provider' => $cached->ai_provider,
                    'model' => $cached->ai_model,
                    'cached' => true,
                    'cache_id' => $cached->id,
                    'usage_count' => $cached->usage_count,
                ];
            }

            // Try similarity match
            $similarCached = AIResponseCache::findSimilar($prompt, $context, $this->defaultSimilarityThreshold);
            
            if ($similarCached) {
                $similarity = AIResponseCache::calculateSimilarity($prompt, $similarCached->original_prompt);
                $similarCached->incrementUsage();
                
                Log::info('AI Cache: Similar match found', [
                    'cache_id' => $similarCached->id,
                    'similarity' => $similarity,
                    'usage_count' => $similarCached->usage_count,
                    'original_prompt' => substr($similarCached->original_prompt, 0, 100) . '...',
                    'current_prompt' => substr($prompt, 0, 100) . '...'
                ]);

                return [
                    'success' => true,
                    'text' => $similarCached->ai_response,
                    'provider' => $similarCached->ai_provider,
                    'model' => $similarCached->ai_model,
                    'cached' => true,
                    'cache_id' => $similarCached->id,
                    'usage_count' => $similarCached->usage_count,
                    'similarity' => $similarity,
                ];
            }

            Log::info('AI Cache: No match found', [
                'prompt_preview' => substr($prompt, 0, 100) . '...',
                'context_keys' => array_keys($context)
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('AI Cache: Error retrieving cached response', [
                'error' => $e->getMessage(),
                'prompt_preview' => substr($prompt, 0, 100) . '...'
            ]);
            
            return null;
        }
    }

    /**
     * Cache a new AI response
     */
    public function cacheResponse(
        string $prompt, 
        array $context, 
        string $response, 
        string $provider, 
        string $model
    ): bool {
        try {
            $cacheKey = AIResponseCache::generateCacheKey($prompt, $context);
            $promptHash = AIResponseCache::generatePromptHash($prompt);

            // Check if already exists (avoid duplicates)
            $existing = AIResponseCache::where('cache_key', $cacheKey)->first();
            if ($existing) {
                Log::info('AI Cache: Response already cached', [
                    'cache_id' => $existing->id,
                    'prompt_preview' => substr($prompt, 0, 100) . '...'
                ]);
                return true;
            }

            // Create new cache entry
            $cached = AIResponseCache::create([
                'cache_key' => $cacheKey,
                'prompt_hash' => $promptHash,
                'original_prompt' => $prompt,
                'context_metadata' => $context,
                'ai_response' => $response,
                'ai_provider' => $provider,
                'ai_model' => $model,
                'usage_count' => 1,
                'last_used_at' => now(),
                'response_length' => strlen($response),
                'similarity_threshold' => $this->defaultSimilarityThreshold,
            ]);

            Log::info('AI Cache: Response cached successfully', [
                'cache_id' => $cached->id,
                'prompt_length' => strlen($prompt),
                'response_length' => strlen($response),
                'provider' => $provider,
                'model' => $model
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('AI Cache: Error caching response', [
                'error' => $e->getMessage(),
                'prompt_preview' => substr($prompt, 0, 100) . '...',
                'provider' => $provider,
                'model' => $model
            ]);
            
            return false;
        }
    }

    /**
     * Clean old cache entries
     */
    public function cleanOldEntries(): int
    {
        try {
            $deleted = AIResponseCache::cleanOldEntries($this->maxCacheAge);
            
            Log::info('AI Cache: Cleaned old entries', [
                'deleted_count' => $deleted,
                'max_age_days' => $this->maxCacheAge
            ]);

            return $deleted;

        } catch (\Exception $e) {
            Log::error('AI Cache: Error cleaning old entries', [
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }

    /**
     * Get cache statistics
     */
    public function getStatistics(): array
    {
        try {
            return AIResponseCache::getStatistics();
        } catch (\Exception $e) {
            Log::error('AI Cache: Error getting statistics', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'total_entries' => 0,
                'total_usage' => 0,
                'average_usage_per_entry' => 0,
                'recently_used_entries' => 0,
                'cache_hit_potential' => 0,
            ];
        }
    }

    /**
     * Invalidate cache for specific context (e.g., when template changes)
     */
    public function invalidateByContext(array $contextFilter): int
    {
        try {
            $query = AIResponseCache::query();
            
            foreach ($contextFilter as $key => $value) {
                $query->whereJsonContains('context_metadata->' . $key, $value);
            }
            
            $deleted = $query->delete();
            
            Log::info('AI Cache: Invalidated cache by context', [
                'context_filter' => $contextFilter,
                'deleted_count' => $deleted
            ]);

            return $deleted;

        } catch (\Exception $e) {
            Log::error('AI Cache: Error invalidating cache by context', [
                'error' => $e->getMessage(),
                'context_filter' => $contextFilter
            ]);
            
            return 0;
        }
    }

    /**
     * Set similarity threshold
     */
    public function setSimilarityThreshold(float $threshold): void
    {
        $this->defaultSimilarityThreshold = max(0.0, min(1.0, $threshold));
    }

    /**
     * Set max cache age in days
     */
    public function setMaxCacheAge(int $days): void
    {
        $this->maxCacheAge = max(1, $days);
    }
}