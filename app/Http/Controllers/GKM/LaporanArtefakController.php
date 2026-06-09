<?php

namespace App\Http\Controllers\GKM;

use App\Http\Controllers\Controller;
use App\Models\LaporanGKM;
use App\Models\TemplateLaporan;
use App\Models\Prodi;
use App\Services\LaporanArtefakService;
use App\Jobs\GenerateLaporanArtefakJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LaporanArtefakController extends Controller
{
    protected $laporanService;

    public function __construct(LaporanArtefakService $laporanService)
    {
        $this->laporanService = $laporanService;
    }

    /**
     * Display list of laporan artefak
     */
    public function index(Request $request)
    {
        $query = LaporanGKM::with(['user', 'template'])
            ->where('jenis_laporan', 'artefak')
            ->orderBy('created_at', 'desc');

        if ($request->filled('periode')) {
            $query->where('periode', $request->periode);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $laporanList = $query->paginate(10);

        $periodes = LaporanGKM::select('periode', 'bulan', 'tahun')
            ->where('jenis_laporan', 'artefak')
            ->distinct()
            ->orderBy('periode', 'desc')
            ->get();

        return view('gkm.laporan-artefak.index', compact('laporanList', 'periodes'));
    }

    /**
     * Show form to create new laporan artefak
     */
    public function create()
    {
        $user = Auth::user();

        $template = TemplateLaporan::where('jenis_template', 'laporan_artefak')
            ->where('is_active', true)
            ->first();

        $templates = TemplateLaporan::where('jenis_template', 'laporan_artefak')
            ->where('is_active', true)
            ->get();

        $periodes = [];
        for ($i = 0; $i < 6; $i++) {
            $date = Carbon::now()->subMonths($i);
            $periodes[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->locale('id')->translatedFormat('F Y')
            ];
        }

        return view('gkm.laporan-artefak.create', compact('template', 'templates', 'periodes'));
    }

    /**
     * Create draft laporan artefak
     */
    public function createDraft(Request $request)
    {
        try {
            $request->validate([
                'judul_laporan' => 'required|string|max:255',
                'periode' => 'required|string|regex:/^\d{4}-\d{2}$/',
                'template_id' => 'nullable|exists:template_laporan,id',
            ]);

            $user = Auth::user();
            $periode = $request->periode;
            
            $periodeObj = Carbon::createFromFormat('Y-m', $periode);
            $bulan = $periodeObj->locale('id')->translatedFormat('F');
            $tahun = $periodeObj->year;

            $existing = LaporanGKM::where('periode', $periode)
                ->where('user_id', $user->id)
                ->where('jenis_laporan', 'artefak')
                ->where('status', 'pending')
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => true,
                    'message' => 'Draft laporan sudah ada',
                    'data' => [
                        'id' => $existing->id,
                        'judul' => $existing->judul_laporan,
                        'periode' => $periode,
                    ],
                ]);
            }

            $laporan = LaporanGKM::create([
                'periode' => $periode,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'user_id' => $user->id,
                'template_id' => $request->template_id,
                'jenis_laporan' => 'artefak',
                'judul_laporan' => $request->judul_laporan,
                'status' => 'pending',
            ]);

            Log::info('Draft Laporan Artefak created', [
                'laporan_id' => $laporan->id,
                'user_id' => $user->id,
                'periode' => $periode,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Draft laporan artefak berhasil dibuat',
                'data' => [
                    'id' => $laporan->id,
                    'judul' => $laporan->judul_laporan,
                    'periode' => $periode,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', $e->validator->errors()->all()),
                'errors' => $e->validator->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to create draft laporan artefak', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat draft: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * AI Prompt Assistant - Process user prompt + file for Laporan Artefak
     * Supports: 
     * - Auto-generate laporan lengkap
     * - Iteration/Revision on existing draft
     * - Contextual responses
     */
    public function aiPrompt(Request $request)
    {
        try {
            $request->validate([
                'prompt' => 'required|string',
                'file_referensi' => 'nullable|array',
                'file_referensi.*' => 'file|mimes:docx,doc,pdf,txt,xlsx,xls,jpg,jpeg,png,gif,webp|max:10240',
                'conversation_history' => 'nullable|array',
                'template_id' => 'nullable|exists:template_laporan,id',
                'periode' => 'nullable|string',
                'laporan_id' => 'nullable|exists:laporan_gkm,id',
            ]);

            $userPrompt = $request->input('prompt');
            $promptLower = strtolower($userPrompt);
            $laporanId = $request->input('laporan_id');

            // ================================================================
            // VALIDASI KONTEKS LAPORAN BULANAN (ARTEFAK) - BACKEND (SIMPLIFIED)
            // ================================================================

            // Keywords yang mengindikasikan KONTEKS VALID tentang laporan (prioritas tinggi)
            $laporanContextKeywords = [
                'laporan', 'report', 'artefak', 'artifact', 'rps', 'materi',
                'monitoring', 'sidak', 'pemeriksaan', 'hasil generate',
                'hasil pemeriksaan', 'hasil monitoring', 'hasil sidak',
                'gkm', 'gjm', 'gugus kendali mutu', 'd4 trpl', 'prodi',
                'bab', 'tabel', 'gambar', 'daftar isi', 'pendahuluan',
                'kesimpulan', 'rekomendasi', 'evaluasi', 'analisis',
                'semester', 'tahun ajaran', 'periode', 'perkuliahan',
                'dosen', 'matakuliah', 'mata kuliah', 'upload', 'week',
                'tim gkm', 'ketua gkm', 'laporan bulanan',
                'buat', 'buatkan', 'bikin', 'generate', 'create',
                'draft', 'format', 'struktur', 'template'
            ];

            // Keyword yang SANGAT JELAS DI LUAR KONTEKS (hanya tolak jika benar-benar tidak relevan)
            $clearlyOffTopicKeywords = [
                'siapa kamu', 'namamu', 'nama kamu', 'kamu siapa',
                'siapa saya', 'namaku', 'nama saya', 'aku siapa',
                'kenalan yuk', 'mari berkenalan', 'mau kenalan',
                'resep masakan', 'cara memasak', 'tutorial memasak',
                'film terbaru', 'game online', 'musik populer',
                'olahraga favorit', 'gym workout', 'fitness tips',
                'hewan peliharaan', 'merawat kucing', 'merawat anjing',
                'ramalan bintang', 'zodiac hari ini', 'horoskop',
                'berita politik', 'berita olahraga', 'berita entertainment',
                'cerita lucu', 'joke terbaru', 'pantun lucu',
            ];

            // Pola pertanyaan singkat yang PASTI tidak relevan (sangat spesifik)
            $clearlyOffTopicPatterns = [
                '/^(halo|hai|hey|hello|hi|yo|haii|hallo|helo)(\s+saja)?$/i',
                '/^(apa kabar|gimana kabar|gmn kabar)(\?)?$/i',
                '/^(siapa (kamu|anda|saya|aku))(\?)?$/i',
                '/^(nama (kamu|anda|saya|aku))(\?)?$/i',
                '/^(kenalan|perkenalkan|kenal)(\s+(yuk|dong))?$/i',
                '/^(cerita|dongeng|joke|lelucon|pantun|puisi)(\s+(dong|yuk))?$/i',
                '/^(resep|masak|makanan|minuman)(\s+(apa|dong))?$/i',
                '/^(cuaca|ramalan|zodiac|horoskop)(\s+(hari ini))?$/i',
            ];

            // Keywords yang mengindikasikan permintaan revisi/perbaikan
            $revisionKeywords = [
                'perbaiki', 'ubah', 'edit', 'revisi', 'revisikan', 'ganti', 
                'tambah', 'hapus', 'update', 'perbaharui', 'koreksi', 'betulkan',
                'memperbaiki', 'mengubah', 'menambah', 'menghapus', 'mengupdate',
                'tolong perbaiki', 'tolong ubah', 'tolong revisi', 'revisi bagian',
                'ubah bagian', 'perbaiki bagian', 'tambah di', 'hapus bagian'
            ];

            $hasFiles = $request->hasFile('file_referensi') && count($request->file('file_referensi')) > 0;
            $hasOCRImages = $request->hasFile('ocr_images') && count($request->file('ocr_images')) > 0;
            $hasAnyFiles = $hasFiles || $hasOCRImages;
            
            // Cek apakah user sudah punya draft laporan (lebih permisif jika sudah ada draft)
            $hasDraftLaporan = $request->filled('laporan_id') || $request->filled('ai_preview_data');

            // Cek apakah ini permintaan revisi
            $isRevision = false;
            foreach ($revisionKeywords as $keyword) {
                if (strpos($promptLower, $keyword) !== false) {
                    $isRevision = true;
                    break;
                }
            }

            // CEK PRIORITAS: Apakah ada konteks laporan yang valid?
            $hasLaporanContext = false;
            foreach ($laporanContextKeywords as $keyword) {
                if (strpos($promptLower, $keyword) !== false) {
                    $hasLaporanContext = true;
                    break;
                }
            }

            // JIKA ADA KONTEKS LAPORAN YANG VALID, LANGSUNG IZINKAN (bypass validasi off-topic)
            if (!$hasLaporanContext) {
                // Cek apakah ada keyword yang JELAS di luar topik
                $hasClearlyOffTopicKeyword = false;
                foreach ($clearlyOffTopicKeywords as $keyword) {
                    if (strpos($promptLower, $keyword) !== false) {
                        $hasClearlyOffTopicKeyword = true;
                        break;
                    }
                }

                // Cek pola pertanyaan yang PASTI tidak relevan
                $matchesClearlyOffTopicPattern = false;
                foreach ($clearlyOffTopicPatterns as $pattern) {
                    if (preg_match($pattern, trim($userPrompt))) {
                        $matchesClearlyOffTopicPattern = true;
                        break;
                    }
                }

                // HANYA TOLAK jika memenuhi kriteria yang SANGAT JELAS di luar topik:
                // 1. Ada keyword/pattern yang jelas off-topic
                // 2. DAN tidak sedang melakukan revisi
                // 3. DAN belum ada draft laporan
                if (($hasClearlyOffTopicKeyword || $matchesClearlyOffTopicPattern) && !$isRevision && !$hasDraftLaporan) {
                    Log::info('AI Prompt Artefak rejected: Clearly off-topic', [
                        'prompt' => $userPrompt,
                        'user_id' => Auth::id(),
                        'ip' => $request->ip()
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'response' => "Maaf, pertanyaan Anda di luar konteks pembuatan **Laporan Bulanan (RPS dan Materi)**.\n\n" .
                                      "Saya dapat membantu dengan:\n" .
                                      "✅ Pembuatan laporan artefak RPS dan Materi\n" .
                                      "✅ Analisis data monitoring RPS dan Materi\n" .
                                      "✅ Struktur dan format laporan artefak\n" .
                                      "✅ Perbaikan dan revisi draft laporan\n" .
                                      "✅ Pertanyaan terkait RPS dan Materi perkuliahan\n\n" .
                                      "Silakan ajukan pertanyaan yang terkait dengan **Laporan Bulanan (Artefak)**.",
                        'model_info' => 'Context Validation (Rejected)',
                        'cached' => false,
                        'rejected' => true
                    ]);
                }
            } else {
                Log::info('AI Prompt Artefak: Valid laporan context detected, bypassing off-topic validation', [
                    'prompt' => $userPrompt,
                    'user_id' => Auth::id()
                ]);
            }

            // Validasi pesan terlalu pendek HANYA untuk kasus yang sangat ekstrem (< 3 karakter)
            if (strlen(trim($userPrompt)) < 3 && !$hasAnyFiles) {
                Log::info('AI Prompt Artefak rejected: Too short', [
                    'prompt' => $userPrompt,
                    'length' => strlen($userPrompt),
                    'user_id' => Auth::id()
                ]);
                
                return response()->json([
                    'success' => true,
                    'response' => "Maaf, instruksi Anda terlalu singkat.\n\n" .
                                  "Silakan berikan instruksi yang lebih jelas.",
                    'model_info' => 'Context Validation (Too Short)',
                    'cached' => false,
                    'rejected' => true
                ]);
            }

            // ================================================================
            // END OF VALIDASI KONTEKS
            // ================================================================

            $aiService = app(\App\Services\UnifiedAIService::class);
            $textExtraction = app(\App\Services\TextExtractionService::class);
            $ocrService = app(\App\Services\OCRService::class);

            $conversationHistory = $request->input('conversation_history', []);
            $templateId = $request->input('template_id');
            $periode = $request->input('periode');

            // ================================================================
            // AMBIL DRAFT LAPORAN YANG SUDAH ADA (UNTUK ITERASI/PERBAIKAN)
            // ================================================================
            $existingDraft = '';
            $existingAISections = [];
            $isRevisionRequest = $isRevision; // Already checked in validation above

            if ($laporanId) {
                $existingLaporan = LaporanGKM::find($laporanId);
                if ($existingLaporan) {
                    if ($existingLaporan->ai_preview_draft) {
                        $existingDraft = $existingLaporan->ai_preview_draft;
                        Log::info('Found existing draft for potential modification', [
                            'laporan_id' => $laporanId,
                            'draft_length' => strlen($existingDraft),
                            'is_revision_request' => $isRevisionRequest
                        ]);
                    }
                    if ($existingLaporan->ai_sections) {
                        $existingAISections = $existingLaporan->ai_sections;
                    }
                }
            }

            // Build context for caching
            $cacheContext = [
                'feature' => 'artefak',
                'type' => 'laporan_artefak',
                'template_id' => $templateId,
                'periode' => $periode,
                'has_files' => $request->hasFile('file_referensi'),
                'file_count' => $request->hasFile('file_referensi') ? count($request->file('file_referensi')) : 0,
                'has_existing_draft' => !empty($existingDraft),
                'is_revision_request' => $isRevisionRequest,
            ];

            // Check cache first (skip cache jika revision request)
            $cacheService = app(\App\Services\AICacheService::class);
            $cachedResponse = null;
            
            if (!$isRevisionRequest) {
                $cachedResponse = $cacheService->getCachedResponse($userPrompt, $cacheContext);
            }
            
            if ($cachedResponse) {
                Log::info('AI Prompt Artefak: Using cached response', [
                    'cache_id' => $cachedResponse['cache_id'],
                    'usage_count' => $cachedResponse['usage_count'],
                    'similarity' => $cachedResponse['similarity'] ?? 1.0,
                    'prompt_length' => strlen($userPrompt)
                ]);

                return response()->json([
                    'success' => true,
                    'response' => $cachedResponse['text'],
                    'model_info' => $cachedResponse['provider'] . ' (' . $cachedResponse['model'] . ') [CACHED]',
                    'cached' => true,
                    'cache_info' => [
                        'usage_count' => $cachedResponse['usage_count'],
                        'similarity' => $cachedResponse['similarity'] ?? 1.0,
                    ]
                ]);
            }

            // Ambil data RPS dan Materi dari database jika tidak ada file upload
            $databaseContext = '';
            if (!$request->hasFile('file_referensi') || $request->file('file_referensi') === null) {
                $databaseContext = $this->getArtefakDataFromDatabase($periode);
                Log::info('Using database context for Artefak AI', [
                    'periode' => $periode,
                    'context_length' => strlen($databaseContext)
                ]);
            }

            // =========================================================================
            // DETEKSI APAKAH INI PERMINTAAN REVISI ATAU AUTO-GENERATE
            // =========================================================================
            
            // Trigger keywords untuk auto-generate (buat laporan baru dari awal)
            $autoGenerateKeywords = [
                'buat laporan',
                'buatkan laporan',
                'generate laporan',
                'bikin laporan',
                'buat laporan artefak',
                'buatkan laporan artefak',
                'generate laporan artefak',
                'laporan rps dan materi',
                'laporan rps',
                'laporan materi',
                'laporan bulanan',
                'buat laporan bulanan',
                'buatkan laporan bulanan',
                'generate report',
            ];

            $userPromptLower = strtolower($userPrompt);
            $shouldAutoGenerate = false;

            // Cek apakah ini permintaan auto-generate (bukan revisi)
            foreach ($autoGenerateKeywords as $keyword) {
                if (str_contains($userPromptLower, $keyword)) {
                    // Jika ada draft yang sudah ada DAN ini bukan permintaan revisi, tetap auto-generate
                    // (user meminta buat ulang)
                    $shouldAutoGenerate = true;
                    break;
                }
            }

            // Jika ada draft yang sudah ada dan ini permintaan revisi, JANGAN auto-generate
            if (!empty($existingDraft) && $isRevisionRequest) {
                $shouldAutoGenerate = false;
                Log::info('Revision request detected, skipping auto-generate', [
                    'prompt' => $userPrompt,
                    'has_existing_draft' => true
                ]);
            }

            if ($shouldAutoGenerate) {
                try {
                    Log::info('Auto-generate laporan artefak triggered', [
                        'prompt' => $userPrompt,
                        'periode' => $periode,
                        'template_id' => $templateId,
                    ]);

                    if (empty($periode)) {
                        $periode = $request->input('periode', date('Y-m'));
                        Log::info('No periode provided in auto-generate, using fallback', ['periode' => $periode]);
                    }

                    $laporan = LaporanGKM::where('periode', $periode)
                        ->where('jenis_laporan', 'artefak')
                        ->where('user_id', Auth::id())
                        ->where('status', '!=', 'completed')
                        ->latest()
                        ->first();

                    if (!$laporan) {
                        $periodeObj = Carbon::createFromFormat('Y-m', $periode);
                        
                        $laporan = LaporanGKM::create([
                            'user_id' => Auth::id(),
                            'periode' => $periode,
                            'bulan' => $periodeObj->month,
                            'tahun' => $periodeObj->year,
                            'jenis_laporan' => 'artefak',
                            'judul_laporan' => 'Laporan Monitoring Artefak Perkuliahan ' . $periodeObj->format('F Y'),
                            'template_id' => $templateId,
                            'status' => 'pending',
                        ]);

                        Log::info('Created new laporan artefak for auto-generation', [
                            'laporan_id' => $laporan->id,
                        ]);
                    }

                    $generatedLaporan = $this->laporanService->generateLaporan($laporan->id);
                    $downloadUrl = route('gkm.laporan-artefak.download', ['id' => $generatedLaporan->id]);
                    
                    $aiResponse = "✅ **LAPORAN ARTEFAK BERHASIL DIBUAT!**\n\n";
                    $aiResponse .= "Saya telah membuat laporan monitoring artefak perkuliahan lengkap untuk periode **{$periode}** menggunakan data dari sistem monitoring.\n\n";
                    
                    $aiResponse .= "## 📊 Ringkasan Data\n\n";
                    $aiResponse .= "- **Total Matakuliah**: {$generatedLaporan->total_rps}\n";
                    $aiResponse .= "- **RPS Sudah Upload**: " . ($generatedLaporan->total_rps > 0 ? round(($generatedLaporan->total_rps / $generatedLaporan->total_rps) * 100, 1) : 0) . "%\n";
                    $aiResponse .= "- **Materi Sudah Upload**: " . ($generatedLaporan->total_materi > 0 ? round(($generatedLaporan->total_materi / $generatedLaporan->total_materi) * 100, 1) : 0) . "%\n\n";
                    
                    $aiResponse .= "## 📄 File Laporan\n\n";
                    $aiResponse .= "Laporan Word (.docx) telah dibuat dengan lengkap meliputi:\n";
                    $aiResponse .= "- ✅ Tabel RPS dan status upload\n";
                    $aiResponse .= "- ✅ Tabel Materi (Week 1-16)\n";
                    $aiResponse .= "- ✅ Hasil pemeriksaan dan analisis ketercapaian\n";
                    $aiResponse .= "- ✅ Tabel hambatan dan saran pemecahan masalah\n";
                    $aiResponse .= "- ✅ Tindak lanjut dan kesimpulan\n\n";
                    
                    $aiResponse .= "📥 **[Download Laporan Word]({$downloadUrl})**\n\n";
                    $aiResponse .= "Anda dapat mendownload file Word dan langsung menggunakannya atau melakukan penyesuaian sesuai kebutuhan.\n\n";
                    $aiResponse .= "Jika Anda membutuhkan perubahan atau penyesuaian pada laporan, silakan beritahu saya! Contoh:\n";
                    $aiResponse .= "- \"Perbaiki bagian kesimpulan\"\n";
                    $aiResponse .= "- \"Tambah rekomendasi tentang monitoring berkala\"\n";
                    $aiResponse .= "- \"Ubah data pada tabel RPS\"\n";

                    $cacheService->cacheResponse(
                        $userPrompt,
                        $cacheContext,
                        $aiResponse,
                        'LaporanArtefakService',
                        'auto-generate',
                        null
                    );

                    try {
                        $evaluationService = app(\App\Services\AIEvaluationService::class);
                        $evaluationService->createAIResponseTest([
                            'test_name' => 'Laporan Artefak Auto - ' . date('Y-m-d H:i:s'),
                            'feature' => 'artefak',
                            'query' => $userPrompt,
                            'expected_response' => null,
                            'actual_response' => $aiResponse,
                        ]);
                        
                        Log::info('AI Evaluation test created', [
                            'feature' => 'artefak_auto',
                            'prompt_length' => strlen($userPrompt),
                            'response_length' => strlen($aiResponse),
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to create AI evaluation test', [
                            'error' => $e->getMessage(),
                            'feature' => 'artefak_auto'
                        ]);
                    }

                    Log::info('Auto-generated laporan artefak successfully', [
                        'laporan_id' => $generatedLaporan->id,
                        'file_word' => $generatedLaporan->file_word,
                    ]);

                    return response()->json([
                        'success' => true,
                        'response' => $aiResponse,
                        'model_info' => 'LaporanArtefakService (Auto-Generate)',
                        'cached' => false,
                        'auto_generated' => true,
                        'laporan_id' => $generatedLaporan->id,
                        'download_url' => $downloadUrl,
                    ]);

                } catch (\Exception $e) {
                    Log::error('Auto-generate laporan artefak failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $shouldAutoGenerate = false;
                    Log::info('Falling back to regular AI chat after auto-generate failure');
                }
            }

            // =========================================================================
            // REGULAR AI CHAT (Termasuk untuk REVISI/PERBAIKAN LAPORAN)
            // =========================================================================

            $templateStructure = $this->extractTemplateStructure($templateId);

            // System context untuk laporan artefak (dengan instruksi revisi)
            $systemContext = "Anda adalah AI Assistant untuk Gugus Kendali Mutu (GKM) Institut Teknologi Del.\n\n";
            $systemContext .= "Tugas Anda: Membantu membuat LAPORAN ARTEFAK BULANAN berdasarkan data monitoring RPS dan Materi yang diupload dan instruksi user.\n\n";
            
            $systemContext .= "=== BATASAN KONTEKS YANG SANGAT KETAT ===\n";
            $systemContext .= "ANDA HANYA BOLEH MEMBANTU DENGAN:\n";
            $systemContext .= "1. Pembuatan laporan artefak RPS dan Materi\n";
            $systemContext .= "2. Analisis data monitoring RPS dan Materi\n";
            $systemContext .= "3. Format dan struktur laporan artefak\n";
            $systemContext .= "4. Perbaikan dan revisi draft laporan artefak\n";
            $systemContext .= "5. Pertanyaan terkait RPS, Materi perkuliahan, dan artefak akademik\n\n";
            
            $systemContext .= "⚠️⚠️⚠️ ANDA TIDAK BOLEH DAN HARUS MENOLAK: ⚠️⚠️⚠️\n";
            $systemContext .= "- Pertanyaan tentang SIAPA (identitas, nama orang, tokoh, dll)\n";
            $systemContext .= "- Pertanyaan tentang PENAMPILAN (ganteng, cantik, tampan, cakep)\n";
            $systemContext .= "- Pertanyaan tentang HEWAN atau BINATANG\n";
            $systemContext .= "- Pertanyaan tentang OLAHRAGA, FITNESS, GYM, DUMBBELL\n";
            $systemContext .= "- Menjawab pertanyaan umum di luar konteks laporan artefak\n";
            $systemContext .= "- Membantu dengan topik selain RPS dan Materi\n";
            $systemContext .= "- Memberikan informasi atau saran di luar monitoring perkuliahan\n";
            $systemContext .= "- Membahas topik pribadi, hiburan, atau hal-hal di luar akademik\n";
            $systemContext .= "- Small talk, chitchat, atau obrolan santai\n";
            $systemContext .= "- Pertanyaan 'apa kabar', 'hello', 'kenalan', dll\n\n";
            
            $systemContext .= "🚨 WAJIB: JIKA USER BERTANYA DI LUAR KONTEKS 🚨\n";
            $systemContext .= "Anda HARUS LANGSUNG menolak dengan respons PERSIS ini:\n\n";
            $systemContext .= "\"Maaf, permintaan Anda di luar konteks pembuatan Laporan Bulanan. Saya hanya dapat membantu dengan pembuatan laporan monitoring RPS dan Materi perkuliahan. Silakan ajukan pertanyaan terkait laporan bulanan.\"\n\n";
            $systemContext .= "JANGAN TAMBAHKAN penjelasan lain. JANGAN JAWAB pertanyaan user. LANGSUNG TOLAK!\n\n";
            
            $systemContext .= "=== INSTRUKSI KHUSUS UNTUK PERBAIKAN/REVISI LAPORAN ===\n";
            $systemContext .= "Jika user meminta PERBAIKAN, UBAHAN, atau REVISI pada draft laporan yang sudah ada:\n";
            $systemContext .= "1. Lihat draft laporan yang sudah ada (akan diberikan di bawah)\n";
            $systemContext .= "2. Identifikasi bagian yang diminta untuk diubah (contoh: 'perbaiki BAB 1', 'ubah tabel RPS', 'tambah rekomendasi')\n";
            $systemContext .= "3. HANYA ubah bagian yang diminta, PERTAHANKAN semua bagian lain\n";
            $systemContext .= "4. JANGAN buat ulang seluruh laporan dari awal\n";
            $systemContext .= "5. Jika user minta 'tambah di bawah ini', tambahkan konten baru di akhir bagian yang relevan\n";
            $systemContext .= "6. Jika user minta 'hapus bagian X', hapus bagian tersebut\n";
            $systemContext .= "7. Setelah selesai, tampilkan SELURUH draft laporan yang sudah diperbaiki\n\n";
            
            $systemContext .= "=== FORMAT RESPONS UNTUK PERMINTAAN REVISI ===\n";
            $systemContext .= "Jika Anda melakukan revisi/perbaikan, awali respons dengan:\n";
            $systemContext .= "\"✅ **Laporan telah diperbaiki sesuai permintaan!**\n\n\"\n";
            $systemContext .= "Kemudian tampilkan seluruh draft laporan yang sudah diperbaiki.\n\n";
            
            $systemContext .= "PENTING - CONVERSATION CONTEXT:\n";
            $systemContext .= "- Ini mungkin percakapan lanjutan. Jika user meminta perubahan atau perbaikan, modifikasi konten yang sudah ada.\n";
            $systemContext .= "- Jika user mengatakan 'ubah bagian X', 'perbaiki Y', atau 'tambahkan Z', lakukan perubahan pada draft sebelumnya.\n";
            $systemContext .= "- Pertahankan konsistensi dengan respons sebelumnya kecuali diminta mengubahnya.\n";
            $systemContext .= "- Jika ini permintaan pertama, buat draft lengkap. Jika permintaan lanjutan, fokus pada perubahan yang diminta.\n";
            $systemContext .= "- SELALU PERIKSA: Apakah pertanyaan user masih dalam konteks laporan artefak? Jika tidak, tolak dengan sopan.\n\n";

            if ($templateStructure) {
                $systemContext .= "STRUKTUR TEMPLATE YANG HARUS DIIKUTI:\n";
                $systemContext .= $templateStructure . "\n\n";
                $systemContext .= "PENTING: Anda HARUS mengikuti struktur template di atas dengan KETAT. Gunakan markdown heading level 1 (#) untuk setiap bagian utama sesuai template.\n";
                $systemContext .= "Jangan menambah atau mengurangi bagian dari template. Isi setiap bagian dengan konten yang relevan berdasarkan dokumen yang diupload.\n\n";
            } else {
                $systemContext .= "PENTING: Gunakan STRUKTUR WAJIB berikut dengan markdown heading level 1 (#):\n\n";
                $systemContext .= "# RINGKASAN EKSEKUTIF\n";
                $systemContext .= "[Ringkasan singkat laporan dan temuan utama]\n\n";
                $systemContext .= "# PENDAHULUAN\n";
                $systemContext .= "[Latar belakang dan tujuan laporan artefak]\n\n";
                $systemContext .= "# METODOLOGI\n";
                $systemContext .= "[Metode pengumpulan dan analisis data artefak]\n\n";
                $systemContext .= "# TEMUAN UTAMA\n";
                $systemContext .= "[Hasil analisis artefak dan dokumen]\n\n";
                $systemContext .= "# ANALISIS KUALITAS\n";
                $systemContext .= "[Evaluasi kualitas artefak berdasarkan standar]\n\n";
                $systemContext .= "# REKOMENDASI\n";
                $systemContext .= "[Saran perbaikan dan tindak lanjut]\n\n";
                $systemContext .= "# KESIMPULAN\n";
                $systemContext .= "[Kesimpulan dan ringkasan rekomendasi]\n\n";
            }

            $systemContext .= "Fokus EKSKLUSIF pada analisis artefak akademik seperti RPS, silabus, materi kuliah, dan dokumen pembelajaran.\n";
            $systemContext .= "Gunakan Bahasa Indonesia formal dan profesional. Setiap bagian harus berisi konten yang substantif dan relevan.\n";
            $systemContext .= "INGAT: Tolak dengan sopan setiap permintaan yang tidak terkait dengan laporan artefak RPS dan Materi!\n\n";
            
            $systemContext .= "=== FORMAT DATA DALAM LAPORAN ===\n";
            $systemContext .= "1. **Penggabungan Matakuliah**: Jika ada matakuliah dengan kode yang sama tetapi dosen berbeda, GABUNGKAN dalam satu baris dengan nama dosen dipisahkan koma.\n";
            $systemContext .= "   Contoh: Dosen A, Dosen B, Dosen C (BUKAN baris terpisah)\n\n";
            $systemContext .= "2. **Format Status Upload**: Gunakan angka:\n";
            $systemContext .= "   - '1' untuk sudah upload\n";
            $systemContext .= "   - '0' untuk belum upload\n";
            $systemContext .= "   - Format header: 'RPS (0=Tidak, 1=Ya)'\n\n";
            $systemContext .= "3. **Tabel yang Rapi**: Pastikan tabel mudah dibaca dengan kolom yang jelas\n\n";

            // Extract file content if uploaded
            $filesContext = [];
            $imageContents = [];
            $ocrTexts = [];

            if ($request->hasFile('file_referensi')) {
                $files = $request->file('file_referensi');

                foreach ($files as $index => $file) {
                    $fileName = $file->getClientOriginalName();
                    $fileExtension = strtolower($file->getClientOriginalExtension());

                    if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        try {
                            $imagePath = $file->store('temp_uploads', 'local');
                            $fullImagePath = storage_path('app/' . $imagePath);

                            $ocrResult = $ocrService->extractText($fullImagePath);

                            if ($ocrResult['success'] && !empty($ocrResult['text'])) {
                                $ocrText = is_array($ocrResult['text']) ? json_encode($ocrResult['text']) : (string)$ocrResult['text'];

                                $ocrTexts[] = [
                                    'filename' => $fileName,
                                    'text' => $ocrText,
                                    'method' => $ocrResult['method'],
                                    'confidence' => $ocrResult['confidence']
                                ];
                            }

                            if (file_exists($fullImagePath)) {
                                @unlink($fullImagePath);
                            }

                            Log::info('Image processed with OCR for Artefak', [
                                'filename' => $fileName,
                                'ocr_text_length' => isset($ocrText) ? strlen($ocrText) : 0
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Image processing failed for Artefak', [
                                'filename' => $fileName,
                                'error' => $e->getMessage()
                            ]);
                        }
                    } else {
                        $filePath = $file->store('temp_uploads', 'local');
                        $fullPath = storage_path('app/' . $filePath);

                        try {
                            $result = $textExtraction->extractFromFile($fullPath);
                            $fileContent = $result['text'] ?? '';

                            $filesContext[] = [
                                'filename' => $fileName,
                                'content' => $fileContent,
                                'type' => $this->categorizeArtefakFile($fileName)
                            ];

                            Log::info('Document extracted for Artefak AI prompt', [
                                'filename' => $fileName,
                                'size' => strlen($fileContent),
                                'type' => $this->categorizeArtefakFile($fileName)
                            ]);
                        } catch (\Exception $e) {
                            Log::error('File extraction failed for Artefak', [
                                'filename' => $fileName,
                                'error' => $e->getMessage()
                            ]);
                            return response()->json([
                                'success' => false,
                                'message' => 'Gagal membaca file ' . $fileName . ': ' . $e->getMessage()
                            ], 400);
                        } finally {
                            if (file_exists($fullPath)) {
                                @unlink($fullPath);
                            }
                        }
                    }
                }
            }

            // Build conversation messages for Chat Completions API
            $messages = [];

            $messages[] = [
                'role' => 'system',
                'content' => $systemContext
            ];

            if (!empty($conversationHistory)) {
                foreach ($conversationHistory as $msg) {
                    $role = $msg['role'] ?? 'user';
                    $content = $msg['content'] ?? '';

                    if (empty($content)) continue;

                    if ($role === 'assistant' || $role === 'ai') {
                        $role = 'assistant';
                    }

                    $messages[] = [
                        'role' => $role,
                        'content' => $content
                    ];
                }
            }

            $currentMessage = '';

            if (!empty($periode)) {
                $currentMessage .= "PERIODE LAPORAN: {$periode}\n\n";
            }

            // ================================================================
            // TAMBAHKAN DRAFT LAPORAN YANG SUDAH ADA (UNTUK KONTEKS REVISI)
            // ================================================================
            if (!empty($existingDraft)) {
                $currentMessage .= "===== DRAFT LAPORAN YANG SUDAH ADA =====\n\n";
                $currentMessage .= $existingDraft . "\n\n";
                $currentMessage .= "===== END DRAFT YANG SUDAH ADA =====\n\n";
                
                if ($isRevisionRequest) {
                    $currentMessage .= "⚠️ PERHATIAN PENTING ⚠️\n";
                    $currentMessage .= "User meminta PERBAIKIAN/REVISI pada draft laporan di atas.\n";
                    $currentMessage .= "INSTRUKSI USER: {$userPrompt}\n\n";
                    $currentMessage .= "Yang harus Anda lakukan:\n";
                    $currentMessage .= "1. Identifikasi bagian yang diminta untuk diubah dari draft di atas\n";
                    $currentMessage .= "2. HANYA ubah bagian yang diminta, PERTAHANKAN semua bagian lain\n";
                    $currentMessage .= "3. JANGAN buat ulang seluruh laporan dari awal\n";
                    $currentMessage .= "4. Jika user minta 'tambah di bawah ini', tambahkan konten baru di akhir bagian yang relevan\n";
                    $currentMessage .= "5. Jika user minta 'hapus bagian X', hapus bagian tersebut\n";
                    $currentMessage .= "6. Setelah selesai, tampilkan SELURUH draft laporan yang sudah diperbaiki\n\n";
                    $currentMessage .= "Awali respons dengan: '✅ **Laporan telah diperbaiki sesuai permintaan!**'\n\n";
                } else {
                    $currentMessage .= "Catatan: Di atas adalah draft laporan yang sudah pernah saya buat sebelumnya.\n";
                    $currentMessage .= "Gunakan sebagai referensi jika user meminta perubahan.\n\n";
                }
            }

            // Add database context (RPS & Materi data) if no file upload
            if (!empty($databaseContext)) {
                $currentMessage .= "===== DATA RPS DAN MATERI DARI DATABASE =====\n\n";
                $currentMessage .= $databaseContext . "\n\n";
                $currentMessage .= "===== END DATA DATABASE =====\n\n";
            }

            // Process uploaded documents
            if (!empty($filesContext)) {
                $currentMessage .= "Saya telah mengupload beberapa dokumen artefak:\n\n";

                foreach ($filesContext as $fileData) {
                    $content = $fileData['content'];
                    $maxFileContentLength = 15000;

                    if (strlen($content) > $maxFileContentLength) {
                        $content = substr($content, 0, $maxFileContentLength) . "\n\n[DOKUMEN DIPOTONG - HANYA BAGIAN AWAL YANG DIPROSES]";
                    }

                    $currentMessage .= "**{$fileData['filename']}** ({$fileData['type']}):\n";
                    $currentMessage .= "```\n" . $content . "\n```\n\n";
                }
            }

            // Add OCR texts from current upload
            if (!empty($ocrTexts)) {
                $currentMessage .= "Teks yang diekstrak dari gambar artefak:\n\n";
                foreach ($ocrTexts as $ocrData) {
                    $currentMessage .= "**{$ocrData['filename']}** (OCR Method: {$ocrData['method']}, Confidence: {$ocrData['confidence']}%):\n";
                    $currentMessage .= "```\n" . $ocrData['text'] . "\n```\n\n";
                }
            }

            // Jika tidak ada draft yang sudah ada, tambahkan instruksi user biasa
            if (empty($existingDraft)) {
                $currentMessage .= "Instruksi dari user: " . $userPrompt . "\n\n";
            } else if (!$isRevisionRequest) {
                // Ada draft tapi bukan permintaan revisi
                $currentMessage .= "Instruksi dari user: " . $userPrompt . "\n\n";
                $currentMessage .= "Catatan: Draft laporan sudah ada di atas. Jika user tidak meminta perubahan, Anda bisa memberikan respons berdasarkan instruksi tersebut.\n\n";
            }
            
            $currentMessage .= "⚠️ PERINGATAN KERAS: Periksa terlebih dahulu apakah instruksi user di atas terkait dengan LAPORAN ARTEFAK RPS DAN MATERI.\n\n";
            $currentMessage .= "Jika instruksi di atas TIDAK terkait dengan:\n";
            $currentMessage .= "- Pembuatan laporan artefak\n";
            $currentMessage .= "- Analisis RPS dan Materi\n";
            $currentMessage .= "- Format/struktur laporan\n";
            $currentMessage .= "- Perbaikan draft laporan\n\n";
            $currentMessage .= "Maka Anda WAJIB menolak dengan respons: \"Maaf, permintaan Anda di luar konteks pembuatan Laporan Bulanan. Saya hanya dapat membantu dengan pembuatan laporan monitoring RPS dan Materi perkuliahan. Silakan ajukan pertanyaan terkait laporan bulanan.\"\n\n";
            $currentMessage .= "JANGAN JAWAB pertanyaan tentang: siapa, kenalan, cuaca, berita, resep, musik, film, game, olahraga, hewan, fitness, atau topik pribadi lainnya!\n\n";

            if ($templateStructure) {
                $currentMessage .= "PENTING: Anda HARUS menghasilkan draft laporan artefak yang mengikuti STRUKTUR TEMPLATE yang telah diberikan di system context.\n\n";
                $currentMessage .= "Gunakan semua dokumen artefak yang saya upload sebagai sumber data dan informasi untuk mengisi setiap bagian template.\n\n";
                $currentMessage .= "Setiap bagian harus berisi minimal 2-3 paragraf dengan konten yang substantif dan relevan berdasarkan analisis artefak.\n";
            } else {
                $currentMessage .= "PENTING: Anda HARUS menghasilkan SEMUA 7 bagian berikut dengan konten yang substantif:\n\n";
                $currentMessage .= "1. # RINGKASAN EKSEKUTIF\n";
                $currentMessage .= "2. # PENDAHULUAN\n";
                $currentMessage .= "3. # METODOLOGI\n";
                $currentMessage .= "4. # TEMUAN UTAMA\n";
                $currentMessage .= "5. # ANALISIS KUALITAS\n";
                $currentMessage .= "6. # REKOMENDASI\n";
                $currentMessage .= "7. # KESIMPULAN\n\n";
                $currentMessage .= "Jangan skip bagian manapun. Setiap bagian harus berisi minimal 2-3 paragraf dengan analisis yang mendalam.\n";
            }

            $currentMessage .= "Fokus pada evaluasi kualitas artefak akademik dan berikan rekomendasi perbaikan yang konkret.\n";

            $messages[] = [
                'role' => 'user',
                'content' => $currentMessage
            ];

            $aiResult = $aiService->generateChat($messages, [
                'max_tokens' => 8192,
                'temperature' => 0.7
            ]);

            if (!$aiResult['success'] || empty($aiResult['text'])) {
                Log::error('AI returned empty response for Artefak', [
                    'prompt_length' => strlen($userPrompt),
                    'files_count' => count($filesContext),
                    'images_count' => count($imageContents),
                    'messages_count' => count($messages),
                    'conversation_turns' => count(array_filter($messages, fn($m) => ($m['role'] ?? '') !== 'system')),
                    'error' => $aiResult['error'] ?? 'Unknown error',
                    'provider' => $aiResult['provider'] ?? 'unknown'
                ]);

                $errorMessage = 'Layanan AI mengalami masalah. ';
                if (isset($aiResult['error'])) {
                    if (str_contains($aiResult['error'], 'Rate limit') || str_contains($aiResult['error'], '429')) {
                        $errorMessage .= 'Terlalu banyak permintaan, silakan tunggu sebentar dan coba lagi.';
                    } elseif (str_contains($aiResult['error'], 'token')) {
                        $errorMessage .= 'Percakapan terlalu panjang, silakan mulai percakapan baru.';
                    } else {
                        $errorMessage .= 'Silakan coba lagi dalam beberapa menit.';
                    }
                } else {
                    $errorMessage .= 'Silakan coba lagi atau hubungi administrator.';
                }

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'debug_info' => [
                        'messages_count' => count($messages),
                        'conversation_turns' => count(array_filter($messages, fn($m) => ($m['role'] ?? '') !== 'system')),
                        'provider' => $aiResult['provider'] ?? 'unknown'
                    ]
                ], 503);
            }

            $aiResponse = $aiResult['text'];

            // Simpan response ke database jika ada laporan_id
            if ($laporanId) {
                try {
                    $laporan = LaporanGKM::find($laporanId);
                    if ($laporan) {
                        $sections = $this->parseMarkdownSections($aiResponse);
                        $laporan->update([
                            'ai_preview_draft' => $aiResponse,
                            'ai_sections' => $sections,
                            'status' => 'preview_ready',
                        ]);
                        Log::info('Updated draft laporan after AI response', [
                            'laporan_id' => $laporanId,
                            'is_revision' => $isRevisionRequest
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to update laporan with AI response', [
                        'laporan_id' => $laporanId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Cache the response for future use (skip jika revision request)
            if (!$isRevisionRequest) {
                $responseTime = isset($aiResult['processing_time_ms']) ? $aiResult['processing_time_ms'] / 1000 : null;
                $cacheService->cacheResponse(
                    $userPrompt,
                    $cacheContext,
                    $aiResponse,
                    $aiResult['provider'],
                    $aiResult['model'],
                    $responseTime
                );
            } else {
                Log::info('Skipping cache for revision request', [
                    'prompt' => $userPrompt
                ]);
            }

            // Create evaluation test entry
            try {
                $evaluationService = app(\App\Services\AIEvaluationService::class);
                $evaluationService->createAIResponseTest([
                    'test_name' => 'Laporan Artefak Chat - ' . date('Y-m-d H:i:s'),
                    'feature' => 'artefak',
                    'query' => $userPrompt,
                    'expected_response' => null,
                    'actual_response' => $aiResponse,
                ]);
                
                Log::info('AI Evaluation test created', [
                    'feature' => 'artefak_chat',
                    'prompt_length' => strlen($userPrompt),
                    'response_length' => strlen($aiResponse),
                    'is_revision' => $isRevisionRequest
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to create AI evaluation test', [
                    'error' => $e->getMessage(),
                    'feature' => 'artefak_chat'
                ]);
            }

            Log::info('AI Prompt successful for Artefak', [
                'prompt_length' => strlen($userPrompt),
                'response_length' => strlen($aiResponse),
                'files_count' => count($filesContext),
                'images_count' => count($imageContents),
                'messages_count' => count($messages),
                'has_template' => !empty($templateStructure),
                'has_existing_draft' => !empty($existingDraft),
                'is_revision_request' => $isRevisionRequest,
                'provider' => $aiResult['provider'],
                'model' => $aiResult['model'],
                'cached' => false
            ]);

            return response()->json([
                'success' => true,
                'response' => $aiResponse,
                'model_info' => $aiResult['provider'] . ' (' . $aiResult['model'] . ')',
                'cached' => false,
                'is_revision' => $isRevisionRequest,
            ]);

        } catch (\Exception $e) {
            Log::error('AI Prompt failed for Artefak', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_prompt' => substr($userPrompt ?? '', 0, 100),
                'files_count' => count($filesContext ?? []),
                'images_count' => count($imageContents ?? [])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse markdown sections from AI response
     */
    private function parseMarkdownSections($text)
    {
        $sections = [];
        $lines = explode("\n", $text);
        $currentSection = null;
        $currentContent = [];

        foreach ($lines as $line) {
            if (preg_match('/^# (.+)$/', $line, $matches)) {
                if ($currentSection) {
                    $sections[$this->sectionTitleToKey($currentSection)] = trim(implode("\n", $currentContent));
                }
                $currentSection = trim($matches[1]);
                $currentContent = [];
            } else {
                if ($currentSection) {
                    $currentContent[] = $line;
                }
            }
        }

        if ($currentSection) {
            $sections[$this->sectionTitleToKey($currentSection)] = trim(implode("\n", $currentContent));
        }

        return $sections;
    }

    /**
     * Save AI preview data before generating Word document
     */
    public function savePreview(Request $request)
    {
        try {
            $request->validate([
                'laporan_id' => 'required|exists:laporan_gkm,id',
                'ai_preview_draft' => 'required|string',
                'ai_sections' => 'nullable|string',
            ]);

            $laporanId = $request->input('laporan_id');
            $aiPreviewDraft = $request->input('ai_preview_draft');
            $aiSectionsJson = $request->input('ai_sections', '[]');
            
            $sections = [];
            try {
                $sectionsArray = json_decode($aiSectionsJson, true);
                if (is_array($sectionsArray)) {
                    foreach ($sectionsArray as $section) {
                        if (isset($section['title']) && isset($section['content'])) {
                            $key = $this->sectionTitleToKey($section['title']);
                            $sections[$key] = $section['content'];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to parse AI sections JSON for Artefak', [
                    'laporan_id' => $laporanId,
                    'error' => $e->getMessage()
                ]);
            }

            $laporan = LaporanGKM::findOrFail($laporanId);
            
            $laporan->update([
                'ai_preview_draft' => $aiPreviewDraft,
                'ai_sections' => $sections,
                'status' => 'preview_ready',
            ]);

            Log::info('AI preview saved for Artefak', [
                'laporan_id' => $laporanId,
                'sections_count' => count($sections),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'AI preview saved successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            Log::error('Save AI preview failed for Artefak', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convert section title to database key
     */
    private function sectionTitleToKey($title)
    {
        $key = strtolower(trim($title));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);
        $key = trim($key, '_');
        return $key;
    }

    /**
     * Categorize artefak file type
     */
    private function categorizeArtefakFile($filename)
    {
        $filename = strtolower($filename);

        if (str_contains($filename, 'rps') || str_contains($filename, 'silabus')) {
            return 'RPS/Silabus';
        } elseif (str_contains($filename, 'materi') || str_contains($filename, 'slide') || str_contains($filename, 'ppt')) {
            return 'Materi Kuliah';
        } elseif (str_contains($filename, 'soal') || str_contains($filename, 'ujian') || str_contains($filename, 'quiz')) {
            return 'Soal/Evaluasi';
        } elseif (str_contains($filename, 'tugas') || str_contains($filename, 'assignment')) {
            return 'Tugas';
        } elseif (str_contains($filename, 'laporan') || str_contains($filename, 'report')) {
            return 'Laporan';
        } else {
            return 'Dokumen Artefak';
        }
    }

    /**
     * Extract template structure
     */
    private function extractTemplateStructure($templateId)
    {
        if (!$templateId) return null;

        try {
            $template = TemplateLaporan::find($templateId);
            if (!$template) return null;

            return "Template structure for artefak report...";
        } catch (\Exception $e) {
            Log::warning('Failed to extract template structure for Artefak', [
                'template_id' => $templateId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Store new laporan artefak
     */
    public function store(Request $request)
    {
        $request->validate([
            'periode' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'template_id' => 'nullable|exists:template_laporan,id',
            'mode' => 'nullable|in:sync,async',
        ]);

        $user = Auth::user();
        $periode = $request->periode;
        $mode = $request->input('mode', 'sync');

        $existing = LaporanGKM::where('periode', $periode)
            ->where('jenis_laporan', 'artefak')
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'Laporan untuk periode ini sudah ada. Silakan hapus terlebih dahulu jika ingin generate ulang.');
        }

        $periodeObj = Carbon::createFromFormat('Y-m', $periode);
        $bulan = $periodeObj->locale('id')->translatedFormat('F');
        $tahun = $periodeObj->year;

        $laporan = LaporanGKM::create([
            'periode' => $periode,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'user_id' => $user->id,
            'template_id' => $request->template_id,
            'jenis_laporan' => 'artefak',
            'status' => 'pending',
        ]);

        if ($mode === 'sync') {
            try {
                $job = new GenerateLaporanArtefakJob($laporan->id);
                $job->handle(app(LaporanArtefakService::class));

                return redirect()->route('gkm.laporan-artefak.show', $laporan->id)
                    ->with('success', 'Laporan berhasil di-generate!');
            } catch (\Exception $e) {
                \Log::error('Sync laporan artefak generation failed', [
                    'laporan_id' => $laporan->id,
                    'error' => $e->getMessage()
                ]);

                return redirect()->route('gkm.laporan-artefak.show', $laporan->id)
                    ->with('error', 'Gagal generate laporan: ' . $e->getMessage());
            }
        } else {
            GenerateLaporanArtefakJob::dispatch($laporan->id);

            return redirect()->route('gkm.laporan-artefak.show', $laporan->id)
                ->with('success', 'Laporan sedang diproses oleh AI Agent. Halaman akan otomatis refresh.');
        }
    }

    /**
     * Show laporan artefak detail
     */
    public function show($id)
    {
        $laporan = LaporanGKM::with(['user', 'template'])
            ->where('jenis_laporan', 'artefak')
            ->findOrFail($id);

        $user = Auth::user();
        $hasAccess = $laporan->user_id == $user->id ||
                     in_array($user->role, ['GKM', 'GJM']);
        
        if (!$hasAccess) {
            abort(403, 'Unauthorized access');
        }

        return view('gkm.laporan-artefak.show', compact('laporan'));
    }

    /**
     * Download laporan artefak
     */
    public function download($id, $format = 'word')
    {
        try {
            $laporan = LaporanGKM::where('jenis_laporan', 'artefak')->findOrFail($id);

            $user = Auth::user();
            $hasAccess = $laporan->user_id == $user->id ||
                         in_array($user->role, ['GKM', 'GJM']);
            
            if (!$hasAccess) {
                abort(403, 'Unauthorized access');
            }

            if ($laporan->status != 'completed') {
                return redirect()->back()->with('error', 'Laporan belum selesai diproses.');
            }

            if ($format == 'word' && $laporan->file_word) {
                $filePath = storage_path('app/' . $laporan->file_word);

                \Log::info('Attempting to download file', [
                    'laporan_id' => $id,
                    'file_path' => $filePath,
                    'file_exists' => file_exists($filePath)
                ]);

                if (!file_exists($filePath)) {
                    \Log::error('File not found', ['path' => $filePath]);
                    return redirect()->back()->with('error', 'File tidak ditemukan di server.');
                }

                $fileName = 'Laporan_Artefak_' . $laporan->periode . '_' . ($laporan->prodi->kode_prodi ?? 'GKM') . '.docx';

                return response()->download($filePath, $fileName, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ]);
            } elseif ($format == 'pdf' && $laporan->file_pdf) {
                $filePath = storage_path('app/' . $laporan->file_pdf);

                if (!file_exists($filePath)) {
                    return redirect()->back()->with('error', 'File tidak ditemukan di server.');
                }

                $fileName = 'Laporan_Artefak_' . $laporan->periode . '_' . ($laporan->prodi->kode_prodi ?? 'GKM') . '.pdf';

                return response()->download($filePath, $fileName, [
                    'Content-Type' => 'application/pdf',
                ]);
            }

            return redirect()->back()->with('error', 'File tidak tersedia.');

        } catch (\Exception $e) {
            \Log::error('Download error', [
                'laporan_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Terjadi kesalahan saat download: ' . $e->getMessage());
        }
    }

    /**
     * Delete laporan artefak
     */
    public function destroy($id)
    {
        $laporan = LaporanGKM::where('jenis_laporan', 'artefak')->findOrFail($id);

        $user = Auth::user();
        $hasAccess = $laporan->user_id == $user->id ||
                     in_array($user->role, ['GKM', 'GJM']);
        
        if (!$hasAccess) {
            abort(403, 'Unauthorized access');
        }

        if ($laporan->file_word) {
            Storage::delete($laporan->file_word);
        }
        if ($laporan->file_pdf) {
            Storage::delete($laporan->file_pdf);
        }

        $laporan->delete();

        return redirect()->route('gkm.laporan-artefak.index')
            ->with('success', 'Laporan berhasil dihapus.');
    }

    /**
     * Generate Word document from AI preview
     */
    public function generateWordDocument(Request $request)
    {
        try {
            $request->validate([
                'laporan_id' => 'required|exists:laporan_gkm,id',
                'ai_preview_data' => 'required|string',
            ]);

            $laporanId = $request->input('laporan_id');
            $aiPreviewData = $request->input('ai_preview_data');

            $laporan = LaporanGKM::findOrFail($laporanId);

            $user = Auth::user();
            $hasAccess = $laporan->user_id == $user->id || in_array($user->role, ['GKM', 'GJM']);
            
            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            Log::info('Generate Word from AI preview', [
                'laporan_id' => $laporanId,
                'preview_length' => strlen($aiPreviewData),
            ]);

            $generatedLaporan = $this->laporanService->generateLaporan($laporanId);

            if (!$generatedLaporan->file_word || !file_exists(storage_path('app/' . $generatedLaporan->file_word))) {
                throw new \Exception('File Word gagal dibuat');
            }

            $filePath = storage_path('app/' . $generatedLaporan->file_word);
            $fileName = 'Laporan_Artefak_' . $generatedLaporan->periode . '_' . time() . '.docx';

            Log::info('Word document generated successfully from AI preview', [
                'laporan_id' => $laporanId,
                'file_path' => $generatedLaporan->file_word,
            ]);

            return response()->download($filePath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);

        } catch (\Exception $e) {
            Log::error('Generate Word from AI preview failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'laporan_id' => $request->input('laporan_id'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate laporan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Template Management - Index
     */
    public function templateIndex()
    {
        $templates = TemplateLaporan::with(['prodi', 'uploader'])
            ->jenis('laporan_artefak')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('gkm.laporan-artefak.template.index', compact('templates'));
    }

    /**
     * Template Management - Upload Form
     */
    public function templateUpload()
    {
        return view('gkm.laporan-artefak.template.upload');
    }

    /**
     * Template Management - Store
     */
    public function templateStore(Request $request)
    {
        $request->validate([
            'nama_template' => 'required|string|max:255',
            'file_template' => 'required|file|mimes:docx,doc,pdf|max:10240',
            'contoh_konten' => 'nullable|string',
        ]);

        $user = Auth::user();
        $file = $request->file('file_template');

        if ($file->getClientOriginalExtension() === 'docx' || $file->getClientOriginalExtension() === 'doc') {
            $tempPath = $file->getRealPath();
            $zip = new \ZipArchive();
            $checkResult = $zip->open($tempPath, \ZipArchive::CHECKCONS);

            if ($checkResult !== true) {
                return redirect()->back()
                    ->withErrors(['file_template' => 'File Word yang diupload tidak valid atau corrupt. Silakan coba file lain.'])
                    ->withInput();
            }
            $zip->close();
        }

        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('templates', $fileName, 'public');

        $template = TemplateLaporan::create([
            'nama_template' => $request->nama_template,
            'nama_file' => $file->getClientOriginalName(),
            'jenis_file' => $file->getClientOriginalExtension(),
            'file_path' => $filePath,
            'ukuran_file' => $file->getSize(),
            'uploaded_by' => $user->id,
            'jenis_template' => 'laporan_artefak',
            'contoh_konten' => $request->contoh_konten,
            'is_active' => true,
        ]);

        try {
            $this->laporanService->processTemplateToVectorDB($template->id);
            $message = 'Template berhasil diupload dan diproses ke vector database.';
        } catch (\Exception $e) {
            Log::error('Failed to process template to vector DB', [
                'template_id' => $template->id,
                'error' => $e->getMessage()
            ]);
            $message = 'Template berhasil diupload, tapi gagal diproses ke vector DB: ' . $e->getMessage();
        }

        return redirect()->route('gkm.laporan-artefak.template.index')
            ->with('success', $message);
    }

    /**
     * Reprocess template ke vector DB
     */
    public function templateReindex($id)
    {
        try {
            $result = $this->laporanService->processTemplateToVectorDB($id);

            return redirect()->back()->with('success',
                "Template berhasil di-reindex. Total chunks: {$result['chunks_indexed']}"
            );
        } catch (\Exception $e) {
            return redirect()->back()->with('error',
                'Gagal reindex template: ' . $e->getMessage()
            );
        }
    }

    /**
     * Template Management - Toggle Active
     */
    public function templateToggle($id)
    {
        $template = TemplateLaporan::findOrFail($id);
        $template->is_active = !$template->is_active;
        $template->save();

        return redirect()->back()->with('success', 'Status template berhasil diubah.');
    }

    /**
     * Template Management - Delete
     */
    public function templateDestroy($id)
    {
        $template = TemplateLaporan::findOrFail($id);

        $usageCount = LaporanGKM::where('template_id', $id)
            ->where('jenis_laporan', 'artefak')
            ->count();

        if ($usageCount > 0) {
            return redirect()->back()->with('error', "Template tidak dapat dihapus karena sedang digunakan oleh {$usageCount} laporan.");
        }

        if ($template->file_path) {
            Storage::disk('public')->delete($template->file_path);
        }

        $template->delete();

        return redirect()->route('gkm.laporan-artefak.template.index')
            ->with('success', 'Template berhasil dihapus.');
    }

    /**
     * Download template file
     */
    public function templateDownload($id)
    {
        try {
            $template = TemplateLaporan::findOrFail($id);

            \Log::info('Template download attempt', [
                'template_id' => $id,
                'file_path' => $template->file_path,
                'nama_file' => $template->nama_file
            ]);

            $possiblePaths = [
                storage_path('app/public/' . $template->file_path),
                storage_path('app/' . $template->file_path),
                storage_path('app/public/templates/' . $template->nama_file),
                storage_path('app/templates/' . $template->nama_file),
            ];

            $filePath = null;
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $filePath = $path;
                    break;
                }
            }

            if (!$filePath) {
                \Log::error('Template file not found', [
                    'template_id' => $id,
                    'checked_paths' => $possiblePaths
                ]);
                return redirect()->back()->with('error', 'File template tidak ditemukan.');
            }

            \Log::info('Template download successful', [
                'template_id' => $id,
                'file_path' => $filePath
            ]);

            $fileName = $template->nama_file;

            return response()->download($filePath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);

        } catch (\Exception $e) {
            \Log::error('Template download error', [
                'template_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Terjadi kesalahan saat download: ' . $e->getMessage());
        }
    }

    /**
     * Get RPS and Materi data from database for AI context
     */
    private function getArtefakDataFromDatabase($periode = null)
    {
        try {
            $user = Auth::user();
            $prodiKode = $user->prodi->kode_prodi ?? 'TRPL';
            
            $prodiIdMap = [
                'TRPL' => 4,
                'TI'   => 1,
                'NM'   => 3,
            ];
            
            $prodiId = $prodiIdMap[$prodiKode] ?? 4;
            
            if ($periode) {
                $year = (int) substr($periode, 0, 4);
                $month = (int) substr($periode, 5, 2);
                
                if ($month <= 6) {
                    $semester = 2;
                    $tahunAjaran = ($year - 1) . '/' . $year;
                } else {
                    $semester = 1;
                    $tahunAjaran = $year . '/' . ($year + 1);
                }
            } else {
                $periodeAktif = \App\Models\PeriodeAkademik::where('is_active', true)->first();
                if ($periodeAktif) {
                    $semester = $periodeAktif->semester;
                    $tahunAjaran = $periodeAktif->tahun_ajaran;
                } else {
                    $currentMonth = (int) date('n');
                    $currentYear = (int) date('Y');
                    if ($currentMonth <= 6) {
                        $semester = 2;
                        $tahunAjaran = ($currentYear - 1) . '/' . $currentYear;
                    } else {
                        $semester = 1;
                        $tahunAjaran = $currentYear . '/' . ($currentYear + 1);
                    }
                }
            }
            
            $context = "=== DATA ARTEFAK DARI MONITORING SISTEM ===\n\n";
            $context .= "Program Studi: " . ($prodiKode === 'TRPL' ? 'Teknik Rekayasa Perangkat Lunak' : 
                                            ($prodiKode === 'TI' ? 'Teknologi Informasi' : 'Teknik Elektro')) . "\n";
            $context .= "Semester: " . ($semester == 1 ? 'Ganjil' : 'Genap') . " {$tahunAjaran}\n";
            $context .= "Periode Pelaporan: {$periode}\n\n";
            
            $rpsSnapshots = \DB::table('perkuliahan_monitoring_snapshots')
                ->where('prodi_id', $prodiId)
                ->where('semester', $semester)
                ->where('tahun_ajaran', $tahunAjaran)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($rpsSnapshots && isset($rpsSnapshots->monitoring_data)) {
                $monitoringData = is_string($rpsSnapshots->monitoring_data) ? 
                                 json_decode($rpsSnapshots->monitoring_data, true) : 
                                 $rpsSnapshots->monitoring_data;
                
                if (is_array($monitoringData) && !empty($monitoringData)) {
                    $groupedByKode = [];
                    
                    foreach ($monitoringData as $matkul) {
                        $kodeMK = $matkul['kode_matakuliah'] ?? '-';
                        
                        if (!isset($groupedByKode[$kodeMK])) {
                            $groupedByKode[$kodeMK] = [
                                'kode_matakuliah' => $kodeMK,
                                'nama_matakuliah' => $matkul['nama_matakuliah'] ?? '-',
                                'dosen_pengampu' => [],
                                'status_upload_rps' => $matkul['status_upload_rps'] ?? 'Belum Upload',
                                'status_materi' => $matkul['status_materi'] ?? 'Belum Upload',
                                'minggu_ke' => $matkul['minggu_ke'] ?? '-',
                                'keterangan' => $matkul['keterangan'] ?? '-',
                            ];
                        }
                        
                        $dosen = $matkul['dosen_pengampu'] ?? '-';
                        if ($dosen !== '-' && !in_array($dosen, $groupedByKode[$kodeMK]['dosen_pengampu'])) {
                            $groupedByKode[$kodeMK]['dosen_pengampu'][] = $dosen;
                        }
                        
                        if (isset($matkul['status_upload_rps']) && 
                            (str_contains(strtolower($matkul['status_upload_rps']), 'upload') || $matkul['status_upload_rps'] === '1')) {
                            $groupedByKode[$kodeMK]['status_upload_rps'] = 'Sudah Upload';
                        }
                        
                        if (isset($matkul['status_materi']) && 
                            (str_contains(strtolower($matkul['status_materi']), 'upload') || $matkul['status_materi'] === '1')) {
                            $groupedByKode[$kodeMK]['status_materi'] = 'Sudah Upload';
                        }
                    }
                    
                    $context .= "## STATUS UPLOAD RPS DAN MATERI\n\n";
                    $context .= "| Kode | Nama Matakuliah | Dosen Pengampu | Status RPS | Status Materi | Minggu Ke | Keterangan |\n";
                    $context .= "|------|----------------|----------------|------------|---------------|-----------|------------|\n";
                    
                    $totalMK = 0;
                    $rpsUploaded = 0;
                    $materiUploaded = 0;
                    
                    foreach ($groupedByKode as $matkul) {
                        $totalMK++;
                        $kodeMK = $matkul['kode_matakuliah'];
                        $namaMK = $matkul['nama_matakuliah'];
                        
                        $dosen = !empty($matkul['dosen_pengampu']) ? 
                                implode(', ', $matkul['dosen_pengampu']) : '-';
                        
                        $statusRPS = $matkul['status_upload_rps'];
                        if ($statusRPS === '0' || $statusRPS === 0 || strtolower($statusRPS) === 'belum upload') {
                            $statusRPS = '0';
                        } elseif ($statusRPS === '1' || $statusRPS === 1 || strtolower($statusRPS) === 'sudah upload') {
                            $statusRPS = '1';
                            $rpsUploaded++;
                        } else {
                            if (str_contains(strtolower($statusRPS), 'upload') && !str_contains(strtolower($statusRPS), 'belum')) {
                                $statusRPS = '1';
                                $rpsUploaded++;
                            } else {
                                $statusRPS = '0';
                            }
                        }
                        
                        $statusMateri = $matkul['status_materi'];
                        if ($statusMateri === '0' || $statusMateri === 0 || strtolower($statusMateri) === 'belum upload') {
                            $statusMateri = '0';
                        } elseif ($statusMateri === '1' || $statusMateri === 1 || strtolower($statusMateri) === 'sudah upload') {
                            $statusMateri = '1';
                            $materiUploaded++;
                        } else {
                            if (str_contains(strtolower($statusMateri), 'upload') && !str_contains(strtolower($statusMateri), 'belum')) {
                                $statusMateri = '1';
                                $materiUploaded++;
                            } else {
                                $statusMateri = '0';
                            }
                        }
                        
                        $mingguKe = $matkul['minggu_ke'];
                        $keterangan = $matkul['keterangan'];
                        
                        $context .= "| {$kodeMK} | {$namaMK} | {$dosen} | {$statusRPS} | {$statusMateri} | {$mingguKe} | {$keterangan} |\n";
                    }
                    
                    $context .= "\n";
                    $context .= "### RINGKASAN STATISTIK\n\n";
                    $context .= "- Total Matakuliah: {$totalMK}\n";
                    $context .= "- RPS Sudah Diupload: {$rpsUploaded} (" . ($totalMK > 0 ? round(($rpsUploaded / $totalMK) * 100, 1) : 0) . "%)\n";
                    $context .= "- Materi Sudah Diupload: {$materiUploaded} (" . ($totalMK > 0 ? round(($materiUploaded / $totalMK) * 100, 1) : 0) . "%)\n";
                    $context .= "- RPS Belum Diupload: " . ($totalMK - $rpsUploaded) . "\n";
                    $context .= "- Materi Belum Diupload: " . ($totalMK - $materiUploaded) . "\n\n";
                }
            } else {
                $context .= "⚠️ Data monitoring RPS dan Materi untuk periode ini belum tersedia di sistem.\n";
                $context .= "Silakan gunakan data umum atau upload file referensi untuk analisis yang lebih mendalam.\n\n";
            }
            
            $context .= "=== END DATA ARTEFAK ===\n";
            
            return $context;
            
        } catch (\Exception $e) {
            Log::error('Failed to get artefak data from database', [
                'error' => $e->getMessage(),
                'periode' => $periode
            ]);
            
            return "Data artefak dari database tidak dapat diambil. Silakan upload file referensi untuk analisis.\n";
        }
    }
}