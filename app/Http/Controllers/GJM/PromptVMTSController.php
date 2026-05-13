<?php

namespace App\Http\Controllers\GJM;

use App\Http\Controllers\Controller;
use App\Services\UnifiedAIService;
use App\Services\TextExtractionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;

/**
 * PromptVMTSController
 *
 * Standalone AI Assistant untuk Laporan VMTS.
 * User upload Excel (data kuesioner) + PDF/Word (contoh laporan sebelumnya).
 * AI membaca keduanya dan langsung menghasilkan analisis + tombol download Word.
 * TIDAK menggunakan sistem template atau draft seperti Triwulan/Semester.
 */
class PromptVMTSController extends Controller
{
    protected UnifiedAIService $aiService;
    protected TextExtractionService $extractor;

    public function __construct(UnifiedAIService $aiService, TextExtractionService $extractor)
    {
        $this->aiService   = $aiService;
        $this->extractor   = $extractor;
    }

    /**
     * Tampilkan halaman AI Assistant VMTS (standalone chat).
     */
    public function index()
    {
        return view('gjm.buat-laporan.vmts-ai');
    }

    /**
     * Endpoint: POST /buat-laporan/vmts-ai/chat
     *
     * Menerima file Excel (data kuesioner) dan/atau PDF/Word (contoh laporan),
     * mengekstrak teks, lalu meminta AI menganalisis dan menghasilkan laporan
     * bergaya laporan VMTS (seperti contoh yang diberikan).
     *
     * File yang didukung:
     *   - excel_files[]:  xlsx, xls  → data kuesioner / hasil survei
     *   - ref_files[]:    docx, pdf, txt → contoh laporan sebelumnya
     *   - prompt:         instruksi tambahan dari user (opsional)
     */
    public function chat(Request $request)
    {
        $request->validate([
            'prompt'          => 'nullable|string|max:5000',
            'excel_files.*'   => 'nullable|file|mimes:xlsx,xls|max:20480',
            'ref_files.*'     => 'nullable|file|mimes:docx,doc,pdf,txt|max:20480',
        ]);

        try {
            $userPrompt    = $request->input('prompt', '');
            $excelContext  = '';
            $refContext    = '';
            $uploadedNames = [];

            // ── 1. Proses file Excel (data kuesioner) ────────────────────────
            if ($request->hasFile('excel_files')) {
                $excelFiles = $request->file('excel_files');
                $excelParts = [];

                foreach ($excelFiles as $file) {
                    $fileName = $file->getClientOriginalName();
                    $path     = $file->storeAs('tmp/vmts_ai', uniqid() . '_' . $fileName, 'local');
                    $fullPath = storage_path('app/' . $path);

                    Log::info('PromptVMTS: processing Excel', ['name' => $fileName]);

                    $extraction  = $this->extractor->extractFromFile($fullPath);
                    $textContent = $this->extractor->cleanText($extraction['text'] ?? '');

                    Storage::disk('local')->delete($path);

                    if (!empty($textContent)) {
                        $uploadedNames[] = $fileName;
                        $excelParts[]    = "=== DATA KUESIONER: {$fileName} ===\n{$textContent}\n";
                    } else {
                        Log::warning('PromptVMTS: empty text from Excel', ['file' => $fileName]);
                    }
                }

                $excelContext = implode("\n\n", $excelParts);
            }

            // ── 2. Proses file referensi (PDF/Word contoh laporan) ───────────
            if ($request->hasFile('ref_files')) {
                $refFiles  = $request->file('ref_files');
                $refParts  = [];

                foreach ($refFiles as $file) {
                    $fileName = $file->getClientOriginalName();
                    $path     = $file->storeAs('tmp/vmts_ai', uniqid() . '_' . $fileName, 'local');
                    $fullPath = storage_path('app/' . $path);

                    Log::info('PromptVMTS: processing ref file', ['name' => $fileName]);

                    $extraction  = $this->extractor->extractFromFile($fullPath);
                    $textContent = $this->extractor->cleanText($extraction['text'] ?? '');

                    Storage::disk('local')->delete($path);

                    if (!empty($textContent)) {
                        $uploadedNames[] = $fileName;
                        $refParts[]      = "=== CONTOH LAPORAN REFERENSI: {$fileName} ===\n{$textContent}\n";
                    }
                }

                $refContext = implode("\n\n", $refParts);
            }

            // ── 3. Validasi: minimal ada satu konteks ─────────────────────────
            $conversationHistory = json_decode($request->input('conversation_history', '[]'), true) ?? [];

            $hasContext = !empty($excelContext) || !empty($refContext)
                || !empty($userPrompt)
                || count($conversationHistory) > 0;

            if (!$hasContext) {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan upload file Excel (data kuesioner) dan/atau file PDF/Word sebagai contoh laporan.',
                ]);
            }

