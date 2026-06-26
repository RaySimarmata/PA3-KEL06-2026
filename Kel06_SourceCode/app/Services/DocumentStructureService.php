<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Service untuk mengekstrak dan memahami struktur dokumen template
 */
class DocumentStructureService
{
    /**
     * Ekstrak struktur dari dokumen template
     */
    public function extractStructure(string $content): array
    {
        Log::info("=== Extracting Document Structure ===");

        $structure = [
            'sections' => [],
            'formatting' => [],
            'patterns' => [],
            'metadata' => []
        ];

        // 1. Identifikasi sections berdasarkan heading
        $structure['sections'] = $this->extractSections($content);

        // 2. Identifikasi formatting patterns
        $structure['formatting'] = $this->extractFormatting($content);

        // 3. Identifikasi content patterns
        $structure['patterns'] = $this->extractPatterns($content);

        // 4. Extract metadata
        $structure['metadata'] = $this->extractMetadata($content);

        Log::info("Structure extracted", [
            'num_sections' => count($structure['sections']),
            'num_patterns' => count($structure['patterns'])
        ]);

        return $structure;
    }

    /**
     * Ekstrak sections dari dokumen
     */
    private function extractSections(string $content): array
    {
        $sections = [];
        $lines = explode("\n", $content);
        
        $currentSection = null;
        $sectionContent = [];
        $sectionIndex = 0;

        foreach ($lines as $lineNum => $line) {
            $trimmed = trim($line);
            
            // Deteksi heading (ALL CAPS, atau dengan numbering)
            if ($this->isHeading($trimmed)) {
                // Save previous section
                if ($currentSection !== null) {
                    $sections[] = [
                        'index' => $sectionIndex++,
                        'title' => $currentSection,
                        'content' => implode("\n", $sectionContent),
                        'line_start' => $lineNum - count($sectionContent),
                        'line_end' => $lineNum - 1,
                        'length' => strlen(implode("\n", $sectionContent))
                    ];
                }
                
                // Start new section
                $currentSection = $trimmed;
                $sectionContent = [];
            } else if ($currentSection !== null && !empty($trimmed)) {
                $sectionContent[] = $line;
            }
        }

        // Add last section
        if ($currentSection !== null) {
            $sections[] = [
                'index' => $sectionIndex,
                'title' => $currentSection,
                'content' => implode("\n", $sectionContent),
                'line_start' => count($lines) - count($sectionContent),
                'line_end' => count($lines) - 1,
                'length' => strlen(implode("\n", $sectionContent))
            ];
        }

        return $sections;
    }

    /**
     * Check if line is a heading
     */
    private function isHeading(string $line): bool
    {
        if (empty($line)) return false;

        // Pattern 1: ALL CAPS dengan minimal 3 kata
        if (preg_match('/^[A-Z\s]{10,}$/', $line)) {
            return true;
        }

        // Pattern 2: Numbering (I., II., 1., 2., a., b., A., B.)
        if (preg_match('/^(I{1,3}|IV|V|VI{0,3}|IX|X|\d+|[a-zA-Z])\.?\s+[A-Z]/', $line)) {
            return true;
        }

        // Pattern 3: Roman numerals with text (I. PENDAHULUAN, II. HASIL, etc)
        if (preg_match('/^(I{1,3}|IV|V|VI{0,3}|IX|X)\.\s+[A-Z]/', $line)) {
            return true;
        }

        // Pattern 4: Sub-sections (Tingkat I, Tingkat II, etc)
        if (preg_match('/^(Tingkat|BAB|Bagian|Section)\s+(I{1,3}|IV|V|VI{0,3}|IX|X|\d+)/i', $line)) {
            return true;
        }

        // Pattern 5: Lowercase roman numerals (i., ii., iii., iv.)
        if (preg_match('/^(i{1,3}|iv|v|vi{0,3}|ix|x)\.\s+/i', $line)) {
            return true;
        }

        // Pattern 6: DAFTAR ISI, Contents, dll
        if (preg_match('/^(DAFTAR ISI|Contents|PENDAHULUAN|HASIL|KESIMPULAN|LATAR BELAKANG|METODOLOGI|PEMBAHASAN)/i', $line)) {
            return true;
        }

        return false;
    }

