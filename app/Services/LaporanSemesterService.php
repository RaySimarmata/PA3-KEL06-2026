<?php

namespace App\Services;

use App\Models\LaporanGJM;
use App\Models\LaporanBulanan;
use App\Helpers\TahunAkademikHelper;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LaporanSemesterService
{
    protected $aiAgentService;
    protected $ragRetrievalService;

    public function __construct(
        AIAgentService $aiAgentService,
        RAGRetrievalService $ragRetrievalService
    ) {
        $this->aiAgentService = $aiAgentService;
        $this->ragRetrievalService = $ragRetrievalService;
    }

    /**
     * Generate Laporan Semester
     * Menggunakan AI preview dari database yang sudah disimpan sebelumnya
     */
    public function generate($laporanId)
    {
        Log::info("=== Starting Laporan Semester Generation ===", ['laporan_id' => $laporanId]);

        $laporan = LaporanGJM::with(['template'])->find($laporanId);
        
        if (!$laporan) {
            throw new \Exception("Laporan not found");
        }

        // Update status to processing
        $laporan->update(['status_laporan' => 'processing']);

        try {
            // 1. Get AI preview dari database
            $cacheService = app(AIPreviewCacheService::class);
            $preview = $cacheService->getAIPreview($laporanId);

            if (!$preview) {
                throw new \Exception('AI preview belum dibuat. Silakan generate preview terlebih dahulu melalui chat assistant.');
            }

            Log::info('AI preview retrieved from database', [
                'laporan_id' => $laporanId,
                'preview_length' => strlen(is_string($preview['draft']) ? $preview['draft'] : json_encode($preview['draft'])),
                'sections_count' => count($preview['sections'])
            ]);

            // 2. Parse instruksi_prompt untuk mendapatkan periode dan tahun
            $instruksi = json_decode($laporan->instruksi_prompt, true);
            $periode = $instruksi['periode'] ?? 'Semester';
            $periodeSemester = $instruksi['periode_semester'] ?? 'ganjil';
            $tahun = $instruksi['tahun'] ?? date('Y');

            // 3. Extract placeholders dari AI preview sections
            $placeholders = $this->extractPlaceholdersFromAIPreview($preview, $periode, $tahun, $laporan);

            // 4. Generate Word document using template processor
            $wordPath = $this->generateWordDocument($laporan, $placeholders);

            // 5. Mark preview sebagai sudah digunakan
            $cacheService->markAsUsedForGeneration($laporanId);

            // 6. Update laporan record
            $laporan->update([
                'dokumen_hasil_path' => $wordPath,
                'status_laporan' => 'completed',
                'analisis_kepatuhan' => 'Laporan berhasil di-generate menggunakan AI preview dari database',
            ]);

            Log::info("Laporan Semester generated successfully", [
                'laporan_id' => $laporanId,
                'file_path' => $wordPath
            ]);

            return [
                'success' => true,
                'laporan_id' => $laporanId,
                'file_path' => $wordPath
            ];

        } catch (\Exception $e) {
            Log::error("Failed to generate Laporan Semester", [
                'laporan_id' => $laporanId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $laporan->update([
                'status_laporan' => 'failed',
                'catatan_review' => 'Error: ' . $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Extract placeholders dari AI preview sections yang sudah disimpan di database
     * Termasuk data OCR jika tersedia
     */
    private function extractPlaceholdersFromAIPreview($preview, $periode, $tahun, $laporan)
    {
        Log::info('Extracting placeholders from AI preview sections (with OCR integration)');

        // Calculate TAHUN_AKADEMIK
        if ($laporan && $laporan->periode_mulai) {
            $tahunAkademik = TahunAkademikHelper::fromPeriode($laporan->periode_mulai);
        } else {
            $tahunAkademik = TahunAkademikHelper::calculate();
        }

        // Get sections dari preview
        $sections = $preview['sections'] ?? [];

        // Check if OCR data is available
        $hasOCRData = $preview['has_ocr_data'] ?? false;
        $ocrData = $preview['ocr_data'] ?? [];

        Log::info('Sections from AI preview', [
            'section_count' => count($sections),
            'section_keys' => array_keys($sections),
            'has_ocr_data' => $hasOCRData,
            'ocr_images_count' => $hasOCRData ? ($ocrData['images_count'] ?? 0) : 0
        ]);

        // Map sections ke placeholders
        $placeholders = [
            'PERIODE'           => $periode,
            'TAHUN_AKADEMIK'    => $tahunAkademik,
            'LATAR_BELAKANG'    => $this->cleanMarkdown($sections['latar_belakang'] ?? ''),
            'DASAR'             => $this->cleanMarkdown($sections['dasar'] ?? ''),
            'TUJUAN'            => $this->cleanMarkdown($sections['tujuan'] ?? ''),
            'RUANG_LINGKUP'     => $this->cleanMarkdown($sections['ruang_lingkup'] ?? ''),
            'PROGRAM_KERJA'     => $this->cleanMarkdown($sections['program_kerja'] ?? ''),
            'PELAKSANAAN'       => $this->cleanMarkdown($sections['pelaksanaan'] ?? ''),
            'HAMBATAN'          => $this->cleanMarkdown($sections['hambatan'] ?? ''),
            'PEMECAHAN_MASALAH' => $this->cleanMarkdown($sections['pemecahan_masalah'] ?? ''),
            'EVALUASI'          => $this->cleanMarkdown($sections['evaluasi'] ?? ''),
            'SARAN'             => $this->cleanMarkdown($sections['rekomendasi'] ?? $sections['kesimpulan'] ?? ''),
        ];

        // Get uploaded images for LAMPIRAN_GAMBAR placeholder
        $uploadedImages = $this->getUploadedImages($laporan);
        $placeholders['LAMPIRAN_GAMBAR'] = $uploadedImages;

        // Log hasil mapping
        foreach ($placeholders as $key => $value) {
            if ($key !== 'PERIODE' && $key !== 'TAHUN_AKADEMIK') {
                Log::info("Mapped section to placeholder", [
                    'placeholder' => $key,
                    'content_length' => strlen($value),
                    'has_content' => !empty($value)
                ]);
            }
        }

        // Log OCR integration info
        if ($hasOCRData) {
            // Ensure combined_text is string for strlen
            $ocrText = $ocrData['combined_text'] ?? '';
            if (is_array($ocrText)) {
                $ocrText = json_encode($ocrText);
            } elseif (!is_string($ocrText)) {
                $ocrText = (string)$ocrText;
            }
            
            Log::info("Laporan generated with OCR data integration", [
                'ocr_images_processed' => $ocrData['images_count'] ?? 0,
                'ocr_text_length' => strlen($ocrText),
                'ocr_successful_images' => $ocrData['successful_count'] ?? 0
            ]);
        }

        return $placeholders;
    }

    /**
     * Clean markdown formatting untuk Word document
     * Preserve structure but remove markdown syntax
     */
    private function cleanMarkdown($text)
    {
        $text = trim($text);
        
        if (empty($text)) {
            return '';
        }
        
        // Remove markdown headers but keep the text
        $text = preg_replace('/^#{1,6}\s+/m', '', $text);
        
        // Convert bold to plain text (keep the text)
        $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
        $text = preg_replace('/__(.+?)__/', '$1', $text);
        
        // Convert italic to plain text (keep the text)
        $text = preg_replace('/\*(.+?)\*/', '$1', $text);
        $text = preg_replace('/_(.+?)_/', '$1', $text);
        
        // Convert bullet points to proper bullets
        $text = preg_replace('/^[\-\*]\s+/m', '• ', $text);
        
        // Convert numbered lists - keep the numbers
        // No change needed for numbered lists
        
        // Remove horizontal rules
        $text = preg_replace('/^---+$/m', '', $text);
        $text = preg_replace('/^\*\*\*+$/m', '', $text);
        
        // Remove code blocks
        $text = preg_replace('/```[\s\S]*?```/', '', $text);
        $text = preg_replace('/`(.+?)`/', '$1', $text);
        
        // Clean up multiple newlines (max 2)
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        // Trim each line
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        $text = implode("\n", $lines);
        
        return trim($text);
    }

    /**
     * Generate Laporan Semester (FALLBACK - jika tidak ada AI draft)
     */
    public function generateLegacy($laporanId)
    {
        Log::info("=== Starting Laporan Semester Generation ===", ['laporan_id' => $laporanId]);

        $laporan = LaporanGJM::with(['template'])->find($laporanId);
        
        if (!$laporan) {
            throw new \Exception("Laporan not found");
        }

        // Update status to processing
        $laporan->update(['status_laporan' => 'processing']);

        try {
            // 1. Collect data from GKM reports in the period
            $dataContext = $this->collectGKMDataForPeriod($laporan->periode_mulai, $laporan->periode_akhir);

            // 2. Get template context if available
            $templateContext = null;
            if ($laporan->template_id && $laporan->template->is_indexed) {
                $retrieval = $this->ragRetrievalService->retrieveContext(
                    "Laporan Semester GJM struktur dan format",
                    ['template_id' => $laporan->template_id]
                );
                $templateContext = $retrieval['context_text'];
            }

            // 3. Parse instruksi_prompt
            $instruksi = json_decode($laporan->instruksi_prompt, true);
            $periode = $instruksi['periode'] ?? 'Semester';  // "Semester Ganjil" atau "Semester Genap"
            $periodeSemester = $instruksi['periode_semester'] ?? 'ganjil';  // "ganjil" atau "genap"
            $tahun = $instruksi['tahun'] ?? date('Y');
            $judul = $instruksi['judul'] ?? 'Laporan Semester';

            Log::info('Parsed instruksi_prompt', [
                'periode' => $periode,
                'periode_semester' => $periodeSemester,
                'tahun' => $tahun,
                'judul' => $judul
            ]);

            // 4. Build prompt for AI Agent
            $prompt = $this->buildPrompt($dataContext, $templateContext, $periode, $tahun, $judul);

            // 5. Generate document using AI Agent
            $result = $this->aiAgentService->generateDocument($prompt, [
                'format' => 'placeholders',
                'template_id' => $laporan->template_id,
                'document_type' => 'laporan_semester',
                'placeholders' => [
                    'PERIODE' => $periode,
                    'TAHUN_AKADEMIK' => $tahun,
                    'LATAR_BELAKANG' => '',
                    'DASAR' => '',
                    'TUJUAN' => '',
                    'RUANG_LINGKUP' => '',
                    'PROGRAM_KERJA' => '',
                    'PELAKSANAAN' => '',
                    'HAMBATAN' => '',
                    'PEMECAHAN_MASALAH' => '',
                    'EVALUASI' => '',
                    'SARAN' => '',
                ]
            ]);

            // 6. Prepare placeholders (with fallback if AI fails)
            $finalPlaceholders = $this->preparePlaceholders($result, $periode, $tahun, $laporan);

            // 7. Generate Word document using template processor
            $wordPath = $this->generateWordDocument($laporan, $finalPlaceholders);

            // 8. Update laporan record
            $laporan->update([
                'dokumen_hasil_path' => $wordPath,
                'status_laporan' => 'completed',
                'analisis_kepatuhan' => $result['metadata']['summary'] ?? 'Laporan berhasil di-generate',
            ]);

            Log::info("Laporan Semester generated successfully", [
                'laporan_id' => $laporanId,
                'file_path' => $wordPath
            ]);

            return [
                'success' => true,
                'laporan_id' => $laporanId,
                'file_path' => $wordPath
            ];

        } catch (\Exception $e) {
            Log::error("Failed to generate Laporan Semester", [
                'laporan_id' => $laporanId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $laporan->update([
                'status_laporan' => 'failed',
                'catatan_review' => 'Error: ' . $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Collect GKM data for the specified period
     */
    private function collectGKMDataForPeriod($periodeMulai, $periodeAkhir)
    {
        Log::info("Collecting GKM data", [
            'periode_mulai' => $periodeMulai,
            'periode_akhir' => $periodeAkhir
        ]);

        $laporanGKM = LaporanBulanan::where('status', 'completed')
            ->whereBetween('created_at', [$periodeMulai, $periodeAkhir])
            ->with(['prodi', 'user'])
            ->get();

        $dataContext = [
            'total_laporan' => $laporanGKM->count(),
            'periode_mulai' => Carbon::parse($periodeMulai)->locale('id')->translatedFormat('d F Y'),
            'periode_akhir' => Carbon::parse($periodeAkhir)->locale('id')->translatedFormat('d F Y'),
            'laporan_per_prodi' => [],
        ];

        foreach ($laporanGKM->groupBy('prodi_id') as $prodiId => $laporans) {
            $prodi = $laporans->first()->prodi;
            $dataContext['laporan_per_prodi'][] = [
                'prodi_nama' => $prodi->nama_prodi ?? 'Unknown',
                'prodi_kode' => $prodi->kode_prodi ?? 'Unknown',
                'jumlah_laporan' => $laporans->count(),
            ];
        }

        return $dataContext;
    }

    /**
     * Build prompt for Semester report
     */
    private function buildPrompt($dataContext, $templateContext, $periode, $tahun, $judul)
    {
        $prompt = "Anda adalah AI Agent yang bertugas membuat Laporan Semester untuk Gugus Jaminan Mutu (GJM) Fakultas Vokasi Institut Teknologi Del.\n\n";
        
        $prompt .= "INFORMASI LAPORAN:\n";
        $prompt .= "- Judul: {$judul}\n";
        $prompt .= "- Periode: {$periode}\n";
        $prompt .= "- Tahun Akademik: {$tahun}\n\n";

        $prompt .= "DATA YANG TERSEDIA:\n";
        $prompt .= "- Total Laporan GKM: {$dataContext['total_laporan']}\n";
        $prompt .= "- Periode Data: {$dataContext['periode_mulai']} s/d {$dataContext['periode_akhir']}\n\n";

        if (!empty($dataContext['laporan_per_prodi'])) {
            $prompt .= "LAPORAN PER PRODI:\n";
            foreach ($dataContext['laporan_per_prodi'] as $prodi) {
                $prompt .= "- {$prodi['prodi_nama']} ({$prodi['prodi_kode']}): {$prodi['jumlah_laporan']} laporan\n";
            }
            $prompt .= "\n";
        }

        if ($templateContext) {
            $prompt .= "TEMPLATE YANG HARUS DIIKUTI:\n";
            $prompt .= $templateContext . "\n\n";
        }

        $prompt .= "INSTRUKSI:\n";
        $prompt .= "1. Buat laporan semester yang komprehensif berdasarkan data GKM yang tersedia\n";
        $prompt .= "2. Gunakan struktur template jika tersedia\n";
        $prompt .= "3. Isi placeholder dengan konten yang relevan\n";
        $prompt .= "4. Gunakan bahasa formal dan profesional\n\n";

        $prompt .= "Silakan generate laporan sekarang.";

        return $prompt;
    }

    /**
     * Prepare final placeholders from AI result with fallback values
     */
    private function preparePlaceholders($result, $periode, $tahun, $laporan)
    {
        if (!is_array($result)) {
            Log::warning('AI Agent returned invalid response, using fallback values');
            $result = ['success' => false, 'placeholders' => []];
        }

        $aiPlaceholders = $result['placeholders'] ?? [];

        // Calculate TAHUN_AKADEMIK based on periode_mulai from laporan
        // This ensures the academic year matches the report period, not current date
        if ($laporan && $laporan->periode_mulai) {
            $tahunAkademik = TahunAkademikHelper::fromPeriode($laporan->periode_mulai);
        } else {
            // Fallback: calculate from current date
            $tahunAkademik = TahunAkademikHelper::calculate();
        }

        Log::info('TAHUN_AKADEMIK calculated for Semester', [
            'periode_mulai' => $laporan->periode_mulai ?? 'N/A',
            'tahun_akademik' => $tahunAkademik
        ]);
        
        return [
            'PERIODE'           => $periode,
            'TAHUN_AKADEMIK'    => $tahunAkademik,
            'LATAR_BELAKANG'    => $this->cleanAiValue($aiPlaceholders['LATAR_BELAKANG']    ?? 'Belum tersedia - silakan isi manual'),
            'DASAR'             => $this->cleanAiValue($aiPlaceholders['DASAR']             ?? 'Belum tersedia - silakan isi manual'),
            'TUJUAN'            => $this->cleanAiValue($aiPlaceholders['TUJUAN']            ?? 'Belum tersedia - silakan isi manual'),
            'RUANG_LINGKUP'     => $this->cleanAiValue($aiPlaceholders['RUANG_LINGKUP']    ?? 'Belum tersedia - silakan isi manual'),
            'PROGRAM_KERJA'     => $this->cleanAiValue($aiPlaceholders['PROGRAM_KERJA']    ?? 'Belum tersedia - silakan isi manual'),
            'PELAKSANAAN'       => $this->cleanAiValue($aiPlaceholders['PELAKSANAAN']       ?? 'Belum tersedia - silakan isi manual'),
            'HAMBATAN'          => $this->cleanAiValue($aiPlaceholders['HAMBATAN']          ?? 'Belum tersedia - silakan isi manual'),
            'PEMECAHAN_MASALAH' => $this->cleanAiValue($aiPlaceholders['PEMECAHAN_MASALAH'] ?? 'Belum tersedia - silakan isi manual'),
            'EVALUASI'          => $this->cleanAiValue($aiPlaceholders['EVALUASI']          ?? 'Belum tersedia - silakan isi manual'),
            'SARAN'             => $this->cleanAiValue($aiPlaceholders['SARAN']             ?? 'Belum tersedia - silakan isi manual'),
        ];
    }

    /**
     * Strip leading/trailing curly braces that the AI sometimes wraps around values.
     * e.g. "{Some text returned by AI}" → "Some text returned by AI"
     */
    private function cleanAiValue(string $value): string
    {
        $value = trim($value);
        // Remove outer { } if the entire string is wrapped
        if (str_starts_with($value, '{') && str_ends_with($value, '}')) {
            $inner = substr($value, 1, -1);
            // Only strip if inner doesn't itself contain balanced braces (not a nested token)
            if (substr_count($inner, '{') === substr_count($inner, '}')) {
                $value = trim($inner);
            }
        }
        return $value;
    }

    /**
     * Generate Word document using TemplateProcessor with placeholders
     */
    private function generateWordDocument($laporan, $placeholders)
    {
        Log::info("Generating Word document", ['laporan_id' => $laporan->id]);

        $template = $laporan->template;
        if (!$template || !$template->file_path) {
            throw new \Exception("Template document not found");
        }

        $templatePath = storage_path('app/public/' . $template->file_path);
        if (!file_exists($templatePath)) {
            throw new \Exception("Template file not found: " . $templatePath);
        }

        $fileName = 'laporan_semester_' . time() . '.docx';
        $filePath = 'laporan_gjm/' . $fileName;
        $tempPath = storage_path('app/' . $filePath);

        $directory = dirname($tempPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        try {
            // Fix split XML runs BEFORE opening with TemplateProcessor
            $fixedTemplatePath = $this->fixSplitPlaceholders($templatePath);

            // Use TemplateProcessor with single-brace format (matching template)
            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($fixedTemplatePath);

            // Template uses {VAR} single braces
            $templateProcessor->setMacroChars('{', '}');

            $availableVars = $templateProcessor->getVariables();
            Log::info('Template variables found', [
                'vars' => $availableVars,
                'count' => count($availableVars)
            ]);

            // Log the placeholders we're trying to replace
            Log::info('Placeholders to replace', [
                'placeholders' => array_keys($placeholders),
                'TAHUN_AKADEMIK_value' => $placeholders['TAHUN_AKADEMIK'] ?? 'NOT SET'
            ]);

            $successCount = 0;

            // Replace placeholders
            foreach ($placeholders as $key => $value) {
                // Skip LAMPIRAN_GAMBAR - will be handled separately
                if ($key === 'LAMPIRAN_GAMBAR') {
                    continue;
                }

                if (is_array($value) || is_object($value))
                    continue;

                $stringValue = htmlspecialchars((string)$value, ENT_XML1, 'UTF-8');

                try {
                    $templateProcessor->setValue($key, $stringValue);
                    $successCount++;
                    Log::info("Replaced placeholder", ['key' => $key]);
                }
                catch (\Exception $e) {
                    Log::warning("Failed to replace placeholder", ['key' => $key, 'error' => $e->getMessage()]);
                }
            }

            Log::info('Placeholder replacement completed', ['success' => $successCount, 'total' => count($placeholders)]);

            // Handle LAMPIRAN_GAMBAR placeholder - insert images
            if (isset($placeholders['LAMPIRAN_GAMBAR']) && is_array($placeholders['LAMPIRAN_GAMBAR'])) {
                $this->insertImagesIntoDocument($templateProcessor, $placeholders['LAMPIRAN_GAMBAR']);
            }

            $templateProcessor->saveAs($tempPath);

            // Clean up temp fixed file if different from original
            if ($fixedTemplatePath !== $templatePath && file_exists($fixedTemplatePath)) {
                @unlink($fixedTemplatePath);
            }

            if (!file_exists($tempPath)) {
                throw new \Exception('File was not created');
            }

            Log::info('Word document generated', ['path' => $tempPath, 'size' => filesize($tempPath)]);

            return $filePath;

        }
        catch (\Exception $e) {
            Log::error('TemplateProcessor failed', ['error' => $e->getMessage()]);
            throw new \Exception('Failed to generate Word document: ' . $e->getMessage());
        }
    }

    /**
     * Fix split XML runs that prevent placeholder detection.
     *
     * Word often spells out {Periode} as four separate runs:
     *   <w:t>{</w:t>  <w:t>P</w:t>  <w:t>eriode</w:t>  <w:t>}</w:t>
     *
     * This method merges all <w:t> texts within each <w:p> paragraph,
     * detects any {Placeholder} tokens in the merged text, and rewrites
     * the XML so that each placeholder token lives in a single <w:t> node.
     */
    private function fixSplitPlaceholders(string $templatePath): string
    {
        $fixedPath = sys_get_temp_dir() . '/semester_fixed_' . time() . '.docx';
        copy($templatePath, $fixedPath);

        $zip = new \ZipArchive();
        if ($zip->open($fixedPath) !== TRUE) {
            Log::warning('Could not open template zip for fixing split placeholders');
            return $templatePath;
        }

        foreach (['word/document.xml'] as $xmlFile) {
            $content = $zip->getFromName($xmlFile);
            if ($content === false)
                continue;

            // Log original content snippet for debugging
            if (strpos($content, 'TAHUN_AKADEMIK') !== false || strpos($content, 'TAHUN AKADEMIK') !== false) {
                Log::info('Found TAHUN_AKADEMIK in template XML before fixing');
            }

            $fixed = $this->mergeRunsInParagraphs($content);
            
            // Log after fixing
            if (strpos($fixed, 'TAHUN_AKADEMIK') !== false) {
                Log::info('TAHUN_AKADEMIK still present in XML after fixing');
            }
            
            $zip->addFromString($xmlFile, $fixed);
        }

        $zip->close();
        return $fixedPath;
    }

    /**
     * Merge split <w:t> runs within each <w:p> so that placeholder
     * tokens like {Periode} or {LATAR_BELAKANG} end up in one run.
     *
     * Strategy per paragraph:
     *  1. Collect all <w:r> runs and their <w:t> text.
     *  2. Concatenate texts; find {TOKEN} with regex.
     *  3. For each token found, locate the subset of runs that together
     *     produce the token, set the first run's <w:t> to the full token
     *     text, and blank the remaining runs.
     */
    private function mergeRunsInParagraphs(string $xml): string
    {
        // Process paragraph by paragraph
        return preg_replace_callback(
            '/<w:p[ >].*?<\/w:p>/s',
            function ($paraMatch) {
            $para = $paraMatch[0];

            // Extract all runs (w:r elements)
            if (!preg_match_all('/<w:r(?:\s[^>]*)?>.*?<\/w:r>/s', $para, $runMatches)) {
                return $para;
            }

            $runs = $runMatches[0];

            // Get text from each run
            $texts = [];
            foreach ($runs as $i => $run) {
                preg_match_all('/<w:t[^>]*>([^<]*)<\/w:t>/', $run, $tm);
                $texts[$i] = implode('', $tm[1]);
            }

            $origCombined = implode('', $texts); // original (pre-normalization) per-run texts
            $origTexts    = $texts;               // keep original for offset calculation

            // Normalize double-brace variants and spaces in combined: 
            // {{VAR}} → {VAR}, {{ VAR}} → {VAR}, { VAR} → {VAR}, {VAR } → {VAR}
            // Also handle underscores: {TAHUN_AKADEMIK}
            $combined = preg_replace('/\{\{?\s*([A-Za-z][A-Za-z0-9_]*)\s*\}?\}/', '{$1}', $origCombined);

            if (!preg_match_all('/\{[A-Za-z][A-Za-z0-9_]*\}/', $combined, $tokenMatches)) {
                return $para; // no placeholders, nothing to fix
            }

            // For each normalized token, find which runs it spans in the ORIGINAL text and consolidate
            foreach ($tokenMatches[0] as $token) {
                $tokenName = substr($token, 1, -1); // e.g. PERIODE or TAHUN_AKADEMIK

                // Search for the un-normalized variant in origCombined
                // Handles: {{VAR}}, {{ VAR}}, { VAR}, {VAR }, {VAR}
                $origPattern = '/\{\{?\s*' . preg_quote($tokenName, '/') . '\s*\}?\}/';
                if (!preg_match($origPattern, $origCombined, $om, PREG_OFFSET_CAPTURE))
                    continue;

                $tokenStart   = $om[0][1];          // byte offset in origCombined
                $origTokenLen = safe_strlen($om[0][0]);   // length of original (possibly {{VAR}}) text

                // Build cumulative offsets to find which run(s) contain the token
                $cumulative = 0;
                $startRun   = -1;
                $endRun     = -1;
                foreach ($origTexts as $i => $t) {
                    $runStart = $cumulative;
                    $runEnd   = $cumulative + strlen($t);

                    if ($startRun === -1 && $tokenStart < $runEnd && $tokenStart >= $runStart)
                        $startRun = $i;
                    if ($startRun !== -1 && ($tokenStart + $origTokenLen) <= $runEnd) {
                        $endRun = $i;
                        break;
                    }
                    $cumulative = $runEnd;
                }

                if ($startRun === -1 || $endRun === -1) continue;

                // Put normalized single-brace token {VAR} into startRun
                $runs[$startRun] = preg_replace(
                    '/<w:t[^>]*>[^<]*<\/w:t>/',
                    '<w:t xml:space="preserve">' . $token . '</w:t>',
                    $runs[$startRun],
                    1
                );
                // Remove extra <w:t> occurrences in startRun (keep only first)
                $runs[$startRun] = preg_replace(
                    '/(<w:t[^>]*>[^<]*<\/w:t>)(?:.*?<w:t[^>]*>[^<]*<\/w:t>)+/s',
                    '$1',
                    $runs[$startRun]
                );

                for ($j = $startRun + 1; $j <= $endRun; $j++) {
                    $runs[$j] = preg_replace('/<w:t[^>]*>[^<]*<\/w:t>/', '<w:t></w:t>', $runs[$j]);
                }

                // Update origTexts so subsequent token searches see clean offsets
                $origTexts[$startRun] = $token;
                for ($j = $startRun + 1; $j <= $endRun; $j++) $origTexts[$j] = '';
                $origCombined = implode('', $origTexts);
            }

            // Rebuild paragraph by replacing original runs with fixed runs
            $fixedPara = $para;
            foreach ($runs as $i => $fixedRun) {
                $fixedPara = str_replace($runMatches[0][$i], $fixedRun, $fixedPara);
            }

            return $fixedPara;
        },
            $xml
        );
    }

    /**
     * Get uploaded images from laporan OCR data or vector database
     */
    private function getUploadedImages($laporan)
    {
        $images = [];

        try {
            // Method 1: Check ocr_uploads folder directly
            $ocrUploadPath = storage_path("app/public/ocr_uploads/{$laporan->id}");
            
            if (is_dir($ocrUploadPath)) {
                $files = scandir($ocrUploadPath);
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') continue;
                    
                    $fullPath = $ocrUploadPath . '/' . $file;
                    if (is_file($fullPath) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file)) {
                        // Get image dimensions
                        $imageInfo = @getimagesize($fullPath);
                        $originalWidth = $imageInfo[0] ?? 800;
                        $originalHeight = $imageInfo[1] ?? 600;
                        
                        // Calculate scaled dimensions (max width 400px, maintain aspect ratio)
                        $maxWidth = 400;
                        $scale = $maxWidth / $originalWidth;
                        $width = $maxWidth;
                        $height = (int)($originalHeight * $scale);
                        
                        $images[] = [
                            'path' => $fullPath,
                            'filename' => $file,
                            'width' => $width,
                            'height' => $height,
                        ];
                    }
                }
            }

            // Method 2: Check if laporan has OCR data with image paths
            if (empty($images) && $laporan->has_ocr_data && !empty($laporan->ocr_data)) {
                $ocrData = is_array($laporan->ocr_data) ? $laporan->ocr_data : json_decode($laporan->ocr_data, true);
                
                if (isset($ocrData['images']) && is_array($ocrData['images'])) {
                    foreach ($ocrData['images'] as $imageData) {
                        if (isset($imageData['path']) && file_exists(storage_path('app/' . $imageData['path']))) {
                            $images[] = [
                                'path' => storage_path('app/' . $imageData['path']),
                                'filename' => $imageData['filename'] ?? basename($imageData['path']),
                                'width' => 400,
                                'height' => 300,
                            ];
                        }
                    }
                }
            }

            // Method 3: Check vector database for image metadata
            if (empty($images)) {
                $chunks = \App\Models\DocumentChunk::where('source_type', 'laporan_gjm_ocr')
                    ->where('source_id', $laporan->id)
                    ->get();

                foreach ($chunks as $chunk) {
                    $metadata = is_array($chunk->metadata) ? $chunk->metadata : json_decode($chunk->metadata, true);
                    
                    if (isset($metadata['type']) && $metadata['type'] === 'ocr_image' && isset($metadata['image_path'])) {
                        $imagePath = storage_path('app/' . $metadata['image_path']);
                        
                        if (file_exists($imagePath)) {
                            // Check if not already added
                            $alreadyAdded = false;
                            foreach ($images as $img) {
                                if ($img['path'] === $imagePath) {
                                    $alreadyAdded = true;
                                    break;
                                }
                            }
                            
                            if (!$alreadyAdded) {
                                $images[] = [
                                    'path' => $imagePath,
                                    'filename' => $metadata['filename'] ?? basename($imagePath),
                                    'width' => 400,
                                    'height' => 300,
                                ];
                            }
                        }
                    }
                }
            }

            Log::info('Retrieved uploaded images for LAMPIRAN_GAMBAR', [
                'laporan_id' => $laporan->id,
                'images_count' => count($images),
                'method' => !empty($images) ? 'ocr_uploads_folder' : 'not_found',
                'ocr_upload_path' => $ocrUploadPath ?? 'N/A'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve uploaded images', [
                'laporan_id' => $laporan->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $images;
    }

    /**
     * Insert images into Word document at LAMPIRAN_GAMBAR placeholder
     */
    private function insertImagesIntoDocument($templateProcessor, $images)
    {
        try {
            if (empty($images)) {
                // Replace with empty text if no images
                $templateProcessor->setValue('LAMPIRAN_GAMBAR', '');
                Log::info('No images to insert for LAMPIRAN_GAMBAR');
                return;
            }

            Log::info('Inserting images into document', [
                'images_count' => count($images)
            ]);

            // Check if placeholder exists
            $variables = $templateProcessor->getVariables();
            if (!in_array('LAMPIRAN_GAMBAR', $variables)) {
                Log::warning('LAMPIRAN_GAMBAR placeholder not found in template');
                return;
            }

            // Clone the block for multiple images if needed
            if (count($images) > 1) {
                try {
                    $templateProcessor->cloneBlock('LAMPIRAN_GAMBAR', count($images), true, true);
                } catch (\Exception $e) {
                    // If cloneBlock fails, we'll just replace with all images at once
                    Log::warning('Failed to clone LAMPIRAN_GAMBAR block, will insert all images at once', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Insert each image
            foreach ($images as $index => $imageData) {
                $imagePath = $imageData['path'];
                $filename = $imageData['filename'];
                
                if (!file_exists($imagePath)) {
                    Log::warning('Image file not found', ['path' => $imagePath]);
                    continue;
                }

                try {
                    // Get image dimensions
                    $imageInfo = getimagesize($imagePath);
                    $originalWidth = $imageInfo[0] ?? 800;
                    $originalHeight = $imageInfo[1] ?? 600;
                    
                    // Calculate scaled dimensions (max width 400px, maintain aspect ratio)
                    $maxWidth = 400;
                    $scale = $maxWidth / $originalWidth;
                    $width = $maxWidth;
                    $height = (int)($originalHeight * $scale);

                    // Use setImageValue to insert image
                    $variableName = count($images) > 1 ? "LAMPIRAN_GAMBAR#{$index}" : 'LAMPIRAN_GAMBAR';
                    
                    $templateProcessor->setImageValue(
                        $variableName,
                        [
                            'path' => $imagePath,
                            'width' => $width,
                            'height' => $height,
                            'ratio' => true
                        ]
                    );

                    Log::info('Image inserted successfully', [
                        'index' => $index,
                        'filename' => $filename,
                        'width' => $width,
                        'height' => $height
                    ]);

                } catch (\Exception $e) {
                    Log::error('Failed to insert image', [
                        'index' => $index,
                        'filename' => $filename,
                        'error' => $e->getMessage()
                    ]);
                    
                    // Fallback: replace with filename text
                    try {
                        $templateProcessor->setValue($variableName, "Gambar: {$filename}");
                    } catch (\Exception $e2) {
                        Log::error('Failed to set fallback text for image', [
                            'error' => $e2->getMessage()
                        ]);
                    }
                }
            }

            Log::info('All images processed for LAMPIRAN_GAMBAR', [
                'total_images' => count($images)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to insert images into document', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback: replace with text
            try {
                $templateProcessor->setValue('LAMPIRAN_GAMBAR', count($images) . ' gambar dilampirkan');
            } catch (\Exception $e2) {
                Log::error('Failed to set fallback text', ['error' => $e2->getMessage()]);
            }
        }
    }
}