            // ── 4. Bangun system prompt ───────────────────────────────────────
            $systemPrompt = $this->buildVMTSSystemPrompt();

            // ── 5. Bangun full prompt ─────────────────────────────────────────
            $fullPrompt = $systemPrompt . "\n\n";

            // Inject riwayat percakapan sebelumnya (multi-turn)
            if (!empty($conversationHistory)) {
                $fullPrompt .= "=== RIWAYAT PERCAKAPAN SEBELUMNYA ===\n";
                foreach ($conversationHistory as $turn) {
                    $role     = $turn['role'] === 'user' ? 'USER' : 'ASSISTANT';
                    $content  = is_string($turn['content']) ? $turn['content'] : '';
                    $fullPrompt .= "[{$role}]: {$content}\n\n";
                }
                $fullPrompt .= "================================\n\n";
            }

            if (!empty($excelContext)) {
                // Truncate if too long (max 30k chars)
                $truncated    = strlen($excelContext) > 30000
                    ? substr($excelContext, 0, 30000) . "\n\n[...data terpotong karena terlalu panjang...]"
                    : $excelContext;
                $fullPrompt  .= "=== DATA KUESIONER / SURVEI (dari Excel) ===\n{$truncated}\n\n";
            }

            if (!empty($refContext)) {
                $truncatedRef = strlen($refContext) > 25000
                    ? substr($refContext, 0, 25000) . "\n\n[...referensi terpotong...]"
                    : $refContext;
                $fullPrompt  .= "=== CONTOH LAPORAN SEBELUMNYA (gunakan sebagai template format) ===\n{$truncatedRef}\n\n";
            }

            if (!empty($userPrompt)) {
                $fullPrompt .= "=== INSTRUKSI TAMBAHAN DARI USER ===\n{$userPrompt}\n\n";
            }

            $fullPrompt .= "Berdasarkan semua data dan referensi di atas, hasilkan laporan analisis survei VMTS yang lengkap dan profesional sesuai dengan format contoh laporan yang diberikan.";

            // ── 6. Panggil AI ─────────────────────────────────────────────────
            // Truncate full prompt if > 60k chars to prevent token limit errors
            if (strlen($fullPrompt) > 60000) {
                $fullPrompt = substr($fullPrompt, 0, 60000) . "\n\n[prompt terpotong - harap buat analisis berdasarkan data di atas]";
            }

            $aiResult = $this->aiService->generateText($fullPrompt, ['max_tokens' => 4096]);

            if (!$aiResult['success'] || empty($aiResult['text'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Layanan AI sedang tidak tersedia. Silakan coba lagi dalam beberapa menit.',
                ], 503);
            }

            $aiResponse = $aiResult['text'];

            // ── 7. Parse sections untuk akordeon ─────────────────────────────
            $sections = $this->parseSections($aiResponse);

