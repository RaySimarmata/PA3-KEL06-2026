<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIEvaluationTest extends Model
{
    protected $table = 'ai_evaluation_tests'; // Explicitly set table name
    
    protected $fillable = [
        'test_name',
        'test_type',
        'feature',
        'input_query',
        'input_image_path',
        'ground_truth',
        'actual_output',
        'ocr_result',
        'wer_score',
        'precision_score',
        'relevance_score',
        'completeness_score',
        'clarity_score',
        'accuracy_score',
        'has_hallucination',
        'ambiguity_score',
        'ambiguous_parts',
        'ambiguity_notes',
        'ragas_faithfulness',
        'ragas_answer_relevancy',
        'ragas_context_precision',
        'ragas_context_recall',
        'ragas_context_relevancy',
        'ragas_overall_score',
        'rag_context_used',
        'rag_chunks_count',
        'rag_avg_similarity',
        'notes',
        'evaluated_by',
        'evaluated_at',
        'status',
    ];

    protected $casts = [
        'has_hallucination' => 'boolean',
        'evaluated_at' => 'datetime',
        'wer_score' => 'decimal:4',
        'precision_score' => 'decimal:4',
        'ambiguous_parts' => 'array',
        'ragas_faithfulness' => 'decimal:4',
        'ragas_answer_relevancy' => 'decimal:4',
        'ragas_context_precision' => 'decimal:4',
        'ragas_context_recall' => 'decimal:4',
        'ragas_context_relevancy' => 'decimal:4',
        'ragas_overall_score' => 'decimal:4',
        'rag_context_used' => 'array',
        'rag_avg_similarity' => 'decimal:4',
    ];

    /**
     * Calculate Word Error Rate (WER) for OCR evaluation
     */
    public function calculateWER(): float
    {
        if (!$this->ground_truth || !$this->ocr_result) {
            return 0.0;
        }

        $reference = explode(' ', strtolower(trim($this->ground_truth)));
        $hypothesis = explode(' ', strtolower(trim($this->ocr_result)));

        $refCount = count($reference);
        if ($refCount === 0) {
            return 0.0;
        }

        // Simple Levenshtein distance for WER calculation
        $distance = levenshtein(
            implode(' ', $reference),
            implode(' ', $hypothesis)
        );

        $wer = $distance / $refCount;
        
        $this->wer_score = min(1.0, $wer); // Cap at 1.0
        $this->save();

        return $this->wer_score;
    }

    /**
     * Calculate average human evaluation score
     */
    public function getAverageHumanScore(): float
    {
        $scores = array_filter([
            $this->relevance_score,
            $this->completeness_score,
            $this->clarity_score,
            $this->accuracy_score,
        ]);

        return count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
    }

    /**
     * Scope for specific test type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('test_type', $type);
    }

    /**
     * Scope for specific feature
     */
    public function scopeForFeature($query, $feature)
    {
        return $query->where('feature', $feature);
    }

    /**
     * Scope for evaluated tests
     */
    public function scopeEvaluated($query)
    {
        return $query->where('status', 'evaluated');
    }

    /**
     * Calculate overall RAGAS score
     */
    public function calculateRAGASOverallScore(): float
    {
        $scores = array_filter([
            $this->ragas_faithfulness,
            $this->ragas_answer_relevancy,
            $this->ragas_context_precision,
            $this->ragas_context_recall,
            $this->ragas_context_relevancy,
        ]);

        if (count($scores) === 0) {
            return 0.0;
        }

        $overall = array_sum($scores) / count($scores);
        $this->ragas_overall_score = $overall;
        $this->save();

        return $overall;
    }

    /**
     * Check if response has high ambiguity
     */
    public function hasHighAmbiguity(): bool
    {
        return $this->ambiguity_score >= 4;
    }

    /**
     * Get ambiguity level label
     */
    public function getAmbiguityLevelAttribute(): string
    {
        if (!$this->ambiguity_score) return 'Not Evaluated';
        
        return match(true) {
            $this->ambiguity_score <= 2 => 'Clear',
            $this->ambiguity_score == 3 => 'Moderate',
            $this->ambiguity_score >= 4 => 'Ambiguous',
            default => 'Unknown'
        };
    }

    /**
     * Get RAGAS quality level
     */
    public function getRAGASQualityLevelAttribute(): string
    {
        if (!$this->ragas_overall_score) return 'Not Evaluated';
        
        return match(true) {
            $this->ragas_overall_score >= 0.8 => 'Excellent',
            $this->ragas_overall_score >= 0.6 => 'Good',
            $this->ragas_overall_score >= 0.4 => 'Fair',
            default => 'Poor'
        };
    }
}
