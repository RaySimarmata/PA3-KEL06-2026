<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class AIResponseCacheMongo extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';

    protected $collection = 'ai_response_cache';

    protected $fillable = [
        'cache_key',
        'prompt_hash',
        'original_prompt',
        'context_metadata',
        'ai_response',
        'ai_provider',
        'ai_model',
        'usage_count',
        'last_used_at',
        'response_length',
        'similarity_threshold',
    ];

    protected $casts = [
        'context_metadata' => 'array',
        'last_used_at' => 'datetime',
        'usage_count' => 'integer',
        'response_length' => 'integer',
        'similarity_threshold' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Generate cache key from prompt and context
     */
    public static function generateCacheKey(
        string $prompt,
        array $context = []
    ): string {

        $normalizedPrompt = strtolower(
            trim(
                preg_replace('/\s+/', ' ', $prompt)
            )
        );

        ksort($context);

        $combined = $normalizedPrompt . '|' . json_encode($context);

        return hash('sha256', $combined);
    }

    /**
     * Generate prompt hash
     */
    public static function generatePromptHash(
        string $prompt
    ): string {

        $normalized = strtolower(
            trim(
                preg_replace('/\s+/', ' ', $prompt)
            )
        );

        $stopWords = [
            'dan',
            'atau',
            'dengan',
            'untuk',
            'dari',
            'ke',
            'di',
            'pada',
            'dalam',
            'yang',
            'adalah',
            'akan',
            'telah',
            'sudah',
            'buat',
            'buatkan',
            'generate',
            'tolong',
            'silakan'
        ];

        $words = explode(' ', $normalized);

        $filteredWords = array_filter(
            $words,
            function ($word) use ($stopWords) {

                return !in_array($word, $stopWords)
                    && strlen($word) > 2;
            }
        );

        sort($filteredWords);

        return hash(
            'sha256',
            implode(' ', $filteredWords)
        );
    }

    /**
     * Calculate similarity between prompts
     */
    public static function calculateSimilarity(
        string $prompt1,
        string $prompt2
    ): float {

        $normalize = function ($text) {

            $text = strtolower(trim($text));

            $text = preg_replace('/[^\w\s]/', '', $text);

            $text = preg_replace('/\s+/', ' ', $text);

            return $text;
        };

        $norm1 = $normalize($prompt1);
        $norm2 = $normalize($prompt2);

        if ($norm1 === $norm2) {
            return 1.0;
        }

        $words1 = array_unique(explode(' ', $norm1));
        $words2 = array_unique(explode(' ', $norm2));

        $intersection = count(
            array_intersect($words1, $words2)
        );

        $union = count(
            array_unique(
                array_merge($words1, $words2)
            )
        );

        if ($union === 0) {
            return 0.0;
        }

        return $intersection / $union;
    }

    /**
     * Find similar cached response
     */
    public static function findSimilar(
        string $prompt,
        array $context = [],
        float $minSimilarity = 0.85
    ): ?self {

        $promptHash = self::generatePromptHash($prompt);

        // exact hash match
        $exactMatch = self::where('prompt_hash', $promptHash)
            ->where(
                'created_at',
                '>',
                Carbon::now()->subDays(30)
            )
            ->orderBy('usage_count', 'desc')
            ->orderBy('last_used_at', 'desc')
            ->first();

        if ($exactMatch) {
            return $exactMatch;
        }

        // similarity matching
        $candidates = self::where(
                'created_at',
                '>',
                Carbon::now()->subDays(30)
            )
            ->orderBy('usage_count', 'desc')
            ->limit(50)
            ->get();

        foreach ($candidates as $candidate) {

            $similarity = self::calculateSimilarity(
                $prompt,
                $candidate->original_prompt
            );

            if ($similarity >= $minSimilarity) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Increment usage
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');

        $this->update([
            'last_used_at' => now()
        ]);
    }

    /**
     * Clean old entries
     */
    public static function cleanOldEntries(
        int $daysToKeep = 90
    ): int {

        return self::where(
            'created_at',
            '<',
            Carbon::now()->subDays($daysToKeep)
        )->delete();
    }

    /**
     * Cache statistics
     */
    public static function getStatistics(): array
    {
        $total = self::count();

        $totalUsage = self::sum('usage_count');

        $avgUsage = $total > 0
            ? $totalUsage / $total
            : 0;

        $recentlyUsed = self::where(
            'last_used_at',
            '>',
            Carbon::now()->subDays(7)
        )->count();

        return [
            'total_entries' => $total,

            'total_usage' => $totalUsage,

            'average_usage_per_entry' => round(
                $avgUsage,
                2
            ),

            'recently_used_entries' => $recentlyUsed,

            'cache_hit_potential' => $totalUsage > 0
                ? round(
                    (($totalUsage - $total) / $totalUsage) * 100,
                    2
                )
                : 0,
        ];
    }
}