<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class VMTSExcelService
{
    /**
     * Extract VMTS data from Excel file
     * 
     * @param string $filePath Full path to Excel file
     * @return array
     */
    public function extractVMTSData(string $filePath): array
    {
        try {
            if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
                throw new \Exception('PhpSpreadsheet library not installed');
            }

            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            $data = [
                'visi' => [],
                'misi' => [],
                'tujuan' => [],
                'sasaran' => [],
                'raw_data' => [],
                'metadata' => [
                    'total_rows' => 0,
                    'total_columns' => 0,
                    'sheet_name' => $worksheet->getTitle(),
                ]
            ];

            // Get highest row and column
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            
            $data['metadata']['total_rows'] = $highestRow;
            $data['metadata']['total_columns'] = $highestColumnIndex;

            // Read all data
            $currentSection = null;
            
            for ($row = 1; $row <= $highestRow; $row++) {
                $rowData = [];
                $hasContent = false;
                
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $cellValue = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                    $rowData[] = $cellValue;
                    
                    if (!empty($cellValue)) {
                        $hasContent = true;
                    }
                }
                
                if ($hasContent) {
                    $data['raw_data'][] = $rowData;
                    
                    // Detect section headers
                    $firstCell = strtolower(trim($rowData[0] ?? ''));
                    
                    if (strpos($firstCell, 'visi') !== false) {
                        $currentSection = 'visi';
                    } elseif (strpos($firstCell, 'misi') !== false) {
                        $currentSection = 'misi';
                    } elseif (strpos($firstCell, 'tujuan') !== false) {
                        $currentSection = 'tujuan';
                    } elseif (strpos($firstCell, 'sasaran') !== false) {
                        $currentSection = 'sasaran';
                    } elseif ($currentSection && !empty($rowData[0])) {
                        // Add content to current section
                        $content = implode(' | ', array_filter($rowData));
                        if (!empty($content)) {
                            $data[$currentSection][] = $content;
                        }
                    }
                }
            }

            Log::info('VMTS Excel data extracted', [
                'file' => basename($filePath),
                'rows' => $highestRow,
                'columns' => $highestColumnIndex,
                'visi_count' => count($data['visi']),
                'misi_count' => count($data['misi']),
                'tujuan_count' => count($data['tujuan']),
                'sasaran_count' => count($data['sasaran']),
            ]);

            return [
                'success' => true,
                'data' => $data,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to extract VMTS data from Excel', [
                'file' => basename($filePath),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate VMTS report content from extracted data
     * 
     * @param array $vmtsData
     * @param string $judul
     * @param string $periode
     * @return string
     */
    public function generateReportContent(array $vmtsData, string $judul, string $periode): string
    {
        $content = "# {$judul}\n\n";
        $content .= "**Periode:** {$periode}\n\n";
        $content .= "---\n\n";

        // VISI
        if (!empty($vmtsData['visi'])) {
            $content .= "## VISI\n\n";
            foreach ($vmtsData['visi'] as $item) {
                $content .= $item . "\n\n";
            }
        }

        // MISI
        if (!empty($vmtsData['misi'])) {
            $content .= "## MISI\n\n";
            foreach ($vmtsData['misi'] as $index => $item) {
                $content .= ($index + 1) . ". " . $item . "\n\n";
            }
        }

        // TUJUAN
        if (!empty($vmtsData['tujuan'])) {
            $content .= "## TUJUAN\n\n";
            foreach ($vmtsData['tujuan'] as $index => $item) {
                $content .= ($index + 1) . ". " . $item . "\n\n";
            }
        }

        // SASARAN
        if (!empty($vmtsData['sasaran'])) {
            $content .= "## SASARAN\n\n";
            foreach ($vmtsData['sasaran'] as $index => $item) {
                $content .= ($index + 1) . ". " . $item . "\n\n";
            }
        }

        // Add raw data summary if sections are empty
        if (empty($vmtsData['visi']) && empty($vmtsData['misi']) && 
            empty($vmtsData['tujuan']) && empty($vmtsData['sasaran'])) {
            
            $content .= "## DATA DARI EXCEL\n\n";
            $content .= "Berikut adalah data yang diekstrak dari file Excel:\n\n";
            
            foreach ($vmtsData['raw_data'] as $rowIndex => $row) {
                $rowContent = implode(' | ', array_filter($row));
                if (!empty($rowContent)) {
                    $content .= "- " . $rowContent . "\n";
                }
            }
        }

        return $content;
    }

    /**
     * Validate Excel file structure for VMTS
     * 
     * @param string $filePath
     * @return array
     */
    public function validateExcelStructure(string $filePath): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            $highestRow = $worksheet->getHighestRow();
            
            if ($highestRow < 2) {
                return [
                    'valid' => false,
                    'message' => 'File Excel terlalu sedikit data. Minimal harus ada 2 baris.',
                ];
            }

            // Check if file has any content
            $hasContent = false;
            for ($row = 1; $row <= min($highestRow, 10); $row++) {
                $cellValue = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
                if (!empty($cellValue)) {
                    $hasContent = true;
                    break;
                }
            }

            if (!$hasContent) {
                return [
                    'valid' => false,
                    'message' => 'File Excel tidak memiliki konten yang dapat dibaca.',
                ];
            }

            return [
                'valid' => true,
                'message' => 'File Excel valid',
                'rows' => $highestRow,
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Gagal membaca file Excel: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get Excel preview data (first 10 rows)
     * 
     * @param string $filePath
     * @return array
     */
    public function getExcelPreview(string $filePath): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            $highestRow = min($worksheet->getHighestRow(), 10);
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            
            $preview = [];
            
            for ($row = 1; $row <= $highestRow; $row++) {
                $rowData = [];
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $rowData[] = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                }
                $preview[] = $rowData;
            }

            return [
                'success' => true,
                'preview' => $preview,
                'total_rows' => $worksheet->getHighestRow(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
