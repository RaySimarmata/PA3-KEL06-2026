<?php

namespace App\Http\Controllers\GKM;

use App\Http\Controllers\Controller;
use App\Models\LaporanBulanan;
use App\Models\TemplateLaporan;
use App\Models\Prodi;
use App\Models\KuesioneUpload;
use App\Services\LaporanKuesioneService;
use App\Jobs\GenerateLaporanBulananJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LaporanKuesioneController extends Controller
{
    protected $laporanService;

    public function __construct(LaporanKuesioneService $laporanService)
    {
        $this->laporanService = $laporanService;
    }

    /**
     * Display a listing of laporan kuesioner
     */
    public function index()
    {
        $user = Auth::user();

        $query = LaporanBulanan::where('user_id', $user->id);

        // Apply filters
        if (request('periode')) {
            $query->where('periode', request('periode'));
        }
        if (request('status')) {
            $query->where('status', request('status'));
        }

        $laporanList = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get distinct periods from database for filter
        $periodesFromDb = LaporanBulanan::select('periode', 'bulan', 'tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->get();

        // Convert to object format expected by view
        $periodes = $periodesFromDb->map(function($item) {
            try {
                // Validate periode format before parsing
                if (empty($item->periode) || !preg_match('/^\d{4}-\d{2}$/', $item->periode)) {
                    return null; // Skip invalid entries
                }

                return (object)[
                    'periode' => $item->periode,
                    'bulan' => Carbon::createFromFormat('Y-m', $item->periode)->locale('id')->translatedFormat('F'),
                    'tahun' => $item->tahun,
                ];
            } catch (\Exception $e) {
                // Log error and skip invalid entry
                \Log::warning('Invalid periode format in LaporanBulanan: ' . $item->periode);
                return null;
            }
        })->filter(); // Remove null entries

        return view('gkm.laporan-kuesioner.index', compact('laporanList', 'periodes'));
    }

    /**
     * Show the form for creating a new laporan kuesioner
     */
    public function create()
    {
        $user = Auth::user();

        // Get available templates
        $templates = TemplateLaporan::where('jenis_laporan', 'kuesioner')
            ->where('is_active', true)
            ->orderBy('nama_template')
            ->get();

        // Get all active academic periods with both UTS and UAS options
        $periodeAkademik = \App\Models\PeriodeAkademik::orderBy('tahun_ajaran', 'desc')
            ->orderBy('semester', 'desc')
            ->get();

        // Build periode options with UTS/UAS
        $periodes = [];
        foreach ($periodeAkademik as $periode) {
            $tahunAjaranFormatted = $periode->tahun_ajaran;
            // Convert tahun_ajaran format if needed (e.g., "2026" -> "25/26")
            if (strlen($tahunAjaranFormatted) === 4 && is_numeric($tahunAjaranFormatted)) {
                $tahunStart = substr($tahunAjaranFormatted, 2, 2);
                $tahunEnd = $tahunStart + 1;
                $tahunAjaranFormatted = $tahunStart . '/' . $tahunEnd;
            }

            // UTS option
            $periodes[] = [
                'value' => $periode->id . '-UTS',
                'label' => 'UTS ' . $periode->semester_label . ' ' . $tahunAjaranFormatted . ' (' . $periode->tahun_ajaran . ')',
                'periode_akademik_id' => $periode->id,
                'tipe_laporan' => 'UTS',
            ];

            // UAS option
            $periodes[] = [
                'value' => $periode->id . '-UAS',
                'label' => 'UAS ' . $periode->semester_label . ' ' . $tahunAjaranFormatted . ' (' . $periode->tahun_ajaran . ')',
                'periode_akademik_id' => $periode->id,
                'tipe_laporan' => 'UAS',
            ];
        }

        // Get current active periode
        $periodeAktif = \App\Models\PeriodeAkademik::where('is_active', true)->first();
        $currentPeriode = $periodeAktif ? $periodeAktif->id . '-UTS' : null;

        $currentMonth = (int) date('n');
        $currentYear = (int) date('Y');

        if ($periodeAktif) {
            $semester = $periodeAktif->semester;
            $tahunAjaran = $periodeAktif->tahun_ajaran;
        } else {
            if ($currentMonth <= 6) {
                $semester = 2; // Genap
                $tahunAjaran = ($currentYear - 1) . '/' . $currentYear;
            } else {
                $semester = 1; // Ganjil
                $tahunAjaran = $currentYear . '/' . ($currentYear + 1);
            }
        }

        return view('gkm.laporan-kuesioner.create', compact(
            'templates',
            'currentPeriode',
            'semester',
            'tahunAjaran',
            'periodes',
            'periodeAkademik'
        ));
    }

    /**
     * Store a newly created laporan kuesioner
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'judul_laporan' => 'required|string|max:255',
                'periode' => 'required|string|regex:/^\d{4}-\d{2}$/',
                'template_id' => 'nullable|exists:template_laporan,id',
                'konten' => 'nullable|string',
            ]);

            $user = Auth::user();
            $periode = $request->periode;

            // Parse periode
            $periodeObj = Carbon::createFromFormat('Y-m', $periode);
            $bulan = $periodeObj->month;
            $tahun = $periodeObj->year;

            // Create laporan
            $laporan = LaporanBulanan::create([
                'periode' => $periode,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'user_id' => $user->id,
                'template_id' => $request->template_id,
                'judul_laporan' => $request->judul_laporan,
                'konten' => $request->konten,
                'status' => 'completed',
            ]);

            Log::info('Laporan Kuesioner created', [
                'laporan_id' => $laporan->id,
                'user_id' => $user->id,
                'periode' => $periode,
            ]);

            return redirect()
                ->route('gkm.laporan-kuesioner.show', $laporan->id)
                ->with('success', 'Laporan kuesioner berhasil dibuat!');

        } catch (\Exception $e) {
            Log::error('Failed to create laporan kuesioner', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Gagal membuat laporan: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified laporan kuesioner
     */
    public function show($id)
    {
        $laporan = LaporanBulanan::findOrFail($id);

        // Check access
        $user = Auth::user();
        $hasAccess = $laporan->user_id == $user->id || in_array($user->role, ['GKM', 'GJM']);

        if (!$hasAccess) {
            abort(403, 'Unauthorized access');
        }

        return view('gkm.laporan-kuesioner.show', compact('laporan'));
    }

    /**
     * Remove the specified laporan kuesioner
     */
    public function destroy($id)
    {
        try {
            $laporan = LaporanBulanan::findOrFail($id);

            // Check access
            $user = Auth::user();
            $hasAccess = $laporan->user_id == $user->id || in_array($user->role, ['GKM', 'GJM']);

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Delete associated files if any
            if ($laporan->file_word && Storage::exists($laporan->file_word)) {
                Storage::delete($laporan->file_word);
            }
            if ($laporan->file_pdf && Storage::exists($laporan->file_pdf)) {
                Storage::delete($laporan->file_pdf);
            }

            $laporan->delete();

            Log::info('Laporan Kuesioner deleted', [
                'laporan_id' => $id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Laporan berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete laporan kuesioner', [
                'laporan_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus laporan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download laporan in specified format
     */
    public function download($id, $format)
    {
        try {
            $laporan = LaporanBulanan::findOrFail($id);

            // Check access
            $user = Auth::user();
            $hasAccess = $laporan->user_id == $user->id || in_array($user->role, ['GKM', 'GJM']);

            if (!$hasAccess) {
                abort(403, 'Unauthorized access');
            }

            if ($format === 'word' || $format === 'docx') {
                if (!$laporan->file_word || !Storage::exists($laporan->file_word)) {
                    return back()->with('error', 'File Word tidak ditemukan');
                }

                $filePath = storage_path('app/' . $laporan->file_word);
                $fileName = 'Laporan_Kuesioner_' . $laporan->periode . '.docx';

                return response()->download($filePath, $fileName, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ]);

            } elseif ($format === 'pdf') {
                if (!$laporan->file_pdf || !Storage::exists($laporan->file_pdf)) {
                    return back()->with('error', 'File PDF tidak ditemukan');
                }

                $filePath = storage_path('app/' . $laporan->file_pdf);
                $fileName = 'Laporan_Kuesioner_' . $laporan->periode . '.pdf';

                return response()->download($filePath, $fileName, [
                    'Content-Type' => 'application/pdf',
                ]);
            }

            return back()->with('error', 'Format tidak didukung');

        } catch (\Exception $e) {
            Log::error('Failed to download laporan kuesioner', [
                'laporan_id' => $id,
                'format' => $format,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal mendownload laporan: ' . $e->getMessage());
        }
    }

    /**
     * Create draft laporan kuesioner
     */
    public function createDraft(Request $request)
    {
        try {
            $request->validate([
                'judul_laporan' => 'required|string|max:255',
                'periode' => 'required|string',
                'tipe_laporan' => 'required|in:UTS,UAS',
                'template_id' => 'nullable|exists:template_laporan,id',
            ]);

            $user = Auth::user();
            $periode = $request->periode;
            $tipeLaporan = $request->tipe_laporan;

            // Extract periode_akademik_id from periode value (format: ID-UTS or ID-UAS)
            $periodeAkademikId = null;
            if (strpos($periode, '-') !== false) {
                list($periodeAkademikId, $extractedTipe) = explode('-', $periode);
                // Use the tipe_laporan from request for consistency
            }

            // If we have periode_akademik_id, fetch the periode data
            $periodeAkademik = null;
            if ($periodeAkademikId) {
                $periodeAkademik = \App\Models\PeriodeAkademik::find($periodeAkademikId);
                if (!$periodeAkademik) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Periode akademik tidak ditemukan',
                    ], 422);
                }
            } else {
                // Fallback: try to parse old format
                return response()->json([
                    'success' => false,
                    'message' => 'Format periode tidak valid',
                ], 422);
            }

            // Generate periode string for display
            $tahunAjaranFormatted = $periodeAkademik->tahun_ajaran;
            if (strlen($tahunAjaranFormatted) === 4 && is_numeric($tahunAjaranFormatted)) {
                $tahunStart = substr($tahunAjaranFormatted, 2, 2);
                $tahunEnd = $tahunStart + 1;
                $tahunAjaranFormatted = $tahunStart . '/' . $tahunEnd;
            }
            $periodeDisplay = $tipeLaporan . ' ' . $periodeAkademik->semester_label . ' ' . $tahunAjaranFormatted;

            // Check if draft already exists for this user + periode + tipe_laporan
            $existing = LaporanBulanan::where('periode_akademik_id', $periodeAkademikId)
                ->where('tipe_laporan', $tipeLaporan)
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if ($existing) {
                // Return existing draft
                return response()->json([
                    'success' => true,
                    'message' => 'Draft laporan sudah ada',
                    'data' => [
                        'id' => $existing->id,
                        'judul' => $existing->judul_laporan,
                        'periode' => $periodeDisplay,
                    ],
                ]);
            }

            // Create new draft
            $laporan = LaporanBulanan::create([
                'periode' => $periodeDisplay,
                'bulan' => $periodeDisplay,
                'tahun' => (int)$periodeAkademik->tahun_ajaran,
                'user_id' => $user->id,
                'periode_akademik_id' => $periodeAkademikId,
                'tipe_laporan' => $tipeLaporan,
                'template_id' => $request->template_id,
                'judul_laporan' => $request->judul_laporan,
                'status' => 'pending',
            ]);

            Log::info('Draft Laporan Kuesioner created', [
                'laporan_id' => $laporan->id,
                'user_id' => $user->id,
                'periode_akademik_id' => $periodeAkademikId,
                'tipe_laporan' => $tipeLaporan,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Draft laporan kuesioner berhasil dibuat',
                'data' => [
                    'id' => $laporan->id,
                    'judul' => $laporan->judul_laporan,
                    'periode' => $periodeDisplay,
                    'tipe_laporan' => $tipeLaporan,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', $e->validator->errors()->all()),
                'errors' => $e->validator->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to create draft laporan kuesioner', [
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
     * AI Prompt Assistant - Process user prompt + file for Laporan Kuesioner
     * Returns AI-generated summary/draft for preview
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
            ]);

            $userPrompt = $request->input('prompt');
            $promptLower = strtolower($userPrompt);

            // ================================================================
            // VALIDASI KONTEKS LAPORAN KUESIONER - BACKEND
            // ================================================================

            // === DAFTAR KEYWORD YANG DI IZINKAN (WAJIB ADA SALAH SATU) ===
            $allowedKeywords = [
                'laporan', 'report', 'kuesioner', 'questionnaire', 'survey',
                'kepuasan', 'satisfaction', 'mahasiswa', 'student',
                'buat', 'buatkan', 'bikin', 'generate', 'create', 'buatlah',
                'ubah', 'perbaiki', 'edit', 'revisi', 'update', 'ganti', 'tambah', 'hapus',
                'revisikan', 'perbaharui', 'memperbaiki', 'mengubah', 'menambah', 'menghapus',
                'struktur', 'format', 'template', 'draft', 'bagian', 'section',
                'pendahuluan', 'latar belakang', 'metodologi', 'temuan', 'analisis',
                'kualitas', 'rekomendasi', 'kesimpulan', 'ringkasan eksekutif',
                'periode', 'semester', 'tahun ajaran', 'tingkat', 'responden',
                'indeks kepuasan', 'persen kepuasan', 'hasil kuesioner', 'masukan', 'saran',
                'data kuesioner', 'statistik', 'rata-rata', 'analisis kuesioner'
            ];

            // === DAFTAR KEYWORD YANG HARUS DITOLAK ===
            $rejectedKeywords = [
                'siapa', 'siapakah', 'nama saya', 'nama kamu', 'namamu', 'namaku',
                'aku siapa', 'kamu siapa', 'perkenalkan', 'kenalan', 'halo', 'hai',
                'hello', 'hi', 'hey', 'apa kabar', 'kabar', 'gimana kabar',
                'ganteng', 'cantik', 'tampan', 'cakep', 'jelek', 'buruk rupa', 'penampilan',
                'hewan', 'binatang', 'kucing', 'anjing', 'ayam', 'bebek', 'sapi', 'kambing',
                'jerapah', 'gajah', 'singa', 'harimau', 'macan', 'ular', 'burung', 'ikan',
                'olahraga', 'sport', 'fitness', 'gym', 'danbel', 'dumbell', 'barbel',
                'barbell', 'lari', 'jogging', 'renang', 'sepak bola', 'bola', 'badminton',
                'film', 'movie', 'game', 'permainan', 'musik', 'lagu', 'song', 'drama',
                'sinetron', 'yt', 'youtube', 'tiktok', 'instagram', 'resep', 'masak',
                'memasak', 'makanan', 'minuman', 'masakan', 'berita', 'news', 'politik',
                'politic', 'pemilu', 'presiden', 'kecelakaan', 'joke', 'lelucon', 'cerita',
                'story', 'pantun', 'puisi', 'poem', 'dongeng', 'ngobrol', 'chat', 'mengobrol',
                'nge-chat', 'obrolan', 'canda', 'guyon', 'cuaca', 'weather', 'ramalan',
                'zodiac', 'horoskop', 'shio', 'tutorial', 'cara membuat', 'cara memasak',
                'DIY', 'kerajinan',
            ];

            // Pola pertanyaan singkat yang mencurigakan
            $suspiciousShortPatterns = [
                '/^(siapa|siapakah|apa|kenapa|mengapa|bagaimana|kapan|dimana|kemana)(\s+(paling|yang|itu|ini|dong|nih|sih|ya|kah))?$/i',
                '/^(halo|hai|hey|hello|hi|yo|haii|hallo|helo)$/i',
                '/^(apa kabar|kabar|gimana kabar|gmn kabar)$/i',
                '/^(ngobrol|chat|yuk ngobrol|yuk chat)$/i',
                '/^(kenalan|perkenalkan|kenal|mari kenalan)$/i',
                '/^(ganteng|cantik|tampan|cakep|jelek|buruk)$/i',
                '/^(danbel|dumbell|gym|fitness|olahraga)$/i',
                '/^(hewan|binatang|kucing|anjing)$/i',
                '/^(film|game|musik|lagu)$/i',
                '/^(resep|masak|makanan)$/i',
                '/^(berita|politik|news)$/i',
                '/^(joke|lelucon|pantun|puisi)$/i',
                '/^(cuaca|ramalan|zodiac)$/i',
            ];

            // Cek apakah ada keyword yang dilarang
            $hasRejectedKeyword = false;
            $foundRejectedKeyword = null;

            foreach ($rejectedKeywords as $keyword) {
                if (strpos($promptLower, $keyword) !== false) {
                    $hasRejectedKeyword = true;
                    $foundRejectedKeyword = $keyword;
                    break;
                }
            }

            // Cek apakah ada keyword yang diizinkan
            $hasAllowedKeyword = false;
            $foundAllowedKeyword = null;
            foreach ($allowedKeywords as $keyword) {
                if (strpos($promptLower, $keyword) !== false) {
                    $hasAllowedKeyword = true;
                    $foundAllowedKeyword = $keyword;
                    break;
                }
            }

            // DEBUG LOGGING
            Log::info('AI Prompt Kuesioner - Keyword Validation', [
                'prompt' => $userPrompt,
                'prompt_lower' => $promptLower,
                'has_rejected_keyword' => $hasRejectedKeyword,
                'found_rejected_keyword' => $foundRejectedKeyword,
                'has_allowed_keyword' => $hasAllowedKeyword,
                'found_allowed_keyword' => $foundAllowedKeyword,
                'will_reject' => $hasRejectedKeyword && !$hasAllowedKeyword,
                'periode' => $periode,
                'tipe_laporan' => $tipeLaporan
            ]);

            // Cek pola pertanyaan singkat yang mencurigakan
            $isSuspiciousShort = false;
            foreach ($suspiciousShortPatterns as $pattern) {
                if (preg_match($pattern, trim($userPrompt))) {
                    $isSuspiciousShort = true;
                    break;
                }
            }

            $hasFiles = $request->hasFile('file_referensi') && count($request->file('file_referensi')) > 0;

            // Jika ada keyword terlarang DAN tidak ada keyword yang diizinkan → TOLAK
            if ($hasRejectedKeyword && !$hasAllowedKeyword) {
                Log::warning('AI Prompt Kuesioner rejected: Rejected keyword without allowed context', [
                    'prompt' => $userPrompt,
                    'found_keyword' => $foundRejectedKeyword,
                    'has_allowed' => $hasAllowedKeyword,
                    'has_rejected' => $hasRejectedKeyword
                ]);

                return response()->json([
                    'success' => true,
                    'response' => "Maaf, permintaan Anda di luar konteks pembuatan **Laporan Kuesioner Kepuasan Mahasiswa**.\n\n" .
                                  "Saya hanya dapat membantu dengan:\n" .
                                  "✅ Pembuatan laporan kuesioner kepuasan mahasiswa\n" .
                                  "✅ Analisis data kuesioner kepuasan mahasiswa\n" .
                                  "✅ Struktur dan format laporan kuesioner\n" .
                                  "✅ Perbaikan dan revisi draft laporan\n" .
                                  "✅ Pertanyaan terkait kuesioner dan survei kepuasan\n\n" .
                                  "Silakan ajukan pertanyaan yang terkait dengan **Laporan Kuesioner**.",
                    'model_info' => 'Context Validation (Rejected)',
                    'cached' => false,
                    'rejected' => true
                ]);
            }

            // Jika pola pertanyaan singkat yang mencurigakan dan tidak ada allowed keyword → TOLAK
            if ($isSuspiciousShort && !$hasAllowedKeyword) {
                Log::info('AI Prompt Kuesioner rejected: Suspicious short pattern detected', [
                    'prompt' => $userPrompt,
                    'user_id' => Auth::id()
                ]);

                return response()->json([
                    'success' => true,
                    'response' => "Maaf, permintaan Anda di luar konteks pembuatan **Laporan Kuesioner Kepuasan Mahasiswa**.\n\n" .
                                  "Saya adalah AI Assistant khusus untuk membantu membuat Laporan Kuesioner. " .
                                  "Silakan ajukan pertanyaan terkait pembuatan laporan kuesioner kepuasan mahasiswa.",
                    'model_info' => 'Context Validation (Suspicious Short Pattern)',
                    'cached' => false,
                    'rejected' => true
                ]);
            }

            // Validasi: Pertanyaan terlalu pendek (< 10 karakter) tanpa file upload
            if (strlen(trim($userPrompt)) < 10 && !$hasFiles && !$hasAllowedKeyword) {
                Log::info('AI Prompt Kuesioner rejected: Too short without context', [
                    'prompt' => $userPrompt,
                    'length' => strlen($userPrompt),
                    'user_id' => Auth::id()
                ]);

                return response()->json([
                    'success' => true,
                    'response' => "Maaf, instruksi Anda terlalu singkat dan tidak jelas.\n\n" .
                                  "Silakan berikan instruksi yang lebih spesifik terkait pembuatan **Laporan Kuesioner Kepuasan Mahasiswa**, misalnya:\n" .
                                  "• \"Buat laporan kuesioner untuk periode ini\"\n" .
                                  "• \"Analisis data kuesioner kepuasan mahasiswa\"\n" .
                                  "• \"Tampilkan hasil kuesioner per tingkat\"",
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
            $periode = $request->input('periode', null);
            $tipeLaporan = $request->input('tipe_laporan', 'UTS');

            // Ensure periode is not null
            if (!$periode || empty(trim($periode))) {
                Log::warning('AI Prompt Kuesioner: periode not provided', [
                    'user_id' => Auth::id(),
                    'prompt' => $userPrompt
                ]);
                $periode = null; // Will be handled in getKuesioneDataFromDatabase
            }

            // Build context for caching
            $cacheContext = [
                'feature' => 'kuesioner',
                'type' => 'laporan_bulanan',
                'template_id' => $templateId,
                'periode' => $periode,
                'tipe_laporan' => $tipeLaporan,
                'has_files' => $request->hasFile('file_referensi'),
                'file_count' => $request->hasFile('file_referensi') ? count($request->file('file_referensi')) : 0,
            ];

            // ===== DISABLE CACHE FOR KUESIONER =====
            // Cache dinonaktifkan untuk Laporan Kuesioner agar selalu fresh & akurat
            Log::info('AI Prompt Kuesioner: Cache DISABLED - Always generating fresh response', [
                'periode' => $periode,
                'tipe_laporan' => $tipeLaporan,
                'prompt_length' => strlen($userPrompt)
            ]);

            // Ambil data Kuesioner Kepuasan Mahasiswa dari database jika tidak ada file upload
            $databaseContext = '';
            if (!$request->hasFile('file_referensi') || $request->file('file_referensi') === null) {
                $databaseContext = $this->getKuesioneDataFromDatabase($periode, null, $tipeLaporan);
                Log::info('Using database context for Kuesioner AI', [
                    'periode' => $periode,
                    'tipe_laporan' => $tipeLaporan,
                    'context_length' => strlen($databaseContext)
                ]);
            }

            // =========================================================================
            // AUTO-GENERATE LAPORAN LENGKAP DENGAN SERVICE
            // Jika user prompt mengandung keyword "buat laporan", "generate laporan",
            // "buatkan laporan", dll, langsung gunakan LaporanKuesioneService
            // =========================================================================
            $triggerKeywords = [
                'buat laporan',
                'buatkan laporan',
                'generate laporan',
                'bikin laporan',
                'buat laporan kuesioner',
                'buatkan laporan kuesioner',
                'generate laporan kuesioner',
                'laporan kuesioner kepuasan mahasiswa',
                'laporan kuesioner',
                'generate report',
            ];

            $userPromptLower = strtolower($userPrompt);
            $shouldAutoGenerate = false;

            foreach ($triggerKeywords as $keyword) {
                if (str_contains($userPromptLower, $keyword)) {
                    $shouldAutoGenerate = true;
                    break;
                }
            }

            if ($shouldAutoGenerate) {
                try {
                    Log::info('Auto-generate laporan kuesioner triggered', [
                        'prompt' => $userPrompt,
                        'periode' => $periode,
                        'tipe_laporan' => $tipeLaporan,
                        'template_id' => $templateId,
                    ]);

                    // Create laporan record if doesn't exist
                    $laporan = LaporanBulanan::where('periode', $periode)
                        ->where('tipe_laporan', $tipeLaporan)
                        ->where('user_id', Auth::id())
                        ->where('status', '!=', 'completed')
                        ->latest()
                        ->first();

                    if (!$laporan) {
                        // Get periode akademik from periode value (format: ID-UTS or ID-UAS)
                        $periodeAkademikId = null;
                        if (strpos($periode, '-') !== false) {
                            list($periodeAkademikId, $extractedTipe) = explode('-', $periode);
                        }

                        // Create new laporan record
                        $laporan = LaporanBulanan::create([
                            'user_id' => Auth::id(),
                            'periode' => $periode,
                            'bulan' => $periode,
                            'tahun' => now()->year,
                            'periode_akademik_id' => $periodeAkademikId,
                            'tipe_laporan' => $tipeLaporan,
                            'judul_laporan' => 'Laporan Monitoring Kuesioner Kepuasan Mahasiswa - ' . $tipeLaporan,
                            'template_id' => $templateId,
                            'status' => 'pending',
                        ]);

                        Log::info('Created new laporan kuesioner for auto-generation', [
                            'laporan_id' => $laporan->id,
                            'tipe_laporan' => $tipeLaporan,
                        ]);
                    }

                    // Call LaporanKuesioneService to generate full report
                    $user = Auth::user();
                    $prodiId = $user->prodi_id ?? null;

                    $result = $this->laporanService->generateLaporan($periode, $prodiId, $templateId, $tipeLaporan);

                    // Update laporan with generated data
                    $laporan->update([
                        'hasil_laporan' => array_merge(
                            $result['hasil_laporan'],
                            ['_aggregated_data' => $result['aggregated_data']]
                        ),
                        'total_kuesioner' => $result['aggregated_data']['total_kuesioner'] ?? 0,
                        'total_responden' => $result['aggregated_data']['total_responden'] ?? 0,
                        'index_kepuasan_rata_rata' => $result['aggregated_data']['index_kepuasan_rata_rata'] ?? null,
                        'persen_kepuasan_rata_rata' => $result['aggregated_data']['persen_kepuasan_rata_rata'] ?? null,
                        'status' => 'completed',
                    ]);

                    // Generate Word document
                    $wordGenerationService = app(\App\Services\KuesioneWordGenerationService::class);
                    $wordGenerated = $wordGenerationService->generateWordDocument($laporan);

                    $downloadUrl = route('gkm.laporan-kuesioner.download', ['id' => $laporan->id, 'format' => 'word']);

                    // Format response for user
                    $aiResponse = "✅ **LAPORAN KUESIONER BERHASIL DIBUAT (" . $tipeLaporan . ")!**\n\n";
                    $periodeDisplay = $periode ?? 'Periode Laporan';
                    $aiResponse .= "Saya telah membuat laporan monitoring kuesioner kepuasan mahasiswa lengkap untuk periode **{$periodeDisplay}** (" . $tipeLaporan . ") menggunakan data dari sistem monitoring.\n\n";

                    $aiResponse .= "## 📊 Ringkasan Data\n\n";
                    if (isset($result['aggregated_data'])) {
                        $stats = $result['aggregated_data'];
                        $aiResponse .= "- **Total Kuesioner**: {$stats['total_kuesioner']}\n";
                        $aiResponse .= "- **Total Responden**: {$stats['total_responden']}\n";
                        $aiResponse .= "- **Indeks Kepuasan Rata-rata**: {$stats['index_kepuasan_rata_rata']}\n";
                        $aiResponse .= "- **Persen Kepuasan Rata-rata**: {$stats['persen_kepuasan_rata_rata']}%\n\n";
                    }

                    $aiResponse .= "## 📄 File Laporan\n\n";
                    $aiResponse .= "Laporan Word (.docx) telah dibuat dengan lengkap meliputi:\n";
                    $aiResponse .= "- ✅ Tabel hasil kuesioner kepuasan mahasiswa\n";
                    $aiResponse .= "- ✅ Analisis tingkat kepuasan per matakuliah\n";
                    $aiResponse .= "- ✅ Hasil pemeriksaan dan analisis ketercapaian\n";
                    $aiResponse .= "- ✅ Tabel hambatan dan saran pemecahan masalah\n";
                    $aiResponse .= "- ✅ Tindak lanjut dan kesimpulan\n\n";

                    $aiResponse .= "Anda dapat mendownload file Word dan langsung menggunakannya atau melakukan penyesuaian sesuai kebutuhan.\n\n";
                    $aiResponse .= "Jika Anda membutuhkan perubahan atau penyesuaian pada laporan, silakan beritahu saya!";

                    Log::info('Auto-generated laporan kuesioner successfully', [
                        'laporan_id' => $laporan->id,
                        'file_word' => $laporan->file_word ?? null,
                    ]);

                    return response()->json([
                        'success' => true,
                        'response' => $aiResponse,
                        'model_info' => 'LaporanKuesioneService (Auto-Generate)',
                        'cached' => false,
                        'auto_generated' => true,
                        'laporan_id' => $laporan->id,
                        'download_url' => $downloadUrl,
                    ]);

                } catch (\Exception $e) {
                    Log::error('Auto-generate laporan kuesioner failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $shouldAutoGenerate = false;
                    Log::info('Falling back to regular AI chat after auto-generate failure');
                }
            }

            // Continue with regular AI chat if auto-generate is not triggered or failed
            // =========================================================================

            // Get template structure if template is selected
            $templateStructure = $this->extractTemplateStructure($templateId);

            // System context for Kuesioner reports
            $systemContext = "Anda adalah AI Assistant untuk Gugus Kendali Mutu (GKM) Institut Teknologi Del.\n\n";
            $systemContext .= "Tugas Anda: Membantu membuat LAPORAN KUESIONER berdasarkan dokumen yang diupload dan instruksi user.\n\n";

            $systemContext .= "=== BATASAN KONTEKS YANG SANGAT KETAT ===\n";
            $systemContext .= "ANDA HANYA BOLEH MEMBANTU DENGAN:\n";
            $systemContext .= "1. Pembuatan laporan kuesioner kepuasan mahasiswa\n";
            $systemContext .= "2. Analisis data kuesioner kepuasan mahasiswa\n";
            $systemContext .= "3. Format dan struktur laporan kuesioner\n";
            $systemContext .= "4. Perbaikan dan revisi draft laporan kuesioner\n";
            $systemContext .= "5. Pertanyaan terkait kuesioner kepuasan mahasiswa\n\n";

            $systemContext .= "⚠️⚠️⚠️ ANDA TIDAK BOLEH DAN HARUS MENOLAK: ⚠️⚠️⚠️\n";
            $systemContext .= "- Pertanyaan tentang SIAPA (identitas, nama orang, tokoh, dll)\n";
            $systemContext .= "- Pertanyaan tentang PENAMPILAN (ganteng, cantik, tampan, cakep)\n";
            $systemContext .= "- Pertanyaan tentang HEWAN atau BINATANG\n";
            $systemContext .= "- Pertanyaan tentang OLAHRAGA, FITNESS, GYM, DUMBBELL\n";
            $systemContext .= "- Menjawab pertanyaan umum di luar konteks laporan kuesioner\n";
            $systemContext .= "- Membantu dengan topik selain kuesioner dan survei\n";
            $systemContext .= "- Memberikan informasi atau saran di luar kepuasan mahasiswa\n";
            $systemContext .= "- Membahas topik pribadi, hiburan, atau hal-hal di luar akademik\n";
            $systemContext .= "- Small talk, chitchat, atau obrolan santai\n";
            $systemContext .= "- Pertanyaan 'apa kabar', 'hello', 'kenalan', dll\n\n";

            $systemContext .= "🚨 WAJIB: JIKA USER BERTANYA DI LUAR KONTEKS 🚨\n";
            $systemContext .= "Anda HARUS LANGSUNG menolak dengan respons PERSIS ini:\n\n";
            $systemContext .= "\"Maaf, permintaan Anda di luar konteks pembuatan Laporan Kuesioner. Saya hanya dapat membantu dengan pembuatan laporan kuesioner kepuasan mahasiswa. Silakan ajukan pertanyaan terkait laporan kuesioner.\"\n\n";
            $systemContext .= "JANGAN TAMBAHKAN penjelasan lain. JANGAN JAWAB pertanyaan user. LANGSUNG TOLAK!\n\n";

            $systemContext .= "PENTING - CONVERSATION CONTEXT:\n";
            $systemContext .= "- Ini mungkin percakapan lanjutan. Jika user meminta perubahan atau perbaikan, modifikasi konten yang sudah ada.\n";
            $systemContext .= "- Jika user mengatakan 'ubah bagian X', 'perbaiki Y', atau 'tambahkan Z', lakukan perubahan pada draft sebelumnya.\n";
            $systemContext .= "- Pertahankan konsistensi dengan respons sebelumnya kecuali diminta mengubahnya.\n";
            $systemContext .= "- Jika ini permintaan pertama, buat draft lengkap. Jika permintaan lanjutan, fokus pada perubahan yang diminta.\n";
            $systemContext .= "- SELALU PERIKSA: Apakah pertanyaan user masih dalam konteks laporan kuesioner? Jika tidak, tolak dengan sopan.\n\n";

            if ($templateStructure) {
                $systemContext .= "STRUKTUR TEMPLATE YANG HARUS DIIKUTI:\n";
                $systemContext .= $templateStructure . "\n\n";
                $systemContext .= "PENTING: Anda HARUS mengikuti struktur template di atas dengan KETAT. Gunakan markdown heading level 1 (#) untuk setiap bagian utama sesuai template.\n";
                $systemContext .= "Jangan menambah atau mengurangi bagian dari template. Isi setiap bagian dengan konten yang relevan berdasarkan dokumen yang diupload.\n\n";
            } else {
                // Default structure untuk laporan kuesioner
                $systemContext .= "PENTING: Gunakan STRUKTUR WAJIB berikut dengan markdown heading level 1 (#):\n\n";
                $systemContext .= "# RINGKASAN EKSEKUTIF\n";
                $systemContext .= "[Ringkasan singkat laporan dan temuan utama]\n\n";
                $systemContext .= "# PENDAHULUAN\n";
                $systemContext .= "[Latar belakang dan tujuan laporan kuesioner]\n\n";
                $systemContext .= "# METODOLOGI\n";
                $systemContext .= "[Metode pengumpulan dan analisis data kuesioner]\n\n";
                $systemContext .= "# I. HASIL KUESIONER\n";
                $systemContext .= "[Hasil kuesioner per tingkat]\n\n";
                $systemContext .= "# II. MASUKAN DAN SARAN\n";
                $systemContext .= "[Masukan dan saran dari responden]\n\n";
                $systemContext .= "# ANALISIS KUALITAS\n";
                $systemContext .= "[Evaluasi kualitas kuesioner berdasarkan standar]\n\n";
                $systemContext .= "# REKOMENDASI\n";
                $systemContext .= "[Saran perbaikan dan tindak lanjut]\n\n";
                $systemContext .= "# KESIMPULAN\n";
                $systemContext .= "[Kesimpulan dan ringkasan rekomendasi]\n\n";
            }

            $systemContext .= "FORMAT DATA KUESIONER:\n";
            $systemContext .= "Data kuesioner dari database sudah disiapkan dalam format tabel.\n";
            $systemContext .= "Anda HARUS menggunakan data ini dalam laporan dengan menyisipkan tabel yang sudah disiapkan.\n\n";

            $systemContext .= "Fokus pada analisis kuesioner kepuasan mahasiswa terhadap proses pembelajaran.\n";
            $systemContext .= "Gunakan Bahasa Indonesia formal dan profesional. Setiap bagian harus berisi konten yang substantif dan relevan.\n\n";

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

                            Log::info('Image processed with OCR for Kuesioner', [
                                'filename' => $fileName,
                                'ocr_text_length' => isset($ocrText) ? strlen($ocrText) : 0
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Image processing failed for Kuesioner', [
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
                                'type' => $this->categorizeKuesioneFile($fileName)
                            ];

                            Log::info('Document extracted for Kuesioner AI prompt', [
                                'filename' => $fileName,
                                'size' => strlen($fileContent),
                                'type' => $this->categorizeKuesioneFile($fileName)
                            ]);
                        } catch (\Exception $e) {
                            Log::error('File extraction failed for Kuesioner', [
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

            if (!empty($databaseContext)) {
                $currentMessage .= "===== DATA KUESIONER KEPUASAN MAHASISWA DARI DATABASE =====\n\n";
                $currentMessage .= $databaseContext . "\n\n";
                $currentMessage .= "===== END DATA DATABASE =====\n\n";
            }

            if (!empty($filesContext)) {
                $currentMessage .= "Saya telah mengupload beberapa dokumen kuesioner:\n\n";

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

            if (!empty($ocrTexts)) {
                $currentMessage .= "Teks yang diekstrak dari gambar kuesioner:\n\n";
                foreach ($ocrTexts as $ocrData) {
                    $currentMessage .= "**{$ocrData['filename']}** (OCR Method: {$ocrData['method']}, Confidence: {$ocrData['confidence']}%):\n";
                    $currentMessage .= "```\n" . $ocrData['text'] . "\n```\n\n";
                }
            }

            $currentMessage .= "Instruksi dari user: " . $userPrompt . "\n\n";

            $currentMessage .= "⚠️ PERINGATAN KERAS: Periksa terlebih dahulu apakah instruksi user di atas terkait dengan LAPORAN KUESIONER KEPUASAN MAHASISWA.\n\n";
            $currentMessage .= "Jika instruksi di atas TIDAK terkait dengan:\n";
            $currentMessage .= "- Pembuatan laporan kuesioner\n";
            $currentMessage .= "- Analisis data kuesioner\n";
            $currentMessage .= "- Format/struktur laporan kuesioner\n";
            $currentMessage .= "- Perbaikan draft laporan kuesioner\n\n";
            $currentMessage .= "Maka Anda WAJIB menolak dengan respons: \"Maaf, permintaan Anda di luar konteks pembuatan Laporan Kuesioner. Saya hanya dapat membantu dengan pembuatan laporan kuesioner kepuasan mahasiswa. Silakan ajukan pertanyaan terkait laporan kuesioner.\"\n\n";
            $currentMessage .= "JANGAN JAWAB pertanyaan tentang: siapa, kenalan, cuaca, berita, resep, musik, film, game, olahraga, hewan, fitness, atau topik pribadi lainnya!\n\n";

            if ($templateStructure) {
                $currentMessage .= "PENTING: Anda HARUS menghasilkan draft laporan kuesioner yang mengikuti STRUKTUR TEMPLATE yang telah diberikan di system context.\n\n";
                $currentMessage .= "Gunakan semua dokumen kuesioner yang saya upload sebagai sumber data dan informasi untuk mengisi setiap bagian template.\n\n";
                $currentMessage .= "Setiap bagian harus berisi minimal 2-3 paragraf dengan konten yang substantif dan relevan berdasarkan analisis kuesioner.\n";
            } else {
                $currentMessage .= "PENTING: Anda HARUS menghasilkan SEMUA 7 bagian berikut dengan konten yang substantif:\n\n";
                $currentMessage .= "1. # RINGKASAN EKSEKUTIF\n";
                $currentMessage .= "2. # PENDAHULUAN\n";
                $currentMessage .= "3. # METODOLOGI\n";
                $currentMessage .= "4. # I. HASIL KUESIONER\n";
                $currentMessage .= "5. # II. MASUKAN DAN SARAN\n";
                $currentMessage .= "6. # ANALISIS KUALITAS\n";
                $currentMessage .= "7. # REKOMENDASI\n";
                $currentMessage .= "8. # KESIMPULAN\n\n";
                $currentMessage .= "Jangan skip bagian manapun. Setiap bagian harus berisi minimal 2-3 paragraf dengan analisis yang mendalam.\n";
            }

            $currentMessage .= "Fokus pada evaluasi hasil kuesioner kepuasan mahasiswa dan berikan rekomendasi perbaikan yang konkret.\n";

            $messages[] = [
                'role' => 'user',
                'content' => $currentMessage
            ];

            $aiResult = $aiService->generateChat($messages, [
                'max_tokens' => 8192,
                'temperature' => 0.7
            ]);

            if (!$aiResult['success'] || empty($aiResult['text'])) {
                Log::error('AI returned empty response for Kuesioner', [
                    'prompt_length' => strlen($userPrompt),
                    'files_count' => count($filesContext),
                    'messages_count' => count($messages),
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

            Log::info('AI Prompt successful for Kuesioner', [
                'prompt_length' => strlen($userPrompt),
                'response_length' => strlen($aiResponse),
                'files_count' => count($filesContext),
                'messages_count' => count($messages),
                'has_template' => !empty($templateStructure),
                'provider' => $aiResult['provider'],
                'model' => $aiResult['model'],
                'cached' => false
            ]);

            return response()->json([
                'success' => true,
                'response' => $aiResponse,
                'model_info' => $aiResult['provider'] . ' (' . $aiResult['model'] . ')',
                'cached' => false,
            ]);

        } catch (\Exception $e) {
            Log::error('AI Prompt failed for Kuesioner', [
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
     * Save AI preview data before generating Word document
     * Called after user gets AI response to store it for Word generation
     */
    public function savePreview(Request $request)
    {
        try {
            $request->validate([
                'laporan_id' => 'required|exists:laporan_bulanan,id',
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
                Log::warning('Failed to parse AI sections JSON for Kuesioner', [
                    'laporan_id' => $laporanId,
                    'error' => $e->getMessage()
                ]);
            }

            $laporan = LaporanBulanan::findOrFail($laporanId);

            $laporan->update([
                'ai_preview_draft' => $aiPreviewDraft,
                'ai_sections' => $sections,
                'status' => 'preview_ready',
            ]);

            Log::info('AI preview saved for Kuesioner', [
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
            Log::error('Save AI preview failed for Kuesioner', [
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
     * Generate Word document from AI preview
     * Called from AI Assistant after user gets AI response
     */
    public function generateWordDocument(Request $request)
    {
        try {
            $request->validate([
                'laporan_id' => 'required|exists:laporan_bulanan,id',
                'ai_preview_data' => 'required|string',
            ]);

            $laporanId = $request->input('laporan_id');
            $aiPreviewData = $request->input('ai_preview_data');

            $laporan = LaporanBulanan::findOrFail($laporanId);

            $user = Auth::user();
            $hasAccess = $laporan->user_id == $user->id || in_array($user->role, ['GKM', 'GJM']);

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            Log::info('Generate Word from AI preview for Kuesioner', [
                'laporan_id' => $laporanId,
                'preview_length' => strlen($aiPreviewData),
                'template_id' => $laporan->template_id,
                'tipe_laporan' => $laporan->tipe_laporan
            ]);

            if ($laporan->template_id) {
                Log::info('Using template with placeholders', ['template_id' => $laporan->template_id]);

                $hasilAI = $this->laporanService->generateLaporanWithPlaceholders(
                    $laporan->periode,
                    null,
                    $laporan->template_id,
                    $laporan->tipe_laporan
                );
            } else {
                Log::info('No template selected, using regular generation');

                $hasilAI = $this->laporanService->generateLaporan(
                    $laporan->periode,
                    null,
                    null,
                    $laporan->tipe_laporan
                );
            }

            $hasilLaporan = $hasilAI['hasil_laporan'] ?? [];
            $aggregatedData = $hasilAI['aggregated_data'] ?? [];

            $laporan->update([
                'hasil_laporan' => $hasilLaporan,
                'aggregated_data' => $aggregatedData,
                'status' => 'completed',
                'ai_preview_draft' => $aiPreviewData,
            ]);

            Log::info('Saved AI-generated laporan data to database', [
                'laporan_id' => $laporanId,
            ]);

            if ($laporan->template_id) {
                Log::info('Generating Word from template with placeholders', [
                    'template_id' => $laporan->template_id
                ]);

                $wordGenerated = $this->generateWordFromTemplateWithPlaceholders(
                    $laporan->fresh(),
                    $hasilLaporan
                );
            } else {
                Log::info('Generating Word without template');

                $wordGenerationService = app(\App\Services\KuesioneWordGenerationService::class);
                $wordGenerated = $wordGenerationService->generateWordDocument($laporan->fresh());
            }

            if (!$wordGenerated) {
                throw new \Exception('Gagal membuat file Word document');
            }

            $laporan = $laporan->fresh();

            if (!$laporan->file_word || !file_exists(storage_path('app/' . $laporan->file_word))) {
                throw new \Exception('File Word gagal dibuat');
            }

            $filePath = storage_path('app/' . $laporan->file_word);
            $fileName = 'Laporan_Kuesioner_' . $laporan->periode . '_' . time() . '.docx';

            Log::info('Word document generated successfully from AI preview for Kuesioner', [
                'laporan_id' => $laporanId,
                'file_path' => $laporan->file_word,
            ]);

            return response()->download($filePath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);

        } catch (\Exception $e) {
            Log::error('Generate Word from AI preview failed for Kuesioner', [
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
     * Generate Word from template with placeholders
     */
    private function generateWordFromTemplateWithPlaceholders($laporan, $hasilLaporan)
    {
        $wordGenerationService = app(\App\Services\KuesioneWordGenerationService::class);
        return $wordGenerationService->generateWordDocument($laporan);
    }

    /**
     * Extract placeholder values from hasil_laporan JSON
     */
    private function extractPlaceholdersFromLaporan($hasilLaporan)
    {
        $placeholders = [];

        $mapping = [
            'PENDAHULUAN_TUJUAN' => 'PENDAHULUAN_TUJUAN',
            'PENDAHULUAN_WAKTU' => 'PENDAHULUAN_WAKTU',
            'PENDAHULUAN_RUANG_LINGKUP' => 'PENDAHULUAN_RUANG_LINGKUP',
            'HASIL_KUESIONER_TINGKAT_I' => 'HASIL_KUESIONER_TINGKAT_I',
            'MASUKAN_SARAN_TINGKAT_I' => 'MASUKAN_SARAN_TINGKAT_I',
            'HASIL_KUESIONER_TINGKAT_II' => 'HASIL_KUESIONER_TINGKAT_II',
            'MASUKAN_SARAN_TINGKAT_II' => 'MASUKAN_SARAN_TINGKAT_II',
            'HASIL_KUESIONER_TINGKAT_III' => 'HASIL_KUESIONER_TINGKAT_III',
            'MASUKAN_SARAN_TINGKAT_III' => 'MASUKAN_SARAN_TINGKAT_III',
            'HASIL_KUESIONER_TINGKAT_IV' => 'HASIL_KUESIONER_TINGKAT_IV',
            'MASUKAN_SARAN_TINGKAT_IV' => 'MASUKAN_SARAN_TINGKAT_IV',
            'KESIMPULAN' => 'KESIMPULAN',
            'SARAN_REKOMENDASI' => 'SARAN_REKOMENDASI',
        ];

        foreach ($mapping as $jsonKey => $placeholderKey) {
            if (isset($hasilLaporan[$jsonKey])) {
                $value = $hasilLaporan[$jsonKey];

                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }

                $placeholders[$placeholderKey] = (string)$value;
            } else {
                $placeholders[$placeholderKey] = '';
            }
        }

        return $placeholders;
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

            return "Template structure for kuesioner report...";
        } catch (\Exception $e) {
            Log::warning('Failed to extract template structure for Kuesioner', [
                'template_id' => $templateId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Categorize kuesioner file type
     */
    private function categorizeKuesioneFile($filename)
    {
        $filename = strtolower($filename);

        if (str_contains($filename, 'kuesioner') || str_contains($filename, 'angket')) {
            return 'Kuesioner/Angket';
        } elseif (str_contains($filename, 'hasil') || str_contains($filename, 'rekap')) {
            return 'Hasil/Rekapitulasi';
        } elseif (str_contains($filename, 'analisis') || str_contains($filename, 'analisa')) {
            return 'Analisis';
        } elseif (str_contains($filename, 'laporan') || str_contains($filename, 'report')) {
            return 'Laporan';
        } elseif (str_contains($filename, 'data') || str_contains($filename, 'survey')) {
            return 'Data Survey';
        } else {
            return 'Dokumen Kuesioner';
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
     * Get Kuesioner Kepuasan Mahasiswa data from database for AI context
     */
    private function getKuesioneDataFromDatabase($periode = null, $prodiId = null, $tipeLaporan = 'UTS')
    {
        try {
            $user = Auth::user();
            $prodiKode = $user->prodi->kode_prodi ?? 'TRPL';

            $semester = null;
            $tahunAjaran = null;
            $periodeLabel = null;

            // Handle new period format: "ID-UTS" or "ID-UAS"
            if ($periode && strpos($periode, '-') !== false) {
                $parts = explode('-', $periode);
                if (count($parts) === 2) {
                    $periodeAkademikId = (int)$parts[0];

                    // Get from database
                    $periodeAkademik = \App\Models\PeriodeAkademik::find($periodeAkademikId);
                    if ($periodeAkademik) {
                        $semester = $periodeAkademik->semester;
                        $tahunAjaran = $periodeAkademik->tahun_ajaran;
                        $semesterLabel = $periodeAkademik->semester_label;
                        $periodeLabel = "{$parts[1]} {$semesterLabel} {$tahunAjaran}";
                    }
                }
            }
            // Handle old period format: "Y-m"
            elseif ($periode && preg_match('/^\d{4}-\d{2}$/', $periode)) {
                $year  = (int) substr($periode, 0, 4);
                $month = (int) substr($periode, 5, 2);

                if ($month <= 6) {
                    $semester    = 2;
                    $tahunAjaran = ($year - 1) . '/' . $year;
                } else {
                    $semester    = 1;
                    $tahunAjaran = $year . '/' . ($year + 1);
                }
                $periodeLabel = $periode;
            }

            // If no period info yet, get from active periode
            if (!$semester || !$tahunAjaran) {
                $periodeAktif = \App\Models\PeriodeAkademik::where('is_active', true)->first();
                if ($periodeAktif) {
                    $semester    = $periodeAktif->semester;
                    $tahunAjaran = $periodeAktif->tahun_ajaran;
                    $periodeLabel = $periodeAktif->semester_label . ' ' . $tahunAjaran;
                } else {
                    $currentMonth = (int) date('n');
                    $currentYear  = (int) date('Y');
                    if ($currentMonth <= 6) {
                        $semester    = 2;
                        $tahunAjaran = ($currentYear - 1) . '/' . $currentYear;
                    } else {
                        $semester    = 1;
                        $tahunAjaran = $currentYear . '/' . ($currentYear + 1);
                    }
                    $periodeLabel = $periode ?? 'Periode Aktif';
                }
            }

            $prodiLabel = $prodiKode === 'TRPL' ? 'Teknik Rekayasa Perangkat Lunak'
                        : ($prodiKode === 'TI'   ? 'Teknologi Informasi'
                        :                          'Teknik Elektro');

            $tipeLabel = $tipeLaporan === 'UAS' ? 'Ujian Akhir Semester (UAS)' : 'Ujian Tengah Semester (UTS)';

            $context  = "=== DATA KUESIONER KEPUASAN MAHASISWA DARI MONITORING SISTEM ===\n\n";
            $context .= "Program Studi: {$prodiLabel}\n";
            $context .= "Semester: " . ($semester == 1 ? 'Ganjil' : 'Genap') . " {$tahunAjaran}\n";
            $context .= "Periode Pelaporan: {$periodeLabel}\n";
            $context .= "Jenis Laporan: {$tipeLabel}\n\n";

            $uploads = KuesioneUpload::query()
                ->where('semester', $semester)
                ->whereHas('user', function($q) use ($prodiKode) {
                    if ($prodiKode) {
                        $q->whereHas('prodi', function($pq) use ($prodiKode) {
                            $pq->where('kode_prodi', $prodiKode);
                        });
                    }
                })
                ->with('user.prodi')
                ->orderBy('tingkat', 'asc')
                ->orderBy('nama_matakuliah', 'asc')
                ->get();

            if ($uploads->isNotEmpty()) {
                $dataByTingkat = $uploads->groupBy('tingkat');

                $context .= "## HASIL KUESIONER PER TINGKAT\n\n";

                for ($tingkat = 1; $tingkat <= 4; $tingkat++) {
                    $context .= "### TINGKAT " . $this->numberToRoman($tingkat) . "\n\n";

                    $kuesioneData = $dataByTingkat->get($tingkat, collect());

                    if ($kuesioneData->isNotEmpty()) {
                        $context .= "| No | Kode Matakuliah | Nama Matakuliah | Dosen Pengampu | Indeks Kepuasan | % Kepuasan | Total Responden |\n";
                        $context .= "|----|----------------|-----------------|----------------|-----------------|-----------|------------------|\n";

                        foreach ($kuesioneData as $i => $row) {
                            $no = $i + 1;
                            $kodeMK = $row->kode_matakuliah ?? '-';
                            $namaMK = $row->nama_matakuliah ?? '-';
                            $dosen = $row->dosen_pengampu ?? '-';
                            $indexKepuasan = number_format($row->index_kepuasan ?? 0, 2);
                            $persenKepuasan = number_format($row->persen_kepuasan ?? 0, 1);
                            $totalResponden = $row->total_responden ?? 0;

                            $context .= "| {$no} | {$kodeMK} | {$namaMK} | {$dosen} | {$indexKepuasan} | {$persenKepuasan}% | {$totalResponden} |\n";
                        }

                        $context .= "\n";

                        $context .= "**Masukan dan Saran Tingkat " . $this->numberToRoman($tingkat) . ":**\n";
                        $context .= "| No | Kode Matakuliah | Nama Matakuliah | Masukan/Saran |\n";
                        $context .= "|----|----------------|-----------------|---------------|\n";

                        foreach ($kuesioneData as $i => $row) {
                            $no = $i + 1;
                            $kodeMK = $row->kode_matakuliah ?? '-';
                            $namaMK = $row->nama_matakuliah ?? '-';

                            $masukanSaran = '-';
                            if (!empty($row->hasil_analisis)) {
                                $analisis = is_string($row->hasil_analisis)
                                    ? json_decode($row->hasil_analisis, true)
                                    : $row->hasil_analisis;

                                if (isset($analisis['rekomendasi']) && is_array($analisis['rekomendasi'])) {
                                    $masukanSaran = implode('; ', array_slice($analisis['rekomendasi'], 0, 3));
                                } elseif (isset($analisis['area_perbaikan']) && is_array($analisis['area_perbaikan'])) {
                                    $masukanSaran = implode('; ', array_slice($analisis['area_perbaikan'], 0, 3));
                                } elseif (isset($analisis['ringkasan'])) {
                                    $masukanSaran = substr($analisis['ringkasan'], 0, 200) . '...';
                                }
                            }

                            if ($masukanSaran === '-' || empty(trim($masukanSaran))) {
                                if ($row->index_kepuasan >= 3.5) {
                                    $masukanSaran = 'Pertahankan kualitas pembelajaran yang sudah baik';
                                } elseif ($row->index_kepuasan >= 3.0) {
                                    $masukanSaran = 'Tingkatkan interaksi dosen-mahasiswa dan perbaiki metode pembelajaran';
                                } else {
                                    $masukanSaran = 'Perlu perbaikan signifikan dalam metode pembelajaran dan penyampaian materi';
                                }
                            }

                            $context .= "| {$no} | {$kodeMK} | {$namaMK} | {$masukanSaran} |\n";
                        }

                        $context .= "\n";

                        $avgIndex = $kuesioneData->avg('index_kepuasan');
                        $avgPersen = $kuesioneData->avg('persen_kepuasan');
                        $totalMK = $kuesioneData->count();
                        $totalResponden = $kuesioneData->sum('total_responden');

                        $context .= "**Statistik Tingkat " . $this->numberToRoman($tingkat) . ":**\n";
                        $context .= "- Total Matakuliah: {$totalMK}\n";
                        $context .= "- Rata-rata Indeks Kepuasan: " . number_format($avgIndex, 2) . "\n";
                        $context .= "- Rata-rata % Kepuasan: " . number_format($avgPersen, 1) . "%\n";
                        $context .= "- Total Responden: {$totalResponden}\n\n";
                    } else {
                        $context .= "Tidak ada data kuesioner untuk Tingkat " . $this->numberToRoman($tingkat) . ".\n\n";
                    }
                }

                $totalMK = $uploads->count();
                $totalResponden = $uploads->sum('total_responden');
                $avgIndex = $uploads->avg('index_kepuasan');
                $avgPersen = $uploads->avg('persen_kepuasan');

                $context .= "### RINGKASAN STATISTIK KESELURUHAN\n\n";
                $context .= "- Total Matakuliah: {$totalMK}\n";
                $context .= "- Total Responden: {$totalResponden}\n";
                $context .= "- Rata-rata Indeks Kepuasan: " . number_format($avgIndex, 2) . "\n";
                $context .= "- Rata-rata % Kepuasan: " . number_format($avgPersen, 1) . "%\n\n";

            } else {
                $context .= "⚠️ Data kuesioner kepuasan mahasiswa untuk periode ini belum tersedia di sistem.\n";
                $context .= "Silakan gunakan data umum atau upload file referensi untuk analisis yang lebih mendalam.\n\n";
            }

            $context .= "=== END DATA KUESIONER ===\n";

            return $context;

        } catch (\Exception $e) {
            Log::error('Failed to get kuesioner data from database', [
                'error'  => $e->getMessage(),
                'periode' => $periode,
                'trace' => $e->getTraceAsString()
            ]);

            return "Data kuesioner kepuasan mahasiswa dari database tidak dapat diambil. Silakan upload file referensi untuk analisis.\n";
        }
    }

    /**
     * Convert number to Roman numeral
     */
    private function numberToRoman($num)
    {
        $romanMap = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V'
        ];

        return $romanMap[$num] ?? (string)$num;
    }

    /**
     * Generate Word document from laporan data
     */
    private function generateWordFromLaporan($laporan)
    {
        try {
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection();

            $periodeObj = Carbon::createFromFormat('Y-m', $laporan->periode);
            $bulanTahun = $periodeObj->locale('id')->translatedFormat('F Y');

            $section->addTitle("LAPORAN MONITORING KUESIONER KEPUASAN MAHASISWA", 1);
            $section->addTitle("Periode: {$bulanTahun}", 2);
            $section->addTextBreak(2);

            $hasilLaporan = $laporan->hasil_laporan ?? [];

            if (isset($hasilLaporan['ringkasan_eksekutif'])) {
                $section->addTitle("RINGKASAN EKSEKUTIF", 1);
                $ringkasan = $hasilLaporan['ringkasan_eksekutif'];

                if (isset($ringkasan['overview'])) {
                    $section->addText($ringkasan['overview']);
                    $section->addTextBreak();
                }

                if (isset($ringkasan['highlight_positif']) && !empty($ringkasan['highlight_positif'])) {
                    $section->addText("Highlight Positif:", ['bold' => true]);
                    foreach ($ringkasan['highlight_positif'] as $point) {
                        $section->addListItem($point);
                    }
                    $section->addTextBreak();
                }

                if (isset($ringkasan['highlight_negatif']) && !empty($ringkasan['highlight_negatif'])) {
                    $section->addText("Area Perhatian:", ['bold' => true]);
                    foreach ($ringkasan['highlight_negatif'] as $point) {
                        $section->addListItem($point);
                    }
                    $section->addTextBreak();
                }
            }

            if (isset($hasilLaporan['statistik_utama'])) {
                $section->addTitle("STATISTIK UTAMA", 1);
                $stats = $hasilLaporan['statistik_utama'];

                $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
                $table->addRow();
                $table->addCell(5000)->addText('Metrik', ['bold' => true]);
                $table->addCell(3000)->addText('Nilai', ['bold' => true]);

                if (isset($stats['total_kuesioner'])) {
                    $table->addRow();
                    $table->addCell(5000)->addText('Total Kuesioner');
                    $table->addCell(3000)->addText((string)$stats['total_kuesioner']);
                }

                if (isset($stats['total_responden'])) {
                    $table->addRow();
                    $table->addCell(5000)->addText('Total Responden');
                    $table->addCell(3000)->addText((string)$stats['total_responden']);
                }

                if (isset($stats['index_kepuasan_rata_rata'])) {
                    $table->addRow();
                    $table->addCell(5000)->addText('Indeks Kepuasan Rata-rata');
                    $table->addCell(3000)->addText((string)$stats['index_kepuasan_rata_rata']);
                }

                if (isset($stats['persen_kepuasan_rata_rata'])) {
                    $table->addRow();
                    $table->addCell(5000)->addText('Persen Kepuasan Rata-rata');
                    $table->addCell(3000)->addText($stats['persen_kepuasan_rata_rata'] . '%');
                }

                $section->addTextBreak(2);
            }

            if (isset($hasilLaporan['insight_utama']) && !empty($hasilLaporan['insight_utama'])) {
                $section->addTitle("INSIGHT UTAMA", 1);
                foreach ($hasilLaporan['insight_utama'] as $idx => $insight) {
                    $section->addListItem($insight, 0, null, null, null);
                }
                $section->addTextBreak(2);
            }

            if (isset($hasilLaporan['rekomendasi']) && !empty($hasilLaporan['rekomendasi'])) {
                $section->addTitle("REKOMENDASI", 1);

                foreach ($hasilLaporan['rekomendasi'] as $idx => $rek) {
                    $rekText = is_array($rek) ? ($rek['rekomendasi'] ?? '') : $rek;
                    $prioritas = is_array($rek) ? ($rek['prioritas'] ?? 'Medium') : 'Medium';
                    $timeline = is_array($rek) ? ($rek['timeline'] ?? '') : '';

                    $listText = ($idx + 1) . ". " . $rekText;
                    if ($prioritas) {
                        $listText .= " [Prioritas: {$prioritas}]";
                    }
                    if ($timeline) {
                        $listText .= " (Timeline: {$timeline})";
                    }

                    $section->addText($listText);
                }
                $section->addTextBreak(2);
            }

            $fileName = 'laporan_kuesioner_' . $laporan->periode . '_' . time() . '.docx';
            $filePath = 'laporan_kuesioner/' . $fileName;
            $fullPath = storage_path('app/' . $filePath);

            $directory = dirname($fullPath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($fullPath);

            $laporan->file_word = $filePath;
            $laporan->save();

            Log::info('Word document generated from laporan', [
                'laporan_id' => $laporan->id,
                'file_path' => $filePath,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to generate Word document from laporan', [
                'laporan_id' => $laporan->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    // ============================================================
    // TEMPLATE MANAGEMENT METHODS
    // ============================================================

    public function templateIndex()
    {
        $templates = TemplateLaporan::where('jenis_laporan', 'kuesioner')
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('gkm.laporan-kuesioner.template.index', compact('templates'));
    }

    public function templateUpload()
    {
        return view('gkm.laporan-kuesioner.template.upload');
    }

    public function templateStore(Request $request)
    {
        try {
            $request->validate([
                'nama_template' => 'required|string|max:255',
                'deskripsi' => 'nullable|string',
                'file_template' => 'required|file|mimes:docx,doc|max:10240',
            ]);

            $file = $request->file('file_template');
            $originalFileName = $file->getClientOriginalName();
            $fileName = time() . '_' . $originalFileName;
            $filePath = $file->storeAs('templates/kuesioner', $fileName, 'local');
            $fileSize = $file->getSize();
            $fileExtension = $file->getClientOriginalExtension();

            $template = TemplateLaporan::create([
                'nama_template' => $request->nama_template,
                'nama_file' => $originalFileName,
                'jenis_file' => $fileExtension,
                'file_path' => $filePath,
                'ukuran_file' => $fileSize,
                'uploaded_by' => Auth::id(),
                'jenis_template' => 'laporan_bulanan',
                'jenis_laporan' => 'kuesioner',
                'is_active' => true,
                'is_indexed' => false,
            ]);

            Log::info('Template Kuesioner uploaded', [
                'template_id' => $template->id,
                'nama' => $template->nama_template,
                'nama_file' => $originalFileName,
            ]);

            try {
                $this->laporanService->processTemplateToVectorDB($template->id);
                $message = 'Template berhasil diupload!';
            } catch (\Exception $e) {
                Log::warning('Template uploaded but indexing failed', [
                    'template_id' => $template->id,
                    'error' => $e->getMessage(),
                ]);
                $message = 'Template berhasil diupload!';
            }

            return redirect()
                ->route('gkm.laporan-kuesioner.template.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to upload template kuesioner', [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Gagal upload template: ' . $e->getMessage());
        }
    }

    public function templateDownload($id)
    {
        try {
            $template = TemplateLaporan::findOrFail($id);

            if (!Storage::exists($template->file_path)) {
                return back()->with('error', 'File template tidak ditemukan');
            }

            $filePath = storage_path('app/' . $template->file_path);
            $fileName = $template->nama_template . '.docx';

            return response()->download($filePath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to download template', [
                'template_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal mendownload template: ' . $e->getMessage());
        }
    }

    public function templateToggle($id)
    {
        try {
            $template = TemplateLaporan::findOrFail($id);
            $template->is_active = !$template->is_active;
            $template->save();

            $status = $template->is_active ? 'diaktifkan' : 'dinonaktifkan';

            Log::info('Template status toggled', [
                'template_id' => $id,
                'nama' => $template->nama_template,
                'is_active' => $template->is_active,
            ]);

            return redirect()
                ->route('gkm.laporan-kuesioner.template.index')
                ->with('success', "Template berhasil {$status}!");

        } catch (\Exception $e) {
            Log::error('Failed to toggle template', [
                'template_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('gkm.laporan-kuesioner.template.index')
                ->with('error', 'Gagal mengubah status template: ' . $e->getMessage());
        }
    }

    public function templateReindex($id)
    {
        try {
            $template = TemplateLaporan::findOrFail($id);

            $result = $this->laporanService->processTemplateToVectorDB($template->id);

            Log::info('Template reindexed', [
                'template_id' => $id,
                'nama' => $template->nama_template,
                'result' => $result,
            ]);

            return redirect()
                ->route('gkm.laporan-kuesioner.template.index')
                ->with('success', 'Template berhasil direindex ke vector database!');

        } catch (\Exception $e) {
            Log::error('Failed to reindex template', [
                'template_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('gkm.laporan-kuesioner.template.index')
                ->with('error', 'Gagal reindex template: ' . $e->getMessage());
        }
    }

    public function templateDestroy($id)
    {
        try {
            $template = TemplateLaporan::findOrFail($id);

            if (Storage::exists($template->file_path)) {
                Storage::delete($template->file_path);
            }

            $template->delete();

            Log::info('Template deleted', [
                'template_id' => $id,
                'nama' => $template->nama_template,
            ]);

            return redirect()
                ->route('gkm.laporan-kuesioner.template.index')
                ->with('success', 'Template berhasil dihapus!');

        } catch (\Exception $e) {
            Log::error('Failed to delete template', [
                'template_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('gkm.laporan-kuesioner.template.index')
                ->with('error', 'Gagal menghapus template: ' . $e->getMessage());
        }
    }
}
