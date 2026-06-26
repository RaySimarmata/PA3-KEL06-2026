<?php

namespace App\Jobs;

use App\Services\OCRService;
use App\Services\ChunkingService;
use App\Services\VectorDatabaseService;
use App\Services\EmbeddingService;
use App\Models\DocumentChunk;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessOCRAndIndexJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    protected $imagePaths;
    protected $sourceType;
    protected $sourceId;
    protected $metadata;

    /**
     * Create a new job instance.
     */
    public function __construct(array $imagePaths, string $sourceType, int $sourceId, array $metadata = [])
    {
        $this->imagePaths = $imagePaths;
        $this->sourceType = $sourceType; // 'laporan_triwulan', 'laporan_semester', etc.
        $this->sourceId = $sourceId;
        $this->metadata = $metadata;
    }

    /**
     * Execute the job.
     */
    public function handle(
        OCRService $ocrService,
        ChunkingService $chunkingService,
        EmbeddingService $embeddingService
    ): void
    {
        Log::info("=== OCR and Indexing Job Started ===", [
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'image_count' => count($this->imagePaths)
        ]);

        try {
            // 1. Extract text from all images using OCR
            $ocrResults = $ocrService->extractTextBatch($this->imagePaths);

            if (!$ocrResults['success']) {
                throw new \Exception("OCR extraction failed for all images");
            }

            Log::info("OCR extraction completed", [
                'successful' => $ocrResults['successful_count'],
                'failed' => $ocrResults['failed_count'],
                'total_text_length' => $ocrResults['total_length']
            ]);

            // 2. Chunk the combined text
            // Ensure combined_text is string before chunking
            $combinedText = $ocrResults['combined_text'] ?? '';
            if (is_array($combinedText)) {
                $combinedText = json_encode($combinedText);
            } elseif (!is_string($combinedText)) {
                $combinedText = (string)$combinedText;
            }
            
            $chunks = $chunkingService->chunkWithMetadata(
                $combinedText,
                array_merge($this->metadata, [
                    'source_type' => $this->sourceType,
                    'source_id' => $this->sourceId,
                    'ocr_image_count' => $ocrResults['successful_count'],
                ])
            );

            Log::info("Text chunked", [
                'chunk_count' => count($chunks)
            ]);

            // 3. Generate embeddings and store in vector database
            $storedCount = 0;
            foreach ($chunks as $chunk) {
                $embedding = $embeddingService->generateEmbedding($chunk['text']);

                if ($embedding) {
                    DocumentChunk::create([
                        'source_type' => $this->sourceType,
                        'source_id' => $this->sourceId,
                        'chunk_text' => $chunk['text'],
                        'chunk_index' => $chunk['index'],
                        'embedding' => $embedding,
                        'metadata' => $chunk['metadata'],
                    ]);
                    $storedCount++;
                }
            }

            Log::info("=== OCR and Indexing Job Completed ===", [
                'source_type' => $this->sourceType,
                'source_id' => $this->sourceId,
                'chunks_stored' => $storedCount
            ]);

        } catch (\Exception $e) {
            Log::error("OCR and Indexing Job failed", [
                'source_type' => $this->sourceType,
                'source_id' => $this->sourceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("OCR and Indexing Job permanently failed", [
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'error' => $exception->getMessage()
        ]);
    }
}
