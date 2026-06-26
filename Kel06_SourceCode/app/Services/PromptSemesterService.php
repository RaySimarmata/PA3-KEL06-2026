<?php

namespace App\Services;

use App\Models\TemplateLaporan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * AI Prompt Service khusus Laporan Semester
 *
 * Menggunakan ClaudeAIService (Anthropic Claude langsung atau Groq fallback)
 * untuk membaca file, membuat summary, dan merespon instruksi user.
 */
class PromptSemesterService
{
    protected ClaudeAIService      $claude;
    protected TextExtractionService $textExtractor;
    protected RAGRetrievalService   $ragRetrieval;

    public function __construct(
        ClaudeAIService       $claude,
        TextExtractionService $textExtractor,
        RAGRetrievalService   $ragRetrieval
    ) {
        $this->claude        = $claude;
        $this->textExtractor = $textExtractor;
        $this->ragRetrieval  = $ragRetrieval;
    }

    // =========================================================================
    // PUBLIC API
    // =========================================================================

    /**
     * Proses prompt user + (opsional) file contoh laporan GKM
     * dan hasilkan draft konten laporan Semester.
     */
    public function processPrompt(
        string        $userPrompt,
        ?UploadedFile $referenceFile,
        ?int          $templateId,
        array         $periodeInfo,
        string        $previousDraft = ''
    ): array {
        Log::info('=== PromptSemesterService::processPrompt START ===', [
            'template_id' => $templateId,
            'has_file'    => $referenceFile !== null,
            'has_prev'    => !empty($previousDraft),
            'periode'     => $periodeInfo['label'] ?? '-',
        ]);

        try {
            // 1. Ekstrak teks file jika diupload
            $fileContext = '';
            if ($referenceFile) {
                $fileContext = $this->extractReferenceFile($referenceFile);
            }

            // 2. Template context via RAG
            $templateContext = '';
            if ($templateId) {
                $templateContext = $this->fetchTemplateContext($templateId);
            }

            // 3. System prompt
            $systemPrompt = $this->buildSystemPrompt($periodeInfo, $templateContext);

            // 4. Pilih strategi: follow-up atau fresh
            if (!empty($previousDraft)) {
                $rawResponse = $this->claude->followUp(
                    $systemPrompt,
                    $previousDraft,
                    $userPrompt,
                    $fileContext,
                    2000
                );
            } else {
                $userMessage = $this->buildUserMessage($userPrompt, $fileContext, $periodeInfo);
                $rawResponse = $this->claude->ask($systemPrompt, $userMessage, 2000);
            }

            if (!$rawResponse) {
                throw new \Exception('AI tidak menghasilkan respons. Model: ' . $this->claude->getModelInfo());
            }

            $sections = $this->parseResponseToSections($rawResponse);

            Log::info('=== PromptSemesterService::processPrompt SUCCESS ===', [
                'model'           => $this->claude->getModelInfo(),
                'response_length' => safe_strlen($rawResponse),
                'sections_count'  => count($sections),
            ]);

            return [
                'success'  => true,
                'preview'  => $rawResponse,
                'sections' => $sections,
                'message'  => 'AI (' . $this->claude->getModelInfo() . ') berhasil menganalisis instruksi Anda.',
            ];

        } catch (\Exception $e) {
            Log::error('PromptSemesterService::processPrompt FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success'  => false,
                'preview'  => '',
                'sections' => [],
                'message'  => 'Gagal memproses: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Baca file yang diupload dan buat summary otomatis — seperti Claude.ai.
     */
    public function readAndSummarizeFile(
        UploadedFile $file,
        ?int         $templateId,
        array        $periodeInfo
    ): array {
        Log::info('=== PromptSemesterService::readAndSummarizeFile START ===', [
            'file_name'   => $file->getClientOriginalName(),
            'file_size'   => $file->getSize(),
            'template_id' => $templateId,
            'periode'     => $periodeInfo['label'] ?? '-',
            'ai_model'    => $this->claude->getModelInfo(),
        ]);

        try {
            // 1. Ekstrak teks
            $extractedText = $this->extractReferenceFile($file);

            if (empty($extractedText)) {
                return [
                    'success'        => false,
                    'preview'        => '',
                    'sections'       => [],
                    'message'        => 'Tidak dapat membaca isi file. Pastikan file tidak rusak dan formatnya didukung (DOCX, PDF, TXT).',
                    'extracted_text' => '',
                ];
            }

            // 2. Template context
            $templateContext = '';
            if ($templateId) {
                $templateContext = $this->fetchTemplateContext($templateId);
            }

            $periode = $periodeInfo['label'] ?? 'Semester';
            $tahun   = $periodeInfo['tahun'] ?? date('Y');
            $judul   = $periodeInfo['judul'] ?? 'Laporan Semester GJM';

            // 3. System prompt
            $systemPrompt  = "Anda adalah AI assistant yang ahli membaca, menganalisis, dan merangkum dokumen akademik.\n";
            $systemPrompt .= "Anda membantu membuat Laporan Semester GJM (Gugus Jaminan Mutu) Fakultas Vokasi Institut Teknologi Del.\n";
            $systemPrompt .= "Anda akan diberikan isi dokumen laporan GJM/GKM dan diminta membacanya secara menyeluruh,\n";
            $systemPrompt .= "kemudian membuat rangkuman dan draft laporan Semester berdasarkan isi dokumen tersebut.\n";
            $systemPrompt .= "Berikan respons dalam Bahasa Indonesia yang formal dan profesional.\n";
            $systemPrompt .= "Pastikan semua informasi yang Anda tuliskan berasal dari dokumen yang diberikan.\n";
            $systemPrompt .= "\nInformasi Laporan:\n";
            $systemPrompt .= "- Jenis     : Laporan Semester GJM\n";
            $systemPrompt .= "- Periode   : {$periode} {$tahun}\n";
            $systemPrompt .= "- Judul     : {$judul}\n";
            $systemPrompt .= "- Institusi : Fakultas Vokasi, Institut Teknologi Del\n";

            if (!empty($templateContext)) {
                $systemPrompt .= "\nFormat template laporan:\n" . $templateContext;
            }

            // 4. Instruksi struktur output
            $instructions  = "Setelah membaca dokumen, buat draft laporan dengan struktur berikut:\n\n";
            $instructions .= "# RINGKASAN DOKUMEN\n[Ringkasan komprehensif isi dokumen]\n\n";
            $instructions .= "# POIN-POIN UTAMA\n[Daftar poin penting]\n\n";
            $instructions .= "# DATA DAN TEMUAN\n[Data, angka, temuan penting]\n\n";
            $instructions .= "# LATAR BELAKANG\n[Berdasarkan dokumen]\n\n";
            $instructions .= "# DASAR HUKUM\n[Berdasarkan dokumen]\n\n";
            $instructions .= "# TUJUAN\n[Berdasarkan dokumen]\n\n";
            $instructions .= "# RUANG LINGKUP\n[Berdasarkan dokumen]\n\n";
            $instructions .= "# PROGRAM KERJA\n[Berdasarkan dokumen]\n\n";
            $instructions .= "# PELAKSANAAN KEGIATAN\n[Berdasarkan dokumen]\n\n";
            $instructions .= "# HAMBATAN DAN KENDALA\n[Berdasarkan dokumen]\n\n";
            $instructions .= "# PEMECAHAN MASALAH\n[Berdasarkan dokumen]\n\n";
            $instructions .= "# EVALUASI DAN ANALISIS\n[Berdasarkan dokumen]\n\n";
            $instructions .= "# REKOMENDASI DAN SARAN\n[Berdasarkan dokumen]\n\n";
            $instructions .= "PENTING: Gunakan Bahasa Indonesia formal. Dasarkan SEMUA konten pada isi dokumen yang diupload.";

            // 5. Panggil Claude
            $rawResponse = $this->claude->readAndSummarize(
                $extractedText,
                $systemPrompt,
                $instructions,
                2000
            );

            if (!$rawResponse) {
                throw new \Exception('AI tidak menghasilkan respons. Model: ' . $this->claude->getModelInfo());
            }

            $sections = $this->parseResponseToSections($rawResponse);

            Log::info('=== PromptSemesterService::readAndSummarizeFile SUCCESS ===', [
                'model'            => $this->claude->getModelInfo(),
                'response_length'  => safe_strlen($rawResponse),
                'sections_count'   => count($sections),
                'extracted_length' => safe_strlen($extractedText),
            ]);

            return [
                'success'        => true,
                'preview'        => $rawResponse,
                'sections'       => $sections,
                'message'        => "AI (" . $this->claude->getModelInfo() . ") berhasil membaca dan merangkum isi file \"{$file->getClientOriginalName()}\".",
                'extracted_text' => mb_substr($extractedText, 0, 500) . (safe_strlen($extractedText) > 500 ? '...' : ''),
            ];

        } catch (\Exception $e) {
            Log::error('PromptSemesterService::readAndSummarizeFile FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success'        => false,
                'preview'        => '',
                'sections'       => [],
                'message'        => 'Gagal membaca file: ' . $e->getMessage(),
                'extracted_text' => '',
            ];
        }
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function buildSystemPrompt(array $periodeInfo, string $templateContext): string
    {
        $periode = $periodeInfo['label'] ?? 'Semester';
        $tahun   = $periodeInfo['tahun'] ?? date('Y');
        $judul   = $periodeInfo['judul'] ?? 'Laporan Semester GJM';

        $system  = "Anda adalah AI assistant ahli penjaminan mutu akademik yang membantu membuat ";
        $system .= "Laporan Semester GJM (Gugus Jaminan Mutu) Fakultas Vokasi Institut Teknologi Del.\n";
        $system .= "Berikan respons dalam Bahasa Indonesia yang formal dan profesional.\n";
        $system .= "Ikuti instruksi user secara tepat, gunakan data/file referensi yang diberikan,\n";
        $system .= "dan hasilkan konten laporan yang komprehensif.\n";
        $system .= "Jika user memberikan arahan berbeda dari draft sebelumnya, IKUTI arahan user tersebut.\n\n";
        $system .= "Informasi Laporan:\n";
        $system .= "- Jenis     : Laporan Semester GJM\n";
        $system .= "- Periode   : {$periode} {$tahun}\n";
        $system .= "- Judul     : {$judul}\n";
        $system .= "- Institusi : Fakultas Vokasi, Institut Teknologi Del\n";

        if (!empty($templateContext)) {
            $system .= "\nFormat template laporan:\n" . $templateContext;
        }

        return $system;
    }

    private function buildUserMessage(string $userPrompt, string $fileContext, array $periodeInfo): string
    {
        $message = '';

        if (!empty($fileContext)) {
            $message .= "Berikut adalah isi dokumen referensi yang saya upload:\n\n";
            $message .= "```\n" . $fileContext . "\n```\n\n";
        }

        $message .= "Instruksi dari saya:\n" . $userPrompt . "\n\n";
        $message .= "Hasilkan draft laporan semester lengkap dengan struktur bagian-bagian (gunakan heading # Markdown).\n";
        $message .= "Gunakan Bahasa Indonesia formal dan profesional.";

        return $message;
    }

    private function extractReferenceFile(UploadedFile $file): string
    {
        try {
            $tempPath = $file->store('temp_prompt_refs', 'local');
            $fullPath = storage_path('app/' . $tempPath);

            Log::info('Extracting reference file (semester)', [
                'original_name' => $file->getClientOriginalName(),
                'temp_path'     => $fullPath,
            ]);

            $extracted = $this->textExtractor->extractFromFile($fullPath);

            @unlink($fullPath);

            if (!$extracted['success'] || empty($extracted['text'])) {
                Log::warning('Reference file extraction empty (semester)');
                return '';
            }

            $text = $this->textExtractor->cleanText($extracted['text']);
            // Groq free tier: ~6k TPM. Batasi input agar tidak rate limited.
            return mb_substr($text, 0, 8000);

        } catch (\Exception $e) {
            Log::warning('Failed to extract reference file (semester)', ['error' => $e->getMessage()]);
            return '';
        }
    }

    private function fetchTemplateContext(int $templateId): string
    {
        try {
            $template = TemplateLaporan::find($templateId);
            if (!$template || !$template->is_indexed) {
                return '';
            }

            $retrieval = $this->ragRetrieval->retrieveContext(
                'Struktur format dan isi Laporan Semester GJM',
                ['template_id' => $templateId]
            );

            return $retrieval['context_text'] ?? '';
        } catch (\Exception $e) {
            Log::warning('Failed to fetch template context (semester)', ['error' => $e->getMessage()]);
            return '';
        }
    }

    private function parseResponseToSections(string $response): array
    {
        $sections = [];
        $parts    = preg_split('/^#{1,2}\s+/m', $response);

        if (count($parts) <= 1) {
            return [['title' => 'Konten Laporan', 'content' => trim($response)]];
        }

        array_shift($parts);

        preg_match_all('/^#{1,2}\s+([^\n]+)/m', $response, $headings);
        $titles = $headings[1] ?? [];

        foreach ($parts as $i => $content) {
            $title      = isset($titles[$i]) ? trim($titles[$i]) : ('Bagian ' . ($i + 1));
            $sections[] = ['title' => $title, 'content' => trim($content)];
        }

        return $sections;
    }
}
