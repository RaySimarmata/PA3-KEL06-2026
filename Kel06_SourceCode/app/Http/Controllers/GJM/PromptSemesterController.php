<?php

namespace App\Http\Controllers\GJM;

use App\Http\Controllers\Controller;
use App\Services\UnifiedAIService;
use App\Services\TextExtractionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

/**
 * PromptSemesterController
 *
 * Menangani AI Prompt Assistant untuk Laporan Semester.
 * Menggunakan Claude AI untuk membaca file & generate summary/draft laporan.
 */
class PromptSemesterController extends Controller
{
    protected UnifiedAIService $aiService;
    protected TextExtractionService $extractor;

    public function __construct(UnifiedAIService $aiService, TextExtractionService $extractor)
    {
        $this->aiService = $aiService;
        $this->extractor = $extractor;
    }

    /**
     * Endpoint: POST /buat-laporan/semester/read-file
     *
     * Menerima file yang diupload user, ekstrak teks, lalu baca & rangkum
     * menggunakan Claude AI. Hasil summary ditampilkan di Preview Draft Laporan.
     *
     * CATATAN: Endpoint ini dipanggil hanya ketika user mengklik tombol ↑ (Generate),
     * bukan otomatis saat file dipilih.
     */
    public function readFile(Request $request)
    {
        $request->validate([
            'file_referensi' => 'required|file|mimes:docx,doc,pdf,txt,xlsx,xls|max:20480',
        ]);

        try {
            // 1. Simpan file sementara
            $file      = $request->file('file_referensi');
            $fileName  = $file->getClientOriginalName();
            $path      = $file->storeAs('tmp/prompt', uniqid() . '_' . $fileName, 'local');
            $fullPath  = storage_path('app/' . $path);

            Log::info('PromptSemester: file received', ['name' => $fileName, 'path' => $fullPath]);

            // 2. Ekstrak teks dari file
            $extraction = $this->extractor->extractFromFile($fullPath);
            $extracted  = $this->extractor->cleanText($extraction['text'] ?? '');

            // Hapus file temp setelah ekstrak
            Storage::disk('local')->delete($path);

            if (empty($extracted)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengekstrak teks dari file. Pastikan file tidak terenkripsi atau kosong.',
                ]);
            }

            // 3. Ambil instruksi tambahan dari user (opsional)
            $userInstructions = $request->input('prompt', '');
            $periode          = $request->input('periode_semester', '');
            $judul            = $request->input('judul_laporan', '');

            // 4. Buat system prompt kontekstual untuk laporan semester
            $systemPrompt = $this->buildSystemPrompt('semester', $periode, $judul);

            // 5. Panggil AI untuk membaca & merangkum
            $fullPrompt = $systemPrompt . "\n\n" . 
                         "DOKUMEN YANG PERLU DIANALISIS:\n" . $extracted . "\n\n" .
                         "INSTRUKSI PENGGUNA:\n" . $userInstructions;
            
            $aiResult = $this->aiService->generateText($fullPrompt, ['max_tokens' => 4096]);
            
            if (!$aiResult['success'] || empty($aiResult['text'])) {
                $errorMessage = 'Layanan AI sedang tidak tersedia. ';
                
                // Provide more specific error information
                if (isset($aiResult['error'])) {
                    if (str_contains($aiResult['error'], 'rate_limit') || str_contains($aiResult['error'], 'Rate limit')) {
                        $errorMessage = 'Layanan AI sedang mengalami rate limit. Silakan tunggu beberapa menit dan coba lagi.';
                    } elseif (str_contains($aiResult['error'], 'quota')) {
                        $errorMessage = 'Kuota layanan AI telah habis. Silakan coba lagi besok atau hubungi administrator.';
                    } elseif (str_contains($aiResult['error'], 'API key')) {
                        $errorMessage = 'Konfigurasi API key tidak valid. Silakan hubungi administrator.';
                    } else {
                        $errorMessage .= 'Detail: ' . $aiResult['error'];
                    }
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 503);
            }
            
            $aiResponse = $aiResult['text'];

