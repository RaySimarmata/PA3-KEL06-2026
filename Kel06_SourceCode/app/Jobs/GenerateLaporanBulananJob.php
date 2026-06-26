<?php

namespace App\Jobs;

use App\Models\LaporanBulanan;
use App\Services\LaporanKuesioneService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;
use Carbon\Carbon;

class GenerateLaporanBulananJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $laporanId;

    /**
     * Create a new job instance.
     */
    public function __construct($laporanId)
    {
        $this->laporanId = $laporanId;
    }

    /**
     * Create a safe temporary file path for Windows
     */
    private function createSafeTemporaryPath($originalPath)
    {
        $directory = dirname($originalPath);
        $filename = basename($originalPath);
        // Sanitize filename to avoid special characters
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        $tempFilename = 'temp_' . uniqid() . '_' . $filename;
        return $directory . DIRECTORY_SEPARATOR . $tempFilename;
    }

    /**
     * Ensure directory exists and is writable
     */
    private function ensureDirectoryWritable($path)
    {
        $directory = dirname($path);
        if (!file_exists($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new \Exception("Cannot create directory: {$directory}");
            }
        }

        if (!is_writable($directory)) {
            throw new \Exception("Directory is not writable: {$directory}");
        }
    }

    /**
     * Safely close ZipArchive with retry for Windows file locking
     */
    private function safeCloseZip(\ZipArchive $zip, $maxRetries = 5)
    {
        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                if ($zip->close()) {
                    return true;
                }
            } catch (\Throwable $e) {
                Log::warning('ZipArchive close failed (attempt ' . ($i + 1) . '/' . $maxRetries . '): ' . $e->getMessage());
            }

            // Exponential backoff for Windows file locking
            if ($i < $maxRetries - 1) {
                usleep((200 + $i * 200) * 1000); // 200ms, 400ms, 600ms, 800ms
            }
        }

        Log::error('safeCloseZip: all retries exhausted');
        return false;
    }

    /**
     * Execute the job.
     */
    public function handle(LaporanKuesioneService $laporanService): void
    {
        try {
            Log::info("=== Starting Laporan Generation Job ===", ['laporan_id' => $this->laporanId]);

            $laporan = LaporanBulanan::findOrFail($this->laporanId);

            // Update status to processing
            $laporan->update(['status' => 'processing']);

            // Check if Advanced RAG is enabled
            $useAdvancedRAG = env('VECTOR_DB_ENABLED', false);
            $useStructureAware = env('TEMPLATE_STRUCTURE_AWARE', false);
            $usePlaceholders = env('TEMPLATE_USE_PLACEHOLDERS', true); // Prioritas template placeholders dari python

            Log::info("Generating laporan", [
                'method' => $usePlaceholders ? 'Placeholders' : ($useStructureAware ? 'Structure-Aware' : ($useAdvancedRAG ? 'Advanced RAG' : 'Simple RAG')),
                'structure_aware' => $useStructureAware,
                'use_placeholders' => $usePlaceholders,
                'vector_db_enabled' => $useAdvancedRAG
            ]);

            // Generate laporan with AI
            if ($usePlaceholders) {
                // New logic: generate placeholders based JSON
                $result = $laporanService->generateLaporanWithPlaceholders(
                    $laporan->periode,
                    $laporan->prodi_id,
                    $laporan->template_id
                );

                $hasilLaporan = $result['hasil_laporan'];
                $aggregatedData = $result['aggregated_data'];
                $metadata = $result['metadata'] ?? [];

                // Update laporan with result
                $laporan->update([
                    'hasil_laporan' => $hasilLaporan,
                    'total_kuesioner' => $aggregatedData['total_kuesioner'] ?? 0,
                    'total_responden' => $aggregatedData['total_responden'] ?? 0,
                    'index_kepuasan_rata_rata' => $aggregatedData['index_kepuasan_rata_rata'] ?? 0,
                    'persen_kepuasan_rata_rata' => $aggregatedData['persen_kepuasan_rata_rata'] ?? 0,
                    'generated_by' => 'ai_placeholders',
                ]);
            } else if ($useStructureAware) {
                // Best: Structure-aware generation (follows template 100%)
                $result = $laporanService->generateLaporanWithTemplateStructure(
                    $laporan->periode,
                    $laporan->prodi_id,
                    $laporan->template_id
                );

                $hasilLaporan = $result['hasil_laporan'];
                $aggregatedData = $result['aggregated_data'];
                $metadata = $result['metadata'];

                // Update laporan with result
                $laporan->update([
                    'hasil_laporan' => $hasilLaporan,
                    'total_kuesioner' => $aggregatedData['total_kuesioner'] ?? 0,
                    'total_responden' => $aggregatedData['total_responden'] ?? 0,
                    'index_kepuasan_rata_rata' => $aggregatedData['index_kepuasan_rata_rata'] ?? 0,
                    'persen_kepuasan_rata_rata' => $aggregatedData['persen_kepuasan_rata_rata'] ?? 0,
                    'generated_by' => 'ai_rag_structure_aware',
                ]);

            } else if ($useAdvancedRAG) {
                // Advanced RAG tanpa structure awareness
                $result = $laporanService->generateLaporanAdvanced(
                    $laporan->periode,
                    $laporan->prodi_id,
                    $laporan->template_id
                );
                $hasilLaporan = $result['hasil_laporan'];
                $aggregatedData = $result['aggregated_data'];

                $laporan->update([
                    'hasil_laporan' => $hasilLaporan,
                    'total_kuesioner' => $aggregatedData['total_kuesioner'] ?? 0,
                    'total_responden' => $aggregatedData['total_responden'] ?? 0,
                    'index_kepuasan_rata_rata' => $aggregatedData['index_kepuasan_rata_rata'] ?? 0,
                    'persen_kepuasan_rata_rata' => $aggregatedData['persen_kepuasan_rata_rata'] ?? 0,
                ]);

            } else {
                // Simple RAG
                $result = $laporanService->generateLaporan(
                    $laporan->periode,
                    $laporan->prodi_id,
                    $laporan->template_id
                );
                $hasilLaporan = $result['hasil_laporan'];
                $aggregatedData = $result['aggregated_data'];

                $laporan->update([
                    'hasil_laporan' => $hasilLaporan,
                    'total_kuesioner' => $aggregatedData['total_kuesioner'] ?? 0,
                    'total_responden' => $aggregatedData['total_responden'] ?? 0,
                    'index_kepuasan_rata_rata' => $aggregatedData['index_kepuasan_rata_rata'] ?? 0,
                    'persen_kepuasan_rata_rata' => $aggregatedData['persen_kepuasan_rata_rata'] ?? 0,
                ]);
            }

            // Generate Word document
            try {
                if (isset($hasilLaporan['content_type']) && $hasilLaporan['content_type'] === 'placeholders') {
                    $wordPath = $this->generateWordDocumentTemplateProcessor($laporan, $hasilLaporan, $metadata ?? []);
                } else {
                    $wordPath = $this->generateWordDocument($laporan, $hasilLaporan);
                }

                Log::info("Word document generated successfully", ['path' => $wordPath]);

            } catch (\Exception $e) {
                Log::error("Word document generation failed", [
                    'error' => $e->getMessage(),
                    'laporan_id' => $this->laporanId
                ]);
                throw new \Exception('Gagal membuat dokumen Word: ' . $e->getMessage());
            }

            $laporan->update([
                'file_word' => $wordPath,
                'status' => 'completed',
            ]);

            Log::info("=== Laporan Generation Job Completed ===", ['laporan_id' => $this->laporanId]);

        } catch (\Exception $e) {
            Log::error("=== Laporan Generation Job Failed ===", [
                'laporan_id' => $this->laporanId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $laporan = LaporanBulanan::find($this->laporanId);
            if ($laporan) {
                $laporan->update([
                    'status' => 'error',
                    'error_message' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Generate Word Document from hasil laporan
     */
    private function generateWordDocument($laporan, $hasilLaporan)
    {
        // Check if it's Markdown format (structure-aware)
        if (isset($hasilLaporan['content_type']) && $hasilLaporan['content_type'] === 'markdown') {
            return $this->generateWordFromMarkdown($laporan, $hasilLaporan);
        }

        // Otherwise, use the old JSON format
        $phpWord = new PhpWord();

        // Set document properties
        $properties = $phpWord->getDocInfo();
        $properties->setCreator('AI Agent GKM System');
        $properties->setTitle('Laporan Bulanan Kuesioner - ' . $laporan->formatted_periode);

        // Add section
        $section = $phpWord->addSection();

        // Title
        $section->addText(
            'LAPORAN BULANAN KUESIONER MAHASISWA',
            ['bold' => true, 'size' => 16],
            ['alignment' => 'center']
        );
        $section->addText(
            'Periode: ' . $laporan->formatted_periode,
            ['size' => 12],
            ['alignment' => 'center']
        );
        if ($laporan->prodi) {
            $section->addText(
                'Program Studi: ' . $laporan->prodi->nama_prodi,
                ['size' => 12],
                ['alignment' => 'center']
            );
        }
        $section->addTextBreak(2);

        // 1. RINGKASAN EKSEKUTIF
        $section->addText('1. RINGKASAN EKSEKUTIF', ['bold' => true, 'size' => 14]);
        $section->addTextBreak();

        if (isset($hasilLaporan['ringkasan_eksekutif'])) {
            $ringkasan = $hasilLaporan['ringkasan_eksekutif'];
            $section->addText($ringkasan['overview'] ?? '');
            $section->addTextBreak();
        }

        // 2. STATISTIK UTAMA
        $section->addText('2. STATISTIK UTAMA', ['bold' => true, 'size' => 14]);
        $section->addTextBreak();

        if (isset($hasilLaporan['statistik_utama'])) {
            $stats = $hasilLaporan['statistik_utama'];
            $section->addText('• Total Kuesioner: ' . ($stats['total_kuesioner'] ?? 0));
            $section->addText('• Total Responden: ' . ($stats['total_responden'] ?? 0));
            $section->addText('• Index Kepuasan Rata-rata: ' . number_format($stats['index_kepuasan_rata_rata'] ?? 0, 2) . ' (skala 0-4)');
            $section->addText('• Persen Kepuasan Rata-rata: ' . number_format($stats['persen_kepuasan_rata_rata'] ?? 0, 2) . '%');
            $section->addTextBreak();
        }

        // 3. INSIGHT UTAMA
        if (isset($hasilLaporan['insight_utama']) && is_array($hasilLaporan['insight_utama'])) {
            $section->addText('3. INSIGHT UTAMA', ['bold' => true, 'size' => 14]);
            $section->addTextBreak();

            foreach ($hasilLaporan['insight_utama'] as $idx => $insight) {
                $section->addText(($idx + 1) . '. ' . $insight);
            }
            $section->addTextBreak();
        }

        // 4. REKOMENDASI
        if (isset($hasilLaporan['rekomendasi']) && is_array($hasilLaporan['rekomendasi'])) {
            $section->addText('4. REKOMENDASI STRATEGIS', ['bold' => true, 'size' => 14]);
            $section->addTextBreak();

            foreach ($hasilLaporan['rekomendasi'] as $idx => $rekom) {
                if (is_array($rekom)) {
                    $text = ($idx + 1) . '. ' . ($rekom['rekomendasi'] ?? '');
                    if (isset($rekom['prioritas'])) {
                        $text .= ' [Prioritas: ' . $rekom['prioritas'] . ']';
                    }
                    $section->addText($text);
                } else {
                    $section->addText(($idx + 1) . '. ' . $rekom);
                }
            }
            $section->addTextBreak();
        }

        // Footer
        $section->addTextBreak(2);
        $section->addText(
            'Laporan ini dihasilkan secara otomatis oleh AI Agent sistem GKM menggunakan teknologi RAG.',
            ['size' => 9, 'italic' => true],
            ['alignment' => 'center']
        );
        $section->addText(
            'Dihasilkan pada: ' . Carbon::now()->format('d/m/Y H:i:s'),
            ['size' => 9, 'italic' => true],
            ['alignment' => 'center']
        );

        // Save document with sanitized filename
        $sanitizedPeriode = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $laporan->periode);
        $fileName = 'laporan_' . $sanitizedPeriode . '_' . time() . '.docx';
        $filePath = 'laporan/' . $fileName;

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $tempPath = storage_path('app/' . $filePath);

        // Create directory if not exists
        $directory = dirname($tempPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Ensure directory is writable
        $this->ensureDirectoryWritable($tempPath);

        try {
            $objWriter->save($tempPath);

            // Verify file was created and is readable
            if (!file_exists($tempPath) || !is_readable($tempPath)) {
                throw new \Exception('File was not created properly or is not readable');
            }

            Log::info('Word document saved successfully', [
                'path' => $tempPath,
                'size' => filesize($tempPath)
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to save Word document: ' . $e->getMessage());
        }

        return $filePath;
    }

    /**
     * Generate Word Document from Markdown content (for structure-aware generation)
     */
    private function generateWordFromMarkdown($laporan, $konten)
    {
        // Check if we should use template
        $template = $laporan->template;
        $useTemplate = $template && $template->file_path && file_exists(storage_path('app/public/' . $template->file_path));

        if ($useTemplate) {
            // Use template-based generation
            return $this->generateWordFromTemplate($laporan, $konten, $template);
        }

        // Fallback to creating from scratch
        $phpWord = new PhpWord();

        // Set default font to Times New Roman
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(11);

        // Set document properties
        $properties = $phpWord->getDocInfo();
        $properties->setCreator('AI Agent GKM System');
        $properties->setTitle('Laporan Bulanan Kuesioner - ' . $laporan->formatted_periode);

        // Add section with proper margins
        $sectionStyle = [
            'marginTop' => 1134,    // 2 cm
            'marginBottom' => 1134, // 2 cm
            'marginLeft' => 1701,   // 3 cm
            'marginRight' => 1134,  // 2 cm
        ];
        $section = $phpWord->addSection($sectionStyle);

        // Add document header (title page style)
        $periodeObj = \Carbon\Carbon::createFromFormat('Y-m', $laporan->periode);
        $bulan = $periodeObj->locale('id')->translatedFormat('F');
        $tahun = $periodeObj->year;

        // Determine semester and UAS/UTS based on month
        // Semester Ganjil: Juli-Desember (bulan 7-12), UTS: Oktober, UAS: Desember
        // Semester Genap: Januari-Juni (bulan 1-6), UTS: Maret, UAS: Mei
        $bulanNum = $periodeObj->month;

        if ($bulanNum >= 7 && $bulanNum <= 12) {
            // Semester Ganjil
            $semester = 'GANJIL';
            $tahunAkademik = $tahun . '/' . ($tahun + 1);

            if ($bulanNum == 10) {
                $jenisUjian = 'UTS';
            } elseif ($bulanNum == 12) {
                $jenisUjian = 'UAS';
            } else {
                $jenisUjian = 'UAS'; // Default
            }
        } else {
            // Semester Genap
            $semester = 'GENAP';
            $tahunAkademik = ($tahun - 1) . '/' . $tahun;

            if ($bulanNum == 3) {
                $jenisUjian = 'UTS';
            } elseif ($bulanNum == 5) {
                $jenisUjian = 'UAS';
            } else {
                $jenisUjian = 'UAS'; // Default
            }
        }

        // Cover Page - Simplified
        $section->addTextBreak(5);

        $section->addText(
            'LAPORAN HASIL KEPUASAN MAHASISWA',
            ['bold' => true, 'size' => 14, 'name' => 'Times New Roman'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]
        );
        $section->addText(
            'di Mata Kuliah',
            ['bold' => true, 'size' => 14, 'name' => 'Times New Roman'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 200]
        );
        $section->addText(
            'Semester ' . $semester . ' ' . $tahunAkademik,
            ['bold' => true, 'size' => 14, 'name' => 'Times New Roman'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]
        );
        $section->addText(
            $jenisUjian,
            ['bold' => true, 'size' => 14, 'name' => 'Times New Roman'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 400]
        );

        // Page break before content
        $section->addPageBreak();

        // Check if content is Markdown
        if (isset($konten['content_type']) && $konten['content_type'] === 'markdown') {
            $markdown = $konten['content'];

            // Parse Markdown and add to Word
            $this->parseMarkdownToWord($section, $markdown);

        } else {
            // Fallback to old format
            $section->addText(
                'LAPORAN BULANAN KUESIONER MAHASISWA',
                ['bold' => true, 'size' => 16, 'name' => 'Times New Roman'],
                ['alignment' => 'center']
            );
            $section->addText(
                'Periode: ' . $laporan->formatted_periode,
                ['size' => 12, 'name' => 'Times New Roman'],
                ['alignment' => 'center']
            );
            if ($laporan->prodi) {
                $section->addText(
                    'Program Studi: ' . $laporan->prodi->nama_prodi,
                    ['size' => 12, 'name' => 'Times New Roman'],
                    ['alignment' => 'center']
                );
            }
            $section->addTextBreak(2);

            // Add content as text
            if (isset($konten['content'])) {
                $section->addText($konten['content'], ['name' => 'Times New Roman']);
            }
        }

        // Save document with sanitized filename
        $sanitizedPeriode = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $laporan->periode);
        $fileName = 'laporan_' . $sanitizedPeriode . '_' . time() . '.docx';
        $filePath = 'laporan/' . $fileName;

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $tempPath = storage_path('app/' . $filePath);

        // Create directory if not exists
        $directory = dirname($tempPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Ensure directory is writable
        $this->ensureDirectoryWritable($tempPath);

        try {
            $objWriter->save($tempPath);

            // Verify file was created and is readable
            if (!file_exists($tempPath) || !is_readable($tempPath)) {
                throw new \Exception('File was not created properly or is not readable');
            }

            Log::info('Word document from Markdown saved successfully', [
                'path' => $tempPath,
                'size' => filesize($tempPath)
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to save Word document from Markdown: ' . $e->getMessage());
        }

        return $filePath;
    }

    /**
     * Generate Word document from template
     */
    private function generateWordFromTemplate($laporan, $konten, $template)
    {
        $templatePath = storage_path('app/public/' . $template->file_path);

        $fileName = 'laporan_' . $laporan->periode . '_' . time() . '.docx';
        $filePath = 'laporan/' . $fileName;
        $tempPath = storage_path('app/' . $filePath);

        $directory = dirname($tempPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Copy template to destination
        copy($templatePath, $tempPath);

        // Extract content from markdown
        $markdown = isset($konten['content']) ? $konten['content'] : '';

        // Parse markdown to extract sections
        $sections = $this->parseMarkdownSections($markdown);

        // Prepare metadata
        $periodeObj = \Carbon\Carbon::createFromFormat('Y-m', $laporan->periode);
        $bulan = $periodeObj->locale('id')->translatedFormat('F');
        $tahun = $periodeObj->year;
        $tanggal = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y');

        // Determine semester and UAS/UTS based on month (same logic as non-template version)
        $bulanNum = $periodeObj->month;

        if ($bulanNum >= 7 && $bulanNum <= 12) {
            // Semester Ganjil
            $semester = 'GANJIL';
            $tahunAkademik = $tahun . '/' . ($tahun + 1);

            if ($bulanNum == 10) {
                $jenisUjian = 'UTS';
            } elseif ($bulanNum == 12) {
                $jenisUjian = 'UAS';
            } else {
                $jenisUjian = 'UAS'; // Default
            }
        } else {
            // Semester Genap
            $semester = 'GENAP';
            $tahunAkademik = ($tahun - 1) . '/' . $tahun;

            if ($bulanNum == 3) {
                $jenisUjian = 'UTS';
            } elseif ($bulanNum == 5) {
                $jenisUjian = 'UAS';
            } else {
                $jenisUjian = 'UAS'; // Default
            }
        }

        $metadata = [
            'PRODI' => $laporan->prodi->nama_prodi ?? 'D3 Teknologi Informasi',
            'SEMESTER' => $semester,
            'TAHUN_AKADEMIK' => $tahunAkademik,
            'JENIS_UJIAN' => $jenisUjian,
            'TEMPAT' => 'Laguboti',
            'TANGGAL' => $tanggal,
        ];

        // Merge with sections
        $replacements = array_merge($metadata, $sections);

        // Process template
        $zip = new \ZipArchive();
        if ($zip->open($tempPath) === true) {
            $filesToProcess = ['word/document.xml'];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (preg_match('/^word\/(header|footer)\d+\.xml$/', $filename)) {
                    $filesToProcess[] = $filename;
                }
            }

            foreach ($filesToProcess as $filename) {
                $xml = $zip->getFromName($filename);
                if ($xml !== false) {
                    foreach ($replacements as $key => $value) {
                        if (is_array($value) || is_object($value)) {
                            continue;
                        }

                        $placeholder = '{{' . $key . '}}';
                        $xml = str_replace($placeholder, htmlspecialchars((string)$value, ENT_XML1, 'UTF-8'), $xml);
                    }

                    // Remove the signature line "( GKM PRODI {{PRODI}} )" or any remaining GKM PRODI text
                    // This handles both filled and unfilled placeholders
                    $xml = preg_replace('/<w:p[^>]*>.*?\(\s*GKM\s+PRODI\s+[^)]*\).*?<\/w:p>/is', '', $xml);
                    $xml = preg_replace('/<w:t[^>]*>\(\s*GKM\s+PRODI\s+[^)]*\)<\/w:t>/is', '', $xml);

                    // Also remove empty paragraphs that might be left after signature removal
                    $xml = preg_replace('/<w:p[^>]*>\s*<w:pPr[^>]*\/>\s*<\/w:p>/is', '', $xml);

                    $zip->addFromString($filename, $xml);
                }
            }
            $this->safeCloseZip($zip);
        }

        return $filePath;
    }

    /**
     * Parse markdown into sections
     */
    private function parseMarkdownSections($markdown)
    {
        $sections = [];

        // Extract sections using regex
        // I. PENDAHULUAN
        if (preg_match('/I\.\s*PENDAHULUAN.*?a\.\s*Tujuan\s*(.*?)(?=b\.|II\.|$)/s', $markdown, $matches)) {
            $sections['PENDAHULUAN_TUJUAN'] = trim($matches[1]);
        }

        if (preg_match('/b\.\s*Waktu pelaksanaan\s*(.*?)(?=c\.|II\.|$)/s', $markdown, $matches)) {
            $sections['PENDAHULUAN_WAKTU'] = trim($matches[1]);
        }

        if (preg_match('/c\.\s*Ruang Lingkup\s*(.*?)(?=II\.|$)/s', $markdown, $matches)) {
            $sections['PENDAHULUAN_RUANG_LINGKUP'] = trim($matches[1]);
        }

        // II. HASIL KUESIONER
        if (preg_match('/I\.\s*Tingkat I.*?(Tabel.*?)(?=II\.\s*Tingkat|III\.\s*KESIMPULAN|$)/s', $markdown, $matches)) {
            $sections['HASIL_KUESIONER_TINGKAT_I'] = trim($matches[0]);
        }

        if (preg_match('/II\.\s*Tingkat II.*?(Tabel.*?)(?=III\.\s*Tingkat|III\.\s*KESIMPULAN|$)/s', $markdown, $matches)) {
            $sections['HASIL_KUESIONER_TINGKAT_II'] = trim($matches[0]);
        }

        if (preg_match('/III\.\s*Tingkat III.*?(Tabel.*?)(?=Masukan|III\.\s*KESIMPULAN|$)/s', $markdown, $matches)) {
            $sections['HASIL_KUESIONER_TINGKAT_III'] = trim($matches[0]);
        }

        if (preg_match('/Masukan dan Saran.*?(Tabel.*?)(?=III\.\s*KESIMPULAN|$)/s', $markdown, $matches)) {
            $sections['MASUKAN_SARAN'] = trim($matches[0]);
        }

        // III. KESIMPULAN DAN SARAN
        if (preg_match('/III\.\s*KESIMPULAN DAN SARAN\s*(.*?)(?=Laguboti|$)/s', $markdown, $matches)) {
            $content = trim($matches[1]);
            // Split into kesimpulan and saran if possible
            $sections['KESIMPULAN'] = $content;
            $sections['SARAN_REKOMENDASI'] = '';
        }

        return $sections;
    }

    /**
     * Parse Markdown to Word format
     */
    private function parseMarkdownToWord($section, $markdown)
    {
        $lines = explode("\n", trim($markdown));
        $inTable = false;
        $tableData = [];
        $lastWasBreak = false;

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                if (!$inTable && !$lastWasBreak) {
                    $section->addTextBreak();
                    $lastWasBreak = true;
                }
                continue;
            }

            $lastWasBreak = false;

            // Headers - detect section headers (I., II., III.) and subsection headers (a., b., c.)
            if (preg_match('/^([IVX]+\.|[a-z]\.)\s+(.+)$/i', $line, $matches)) {
                $prefix = $matches[1];
                $text = $matches[2];

                // Main sections (I., II., III.)
                if (preg_match('/^[IVX]+\.$/i', $prefix)) {
                    $section->addText(
                        $line,
                        ['bold' => true, 'size' => 12, 'name' => 'Times New Roman'],
                        ['spaceAfter' => 100, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]
                    );
                } else {
                    // Subsections (a., b., c.)
                    $section->addText(
                        $line,
                        ['bold' => true, 'size' => 11, 'name' => 'Times New Roman'],
                        ['spaceAfter' => 100, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]
                    );
                }
                continue;
            }

            // Table detection
            if (strpos($line, '|') !== false) {
                if (!$inTable) {
                    $inTable = true;
                    $tableData = [];
                }

                // Skip separator lines (Markdown table separator with dashes)
                if (preg_match('/^\|[\s\-:|]+\|$/', $line) || preg_match('/^\|[\s\-:]+\|[\s\-:]+\|[\s\-:]+\|$/', $line)) {
                    continue;
                }

                // Parse table row
                $cells = array_map('trim', explode('|', trim($line, '|')));

                // Skip rows that only contain dashes or are empty
                $isEmptyRow = true;
                foreach ($cells as $cell) {
                    if (!empty($cell) && !preg_match('/^[\-\s]+$/', $cell)) {
                        $isEmptyRow = false;
                        break;
                    }
                }

                if (!$isEmptyRow) {
                    $tableData[] = $cells;
                }
                continue;
            } else if ($inTable) {
                // End of table, render it
                $this->addTableToWord($section, $tableData);
                $inTable = false;
                $tableData = [];
            }

            // Bullet points
            if (preg_match('/^[\*\-]\s+(.+)$/', $line, $matches)) {
                $section->addText(
                    '- ' . $matches[1],
                    ['name' => 'Times New Roman', 'size' => 11],
                    ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]
                );
                continue;
            }

            // Numbered lists
            if (preg_match('/^\d+\.\s+(.+)$/', $line, $matches)) {
                $section->addText(
                    $line,
                    ['name' => 'Times New Roman', 'size' => 11],
                    ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]
                );
                continue;
            }

            // Check for right-aligned text (Laguboti, tanggal)
            if (preg_match('/^Laguboti,\s+\d+\s+\w+\s+\d{4}$/i', $line)) {
                $section->addText(
                    $line,
                    ['name' => 'Times New Roman', 'size' => 11],
                    ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::END]
                );
                continue;
            }

            // Bold text
            $line = preg_replace('/\*\*(.+?)\*\*/', '$1', $line);

            // Regular paragraph with justified alignment
            $section->addText(
                $line,
                ['name' => 'Times New Roman', 'size' => 11],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH]
            );
        }

        // Render any remaining table
        if ($inTable && !empty($tableData)) {
            $this->addTableToWord($section, $tableData);
        }
    }

    /**
     * Add table to Word document
     */
    private function addTableToWord($section, $tableData)
    {
        if (empty($tableData)) {
            return;
        }

        // Define table style - simple borders like in the document
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
            'layout' => \PhpOffice\PhpWord\Style\Table::LAYOUT_AUTO
        ];

        $table = $section->addTable($tableStyle);

        // Calculate column count
        $columnCount = count($tableData[0]);

        // Define column widths based on column count
        $totalWidth = 9000; // Total width in twips
        $columnWidths = [];

        if ($columnCount == 4) {
            // For 4-column tables (Kode MK, Nama MK, Dosen, Indeks)
            $columnWidths = [1500, 3500, 2500, 1500];
        } elseif ($columnCount == 3) {
            // For 3-column tables (Kode MK, Nama MK, Masukan/Saran)
            $columnWidths = [1500, 2500, 5000];
        } elseif ($columnCount == 2) {
            // For 2-column tables (Pernyataan, Kode/Skala)
            $columnWidths = [6000, 3000];
        } else {
            // Default: equal width
            $cellWidth = $totalWidth / $columnCount;
            $columnWidths = array_fill(0, $columnCount, $cellWidth);
        }

        $headerFontStyle = ['bold' => true, 'name' => 'Times New Roman', 'size' => 11];
        $headerPStyle = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER];
        $bodyFontStyle = ['name' => 'Times New Roman', 'size' => 11];
        $bodyPStyle = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT];

        foreach ($tableData as $rowIndex => $rowData) {
            $table->addRow();

            $isHeader = ($rowIndex === 0);

            foreach ($rowData as $cellIndex => $cellData) {
                $cellStyle = ['valign' => 'center'];

                $cell = $table->addCell($columnWidths[$cellIndex] ?? 2000, $cellStyle);

                // Determine alignment based on column
                $pStyle = $bodyPStyle;
                if ($isHeader) {
                    $pStyle = $headerPStyle;
                } else {
                    // Center align for Kode Matakuliah (column 0) and Indeks Kepuasan (column 3)
                    if ($cellIndex === 0 || $cellIndex === 3) {
                        $pStyle = $headerPStyle; // center
                    }
                }

                $cell->addText($cellData, $isHeader ? $headerFontStyle : $bodyFontStyle, $pStyle);
            }
        }

        $section->addTextBreak();
    }

    /**
     * Generate Word menggunakan TemplateProcessor dan replace placeholders
     */
    private function generateWordDocumentTemplateProcessor($laporan, $hasilLaporan, $metadata)
    {
        // Require the template
        $template = $laporan->template;
        if (!$template || !$template->file_path) {
            throw new \Exception("Template document not found for this report.");
        }

        $templatePath = storage_path('app/' . $template->file_path);
        if ($template->file_path && str_starts_with($template->file_path, 'public/')) {
            $templatePath = storage_path('app/public/' . substr($template->file_path, 7));
        }

        if (!file_exists($templatePath)) {
            $templatePath = storage_path('app/public/' . $template->file_path);
            if (!file_exists($templatePath)) {
                throw new \Exception("Template file not found at path: " . $templatePath);
            }
        }

        // Verify template file is valid before processing
        if (!is_readable($templatePath)) {
            throw new \Exception("Template file is not readable: " . $templatePath);
        }

        // Check if template is a valid ZIP file (docx is a ZIP)
        $zip = new \ZipArchive();
        $checkResult = $zip->open($templatePath, \ZipArchive::CHECKCONS);
        if ($checkResult !== true) {
            throw new \Exception("Template file is not a valid Word document (ZIP error code: {$checkResult}). The uploaded template may be corrupted.");
        }
        $this->safeCloseZip($zip);

        Log::info('Template validation passed', ['template_path' => $templatePath]);

        // Sanitize filename
        $sanitizedPeriode = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $laporan->periode);
        $fileName = 'laporan_' . $sanitizedPeriode . '_' . time() . '.docx';
        $filePath = 'laporan/' . $fileName;
        $tempPath = storage_path('app/' . $filePath);

        // Ensure directory is writable
        $this->ensureDirectoryWritable($tempPath);

        // FIX: Perbaiki placeholder yang terpecah di template sebelum diproses
        $fixedTemplatePath = $this->fixBrokenPlaceholders($templatePath);

        try {
            // Use TemplateProcessor for safe placeholder replacement
            $templateProcessor = new TemplateProcessor($fixedTemplatePath);

            // IMPORTANT: Set macro chars to {{ }} instead of default ${ }
            $templateProcessor->setMacroChars('{{', '}}');

            // Get available variables in template
            $availableVariables = $templateProcessor->getVariables();

            Log::info('Template variables detected', [
                'count' => count($availableVariables),
                'variables' => $availableVariables
            ]);

            // Merge metadata and hasil laporan
            $replacements = array_merge($metadata, $hasilLaporan);

            Log::info('Starting placeholder replacement', [
                'total_replacements' => count($replacements),
                'replacement_keys' => array_keys($replacements),
                'available_in_template' => $availableVariables
            ]);

            $successCount = 0;
            $failCount = 0;

            // Re-order replacements to process Tingkat III/IV first if needed? No, order shouldn't matter.

            // Replace each placeholder
            foreach ($replacements as $key => $value) {
                // Log strictly if it's one of the missing ones
                if (strpos($key, 'MASUKAN_SARAN') !== false) {
                    Log::info("Processing target key: $key", [
                        'in_template' => in_array($key, $availableVariables),
                        'value_preview' => is_string($value) ? substr($value, 0, 50) : 'not a string'
                    ]);
                }

                if (is_array($value) || is_object($value)) {
                    Log::debug("Skipping non-scalar value", ['key' => $key]);
                    continue;
                }

                // Convert value to string and handle special characters
                $stringValue = (string)$value;

                // Fix literal \n to actual newlines
                $stringValue = str_replace('\\n', "\n", $stringValue);

                // If this variable exists in template, process it
                if (in_array($key, $availableVariables)) {
                    // Cek apakah value mengandung tabel markdown
                    if (strpos($stringValue, '|') !== false && strpos($stringValue, "\n") !== false) {
                        // Gunakan method baru untuk merender konten kompleks (teks + tabel real)
                        $this->replacePlaceholderWithComplexContent($templateProcessor, $key, $stringValue);
                        $successCount++;
                    } else {
                        try {
                            $templateProcessor->setValue($key, $stringValue);
                            $successCount++;
                            Log::debug("Replaced placeholder", [
                                'key' => $key,
                                'value_length' => strlen($stringValue)
                            ]);
                        } catch (\Exception $e) {
                            $failCount++;
                            Log::warning("Failed to replace placeholder $key", ['error' => $e->getMessage()]);
                        }
                    }
                } else {
                    Log::debug("Placeholder not found in template variables", ['key' => $key]);
                }
            }

            Log::info('Placeholder replacement completed', [
                'success' => $successCount,
                'failed' => $failCount,
                'total_found_in_template' => count($availableVariables)
            ]);

            // Save the document - retry on Windows file locking errors
            $saveAttempts = 0;
            $maxSaveAttempts = 4;
            $saveSuccess = false;
            while ($saveAttempts < $maxSaveAttempts) {
                $saveAttempts++;
                try {
                    // GC to release any lingering file handles before save
                    gc_collect_cycles();
                    $templateProcessor->saveAs($tempPath);
                    $saveSuccess = true;
                    break;
                } catch (\Throwable $saveEx) {
                    $msg = $saveEx->getMessage();
                    Log::warning("TemplateProcessor saveAs attempt {$saveAttempts}/{$maxSaveAttempts} failed: {$msg}");
                    if ($saveAttempts < $maxSaveAttempts) {
                        usleep($saveAttempts * 500000); // 500ms, 1s, 1.5s
                    } else {
                        throw $saveEx;
                    }
                }
            }
            Log::info("Document saved after {$saveAttempts} attempt(s)");

            // Clean up temporary fixed template if it was created
            if (file_exists($fixedTemplatePath) && $fixedTemplatePath !== $templatePath) {
                @unlink($fixedTemplatePath);
            }

            // Verify file was created and is readable
            if (!file_exists($tempPath) || !is_readable($tempPath)) {
                throw new \Exception('File was not created properly or is not readable');
            }

            // Verify the output is a valid ZIP
            $verifyZip = new \ZipArchive();
            $verifyResult = $verifyZip->open($tempPath, \ZipArchive::CHECKCONS);
            if ($verifyResult !== true) {
                throw new \Exception("Generated Word document is corrupted (ZIP error code: {$verifyResult}).");
            }
            $this->safeCloseZip($verifyZip);

            Log::info('Word document generated successfully using TemplateProcessor', [
                'path' => $tempPath,
                'size' => filesize($tempPath)
            ]);

        } catch (\Exception $e) {
            // Clean up temporary fixed template on error
            if (isset($fixedTemplatePath) && file_exists($fixedTemplatePath) && $fixedTemplatePath !== $templatePath) {
                @unlink($fixedTemplatePath);
            }

            Log::error('TemplateProcessor failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Error processing Word document with TemplateProcessor: ' . $e->getMessage());
        }

        return $filePath;
    }

    /**
     * Fix broken placeholders in Word template
     * Word often splits {{PLACEHOLDER}} into multiple XML tags
     */
    private function fixBrokenPlaceholders($templatePath)
    {
        Log::info('Attempting to repair split placeholders in template', ['path' => $templatePath]);

        // Buat temporary file
        $tempPath = $templatePath . '.fixed.docx';

        // Copy file
        if (!copy($templatePath, $tempPath)) {
            Log::error('Failed to copy template for repair');
            return $templatePath;
        }

        // Buka ZIP
        $zip = new \ZipArchive();
        $openResult = false;
        for ($attempt = 0; $attempt < 3; $attempt++) {
            if ($zip->open($tempPath) === true) {
                $openResult = true;
                break;
            }
            usleep(200000);
        }
        if (!$openResult) {
            Log::error('Failed to open ZIP for repair after 3 attempts');
            return $templatePath;
        }

        // Baca document.xml
        $xml = $zip->getFromName('word/document.xml');

        if ($xml) {
            $originalXmlLength = strlen($xml);

            // 1. Pre-process: Gabungkan { dan { yang terpisah tag XML
            $xml = preg_replace('/\{[ \t\n\r]*(<[^>]+>)+[ \t\n\r]*\{/', '{{', $xml);
            // Gabungkan } dan } yang terpisah tag XML
            $xml = preg_replace('/\}[ \t\n\r]*(<[^>]+>)+[ \t\n\r]*\}/', '}}', $xml);

            // 2. Gabungkan placeholder yang terpecah
            // Cari pola {{ ... }} yang mungkin mengandung tag XML di tengahnya
            $xml = preg_replace_callback(
                '/\{\{(.*?)\}\}/s',
                function($matches) {
                    // Hapus semua tag XML di dalam placeholder
                    return '{{' . preg_replace('/<[^>]*>/', '', $matches[1]) . '}}';
                },
                $xml
            );

            // 3. Gabungkan <w:t> yang berdekatan untuk membantu TemplateProcessor
            $xml = preg_replace('/(<\/w:t>)\s*(<w:t[^>]*>)/', '$1$2', $xml);
            $xml = preg_replace('/<\/w:t><w:t[^>]*>/', '', $xml);

            $zip->addFromString('word/document.xml', $xml);

            Log::info('Placeholder repair completed', [
                'original_length' => $originalXmlLength,
                'new_length' => strlen($xml),
                'placeholders_detected' => substr_count($xml, '{{')
            ]);
        }

        $zip->close();

        return $tempPath;
    }

    /**
     * Replace a placeholder with complex content (text + native Word tables)
     */
    private function replacePlaceholderWithComplexContent($templateProcessor, $key, $content)
    {
        Log::info("Replacing placeholder with complex content", ['key' => $key]);

        // Regex untuk menemukan tabel markdown
        // Tabel biasanya dimulai dengan | , diikuti baris pemisah |---| , dan baris data |
        $pattern = '/(\|[^\n]+\|\n\|[\s\-:|]+\|\n(?:\|[^\n]+\|\n?)*)/s';

        $parts = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE);

        // Kita tidak bisa langsung menggunakan setComplexBlock berkali-kali untuk satu key
        // Strategi:
        // 1. Jika hanya ada satu tabel, ganti teks SEBELUM tabel dengan setValue,
        //    lalu ganti key dengan setComplexBlock (tabel),
        //    lalu tambahkan teks SESUDAH tabel (ini sulit di TemplateProcessor).

        // Strategi yang lebih aman:
        // Render tabel markdown menjadi baris-baris teks yang rapi (sebagai fallback jika multi-table kompleks)
        // ATAU gunakan XML injection yang lebih advanced.

        // Untuk case ini, kita coba implementasikan pemisahan sederhana:
        // Bagian 0: Teks sebelum tabel
        // Bagian 1: Tabel
        // Bagian 2: Teks sesudah tabel

        if (count($parts) >= 2) {
            $combinedXml = '';
            $xmlEscaper = new \PhpOffice\PhpWord\Escaper\Xml();

            foreach ($parts as $index => $part) {
                $part = trim($part);
                if (empty($part)) continue;

                if (strpos($part, '|') !== false && strpos($part, "\n") !== false) {
                    // Ini adalah tabel
                    $table = $this->createPhpWordTable($part);
                    $combinedXml .= $this->getPhpWordElementXml($table);
                } else {
                    // Ini adalah teks - bungkus dalam paragraph
                    $escapedText = $xmlEscaper->escape($part);
                    // Handle newlines di dalam teks dengan <w:br/>
                    $escapedText = str_replace("\n", '<w:br/>', $escapedText);
                    $combinedXml .= '<w:p><w:r><w:t xml:space="preserve">' . $escapedText . '</w:t></w:r></w:p>';
                }
            }

            if (!empty($combinedXml)) {
                $templateProcessor->replaceXmlBlock($key, $combinedXml, 'w:p');
            }
        } else {
            // Fallback ke format teks biasa jika tidak bisa diparse
            $templateProcessor->setValue($key, $this->formatTableForWord($content));
        }
    }

    /**
     * Helper to get XML content of a PhpWord Element
     */
    private function getPhpWordElementXml($element)
    {
        $elementName = substr(get_class($element), strrpos(get_class($element), '\\') + 1);
        $objectClass = 'PhpOffice\\PhpWord\\Writer\\Word2007\\Element\\' . $elementName;

        $xmlWriter = new \PhpOffice\PhpWord\Shared\XMLWriter();
        $elementWriter = new $objectClass($xmlWriter, $element, false);
        $elementWriter->write();

        return $xmlWriter->getData();
    }

    /**
     * Create a PhpWord Table object from markdown string
     */
    private function createPhpWordTable($markdown)
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();

        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'width' => 100 * 50, // 100% width (approx)
            'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT,
        ];

        $table = $section->addTable($tableStyle);

        $lines = explode("\n", trim($markdown));
        $isHeader = true;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Skip separator line
            if (preg_match('/^\|[\s\-:|]+\|$/', $line)) {
                $isHeader = false;
                continue;
            }

            if (strpos($line, '|') !== false) {
                $cells = array_map('trim', explode('|', trim($line, '|')));
                $table->addRow();

                // Pre-calculate column widths if it's a typical 4-column table (like Tabel 2 or Tabel 3)
                $colCount = count($cells);
                $colWidths = [];
                if ($colCount === 4) {
                    // Kode (15%), Nama MK (30%), Dosen (20%), Saran/Indeks (35%)
                    $colWidths = [15 * 50, 30 * 50, 20 * 50, 35 * 50];
                } else {
                    // Even distribution
                    $widthPerCol = floor(100 / max(1, $colCount)) * 50;
                    $colWidths = array_fill(0, $colCount, $widthPerCol);
                }

                foreach ($cells as $colIndex => $cellText) {
                    $cellStyle = $isHeader ? ['bgColor' => 'F2F2F2'] : [];

                    // Add specific width to cell
                    if (isset($colWidths[$colIndex])) {
                        $cellStyle['width'] = $colWidths[$colIndex];
                    }

                    $fontStyle = $isHeader ? ['bold' => true] : [];

                    $cell = $table->addCell($colWidths[$colIndex] ?? null, $cellStyle);

                    // Style untuk paragraf (justify alignment)
                    $paragraphStyle = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH];

                    // Cek apakan text ini mengandung list item (1. ..., 2. ...) berturut-turut pada satu baris
                    // Pola: "1. Teks... 2. Teks..."
                    if (preg_match('/(?:\b\d+\.\s+.*?)(?=\s+\d+\.\s+|$)/', $cellText)) {
                        preg_match_all('/(\d+\.\s+.*?)(?=\s+\d+\.\s+|$)/', $cellText, $matches);
                        $items = $matches[1] ?? [];

                        // Jika berhasil dipisah dan memang berbentuk list (lebih dari 1 item, atau 1 item berawalan angka)
                        if (count($items) > 1 || (count($items) === 1 && strpos(trim($cellText), '1.') === 0)) {
                            foreach ($items as $item) {
                                $cell->addText(trim($item), $fontStyle, $paragraphStyle);
                            }
                        } else {
                            // Fallback jika regexp salah tangkap
                            $cell->addText($cellText, $fontStyle, $paragraphStyle);
                        }
                    } else {
                        // Teks biasa
                        $cell->addText($cellText, $fontStyle, $paragraphStyle);
                    }
                }

                if ($isHeader) $isHeader = false;
            }
        }

        return $table;
    }

    /**
     * Format markdown table for Word (fallback textual representation)
     */
    private function formatTableForWord($markdown)
    {
        $lines = explode("\n", trim($markdown));
        $result = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) continue;

            // Skip separator line (|---|---|)
            if (preg_match('/^\|[\s\-:|]+\|$/', $line)) {
                continue;
            }

            // Format row
            if (strpos($line, '|') !== false) {
                // Remove leading/trailing pipes and split by pipes
                $cells = array_map('trim', explode('|', trim($line, '|')));
                // Use a space separator instead of pipe for a cleaner look in Word paragraphs
                // or keep formatting if user prefers. User suggested tabs.
                $result[] = implode("\t", $cells);
            } else {
                $result[] = $line;
            }
        }

        return implode("\n", $result);
    }

    /**
     * Convert markdown tables in document to actual Word tables
     */
    private function convertMarkdownTablesToWordTables($docxPath, $replacements)
    {
        // Open the document
        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) {
            Log::warning('Could not open document for table conversion');
            return;
        }

        $documentXml = $zip->getFromName('word/document.xml');
        if ($documentXml === false) {
            $this->safeCloseZip($zip);
            return;
        }

        // Find all text that looks like markdown tables and convert them
        $modified = false;

        foreach ($replacements as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            // Check if this value contains a markdown table
            if (strpos($value, '|') !== false && strpos($value, "\n") !== false) {
                // Parse the markdown table
                $tableXml = $this->markdownTableToWordTableXml($value);

                if ($tableXml) {
                    // Find the formatted text in XML and replace with table
                    $formattedText = $this->convertMarkdownTableToFormattedText($value);
                    $escapedText = htmlspecialchars($formattedText, ENT_XML1, 'UTF-8');

                    // Replace the text with table XML
                    // This is tricky because the text might be split across multiple <w:t> tags
                    // For now, we'll use a simpler approach: find the paragraph containing the text
                    // and replace the entire paragraph with the table

                    $modified = true;
                }
            }
        }

        if ($modified) {
            $zip->addFromString('word/document.xml', $documentXml);
        }

        $this->safeCloseZip($zip);
    }

    /**
     * Convert markdown table to Word table XML
     */
    private function markdownTableToWordTableXml($markdown)
    {
        $lines = explode("\n", $markdown);
        $rows = [];
        $isHeader = true;

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and separator lines
            if (empty($line) || preg_match('/^\|[\s\-:|]+\|$/', $line)) {
                if (!empty($rows)) {
                    $isHeader = false;
                }
                continue;
            }

            // Parse table row
            if (strpos($line, '|') !== false) {
                $cells = array_map('trim', explode('|', trim($line, '|')));
                $rows[] = [
                    'cells' => $cells,
                    'isHeader' => $isHeader
                ];
                $isHeader = false;
            }
        }

        if (empty($rows)) {
            return null;
        }

        // Generate Word table XML
        $tableXml = '<w:tbl>';

        // Table properties
        $tableXml .= '<w:tblPr>';
        $tableXml .= '<w:tblStyle w:val="TableGrid"/>';
        $tableXml .= '<w:tblW w:w="5000" w:type="pct"/>';
        $tableXml .= '<w:tblBorders>';
        $tableXml .= '<w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $tableXml .= '<w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $tableXml .= '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $tableXml .= '<w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $tableXml .= '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $tableXml .= '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $tableXml .= '</w:tblBorders>';
        $tableXml .= '</w:tblPr>';

        // Table grid (column definitions)
        $numCols = count($rows[0]['cells']);
        $tableXml .= '<w:tblGrid>';
        for ($i = 0; $i < $numCols; $i++) {
            $tableXml .= '<w:gridCol w:w="' . (5000 / $numCols) . '"/>';
        }
        $tableXml .= '</w:tblGrid>';

        // Table rows
        foreach ($rows as $row) {
            $tableXml .= '<w:tr>';

            foreach ($row['cells'] as $cell) {
                $tableXml .= '<w:tc>';
                $tableXml .= '<w:tcPr>';
                $tableXml .= '<w:tcW w:w="' . (5000 / $numCols) . '" w:type="pct"/>';
                if ($row['isHeader']) {
                    $tableXml .= '<w:shd w:val="clear" w:color="auto" w:fill="D9D9D9"/>';
                }
                $tableXml .= '</w:tcPr>';
                $tableXml .= '<w:p>';
                $tableXml .= '<w:pPr>';
                if ($row['isHeader']) {
                    $tableXml .= '<w:jc w:val="center"/>';
                }
                $tableXml .= '</w:pPr>';
                $tableXml .= '<w:r>';
                $tableXml .= '<w:rPr>';
                if ($row['isHeader']) {
                    $tableXml .= '<w:b/>';
                }
                $tableXml .= '</w:rPr>';
                $tableXml .= '<w:t>' . htmlspecialchars($cell, ENT_XML1, 'UTF-8') . '</w:t>';
                $tableXml .= '</w:r>';
                $tableXml .= '</w:p>';
                $tableXml .= '</w:tc>';
            }

            $tableXml .= '</w:tr>';
        }

        $tableXml .= '</w:tbl>';

        return $tableXml;
    }

    /**
     * Convert markdown table to formatted text that preserves table structure
     */
    private function convertMarkdownTableToFormattedText($markdown)
    {
        $lines = explode("\n", $markdown);
        $tableRows = [];
        $columnWidths = [];

        // First pass: collect all rows and calculate column widths
        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and separator lines
            if (empty($line) || preg_match('/^\|[\s\-:|]+\|$/', $line)) {
                continue;
            }

            // Parse table row
            if (strpos($line, '|') !== false) {
                $cells = array_map('trim', explode('|', trim($line, '|')));
                $tableRows[] = $cells;

                // Track maximum width for each column
                foreach ($cells as $index => $cell) {
                    $width = mb_strlen($cell);
                    if (!isset($columnWidths[$index]) || $width > $columnWidths[$index]) {
                        $columnWidths[$index] = $width;
                    }
                }
            }
        }

        if (empty($tableRows)) {
            return $markdown;
        }

        // Second pass: format rows with proper padding
        $result = [];
        $isFirstRow = true;

        // Calculate total width for separator
        $totalWidth = array_sum($columnWidths) + (count($columnWidths) - 1) * 3; // 3 for " | "

        foreach ($tableRows as $cells) {
            $formattedCells = [];

            foreach ($cells as $index => $cell) {
                $width = $columnWidths[$index] ?? 20;
                // Pad cell to column width
                $formattedCells[] = str_pad($cell, $width, ' ', STR_PAD_RIGHT);
            }

            $result[] = implode(' | ', $formattedCells);

            // Add separator line after header using equals signs
            if ($isFirstRow) {
                $result[] = str_repeat('=', $totalWidth);
                $isFirstRow = false;
            }
        }

        // Add top and bottom borders
        $border = str_repeat('=', $totalWidth);
        array_unshift($result, $border);
        $result[] = $border;

        return implode("\n", $result);
    }

    /**
     * Convert markdown table to plain text
     */
    private function convertMarkdownTableToText($markdown)
    {
        $lines = explode("\n", $markdown);
        $result = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip separator lines
            if (preg_match('/^\|[\s\-:|]+\|$/', $line)) {
                continue;
            }

            // Process table rows
            if (strpos($line, '|') !== false) {
                $cells = array_map('trim', explode('|', trim($line, '|')));
                $result[] = implode(' | ', $cells);
            } else {
                $result[] = $line;
            }
        }

        return implode("\n", $result);
    }

    /**
     * Parse markdown to pure openXML string to insert into template blocks
     */
    private function convertMarkdownToOpenXML($markdown)
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();
        $this->parseMarkdownToWord($section, $markdown);

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $dummyName = 'dummy_' . time() . '_' . rand() . '.docx';
        $dummyPath = storage_path('app/laporan/' . $dummyName);
        $objWriter->save($dummyPath);

        $xml = '';
        $zip = new \ZipArchive();
        if ($zip->open($dummyPath) === true) {
            $docXml = $zip->getFromName('word/document.xml');

            // Extract namespaces from root to ensure all w:tbl, w:tr etc are recognized
            // even if the user's docx template doesn't have the exact same versions
            $namespaces = '';
            if (preg_match('/<w:document([^>]+)>/', $docXml, $nsMatches)) {
                $namespaces = $nsMatches[1];
            }

            // Extract everything between <w:body> and <w:sectPr> (ignoring page margins layout)
            if (preg_match('/<w:body(?:[^>]*)>(.*?)<w:sectPr/s', $docXml, $matches)) {
                $xml = $matches[1];
            } else if (preg_match('/<w:body(?:[^>]*)>(.*?)<\/w:body>/s', $docXml, $matches)) {
                $xml = $matches[1];
            }
            $this->safeCloseZip($zip);
        }
        @unlink($dummyPath);

        // PENTING: Bungkus dengan dummy OpenXML tag yang tidak terlihat secara layout tapi
        // membawa semua namespace definition, agar MS Word tidak nge-drop w:tbl yang tak dikenalnya.
        // We use <w:sdt> (Structured Document Tag) or just a plain <w:p> wrapper?
        // Actually since we swap the placeholder <w:p> out completely, returning multiple <w:tbl> and <w:p>
        // at the root level without a container works IF the document has the namespaces.
        // To be absolutely safe without breaking schema, we can inject the namespaces into the first element.

        if ($xml && $namespaces) {
            // Find the first <w:p> or <w:tbl> and inject namespaces
            $xml = preg_replace('/^(<w:[a-z0-9]+)/i', '$1 ' . trim($namespaces), trim($xml), 1);
        }

        return $xml;
    }
}
