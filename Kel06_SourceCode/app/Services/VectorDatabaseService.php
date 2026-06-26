<?php

namespace App\Services;

use App\Models\DocumentChunk;
use App\Models\KuesioneUpload;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class VectorDatabaseService
{
    private $embeddingService;
    private $chunkingService;

    public function __construct(
        EmbeddingService $embeddingService,
        ChunkingService $chunkingService
    ) {
        $this->embeddingService = $embeddingService;
        $this->chunkingService = $chunkingService;
    }

    /**
     * Index a generic document (for OCR results, uploaded files, etc.)
     * 
     * @param array $documentData Array with keys: text, source_type, source_id, metadata
     * @return bool Success status
     */
    public function indexDocument(array $documentData): bool
    {
        try {
            Log::info("=== Indexing Document ===", [
                'source_type' => $documentData['source_type'] ?? 'unknown',
                'source_id' => $documentData['source_id'] ?? null,
                'text_length' => safe_strlen($documentData['text'] ?? '')
            ]);

            $text = $documentData['text'] ?? '';
            if (empty($text)) {
                Log::warning("No text provided for indexing");
                return false;
            }

            // Prepare metadata
            $sourceType = $documentData['source_type'] ?? 'generic';
            $sourceId = $documentData['source_id'] ?? null;
            $chunkIndex = $documentData['chunk_index'] ?? 0;
            
            $metadata = array_merge(
                $documentData['metadata'] ?? [],
                [
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'indexed_at' => now()->toIso8601String(),
                ]
            );

            // Generate embedding for the text
            $embedding = $this->embeddingService->generateEmbedding($text);

            if (!$embedding) {
                Log::warning("Failed to generate embedding for document", [
                    'source_type' => $sourceType,
                    'source_id' => $sourceId
                ]);
                return false;
            }

            // Store the document chunk
            DocumentChunk::create([
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'kuesioner_upload_id' => null, // Generic document, not tied to kuesioner
                'chunk_text' => $text,
                'chunk_index' => $chunkIndex,
                'embedding' => $embedding,
                'metadata' => $metadata,
            ]);

            Log::info("Document indexed successfully", [
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'chunk_index' => $chunkIndex,
                'text_length' => strlen($text)
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Document indexing failed", [
                'source_type' => $documentData['source_type'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Index a kuesioner (create chunks and embeddings)
     */
    public function indexKuesioner(KuesioneUpload $kuesioner): bool
    {
        try {
            Log::info("=== Indexing Kuesioner ===", [
                'kuesioner_id' => $kuesioner->id,
                'nama_file' => $kuesioner->nama_file
            ]);

            // Extract text from kuesioner
            $text = $this->extractTextFromKuesioner($kuesioner);

            if (empty($text)) {
                Log::warning("No text extracted from kuesioner", [
                    'kuesioner_id' => $kuesioner->id
                ]);
                return false;
            }

            // Chunk the text
            $chunks = $this->chunkingService->chunkWithMetadata($text, [
                'kuesioner_id' => $kuesioner->id,
                'periode' => $kuesioner->periode,
                'prodi_id' => $kuesioner->user->prodi_id ?? null,
            ]);

            Log::info("Text chunked", [
                'num_chunks' => count($chunks)
            ]);

            // Generate embeddings and store
            foreach ($chunks as $chunk) {
                $embedding = $this->embeddingService->generateEmbedding($chunk['text']);

                if ($embedding) {
                    DocumentChunk::create([
                        'kuesioner_upload_id' => $kuesioner->id,
                        'chunk_text' => $chunk['text'],
                        'chunk_index' => $chunk['index'],
                        'embedding' => $embedding,
                        'metadata' => $chunk['metadata'],
                    ]);
                }
            }

            Log::info("Kuesioner indexed successfully", [
                'kuesioner_id' => $kuesioner->id,
                'chunks_created' => count($chunks)
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Indexing failed", [
                'kuesioner_id' => $kuesioner->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Search for similar chunks
     */
    public function search(string $query, int $topK = 10, array $filters = []): array
    {
        try {
            Log::info("=== Vector Search ===", [
                'query' => substr($query, 0, 100),
                'top_k' => $topK,
                'filters' => $filters
            ]);

            // Generate query embedding
            $queryEmbedding = $this->embeddingService->generateEmbedding($query);

            if (!$queryEmbedding) {
                Log::error("Failed to generate query embedding");
                return [];
            }

            // Get all chunks (with filters)
            $chunksQuery = DocumentChunk::with('kuesioneUpload');

            // Apply filters
            if (isset($filters['source_type'])) {
                $chunksQuery->where('source_type', $filters['source_type']);
            }

            if (isset($filters['source_id'])) {
                $chunksQuery->where('source_id', $filters['source_id']);
            }

            if (isset($filters['periode'])) {
                $chunksQuery->whereHas('kuesioneUpload', function($q) use ($filters) {
                    $q->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$filters['periode']]);
                });
            }

            if (isset($filters['prodi_id'])) {
                $chunksQuery->whereHas('kuesioneUpload.user', function($q) use ($filters) {
                    $q->where('prodi_id', $filters['prodi_id']);
                });
            }

            $chunks = $chunksQuery->get();

            Log::info("Chunks retrieved for search", [
                'total_chunks' => $chunks->count()
            ]);

            // Calculate similarities
            $results = [];
            foreach ($chunks as $chunk) {
                if ($chunk->embedding) {
                    $similarity = $this->embeddingService->cosineSimilarity(
                        $queryEmbedding,
                        $chunk->embedding
                    );

                    $results[] = [
                        'chunk' => $chunk,
                        'similarity' => $similarity,
                        'text' => $chunk->chunk_text,
                        'metadata' => $chunk->metadata,
                    ];
                }
            }

            // Sort by similarity (descending)
            usort($results, function($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });

            // Return top K
            $topResults = array_slice($results, 0, $topK);

            Log::info("Search completed", [
                'results_found' => count($results),
                'top_k_returned' => count($topResults),
                'top_similarity' => $topResults[0]['similarity'] ?? 0
            ]);

            return $topResults;

        } catch (\Exception $e) {
            Log::error("Vector search failed", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Extract text from kuesioner
     */
    private function extractTextFromKuesioner(KuesioneUpload $kuesioner): string
    {
        $text = '';

        // Add basic info
        $text .= "Kuesioner: {$kuesioner->nama_file}\n";
        $text .= "Periode: {$kuesioner->periode}\n\n";

        // Add hasil analisis if available
        if ($kuesioner->hasil_analisis) {
            $hasil = $kuesioner->hasil_analisis;

            if (isset($hasil['ringkasan'])) {
                $text .= "Ringkasan: {$hasil['ringkasan']}\n\n";
            }

            if (isset($hasil['poin_positif']) && is_array($hasil['poin_positif'])) {
                $text .= "Poin Positif:\n";
                foreach ($hasil['poin_positif'] as $poin) {
                    $text .= "- {$poin}\n";
                }
                $text .= "\n";
            }

            if (isset($hasil['area_perbaikan']) && is_array($hasil['area_perbaikan'])) {
                $text .= "Area Perbaikan:\n";
                foreach ($hasil['area_perbaikan'] as $area) {
                    $text .= "- {$area}\n";
                }
                $text .= "\n";
            }

            if (isset($hasil['rekomendasi']) && is_array($hasil['rekomendasi'])) {
                $text .= "Rekomendasi:\n";
                foreach ($hasil['rekomendasi'] as $rekom) {
                    $text .= "- {$rekom}\n";
                }
                $text .= "\n";
            }

            // Add statistik per pertanyaan
            if (isset($hasil['statistik_per_pertanyaan']) && is_array($hasil['statistik_per_pertanyaan'])) {
                $text .= "Statistik Per Pertanyaan:\n";
                foreach ($hasil['statistik_per_pertanyaan'] as $stat) {
                    if (isset($stat['pertanyaan'])) {
                        $text .= "Q: {$stat['pertanyaan']}\n";
                        $text .= "Rata-rata: {$stat['rata_rata']}\n\n";
                    }
                }
            }
        }

        return $text;
    }

    /**
     * Delete chunks for a kuesioner
     */
    public function deleteKuesioneIndex(int $kuesioneId): bool
    {
        try {
            DocumentChunk::where('kuesioner_upload_id', $kuesioneId)->delete();
            Log::info("Kuesioner index deleted", ['kuesioner_id' => $kuesioneId]);
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to delete kuesioner index", [
                'kuesioner_id' => $kuesioneId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Re-index all kuesioner for a periode
     */
    public function reindexPeriode(string $periode, ?int $prodiId = null): int
    {
        $query = KuesioneUpload::where('status', 'completed')
            ->whereNotNull('hasil_analisis')
            ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$periode]);

        if ($prodiId) {
            $query->whereHas('user', function($q) use ($prodiId) {
                $q->where('prodi_id', $prodiId);
            });
        }

        $kuesioneList = $query->get();
        $indexed = 0;

        foreach ($kuesioneList as $kuesioner) {
            // Delete existing chunks
            $this->deleteKuesioneIndex($kuesioner->id);

            // Re-index
            if ($this->indexKuesioner($kuesioner)) {
                $indexed++;
            }
        }

        Log::info("Periode re-indexed", [
            'periode' => $periode,
            'total' => $kuesioneList->count(),
            'indexed' => $indexed
        ]);

        return $indexed;
    }
}
