<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ChunkingService
{
    private $chunkSize;
    private $chunkOverlap;

    public function __construct()
    {
        $this->chunkSize = env('CHUNK_SIZE', 500);
        $this->chunkOverlap = env('CHUNK_OVERLAP', 50);
    }

    /**
     * Split text into chunks with overlap
     */
    public function chunkText(string $text): array
    {
        // Clean text first
        $text = $this->cleanText($text);

        // Split by sentences first
        $sentences = $this->splitIntoSentences($text);

        $chunks = [];
        $currentChunk = '';
        $chunkIndex = 0;

        foreach ($sentences as $sentence) {
            $testChunk = $currentChunk . ' ' . $sentence;

            if (strlen($testChunk) > $this->chunkSize && !empty($currentChunk)) {
                // Save current chunk
                $chunks[] = [
                    'text' => trim($currentChunk),
                    'index' => $chunkIndex++,
                    'length' => strlen($currentChunk),
                ];

                // Start new chunk with overlap
                $words = explode(' ', $currentChunk);
                $overlapWords = array_slice($words, -10); // Last 10 words as overlap
                $currentChunk = implode(' ', $overlapWords) . ' ' . $sentence;
            } else {
                $currentChunk = $testChunk;
            }
        }

        // Add last chunk
        if (!empty(trim($currentChunk))) {
            $chunks[] = [
                'text' => trim($currentChunk),
                'index' => $chunkIndex,
                'length' => strlen($currentChunk),
            ];
        }

        Log::info("Text chunked", [
            'original_length' => strlen($text),
            'num_chunks' => count($chunks),
            'avg_chunk_size' => count($chunks) > 0 ? array_sum(array_column($chunks, 'length')) / count($chunks) : 0
        ]);

        return $chunks;
    }

    /**
     * Clean text
     */
    private function cleanText(string $text): string
    {
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        // Remove special characters but keep punctuation
        $text = preg_replace('/[^\p{L}\p{N}\s\.\,\!\?\-\:]/u', '', $text);

        return trim($text);
    }

    /**
     * Split text into sentences
     */
    private function splitIntoSentences(string $text): array
    {
        // Split by common sentence endings
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        return array_filter($sentences, function($sentence) {
            return strlen(trim($sentence)) > 10; // Minimum sentence length
        });
    }

    /**
     * Chunk with metadata
     */
    public function chunkWithMetadata(string $text, array $metadata = []): array
    {
        $chunks = $this->chunkText($text);

        return array_map(function($chunk) use ($metadata) {
            return [
                'text' => $chunk['text'],
                'index' => $chunk['index'],
                'length' => $chunk['length'],
                'metadata' => array_merge($metadata, [
                    'chunk_index' => $chunk['index'],
                    'chunk_length' => $chunk['length'],
                ]),
            ];
        }, $chunks);
    }
}
