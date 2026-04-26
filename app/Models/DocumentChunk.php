<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentChunk extends Model
{
    protected $fillable = [
        'template_id',
        'kuesioner_upload_id',
        'source_type',
        'source_id',
        'chunk_text',
        'chunk_index',
        'embedding',
        'metadata',
    ];

    protected $casts = [
        'embedding' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Relasi ke TemplateLaporan
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplateLaporan::class, 'template_id');
    }

    /**
     * Relasi ke KuesioneUpload
     */
    public function kuesioneUpload(): BelongsTo
    {
        return $this->belongsTo(KuesioneUpload::class, 'kuesioner_upload_id');
    }

    /**
     * Calculate cosine similarity with another embedding
     */
    public function cosineSimilarity(array $otherEmbedding): float
    {
        if (!$this->embedding || empty($this->embedding)) {
            return 0.0;
        }

        $embedding1 = $this->embedding;
        $embedding2 = $otherEmbedding;

        $dotProduct = 0.0;
        $magnitude1 = 0.0;
        $magnitude2 = 0.0;

        for ($i = 0; $i < count($embedding1); $i++) {
            $dotProduct += $embedding1[$i] * $embedding2[$i];
            $magnitude1 += $embedding1[$i] * $embedding1[$i];
            $magnitude2 += $embedding2[$i] * $embedding2[$i];
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0.0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }
}