            return response()->json([
                'success'        => true,
                'preview'        => $aiResponse,
                'sections'       => $sections,
                'model'          => ($aiResult['provider'] ?? 'AI') . ' (' . ($aiResult['model'] ?? 'unknown') . ')',
                'uploaded_files' => $uploadedNames,
            ]);

        } catch (\Exception $e) {
            Log::error('PromptVMTS chat error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Endpoint: POST /buat-laporan/vmts-ai/download-word
     *
     * Menghasilkan file .docx dari teks laporan yang dihasilkan AI,
     * lalu langsung mengirimkannya sebagai download.
     */
    public function downloadWord(Request $request)
    {
        $request->validate([
            'laporan_text' => 'required|string',
            'judul'        => 'nullable|string|max:255',
        ]);

        try {
            $laporanText = $request->input('laporan_text');
            $judul       = $request->input('judul', 'Laporan Analisis VMTS');

            // Buat file Word menggunakan PhpWord
            $phpWord = new PhpWord();

            // Global font settings
            $phpWord->setDefaultFontName('Times New Roman');
            $phpWord->setDefaultFontSize(12);

            // Tambahkan section
            $section = $phpWord->addSection([
                'marginTop'    => 1440,  // 1 inch in twips
                'marginBottom' => 1440,
                'marginLeft'   => 1800,  // 1.25 inch
                'marginRight'  => 1440,
            ]);

            // Parse dan render teks markdown ke Word
            $this->renderMarkdownToWord($section, $laporanText, $judul);

            // Generate filename
            $timestamp = date('Ymd_His');
            $filename  = 'Laporan_VMTS_' . $timestamp . '.docx';
            $tempPath  = storage_path('app/tmp/vmts_word/' . $filename);

            // Pastikan direktori ada
            if (!is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            // Simpan file Word
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempPath);

            // Kirim sebagai download
            return response()->download($tempPath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('PromptVMTS downloadWord error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat file Word: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Build system prompt untuk laporan analisis VMTS.
     */
    private function buildVMTSSystemPrompt(): string
    {
        return <<<'PROMPT'
Kamu adalah AI Assistant yang ahli dalam menganalisis data survei dan membuat laporan analisis akademik untuk institusi pendidikan tinggi di Indonesia, khususnya Gugus Jaminan Mutu (GJM) dan Gugus Kendali Mutu (GKM).

TUGAS UTAMAMU:
Menganalisis data kuesioner survei pemahaman Visi, Misi, Tujuan, dan Sasaran (VMTS) yang diberikan dalam bentuk Excel, kemudian menghasilkan laporan analisis yang komprehensif dan profesional — mengikuti format dan gaya penulisan dari contoh laporan yang diberikan sebagai referensi.

STRUKTUR LAPORAN YANG HARUS DIHASILKAN (ikuti persis format ini):

# LAPORAN ANALISIS DATA HASIL SURVEI [JUDUL SESUAI KONTEKS]

## I. Pendahuluan
[Uraikan latar belakang survei, tujuan, dan kelompok responden]

## II. Metode Penelitian
[Jelaskan metode pengumpulan data, skala yang digunakan, dan metode analisis]

## III. Hasil Analisis Deskriptif

### 1. Gambaran Umum Responden
[Buat tabel ringkasan responden: unit/prodi, jumlah responden, skala, catatan]

### 2. Analisis Per Butir Pertanyaan
[Buat tabel analisis per pertanyaan dengan kolom: No, Aspek yang Dinilai, nilai per unit/prodi, Interpretasi]

## IV. Pembahasan

### 1. Pola Umum
[Analisis pola yang ditemukan dari data (bullet points)]

### 2. Analisis Komparatif
[Tabel perbandingan antar unit/prodi untuk setiap aspek]

[Paragraf pembahasan panjang dan mendalam berdasarkan data]

## V. Kesimpulan
[Numbered list kesimpulan yang jelas dan spesifik]

## VI. Rekomendasi
[Numbered list rekomendasi strategis yang dapat ditindaklanjuti]

ATURAN PENTING:
1. Gunakan Bahasa Indonesia formal dan akademik
2. Jika ada data numerik (mean, median, variance) dari Excel → tampilkan dalam tabel
3. Jika tidak ada data numerik → estimasi berdasarkan distribusi jawaban yang tersedia
4. Buat tabel menggunakan format markdown (|---|---|)
5. Setiap bagian HARUS memiliki konten yang substantif (minimal 2-3 paragraf untuk IV. Pembahasan)
6. Ikuti gaya penulisan dan kedalaman analisis dari contoh laporan yang diberikan
7. Sebutkan nama prodi/unit yang spesifik sesuai data yang tersedia
8. JANGAN skip bagian manapun
9. Output harus langsung berupa laporan (bukan penjelasan tentang laporan)
PROMPT;
    }

    /**
     * Parse response AI menjadi sections untuk tampilan accordion.
     */
    private function parseSections(string $text): array
    {
        $sections = [];
        $lines    = explode("\n", $text);
        $current  = null;

        foreach ($lines as $line) {
            if (preg_match('/^#{1,3} (.+)$/', $line, $m)) {
                if ($current !== null) {
                    $sections[] = $current;
                }
                $current = ['title' => trim($m[1]), 'content' => ''];
            } else {
                if ($current !== null) {
                    $current['content'] .= $line . "\n";
                }
            }
        }

        if ($current !== null) {
            $sections[] = $current;
        }

        foreach ($sections as &$sec) {
            $sec['content'] = trim($sec['content']);
        }

        return $sections;
    }

    /**
     * Render teks markdown ke Word document sections.
     */
    private function renderMarkdownToWord($section, string $text, string $judul): void
    {
        // Style definitions
        $titleStyle = [
            'bold'      => true,
            'size'      => 16,
            'name'      => 'Times New Roman',
            'color'     => '1F3864',
        ];
        $heading1Style = [
            'bold'      => true,
            'size'      => 14,
            'name'      => 'Times New Roman',
            'color'     => '1F3864',
        ];
        $heading2Style = [
            'bold'      => true,
            'size'      => 13,
            'name'      => 'Times New Roman',
            'color'     => '2E4C7E',
        ];
        $heading3Style = [
            'bold'      => true,
            'italic'    => true,
            'size'      => 12,
            'name'      => 'Times New Roman',
            'color'     => '2E4C7E',
        ];
        $bodyStyle = [
            'size'      => 12,
            'name'      => 'Times New Roman',
        ];

        $paragraphStyle = ['spaceAfter' => 160, 'lineHeight' => 1.5, 'alignment' => 'both'];

        $lines = explode("\n", $text);

        // Add document title if not in content
        if (!empty($judul) && stripos($text, $judul) === false) {
            $titlePara = $section->addTextRun(['alignment' => 'center', 'spaceAfter' => 240]);
            $titlePara->addText(strtoupper($judul), array_merge($titleStyle, ['size' => 14]));
            $section->addTextBreak(1);
        }

        $i = 0;
        $tableBuffer = [];
        $inTable = false;

        while ($i < count($lines)) {
            $line = $lines[$i];

            // ── Detect table rows ──────────────────────────────────────────
            if (preg_match('/^\|/', trim($line))) {
                $inTable = true;
                $tableBuffer[] = $line;
                $i++;
                continue;
            } elseif ($inTable) {
                // Flush table buffer
                $this->renderTableToWord($section, $tableBuffer);
                $tableBuffer = [];
                $inTable = false;
                // Don't increment, re-process current line
                continue;
            }

            // ── Headings ───────────────────────────────────────────────────
            if (preg_match('/^# (.+)$/', $line, $m)) {
                $para = $section->addTextRun(['alignment' => 'center', 'spaceAfter' => 200]);
                $para->addText(strtoupper($m[1]), $titleStyle);
            } elseif (preg_match('/^## (.+)$/', $line, $m)) {
                $section->addTextBreak(1);
                $para = $section->addTextRun(['spaceAfter' => 120]);
                $para->addText($m[1], $heading1Style);
            } elseif (preg_match('/^### (.+)$/', $line, $m)) {
                $para = $section->addTextRun(['spaceAfter' => 100]);
                $para->addText($m[1], $heading2Style);
            } elseif (preg_match('/^#### (.+)$/', $line, $m)) {
                $para = $section->addTextRun(['spaceAfter' => 80]);
                $para->addText($m[1], $heading3Style);
            }
            // ── Bullet/numbered lists ──────────────────────────────────────
            elseif (preg_match('/^[\-\*] (.+)$/', $line, $m)) {
                $listPara = $section->addTextRun(['indent' => 720, 'spaceAfter' => 80]);
                $listPara->addText('• ', $bodyStyle);
                $this->addInlineMarkdown($listPara, $m[1], $bodyStyle);
            } elseif (preg_match('/^\d+\. (.+)$/', $line, $m)) {
                $num = preg_replace('/^(\d+)\..+$/', '$1', $line);
                $listPara = $section->addTextRun(['indent' => 720, 'spaceAfter' => 80]);
                $listPara->addText($num . '. ', $bodyStyle);
                $this->addInlineMarkdown($listPara, $m[1], $bodyStyle);
            }
            // ── Horizontal rule ────────────────────────────────────────────
            elseif (preg_match('/^---+$/', trim($line))) {
                $section->addTextBreak(1);
            }
            // ── Empty line ─────────────────────────────────────────────────
            elseif (trim($line) === '') {
                // skip — spaceAfter on paragraphs handles spacing
            }
            // ── Regular paragraph ──────────────────────────────────────────
            else {
                $para = $section->addTextRun($paragraphStyle);
                $this->addInlineMarkdown($para, $line, $bodyStyle);
            }

            $i++;
        }

        // Flush any remaining table
        if ($inTable && !empty($tableBuffer)) {
            $this->renderTableToWord($section, $tableBuffer);
        }
    }

    /**
     * Render a markdown table buffer to a Word table.
     */
    private function renderTableToWord($section, array $tableLines): void
    {
        // Filter out separator rows (|---|---|)
        $dataRows = array_filter($tableLines, function ($line) {
            return !preg_match('/^\|[\s\-:]+\|/', trim($line));
        });

        if (empty($dataRows)) return;

        $parsedRows = [];
        foreach ($dataRows as $line) {
            $line = trim($line);
            // Remove leading/trailing pipes
            $line = preg_replace('/^\||\|$/', '', $line);
            $cells = array_map('trim', explode('|', $line));
            if (!empty(array_filter($cells))) {
                $parsedRows[] = $cells;
            }
        }

        if (empty($parsedRows)) return;

        $colCount = max(array_map('count', $parsedRows));

        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => 'AAAAAA',
            'cellMargin'  => 80,
        ];
        $table = $section->addTable($tableStyle);

        foreach ($parsedRows as $rowIdx => $cells) {
            $table->addRow();
            for ($c = 0; $c < $colCount; $c++) {
                $cellText = $cells[$c] ?? '';
                $isHeader = ($rowIdx === 0);

                $cellStyle = [];
                if ($isHeader) {
                    $cellStyle['bgColor'] = '1F3864';
                }

                $cell = $table->addCell(null, $cellStyle);
                $textStyle = [
                    'name'  => 'Times New Roman',
                    'size'  => 11,
                    'bold'  => $isHeader,
                    'color' => $isHeader ? 'FFFFFF' : '000000',
                ];

                // Strip markdown bold from cell text
                $cellText = preg_replace('/\*\*(.+?)\*\*/', '$1', $cellText);
                $cell->addText($cellText, $textStyle, ['spaceAfter' => 40]);
            }
        }

        $section->addTextBreak(1);
    }

    /**
     * Add inline markdown (bold, italic) to a TextRun.
     */
    private function addInlineMarkdown($textRun, string $text, array $baseStyle): void
    {
        // Pattern: **bold**, *italic*, ***bold-italic***
        $pattern = '/(\*\*\*(.+?)\*\*\*|\*\*(.+?)\*\*|\*(.+?)\*)/';

        $lastPos = 0;
        preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $match) {
            $matchText   = $match[0];
            $matchOffset = $match[1];

            // Add plain text before this match
            if ($matchOffset > $lastPos) {
                $plain = substr($text, $lastPos, $matchOffset - $lastPos);
                if ($plain !== '') {
                    $textRun->addText($plain, $baseStyle);
                }
            }

            // Add formatted text
            if (str_starts_with($matchText, '***')) {
                $inner = trim($matchText, '*');
                $textRun->addText($inner, array_merge($baseStyle, ['bold' => true, 'italic' => true]));
            } elseif (str_starts_with($matchText, '**')) {
                $inner = trim($matchText, '*');
                $textRun->addText($inner, array_merge($baseStyle, ['bold' => true]));
            } else {
                $inner = trim($matchText, '*');
                $textRun->addText($inner, array_merge($baseStyle, ['italic' => true]));
            }

            $lastPos = $matchOffset + strlen($matchText);
        }

        // Add remaining plain text
        if ($lastPos < strlen($text)) {
            $remaining = substr($text, $lastPos);
            if ($remaining !== '') {
                $textRun->addText($remaining, $baseStyle);
            }
        }
    }
}
