<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class FileRelevanceValidationService
{
    protected $aiService;
    protected $textExtractionService;
    protected $ocrService;

    public function __construct(
        UnifiedAIService $aiService,
        TextExtractionService $textExtractionService,
        OCRService $ocrService
    ) {
        $this->aiService = $aiService;
        $this->textExtractionService = $textExtractionService;
        $this->ocrService = $ocrService;
    }

    /**
     * Validate if uploaded files are relevant to the report type
     * 
     * @param array $files Array of uploaded files
     * @param string $reportType Type of report: 'triwulan' or 'semester'
     * @return array ['valid' => bool, 'message' => string, 'invalid_files' => array]
     */
    public function validateFileRelevance(array $files, string $reportType): array
    {
        $invalidFiles = [];
        $extractedContents = [];

        foreach ($files as $file) {
            $fileName = $file->getClientOriginalName();
            $fileExtension = strtolower($file->getClientOriginalExtension());
            
            try {
                // Extract content based on file type
                $content = $this->extractFileContent($file, $fileExtension);
                
                if (empty($content)) {
                    Log::warning('File content is empty, skipping validation', [
                        'filename' => $fileName
                    ]);
                    continue;
                }

                $extractedContents[] = [
                    'filename' => $fileName,
                    'content' => $content
                ];

            } catch (\Exception $e) {
                Log::error('Failed to extract file content for validation', [
                    'filename' => $fileName,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        // If no content could be extracted, skip validation
        if (empty($extractedContents)) {
            return [
                'valid' => true,
                'message' => 'No content to validate',
                'invalid_files' => []
            ];
        }

        // Use AI to validate relevance
        $validationResult = $this->validateWithAI($extractedContents, $reportType);

        return $validationResult;
    }

    /**
     * Extract content from file based on type
     */
    protected function extractFileContent($file, string $extension): string
    {
        // For images, use OCR
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $tempPath = $file->store('temp_validation', 'local');
            $fullPath = storage_path('app/' . $tempPath);
            
            try {
                $ocrResult = $this->ocrService->extractText($fullPath);
                $content = '';
                
                if ($ocrResult['success'] && !empty($ocrResult['text'])) {
                    $content = is_array($ocrResult['text']) 
                        ? json_encode($ocrResult['text']) 
                        : (string)$ocrResult['text'];
                }
                
                return $content;
            } finally {
                // Clean up temp file
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }
        }
        
        // For documents (PDF, DOCX, XLSX, etc.)
        if (in_array($extension, ['pdf', 'docx', 'doc', 'xlsx', 'xls', 'txt'])) {
            $tempPath = $file->store('temp_validation', 'local');
            $fullPath = storage_path('app/' . $tempPath);
            
            try {
                $result = $this->textExtractionService->extractFromFile($fullPath);
                return $result['text'] ?? '';
            } finally {
                // Clean up temp file
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }
        }

        return '';
    }

    /**
     * Validate file relevance using AI
     */
    protected function validateWithAI(array $extractedContents, string $reportType): array
    {
        $reportTypeLabel = $reportType === 'triwulan' ? 'Laporan Triwulan' : 'Laporan Semester';
        
        // Build validation prompt
        $validationPrompt = "Anda adalah validator dokumen untuk sistem pelaporan Gugus Jaminan Mutu (GJM).\n\n";
        $validationPrompt .= "TUGAS: Validasi apakah dokumen yang diupload RELEVAN dengan pembuatan {$reportTypeLabel}.\n\n";
        
        $validationPrompt .= "KRITERIA DOKUMEN RELEVAN untuk {$reportTypeLabel}:\n";
        
        if ($reportType === 'triwulan') {
            $validationPrompt .= "- Berisi data/informasi kegiatan dalam periode 3 bulan (triwulan)\n";
            $validationPrompt .= "- Berisi laporan kegiatan, program kerja, atau evaluasi triwulanan\n";
            $validationPrompt .= "- Berisi data monitoring, statistik, atau capaian dalam periode triwulan\n";
            $validationPrompt .= "- Berisi dokumentasi kegiatan GJM atau unit terkait\n";
            $validationPrompt .= "- Berisi informasi tentang mutu pendidikan, akreditasi, atau penjaminan mutu\n";
        } else {
            $validationPrompt .= "- Berisi data/informasi kegiatan dalam periode semester (6 bulan)\n";
            $validationPrompt .= "- Berisi laporan kegiatan, program kerja, atau evaluasi semesteran\n";
            $validationPrompt .= "- Berisi data monitoring, statistik, atau capaian dalam periode semester\n";
            $validationPrompt .= "- Berisi dokumentasi kegiatan GJM atau unit terkait\n";
            $validationPrompt .= "- Berisi informasi tentang mutu pendidikan, akreditasi, atau penjaminan mutu\n";
        }
        
        $validationPrompt .= "\nKRITERIA DOKUMEN TIDAK RELEVAN:\n";
        $validationPrompt .= "- Gambar/foto random yang tidak ada hubungannya dengan laporan (selfie, meme, landscape, dll)\n";
        $validationPrompt .= "- Dokumen pribadi (CV, surat lamaran, KTP, dll)\n";
        $validationPrompt .= "- Data keuangan pribadi atau tidak terkait institusi\n";
        $validationPrompt .= "- Dokumen hiburan (resep masakan, artikel lifestyle, dll)\n";
        $validationPrompt .= "- File Excel/spreadsheet yang tidak berisi data institusi atau kegiatan\n";
        $validationPrompt .= "- Dokumen yang sama sekali tidak terkait dengan pendidikan tinggi atau penjaminan mutu\n\n";
        
        $validationPrompt .= "DOKUMEN YANG DIUPLOAD:\n\n";
        
        foreach ($extractedContents as $idx => $fileData) {
            $content = $fileData['content'];
            
            // Limit content length for validation
            $maxLength = 2000;
            if (strlen($content) > $maxLength) {
                $content = substr($content, 0, $maxLength) . '...';
            }
            
            $validationPrompt .= "File " . ($idx + 1) . ": {$fileData['filename']}\n";
            $validationPrompt .= "Konten:\n```\n{$content}\n```\n\n";
        }
        
        $validationPrompt .= "\nINSTRUKSI:\n";
        $validationPrompt .= "1. Analisis setiap file apakah RELEVAN atau TIDAK RELEVAN dengan {$reportTypeLabel}\n";
        $validationPrompt .= "2. Berikan penilaian untuk SETIAP file\n";
        $validationPrompt .= "3. Format output JSON:\n";
        $validationPrompt .= "{\n";
        $validationPrompt .= "  \"overall_valid\": true/false,\n";
        $validationPrompt .= "  \"files\": [\n";
        $validationPrompt .= "    {\n";
        $validationPrompt .= "      \"filename\": \"nama_file\",\n";
        $validationPrompt .= "      \"relevant\": true/false,\n";
        $validationPrompt .= "      \"reason\": \"alasan singkat\"\n";
        $validationPrompt .= "    }\n";
        $validationPrompt .= "  ],\n";
        $validationPrompt .= "  \"message\": \"pesan untuk user\"\n";
        $validationPrompt .= "}\n\n";
        $validationPrompt .= "PENTING: Jika ADA SATU SAJA file yang tidak relevan, set overall_valid = false\n";
        $validationPrompt .= "Berikan output HANYA dalam format JSON, tanpa teks tambahan.";

        try {
            // Call AI service
            $result = $this->aiService->generateText($validationPrompt, [
                'temperature' => 0.1, // Low temperature for consistent validation
                'max_tokens' => 1000,
            ]);

            if (!$result['success']) {
                Log::error('AI validation failed', [
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                
                // If AI fails, allow the upload (fail-open approach)
                return [
                    'valid' => true,
                    'message' => 'Validasi otomatis tidak tersedia, file diterima',
                    'invalid_files' => []
                ];
            }

            // Parse AI response
            $aiResponse = $result['text'];
            
            // Extract JSON from response (in case AI adds extra text)
            if (preg_match('/\{[\s\S]*\}/', $aiResponse, $matches)) {
                $jsonResponse = $matches[0];
                $validation = json_decode($jsonResponse, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    $invalidFiles = [];
                    
                    if (isset($validation['files']) && is_array($validation['files'])) {
                        foreach ($validation['files'] as $fileValidation) {
                            if (isset($fileValidation['relevant']) && !$fileValidation['relevant']) {
                                $invalidFiles[] = [
                                    'filename' => $fileValidation['filename'] ?? 'Unknown',
                                    'reason' => $fileValidation['reason'] ?? 'Tidak relevan'
                                ];
                            }
                        }
                    }
                    
                    $isValid = $validation['overall_valid'] ?? true;
                    $message = $validation['message'] ?? '';
                    
                    Log::info('File relevance validation completed', [
                        'report_type' => $reportType,
                        'valid' => $isValid,
                        'invalid_count' => count($invalidFiles)
                    ]);
                    
                    return [
                        'valid' => $isValid,
                        'message' => $message,
                        'invalid_files' => $invalidFiles
                    ];
                }
            }
            
            // If JSON parsing fails, log and allow upload
            Log::warning('Failed to parse AI validation response', [
                'response' => substr($aiResponse, 0, 500)
            ]);
            
            return [
                'valid' => true,
                'message' => 'Validasi tidak dapat diproses, file diterima',
                'invalid_files' => []
            ];

        } catch (\Exception $e) {
            Log::error('File relevance validation exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fail-open: allow upload if validation fails
            return [
                'valid' => true,
                'message' => 'Validasi tidak tersedia, file diterima',
                'invalid_files' => []
            ];
        }
    }
}
