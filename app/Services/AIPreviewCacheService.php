<?php

namespace App\Services;

use App\Models\LaporanGJM;
use Illuminate\Support\Facades\Log;

/**
 * AIPreviewCacheService
 * 
 * Service untuk menyimpan dan mengambil AI preview/draft dari chat assistant
 * Preview ini akan digunakan untuk generate Laporan Word, Semester, dan PPT
 */
class AIPreviewCacheService
{
    /**
     * Simpan AI preview ke database
     * 
     * @param int $laporanId ID dari LaporanGJM
     * @param string $aiPreviewDraft Full AI preview/draft text
     * @param array $sections Parsed sections dari preview
     * @param array $fileDetails File details (opsional)
     * @return bool
     */
    public function saveAIPreview(
        int $laporanId,
        string $aiPreviewDraft,
        array $sections = [],
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
            
            $laporan->update($updateData);
            
            Log::info('AI preview saved', [
                'laporan_id' => $laporanId,
                'preview_length' => strlen($aiPreviewDraft),
                'sections_count' => count($sections),
                'has_file_details' => !empty($fileDetails),
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
            // Check if line is a heading
            if (preg_match('/^#+\s+(.+)$/i', $line, $matches)) {
                // Save previous section
                if ($currentSection && !empty(trim($currentContent))) {
                    $sections[$currentSection] = trim($currentContent);
                }
                
                // Identify new section
                $heading = strtolower($matches[1]);
                $currentSection = null;
                $currentContent = '';
                
                // Match heading with keywords
                foreach ($sectionKeywords as $sectionKey => $keywords) {
                    foreach ($keywords as $keyword) {
                        if (strpos($heading, $keyword) !== false) {
                            $currentSection = $sectionKey;
                            break 2;
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
            ];
        }
        
        return [
            'has_preview' => true,
            'preview_length' => strlen($preview['draft']),
            'sections_count' => count($preview['sections']),
            'created_at' => $preview['created_at'],
            'used_for_generation' => $preview['used_for_generation'],
            'sections' => array_keys($preview['sections']),
            'file_details_count' => count($preview['file_details']),
            'has_file_details' => !empty($preview['file_details']),
        ];
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
}
