<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class RAGRetrievalService
{
    private $vectorDbService;
    private $topK;
    private $similarityThreshold;

    public function __construct(VectorDatabaseService $vectorDbService)
    {
        $this->vectorDbService = $vectorDbService;
        $this->topK = env('RAG_TOP_K', 10);
        $this->similarityThreshold = env('RAG_SIMILARITY_THRESHOLD', 0.3);
    }

    /**
     * Retrieve relevant context for a query
     */
    public function retrieveContext(string $query, array $filters = []): array
    {
        Log::info("=== RAG Retrieval ===", [
            'query' => substr($query, 0, 100),
            'filters' => $filters
        ]);

        // Search vector database
        $results = $this->vectorDbService->search($query, $this->topK, $filters);

        // Filter by similarity threshold
        $relevantResults = array_filter($results, function($result) {
            return $result['similarity'] >= $this->similarityThreshold;
        });

        Log::info("Context retrieved", [
            'total_results' => count($results),
            'relevant_results' => count($relevantResults),
            'avg_similarity' => count($relevantResults) > 0 
                ? array_sum(array_column($relevantResults, 'similarity')) / count($relevantResults)
                : 0
        ]);

        return [
            'results' => $relevantResults,
            'context_text' => $this->buildContextText($relevantResults),
            'metadata' => $this->extractMetadata($relevantResults),
        ];
    }

    /**
     * Build context text from results
     */
    private function buildContextText(array $results): string
    {
        if (empty($results)) {
            return '';
        }

        $contextParts = [];

        foreach ($results as $index => $result) {
            $similarity = round($result['similarity'] * 100, 1);
            $contextParts[] = "--- Context {$index} (Relevance: {$similarity}%) ---\n{$result['text']}\n";
        }

        return implode("\n", $contextParts);
    }

    /**
     * Extract metadata from results
     */
    private function extractMetadata(array $results): array
    {
        $metadata = [
            'total_chunks' => count($results),
            'avg_similarity' => 0,
            'sources' => [],
        ];

        if (empty($results)) {
            return $metadata;
        }

        // Calculate average similarity
        $metadata['avg_similarity'] = array_sum(array_column($results, 'similarity')) / count($results);

        // Extract unique sources
        $sources = [];
        foreach ($results as $result) {
            if (isset($result['metadata']['kuesioner_id'])) {
                $sources[$result['metadata']['kuesioner_id']] = true;
            }
        }
        $metadata['sources'] = array_keys($sources);
        $metadata['num_sources'] = count($sources);

        return $metadata;
    }

    /**
     * Retrieve and rank context
     */
    public function retrieveAndRank(string $query, array $filters = [], array $rankingWeights = []): array
    {
        // Get initial results
        $retrieval = $this->retrieveContext($query, $filters);

        // Apply additional ranking if needed
        if (!empty($rankingWeights)) {
            $retrieval['results'] = $this->rerank($retrieval['results'], $rankingWeights);
            $retrieval['context_text'] = $this->buildContextText($retrieval['results']);
        }

        return $retrieval;
    }

    /**
     * Re-rank results based on additional criteria
     */
    private function rerank(array $results, array $weights): array
    {
        // Apply weights to similarity scores
        foreach ($results as &$result) {
            $score = $result['similarity'];

            // Boost recent documents
            if (isset($weights['recency']) && isset($result['metadata']['created_at'])) {
                $recencyBoost = $this->calculateRecencyBoost($result['metadata']['created_at']);
                $score += $weights['recency'] * $recencyBoost;
            }

            // Boost by chunk position (earlier chunks might be more important)
            if (isset($weights['position']) && isset($result['metadata']['chunk_index'])) {
                $positionBoost = 1.0 / (1.0 + $result['metadata']['chunk_index'] * 0.1);
                $score += $weights['position'] * $positionBoost;
            }

            $result['final_score'] = $score;
        }

        // Re-sort by final score
        usort($results, function($a, $b) {
            return $b['final_score'] <=> $a['final_score'];
        });

        return $results;
    }

    /**
     * Calculate recency boost (newer = higher)
     */
    private function calculateRecencyBoost(string $createdAt): float
    {
        $created = strtotime($createdAt);
        $now = time();
        $daysDiff = ($now - $created) / (60 * 60 * 24);

        // Exponential decay: newer documents get higher boost
        return exp(-$daysDiff / 30); // Decay over 30 days
    }
}
