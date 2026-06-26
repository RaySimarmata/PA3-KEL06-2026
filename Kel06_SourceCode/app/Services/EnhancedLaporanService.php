<?php

namespace App\Services;

use App\Models\LaporanGJM;
use App\Models\DocumentChunk;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Enhanced Laporan Service
 * 
 * Service yang mengintegrasikan OCR + AI untuk Asisten Pembuatan Laporan
 * Menggabungkan data OCR dari gambar dengan AI preview untuk generate laporan
 */
class EnhancedLaporanService
{
    protected $ocrService;
    protected $aiService;
    protected $chunkingService;
    protected $vectorDbService;
    protected $ragService;
    protected $aiPreviewCacheService;

    public function __construct(
        OCRService $ocrService,
        UnifiedAIService $aiService,
        ChunkingService $chunkingService,
        VectorDatabaseService $vectorDbService,
        RAGRetrievalService $ragService,
        AIPreviewCacheService $aiPreviewCacheService
    ) {
        $this->ocrService = $ocrService;
        $this->aiService = $aiService;
        $this->chunkingService = $chunkingService;
        $this->vectorDbService = $vectorDbService;
        $this->ragService = $ragService;
        $this->aiPreviewCacheService = $aiPreviewCacheService;
    }

    /**
     * Process images dengan OCR dan integrate dengan AI preview
     * 
     * @param int $laporanId ID dari LaporanGJM
     * @param array $imagePaths Array of image file paths
     * @return array
     */
    public function processImagesForLaporan(int $laporanId, array $imagePaths): array
    {
        Log::info("=== Processing Images for Laporan ===", [
            'laporan_id' => $laporanId,
            'images_count' => count($imagePaths)
        ]);

        try {
            $laporan = LaporanGJM::findOrFail($laporanId);

            // 1. Extract text using OCR
            $ocrResults = $this->ocrService->extractTextBatch($imagePaths);

            // Log a warning if no images succeeded, but don't hard-fail —
            // the AI can still work with partial or empty OCR data.
            if ($ocrResults['successful_count'] === 0) {
                Log::warning('OCR: no images produced text. Continuing with fallback metadata.', [
                    'laporan_id'  => $laporanId,
                    'failed'      => $ocrResults['failed_count'],
                    'combined_text_sample' => substr($ocrResults['combined_text'], 0, 200),
                ]);
            }

            // 2. Save OCR data to laporan
            $ocrData = [
                'images_count' => count($imagePaths),
                'successful_count' => $ocrResults['successful_count'],
                'failed_count' => $ocrResults['failed_count'],
                'combined_text' => $ocrResults['combined_text'],
                'total_length' => $ocrResults['total_length'],
                'total_processing_time_ms' => $ocrResults['total_processing_time_ms'],
                'processed_at' => now()->toISOString(),
                'results' => $ocrResults['results'],
            ];

            $this->aiPreviewCacheService->saveOCRData($laporanId, $ocrData);

            // 3. Chunk and index OCR text for RAG
            // Ensure combined_text is string before indexing
            $combinedTextForIndex = $ocrResults['combined_text'] ?? '';
            if (is_array($combinedTextForIndex)) {
                $combinedTextForIndex = json_encode($combinedTextForIndex);
            } elseif (!is_string($combinedTextForIndex)) {
                $combinedTextForIndex = (string)$combinedTextForIndex;
            }
            $this->indexOCRText($laporanId, $combinedTextForIndex);

            // Ensure combined_text is string for strlen
            $combinedText = $ocrResults['combined_text'] ?? '';
            if (is_array($combinedText)) {
                $combinedText = json_encode($combinedText);
            } elseif (!is_string($combinedText)) {
                $combinedText = (string)$combinedText;
            }

            Log::info("Images processed successfully", [
                'laporan_id' => $laporanId,
                'ocr_text_length' => strlen($combinedText),
                'successful_images' => $ocrResults['successful_count']
            ]);

            return [
                'success' => true,
                'message' => 'Images processed and OCR text extracted successfully',
                'data' => [
                    'ocr_data'           => $ocrData,
                    'extracted_text'     => $combinedText,
                    'text_length'        => strlen($combinedText),
                    'successful_images'  => $ocrResults['successful_count'],
                    'failed_images'      => $ocrResults['failed_count'],
                    'images_count'       => count($imagePaths),
                    'processing_time_ms' => $ocrResults['total_processing_time_ms'],
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Failed to process images for laporan", [
                'laporan_id' => $laporanId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process images: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Generate enhanced AI preview dengan OCR data
     * 
     * @param int $laporanId ID dari LaporanGJM
     * @param string $userPrompt User prompt dari chat assistant
     * @return array
     */
    public function generateEnhancedPreview(int $laporanId, string $userPrompt = ''): array
    {
        Log::info("=== Generating Enhanced AI Preview ===", [
            'laporan_id' => $laporanId,
            'user_prompt_length' => strlen($userPrompt)
        ]);

        try {
            $laporan = LaporanGJM::findOrFail($laporanId);

            // 1. Get existing OCR data
            $ocrData = $this->aiPreviewCacheService->getOCRData($laporanId);

            // 2. Get RAG context (from existing data + OCR chunks)
            $ragContext = $this->getRagContextForLaporan($laporanId, $userPrompt);

            // 3. Build enhanced context
            $enhancedContext = $this->buildEnhancedContext($laporan, $ocrData, $ragContext, $userPrompt);

            // 4. Parse instruksi_prompt
            $instruksi = json_decode($laporan->instruksi_prompt, true);
            $periode = $instruksi['periode'] ?? 'Triwulan';
            $tahun = $instruksi['tahun'] ?? date('Y');

            // 5. Define sections to generate
            $sections = [
                'latar_belakang' => 'Buat latar belakang laporan berdasarkan data yang tersedia, termasuk data dari gambar OCR',
                'tujuan' => 'Jelaskan tujuan dari laporan ini',
                'program_kerja' => 'Rangkum program kerja yang telah dilaksanakan berdasarkan data dan gambar',
                'pelaksanaan' => 'Jelaskan pelaksanaan program kerja dengan detail dari data OCR',
                'hambatan' => 'Identifikasi hambatan yang dihadapi berdasarkan data yang ada',
                'evaluasi' => 'Buat evaluasi dari pelaksanaan program berdasarkan semua data',
                'rekomendasi' => 'Berikan rekomendasi untuk perbaikan berdasarkan analisis data',
            ];

            // 6. Generate sections using AI
            $result = $this->aiService->generateReportSections(
                $enhancedContext,
                $sections,
                [
                    'periode' => $periode,
                    'tahun' => $tahun,
                    'has_ocr_data' => !empty($ocrData),
                    'ocr_images_count' => $ocrData['images_count'] ?? 0,
                ]
            );

            if (!$result['success']) {
                throw new \Exception('Failed to generate AI sections');
            }

            // 7. Build preview draft
            $previewDraft = $this->buildEnhancedPreviewDraft($result['sections'], $periode, $tahun, $ocrData);

            // 8. Save enhanced preview
            $this->aiPreviewCacheService->saveAIPreview(
                $laporanId,
                $previewDraft,
                $result['sections'],
                [], // file_details
                $ocrData // OCR data
            );

            Log::info("Enhanced AI preview generated successfully", [
                'laporan_id' => $laporanId,
                'preview_length' => strlen($previewDraft),
                'sections_count' => count($result['sections']),
                'has_ocr_data' => !empty($ocrData)
            ]);

            return [
                'success' => true,
                'message' => 'Enhanced AI preview generated successfully',
                'data' => [
                    'preview_draft' => $previewDraft,
                    'sections' => $result['sections'],
                    'metadata' => $result['metadata'],
                    'ocr_data' => $ocrData,
                    'has_ocr_data' => !empty($ocrData),
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Failed to generate enhanced preview", [
                'laporan_id' => $laporanId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to generate enhanced preview: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get RAG context untuk laporan (existing data + OCR chunks)
     */
    private function getRagContextForLaporan(int $laporanId, string $query): array
    {
        try {
            // Get relevant chunks from vector database
            $retrieval = $this->ragService->retrieveContext($query, [
                'source_type' => 'laporan_gjm',
                'source_id' => $laporanId,
                'top_k' => 10,
            ]);

            return [
                'context_text' => $retrieval['context_text'] ?? '',
                'chunks_count' => count($retrieval['chunks'] ?? []),
                'similarity_scores' => array_column($retrieval['chunks'] ?? [], 'similarity'),
            ];

        } catch (\Exception $e) {
            Log::warning("Failed to get RAG context", [
                'laporan_id' => $laporanId,
                'error' => $e->getMessage()
            ]);

            return [
                'context_text' => '',
                'chunks_count' => 0,
                'similarity_scores' => [],
            ];
        }
    }

    /**
     * Build enhanced context dengan OCR data
     */
    private function buildEnhancedContext(LaporanGJM $laporan, ?array $ocrData, array $ragContext, string $userPrompt): string
    {
        $context = "INFORMASI LAPORAN:\n";
        $context .= "- ID: {$laporan->id}\n";
        $context .= "- Jenis: " . $laporan->getJenisLaporanLabel() . "\n";
        $context .= "- Periode: " . $laporan->getPeriodeLabel() . "\n";
        $context .= "- Status: {$laporan->status_laporan}\n\n";

        // Add user prompt if provided
        if (!empty($userPrompt)) {
            $context .= "INSTRUKSI PENGGUNA:\n";
            $context .= $userPrompt . "\n\n";
        }

        // Add OCR data if available
        if (!empty($ocrData) && !empty($ocrData['combined_text'])) {
            // Ensure combined_text is string
            $combinedText = $ocrData['combined_text'] ?? '';
            if (is_array($combinedText)) {
                $combinedText = json_encode($combinedText);
            } elseif (!is_string($combinedText)) {
                $combinedText = (string)$combinedText;
            }
            
            $context .= "=== DATA DARI GAMBAR (OCR) ===\n";
            $context .= "Jumlah gambar: {$ocrData['images_count']}\n";
            $context .= "Gambar berhasil: {$ocrData['successful_count']}\n";
            $context .= "Total teks: " . number_format(strlen($combinedText)) . " karakter\n\n";
            $context .= "TEKS DARI GAMBAR:\n";
            $context .= $combinedText . "\n\n";
        }

        // Add RAG context if available
        if (!empty($ragContext['context_text'])) {
            $context .= "=== DATA TERKAIT DARI DATABASE ===\n";
            $context .= "Chunks ditemukan: {$ragContext['chunks_count']}\n\n";
            $context .= $ragContext['context_text'] . "\n\n";
        }

        return $context;
    }

    /**
     * Build enhanced preview draft
     */
    private function buildEnhancedPreviewDraft(array $sections, string $periode, string $tahun, ?array $ocrData): string
    {
        $draft = "# PREVIEW DRAFT LAPORAN {$periode} {$tahun}\n\n";
        
        // Add OCR info if available
        if (!empty($ocrData)) {
            // Ensure combined_text is string
            $combinedText = $ocrData['combined_text'] ?? '';
            if (is_array($combinedText)) {
                $combinedText = json_encode($combinedText);
            } elseif (!is_string($combinedText)) {
                $combinedText = (string)$combinedText;
            }
            
            $draft .= "**📸 Data dari Gambar:**\n";
            $draft .= "- Jumlah gambar: {$ocrData['images_count']}\n";
            $draft .= "- Berhasil diproses: {$ocrData['successful_count']}\n";
            $draft .= "- Total teks: " . number_format(strlen($combinedText)) . " karakter\n\n";
            $draft .= "---\n\n";
        }
        
        $sectionTitles = [
            'latar_belakang' => 'LATAR BELAKANG',
            'tujuan' => 'TUJUAN',
            'program_kerja' => 'PROGRAM KERJA',
            'pelaksanaan' => 'PELAKSANAAN',
            'hambatan' => 'HAMBATAN',
            'evaluasi' => 'EVALUASI',
            'rekomendasi' => 'REKOMENDASI',
        ];

        foreach ($sections as $key => $content) {
            $title = $sectionTitles[$key] ?? strtoupper($key);
            $draft .= "## {$title}\n\n";
            $draft .= $content . "\n\n";
            $draft .= "---\n\n";
        }

        return $draft;
    }

    /**
     * Index OCR text untuk RAG retrieval
     */
    private function indexOCRText(int $laporanId, string $ocrText): void
    {
        try {
            // Ensure ocrText is string
            if (is_array($ocrText)) {
                $ocrText = json_encode($ocrText);
            } elseif (!is_string($ocrText)) {
                $ocrText = (string)$ocrText;
            }
            
            if (empty($ocrText) || strlen($ocrText) < 50) {
                Log::info("OCR text too short to index", ['laporan_id' => $laporanId]);
                return;
            }

            // Chunk the OCR text
            $chunks = $this->chunkingService->chunkText($ocrText, [
                'chunk_size' => 500,
                'chunk_overlap' => 50,
                'preserve_structure' => true,
            ]);

            Log::info("OCR text chunked", [
                'laporan_id' => $laporanId,
                'chunks_count' => count($chunks),
                'total_length' => strlen($ocrText)
            ]);

            // Index each chunk
            foreach ($chunks as $index => $chunk) {
                // Ensure chunk is string
                if (is_array($chunk)) {
                    $chunk = json_encode($chunk);
                } elseif (!is_string($chunk)) {
                    $chunk = (string)$chunk;
                }
                
                $this->vectorDbService->indexDocument([
                    'text' => $chunk,
                    'source_type' => 'laporan_gjm',
                    'source_id' => $laporanId,
                    'chunk_index' => $index,
                    'metadata' => [
                        'type' => 'ocr_text',
                        'laporan_id' => $laporanId,
                        'chunk_size' => strlen($chunk),
                        'indexed_at' => now()->toISOString(),
                    ]
                ]);
            }

            Log::info("OCR text indexed successfully", [
                'laporan_id' => $laporanId,
                'chunks_indexed' => count($chunks)
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to index OCR text", [
                'laporan_id' => $laporanId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get combined statistics untuk laporan
     */
    public function getLaporanStats(int $laporanId): array
    {
        try {
            $previewStats = $this->aiPreviewCacheService->getPreviewStats($laporanId);
            
            // Get document chunks count
            $chunksCount = DocumentChunk::where('source_type', 'laporan_gjm')
                ->where('source_id', $laporanId)
                ->count();

            return array_merge($previewStats, [
                'indexed_chunks_count' => $chunksCount,
                'has_indexed_data' => $chunksCount > 0,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to get laporan stats", [
                'laporan_id' => $laporanId,
                'error' => $e->getMessage()
            ]);

            return [
                'has_preview' => false,
                'has_ocr_data' => false,
                'indexed_chunks_count' => 0,
                'has_indexed_data' => false,
            ];
        }
    }

    /**
     * Clear all data untuk laporan
     */
    public function clearLaporanData(int $laporanId): bool
    {
        try {
            // Clear AI preview
            $this->aiPreviewCacheService->clearAIPreview($laporanId);

            // Clear indexed chunks
            DocumentChunk::where('source_type', 'laporan_gjm')
                ->where('source_id', $laporanId)
                ->delete();

            Log::info("Laporan data cleared", ['laporan_id' => $laporanId]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to clear laporan data", [
                'laporan_id' => $laporanId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
