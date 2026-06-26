<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIEvaluationResult extends Model
{
    protected $table = 'ai_evaluation_results'; // Explicitly set table name
    
    protected $fillable = [
        'evaluation_name',
        'feature',
        'evaluation_date',
        'avg_wer',
        'total_ocr_tests',
        'avg_relevance',
        'avg_completeness',
        'avg_clarity',
        'avg_accuracy',
        'total_ai_tests',
        'hallucination_count',
        'avg_ambiguity',
        'high_ambiguity_count',
        'avg_ragas_faithfulness',
        'avg_ragas_answer_relevancy',
        'avg_ragas_context_precision',
        'avg_ragas_context_recall',
        'avg_ragas_context_relevancy',
        'avg_ragas_overall',
        'total_ragas_tests',
        'avg_precision',
        'total_retrieval_tests',
        'before_metrics',
        'after_metrics',
        'improvements',
        'summary',
        'strengths',
        'limitations',
        'recommendations',
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'avg_wer' => 'decimal:4',
        'avg_relevance' => 'decimal:2',
        'avg_completeness' => 'decimal:2',
        'avg_clarity' => 'decimal:2',
        'avg_accuracy' => 'decimal:2',
        'avg_ambiguity' => 'decimal:2',
        'avg_ragas_faithfulness' => 'decimal:4',
        'avg_ragas_answer_relevancy' => 'decimal:4',
        'avg_ragas_context_precision' => 'decimal:4',
        'avg_ragas_context_recall' => 'decimal:4',
        'avg_ragas_context_relevancy' => 'decimal:4',
        'avg_ragas_overall' => 'decimal:4',
        'avg_precision' => 'decimal:4',
        'before_metrics' => 'array',
        'after_metrics' => 'array',
        'improvements' => 'array',
    ];

    /**
     * Get overall quality score
     */
    public function getOverallQualityScore(): float
    {
        $scores = array_filter([
            $this->avg_relevance,
            $this->avg_completeness,
            $this->avg_clarity,
            $this->avg_accuracy,
        ]);

        return count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
    }

    /**
     * Get hallucination rate
     */
    public function getHallucinationRate(): float
    {
        if ($this->total_ai_tests === 0) {
            return 0.0;
        }

        return ($this->hallucination_count / $this->total_ai_tests) * 100;
    }

    /**
     * Scope for specific feature
     */
    public function scopeForFeature($query, $feature)
    {
        return $query->where('feature', $feature);
    }

    /**
     * Scope for recent evaluations
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('evaluation_date', '>=', now()->subDays($days));
    }

    /**
     * Get ambiguity rate
     */
    public function getAmbiguityRateAttribute(): float
    {
        if ($this->total_ai_tests === 0) {
            return 0.0;
        }

        return ($this->high_ambiguity_count / $this->total_ai_tests) * 100;
    }

    /**
     * Get RAGAS quality assessment
     */
    public function getRAGASQualityAssessmentAttribute(): string
    {
        if (!$this->avg_ragas_overall) return 'Not Evaluated';
        
        return match(true) {
            $this->avg_ragas_overall >= 0.8 => 'Excellent - RAG system performing very well',
            $this->avg_ragas_overall >= 0.6 => 'Good - RAG system performing adequately',
            $this->avg_ragas_overall >= 0.4 => 'Fair - RAG system needs improvement',
            default => 'Poor - RAG system requires significant optimization'
        };
    }

    /**
     * Get combined quality score (Human + RAGAS)
     */
    public function getCombinedQualityScoreAttribute(): float
    {
        $humanScore = $this->getOverallQualityScore(); // 0-5 scale
        $ragasScore = $this->avg_ragas_overall ?? 0; // 0-1 scale
        
        // Normalize to 0-100 scale
        $humanNormalized = ($humanScore / 5) * 100;
        $ragasNormalized = $ragasScore * 100;
        
        // Weighted average: 60% human, 40% RAGAS
        return ($humanNormalized * 0.6) + ($ragasNormalized * 0.4);
    }
}
