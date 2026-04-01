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
                
                default:
                    Log::warning("Unsupported file type", ['extension' => $extension]);
                    $result['metadata']['error'] = "Unsupported file type: {$extension}";
            }

            Log::info("Text extraction completed", [
                'success' => $result['success'],
                'text_length' => strlen($result['text']),
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
     */
    private function extractFromPdf(string $fullPath): array
    {
        // Try using pdftotext command if available
        if ($this->commandExists('pdftotext')) {
            $outputPath = $fullPath . '.txt';
            exec("pdftotext -layout '{$fullPath}' '{$outputPath}'", $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($outputPath)) {
                $text = file_get_contents($outputPath);
                unlink($outputPath);
                
                return [
                    'text' => $text,
                    'metadata' => [
                        'file_path' => $fullPath,
                        'extraction_method' => 'pdftotext'
                    ],
                    'success' => true
                ];
            }
        }

        // Fallback: basic extraction
        return [
            'text' => '',
            'metadata' => [
                'file_path' => $fullPath,
                'extraction_method' => 'none',
                'error' => 'PDF extraction requires pdftotext command'
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
        
        // Trim
        $text = trim($text);
        
        return $text;
    }
}
