<?php

namespace App\Services;

/**
 * Post-processor untuk memperbaiki output Markdown dari AI
 * Khususnya untuk masalah Tabel 1 yang terpisah
 */
class MarkdownPostProcessor
{
    /**
     * Fix Tabel 1 yang terpisah (judul di section I, isi di section II)
     */
    public function fixSeparatedTabel1($markdown)
    {
        // Pattern 1: Detect if "Tabel 1" title is followed immediately by "II. HASIL KUESIONER"
        // This means the table rows are missing
        $pattern1 = '/Tabel 1\. Matakuliah Mahasiswa Tingkat\s*\n+\s*(?:#{1,2}\s*)?II\.\s*HASIL KUESIONER/i';
        
        if (preg_match($pattern1, $markdown)) {
            \Log::info('MarkdownPostProcessor: Detected separated Tabel 1 (title without rows)');
            
            // Find the table rows in section II
            // Look for the table structure with Pernyataan, Kode, Skala
            $tablePattern = '/\|\s*Pernyataan\s*\|\s*Kode\s*\|\s*Skala\s*\|.*?\|\s*Sangat Setuju \(SS\)\s*\|\s*SS\s*\|\s*4\s*\|/s';
            
            if (preg_match($tablePattern, $markdown, $tableMatch)) {
                $tableRows = trim($tableMatch[0]);
                \Log::info('MarkdownPostProcessor: Found table rows in wrong location');
                
                // Remove table from its current location (section II)
                $markdown = str_replace($tableMatch[0], '', $markdown);
                
                // Insert table after "Tabel 1. Matakuliah Mahasiswa Tingkat"
                $markdown = preg_replace(
                    '/(Tabel 1\. Matakuliah Mahasiswa Tingkat)\s*\n+/',
                    "$1\n\n" . $tableRows . "\n\n",
                    $markdown,
                    1 // Only replace first occurrence
                );
                
                \Log::info('MarkdownPostProcessor: Fixed Tabel 1 placement');
            } else {
                \Log::warning('MarkdownPostProcessor: Table rows not found in markdown');
            }
        }
        
        return $markdown;
    }
    
    /**
     * Fix Tabel 1 yang tidak ada sama sekali
     * Tambahkan tabel default jika tidak ditemukan
     */
    public function ensureTabel1Exists($markdown)
    {
        // Check if "Tabel 1. Matakuliah Mahasiswa Tingkat" exists
        if (stripos($markdown, 'Tabel 1') === false) {
            \Log::warning('MarkdownPostProcessor: Tabel 1 not found, adding default table');
            
            $defaultTable = <<<'TABLE'

Tabel 1. Matakuliah Mahasiswa Tingkat

| Pernyataan | Kode | Skala |
|------------|------|-------|
| Tidak setuju (TS) | TS | 1 |
| Cukup Setuju (CS) | CS | 2 |
| Setuju (S) | S | 3 |
| Sangat Setuju (SS) | SS | 4 |

TABLE;
            
            // Insert before "II. HASIL KUESIONER"
            $markdown = preg_replace(
                '/(?:#{1,2}\s*)?II\.\s*HASIL KUESIONER/i',
                $defaultTable . "\n\n## II. HASIL KUESIONER",
                $markdown,
                1
            );
        }
        
        return $markdown;
    }
    
    /**
     * Process markdown dengan semua fixes
     */
    public function process($markdown)
    {
        \Log::info('MarkdownPostProcessor: Starting post-processing');
        
        // Fix 1: Separated table (title in section I, rows in section II)
        $markdown = $this->fixSeparatedTabel1($markdown);
        
        // Fix 2: Ensure table exists
        $markdown = $this->ensureTabel1Exists($markdown);
        
        // Clean up multiple blank lines
        $markdown = preg_replace('/\n{4,}/', "\n\n\n", $markdown);
        
        \Log::info('MarkdownPostProcessor: Post-processing complete');
        
        return $markdown;
    }
}
