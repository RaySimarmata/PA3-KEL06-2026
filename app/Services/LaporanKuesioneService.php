<?php

namespace App\Services;

use App\Models\TemplateLaporan;
use App\Models\DocumentChunk;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LaporanKuesioneService
{
    protected $vectorDatabaseService;
    protected $embeddingService;
    protected $documentStructureService;
    protected $textExtractionService;

    public function __construct(
        VectorDatabaseService $vectorDatabaseService,
        EmbeddingService $embeddingService,
        DocumentStructureService $documentStructureService,
        TextExtractionService $textExtractionService
    ) {
        $this->vectorDatabaseService = $vectorDatabaseService;
        $this->embeddingService = $embeddingService;
        $this->documentStructureService = $documentStructureService;
        $this->textExtractionService = $textExtractionService;
    }

    /**
     * Get active template by jenis
     */
    public function getActiveTemplate($jenis)
    {
        return TemplateLaporan::where('jenis', $jenis)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Process template to vector database
     */
    public function processTemplateToVectorDB($templateId)
    {
        try {
            $template = TemplateLaporan::findOrFail($templateId);

            // Get file path
            $filePath = $template->file_path;

            Log::info("Processing template to vector DB", [
                'template_id' => $templateId,
                'file_path' => $filePath
            ]);

            // Extract text from file
            $extractionResult = $this->textExtractionService->extractFromFile($filePath);

            if (!$extractionResult['success'] || empty($extractionResult['text'])) {
                throw new \Exception("Failed to extract text from template: " . ($extractionResult['metadata']['error'] ?? 'Unknown error'));
            }

            $content = $extractionResult['text'];

            Log::info("Text extracted successfully", [
                'content_length' => strlen($content),
                'extraction_method' => $extractionResult['metadata']['extraction_method'] ?? 'unknown'
            ]);

            // Extract document structure
            $structure = $this->documentStructureService->extractStructure($content);

            // Process chunks and embeddings (this also stores them in the database)
            $chunks = $this->createChunks($template, $structure);

            // Mark template as processed and indexed
            $template->update([
                'is_processed' => true,
                'is_indexed' => true,
                'indexed_at' => now(),
                'total_chunks' => count($chunks),
                'structure_metadata' => [
                    'sections_count' => count($structure['sections'] ?? []),
                    'has_formatting' => !empty($structure['formatting']),
                    'patterns' => $structure['patterns'] ?? [],
                    'metadata' => $structure['metadata'] ?? [],
                ]
            ]);

            Log::info("Template {$templateId} processed successfully to vector database", [
                'chunks_count' => count($chunks)
            ]);

            return [
                'success' => true,
                'message' => 'Template processed successfully',
                'chunks_count' => count($chunks)
            ];
        } catch (\Exception $e) {
            Log::error("Error processing template to vector DB: " . $e->getMessage(), [
                'template_id' => $templateId,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Create chunks from template structure
     */
    private function createChunks($template, $structure)
    {
        $chunks = [];

        // Extract sections from structure
        $sections = $structure['sections'] ?? [];

        // If no sections found, create a single chunk from the whole structure
        if (empty($sections)) {
            Log::warning("No sections found in structure, creating single chunk");
            
            // Convert structure to text content
            $content = json_encode($structure, JSON_PRETTY_PRINT);
            
            $embedding = $this->embeddingService->generateEmbedding($content);

            $chunk = DocumentChunk::create([
                'template_id' => $template->id,
                'kuesioner_upload_id' => null,
                'chunk_text' => $content,
                'chunk_index' => 0,
                'embedding' => $embedding,
                'metadata' => [
                    'template_id' => $template->id,
                    'section' => 'full_document',
                    'section_title' => 'Full Document',
                    'structure' => $structure,
                ]
            ]);

            $chunks[] = [
                'id' => $chunk->id,
                'embedding' => $embedding,
                'metadata' => [
                    'template_id' => $template->id,
                    'chunk_id' => $chunk->id,
                    'section' => 'full_document',
                ]
            ];

            return $chunks;
        }

        // Process each section
        foreach ($sections as $section) {
            // Skip sections without content
            if (empty($section['content'])) {
                Log::info("Skipping section without content", ['title' => $section['title'] ?? 'unknown']);
                continue;
            }

            // Generate embedding for section
            $embedding = $this->embeddingService->generateEmbedding($section['content']);

            $chunk = DocumentChunk::create([
                'template_id' => $template->id,
                'kuesioner_upload_id' => null,
                'chunk_text' => $section['content'],
                'chunk_index' => $section['index'] ?? 0,
                'embedding' => $embedding,
                'metadata' => [
                    'template_id' => $template->id,
                    'section' => $section['title'] ?? 'main',
                    'section_title' => $section['title'] ?? null,
                    'line_start' => $section['line_start'] ?? null,
                    'line_end' => $section['line_end'] ?? null,
                ]
            ]);

            $chunks[] = [
                'id' => $chunk->id,
                'embedding' => $embedding,
                'metadata' => [
                    'template_id' => $template->id,
                    'chunk_id' => $chunk->id,
                    'section' => $section['title'] ?? 'main',
                ]
            ];
        }

        return $chunks;
    }
}
