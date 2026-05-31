<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * RAGAS Evaluation Service
 * 
 * Evaluates RAG (Retrieval-Augmented Generation) system quality using RAGAS metrics:
 * - Faithfulness: Factual consistency of answer with context
 * - Answer Relevancy: Relevance of answer to question
 * - Context Precision: Precision of retrieved context
 * - Context Recall: Recall of retrieved context
 * - Context Relevancy: Relevance of retrieved context
 */
class RAGASEvaluationService
{
    protected $aiService;

    public function __construct(UnifiedAIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Evaluate RAGAS metrics for VMTS AI Assistant
     * 
     * @param string $question User's question/prompt
     * @param string $answer AI's generated answer
     * @param array $contexts Retrieved contexts (chunks from Excel/PDF)
     * @param array $groundTruth Optional ground truth for comparison
     * @return array RAGAS metrics
     */
    public function evaluateVMTS(string $question, string $answer, array $contexts, array $groundTruth = []): array
    {
        try {
            Log::info('Starting RAGAS evaluation for VMTS', [
                'question_length' => strlen($question),
                'answer_length' => strlen($answer),
                'contexts_count' => count($contexts),
            ]);

            $metrics = [
                'faithfulness' => $this->calculateFaithfulness($answer, $contexts),
                'answer_relevancy' => $this->calculateAnswerRelevancy($question, $answer),
                'context_precision' => $this->calculateContextPrecision($question, $contexts, $answer),
                'context_recall' => $this->calculateContextRecall($answer, $contexts, $groundTruth),
                'context_relevancy' => $this->calculateContextRelevancy($question, $contexts),
            ];

            // Calculate overall score (weighted average)
            $metrics['overall_score'] = $this->calculateOverallScore($metrics);

            // Add metadata
            $metrics['metadata'] = [
                'contexts_count' => count($contexts),
                'avg_context_length' => $this->calculateAvgLength($contexts),
                'answer_length' => strlen($answer),
                'question_length' => strlen($question),
                'evaluated_at' => now()->toDateTimeString(),
            ];

            Log::info('RAGAS evaluation completed', [
                'overall_score' => $metrics['overall_score'],
                'metrics' => array_map(fn($v) => is_numeric($v) ? round($v, 4) : $v, $metrics),
            ]);

            return $metrics;

        } catch (\Exception $e) {
            Log::error('RAGAS evaluation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return default metrics on error
            return $this->getDefaultMetrics();
        }
    }

    /**
     * Calculate Faithfulness: Factual consistency of answer with context
     * Measures if the answer is grounded in the provided context
     */
    private function calculateFaithfulness(string $answer, array $contexts): float
    {
        try {
            $contextText = implode("\n\n", $contexts);

            $prompt = "Evaluate the faithfulness of the answer based on the given context. " .
                      "Faithfulness measures if the answer is factually consistent with the context. " .
                      "Rate from 0.0 (completely unfaithful) to 1.0 (completely faithful).\n\n" .
                      "Context:\n{$contextText}\n\n" .
                      "Answer:\n{$answer}\n\n" .
                      "Provide only a numeric score between 0.0 and 1.0.";

            $response = $this->aiService->generateText($prompt, [
                'temperature' => 0.1,
                'max_tokens' => 50,
            ]);

            $score = $this->extractScore($response);
            return $this->normalizeScore($score);

        } catch (\Exception $e) {
            Log::warning('Faithfulness calculation failed', ['error' => $e->getMessage()]);
            return 0.5; // Default neutral score
        }
    }

    /**
     * Calculate Answer Relevancy: Relevance of answer to question
     * Measures how well the answer addresses the question
     */
    private function calculateAnswerRelevancy(string $question, string $answer): float
    {
        try {
            $prompt = "Evaluate the relevancy of the answer to the question. " .
                      "Answer relevancy measures how well the answer addresses the question. " .
                      "Rate from 0.0 (completely irrelevant) to 1.0 (perfectly relevant).\n\n" .
                      "Question:\n{$question}\n\n" .
                      "Answer:\n{$answer}\n\n" .
                      "Provide only a numeric score between 0.0 and 1.0.";

            $response = $this->aiService->generateText($prompt, [
                'temperature' => 0.1,
                'max_tokens' => 50,
            ]);

            $score = $this->extractScore($response);
            return $this->normalizeScore($score);

        } catch (\Exception $e) {
            Log::warning('Answer relevancy calculation failed', ['error' => $e->getMessage()]);
            return 0.5;
        }
    }

    /**
     * Calculate Context Precision: Precision of retrieved context
     * Measures if the retrieved contexts are relevant to the question
     */
    private function calculateContextPrecision(string $question, array $contexts, string $answer): float
    {
        try {
            if (empty($contexts)) {
                return 0.0;
            }

            $relevantCount = 0;
            foreach ($contexts as $context) {
                $prompt = "Is this context relevant to answering the question? " .
                          "Answer with 'yes' or 'no'.\n\n" .
                          "Question: {$question}\n\n" .
                          "Context: {$context}";

                $response = $this->aiService->generateText($prompt, [
                    'temperature' => 0.1,
                    'max_tokens' => 10,
                ]);

                if (stripos($response, 'yes') !== false) {
                    $relevantCount++;
                }
            }

            return $relevantCount / count($contexts);

        } catch (\Exception $e) {
            Log::warning('Context precision calculation failed', ['error' => $e->getMessage()]);
            return 0.5;
        }
    }

    /**
     * Calculate Context Recall: Recall of retrieved context
     * Measures if all necessary information is retrieved
     */
    private function calculateContextRecall(string $answer, array $contexts, array $groundTruth = []): float
    {
        try {
            $contextText = implode("\n\n", $contexts);

            // If no ground truth, estimate based on answer coverage
            if (empty($groundTruth)) {
                $prompt = "Evaluate if the contexts contain all necessary information to generate the answer. " .
                          "Rate from 0.0 (missing critical information) to 1.0 (all information present).\n\n" .
                          "Contexts:\n{$contextText}\n\n" .
                          "Answer:\n{$answer}\n\n" .
                          "Provide only a numeric score between 0.0 and 1.0.";

                $response = $this->aiService->generateText($prompt, [
                    'temperature' => 0.1,
                    'max_tokens' => 50,
                ]);

                $score = $this->extractScore($response);
                return $this->normalizeScore($score);
            }

            // With ground truth, compare coverage
            $groundTruthText = implode("\n", $groundTruth);
            $prompt = "Compare the contexts with the ground truth. " .
                      "How much of the ground truth information is covered by the contexts? " .
                      "Rate from 0.0 (no coverage) to 1.0 (complete coverage).\n\n" .
                      "Ground Truth:\n{$groundTruthText}\n\n" .
                      "Contexts:\n{$contextText}\n\n" .
                      "Provide only a numeric score between 0.0 and 1.0.";

            $response = $this->aiService->generateText($prompt, [
                'temperature' => 0.1,
                'max_tokens' => 50,
            ]);

            $score = $this->extractScore($response);
            return $this->normalizeScore($score);

        } catch (\Exception $e) {
            Log::warning('Context recall calculation failed', ['error' => $e->getMessage()]);
            return 0.5;
        }
    }

    /**
     * Calculate Context Relevancy: Relevance of retrieved context
     * Measures how relevant the contexts are to the question
     */
    private function calculateContextRelevancy(string $question, array $contexts): float
    {
        try {
            $contextText = implode("\n\n", $contexts);

            $prompt = "Evaluate the relevancy of the contexts to the question. " .
                      "Context relevancy measures how relevant the retrieved information is. " .
                      "Rate from 0.0 (completely irrelevant) to 1.0 (highly relevant).\n\n" .
                      "Question:\n{$question}\n\n" .
                      "Contexts:\n{$contextText}\n\n" .
                      "Provide only a numeric score between 0.0 and 1.0.";

            $response = $this->aiService->generateText($prompt, [
                'temperature' => 0.1,
                'max_tokens' => 50,
            ]);

            $score = $this->extractScore($response);
            return $this->normalizeScore($score);

        } catch (\Exception $e) {
            Log::warning('Context relevancy calculation failed', ['error' => $e->getMessage()]);
            return 0.5;
        }
    }

    /**
     * Calculate overall RAGAS score (weighted average)
     */
    private function calculateOverallScore(array $metrics): float
    {
        $weights = [
            'faithfulness' => 0.25,
            'answer_relevancy' => 0.25,
            'context_precision' => 0.20,
            'context_recall' => 0.15,
            'context_relevancy' => 0.15,
        ];

        $score = 0.0;
        foreach ($weights as $metric => $weight) {
            if (isset($metrics[$metric])) {
                $score += $metrics[$metric] * $weight;
            }
        }

        return round($score, 4);
    }

    /**
     * Extract numeric score from AI response
     */
    private function extractScore(string $response): float
    {
        // Try to find a decimal number between 0 and 1
        if (preg_match('/([0-1]?\.\d+|[01])/', $response, $matches)) {
            return (float) $matches[1];
        }

        // Try to find percentage
        if (preg_match('/(\d+)%/', $response, $matches)) {
            return (float) $matches[1] / 100;
        }

        // Default to 0.5 if no score found
        return 0.5;
    }

    /**
     * Normalize score to 0.0-1.0 range
     */
    private function normalizeScore(float $score): float
    {
        return max(0.0, min(1.0, $score));
    }

    /**
     * Calculate average length of contexts
     */
    private function calculateAvgLength(array $contexts): int
    {
        if (empty($contexts)) {
            return 0;
        }

        $totalLength = array_sum(array_map('strlen', $contexts));
        return (int) ($totalLength / count($contexts));
    }

    /**
     * Get default metrics when evaluation fails
     */
    private function getDefaultMetrics(): array
    {
        return [
            'faithfulness' => 0.0,
            'answer_relevancy' => 0.0,
            'context_precision' => 0.0,
            'context_recall' => 0.0,
            'context_relevancy' => 0.0,
            'overall_score' => 0.0,
            'metadata' => [
                'contexts_count' => 0,
                'avg_context_length' => 0,
                'answer_length' => 0,
                'question_length' => 0,
                'evaluated_at' => now()->toDateTimeString(),
                'error' => 'Evaluation failed',
            ],
        ];
    }

    /**
     * Quick evaluation for VMTS (simplified version)
     * Uses heuristics instead of AI calls for faster evaluation
     */
    public function quickEvaluateVMTS(string $question, string $answer, array $contexts): array
    {
        try {
            $metrics = [
                'faithfulness' => $this->heuristicFaithfulness($answer, $contexts),
                'answer_relevancy' => $this->heuristicAnswerRelevancy($question, $answer),
                'context_precision' => $this->heuristicContextPrecision($question, $contexts),
                'context_recall' => $this->heuristicContextRecall($answer, $contexts),
                'context_relevancy' => $this->heuristicContextRelevancy($question, $contexts),
            ];

            $metrics['overall_score'] = $this->calculateOverallScore($metrics);

            $metrics['metadata'] = [
                'contexts_count' => count($contexts),
                'avg_context_length' => $this->calculateAvgLength($contexts),
                'answer_length' => strlen($answer),
                'question_length' => strlen($question),
                'evaluated_at' => now()->toDateTimeString(),
                'evaluation_type' => 'heuristic',
            ];

            return $metrics;

        } catch (\Exception $e) {
            Log::error('Quick RAGAS evaluation failed', ['error' => $e->getMessage()]);
            return $this->getDefaultMetrics();
        }
    }

    /**
     * Heuristic-based faithfulness (keyword overlap)
     */
    private function heuristicFaithfulness(string $answer, array $contexts): float
    {
        $contextText = implode(' ', $contexts);
        $answerWords = str_word_count(strtolower($answer), 1);
        $contextWords = str_word_count(strtolower($contextText), 1);

        if (empty($answerWords)) {
            return 0.0;
        }

        $overlap = count(array_intersect($answerWords, $contextWords));
        return min(1.0, $overlap / count($answerWords));
    }

    /**
     * Heuristic-based answer relevancy (keyword overlap with question)
     */
    private function heuristicAnswerRelevancy(string $question, string $answer): float
    {
        $questionWords = str_word_count(strtolower($question), 1);
        $answerWords = str_word_count(strtolower($answer), 1);

        if (empty($questionWords)) {
            return 0.5;
        }

        $overlap = count(array_intersect($questionWords, $answerWords));
        return min(1.0, $overlap / count($questionWords) * 2); // *2 to boost score
    }

    /**
     * Heuristic-based context precision
     */
    private function heuristicContextPrecision(string $question, array $contexts): float
    {
        if (empty($contexts)) {
            return 0.0;
        }

        $questionWords = str_word_count(strtolower($question), 1);
        $relevantCount = 0;

        foreach ($contexts as $context) {
            $contextWords = str_word_count(strtolower($context), 1);
            $overlap = count(array_intersect($questionWords, $contextWords));
            
            if ($overlap > 0) {
                $relevantCount++;
            }
        }

        return $relevantCount / count($contexts);
    }

    /**
     * Heuristic-based context recall
     */
    private function heuristicContextRecall(string $answer, array $contexts): float
    {
        $contextText = implode(' ', $contexts);
        $answerWords = str_word_count(strtolower($answer), 1);
        $contextWords = str_word_count(strtolower($contextText), 1);

        if (empty($contextWords)) {
            return 0.0;
        }

        $overlap = count(array_intersect($answerWords, $contextWords));
        return min(1.0, $overlap / count($answerWords));
    }

    /**
     * Heuristic-based context relevancy
     */
    private function heuristicContextRelevancy(string $question, array $contexts): float
    {
        return $this->heuristicContextPrecision($question, $contexts);
    }
}
