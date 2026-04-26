<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * EnhancedFileExtractionService
 * 
 * Service untuk ekstraksi file yang lebih detail dan komprehensif
 * Khususnya untuk Excel dan gambar, dengan metadata yang lebih kaya
 */
class EnhancedFileExtractionService
{
    protected $textExtraction;

    public function __construct(TextExtractionService $textExtraction)
    {
        $this->textExtraction = $textExtraction;
    }

    /**
     * Extract file dengan detail lengkap (termasuk struktur, metadata, dll)
     */
    public function extractWithDetails(string $filePath): array
    {
        Log::info("=== Enhanced File Extraction ===", ['file' => $filePath]);

        $fullPath = $this->getFullPath($filePath);
        
        if (!file_exists($fullPath)) {
            return [
                'success' => false,
                'error' => "File not found: {$filePath}",
                'text' => '',
                'metadata' => [],
                'structure' => [],
                'details' => []
            ];
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        
        // Get basic extraction
        $basicExtraction = $this->textExtraction->extractFromFile($fullPath);
        
        $result = [
            'success' => $basicExtraction['success'],
            'text' => $basicExtraction['text'],
            'metadata' => $basicExtraction['metadata'],
            'structure' => [],
            'details' => [],
            'file_info' => [
                'name' => basename($fullPath),
                'extension' => $extension,
                'size' => filesize($fullPath),
                'size_formatted' => $this->formatBytes(filesize($fullPath)),
                'modified' => date('Y-m-d H:i:s', filemtime($fullPath))
            ]
        ];

        try {
            // Get detailed extraction based on file type
            switch ($extension) {
                case 'xlsx':
                case 'xls':
                    $result['structure'] = $this->extractExcelStructure($fullPath);
                    $result['details'] = $this->extractExcelDetails($fullPath);
                    break;
                
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'gif':
                case 'webp':
                    $result['structure'] = $this->extractImageStructure($fullPath);
                    $result['details'] = $this->extractImageDetails($fullPath);
                    break;
                
                case 'docx':
                case 'doc':
                    $result['structure'] = $this->extractDocumentStructure($fullPath);
                    $result['details'] = $this->extractDocumentDetails($fullPath);
                    break;
                
                case 'pdf':
                    $result['structure'] = $this->extractPdfStructure($fullPath);
                    $result['details'] = $this->extractPdfDetails($fullPath);
                    break;
            }

            Log::info("Enhanced extraction completed", [
                'file' => basename($fullPath),
                'success' => $result['success'],
                'text_length' => strlen(is_string($result['text']) ? $result['text'] : json_encode($result['text'])),
                'structure_items' => count($result['structure']),
                'details_count' => count($result['details'])
            ]);

        } catch (\Exception $e) {
            Log::error("Enhanced extraction failed", [
                'error' => $e->getMessage(),
                'file' => $filePath
            ]);
        }

        return $result;
    }

    /**
     * Extract Excel structure (sheets, columns, rows count)
     */
    private function extractExcelStructure(string $fullPath): array
    {
        if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            return [];
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $structure = [];

            foreach ($spreadsheet->getSheetNames() as $sheetName) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Get header row
                $headers = [];
                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    $cell = $sheet->getCell($col . '1');
                    $headers[] = trim((string)$cell->getValue());
                }

                $structure[] = [
                    'sheet_name' => $sheetName,
                    'rows' => $highestRow,
                    'columns' => $this->columnLetterToNumber($highestColumn),
                    'headers' => array_filter($headers),
                    'data_rows' => max(0, $highestRow - 1)
                ];
            }