    /**
     * Ekstrak formatting patterns
     */
    private function extractFormatting(string $content): array
    {
        $formatting = [];

        // Deteksi tabel
        if (preg_match_all('/\|[^\n]+\|/', $content, $matches)) {
            $formatting['has_tables'] = true;
            $formatting['table_count'] = count($matches[0]);
        }

        // Deteksi bullet points
        if (preg_match_all('/^[\s]*[-•*]\s+/m', $content, $matches)) {
            $formatting['has_bullets'] = true;
            $formatting['bullet_count'] = count($matches[0]);
        }

        // Deteksi numbering
        if (preg_match_all('/^[\s]*\d+\.\s+/m', $content, $matches)) {
            $formatting['has_numbering'] = true;
            $formatting['numbering_count'] = count($matches[0]);
        }

        // Deteksi indentasi
        $formatting['indentation_style'] = $this->detectIndentation($content);

        return $formatting;
    }

    /**
     * Deteksi style indentasi
     */
    private function detectIndentation(string $content): string
    {
        $lines = explode("\n", $content);
        $indentCounts = [];

        foreach ($lines as $line) {
            if (preg_match('/^(\s+)/', $line, $matches)) {
                $indent = safe_strlen($matches[1]);
                $indentCounts[$indent] = ($indentCounts[$indent] ?? 0) + 1;
            }
        }

        if (empty($indentCounts)) {
            return 'none';
        }

        // Most common indentation
        arsort($indentCounts);
        $commonIndent = key($indentCounts);

        return $commonIndent . ' spaces';
    }

    /**
     * Ekstrak content patterns
     */
    private function extractPatterns(string $content): array
    {
        $patterns = [];

        // Pattern: Tabel dengan header
        if (preg_match('/\|[^\n]+\|\n\|[-\s|]+\|/', $content)) {
            $patterns[] = 'markdown_table_with_header';
        }

        // Pattern: Daftar isi dengan page numbers
        if (preg_match('/\.{3,}\s*\d+/', $content)) {
            $patterns[] = 'table_of_contents_with_dots';
        }

        // Pattern: Cover page dengan centered text
        if (preg_match('/^\s{10,}[A-Z]/m', $content)) {
            $patterns[] = 'centered_text';
        }

        // Pattern: Signature section
        if (preg_match('/(Laguboti|Makassar|Jakarta|Surabaya|Bandung).*\d{4}/s', $content)) {
            $patterns[] = 'signature_with_location_date';
        }

        return $patterns;
    }

    /**
     * Ekstrak metadata dari dokumen
     */
    private function extractMetadata(string $content): array
    {
        $metadata = [];

        // Extract periode/semester
        if (preg_match('/Semester\s+(GENAP|GANJIL)\s+(\d{4})\/(\d{4})/i', $content, $matches)) {
            $metadata['semester'] = $matches[1];
            $metadata['tahun_ajaran'] = $matches[2] . '/' . $matches[3];
        }

        // Extract prodi
        if (preg_match('/PRODI\s+([A-Z0-9\s]+)/i', $content, $matches)) {
            $metadata['prodi'] = trim($matches[1]);
        }

        // Extract jenis laporan
        if (preg_match('/LAPORAN\s+([A-Z\s]+)/i', $content, $matches)) {
            $metadata['jenis_laporan'] = trim($matches[1]);
        }

        // Extract institusi
        if (preg_match('/(IT DEL|Institut Teknologi Del)/i', $content, $matches)) {
            $metadata['institusi'] = $matches[1];
        }

        return $metadata;
    }

    /**
     * Generate format instructions dari structure
     */
    public function generateFormatInstructions(array $structure): string
    {
        $instructions = "FORMAT TEMPLATE YANG HARUS DIIKUTI:\n\n";

        // 1. Struktur sections
        if (!empty($structure['sections'])) {
            $instructions .= "STRUKTUR DOKUMEN:\n";
            foreach ($structure['sections'] as $section) {
                $instructions .= "- {$section['title']}\n";
            }
            $instructions .= "\n";
        }

        // 2. Formatting style
        if (!empty($structure['formatting'])) {
            $instructions .= "STYLE FORMATTING:\n";
            
            if ($structure['formatting']['has_tables'] ?? false) {
                $instructions .= "- Gunakan tabel untuk data terstruktur\n";
            }
            
            if ($structure['formatting']['has_bullets'] ?? false) {
                $instructions .= "- Gunakan bullet points untuk list\n";
            }
            
            if ($structure['formatting']['has_numbering'] ?? false) {
                $instructions .= "- Gunakan numbering untuk urutan\n";
            }
            
            $instructions .= "\n";
        }

        // 3. Content patterns
        if (!empty($structure['patterns'])) {
            $instructions .= "PATTERN KONTEN:\n";
            foreach ($structure['patterns'] as $pattern) {
                $instructions .= "- " . str_replace('_', ' ', $pattern) . "\n";
            }
            $instructions .= "\n";
        }

        return $instructions;
    }
}
