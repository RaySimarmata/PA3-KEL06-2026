<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ImageContentValidationService
{
    protected $ocrService;
    
    // Keywords yang relevan untuk Laporan Triwulan
    private $triwulanRelevantKeywords = [
        // Kata kunci akademik
        'mahasiswa', 'dosen', 'perkuliahan', 'kuliah', 'semester', 'triwulan',
        'mata kuliah', 'matakuliah', 'kelas', 'ruang', 'jadwal', 'absen', 'hadir',
        'nilai', 'ujian', 'tugas', 'praktikum', 'laboratorium', 'lab',
        
        // Kata kunci monitoring
        'monitoring', 'evaluasi', 'penilaian', 'kualitas', 'mutu', 'standar',
        'capaian', 'target', 'realisasi', 'persentase', 'grafik', 'chart',
        'data', 'statistik', 'laporan', 'dokumentasi',
        
        // Kata kunci institusi
        'universitas', 'fakultas', 'prodi', 'program studi', 'jurusan',
        'kampus', 'akademik', 'pendidikan', 'pembelajaran',
        
        // Kata kunci GKM/GJM
        'gkm', 'gjm', 'gugus', 'kendali', 'jaminan', 'mutu',
        'rps', 'silabus', 'kurikulum', 'obe', 'cpmk', 'cpl',
        
        // Kata kunci dokumen
        'tanggal', 'bulan', 'tahun', 'periode', 'nama', 'nim', 'nidn',
        'tanda tangan', 'ttd', 'paraf', 'stempel', 'cap',
        
        // Kata kunci kegiatan
        'kegiatan', 'acara', 'rapat', 'pertemuan', 'workshop', 'seminar',
        'pelatihan', 'sosialisasi', 'koordinasi', 'diskusi',
        
        // Kata kunci sistem
        'sistem', 'aplikasi', 'portal', 'dashboard', 'login', 'user',
        'menu', 'form', 'input', 'output', 'report'
    ];
    
    // Keywords yang tidak relevan (red flags)
    private $irrelevantKeywords = [
        'apple', 'iphone', 'android', 'samsung', 'logo brand',
        'game', 'gaming', 'entertainment', 'movie', 'film',
        'fashion', 'food', 'recipe', 'travel', 'tourism',
        'shopping', 'e-commerce', 'product', 'sale', 'discount',
        'social media', 'instagram', 'facebook', 'twitter', 'tiktok'
    ];

    public function __construct(OCRService $ocrService)
    {
        $this->ocrService = $ocrService;
    }

    /**
     * Validate if uploaded image is relevant to the report type
     * 
     * @param string $imagePath Path to the uploaded image
     * @param string $reportType Type of report (laporan_triwulan, laporan_semester, laporan_vmts)
     * @return array ['is_valid' => bool, 'confidence' => float, 'reason' => string, 'extracted_text' => string]
     */
    public function validateImageRelevance(string $imagePath, string $reportType = 'laporan_triwulan'): array
    {
        try {
            Log::info("=== Image Content Validation Started ===", [
                'image_path' => $imagePath,
                'report_type' => $reportType
            ]);

            // Step 1: Extract text from image using OCR
            $ocrResult = $this->ocrService->extractText($imagePath);
            
            if (!$ocrResult['success']) {
                Log::warning("OCR extraction failed during validation", [
                    'error' => $ocrResult['error'] ?? 'Unknown error'
                ]);
                
                // If OCR fails, we can't validate content, so we allow it with low confidence
                return [
                    'is_valid' => true,
                    'confidence' => 0.3,
                    'reason' => 'OCR tidak dapat membaca teks dari gambar, validasi konten tidak dapat dilakukan',
                    'extracted_text' => '',
                    'ocr_method' => $ocrResult['method'] ?? 'unknown'
                ];
            }

            $extractedText = $ocrResult['text'] ?? '';
            $textLength = strlen($extractedText);
            
            Log::info("OCR extraction successful", [
                'text_length' => $textLength,
                'word_count' => $ocrResult['word_count'] ?? 0,
                'method' => $ocrResult['method'] ?? 'unknown'
            ]);

            // Step 2: Check if text is too short (likely not a document)
            if ($textLength < 20) {
                Log::warning("Extracted text too short", [
                    'text_length' => $textLength,
                    'text_preview' => substr($extractedText, 0, 50)
                ]);
                
                // Check if it's just a logo or simple image
                if ($this->isLikelyLogoOrSimpleImage($extractedText)) {
                    return [
                        'is_valid' => false,
                        'confidence' => 0.85,
                        'reason' => 'Gambar yang diupload tampaknya hanya logo atau gambar sederhana, bukan dokumen laporan. Silakan upload dokumentasi kegiatan, daftar hadir, atau dokumen akademik yang relevan.',
                        'extracted_text' => $extractedText,
                        'ocr_method' => $ocrResult['method']
                    ];
                }
                
                // If text is short but not a logo, allow with low confidence
                return [
                    'is_valid' => true,
                    'confidence' => 0.4,
                    'reason' => 'Teks yang terdeteksi sangat sedikit, validasi konten terbatas',
                    'extracted_text' => $extractedText,
                    'ocr_method' => $ocrResult['method']
                ];
            }

            // Step 3: Analyze text content for relevance
            $relevanceScore = $this->calculateRelevanceScore($extractedText, $reportType);
            
            Log::info("Relevance analysis completed", [
                'relevance_score' => $relevanceScore,
                'threshold' => 0.3
            ]);

            // Step 4: Determine if image is valid based on relevance score
            $isValid = $relevanceScore >= 0.3; // Threshold: 30% relevance
            $confidence = min($relevanceScore * 1.5, 1.0); // Scale confidence
            
            $reason = $this->generateValidationReason($isValid, $relevanceScore, $extractedText);

            Log::info("=== Image Content Validation Completed ===", [
                'is_valid' => $isValid,
                'confidence' => $confidence,
                'relevance_score' => $relevanceScore
            ]);

            return [
                'is_valid' => $isValid,
                'confidence' => $confidence,
                'reason' => $reason,
                'extracted_text' => $extractedText,
                'ocr_method' => $ocrResult['method'],
                'relevance_score' => $relevanceScore
            ];

        } catch (\Exception $e) {
            Log::error("Image validation failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // On error, allow image with low confidence
            return [
                'is_valid' => true,
                'confidence' => 0.2,
                'reason' => 'Validasi gambar gagal: ' . $e->getMessage(),
                'extracted_text' => '',
                'ocr_method' => 'error'
            ];
        }
    }

    /**
     * Calculate relevance score based on keyword matching
     * 
     * @param string $text Extracted text from image
     * @param string $reportType Type of report
     * @return float Score between 0 and 1
     */
    private function calculateRelevanceScore(string $text, string $reportType): float
    {
        $text = strtolower($text);
        $text = $this->normalizeText($text);
        
        // Count relevant keywords
        $relevantCount = 0;
        $relevantMatches = [];
        
        foreach ($this->triwulanRelevantKeywords as $keyword) {
            $keyword = strtolower($keyword);
            if (strpos($text, $keyword) !== false) {
                $relevantCount++;
                $relevantMatches[] = $keyword;
            }
        }
        
        // Count irrelevant keywords (red flags)
        $irrelevantCount = 0;
        $irrelevantMatches = [];
        
        foreach ($this->irrelevantKeywords as $keyword) {
            $keyword = strtolower($keyword);
            if (strpos($text, $keyword) !== false) {
                $irrelevantCount++;
                $irrelevantMatches[] = $keyword;
            }
        }
        
        Log::info("Keyword matching results", [
            'relevant_count' => $relevantCount,
            'relevant_matches' => array_slice($relevantMatches, 0, 10),
            'irrelevant_count' => $irrelevantCount,
            'irrelevant_matches' => $irrelevantMatches
        ]);
        
        // Calculate base score from relevant keywords
        $baseScore = min($relevantCount / 5, 1.0); // Need at least 5 relevant keywords for full score
        
        // Penalty for irrelevant keywords
        $penalty = $irrelevantCount * 0.2;
        
        // Final score
        $score = max(0, $baseScore - $penalty);
        
        // Boost score if contains specific high-value keywords
        $highValueKeywords = ['monitoring', 'laporan', 'triwulan', 'semester', 'gkm', 'gjm', 'mutu'];
        foreach ($highValueKeywords as $hvKeyword) {
            if (strpos($text, $hvKeyword) !== false) {
                $score += 0.1;
            }
        }
        
        return min($score, 1.0);
    }

    /**
     * Check if image is likely just a logo or simple image
     * 
     * @param string $text Extracted text
     * @return bool
     */
    private function isLikelyLogoOrSimpleImage(string $text): bool
    {
        $text = strtolower(trim($text));
        
        // Check for common logo indicators
        $logoIndicators = [
            'apple', 'logo', 'brand', 'trademark', '®', '™', '©',
            'inc', 'ltd', 'corp', 'company'
        ];
        
        foreach ($logoIndicators as $indicator) {
            if (strpos($text, $indicator) !== false) {
                return true;
            }
        }
        
        // If text is very short and contains no spaces, likely a logo
        if (strlen($text) < 15 && substr_count($text, ' ') < 2) {
            return true;
        }
        
        return false;
    }

    /**
     * Generate human-readable validation reason
     * 
     * @param bool $isValid
     * @param float $relevanceScore
     * @param string $extractedText
     * @return string
     */
    private function generateValidationReason(bool $isValid, float $relevanceScore, string $extractedText): string
    {
        if ($isValid) {
            if ($relevanceScore >= 0.7) {
                return 'Gambar tervalidasi: Konten sangat relevan dengan Laporan Triwulan';
            } elseif ($relevanceScore >= 0.5) {
                return 'Gambar tervalidasi: Konten cukup relevan dengan Laporan Triwulan';
            } else {
                return 'Gambar tervalidasi: Konten memiliki relevansi minimal dengan Laporan Triwulan';
            }
        } else {
            $textPreview = substr($extractedText, 0, 100);
            
            return "Gambar tidak relevan dengan Laporan Triwulan. " .
                   "Konten yang terdeteksi tidak mengandung informasi akademik, monitoring, atau dokumentasi kegiatan yang diperlukan. " .
                   "Silakan upload gambar yang berisi:\n" .
                   "• Dokumentasi kegiatan kampus/akademik\n" .
                   "• Daftar hadir perkuliahan\n" .
                   "• Grafik/chart data monitoring\n" .
                   "• Screenshot sistem akademik\n" .
                   "• Dokumen RPS/Silabus\n" .
                   "• Dokumentasi evaluasi mutu";
        }
    }

    /**
     * Normalize text for better matching
     * 
     * @param string $text
     * @return string
     */
    private function normalizeText(string $text): string
    {
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove special characters but keep spaces
        $text = preg_replace('/[^\w\s]/u', ' ', $text);
        
        return trim($text);
    }

    /**
     * Batch validate multiple images
     * 
     * @param array $imagePaths
     * @param string $reportType
     * @return array
     */
    public function validateImagesBatch(array $imagePaths, string $reportType = 'laporan_triwulan'): array
    {
        $results = [];
        $validCount = 0;
        $invalidCount = 0;
        
        foreach ($imagePaths as $imagePath) {
            $result = $this->validateImageRelevance($imagePath, $reportType);
            $results[] = $result;
            
            if ($result['is_valid']) {
                $validCount++;
            } else {
                $invalidCount++;
            }
        }
        
        return [
            'results' => $results,
            'valid_count' => $validCount,
            'invalid_count' => $invalidCount,
            'total_count' => count($imagePaths)
        ];
    }
}
