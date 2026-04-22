<?php

namespace App\Helpers;

class PlaceholderHelper
{
    /**
     * Valid placeholders for GJM reports
     */
    const VALID_PLACEHOLDERS = [
        'PERIODE',
        'TAHUN_AKADEMIK',
        'LATAR_BELAKANG',
        'DASAR',
        'TUJUAN',
        'RUANG_LINGKUP',
        'PROGRAM_KERJA',
        'PELAKSANAAN',
        'HAMBATAN',
        'PEMECAHAN_MASALAH',
        'EVALUASI',
        'SARAN',
    ];

    /**
     * Extract placeholders from Word document
     * 
     * @param string $filePath
     * @return array
     */
    public static function extractPlaceholdersFromWord($filePath)
    {
        if (!file_exists($filePath)) {
            return [];
        }

        try {
            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($filePath);
            $templateProcessor->setMacroChars('{{', '}}');
            $variables = $templateProcessor->getVariables();
            
            return $variables;
        } catch (\Exception $e) {
            \Log::error('Failed to extract placeholders', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Validate placeholders in template
     * 
     * @param array $placeholders
     * @return array ['valid' => [...], 'invalid' => [...], 'missing' => [...]]
     */
    public static function validatePlaceholders($placeholders)
    {
        $valid = [];
        $invalid = [];
        $missing = [];

        // Check which placeholders are valid
        foreach ($placeholders as $placeholder) {
            if (in_array($placeholder, self::VALID_PLACEHOLDERS)) {
                $valid[] = $placeholder;
            } else {
                $invalid[] = $placeholder;
            }
        }

        // Check which required placeholders are missing
        $required = ['PERIODE', 'TAHUN_AKADEMIK', 'LATAR_BELAKANG', 'TUJUAN', 'EVALUASI', 'SARAN'];
        foreach ($required as $req) {
            if (!in_array($req, $placeholders)) {
                $missing[] = $req;
            }
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid,
            'missing' => $missing,
            'is_valid' => empty($invalid) && empty($missing),
        ];
    }

    /**
     * Get default values for placeholders
     * 
     * @return array
     */
    public static function getDefaultValues()
    {
        return [
            'PERIODE' => 'Triwulan I',
            'TAHUN_AKADEMIK' => date('Y'),
            'LATAR_BELAKANG' => 'Belum tersedia',
            'DASAR' => 'Belum tersedia',
            'TUJUAN' => 'Belum tersedia',
            'RUANG_LINGKUP' => 'Belum tersedia',
            'PROGRAM_KERJA' => 'Belum tersedia',
            'PELAKSANAAN' => 'Belum tersedia',
            'HAMBATAN' => 'Belum tersedia',
            'PEMECAHAN_MASALAH' => 'Belum tersedia',
            'EVALUASI' => 'Belum tersedia',
            'SARAN' => 'Belum tersedia',
        ];
    }

    /**
     * Format placeholder name (ensure uppercase and valid format)
     * 
     * @param string $name
     * @return string
     */
    public static function formatPlaceholderName($name)
    {
        // Remove any curly braces
        $name = str_replace(['{', '}'], '', $name);
        
        // Convert to uppercase
        $name = strtoupper($name);
        
        // Replace spaces with underscores
        $name = str_replace(' ', '_', $name);
        
        return $name;
    }

    /**
     * Wrap placeholder name with macro chars
     * 
     * @param string $name
     * @return string
     */
    public static function wrapPlaceholder($name)
    {
        $formatted = self::formatPlaceholderName($name);
        return '{{' . $formatted . '}}';
    }

    /**
     * Get placeholder description
     * 
     * @param string $placeholder
     * @return string
     */
    public static function getDescription($placeholder)
    {
        $descriptions = [
            'PERIODE' => 'Periode laporan (Q1, Q2, Q3, Q4 atau Ganjil/Genap)',
            'TAHUN_AKADEMIK' => 'Tahun akademik',
            'LATAR_BELAKANG' => 'Latar belakang pembuatan laporan',
            'DASAR' => 'Dasar hukum atau kebijakan',
            'TUJUAN' => 'Tujuan pembuatan laporan',
            'RUANG_LINGKUP' => 'Ruang lingkup laporan',
            'PROGRAM_KERJA' => 'Program kerja yang dilaksanakan',
            'PELAKSANAAN' => 'Detail pelaksanaan kegiatan',
            'HAMBATAN' => 'Hambatan yang dihadapi',
            'PEMECAHAN_MASALAH' => 'Cara pemecahan masalah',
            'EVALUASI' => 'Evaluasi dan analisis hasil',
            'SARAN' => 'Kesimpulan dan saran perbaikan',
        ];

        return $descriptions[$placeholder] ?? 'Tidak ada deskripsi';
    }

    /**
     * Generate template validation report
     * 
     * @param string $filePath
     * @return array
     */
    public static function generateValidationReport($filePath)
    {
        $placeholders = self::extractPlaceholdersFromWord($filePath);
        $validation = self::validatePlaceholders($placeholders);

        $report = [
            'file' => basename($filePath),
            'total_placeholders' => count($placeholders),
            'placeholders' => $placeholders,
            'validation' => $validation,
            'recommendations' => [],
        ];

        // Add recommendations
        if (!empty($validation['invalid'])) {
            $report['recommendations'][] = 'Hapus atau ganti placeholder yang tidak valid: ' . implode(', ', $validation['invalid']);
        }

        if (!empty($validation['missing'])) {
            $report['recommendations'][] = 'Tambahkan placeholder yang diperlukan: ' . implode(', ', $validation['missing']);
        }

        if ($validation['is_valid']) {
            $report['recommendations'][] = 'Template sudah valid dan siap digunakan!';
        }

        return $report;
    }
}
