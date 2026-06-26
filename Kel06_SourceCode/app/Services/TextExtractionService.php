<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Service untuk ekstraksi text dari berbagai format file
 */
class TextExtractionService
{
    /**
     * Extract text dari file
     */
    public function extractFromFile(string $filePath): array
    {
        Log::info("=== Text Extraction ===", ['file' => $filePath]);

        // Normalize path - handle both storage path and public path
        $fullPath = $this->getFullPath($filePath);

        if (!file_exists($fullPath)) {
            Log::error("File not found", [
                'original_path' => $filePath,
                'full_path' => $fullPath
            ]);
            return [
                'text' => '',
                'metadata' => [
                    'file_path' => $filePath,
                    'error' => "File not found: {$filePath}"
                ],
                'success' => false
            ];
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        $result = [
            'text' => '',
            'metadata' => [
                'file_path' => $filePath,
                'full_path' => $fullPath,
                'file_extension' => $extension,
                'extraction_method' => ''
            ],
            'success' => false
        ];

        try {
            switch ($extension) {
                case 'txt':
                case 'md':
                    $result = $this->extractFromText($fullPath);
                    break;

                case 'docx':
                    $result = $this->extractFromDocx($fullPath);
                    break;

                case 'pdf':
                    $result = $this->extractFromPdf($fullPath);
                    break;

                case 'doc':
                    $result = $this->extractFromDoc($fullPath);
                    break;

                case 'xlsx':
                case 'xls':
                    $result = $this->extractFromExcel($fullPath);
                    break;

                // Image files - use OCR
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'gif':
                case 'bmp':
                case 'webp':
                case 'tiff':
                case 'tif':
                    $result = $this->extractFromImage($fullPath);
                    break;

                default:
                    Log::warning("Unsupported file type", ['extension' => $extension]);
                    $result['metadata']['error'] = "Unsupported file type: {$extension}";
            }

            Log::info("Text extraction completed", [
                'success' => $result['success'],
                'text_length' => strlen(is_string($result['text']) ? $result['text'] : json_encode($result['text'])),
                'method' => $result['metadata']['extraction_method']
            ]);

        } catch (\Exception $e) {
            Log::error("Text extraction failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $filePath
            ]);
            $result['metadata']['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Get full file path from storage path
     */
    private function getFullPath(string $filePath): string
    {
        // If already absolute path
        if (file_exists($filePath)) {
            return $filePath;
        }

        // Try storage/app path
        $storagePath = storage_path('app/' . $filePath);
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        // Try public storage path
        $publicPath = storage_path('app/public/' . $filePath);
        if (file_exists($publicPath)) {
            return $publicPath;
        }

        // Try public path directly
        $directPublicPath = public_path('storage/' . $filePath);
        if (file_exists($directPublicPath)) {
            return $directPublicPath;
        }

        // Return original if nothing found
        return $filePath;
    }

    /**
     * Extract dari text file
     */
    private function extractFromText(string $fullPath): array
    {
        $content = file_get_contents($fullPath);

        return [
            'text' => $content,
            'metadata' => [
                'file_path' => $fullPath,
                'extraction_method' => 'direct_read',
                'encoding' => mb_detect_encoding($content)
            ],
            'success' => true
        ];
    }

    /**
     * Extract dari DOCX menggunakan PhpWord
     */
    private function extractFromDocx(string $fullPath): array
    {
        // Check if PhpWord is available
        if (!class_exists('\PhpOffice\PhpWord\IOFactory')) {
            return $this->extractFromDocxFallback($fullPath);
        }

        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($fullPath);
            $text = '';

            foreach ($phpWord->getSections() as $section) {
                $text .= $this->extractTextFromElements($section->getElements());
            }

            return [
                'text' => $text,
                'metadata' => [
                    'file_path' => $fullPath,
                    'extraction_method' => 'phpword'
                ],
                'success' => true
            ];

        } catch (\Exception $e) {
            Log::warning("PhpWord extraction failed, trying fallback", ['error' => $e->getMessage()]);
            return $this->extractFromDocxFallback($fullPath);
        }
    }

    /**
     * Recursively extract text from PhpWord elements
     */
    private function extractTextFromElements($elements): string
    {
        $text = '';

        foreach ($elements as $element) {
            $elementClass = get_class($element);

            // Handle different element types
            if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                $text .= $element->getText() . ' ';
            }
            elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                // TextRun contains multiple text elements
                foreach ($element->getElements() as $textElement) {
                    if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
                        $text .= $textElement->getText() . ' ';
                    }
                }
                $text .= "\n";
            }
            elseif ($element instanceof \PhpOffice\PhpWord\Element\TextBreak) {
                $text .= "\n";
            }
            elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                // Extract text from table
                foreach ($element->getRows() as $row) {
                    foreach ($row->getCells() as $cell) {
                        $text .= $this->extractTextFromElements($cell->getElements()) . ' | ';
                    }
                    $text .= "\n";
                }
            }
            elseif ($element instanceof \PhpOffice\PhpWord\Element\ListItem) {
                $text .= '- ' . $element->getTextObject()->getText() . "\n";
            }
            elseif (method_exists($element, 'getElements')) {
                // Recursively handle containers
                $text .= $this->extractTextFromElements($element->getElements());
            }
            elseif (method_exists($element, 'getText')) {
                // Fallback for any element with getText method
                try {
                    $elementText = $element->getText();
                    if (is_string($elementText)) {
                        $text .= $elementText . ' ';
                    }
                } catch (\Exception $e) {
                    // Skip elements that can't be converted to text
                    continue;
                }
            }
        }

        return $text;
    }

    /**
     * Fallback extraction untuk DOCX (unzip dan parse XML)
     */
    private function extractFromDocxFallback(string $fullPath): array
    {
        $zip = new \ZipArchive();
        $text = '';

        if ($zip->open($fullPath) === true) {
            $xml = $zip->getFromName('word/document.xml');

            if ($xml) {
                // Parse XML dan extract text
                $xml = simplexml_load_string($xml);
                $xml->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

                $textNodes = $xml->xpath('//w:t');
                foreach ($textNodes as $textNode) {
                    $text .= (string)$textNode . ' ';
                }

                // Clean up
                $text = preg_replace('/\s+/', ' ', $text);
                $text = str_replace(' . ', ".\n", $text);
            }

            $zip->close();
        }

        return [
            'text' => $text,
            'metadata' => [
                'file_path' => $fullPath,
                'extraction_method' => 'zip_xml_parse'
            ],
            'success' => !empty($text)
        ];
    }

    /**
     * Extract dari PDF
     * Mencoba berbagai metode: pdftotext, smalot/pdfparser, atau fallback
     * Windows-compatible version
     */
    private function extractFromPdf(string $fullPath): array
    {
        // Method 1: Try using smalot/pdfparser library (works on all platforms)
        if (class_exists('\Smalot\PdfParser\Parser')) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($fullPath);
                $text = $pdf->getText();

                if (!empty($text)) {
                    return [
                        'text' => $text,
                        'metadata' => [
                            'file_path' => $fullPath,
                            'extraction_method' => 'smalot_pdfparser',
                            'file_size' => filesize($fullPath)
                        ],
                        'success' => true
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('smalot/pdfparser extraction failed', ['error' => $e->getMessage()]);
            }
        }

        // Method 2: Try using pdftotext command if available
        if ($this->commandExists('pdftotext')) {
            try {
                $outputPath = $fullPath . '.txt';
                $command = "pdftotext -layout \"{$fullPath}\" \"{$outputPath}\"";
                exec($command, $output, $returnCode);

                if ($returnCode === 0 && file_exists($outputPath)) {
                    $text = file_get_contents($outputPath);
                    @unlink($outputPath);

                    if (!empty($text)) {
                        return [
                            'text' => $text,
                            'metadata' => [
                                'file_path' => $fullPath,
                                'extraction_method' => 'pdftotext',
                                'file_size' => filesize($fullPath)
                            ],
                            'success' => true
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('pdftotext extraction failed', ['error' => $e->getMessage()]);
            }
        }

        // Method 3: Try using pdfbox command if available
        if ($this->commandExists('pdfbox')) {
            try {
                $outputPath = $fullPath . '.txt';
                $command = "pdfbox ExtractText \"{$fullPath}\" \"{$outputPath}\"";
                exec($command, $output, $returnCode);

                if ($returnCode === 0 && file_exists($outputPath)) {
                    $text = file_get_contents($outputPath);
                    @unlink($outputPath);

                    if (!empty($text)) {
                        return [
                            'text' => $text,
                            'metadata' => [
                                'file_path' => $fullPath,
                                'extraction_method' => 'pdfbox',
                                'file_size' => filesize($fullPath)
                            ],
                            'success' => true
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('pdfbox extraction failed', ['error' => $e->getMessage()]);
            }
        }

        // Fallback: Return error with helpful message
        $fileSize = filesize($fullPath);
        $errorMsg = 'PDF extraction tidak tersedia di sistem ini. ';

        $errorMsg .= 'Solusi: ';
        $errorMsg .= '(1) Install smalot/pdfparser: composer require smalot/pdfparser (RECOMMENDED untuk Windows), ';
        $errorMsg .= '(2) Install pdftotext (poppler-utils), ';
        $errorMsg .= '(3) Konversi PDF ke DOCX/TXT menggunakan online converter.';

        Log::warning('PDF extraction failed - no method available', [
            'file' => $fullPath,
            'file_size' => $fileSize,
            'pdftotext_available' => $this->commandExists('pdftotext'),
            'pdfbox_available' => $this->commandExists('pdfbox'),
            'smalot_available' => class_exists('\Smalot\PdfParser\Parser'),
            'os' => PHP_OS
        ]);

        return [
            'text' => '',
            'metadata' => [
                'file_path' => $fullPath,
                'extraction_method' => 'none',
                'error' => $errorMsg,
                'file_size' => $fileSize,
                'os' => PHP_OS
            ],
            'success' => false
        ];
    }

    /**
     * Extract dari DOC (old format)
     */
    private function extractFromDoc(string $fullPath): array
    {
        // Try using antiword command if available
        if ($this->commandExists('antiword')) {
            exec("antiword '{$fullPath}'", $output, $returnCode);

            if ($returnCode === 0) {
                $text = implode("\n", $output);

                return [
                    'text' => $text,
                    'metadata' => [
                        'file_path' => $fullPath,
                        'extraction_method' => 'antiword'
                    ],
                    'success' => true
                ];
            }
        }

        return [
            'text' => '',
            'metadata' => [
                'file_path' => $fullPath,
                'extraction_method' => 'none',
                'error' => 'DOC extraction requires antiword command'
            ],
            'success' => false
        ];
    }

    /**
     * Extract dari Excel (XLSX/XLS)
     */
    private function extractFromExcel(string $fullPath): array
    {
        // Check if PhpSpreadsheet is available
        if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            return [
                'text' => '',
                'metadata' => [
                    'file_path' => $fullPath,
                    'extraction_method' => 'none',
                    'error' => 'PhpSpreadsheet library not available'
                ],
                'success' => false
            ];
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $text = '';

            // Iterate through all sheets
            foreach ($spreadsheet->getSheetNames() as $sheetName) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                $text .= "=== SHEET: {$sheetName} ===\n";

                // Get the highest row and column
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Iterate through rows
                for ($row = 1; $row <= $highestRow; $row++) {
                    $rowData = [];

                    // Iterate through columns
                    for ($col = 'A'; $col <= $highestColumn; $col++) {
                        $cell = $sheet->getCell($col . $row);
                        $value = $cell->getValue();

                        // Handle different value types
                        if ($value instanceof \DateTime) {
                            $value = $value->format('Y-m-d H:i:s');
                        } elseif (is_object($value)) {
                            $value = (string)$value;
                        }

                        $rowData[] = trim((string)$value);
                    }

                    // Join row data with pipe separator
                    $rowText = implode(' | ', array_filter($rowData));
                    if (!empty($rowText)) {
                        $text .= $rowText . "\n";
                    }
                }

                $text .= "\n";
            }

            return [
                'text' => $text,
                'metadata' => [
                    'file_path' => $fullPath,
                    'extraction_method' => 'phpspreadsheet',
                    'sheet_count' => count($spreadsheet->getSheetNames())
                ],
                'success' => !empty($text)
            ];

        } catch (\Exception $e) {
            Log::error('Excel extraction failed', [
                'error' => $e->getMessage(),
                'file' => $fullPath
            ]);

            return [
                'text' => '',
                'metadata' => [
                    'file_path' => $fullPath,
                    'extraction_method' => 'none',
                    'error' => 'Failed to extract Excel: ' . $e->getMessage()
                ],
                'success' => false
            ];
        }
    }

    /**
     * Check if command exists
     */
    private function commandExists(string $command): bool
    {
        $os = strtoupper(substr(PHP_OS, 0, 3));

        if ($os === 'WIN') {
            exec("where {$command}", $output, $returnCode);
        } else {
            exec("which {$command}", $output, $returnCode);
        }

        return $returnCode === 0;
    }

    /**
     * Clean extracted text
     */
    public function cleanText(string $text): string
    {
        // Remove excessive whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);

        // Normalize line breaks
        $text = preg_replace('/\r\n|\r/', "\n", $text);

        // Remove more than 2 consecutive line breaks
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // IMPORTANT: Escape curly braces to prevent template string errors
        // This prevents "Unclosed '{' on line X" errors when PDF content contains { or }
        $text = str_replace(['{', '}'], ['{{', '}}'], $text);

        // Trim
        $text = trim($text);

        return $text;
    }

    /**
     * Backward compatibility method - calls extractFromFile and returns just the text
     */
    public function extractText(string $filePath): string
    {
        $result = $this->extractFromFile($filePath);
        return $result['text'] ?? '';
    }

    /**
     * Extract text from image using OCR
     */
    private function extractFromImage(string $fullPath): array
    {
        try {
            $ocrService = app(\App\Services\OCRService::class);
            $ocrResult = $ocrService->extractTextFromImage($fullPath);

            if ($ocrResult['success']) {
                return [
                    'text' => $ocrResult['text'],
                    'metadata' => [
                        'file_path' => $fullPath,
                        'extraction_method' => 'ocr_' . $ocrResult['method'],
                        'ocr_confidence' => $ocrResult['confidence'],
                        'file_size' => filesize($fullPath)
                    ],
                    'success' => true
                ];
            } else {
                return [
                    'text' => '',
                    'metadata' => [
                        'file_path' => $fullPath,
                        'extraction_method' => 'ocr_failed',
                        'error' => $ocrResult['error'] ?? 'OCR extraction failed',
                        'file_size' => filesize($fullPath)
                    ],
                    'success' => false
                ];
            }

        } catch (\Exception $e) {
            Log::error('Image OCR extraction failed', [
                'file' => $fullPath,
                'error' => $e->getMessage()
            ]);

            return [
                'text' => '',
                'metadata' => [
                    'file_path' => $fullPath,
                    'extraction_method' => 'ocr_error',
                    'error' => 'OCR service error: ' . $e->getMessage(),
                    'file_size' => filesize($fullPath)
                ],
                'success' => false
            ];
        }
    }
}
