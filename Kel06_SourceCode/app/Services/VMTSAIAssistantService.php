<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class VMTSAIAssistantService
{
    protected $unifiedAIService;
    protected $textExtractionService;
    protected $cacheService;
    protected $ragasService;

    public function __construct(
        UnifiedAIService $unifiedAIService,
        TextExtractionService $textExtractionService,
        AICacheService $cacheService,
        RAGASEvaluationService $ragasService
    ) {
        $this->unifiedAIService = $unifiedAIService;
        $this->textExtractionService = $textExtractionService;
        $this->cacheService = $cacheService;
        $this->ragasService = $ragasService;
    }

    /**
     * Process chat with file uploads (Excel, PDF, Word)
     */
    public function processChat($userMessage, $uploadedFiles = [], $conversationHistory = [])
    {
        try {
            Log::info("=== VMTS AI Assistant: Processing Chat ===", [
                'message_length' => strlen($userMessage),
                'files_count' => count($uploadedFiles)
            ]);

            $fileContents = [];

            // Process uploaded files
            foreach ($uploadedFiles as $file) {
                $fileContent = $this->extractFileContent($file);
                if ($fileContent) {
                    $fileContents[] = [
                        'filename' => $file->getClientOriginalName(),
                        'type' => $file->getClientOriginalExtension(),
                        'content' => $fileContent
                    ];
                }
            }

            // Build cache context for VMTS feature
            $cacheContext = [
                'feature' => 'vmts',  // ← IMPORTANT for evaluation tracking
                'type' => 'laporan_vmts',
                'has_files' => !empty($fileContents),
                'file_count' => count($fileContents),
                'has_conversation' => !empty($conversationHistory),
            ];

            // Check cache first (only for single messages without conversation history)
            $hasConversationHistory = !empty($conversationHistory);
            $cachedResponse = null;
            
            if (!$hasConversationHistory) {
                $cachedResponse = $this->cacheService->getCachedResponse($userMessage, $cacheContext);
                
                if ($cachedResponse && $cachedResponse['success']) {
                    Log::info('VMTS AI: Cache hit', [
                        'cache_id' => $cachedResponse['cache_id'] ?? null,
                        'usage_count' => $cachedResponse['usage_count'] ?? 0,
                        'similarity' => $cachedResponse['similarity'] ?? 1.0,
                    ]);

                    return [
                        'success' => true,
                        'response' => $cachedResponse['text'],
                        'files_processed' => count($fileContents),
                        'provider' => $cachedResponse['provider'] ?? 'cache',
                        'model' => $cachedResponse['model'] ?? 'cached',
                        'cached' => true,
                        'cache_id' => $cachedResponse['cache_id'] ?? null,
                        'usage_count' => $cachedResponse['usage_count'] ?? 0,
                    ];
                }
            }

            // Build enhanced prompt
            $enhancedPrompt = $this->buildEnhancedPrompt($userMessage, $fileContents, $conversationHistory);

            // Call AI using generateText method
            $result = $this->unifiedAIService->generateText($enhancedPrompt, [
                'temperature' => 0.7,
                'max_tokens' => 4096
            ]);

            if ($result['success']) {
                // Cache the response for future use (only if no conversation history)
                if (!$hasConversationHistory) {
                    $this->cacheService->cacheResponse(
                        $userMessage,
                        $cacheContext,
                        $result['text'],
                        $result['provider'] ?? 'unknown',
                        $result['model'] ?? 'unknown'
                    );
                    
                    Log::info('VMTS AI: Response cached', [
                        'prompt_length' => strlen($userMessage),
                        'response_length' => strlen($result['text']),
                        'provider' => $result['provider'] ?? 'unknown',
                        'model' => $result['model'] ?? 'unknown'
                    ]);
                    
                    // Create evaluation test entry for model evaluation tracking
                    try {
                        $evaluationService = app(\App\Services\AIEvaluationService::class);
                        $evaluationService->createAIResponseTest([
                            'test_name' => 'VMTS AI Assistant - ' . date('Y-m-d H:i:s'),
                            'feature' => 'vmts',
                            'query' => $userMessage,
                            'expected_response' => null, // No ground truth for user-generated content
                            'actual_response' => $result['text'],
                        ]);
                        
                        Log::info('AI Evaluation test created', [
                            'feature' => 'vmts_assistant',
                            'prompt_length' => strlen($userMessage),
                            'response_length' => strlen($result['text']),
                        ]);
                    } catch (\Exception $e) {
                        // Don't fail the request if evaluation logging fails
                        Log::warning('Failed to create AI evaluation test', [
                            'error' => $e->getMessage(),
                            'feature' => 'vmts_assistant'
                        ]);
                    }
                }

                return [
                    'success' => true,
                    'response' => $result['text'],
                    'files_processed' => count($fileContents),
                    'provider' => $result['provider'] ?? 'unknown',
                    'model' => $result['model'] ?? 'unknown',
                    'cached' => false,
                    'file_contents' => $fileContents, // For RAGAS evaluation
                ];
            } else {
                throw new \Exception($result['error'] ?? 'AI generation failed');
            }

        } catch (\Exception $e) {
            Log::error('VMTS AI Assistant Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract content from uploaded file
     */
    private function extractFileContent($file)
    {
        try {
            $extension = strtolower($file->getClientOriginalExtension());
            $tempPath = $file->store('temp_vmts', 'local');
            $fullPath = storage_path('app/' . $tempPath);

            $content = '';

            switch ($extension) {
                case 'xlsx':
                case 'xls':
                    $content = $this->extractExcelContent($fullPath);
                    break;

                case 'pdf':
                    $content = $this->extractPdfContent($fullPath);
                    break;

                case 'docx':
                case 'doc':
                    $content = $this->extractWordContent($fullPath);
                    break;

                default:
                    Log::warning("Unsupported file type: {$extension}");
            }

            // Clean up temp file
            @unlink($fullPath);

            return $content;

        } catch (\Exception $e) {
            Log::error('File extraction error', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Extract content from Excel file with enhanced structure detection
     */
    private function extractExcelContent($filePath)
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $content = "=== DATA EXCEL SURVEI VMTS ===\n\n";

            foreach ($spreadsheet->getAllSheets() as $sheetIndex => $sheet) {
                $sheetName = $sheet->getTitle();
                $content .= "SHEET: {$sheetName}\n";
                $content .= str_repeat('=', 80) . "\n\n";

                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

                // Extract all data with structure preservation
                $sheetData = [];
                for ($row = 1; $row <= $highestRow; $row++) {
                    $rowData = [];
                    $hasContent = false;
                    
                    for ($col = 1; $col <= $highestColumnIndex; $col++) {
                        $cell = $sheet->getCellByColumnAndRow($col, $row);
                        $cellValue = $cell->getCalculatedValue(); // Get calculated value for formulas
                        
                        // Get merged cell info
                        $coordinate = $cell->getCoordinate();
                        $isMerged = false;
                        foreach ($sheet->getMergeCells() as $mergeRange) {
                            if ($cell->isInRange($mergeRange)) {
                                $isMerged = true;
                                break;
                            }
                        }
                        
                        if ($cellValue !== null && $cellValue !== '') {
                            $hasContent = true;
                        }
                        
                        $rowData[] = $cellValue;
                    }
                    
                    if ($hasContent) {
                        $sheetData[] = $rowData;
                    }
                }

                // Detect and format tables
                $content .= $this->formatExcelDataAsStructuredText($sheetData, $sheetName);
                $content .= "\n\n";
            }

            Log::info('Excel content extracted with structure', [
                'sheets' => $spreadsheet->getSheetCount(),
                'content_length' => strlen($content)
            ]);

            return $content;

        } catch (\Exception $e) {
            Log::error('Excel extraction error', ['error' => $e->getMessage()]);
            return "Error membaca file Excel: " . $e->getMessage();
        }
    }

    /**
     * Format Excel data as structured text with table detection
     */
    private function formatExcelDataAsStructuredText($data, $sheetName)
    {
        if (empty($data)) {
            return "Tidak ada data.\n";
        }

        $formatted = "";
        $inTable = false;
        $tableBuffer = [];
        $currentSection = "";

        foreach ($data as $rowIndex => $row) {
            // Clean row data
            $cleanRow = array_map(function($cell) {
                if ($cell === null || $cell === '') return '';
                return trim(str_replace(["\n", "\r"], ' ', $cell));
            }, $row);

            // Remove empty cells from end
            while (!empty($cleanRow) && end($cleanRow) === '') {
                array_pop($cleanRow);
            }

            if (empty($cleanRow) || (count($cleanRow) === 1 && $cleanRow[0] === '')) {
                // Empty row - might be section separator
                if ($inTable && !empty($tableBuffer)) {
                    $formatted .= $this->formatTableData($tableBuffer);
                    $tableBuffer = [];
                    $inTable = false;
                }
                $formatted .= "\n";
                continue;
            }

            $firstCell = $cleanRow[0];
            $cellCount = count(array_filter($cleanRow, fn($c) => $c !== ''));

            // Detect section headers (bold, single cell, or specific keywords)
            if ($this->isSectionHeader($firstCell, $cellCount, count($cleanRow))) {
                if ($inTable && !empty($tableBuffer)) {
                    $formatted .= $this->formatTableData($tableBuffer);
                    $tableBuffer = [];
                    $inTable = false;
                }
                
                $formatted .= "\n## " . strtoupper($firstCell) . "\n\n";
                $currentSection = $firstCell;
                continue;
            }

            // Detect table rows (multiple cells with data)
            if ($cellCount >= 2) {
                $inTable = true;
                $tableBuffer[] = $cleanRow;
            } else {
                // Single cell content - might be paragraph or list item
                if ($inTable && !empty($tableBuffer)) {
                    $formatted .= $this->formatTableData($tableBuffer);
                    $tableBuffer = [];
                    $inTable = false;
                }
                
                if (!empty($firstCell)) {
                    // Check if it's a numbered or bulleted item
                    if (preg_match('/^[\d]+[\.\)]\s*(.+)/', $firstCell, $matches)) {
                        $formatted .= "- " . $matches[1] . "\n";
                    } elseif (preg_match('/^[•\-\*]\s*(.+)/', $firstCell, $matches)) {
                        $formatted .= "- " . $matches[1] . "\n";
                    } else {
                        $formatted .= $firstCell . "\n";
                    }
                }
            }
        }

        // Flush remaining table
        if ($inTable && !empty($tableBuffer)) {
            $formatted .= $this->formatTableData($tableBuffer);
        }

        return $formatted;
    }

    /**
     * Check if a row is a section header
     */
    private function isSectionHeader($text, $filledCells, $totalCells)
    {
        if (empty($text)) return false;

        $text = strtolower($text);
        
        // Keywords that indicate section headers
        $sectionKeywords = [
            'pendahuluan', 'metode', 'penelitian', 'hasil', 'analisis', 
            'deskriptif', 'pembahasan', 'kesimpulan', 'rekomendasi',
            'gambaran', 'responden', 'butir', 'pertanyaan', 'pola',
            'komparatif', 'laporan', 'survei', 'visi', 'misi', 'tujuan', 'sasaran'
        ];

        foreach ($sectionKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }

        // Roman numerals (I., II., III., etc.)
        if (preg_match('/^[IVX]+\.\s+/', $text)) {
            return true;
        }

        // Single cell with significant text
        if ($filledCells === 1 && strlen($text) > 10 && strlen($text) < 100) {
            return true;
        }

        return false;
    }

    /**
     * Format table data as markdown table
     */
    private function formatTableData($tableData)
    {
        if (empty($tableData)) {
            return "";
        }

        $formatted = "\n";
        
        // Determine column count
        $maxCols = 0;
        foreach ($tableData as $row) {
            $maxCols = max($maxCols, count($row));
        }

        // Format as markdown table
        foreach ($tableData as $rowIndex => $row) {
            // Pad row to max columns
            while (count($row) < $maxCols) {
                $row[] = '';
            }

            $formatted .= "| " . implode(" | ", $row) . " |\n";

            // Add separator after first row (header)
            if ($rowIndex === 0) {
                $formatted .= "|" . str_repeat(" --- |", $maxCols) . "\n";
            }
        }

        $formatted .= "\n";
        return $formatted;
    }

    /**
     * Extract content from PDF file
     */
    private function extractPdfContent($filePath)
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            // Limit content size
            if (strlen($text) > 50000) {
                $text = substr($text, 0, 50000) . "\n\n[Konten dipotong untuk efisiensi...]";
            }

            return "=== KONTEN PDF ===\n\n" . $text;

        } catch (\Exception $e) {
            Log::error('PDF extraction error', ['error' => $e->getMessage()]);
            return "Error membaca file PDF: " . $e->getMessage();
        }
    }

    /**
     * Extract content from Word file
     */
    private function extractWordContent($filePath)
    {
        try {
            $phpWord = WordIOFactory::load($filePath);
            $content = "=== KONTEN WORD (TEMPLATE REFERENSI) ===\n\n";

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text = $element->getText();
                        if (!empty($text)) {
                            $content .= $text . "\n";
                        }
                    } elseif (method_exists($element, 'getElements')) {
                        foreach ($element->getElements() as $childElement) {
                            if (method_exists($childElement, 'getText')) {
                                $text = $childElement->getText();
                                if (!empty($text)) {
                                    $content .= $text . "\n";
                                }
                            }
                        }
                    }
                }
            }

            // Limit content size
            if (strlen($content) > 50000) {
                $content = substr($content, 0, 50000) . "\n\n[Konten dipotong untuk efisiensi...]";
            }

            return $content;

        } catch (\Exception $e) {
            Log::error('Word extraction error', ['error' => $e->getMessage()]);
            return "Error membaca file Word: " . $e->getMessage();
        }
    }

    /**
     * Build system context for AI
     */
    private function buildSystemContext()
    {
        $context = "Anda adalah AI Assistant Expert untuk Gugus Jaminan Mutu (GJM) Institut Teknologi Del.\n\n";
        
        $context .= "SPESIALISASI ANDA:\n";
        $context .= "Membuat LAPORAN ANALISIS DATA HASIL SURVEI SOSIALISASI DAN PEMAHAMAN VISI-MISI FAKULTAS TEKNOLOGI INFORMASI.\n\n";
        
        $context .= "KEMAMPUAN ANALISIS:\n";
        $context .= "1. Membaca dan menganalisis data survei dari Excel dengan struktur kompleks (multiple sheets, tabel, statistik)\n";
        $context .= "2. Menginterpretasi data statistik deskriptif (mean, median, variance, skala Likert 1-6)\n";
        $context .= "3. Melakukan analisis komparatif antar program studi (Fakultas, D3TK, D3TI, D4 TRPL)\n";
        $context .= "4. Mengidentifikasi pola, tren, dan insight dari data survei\n";
        $context .= "5. Menyusun pembahasan yang mendalam dengan interpretasi kontekstual\n";
        $context .= "6. Memberikan kesimpulan dan rekomendasi yang actionable\n\n";
        
        $context .= "STRUKTUR LAPORAN YANG HARUS DIHASILKAN:\n\n";
        
        $context .= "I. PENDAHULUAN\n";
        $context .= "   - Jelaskan pentingnya visi-misi dalam pengelolaan institusi pendidikan tinggi\n";
        $context .= "   - Tujuan survei: mengetahui tingkat sosialisasi, pemahaman, dan implementasi visi-misi\n";
        $context .= "   - Responden: Seluruh civitas Fakultas Teknologi Informasi (Fakultas, D3TK, D3TI, D4 TRPL)\n\n";
        
        $context .= "II. METODE PENELITIAN\n";
        $context .= "   - Metode: Kuesioner dengan skala Likert 1-6 (1=sangat tidak setuju, 6=sangat setuju)\n";
        $context .= "   - Analisis: Statistical Package for the Social Sciences (SPSS)\n";
        $context .= "   - Perhitungan: mean, median, variance untuk setiap butir pertanyaan (P1-P10)\n\n";
        
        $context .= "III. HASIL ANALISIS DESKRIPTIF\n";
        $context .= "   1. Gambaran Umum Responden\n";
        $context .= "      - Tabel jumlah responden per unit (Fakultas, D3TK, D3TI, D4 TRPL)\n";
        $context .= "      - Rentang skala dan catatan metodologi\n\n";
        
        $context .= "   2. Analisis Per Butir Pertanyaan (P1-P10)\n";
        $context .= "      Untuk setiap pertanyaan, buat tabel dengan kolom:\n";
        $context .= "      | No | Aspek yang Dinilai | Fakultas | D3TK | D3TI | D4 TRPL | Interpretasi |\n";
        $context .= "      \n";
        $context .= "      Contoh pertanyaan:\n";
        $context .= "      - P1: Status di IT Del\n";
        $context .= "      - P2: Lama mengenal IT Del\n";
        $context .= "      - P3: Mengetahui visi-misi fakultas\n";
        $context .= "      - P4: Sumber informasi visi-misi\n";
        $context .= "      - P5: Pernah ikut sosialisasi visi-misi\n";
        $context .= "      - P6: Tingkat pemahaman visi-misi\n";
        $context .= "      - P7: Kegiatan akademik mencerminkan visi-misi\n";
        $context .= "      - P8: Aspek yang mencerminkan visi-misi\n";
        $context .= "      - P9: Dukungan visi-misi terhadap kompetensi\n";
        $context .= "      - P10: Perlu perbaikan visi-misi\n\n";
        
        $context .= "IV. PEMBAHASAN\n";
        $context .= "   1. Pola Umum\n";
        $context .= "      - Analisis tingkat pengetahuan dan pemahaman visi-misi di semua kelompok\n";
        $context .= "      - Identifikasi kelompok dengan tingkat tertinggi dan terendah\n";
        $context .= "      - Analisis efektivitas sosialisasi\n\n";
        
        $context .= "   2. Analisis Komparatif\n";
        $context .= "      Buat tabel perbandingan:\n";
        $context .= "      | Aspek | Fakultas | D3TK | D3TI | D4 TRPL | Kesimpulan |\n";
        $context .= "      \n";
        $context .= "      Aspek yang dibandingkan:\n";
        $context .= "      - Pengetahuan dasar visi-misi\n";
        $context .= "      - Sosialisasi visi-misi\n";
        $context .= "      - Pemahaman mendalam\n";
        $context .= "      - Integrasi dalam akademik\n";
        $context .= "      - Kebutuhan revisi visi-misi\n\n";
        
        $context .= "   3. Pembahasan Mendalam\n";
        $context .= "      - Interpretasi hasil per butir pertanyaan dengan konteks akademik\n";
        $context .= "      - Analisis gap antara pengetahuan dan pemahaman\n";
        $context .= "      - Evaluasi efektivitas strategi sosialisasi saat ini\n";
        $context .= "      - Identifikasi faktor-faktor yang mempengaruhi pemahaman visi-misi\n\n";
        
        $context .= "V. KESIMPULAN\n";
        $context .= "   Ringkas temuan utama dalam poin-poin:\n";
        $context .= "   1. Tingkat sosialisasi dan pemahaman visi-misi secara umum\n";
        $context .= "   2. Perbandingan antar program studi\n";
        $context .= "   3. Efektivitas integrasi visi-misi dalam kegiatan akademik\n";
        $context .= "   4. Kebutuhan perbaikan dan revisi\n\n";
        
        $context .= "VI. REKOMENDASI\n";
        $context .= "   Berikan rekomendasi yang spesifik dan actionable:\n";
        $context .= "   1. Strategi peningkatan sosialisasi (kegiatan akademik, nonakademik, media digital)\n";
        $context .= "   2. Integrasi visi-misi ke dalam kurikulum dan mata kuliah\n";
        $context .= "   3. Evaluasi berkala terhadap relevansi visi-misi\n";
        $context .= "   4. Pelatihan untuk dosen dan tenaga kependidikan\n";
        $context .= "   5. Pembangunan budaya institusional berbasis visi-misi\n\n";
        
        $context .= "ATURAN PENULISAN:\n";
        $context .= "1. Gunakan Bahasa Indonesia formal, akademik, dan profesional\n";
        $context .= "2. Setiap tabel HARUS menggunakan format Markdown yang benar\n";
        $context .= "3. Interpretasi harus mendalam, bukan hanya deskripsi angka\n";
        $context .= "4. Gunakan istilah statistik dengan tepat (mean, median, variance, rendah, sedang, tinggi)\n";
        $context .= "5. Skala Likert: 1.0-2.0 (rendah), 2.1-4.0 (sedang), 4.1-6.0 (tinggi)\n";
        $context .= "6. Setiap bagian harus substantif dan informatif\n";
        $context .= "7. Kesimpulan harus komprehensif dan rekomendasi harus spesifik\n";
        $context .= "8. Gunakan heading Markdown: # untuk judul utama, ## untuk sub-bagian, ### untuk sub-sub-bagian\n";
        $context .= "9. Jika data tidak lengkap, berikan analisis berdasarkan data yang tersedia\n\n";
        
        $context .= "FORMAT OUTPUT:\n";
        $context .= "Hasilkan laporan lengkap dalam format Markdown yang siap dikonversi ke Word.\n";
        $context .= "Pastikan semua tabel menggunakan format: | Kolom 1 | Kolom 2 | ... |\n";
        $context .= "Gunakan bold (**text**) untuk penekanan penting.\n";
        $context .= "Gunakan list (- atau 1.) untuk poin-poin.\n\n";

        return $context;
    }

    /**
     * Build enhanced prompt with file contents
     */
    private function buildEnhancedPrompt($userMessage, $fileContents, $conversationHistory)
    {
        $prompt = "";

        // Add system context at the beginning
        $prompt .= $this->buildSystemContext() . "\n\n";
        $prompt .= str_repeat('=', 80) . "\n\n";

        // Add conversation history
        if (!empty($conversationHistory)) {
            $prompt .= "=== RIWAYAT PERCAKAPAN ===\n";
            foreach ($conversationHistory as $msg) {
                $role = $msg['role'] === 'user' ? 'User' : 'Assistant';
                $prompt .= "{$role}: {$msg['content']}\n\n";
            }
            $prompt .= "=== AKHIR RIWAYAT ===\n\n";
        }

        // Add file contents
        if (!empty($fileContents)) {
            $prompt .= "=== FILE YANG DIUPLOAD ===\n\n";
            foreach ($fileContents as $file) {
                $prompt .= "Filename: {$file['filename']}\n";
                $prompt .= "Type: {$file['type']}\n\n";
                $prompt .= $file['content'] . "\n\n";
                $prompt .= str_repeat('=', 80) . "\n\n";
            }
        }

        // Add user message
        $prompt .= "=== INSTRUKSI USER ===\n";
        $prompt .= $userMessage . "\n\n";

        // Add specific instructions based on files
        $prompt .= "=== TUGAS ANDA ===\n";
        if (!empty($fileContents)) {
            $hasExcel = false;
            $hasTemplate = false;

            foreach ($fileContents as $file) {
                if (in_array($file['type'], ['xlsx', 'xls'])) {
                    $hasExcel = true;
                }
                if (in_array($file['type'], ['pdf', 'docx', 'doc'])) {
                    $hasTemplate = true;
                }
            }

            if ($hasExcel && $hasTemplate) {
                $prompt .= "ANDA MEMILIKI:\n";
                $prompt .= "1. File Excel dengan data survei VMTS (hasil kuesioner)\n";
                $prompt .= "2. File template (PDF/Word) sebagai referensi format\n\n";
                
                $prompt .= "LANGKAH-LANGKAH:\n";
                $prompt .= "1. ANALISIS DATA EXCEL:\n";
                $prompt .= "   - Identifikasi semua sheet dan tabel dalam Excel\n";
                $prompt .= "   - Ekstrak data responden (jumlah per program studi)\n";
                $prompt .= "   - Ekstrak data statistik untuk setiap butir pertanyaan (P1-P10)\n";
                $prompt .= "   - Identifikasi nilai mean/rata-rata untuk setiap program studi\n";
                $prompt .= "   - Catat interpretasi yang sudah ada di Excel\n\n";
                
                $prompt .= "2. PELAJARI TEMPLATE:\n";
                $prompt .= "   - Perhatikan struktur dan format penulisan\n";
                $prompt .= "   - Ikuti gaya bahasa dan cara penyajian data\n";
                $prompt .= "   - Gunakan format tabel yang sama\n\n";
                
                $prompt .= "3. HASILKAN LAPORAN LENGKAP:\n";
                $prompt .= "   - Gabungkan data Excel dengan format template\n";
                $prompt .= "   - Tulis semua bagian: Pendahuluan, Metode, Hasil, Pembahasan, Kesimpulan, Rekomendasi\n";
                $prompt .= "   - Buat tabel untuk setiap butir pertanyaan dengan data dari Excel\n";
                $prompt .= "   - Berikan interpretasi mendalam untuk setiap temuan\n";
                $prompt .= "   - Analisis komparatif antar program studi\n";
                $prompt .= "   - Kesimpulan yang komprehensif\n";
                $prompt .= "   - Rekomendasi yang spesifik dan actionable\n\n";
                
            } elseif ($hasExcel) {
                $prompt .= "ANDA MEMILIKI:\n";
                $prompt .= "File Excel dengan data survei VMTS\n\n";
                
                $prompt .= "TUGAS ANDA:\n";
                $prompt .= "1. Analisis SEMUA data dalam Excel:\n";
                $prompt .= "   - Baca semua sheet (Fakultas Vokasi, Perguruan Tinggi, Program Studi D4 TRPL, Program Studi D3 Teknologi Info, dll)\n";
                $prompt .= "   - Ekstrak data responden dan statistik deskriptif\n";
                $prompt .= "   - Identifikasi pola dan tren dari data\n\n";
                
                $prompt .= "2. Hasilkan laporan LENGKAP dengan struktur:\n";
                $prompt .= "   I. Pendahuluan (konteks dan tujuan survei)\n";
                $prompt .= "   II. Metode Penelitian (kuesioner, skala Likert, SPSS)\n";
                $prompt .= "   III. Hasil Analisis Deskriptif:\n";
                $prompt .= "        - Tabel gambaran umum responden\n";
                $prompt .= "        - Tabel analisis per butir pertanyaan (P1-P10) dengan interpretasi\n";
                $prompt .= "   IV. Pembahasan:\n";
                $prompt .= "        - Pola umum dari hasil survei\n";
                $prompt .= "        - Tabel analisis komparatif antar program studi\n";
                $prompt .= "        - Pembahasan mendalam dengan interpretasi kontekstual\n";
                $prompt .= "   V. Kesimpulan (4-5 poin utama)\n";
                $prompt .= "   VI. Rekomendasi (5 rekomendasi spesifik)\n\n";
                
                $prompt .= "3. PENTING:\n";
                $prompt .= "   - Setiap tabel HARUS dalam format Markdown yang benar\n";
                $prompt .= "   - Interpretasi harus mendalam, bukan hanya deskripsi angka\n";
                $prompt .= "   - Gunakan data AKTUAL dari Excel, jangan membuat data fiktif\n";
                $prompt .= "   - Jika ada nilai mean/rata-rata, gunakan untuk interpretasi (1.0-2.0=rendah, 2.1-4.0=sedang, 4.1-6.0=tinggi)\n\n";
                
            } elseif ($hasTemplate) {
                $prompt .= "ANDA MEMILIKI:\n";
                $prompt .= "File template (PDF/Word) sebagai referensi\n\n";
                
                $prompt .= "TUGAS ANDA:\n";
                $prompt .= "1. Pelajari struktur dan format dari template\n";
                $prompt .= "2. Gunakan format tersebut untuk membuat laporan\n";
                $prompt .= "3. Jika user meminta generate laporan, buat laporan dengan struktur yang sama\n\n";
            }
        } else {
            $prompt .= "Jawab pertanyaan user dengan informasi yang relevan tentang pembuatan laporan VMTS.\n";
            $prompt .= "Jika user bertanya tentang cara membuat laporan, jelaskan struktur dan isi yang diperlukan.\n\n";
        }

        $prompt .= "OUTPUT:\n";
        $prompt .= "Berikan respons dalam format Markdown yang terstruktur, profesional, dan siap dikonversi ke Word.\n";
        $prompt .= "Pastikan SEMUA tabel menggunakan format Markdown yang benar dengan separator | --- |.\n";
        $prompt .= "Gunakan heading (#, ##, ###), bold (**text**), dan list (-, 1.) dengan tepat.\n\n";

        return $prompt;
    }

    /**
     * Generate Word document from markdown content
     */
    public function generateWordDocument($markdownContent, $judul, $periode)
    {
        try {
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            
            // Set document properties
            $properties = $phpWord->getDocInfo();
            $properties->setCreator('Institut Teknologi Del - GJM');
            $properties->setTitle($judul);
            $properties->setSubject('Laporan VMTS');
            $properties->setDescription('Laporan Analisis Data Hasil Survei VMTS');

            // Add section with simple settings
            $section = $phpWord->addSection([
                'marginLeft'   => 1134,
                'marginRight'  => 1134,
                'marginTop'    => 1134,
                'marginBottom' => 1134,
            ]);

            // Define styles
            $phpWord->addFontStyle('titleStyle', [
                'bold' => true,
                'size' => 16,
                'name' => 'Arial'
            ]);

            $phpWord->addFontStyle('heading1Style', [
                'bold' => true,
                'size' => 14,
                'name' => 'Arial'
            ]);

            $phpWord->addFontStyle('heading2Style', [
                'bold' => true,
                'size' => 12,
                'name' => 'Arial'
            ]);

            $phpWord->addFontStyle('normalStyle', [
                'size' => 11,
                'name' => 'Arial'
            ]);

            $phpWord->addParagraphStyle('centerStyle', [
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceAfter' => 200
            ]);

            // Add title
            $section->addText($judul, 'titleStyle', 'centerStyle');
            $section->addText('Periode: ' . $periode, 'normalStyle', 'centerStyle');
            $section->addText('Institut Teknologi Del', 'normalStyle', 'centerStyle');
            $section->addTextBreak(2);

            // Parse markdown and add to document
            $this->parseMarkdownToWord($markdownContent, $section, $phpWord);

            // Save document
            $filename = 'Laporan_VMTS_' . str_replace(['/', ' '], ['_', '_'], $periode) . '_' . time() . '.docx';
            $filepath = storage_path('app/public/laporan_vmts/' . $filename);

            // Create directory if not exists
            if (!file_exists(dirname($filepath))) {
                mkdir(dirname($filepath), 0755, true);
            }

            $objWriter = WordIOFactory::create($phpWord, 'Word2007');
            $objWriter->save($filepath);

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'url' => asset('storage/laporan_vmts/' . $filename)
            ];

        } catch (\Exception $e) {
            Log::error('Word generation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Parse markdown content and add to Word document
     */
    private function parseMarkdownToWord($markdown, $section, $phpWord)
    {
        $lines = explode("\n", $markdown);
        $inTable = false;
        $tableData = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                if (!$inTable) {
                    $section->addTextBreak();
                }
                continue;
            }

            // Heading 1
            if (preg_match('/^#\s+(.+)$/', $line, $matches)) {
                $section->addText($matches[1], 'heading1Style');
                $section->addTextBreak();
                continue;
            }

            // Heading 2
            if (preg_match('/^##\s+(.+)$/', $line, $matches)) {
                $section->addText($matches[1], 'heading2Style');
                $section->addTextBreak();
                continue;
            }

            // Heading 3
            if (preg_match('/^###\s+(.+)$/', $line, $matches)) {
                $section->addText($matches[1], ['bold' => true, 'size' => 11]);
                $section->addTextBreak();
                continue;
            }

            // Heading 4 (#### )
            if (preg_match('/^####\s+(.+)$/', $line, $matches)) {
                $section->addText($matches[1], ['bold' => true, 'italic' => true, 'size' => 11]);
                $section->addTextBreak();
                continue;
            }

            // Heading 5+ (#####...)
            if (preg_match('/^#{5,}\s+(.+)$/', $line, $matches)) {
                $section->addText($matches[1], ['bold' => true, 'size' => 10]);
                continue;
            }

            // Table detection
            if (strpos($line, '|') !== false) {
                if (!$inTable) {
                    $inTable = true;
                    $tableData = [];
                }
                
                // Skip separator line
                if (preg_match('/^\|[\s\-:]+\|$/', $line)) {
                    continue;
                }

                $cells = array_map('trim', explode('|', trim($line, '|')));
                $tableData[] = $cells;
                continue;
            } else {
                // End of table
                if ($inTable && !empty($tableData)) {
                    $this->addTableToSection($section, $tableData);
                    $inTable = false;
                    $tableData = [];
                    $section->addTextBreak();
                }
            }

            // List items
            if (preg_match('/^[\-\*]\s+(.+)$/', $line, $matches)) {
                $section->addListItem($matches[1], 0, 'normalStyle');
                continue;
            }

            // Numbered list
            if (preg_match('/^\d+\.\s+(.+)$/', $line, $matches)) {
                $section->addListItem($matches[1], 0, 'normalStyle', null, \PhpOffice\PhpWord\Style\ListItem::TYPE_NUMBER);
                continue;
            }

            // Normal text
            $section->addText($line, 'normalStyle');
        }

        // Add remaining table if any
        if ($inTable && !empty($tableData)) {
            $this->addTableToSection($section, $tableData);
        }
    }

    /**
     * Add table to Word section
     */
    private function addTableToSection($section, $tableData)
    {
        if (empty($tableData)) {
            return;
        }

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80
        ]);

        foreach ($tableData as $rowIndex => $rowData) {
            $table->addRow();
            foreach ($rowData as $cellData) {
                $cell = $table->addCell(2000);
                
                if ($rowIndex === 0) {
                    // Header row
                    $cell->addText($cellData, ['bold' => true, 'size' => 10]);
                } else {
                    $cell->addText($cellData, ['size' => 10]);
                }
            }
        }
    }
}


    /**
     * Save laporan VMTS to database with RAGAS evaluation
     */
    public function saveLaporanWithRAGAS($content, $judul, $periode, $userMessage, $fileContents)
    {
        try {
            Log::info('Saving VMTS laporan with RAGAS evaluation', [
                'judul' => $judul,
                'periode' => $periode,
                'content_length' => strlen($content),
                'files_count' => count($fileContents),
            ]);

            // Extract contexts from file contents
            $contexts = [];
            foreach ($fileContents as $file) {
                if (!empty($file['content'])) {
                    // Split content into chunks (500 words each)
                    $chunks = $this->splitIntoChunks($file['content'], 500);
                    $contexts = array_merge($contexts, $chunks);
                }
            }

            // If no contexts from files, use content itself as context
            if (empty($contexts)) {
                $contexts = [$content];
            }

            // Evaluate RAGAS metrics (use quick evaluation for performance)
            $ragasMetrics = $this->ragasService->quickEvaluateVMTS(
                $userMessage,
                $content,
                $contexts
            );

            Log::info('RAGAS evaluation completed', [
                'overall_score' => $ragasMetrics['overall_score'],
                'contexts_count' => count($contexts),
            ]);

            // Save to database
            $laporan = \App\Models\LaporanGJM::create([
                'jenis_laporan' => 'VMTS',
                'created_by' => \Auth::id(),
                'program_studi' => 'Fakultas Teknologi Informasi',
                'ringkasan_mutu_institusi' => $judul,
                'ai_preview_draft' => $content,
                'status_laporan' => 'completed',
                'instruksi_prompt' => [
                    'periode_VMTS' => $periode,
                    'judul' => $judul,
                    'tahun' => date('Y'),
                    'user_message' => $userMessage,
                ],
                
                // RAGAS Metrics
                'ragas_faithfulness' => $ragasMetrics['faithfulness'],
                'ragas_answer_relevancy' => $ragasMetrics['answer_relevancy'],
                'ragas_context_precision' => $ragasMetrics['context_precision'],
                'ragas_context_recall' => $ragasMetrics['context_recall'],
                'ragas_context_relevancy' => $ragasMetrics['context_relevancy'],
                'ragas_overall_score' => $ragasMetrics['overall_score'],
                
                // RAG Metadata
                'rag_chunks_count' => count($contexts),
                'rag_avg_similarity' => 0.85, // Default similarity
                'rag_contexts' => array_slice($contexts, 0, 10), // Store first 10 chunks only
                'ragas_evaluation_type' => $ragasMetrics['metadata']['evaluation_type'] ?? 'heuristic',
                'ragas_evaluated_at' => now(),
            ]);

            Log::info('VMTS laporan saved with RAGAS metrics', [
                'laporan_id' => $laporan->id,
                'ragas_score' => $ragasMetrics['overall_score'],
            ]);

            return [
                'success' => true,
                'laporan_id' => $laporan->id,
                'ragas_score' => $ragasMetrics['overall_score'],
            ];

        } catch (\Exception $e) {
            Log::error('Failed to save VMTS laporan with RAGAS', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Split text into chunks
     */
    private function splitIntoChunks($text, $wordsPerChunk = 500)
    {
        $words = preg_split('/\s+/', $text);
        $chunks = [];
        $currentChunk = [];

        foreach ($words as $word) {
            $currentChunk[] = $word;
            
            if (count($currentChunk) >= $wordsPerChunk) {
                $chunks[] = implode(' ', $currentChunk);
                $currentChunk = [];
            }
        }

        // Add remaining words
        if (!empty($currentChunk)) {
            $chunks[] = implode(' ', $currentChunk);
        }

        return $chunks;
    }
}
