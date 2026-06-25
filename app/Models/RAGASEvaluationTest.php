<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RAGASEvaluationTest extends Model
{
    protected $table = 'ragas_evaluation_tests';
    
    protected $fillable = [
        'cache_key',
        'question',
        'kategori',
        'expected_answer',
        'actual_answer',
        'retrieved_context',
        'faithfulness',
        'answer_relevancy',
        'context_precision',
        'context_recall',
        'context_relevancy',
        'hallucination_rate',
        'f1_score',
        'chunks_used',
        'avg_similarity',
        'ai_model',
        'response_time_ms',
        'status',
    ];

    protected $casts = [
        'faithfulness' => 'float',
        'answer_relevancy' => 'float',
        'context_precision' => 'float',
        'context_recall' => 'float',
        'context_relevancy' => 'float',
        'hallucination_rate' => 'float',
        'f1_score' => 'float',
        'avg_similarity' => 'float',
        'chunks_used' => 'integer',
        'response_time_ms' => 'integer',
    ];

    /**
     * Get RAGAS score (average of all metrics except hallucination)
     */
    public function getRAGASScoreAttribute()
    {
        return (
            $this->faithfulness +
            $this->answer_relevancy +
            $this->context_recall +
            $this->context_precision +
            $this->context_relevancy
        ) / 5;
    }

    /**
     * Scope for evaluated tests only
     */
    public function scopeEvaluated($query)
    {
        return $query->where('status', 'evaluated');
    }

    /**
     * Scope by kategori
     */
    public function scopeByKategori($query, $kategori)
    {
        return $query->where('kategori', $kategori);
    }
}
