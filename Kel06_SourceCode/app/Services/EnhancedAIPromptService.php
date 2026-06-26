<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * EnhancedAIPromptService
 * 
 * Service untuk membuat prompt yang lebih baik dan komprehensif
 * agar AI dapat membaca file dengan lebih akurat dan detail
 */
class EnhancedAIPromptService
{
    protected $enhancedExtraction;
    protected $claudeService;

    public function __construct(
        EnhancedFileExtractionService $enhancedExtraction,
        ClaudeAIService $claudeService
    ) {
        $this->enhancedExtraction = $enhancedExtraction;
        $this->claudeService = $claudeService;
    }

    /**
     * Build comprehensive system prompt untuk membaca file
     */
    public function buildComprehensiveSystemPrompt(
        string $reportType = 'triwulan',
        string $templateStructure = '',
        array $gkmContext = []
    ): string {
        $prompt = "Anda adalah AI Assistant untuk Gugus Jaminan Mutu (GJM) Institut Teknologi Del.\n\n";
        
        $prompt .= "PERAN ANDA:\n";
        $prompt .= "- Membaca dan memahami dokumen yang diupload dengan SANGAT DETAIL\n";
        $prompt .= "- Mengekstrak SEMUA informasi penting dari dokumen\n";
        $prompt .= "- Membuat ringkasan dan draft laporan yang KOMPREHENSIF\n";
        $prompt .= "- Memastikan TIDAK ADA informasi dari dokumen yang terlewat\n\n";

        $prompt .= "JENIS LAPORAN: " . ucfirst($reportType) . "\n\n";

        if (!empty($templateStructure)) {
            $prompt .= "STRUKTUR TEMPLATE YANG HARUS DIIKUTI:\n";
            $prompt .= $templateStructure . "\n\n";
            $prompt .= "PENTING: Anda HARUS mengikuti struktur template di atas dengan KETAT.\n";
            $prompt .= "Gunakan markdown heading level 1 (#) untuk setiap bagian utama sesuai template.\n";
            $prompt .= "Jangan menambah atau mengurangi bagian dari template.\n";
            $prompt .= "Isi setiap bagian dengan konten yang relevan berdasarkan dokumen yang diupload.\n\n";
        } else {
            $prompt .= "STRUKTUR WAJIB (gunakan markdown heading level 1 #):\n";
            $prompt .= "# LATAR BELAKANG\n";
            $prompt .= "# DASAR\n";
            $prompt .= "# TUJUAN\n";
            $prompt .= "# RUANG LINGKUP\n";
            $prompt .= "# PROGRAM KERJA\n";
            $prompt .= "# PELAKSANAAN\n";
            $prompt .= "# HAMBATAN\n";
            $prompt .= "# PEMECAHAN MASALAH\n";
            $prompt .= "# EVALUASI\n";
            $prompt .= "# SARAN\n\n";
        }

        if (!empty($gkmContext)) {
            $prompt .= "KONTEKS LAPORAN GKM BULANAN (untuk referensi):\n";
            $prompt .= json_encode($gkmContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        }

        $prompt .= "INSTRUKSI PENTING:\n";
        $prompt .= "1. BACA DOKUMEN DENGAN SANGAT DETAIL - ekstrak SEMUA informasi penting\n";
        $prompt .= "2. JANGAN ABAIKAN DATA APAPUN - termasuk tabel, grafik, angka, tanggal, nama\n";
        $prompt .= "3. JANGAN KATAKAN 'tidak bisa mengakses' - dokumen SUDAH diberikan\n";
        $prompt .= "4. GUNAKAN BAHASA INDONESIA FORMAL DAN PROFESIONAL\n";
        $prompt .= "5. SEMUA KONTEN HARUS BERDASARKAN DOKUMEN YANG DIBERIKAN\n";
        $prompt .= "6. JANGAN GUNAKAN KODE PERIODE (Q1, Q2, Q3, Q4) - gunakan nama lengkap\n";
        $prompt .= "7. BUAT RINGKASAN YANG SANGAT DETAIL DAN KOMPREHENSIF\n";
        $prompt .= "8. EKSTRAK SEMUA DATA NUMERIK, TANGGAL, DAN INFORMASI SPESIFIK\n";
        $prompt .= "9. JIKA ADA TABEL ATAU DATA TERSTRUKTUR, JELASKAN DENGAN DETAIL\n";
        $prompt .= "10. PASTIKAN DRAFT LAPORAN MENCAKUP SEMUA POIN DARI DOKUMEN\n\n";

        return $prompt;
    }

    /**
     * Build user message dengan file details yang komprehensif
     */
    public function buildComprehensiveUserMessage(
        string $extractedText,
        array $fileDetails,
        string $userInstructions = ''
    ): string {
        $message = "DOKUMEN TELAH BERHASIL DIBACA DAN DIEKSTRAK\n";
        $message .= "=" . str_repeat("=", 60) . "\n\n";

        // File information
        $message .= "INFORMASI FILE:\n";
        $message .= "- Nama: " . ($fileDetails['file_info']['name'] ?? 'Unknown') . "\n";
        $message .= "- Tipe: " . ($fileDetails['file_info']['extension'] ?? 'Unknown') . "\n";
        $message .= "- Ukuran: " . ($fileDetails['file_info']['size_formatted'] ?? 'Unknown') . "\n";
        $message .= "- Dimodifikasi: " . ($fileDetails['file_info']['modified'] ?? 'Unknown') . "\n\n";

        // File structure
        if (!empty($fileDetails['structure'])) {
            $message .= "STRUKTUR FILE:\n";
            $message .= $this->formatStructure($fileDetails['structure']) . "\n\n";
        }

        // File details
        if (!empty($fileDetails['details'])) {
            $message .= "DETAIL FILE:\n";
            $message .= $this->formatDetails($fileDetails['details']) . "\n\n";
        }

        // Extracted content
        $message .= "KONTEN LENGKAP DOKUMEN:\n";
        $message .= "=" . str_repeat("=", 60) . "\n";
        $message .= $extractedText . "\n";
        $message .= "=" . str_repeat("=", 60) . "\n\n";

        // User instructions
        if (!empty($userInstructions)) {
            $message .= "INSTRUKSI DARI USER:\n";
            $message .= $userInstructions . "\n\n";
        }

        // Task
        $message .= "TUGAS ANDA:\n";
        $message .= "1. Baca dan pahami SEMUA isi dokumen di atas\n";
        $message .= "2. Buat RINGKASAN DOKUMEN yang SANGAT DETAIL\n";
        $message .= "3. Buat POIN-POIN UTAMA dari dokumen\n";
        $message .= "4. Buat DAFTAR DATA PENTING (angka, tanggal, nama, dll)\n";
        $message .= "5. Buat DRAFT LAPORAN dengan struktur yang telah ditentukan\n\n";

        $message .= "MULAI SEKARANG DENGAN HEADING # RINGKASAN DOKUMEN\n";

        return $message;
    }

    /**
     * Process file dengan enhanced extraction dan AI
     */
    public function processFileWithEnhancedExtraction(
        string $filePath,
        string $systemContext,
        string $userInstructions = '',
        int $maxTokens = 4096
    ): array {
        Log::info("=== Enhanced AI Processing ===", ['file' => $filePath]);

        try {
            // 1. Extract file dengan detail
            $fileDetails = $this->enhancedExtraction->extractWithDetails($filePath);

            if (!$fileDetails['success']) {
                return [
                    'success' => false,
                    'error' => $fileDetails['error'] ?? 'Extraction failed',
                    'ai_response' => null,
                    'file_details' => $fileDetails
                ];
            }

            // 2. Build comprehensive user message
            $userMessage = $this->buildComprehensiveUserMessage(
                $fileDetails['text'],
                $fileDetails,
                $userInstructions
            );

            // 3. Call Claude AI
            $aiResponse = $this->claudeService->chat($systemContext, [
                ['role' => 'user', 'content' => $userMessage]
            ], $maxTokens);

            if (empty($aiResponse)) {
                return [
                    'success' => false,
                    'error' => 'AI failed to generate response',
                    'ai_response' => null,
                    'file_details' => $fileDetails
                ];
            }

            // 4. Validate AI response
            if ($this->containsFileAccessError($aiResponse)) {
                Log::warning('AI returned file access error, retrying');
                
                // Retry dengan prompt yang lebih tegas
                $retryMessage = "INSTRUKSI TEGAS:\n\n";
                $retryMessage .= "Konten dokumen SUDAH diberikan di atas. Kamu HARUS memproses konten ini.\n";
                $retryMessage .= "JANGAN katakan tidak bisa mengakses file. File SUDAH diekstrak.\n\n";
                $retryMessage .= "LANGSUNG buat:\n";
                $retryMessage .= "1. Ringkasan dokumen yang SANGAT DETAIL\n";
                $retryMessage .= "2. Poin-poin utama\n";
                $retryMessage .= "3. Data penting (angka, tanggal, nama)\n";
                $retryMessage .= "4. Draft laporan dengan struktur yang ditentukan\n\n";
                $retryMessage .= "Mulai dengan heading # RINGKASAN DOKUMEN";

                $aiResponse = $this->claudeService->chat($systemContext, [
                    ['role' => 'user', 'content' => $retryMessage]
                ], $maxTokens);
            }

            Log::info("Enhanced processing completed", [
                'file' => basename($filePath),
                'ai_response_length' => strlen($aiResponse ?? ''),
                'file_details_count' => count($fileDetails['details'])
            ]);

            return [
                'success' => true,
                'error' => null,
                'ai_response' => $aiResponse,
                'file_details' => $fileDetails,
                'extraction_summary' => [
                    'text_length' => safe_strlen($fileDetails['text']),
                    'structure_items' => count($fileDetails['structure']),
                    'details_items' => count($fileDetails['details']),
                    'file_size' => $fileDetails['file_info']['size'] ?? 0
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Enhanced processing failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $filePath
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'ai_response' => null,
                'file_details' => []
            ];
        }
    }

    /**
     * Format structure untuk display
     */
    private function formatStructure(array $structure): string
    {
        $formatted = '';

        foreach ($structure as $item) {
            if (isset($item['sheet_name'])) {
                // Excel sheet
                $formatted .= "Sheet: {$item['sheet_name']}\n";
                $formatted .= "  - Baris: {$item['rows']}\n";
                $formatted .= "  - Kolom: {$item['columns']}\n";
                if (!empty($item['headers'])) {
                    $formatted .= "  - Header: " . implode(', ', $item['headers']) . "\n";
                }
            } elseif (isset($item['type']) && $item['type'] === 'image') {
                // Image
                $formatted .= "Gambar: {$item['width']}x{$item['height']} ({$item['mime_type']})\n";
            } elseif (isset($item['total_pages'])) {
                // PDF
                $formatted .= "PDF: {$item['total_pages']} halaman\n";
            }
        }

        return $formatted;
    }

    /**
     * Format details untuk display
     */
    private function formatDetails(array $details): string
    {
        $formatted = '';

        foreach ($details as $detail) {
            if (isset($detail['sheet_name'])) {
                // Excel details
                $formatted .= "Sheet: {$detail['sheet_name']}\n";
                $formatted .= "  Statistik:\n";
                $formatted .= "  - Total Baris: {$detail['statistics']['total_rows']}\n";
                $formatted .= "  - Total Kolom: {$detail['statistics']['total_columns']}\n";
                $formatted .= "  - Sel Terisi: {$detail['statistics']['non_empty_cells']}\n";
                
                if (!empty($detail['sample_data'])) {
                    $formatted .= "  Sample Data (5 baris pertama):\n";
                    foreach ($detail['sample_data'] as $row) {
                        $formatted .= "    " . implode(' | ', $row) . "\n";
                    }
                }
            } elseif (isset($detail['type']) && $detail['type'] === 'image_info') {
                // Image details
                $formatted .= "Dimensi: {$detail['width']}x{$detail['height']}\n";
                $formatted .= "Bits: {$detail['bits']}\n";
            } elseif (isset($detail['type']) && $detail['type'] === 'pdf_metadata') {
                // PDF metadata
                $formatted .= "Judul: {$detail['title']}\n";
                $formatted .= "Penulis: {$detail['author']}\n";
                $formatted .= "Subjek: {$detail['subject']}\n";
            }
        }

        return $formatted;
    }

    /**
     * Check if AI response contains file access error
     */
    private function containsFileAccessError(string $response): bool
    {
        $errorPatterns = [
            'tidak dapat melihat',
            'tidak dapat mengakses',
            'tidak memiliki kemampuan',
            'tidak bisa melihat',
            'tidak bisa mengakses',
            'model bahasa yang berjalan di server',
            'tidak memiliki akses',
            'mohon maaf, saya tidak dapat',
            'i cannot see',
            'i cannot access',
            'i don\'t have access',
        ];

        $lowerResponse = strtolower($response);
        foreach ($errorPatterns as $pattern) {
            if (strpos($lowerResponse, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}