            // 6. Parse sections dari response AI
            $sections = $this->parseSections($aiResponse);

            return response()->json([
                'success'  => true,
                'preview'  => $aiResponse,
                'sections' => $sections,
                'model'    => $aiResult['provider'] . ' (' . $aiResult['model'] . ')',
                'file'     => $fileName,
            ]);
        } catch (\Exception $e) {
            Log::error('PromptSemester readFile error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Endpoint: POST /buat-laporan/semester/prompt
     *
     * Chat/follow-up: user mengirim instruksi baru (dengan atau tanpa file baru).
     * AI merespons berdasarkan konteks sebelumnya.
     */
    public function chat(Request $request)
    {
        $request->validate([
            'prompt'           => 'required|string|max:5000',
            'file_referensi.*' => 'nullable|file|mimes:docx,doc,pdf,txt,xlsx,xls,jpg,jpeg,png,gif,bmp,webp|max:20480',
        ]);

        try {
            $prompt        = $request->input('prompt');
            $previousDraft = $request->input('previous_draft', '');
            $periode       = $request->input('periode_semester', '');
            $judul         = $request->input('judul_laporan', '');
            $allFileContext = '';

            // Process multiple files
            if ($request->hasFile('file_referensi')) {
                $files = $request->file('file_referensi');
                $fileContexts = [];

                foreach ($files as $index => $file) {
                    $fileName = $file->getClientOriginalName();
                    $path     = $file->storeAs('tmp/prompt', uniqid() . '_' . $fileName, 'local');
                    $fullPath = storage_path('app/' . $path);

                    // Check if file is an image
                    $isImage = $this->isImageFile($fileName);
                    
                    if ($isImage) {
                        // Process image with vision AI
                        $imageContext = $this->processImageWithVision($fullPath, $fileName);
                        if (!empty($imageContext)) {
                            $fileContexts[] = "=== ANALISIS GAMBAR: {$fileName} ===\n{$imageContext}\n";
                        }
                    } else {
                        // Process document normally
                        $extraction = $this->extractor->extractFromFile($fullPath);
                        $textContent = $this->extractor->cleanText($extraction['text'] ?? '');
                        if (!empty($textContent)) {
                            $fileContexts[] = "=== DOKUMEN: {$fileName} ===\n{$textContent}\n";
                        }
                    }

                    // Clean up temporary file
                    Storage::disk('local')->delete($path);
                }

                $allFileContext = implode("\n\n", $fileContexts);

                // Ensure allFileContext is string
                if (is_array($allFileContext)) {
                    $allFileContext = json_encode($allFileContext);
                } elseif (!is_string($allFileContext)) {
                    $allFileContext = (string)$allFileContext;
                }

                Log::info('PromptSemester chat: multiple files processed', [
                    'file_count' => count($files),
                    'total_length' => strlen($allFileContext),
                ]);
            }

            // Get GKM reports context for the period
            $gkmContext = $this->getGKMReportsContext($periode);
            
            // Combine file context with GKM context
            $combinedContext = '';
            if (!empty($allFileContext)) {
                $combinedContext .= "=== DOKUMEN REFERENSI YANG DIUPLOAD ===\n{$allFileContext}\n\n";
            }
            if (!empty($gkmContext)) {
                $combinedContext .= "=== DATA LAPORAN GKM BULANAN ===\n{$gkmContext}\n\n";
            }

            $systemPrompt = $this->buildSystemPrompt('semester', $periode, $judul);

            // Build full prompt based on context availability
            $fullPrompt = $systemPrompt . "\n\n";
            
            if (!empty($previousDraft)) {
                $fullPrompt .= "DRAFT SEBELUMNYA:\n{$previousDraft}\n\n";
            }
            
            if (!empty($combinedContext)) {
                $fullPrompt .= $combinedContext;
            }
            
            $fullPrompt .= "INSTRUKSI PENGGUNA:\n{$prompt}";

            $aiResult = $this->aiService->generateText($fullPrompt, ['max_tokens' => 4096]);
            
            if (!$aiResult['success'] || empty($aiResult['text'])) {
                $errorMessage = 'Layanan AI sedang tidak tersedia. ';
                
                // Provide more specific error information
                if (isset($aiResult['error'])) {
                    if (str_contains($aiResult['error'], 'rate_limit') || str_contains($aiResult['error'], 'Rate limit')) {
                        $errorMessage = 'Layanan AI sedang mengalami rate limit. Silakan tunggu beberapa menit dan coba lagi.';
                    } elseif (str_contains($aiResult['error'], 'quota')) {
                        $errorMessage = 'Kuota layanan AI telah habis. Silakan coba lagi besok atau hubungi administrator.';
                    } elseif (str_contains($aiResult['error'], 'API key')) {
                        $errorMessage = 'Konfigurasi API key tidak valid. Silakan hubungi administrator.';
                    } else {
                        $errorMessage .= 'Detail: ' . $aiResult['error'];
                    }
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 503);
            }
            
            $aiResponse = $aiResult['text'];
            $sections = $this->parseSections($aiResponse);

            return response()->json([
                'success'  => true,
                'preview'  => $aiResponse,
                'sections' => $sections,
                'model'    => $aiResult['provider'] . ' (' . $aiResult['model'] . ')',
            ]);
        } catch (\Exception $e) {
            Log::error('PromptSemester chat error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Diagnostic endpoint to check AI service status
     * GET /buat-laporan/semester/diagnostic
     */
    public function diagnostic()
    {
        try {
            $info = $this->aiService->getProviderInfo();
            
            // Test simple generation
            $testResult = $this->aiService->generateText("Test: Balas dengan 'OK'", ['max_tokens' => 50]);
            
            // Get Gemini status
            $geminiService = app(\App\Services\GeminiAIService::class);
            $geminiStats = $geminiService->getUsageStats();
            
            return response()->json([
                'success' => true,
                'primary_provider' => $info,
                'test_generation' => [
                    'success' => $testResult['success'],
                    'provider_used' => $testResult['provider'] ?? 'unknown',
                    'processing_time_ms' => $testResult['processing_time_ms'] ?? 0,
                    'error' => $testResult['error'] ?? null,
                ],
                'gemini_fallback' => $geminiStats,
                'timestamp' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Build system prompt kontekstual untuk laporan semester.
     */
    private function buildSystemPrompt(string $type, string $periode = '', string $judul = ''): string
    {
        $periodeLabel = $periode ? " {$periode}" : '';
        $judulLabel   = $judul  ? " dengan judul \"{$judul}\"" : '';

        // Determine semester label
        $semesterLabel = '';
        if (stripos($periode, 'ganjil') !== false) {
            $semesterLabel = 'Semester Ganjil';
        } elseif (stripos($periode, 'genap') !== false) {
            $semesterLabel = 'Semester Genap';
        } else {
            $semesterLabel = $periode;
        }

        return "Kamu adalah AI Assistant yang ahli dalam membuat laporan penjaminan mutu akademik untuk Gugus Jaminan Mutu (GJM) Fakultas Vokasi Institut Teknologi Del.

KEMAMPUAN KHUSUS KAMU:
- Membaca dan menganalisis dokumen (DOCX, PDF, TXT, Excel)
- Menganalisis gambar dan foto dokumentasi kegiatan dengan detail
- Mengintegrasikan data laporan GKM bulanan sebagai konteks pendukung
- Membuat draft laporan yang komprehensif dan terstruktur

JENIS DOKUMEN YANG DAPAT KAMU PROSES:
- Dokumentasi foto kegiatan (Grand Opening, sosialisasi VMTS, dll)
- Daftar hadir peserta kegiatan dan kuesioner
- Reminder dan komunikasi internal (BAA, Review UTS, RPS, Pedoman)
- Kalender mutu akademik dan jadwal kegiatan
- Laporan GKM bulanan sebagai data pendukung
- Contoh kuesioner VMTS dan hasil survei

STRUKTUR LAPORAN YANG WAJIB DIBUAT (JANGAN SKIP BAGIAN MANAPUN):

# Laporan {$semesterLabel}

## I. PENDAHULUAN

### a. Latar Belakang
[Jelaskan konteks dan alasan pembuatan laporan, kegiatan GJM yang dilakukan]

### b. Dasar
[Jelaskan dasar hukum, peraturan, dan kebijakan yang menjadi landasan]

### c. Tujuan
[Jelaskan tujuan laporan dan kegiatan yang dilakukan]

### d. Ruang Lingkup
[Jelaskan cakupan laporan dan area yang dibahas]

## II. PROGRAM KERJA
[Jelaskan program kerja yang dilaksanakan]

## III. PELAKSANAAN
[Jelaskan pelaksanaan program kerja dan capaiannya]

## IV. HAMBATAN
[Jelaskan hambatan yang dihadapi]

## V. PEMECAHAN MASALAH
[Jelaskan solusi untuk mengatasi hambatan]

## VI. EVALUASI
[Jelaskan evaluasi dan analisis capaian]

## VII. SARAN
[Jelaskan saran dan rekomendasi untuk perbaikan]

PENTING - INSTRUKSI WAJIB:
1. SEMUA 7 bagian utama HARUS ada dalam laporan (I, II, III, IV, V, VI, VII)
2. Bagian I. PENDAHULUAN HARUS memiliki 4 sub-bagian (a, b, c, d)
3. Setiap bagian HARUS memiliki minimal 2-3 paragraf dengan konten substantif
4. Gunakan heading Markdown dengan format yang TEPAT:
   - # untuk judul utama (Laporan Semester Ganjil)
   - ## untuk bagian utama (I. PENDAHULUAN, II. PROGRAM KERJA, dll)
   - ### untuk sub-bagian (a. Latar Belakang, b. Dasar, c. Tujuan, d. Ruang Lingkup)
5. Integrasikan informasi dari SEMUA dokumen dan gambar yang diupload
6. Manfaatkan data laporan GKM bulanan sebagai konteks
7. Gunakan bahasa formal akademik

JIKA USER MEMBERIKAN INSTRUKSI TAMBAHAN:
- Ikuti arahan tersebut dengan seksama
- Tetap pertahankan STRUKTUR WAJIB di atas
- JANGAN skip bagian manapun
- Isi setiap bagian dengan konten yang relevan berdasarkan instruksi user

PENTING - Cara Kerja:
- Dokumen dan gambar SUDAH diekstrak dan kontennya SUDAH diberikan dalam bentuk teks
- JANGAN katakan bahwa kamu tidak bisa membaca file atau tidak memiliki akses ke file
- JANGAN katakan bahwa kamu adalah model bahasa yang tidak bisa mengakses file eksternal
- Langsung proses dan analisis SEMUA konten yang sudah diberikan
- Integrasikan informasi dari berbagai sumber untuk membuat laporan yang komprehensif
- Jika user memberikan instruksi tambahan, ikuti arahan tersebut dengan seksama
- Jika diminta merevisi atau mengubah bagian tertentu, lakukan perubahan sesuai arahan
- Selalu gunakan Bahasa Indonesia formal dan baku

REMINDER: Pastikan output Anda memiliki SEMUA 7 bagian utama dengan sub-bagian yang lengkap. Jangan pernah skip bagian apapun.";
    }

    /**
     * Parse response AI menjadi sections berdasarkan heading markdown.
     * Digunakan untuk tampilan accordion di preview panel.
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
     * Check if file is an image
     */
    private function isImageFile(string $fileName): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        return in_array($extension, $imageExtensions);
    }

    /**
     * Process image with vision AI (Claude Vision or OCR)
     */
    private function processImageWithVision(string $imagePath, string $fileName): string
    {
        try {
            // Convert image to base64
            $imageData = file_get_contents($imagePath);
            $base64Image = base64_encode($imageData);
            $mimeType = mime_content_type($imagePath);

            // UnifiedAIService doesn't support vision, use OCR fallback
            return $this->processImageWithOCR($imagePath, $fileName);

        } catch (\Exception $e) {
            Log::error('Image processing failed', [
                'file' => $fileName,
                'error' => $e->getMessage()
            ]);
            return "Gambar {$fileName} berhasil diupload namun tidak dapat dianalisis secara detail.";
        }
    }

    /**
     * Process image with Claude Vision API
     */
    private function processImageWithClaudeVision(string $base64Image, string $mimeType, string $fileName): string
    {
        try {
            $systemPrompt = "Anda adalah AI yang bertugas menganalisis gambar untuk keperluan laporan akademik. Analisis gambar dengan detail dan berikan informasi yang relevan untuk pembuatan laporan.";
            
            $userMessage = "Analisis gambar ini dan berikan informasi berikut:\n\n";
            $userMessage .= "1. **Deskripsi Gambar**: Jelaskan apa yang terlihat dalam gambar\n";
            $userMessage .= "2. **Jenis Dokumen/Kegiatan**: Identifikasi jenis dokumen atau kegiatan yang terdokumentasi\n";
            $userMessage .= "3. **Informasi Penting**: Ekstrak teks, data, atau informasi penting yang terlihat\n";
            $userMessage .= "4. **Relevansi untuk Laporan**: Jelaskan bagaimana informasi ini dapat digunakan dalam laporan akademik\n\n";
            $userMessage .= "Berikan analisis dalam bahasa Indonesia yang formal dan profesional.";

            // Call Claude Vision API
            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-3-5-sonnet-20241022',
                'max_tokens' => 2000,
                'system' => $systemPrompt,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $userMessage
                            ],
                            [
                                'type' => 'image',
                                'source' => [
                                    'type' => 'base64',
                                    'media_type' => $mimeType,
                                    'data' => $base64Image
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $analysis = $data['content'][0]['text'] ?? '';
                
                // Ensure analysis is string
                if (is_array($analysis)) {
                    $analysis = json_encode($analysis);
                } elseif (!is_string($analysis)) {
                    $analysis = (string)$analysis;
                }
                
                if (!empty($analysis)) {
                    Log::info('Claude Vision analysis successful', [
                        'file' => $fileName,
                        'analysis_length' => strlen($analysis)
                    ]);
                    return $analysis;
                }
            }

            Log::warning('Claude Vision API failed', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500)
            ]);

            // Fallback to OCR
            return $this->processImageWithOCR($imagePath, $fileName);

        } catch (\Exception $e) {
            Log::error('Claude Vision processing failed', [
                'error' => $e->getMessage(),
                'file' => $fileName
            ]);
            return $this->processImageWithOCR($imagePath, $fileName);
        }
    }

    /**
     * Fallback OCR processing
     */
    private function processImageWithOCR(string $imagePath, string $fileName): string
    {
        // Basic image analysis without OCR
        $imageInfo = getimagesize($imagePath);
        $fileSize = filesize($imagePath);
        
        $analysis = "**ANALISIS GAMBAR: {$fileName}**\n\n";
        $analysis .= "**Informasi File:**\n";
        $analysis .= "- Nama file: {$fileName}\n";
        $analysis .= "- Ukuran: " . round($fileSize / 1024, 2) . " KB\n";
        
        if ($imageInfo) {
            $analysis .= "- Dimensi: {$imageInfo[0]} x {$imageInfo[1]} pixels\n";
            $analysis .= "- Format: " . image_type_to_extension($imageInfo[2]) . "\n";
        }
        
        $analysis .= "\n**Catatan:**\n";
        $analysis .= "Gambar berhasil diupload dan dapat digunakan sebagai dokumentasi pendukung laporan. ";
        $analysis .= "Untuk analisis teks dalam gambar yang lebih detail, diperlukan konfigurasi OCR atau Claude Vision API.\n\n";
        $analysis .= "**Saran Penggunaan:**\n";
        $analysis .= "- Gunakan sebagai dokumentasi visual kegiatan\n";
        $analysis .= "- Lampirkan dalam laporan sebagai bukti pelaksanaan\n";
        $analysis .= "- Referensikan dalam narasi laporan sesuai konteks kegiatan";

        return $analysis;
    }

    /**
     * Get GKM reports context for the period
     */
    private function getGKMReportsContext(string $periode): string
    {
        try {
            // Determine date range based on semester period
            $currentYear = date('Y');
            $startDate = null;
            $endDate = null;

            if ($periode === 'ganjil') {
                // Semester Ganjil: August - January
                $startDate = Carbon::create($currentYear, 8, 1);
                $endDate = Carbon::create($currentYear + 1, 1, 31);
            } elseif ($periode === 'genap') {
                // Semester Genap: February - July
                $startDate = Carbon::create($currentYear, 2, 1);
                $endDate = Carbon::create($currentYear, 7, 31);
            }

            if (!$startDate || !$endDate) {
                return '';
            }

            // Get GKM monthly reports in the period
            $laporanGKM = \App\Models\LaporanBulanan::where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->with(['prodi'])
                ->orderBy('created_at', 'desc')
                ->limit(10) // Limit to recent reports
                ->get();

            if ($laporanGKM->isEmpty()) {
                return '';
            }

            $context = "**DATA LAPORAN GKM BULANAN PERIODE " . strtoupper($periode) . "**\n\n";
            $context .= "Periode: " . $startDate->format('d F Y') . " - " . $endDate->format('d F Y') . "\n";
            $context .= "Total laporan: " . $laporanGKM->count() . "\n\n";

            // Group by prodi
            $laporanPerProdi = $laporanGKM->groupBy('prodi_id');
            
            foreach ($laporanPerProdi as $prodiId => $laporan) {
                $prodi = $laporan->first()->prodi;
                $context .= "**" . ($prodi->nama_prodi ?? 'Unknown Prodi') . "**\n";
                $context .= "- Jumlah laporan: " . $laporan->count() . "\n";
                
                // Add summary of recent reports
                foreach ($laporan->take(3) as $report) {
                    $context .= "- " . $report->created_at->format('M Y') . ": ";
                    if (!empty($report->ringkasan_eksekutif)) {
                        $context .= substr($report->ringkasan_eksekutif, 0, 100) . "...\n";
                    } else {
                        $context .= "Laporan bulanan tersedia\n";
                    }
                }
                $context .= "\n";
            }

            // Add monitoring data summary
            $context .= "**RINGKASAN DATA MONITORING:**\n";
            $context .= "- Monitoring perkuliahan, RPS, dan kuesioner mahasiswa\n";
            $context .= "- Data kehadiran dosen dan mahasiswa\n";
            $context .= "- Evaluasi kualitas pembelajaran\n";
            $context .= "- Feedback dan saran perbaikan\n\n";

            Log::info('GKM context generated', [
                'periode' => $periode,
                'report_count' => $laporanGKM->count(),
                'context_length' => strlen($context)
            ]);

            return $context;

        } catch (\Exception $e) {
            Log::error('Failed to get GKM context', [
                'periode' => $periode,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    /**
     * Generate user-friendly error message for AI configuration issues.
     *
     * @return string
     */
    private function getAIConfigErrorMessage(): string
    {
        return '⚠ AI tidak memberikan respons. Periksa konfigurasi API key di file .env:<br><br>' .
               '<strong>1. ANTHROPIC_API_KEY</strong> (Primary - Claude AI)<br>' .
               '   Dapatkan dari: <a href="https://console.anthropic.com/" target="_blank">https://console.anthropic.com/</a><br><br>' .
               '<strong>2. LLM_API_KEY</strong> (Fallback - Groq)<br>' .
               '   Dapatkan dari: <a href="https://console.groq.com/" target="_blank">https://console.groq.com/</a><br><br>' .
               '<strong>3. OPENROUTER_API_KEY</strong> (Alternative)<br>' .
               '   Dapatkan dari: <a href="https://openrouter.ai/" target="_blank">https://openrouter.ai/</a><br><br>' .
               'Pastikan minimal satu API key sudah dikonfigurasi dengan benar.';
    }
}
