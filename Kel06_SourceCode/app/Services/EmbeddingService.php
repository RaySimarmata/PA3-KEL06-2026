<?php

namespace App\Services;

use App\Models\EmbeddingsCache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    private $provider;
    private $model;
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        $this->provider = env('EMBEDDING_PROVIDER', 'local'); // 'openai', 'local', 'simple'
        $this->model = env('EMBEDDING_MODEL', 'bge-small-en'); // or 'intfloat/e5-base'
        $this->apiKey = env('EMBEDDING_API_KEY') ?: env('LLM_API_KEY');
        $this->baseUrl = env('EMBEDDING_BASE_URL', 'http://localhost:11434'); // Ollama default
    }

    /**
     * Generate embedding for text
     * Uses cache to avoid redundant API calls
     */
    public function generateEmbedding(string $text): ?array
    {
        // Check cache first
        $cached = EmbeddingsCache::getCached($text, $this->model);
        if ($cached) {
            Log::info("Embedding cache hit", ['text_length' => strlen($text)]);
            return $cached;
        }

        // Generate new embedding
        try {
            Log::info("Generating new embedding", [
                'provider' => $this->provider,
                'model' => $this->model,
                'text_length' => strlen($text)
            ]);

            $embedding = $this->callEmbeddingAPI($text);

            if ($embedding) {
                // Cache for future use
                EmbeddingsCache::store($text, $embedding, $this->model);
                return $embedding;
            }

            return null;

        } catch (\Exception $e) {
            Log::error("Embedding generation failed", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Generate embeddings for multiple texts (batch)
     */
    public function generateEmbeddings(array $texts): array
    {
        $embeddings = [];

        foreach ($texts as $index => $text) {
            $embedding = $this->generateEmbedding($text);
            if ($embedding) {
                $embeddings[$index] = $embedding;
            }
        }

        return $embeddings;
    }

    /**
     * Call embedding API
     */
    private function callEmbeddingAPI(string $text): ?array
    {
        try {
            return match($this->provider) {
                'openai' => $this->callOpenAI($text),
                'local' => $this->callLocalModel($text),
                'simple' => $this->generateSimpleEmbedding($text),
                default => $this->generateSimpleEmbedding($text),
            };
        } catch (\Exception $e) {
            Log::error("Embedding API call failed", [
                'provider' => $this->provider,
                'error' => $e->getMessage()
            ]);
            // Fallback to simple embedding
            return $this->generateSimpleEmbedding($text);
        }
    }

    /**
     * Call OpenAI Embeddings API
     */
    private function callOpenAI(string $text): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($this->baseUrl . '/embeddings', [
            'model' => $this->model,
            'input' => $text,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['data'][0]['embedding'] ?? null;
        }

        Log::error("OpenAI Embeddings API error", [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return null;
    }

    /**
     * Call Local Model (Ollama)
     * Supports: bge-small-en, intfloat/e5-base, nomic-embed-text
     */
    private function callLocalModel(string $text): ?array
    {
        try {
            Log::info("Calling local embedding model", [
                'model' => $this->model,
                'base_url' => $this->baseUrl
            ]);

            $response = Http::timeout(60)->post($this->baseUrl . '/api/embeddings', [
                'model' => $this->model,
                'prompt' => $text,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $embedding = $data['embedding'] ?? null;
                
                if ($embedding && is_array($embedding)) {
                    Log::info("Local embedding generated", [
                        'dimensions' => count($embedding)
                    ]);
                    return $embedding;
                }
            }

            Log::warning("Local model failed, falling back to simple embedding", [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 200)
            ]);

            return $this->generateSimpleEmbedding($text);

        } catch (\Exception $e) {
            Log::error("Local model error", [
                'error' => $e->getMessage()
            ]);
            return $this->generateSimpleEmbedding($text);
        }
    }

    /**
     * Generate simple embedding (fallback for testing)
     * Uses TF-IDF-like approach
     */
    private function generateSimpleEmbedding(string $text, int $dimensions = 384): array
    {
        // Simple hash-based embedding for testing
        // In production, use proper embedding model
        
        $words = str_word_count(strtolower($text), 1);
        $embedding = array_fill(0, $dimensions, 0.0);

        foreach ($words as $word) {
            $hash = crc32($word);
            $index = abs($hash) % $dimensions;
            $embedding[$index] += 1.0;
        }

        // Normalize
        $magnitude = sqrt(array_sum(array_map(function($x) { return $x * $x; }, $embedding)));
        if ($magnitude > 0) {
            $embedding = array_map(function($x) use ($magnitude) { 
                return $x / $magnitude; 
            }, $embedding);
        }

        return $embedding;
    }

    /**
     * Calculate cosine similarity between two embeddings
     */
    public function cosineSimilarity(array $embedding1, array $embedding2): float
    {
        if (count($embedding1) !== count($embedding2)) {
            return 0.0;
        }

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
