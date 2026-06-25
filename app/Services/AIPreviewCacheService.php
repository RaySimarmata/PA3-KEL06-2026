<?php

namespace App\Services;

use App\Models\LaporanGJM;
use Illuminate\Support\Facades\Log;

/**
 * AIPreviewCacheService
 *
 * Service untuk menyimpan dan mengambil AI preview/draft dari chat assistant
 * Preview ini akan digunakan untuk generate Laporan Word, Semester, dan PPT
 * Enhanced dengan OCR data integration
 */
class AIPreviewCacheService
{
    /**
     * Simpan AI preview ke database
     *
     * @param int $laporanId ID dari LaporanGJM
     * @param string $aiPreviewDraft Full AI preview/draft text
     * @param array $sections Parsed sections dari preview
     * @param array $ocrImages Array of image paths and metadata
     * @param array $fileDetails File details (opsional)
     * @return bool
     */
    public function saveAIPreview(
        int $laporanId,
        string $aiPreviewDraft,
        array $sections = [],
        array $ocrImages = [],
        array $fileDetails = []
    ): bool {
        try {
            $laporan = LaporanGJM::findOrFail($laporanId);

            $updateData = [
                'ai_preview_draft' => $aiPreviewDraft,
                'ai_sections' => $sections,
                'ai_preview_created_at' => now(),
                'ai_preview_used_for_generation' => false,
            ];

            // Store file details if provided
            if (!empty($fileDetails)) {
                $updateData['ai_file_details'] = $fileDetails;
            }

            // Store OCR images data if provided
            if (!empty($ocrImages)) {
                $ocrData = [
                    'images' => $ocrImages,
                    'images_count' => count($ocrImages),
                    'saved_at' => now()->toIso8601String(),
                ];

                $updateData['ocr_data'] = $ocrData;
                $updateData['has_ocr_data'] = true;
            }

            $laporan->update($updateData);

            Log::info('AI preview saved', [
                'laporan_id' => $laporanId,
                'preview_length' => strlen($aiPreviewDraft),
                'sections_count' => count($sections),
                'has_file_details' => !empty($fileDetails),
                'has_ocr_images' => !empty($ocrImages),
                'ocr_images_count' => count($ocrImages),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to save AI preview', [
                'laporan_id' => $laporanId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Ambil AI preview dari database
     *
     * @param int $laporanId ID dari LaporanGJM
     * @return array|null
     */
    public function getAIPreview(int $laporanId): ?array
    {
        try {
            $laporan = LaporanGJM::findOrFail($laporanId);

            if (empty($laporan->ai_preview_draft)) {
                return null;
            }

            return [
                'draft' => $laporan->ai_preview_draft,
                'sections' => $laporan->ai_sections ?? [],
                'file_details' => $laporan->ai_file_details ?? [],
                'ocr_data' => $laporan->ocr_data ?? [],
                'has_ocr_data' => $laporan->has_ocr_data ?? false,
                'created_at' => $laporan->ai_preview_created_at,
                'used_for_generation' => $laporan->ai_preview_used_for_generation,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get AI preview', [
                'laporan_id' => $laporanId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Parse AI preview menjadi sections
     *
     * @param string $aiPreview Full AI preview text
     * @return array Parsed sections
     */
    public function parseAIPreview(string $aiPreview): array
    {
        $sections = [];

        // Define section keywords
        $sectionKeywords = [
            'latar_belakang' => ['latar belakang', 'background', 'pendahuluan'],
            'dasar' => ['dasar penyusunan', 'dasar', 'basis', 'foundation'],
            'tujuan' => ['tujuan', 'objective', 'purpose'],
            'ruang_lingkup' => ['ruang lingkup', 'scope', 'cakupan'],
            'program_kerja' => ['program kerja', 'work program', 'kegiatan'],
            'pelaksanaan' => ['pelaksanaan', 'implementation', 'execution'],
            'hambatan' => ['hambatan', 'obstacle', 'challenge', 'kendala'],
            'pemecahan_masalah' => ['pemecahan masalah', 'solusi', 'solution'],
            'evaluasi' => ['evaluasi', 'evaluation', 'assessment'],
            'kesimpulan' => ['kesimpulan', 'conclusion', 'penutup'],
            'rekomendasi' => ['rekomendasi', 'recommendation', 'saran'],
        ];

        // Split by markdown headings
        $lines = explode("\n", $aiPreview);
        $currentSection = null;
        $currentContent = '';

        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            $isHeading = false;

            // Check if line is a markdown heading
            if (preg_match('/^#+\s+(.+)$/i', $line, $matches)) {
                $heading = strtolower(trim($matches[1]));
                $isHeading = true;
            } elseif (!empty($trimmedLine)) {
                // Check for plain text section headings such as "Latar Belakang:" or "1. Tujuan"
                foreach ($sectionKeywords as $sectionKey => $keywords) {
                    foreach ($keywords as $keyword) {
                        $pattern = '/^\s*(?:\d+\.\s*)?' . preg_quote($keyword, '/') . '(?:\s*[:\-]|\s*$)/i';
                        if (preg_match($pattern, $trimmedLine)) {
                            $heading = strtolower($keyword);
                            $isHeading = true;
                            break 2;
                        }
                    }
                }
            }

            if ($isHeading) {
                // Save previous section
                if ($currentSection && !empty(trim($currentContent))) {
                    $sections[$currentSection] = trim($currentContent);
                }

                $currentSection = null;
                $currentContent = '';

                // Match heading with keywords using normalized text
                if (!empty($heading)) {
                    foreach ($sectionKeywords as $sectionKey => $keywords) {
                        foreach ($keywords as $keyword) {
                            if (strpos($heading, $keyword) !== false) {
                                $currentSection = $sectionKey;
                                break 2;
                            }
                        }
                    }
                }
            } else {
                // Add line to current section
                if ($currentSection) {
                    $currentContent .= $line . "\n";
                }
            }
        }

        // Save last section
        if ($currentSection && !empty(trim($currentContent))) {
            $sections[$currentSection] = trim($currentContent);
        }

        Log::info('AI preview parsed', [
            'sections_found' => count($sections),
            'section_keys' => array_keys($sections),
        ]);

        return $sections;
    }

    /**
     * Get specific section dari AI preview
     *
     * @param int $laporanId ID dari LaporanGJM
     * @param string $sectionKey Section key (latar_belakang, dasar, dll)
     * @return string|null
     */
    public function getSection(int $laporanId, string $sectionKey): ?string
    {
        $preview = $this->getAIPreview($laporanId);

        if (!$preview || empty($preview['sections'])) {
            return null;
        }

        return $preview['sections'][$sectionKey] ?? null;
    }

    /**
     * Get all sections dari AI preview
     *
     * @param int $laporanId ID dari LaporanGJM
     * @return array
     */
    public function getAllSections(int $laporanId): array
    {
        $preview = $this->getAIPreview($laporanId);

        if (!$preview) {
            return [];
        }

        return $preview['sections'] ?? [];
    }

    /**
     * Mark AI preview sebagai sudah digunakan untuk generate
     *
     * @param int $laporanId ID dari LaporanGJM
     * @return bool
     */
    public function markAsUsedForGeneration(int $laporanId): bool
    {
        try {
            $laporan = LaporanGJM::findOrFail($laporanId);
            $laporan->update(['ai_preview_used_for_generation' => true]);

            Log::info('AI preview marked as used', ['laporan_id' => $laporanId]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark AI preview as used', [
                'laporan_id' => $laporanId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Clear AI preview
     *
     * @param int $laporanId ID dari LaporanGJM
     * @return bool
     */
    public function clearAIPreview(int $laporanId): bool
    {
        try {
            $laporan = LaporanGJM::findOrFail($laporanId);
            $laporan->update([
                'ai_preview_draft' => null,
                'ai_sections' => null,
                'ai_preview_created_at' => null,
                'ai_preview_used_for_generation' => false,
                'ocr_data' => null,
                'has_ocr_data' => false,
            ]);

            Log::info('AI preview cleared', ['laporan_id' => $laporanId]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear AI preview', [
                'laporan_id' => $laporanId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get AI preview statistics
     *
     * @param int $laporanId ID dari LaporanGJM
     * @return array
     */
    public function getPreviewStats(int $laporanId): array
    {
        $preview = $this->getAIPreview($laporanId);

        if (!$preview) {
            return [
                'has_preview' => false,
                'preview_length' => 0,
                'sections_count' => 0,
                'created_at' => null,
                'used_for_generation' => false,
                'file_details_count' => 0,
                'has_ocr_data' => false,
                'ocr_images_count' => 0,
                'ocr_text_length' => 0,
            ];
        }

        $ocrStats = [];
        if ($preview['has_ocr_data'] && !empty($preview['ocr_data'])) {
            $ocrData = $preview['ocr_data'];
            $ocrText = $ocrData['combined_text'] ?? '';
            if (is_array($ocrText)) {
                $ocrText = json_encode($ocrText);
            } elseif (!is_string($ocrText)) {
                $ocrText = (string)$ocrText;
            }
            $ocrStats = [
                'ocr_images_count' => $ocrData['images_count'] ?? 0,
                'ocr_text_length' => strlen($ocrText),
                'ocr_successful_images' => $ocrData['successful_count'] ?? 0,
                'ocr_failed_images' => $ocrData['failed_count'] ?? 0,
                'ocr_processing_time_ms' => $ocrData['total_processing_time_ms'] ?? 0,
            ];
        }

        return array_merge([
            'has_preview' => true,
            'preview_length' => strlen(is_string($preview['draft']) ? $preview['draft'] : json_encode($preview['draft'])),
            'sections_count' => count($preview['sections']),
            'created_at' => $preview['created_at'],
            'used_for_generation' => $preview['used_for_generation'],
            'sections' => array_keys($preview['sections']),
            'file_details_count' => count($preview['file_details']),
            'has_file_details' => !empty($preview['file_details']),
            'has_ocr_data' => $preview['has_ocr_data'],
        ], $ocrStats);
    }

    /**
     * Get file details dari AI preview
     *
     * @param int $laporanId ID dari LaporanGJM
     * @return array|null
     */
    public function getFileDetails(int $laporanId): ?array
    {
        $preview = $this->getAIPreview($laporanId);

        if (!$preview || empty($preview['file_details'])) {
            return null;
        }

        return $preview['file_details'];
    }

    /**
     * Get OCR data dari AI preview
     *
     * @param int $laporanId ID dari LaporanGJM
     * @return array|null
     */
    public function getOCRData(int $laporanId): ?array
    {
        $preview = $this->getAIPreview($laporanId);

        if (!$preview || !$preview['has_ocr_data'] || empty($preview['ocr_data'])) {
            return null;
        }

        return $preview['ocr_data'];
    }

    /**
     * Save OCR data untuk laporan
     *
     * @param int $laporanId ID dari LaporanGJM
     * @param array $ocrData OCR data dari gambar
     * @return bool
     */
    public function saveOCRData(int $laporanId, array $ocrData): bool
    {
        try {
            $laporan = LaporanGJM::findOrFail($laporanId);

            $laporan->update([
                'ocr_data' => $ocrData,
                'has_ocr_data' => true,
            ]);

            // Ensure combined_text is string for strlen
            $ocrText = $ocrData['combined_text'] ?? '';
            if (is_array($ocrText)) {
                $ocrText = json_encode($ocrText);
            } elseif (!is_string($ocrText)) {
                $ocrText = (string)$ocrText;
            }

            Log::info('OCR data saved to laporan', [
                'laporan_id' => $laporanId,
                'images_count' => $ocrData['images_count'] ?? 0,
                'text_length' => strlen($ocrText),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to save OCR data', [
                'laporan_id' => $laporanId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get combined context (AI preview + OCR data) untuk generate laporan
     *
     * @param int $laporanId ID dari LaporanGJM
     * @return array
     */
    public function getCombinedContext(int $laporanId): array
    {
        $preview = $this->getAIPreview($laporanId);

        if (!$preview) {
            return [
                'has_data' => false,
                'ai_preview' => null,
                'ocr_data' => null,
                'combined_text' => '',
            ];
        }

        $combinedText = $preview['draft'];

        // Add OCR text if available
        if ($preview['has_ocr_data'] && !empty($preview['ocr_data']['combined_text'])) {
            $ocrText = $preview['ocr_data']['combined_text'];
            if (is_array($ocrText)) {
                $ocrText = json_encode($ocrText);
            } elseif (!is_string($ocrText)) {
                $ocrText = (string)$ocrText;
            }
            $combinedText .= "\n\n=== DATA DARI GAMBAR (OCR) ===\n\n";
            $combinedText .= $ocrText;
        }

        return [
            'has_data' => true,
            'ai_preview' => $preview,
            'ocr_data' => $preview['ocr_data'] ?? null,
            'combined_text' => $combinedText,
            'has_ocr_data' => $preview['has_ocr_data'],
        ];
    }
}