            return $structure;

        } catch (\Exception $e) {
            Log::warning("Excel structure extraction failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Extract Excel details (sample data, statistics)
     */
    private function extractExcelDetails(string $fullPath): array
    {
        if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            return [];
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $details = [];

            foreach ($spreadsheet->getSheetNames() as $sheetName) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Get sample data (first 5 rows)
                $sampleData = [];
                $rowLimit = min(5, $highestRow);
                
                for ($row = 1; $row <= $rowLimit; $row++) {
                    $rowData = [];
                    for ($col = 'A'; $col <= $highestColumn; $col++) {
                        $cell = $sheet->getCell($col . $row);
                        $value = $cell->getValue();
                        
                        if ($value instanceof \DateTime) {
                            $value = $value->format('Y-m-d');
                        } elseif (is_object($value)) {
                            $value = (string)$value;
                        }
                        
                        $rowData[] = trim((string)$value);
                    }
                    $sampleData[] = $rowData;
                }

                // Get statistics
                $stats = [
                    'total_rows' => $highestRow,
                    'total_columns' => $this->columnLetterToNumber($highestColumn),
                    'non_empty_cells' => $this->countNonEmptyCells($sheet),
                    'numeric_columns' => $this->identifyNumericColumns($sheet),
                    'date_columns' => $this->identifyDateColumns($sheet)
                ];

                $details[] = [
                    'sheet_name' => $sheetName,
                    'sample_data' => $sampleData,
                    'statistics' => $stats
                ];
            }

            return $details;

        } catch (\Exception $e) {
            Log::warning("Excel details extraction failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Extract image structure and metadata
     */
    private function extractImageStructure(string $fullPath): array
    {
        try {
            $imageInfo = @getimagesize($fullPath);
            
            if ($imageInfo === false) {
                return [];
            }

            return [
                [
                    'type' => 'image',
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                    'mime_type' => $imageInfo['mime'],
                    'aspect_ratio' => round($imageInfo[0] / $imageInfo[1], 2)
                ]
            ];

        } catch (\Exception $e) {
            Log::warning("Image structure extraction failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Extract image details (EXIF, color info, etc)
     */
    private function extractImageDetails(string $fullPath): array
    {
        try {
            $details = [];

            // Get EXIF data if available
            if (function_exists('exif_read_data')) {
                $exif = @exif_read_data($fullPath);
                if ($exif !== false) {
                    $details[] = [
                        'type' => 'exif',
                        'camera' => $exif['Model'] ?? 'Unknown',
                        'date_taken' => $exif['DateTime'] ?? 'Unknown',
                        'orientation' => $exif['Orientation'] ?? 'Normal'
                    ];
                }
            }

            // Get image dimensions and color info
            $imageInfo = @getimagesize($fullPath);
            if ($imageInfo !== false) {
                $details[] = [
                    'type' => 'image_info',
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                    'bits' => $imageInfo['bits'] ?? 'Unknown',
                    'channels' => $imageInfo['channels'] ?? 'Unknown'
                ];
            }

            return $details;

        } catch (\Exception $e) {
            Log::warning("Image details extraction failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Extract document structure (headings, sections, etc)
     */
    private function extractDocumentStructure(string $fullPath): array
    {
        if (!class_exists('\PhpOffice\PhpWord\IOFactory')) {
            return [];
        }

        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($fullPath);
            $structure = [];
            $headingCount = 0;

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if ($element instanceof \PhpOffice\PhpWord\Element\Heading) {
                        $headingCount++;
                        $structure[] = [
                            'type' => 'heading',
                            'level' => $element->getDepth(),
                            'text' => $element->getText(),
                            'order' => $headingCount
                        ];
                    }
                }
            }

            return $structure;

        } catch (\Exception $e) {
            Log::warning("Document structure extraction failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Extract document details (paragraphs, tables, etc)
     */
    private function extractDocumentDetails(string $fullPath): array
    {
        if (!class_exists('\PhpOffice\PhpWord\IOFactory')) {
            return [];
        }

        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($fullPath);
            $details = [
                'total_paragraphs' => 0,
                'total_tables' => 0,
                'total_images' => 0,
                'sections' => []
            ];

            foreach ($phpWord->getSections() as $sectionIndex => $section) {
                $sectionDetail = [
                    'section_number' => $sectionIndex + 1,
                    'elements' => 0,
                    'tables' => 0,
                    'images' => 0
                ];

                foreach ($section->getElements() as $element) {
                    $sectionDetail['elements']++;
                    
                    if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                        $sectionDetail['tables']++;
                        $details['total_tables']++;
                    } elseif ($element instanceof \PhpOffice\PhpWord\Element\Image) {
                        $sectionDetail['images']++;
                        $details['total_images']++;
                    } else {
                        $details['total_paragraphs']++;
                    }
                }

                $details['sections'][] = $sectionDetail;
            }

            return [$details];

        } catch (\Exception $e) {
            Log::warning("Document details extraction failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Extract PDF structure (pages, bookmarks, etc)
     */
    private function extractPdfStructure(string $fullPath): array
    {
        if (!class_exists('\Smalot\PdfParser\Parser')) {
            return [];
        }

        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($fullPath);
            
            $pages = $pdf->getPages();
            
            return [
                [
                    'type' => 'pdf',
                    'total_pages' => count($pages),
                    'page_details' => array_map(function($page, $index) {
                        return [
                            'page_number' => $index + 1,
                            'width' => $page->getWidth() ?? 'Unknown',
                            'height' => $page->getHeight() ?? 'Unknown'
                        ];
                    }, $pages, array_keys($pages))
                ]
            ];

        } catch (\Exception $e) {
            Log::warning("PDF structure extraction failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Extract PDF details (metadata, etc)
     */
    private function extractPdfDetails(string $fullPath): array
    {
        if (!class_exists('\Smalot\PdfParser\Parser')) {
            return [];
        }

        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($fullPath);
            
            $details = $pdf->getDetails();
            
            return [
                [
                    'type' => 'pdf_metadata',
                    'title' => $details['Title'] ?? 'Unknown',
                    'author' => $details['Author'] ?? 'Unknown',
                    'subject' => $details['Subject'] ?? 'Unknown',
                    'creator' => $details['Creator'] ?? 'Unknown',
                    'producer' => $details['Producer'] ?? 'Unknown',
                    'creation_date' => $details['CreationDate'] ?? 'Unknown'
                ]
            ];

        } catch (\Exception $e) {
            Log::warning("PDF details extraction failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Helper: Convert column letter to number (A=1, B=2, etc)
     */
    private function columnLetterToNumber(string $letter): int
    {
        $number = 0;
        for ($i = 0; $i < strlen($letter); $i++) {
            $number = $number * 26 + (ord($letter[$i]) - ord('A') + 1);
        }
        return $number;
    }

    /**
     * Helper: Count non-empty cells in sheet
     */
    private function countNonEmptyCells($sheet): int
    {
        $count = 0;
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $cell = $sheet->getCell($col . $row);
                if (!empty($cell->getValue())) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Helper: Identify numeric columns
     */
    private function identifyNumericColumns($sheet): array
    {
        $numericCols = [];
        $highestColumn = $sheet->getHighestColumn();

        for ($col = 'A'; $col <= $highestColumn; $col++) {
            $isNumeric = true;
            
            for ($row = 2; $row <= min(10, $sheet->getHighestRow()); $row++) {
                $cell = $sheet->getCell($col . $row);
                $value = $cell->getValue();
                
                if (!empty($value) && !is_numeric($value)) {
                    $isNumeric = false;
                    break;
                }
            }

            if ($isNumeric) {
                $numericCols[] = $col;
            }
        }

        return $numericCols;
    }

    /**
     * Helper: Identify date columns
     */
    private function identifyDateColumns($sheet): array
    {
        $dateCols = [];
        $highestColumn = $sheet->getHighestColumn();

        for ($col = 'A'; $col <= $highestColumn; $col++) {
            $isDate = true;
            
            for ($row = 2; $row <= min(10, $sheet->getHighestRow()); $row++) {
                $cell = $sheet->getCell($col . $row);
                $value = $cell->getValue();
                
                if ($value instanceof \DateTime) {
                    continue;
                } elseif (!empty($value) && !strtotime($value)) {
                    $isDate = false;
                    break;
                }
            }

            if ($isDate) {
                $dateCols[] = $col;
            }
        }

        return $dateCols;
    }

    /**
     * Helper: Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Helper: Get full path
     */
    private function getFullPath(string $filePath): string
    {
        if (file_exists($filePath)) {
            return $filePath;
        }

        $storagePath = storage_path('app/' . $filePath);
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        $publicPath = storage_path('app/public/' . $filePath);
        if (file_exists($publicPath)) {
            return $publicPath;
        }

        $directPublicPath = public_path('storage/' . $filePath);
        if (file_exists($directPublicPath)) {
            return $directPublicPath;
        }

        return $filePath;
    }
}
