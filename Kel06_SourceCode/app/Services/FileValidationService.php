<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * FileValidationService
 * 
 * Service untuk validasi bahwa file benar-benar dibaca dengan baik
 * dan memberikan feedback yang informatif kepada user
 */
class FileValidationService
{
    /**
     * Validasi hasil ekstraksi file
     * 
     * @param array $extraction Hasil dari TextExtractionService::extractFromFile()
     * @param string $fileName Nama file original
     * @return array ['valid' => bool, 'message' => string, 'warnings' => array]
     */
    public function validateExtraction(array $extraction, string $fileName): array
    {
        $result = [
            'valid' => false,
            'message' => '',
            'warnings' => [],
            'suggestions' => []
        ];

        // Check if extraction was successful
        if (!($extraction['success'] ?? false)) {
            $result['message'] = 'Ekstraksi file gagal.';
            
            if (isset($extraction['metadata']['error'])) {
                $result['message'] .= ' ' . $extraction['metadata']['error'];
            }
            
            $result['suggestions'] = $this->getSuggestions($fileName, $extraction);
            return $result;
        }

        // Check if text was extracted
        $text = $extraction['text'] ?? '';
        if (empty($text)) {
            $result['message'] = 'File berhasil dibaca namun tidak ada teks yang diekstrak.';
            $result['warnings'][] = 'File mungkin kosong atau berisi hanya gambar/tabel tanpa teks.';
            $result['suggestions'] = $this->getSuggestions($fileName, $extraction);
            return $result;
        }

        // Check text length
        $textLength = strlen($text);
        $wordCount = str_word_count($text);

        if ($textLength < 50) {
            $result['message'] = 'File berhasil dibaca namun konten sangat singkat.';
            $result['warnings'][] = "Hanya {$wordCount} kata diekstrak dari file.";
            $result['suggestions'][] = 'Pastikan file berisi konten yang cukup untuk analisis.';
        } else {
            $result['valid'] = true;
            $result['message'] = 'File berhasil dibaca dan siap untuk dianalisis.';
        }

        // Add extraction method info
        $method = $extraction['metadata']['extraction_method'] ?? 'unknown';
        $result['extraction_method'] = $method;
        $result['text_length'] = $textLength;
        $result['word_count'] = $wordCount;

        // Check for potential issues based on extraction method
        if ($method === 'none' || $method === '') {
            $result['valid'] = false;
            $result['message'] = 'Metode ekstraksi tidak tersedia untuk file ini.';
            $result['suggestions'] = $this->getSuggestions($fileName, $extraction);
        }

        return $result;
    }

    /**
     * Get suggestions based on file type and extraction failure
     */
    private function getSuggestions(string $fileName, array $extraction): array
    {
        $suggestions = [];
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $method = $extraction['metadata']['extraction_method'] ?? 'none';

        switch ($extension) {
            case 'pdf':
                if ($method === 'none') {
                    $suggestions[] = 'Install pdftotext (poppler-utils) atau pdfbox untuk ekstraksi PDF.';
                    $suggestions[] = 'Atau konversi PDF ke format DOCX/TXT menggunakan tools online.';
                }
                $suggestions[] = 'Pastikan PDF tidak terenkripsi atau password-protected.';
                break;

            case 'doc':
                if ($method === 'none') {
                    $suggestions[] = 'Install antiword untuk ekstraksi file DOC.';
                    $suggestions[] = 'Atau konversi DOC ke format DOCX menggunakan Microsoft Word atau LibreOffice.';
                }
                break;

            case 'docx':
                $suggestions[] = 'Pastikan file DOCX tidak corrupt.';
                $suggestions[] = 'Coba buka file di Microsoft Word atau LibreOffice untuk verifikasi.';
                break;

            case 'xlsx':
            case 'xls':
                $suggestions[] = 'Pastikan file Excel berisi data teks, bukan hanya formula atau gambar.';
                $suggestions[] = 'Coba export data ke format CSV atau DOCX untuk hasil yang lebih baik.';
                break;

            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
                $suggestions[] = 'Gambar berhasil diupload namun memerlukan OCR untuk ekstraksi teks.';
                $suggestions[] = 'Install Tesseract OCR untuk analisis teks dalam gambar.';
                $suggestions[] = 'Atau upload versi teks dari dokumen yang sama (DOCX/PDF/TXT).';
                break;

            default:
                $suggestions[] = "Format file {$extension} tidak didukung.";
                $suggestions[] = 'Gunakan format: DOCX, PDF, TXT, XLSX, atau gambar (JPG, PNG).';
                break;
        }

        return $suggestions;
    }

    /**
     * Check if system has required tools for file extraction
     */
    public function checkSystemCapabilities(): array
    {
        $capabilities = [
            'pdftotext' => $this->commandExists('pdftotext'),
            'pdfbox' => $this->commandExists('pdfbox'),
            'antiword' => $this->commandExists('antiword'),
            'tesseract' => $this->commandExists('tesseract'),
            'phpword' => class_exists('\PhpOffice\PhpWord\IOFactory'),
            'phpspreadsheet' => class_exists('\PhpOffice\PhpSpreadsheet\IOFactory'),
            'smalot_pdfparser' => class_exists('\Smalot\PdfParser\Parser'),
        ];

        Log::info('System capabilities check', $capabilities);

        return $capabilities;
    }

    /**
     * Check if command exists on system
     */
    private function commandExists(string $command): bool
    {
        $os = strtoupper(substr(PHP_OS, 0, 3));
        
        try {
            if ($os === 'WIN') {
                // Windows: use 'where' command
                exec("where {$command} 2>nul", $output, $returnCode);
            } else {
                // Linux/Mac: use 'which' command
                exec("which {$command} 2>/dev/null", $output, $returnCode);
            }
            
            return $returnCode === 0 && !empty($output);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get detailed extraction report
     */
    public function getExtractionReport(array $extraction, string $fileName): string
    {
        $report = "=== LAPORAN EKSTRAKSI FILE ===\n\n";
        $report .= "**File:** {$fileName}\n";
        $report .= "**Status:** " . ($extraction['success'] ? 'BERHASIL' : 'GAGAL') . "\n";
        $report .= "**Metode:** " . ($extraction['metadata']['extraction_method'] ?? 'unknown') . "\n";
        
        if (isset($extraction['metadata']['file_size'])) {
            $report .= "**Ukuran File:** " . round($extraction['metadata']['file_size'] / 1024, 2) . " KB\n";
        }
        
        $text = $extraction['text'] ?? '';
        $report .= "**Panjang Teks:** " . strlen($text) . " karakter\n";
        $report .= "**Jumlah Kata:** " . str_word_count($text) . " kata\n";
        
        if (isset($extraction['metadata']['error'])) {
            $report .= "**Error:** " . $extraction['metadata']['error'] . "\n";
        }
        
        $report .= "\n**Preview Teks (100 karakter pertama):**\n";
        $report .= substr($text, 0, 100) . "...\n";
        
        return $report;
    }
}
