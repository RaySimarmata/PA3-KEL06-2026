<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmbeddingsCache extends Model
{
    protected $table = 'embeddings_cache';

    protected $fillable = [
        'text_hash',
        'embedding',
        'model',
    ];

    protected $casts = [
        'embedding' => 'array',
    ];

    /**
     * Get cached embedding by text
     */
    public static function getCached(string $text, string $model): ?array
    {
        $hash = hash('sha256', $text);
        $cache = self::where('text_hash', $hash)
            ->where('model', $model)
            ->first();

        return $cache ? $cache->embedding : null;
    }

    /**
     * Store embedding in cache
     */
    public static function store(string $text, array $embedding, string $model): void
    {
        $hash = hash('sha256', $text);
        
        self::updateOrCreate(
            ['text_hash' => $hash, 'model' => $model],
            ['embedding' => $embedding]
        );
    }
}
