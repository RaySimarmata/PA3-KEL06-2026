<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Advanced chunking yang mempertahankan struktur dokumen
 */
class AdvancedChunkingService
{
    private $chunkSize;
    private $chunkOverlap;
    private $structureService;

    public function __construct(DocumentStructureService $structureService)
    {
        $this->chunkSize = env('CHUNK_SIZE', 800); // Lebih besar untuk context
        $this->chunkOverlap = env('CHUNK_OVERLAP', 100);
        $this->structureService = $structureService;
    }

    /**
     * Chunk dengan mempertahankan struktur dokumen
     */
    public function chunkWithStructure(string $text, string $documentType = 'template'): array
    {
        Log::info("=== Advanced Chunking with Structure ===", [
            'document_type' => $documentType,
            'text_length' => strlen($text)
        ]);

        // Extract structure first
        $structure = $this->structureService->extractStructure($text);

        $chunks = [];

        if ($documentType === 'template') {
            // Untuk template: chunk per section untuk mempertahankan struktur
            $chunks = $this->chunkBySection($text, $structure);
        } else {
            // Untuk data: chunk semantic
            $chunks = $this->chunkSemantic($text);
        }

        Log::info("Chunking completed", [
            'num_chunks' => count($chunks),
            'avg_size' => count($chunks) > 0 ? array_sum(array_column($chunks, 'length')) / count($chunks) : 0
        ]);

        return $chunks;
    }

    /**
     * Chunk berdasarkan section untuk template
     */
    private function chunkBySection(string $text, array $structure): array
    {
        $chunks = [];
        $chunkIndex = 0;

        // Chunk 0: Full structure overview
        $formatInstructions = $this->structureService->generateFormatInstructions($structure);
        $chunks[] = [
            'text' => $formatInstructions,
            'index' => $chunkIndex++,
            'type' => 'structure_overview',
            'section' => 'FORMAT_INSTRUCTIONS',
            'length' => strlen($formatInstructions),
            'metadata' => [
                'is_template' => true,
                'chunk_type' => 'format_guide'
            ]
        ];

        // Chunk per section
        foreach ($structure['sections'] as $section) {
            $sectionText = "SECTION: {$section['title']}\n\n{$section['content']}";
            
            // Jika section terlalu besar, split lagi
            if (strlen($sectionText) > $this->chunkSize * 1.5) {
                $subChunks = $this->splitLargeSection($sectionText, $section['title']);
                foreach ($subChunks as $subChunk) {
                    $chunks[] = [
                        'text' => $subChunk,
                        'index' => $chunkIndex++,
                        'type' => 'section_part',
                        'section' => $section['title'],
                        'length' => strlen($subChunk),
                        'metadata' => [
                            'is_template' => true,
                            'chunk_type' => 'section_content',
                            'section_title' => $section['title']
                        ]
                    ];
                }
            } else {
                $chunks[] = [
                    'text' => $sectionText,
                    'index' => $chunkIndex++,
                    'type' => 'section',
                    'section' => $section['title'],
                    'length' => strlen($sectionText),
                    'metadata' => [
                        'is_template' => true,
                        'chunk_type' => 'section_content',
                        'section_title' => $section['title']
                    ]
                ];
            }
        }

        return $chunks;
    }

    /**
     * Split section yang terlalu besar
     */
    private function splitLargeSection(string $text, string $sectionTitle): array
    {
        $paragraphs = preg_split('/\n\s*\n/', $text);
        $chunks = [];
        $currentChunk = "SECTION: {$sectionTitle}\n\n";

        foreach ($paragraphs as $para) {
            if (strlen($currentChunk . $para) > $this->chunkSize && strlen($currentChunk) > 100) {
                $chunks[] = $currentChunk;
                $currentChunk = "SECTION: {$sectionTitle} (continued)\n\n" . $para;
            } else {
                $currentChunk .= $para . "\n\n";
            }
        }

        if (strlen(trim($currentChunk)) > 0) {
            $chunks[] = $currentChunk;
        }

        return $chunks;
    }

    /**
     * Chunk semantic untuk data kuesioner
     */
    private function chunkSemantic(string $text): array
    {
        $chunks = [];
        $chunkIndex = 0;

        // Split by paragraphs
        $paragraphs = preg_split('/\n\s*\n/', $text);
        $currentChunk = '';

        foreach ($paragraphs as $para) {
            $para = trim($para);
            if (empty($para)) continue;

            $testChunk = $currentChunk . "\n\n" . $para;

            if (strlen($testChunk) > $this->chunkSize && !empty($currentChunk)) {
                // Save current chunk
                $chunks[] = [
                    'text' => trim($currentChunk),
                    'index' => $chunkIndex++,
                    'type' => 'semantic',
                    'length' => strlen($currentChunk),
                    'metadata' => [
                        'is_template' => false,
                        'chunk_type' => 'data'
                    ]
                ];

                // Start new chunk with overlap
                $sentences = preg_split('/(?<=[.!?])\s+/', $currentChunk);
                $overlapText = implode(' ', array_slice($sentences, -2)); // Last 2 sentences
                $currentChunk = $overlapText . "\n\n" . $para;
            } else {
                $currentChunk = $testChunk;
            }
        }

        // Add last chunk
        if (!empty(trim($currentChunk))) {
            $chunks[] = [
                'text' => trim($currentChunk),
                'index' => $chunkIndex,
                'type' => 'semantic',
                'length' => strlen($currentChunk),
                'metadata' => [
                    'is_template' => false,
                    'chunk_type' => 'data'
                ]
            ];
        }

        return $chunks;
    }

    /**
     * Chunk dengan metadata lengkap
     */
    public function chunkWithMetadata(string $text, array $metadata = [], string $documentType = 'template'): array
    {
        $chunks = $this->chunkWithStructure($text, $documentType);

        return array_map(function($chunk) use ($metadata) {
            $chunk['metadata'] = array_merge($chunk['metadata'] ?? [], $metadata, [
                'chunk_index' => $chunk['index'],
                'chunk_length' => $chunk['length'],
                'chunk_type' => $chunk['type'] ?? 'unknown'
            ]);
            return $chunk;
        }, $chunks);
    }
}
