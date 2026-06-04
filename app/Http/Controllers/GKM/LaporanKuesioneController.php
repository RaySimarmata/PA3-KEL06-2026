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
            return (object)[
                'periode' => $item->periode,
                'bulan' => Carbon::createFromFormat('Y-m', $item->periode)->locale('id')->translatedFormat('F'),
                'tahun' => $item->tahun,
            ];
        });

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

        // Get current periode
        $periodeAktif = \App\Models\PeriodeAkademik::where('is_active', true)->first();

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

        $currentPeriode = $currentYear . '-' . str_pad($currentMonth, 2, '0', STR_PAD_LEFT);

        // Generate periode options (January of current year up to current month)
        $periodes = [];
        $startMonth = 1; // January
        $endMonth = $currentMonth; // Current month

        for ($month = $endMonth; $month >= $startMonth; $month--) {
            $date = Carbon::create($currentYear, $month, 1);
            $periodes[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->locale('id')->translatedFormat('F Y'),
                'is_current' => $date->format('Y-m') === $currentPeriode
            ];
        }

        return view('gkm.laporan-kuesioner.create', compact(
            'templates',
            'currentPeriode',
            'semester',
            'tahunAjaran',
            'periodes'
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
                'periode' => 'required|string|regex:/^\d{4}-\d{2}$/',
                'template_id' => 'nullable|exists:template_laporan,id',
            ]);

            $user = Auth::user();
            $periode = $request->periode;

            // Parse periode
            $periodeObj = Carbon::createFromFormat('Y-m', $periode);
            $bulan = $periodeObj->locale('id')->translatedFormat('F'); // Month name in Indonesian
            $tahun = $periodeObj->year;

            // Check if draft already exists for this user + periode
            $existing = LaporanBulanan::where('periode', $periode)
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
                        'periode' => $periode,
                    ],
                ]);
            }

            // Create new draft
            $laporan = LaporanBulanan::create([
                'periode' => $periode,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'user_id' => $user->id,
                'template_id' => $request->template_id,
                'judul_laporan' => $request->judul_laporan,
                'status' => 'pending',
            ]);

            Log::info('Draft Laporan Kuesioner created', [
                'laporan_id' => $laporan->id,
                'user_id' => $user->id,
                'periode' => $periode,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Draft laporan kuesioner berhasil dibuat',
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

            $aiService = app(\App\Services\UnifiedAIService::class);
            $textExtraction = app(\App\Services\TextExtractionService::class);
            $ocrService = app(\App\Services\OCRService::class);

            $userPrompt = $request->input('prompt');
            $conversationHistory = $request->input('conversation_history', []);
            $templateId = $request->input('template_id');
            $periode = $request->input('periode');

            // Build context for caching
            $cacheContext = [
                'feature' => 'kuesioner', // For evaluation tracking
                'type' => 'laporan_bulanan',
                'template_id' => $templateId,
                'periode' => $periode,
                'has_files' => $request->hasFile('file_referensi'),
                'file_count' => $request->hasFile('file_referensi') ? count($request->file('file_referensi')) : 0,
            ];

            // ===== DISABLE CACHE FOR KUESIONER =====
            // Cache dinonaktifkan untuk Laporan Kuesioner agar selalu fresh & akurat
            Log::info('AI Prompt Kuesioner: Cache DISABLED - Always generating fresh response', [
                'periode' => $periode,
                'prompt_length' => strlen($userPrompt)
            ]);

            // Ambil data Kuesioner Kepuasan Mahasiswa dari database jika tidak ada file upload
            $databaseContext = '';
            if (!$request->hasFile('file_referensi') || $request->file('file_referensi') === null) {
                $databaseContext = $this->getKuesioneDataFromDatabase($periode);
                Log::info('Using database context for Kuesioner AI', [
                    'periode' => $periode,
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
                        'template_id' => $templateId,
                    ]);

                    // Create laporan record if doesn't exist
                    $laporan = LaporanBulanan::where('periode', $periode)
                        ->where('user_id', Auth::id())
                        ->where('status', '!=', 'completed')
                        ->latest()
                        ->first();

                    if (!$laporan) {
                        // Create new laporan record
                        $periodeObj = Carbon::createFromFormat('Y-m', $periode);

                        $laporan = LaporanBulanan::create([
                            'user_id' => Auth::id(),
                            'periode' => $periode,
                            'bulan' => $periodeObj->month,
                            'tahun' => $periodeObj->year,
                            'judul_laporan' => 'Laporan Monitoring Kuesioner Kepuasan Mahasiswa ' . $periodeObj->format('F Y'),
                            'template_id' => $templateId,
                            'status' => 'pending',
                        ]);

                        Log::info('Created new laporan kuesioner for auto-generation', [
                            'laporan_id' => $laporan->id,
                        ]);
                    }

                    // Call LaporanKuesioneService to generate full report
                    // Get user's prodi_id for filtering
                    $user = Auth::user();
                    $prodiId = $user->prodi_id ?? null;

                    $result = $this->laporanService->generateLaporan($periode, $prodiId, $templateId);

                    // Update laporan with generated data (including aggregated_data for Word generation)
                    $laporan->update([
                        'hasil_laporan' => array_merge(
                            $result['hasil_laporan'],
                            ['_aggregated_data' => $result['aggregated_data']] // Store aggregated data with underscore prefix
                        ),
                        'total_kuesioner' => $result['aggregated_data']['total_kuesioner'] ?? 0,
                        'total_responden' => $result['aggregated_data']['total_responden'] ?? 0,
                        'index_kepuasan_rata_rata' => $result['aggregated_data']['index_kepuasan_rata_rata'] ?? null,
                        'persen_kepuasan_rata_rata' => $result['aggregated_data']['persen_kepuasan_rata_rata'] ?? null,
                        'status' => 'completed',
                    ]);

                    // Generate Word document dari hasil laporan menggunakan service
                    $wordGenerationService = app(\App\Services\KuesioneWordGenerationService::class);
                    $wordGenerated = $wordGenerationService->generateWordDocument($laporan);

                    // Get download URL
                    $downloadUrl = route('gkm.laporan-kuesioner.download', ['id' => $laporan->id, 'format' => 'word']);

                    // Format response for user
                    $aiResponse = "✅ **LAPORAN KUESIONER BERHASIL DIBUAT!**\n\n";
                    $aiResponse .= "Saya telah membuat laporan monitoring kuesioner kepuasan mahasiswa lengkap untuk periode **{$periode}** menggunakan data dari sistem monitoring.\n\n";

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

                    $aiResponse .= "📥 **[Download Laporan Word]({$downloadUrl})**\n\n";
                    $aiResponse .= "Anda dapat mendownload file Word dan langsung menggunakannya atau melakukan penyesuaian sesuai kebutuhan.\n\n";
                    $aiResponse .= "Jika Anda membutuhkan perubahan atau penyesuaian pada laporan, silakan beritahu saya!";

                    // ===== CACHE DISABLED FOR KUESIONER =====
                    // Cache dinonaktifkan untuk selalu generate fresh & akurat
                    Log::info('Cache save SKIPPED for Kuesioner - Always fresh response');

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

                    // Fallback to regular AI chat if auto-generate fails
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
            $systemContext .= "PENTING - CONVERSATION CONTEXT:\n";
            $systemContext .= "- Ini mungkin percakapan lanjutan. Jika user meminta perubahan atau perbaikan, modifikasi konten yang sudah ada.\n";
            $systemContext .= "- Jika user mengatakan 'ubah bagian X', 'perbaiki Y', atau 'tambahkan Z', lakukan perubahan pada draft sebelumnya.\n";
            $systemContext .= "- Pertahankan konsistensi dengan respons sebelumnya kecuali diminta mengubahnya.\n";
            $systemContext .= "- Jika ini permintaan pertama, buat draft lengkap. Jika permintaan lanjutan, fokus pada perubahan yang diminta.\n\n";

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
                $systemContext .= "[Gunakan format berikut untuk hasil kuesioner per tingkat:]\n\n";
                $systemContext .= "## I. Tingkat I\n";
                $systemContext .= "{{HASIL_KUESIONER_TINGKAT_I}}\n";
                $systemContext .= "{{MASUKAN_SARAN_TINGKAT_I}}\n\n";
                $systemContext .= "## II. Tingkat II\n";
                $systemContext .= "{{HASIL_KUESIONER_TINGKAT_II}}\n";
                $systemContext .= "{{MASUKAN_SARAN_TINGKAT_II}}\n\n";
                $systemContext .= "## III. Tingkat III\n";
                $systemContext .= "{{HASIL_KUESIONER_TINGKAT_III}}\n";
                $systemContext .= "{{MASUKAN_SARAN_TINGKAT_III}}\n\n";
                $systemContext .= "## IV. Tingkat IV\n";
                $systemContext .= "{{HASIL_KUESIONER_TINGKAT_IV}}\n";
                $systemContext .= "{{MASUKAN_SARAN_TINGKAT_IV}}\n\n";
                $systemContext .= "# ANALISIS KUALITAS\n";
                $systemContext .= "[Evaluasi kualitas kuesioner berdasarkan standar]\n\n";
                $systemContext .= "# REKOMENDASI\n";
                $systemContext .= "[Saran perbaikan dan tindak lanjut]\n\n";
                $systemContext .= "# KESIMPULAN\n";
                $systemContext .= "[Kesimpulan dan ringkasan rekomendasi]\n\n";
            }

            $systemContext .= "FORMAT DATA KUESIONER:\n";
            $systemContext .= "Data kuesioner dari database sudah disiapkan dalam format placeholder seperti {{HASIL_KUESIONER_TINGKAT_I}}, {{MASUKAN_SARAN_TINGKAT_I}}, dll.\n";
            $systemContext .= "Anda HARUS menggunakan data ini dalam laporan dengan menyisipkan tabel yang sudah disiapkan.\n";
            $systemContext .= "Placeholder akan otomatis diganti dengan tabel data kuesioner yang sebenarnya.\n\n";

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

                    // Check if it's an image
                    if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        // Process image with OCR
                        try {
                            $imagePath = $file->store('temp_uploads', 'local');
                            $fullImagePath = storage_path('app/' . $imagePath);

                            // Extract text using OCR
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

                            // Clean up temp file
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
                        // Process document file
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
                            // Clean up temp file
                            if (file_exists($fullPath)) {
                                @unlink($fullPath);
                            }
                        }
                    }
                }
            }

            // Build conversation messages for Chat Completions API
            $messages = [];

            // Add system context as first message
            $messages[] = [
                'role' => 'system',
                'content' => $systemContext
            ];

            // If there's conversation history, include it (for multi-turn conversation)
            if (!empty($conversationHistory)) {
                foreach ($conversationHistory as $msg) {
                    $role = $msg['role'] ?? 'user';
                    $content = $msg['content'] ?? '';

                    // Skip empty messages
                    if (empty($content)) continue;

                    // Normalize role
                    if ($role === 'assistant' || $role === 'ai') {
                        $role = 'assistant';
                    }

                    $messages[] = [
                        'role' => $role,
                        'content' => $content
                    ];
                }
            }

            // Add current user message
            $currentMessage = '';

            // Add periode context if provided
            if (!empty($periode)) {
                $currentMessage .= "PERIODE LAPORAN: {$periode}\n\n";
            }

            // Add database context (Kuesioner Kepuasan Mahasiswa data) if no file upload
            if (!empty($databaseContext)) {
                $currentMessage .= "===== DATA KUESIONER KEPUASAN MAHASISWA DARI DATABASE =====\n\n";
                $currentMessage .= $databaseContext . "\n\n";
                $currentMessage .= "===== END DATA DATABASE =====\n\n";
            }

            // Process uploaded documents
            if (!empty($filesContext)) {
                $currentMessage .= "Saya telah mengupload beberapa dokumen kuesioner:\n\n";

                foreach ($filesContext as $fileData) {
                    // Truncate very long content
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
                $currentMessage .= "Teks yang diekstrak dari gambar kuesioner:\n\n";
                foreach ($ocrTexts as $ocrData) {
                    $currentMessage .= "**{$ocrData['filename']}** (OCR Method: {$ocrData['method']}, Confidence: {$ocrData['confidence']}%):\n";
                    $currentMessage .= "```\n" . $ocrData['text'] . "\n```\n\n";
                }
            }

            $currentMessage .= "Instruksi dari user: " . $userPrompt . "\n\n";

            if ($templateStructure) {
                $currentMessage .= "PENTING: Anda HARUS menghasilkan draft laporan kuesioner yang mengikuti STRUKTUR TEMPLATE yang telah diberikan di system context.\n\n";
                $currentMessage .= "Gunakan semua dokumen kuesioner yang saya upload sebagai sumber data dan informasi untuk mengisi setiap bagian template.\n\n";
                $currentMessage .= "Setiap bagian harus berisi minimal 2-3 paragraf dengan konten yang substantif dan relevan berdasarkan analisis kuesioner.\n";
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

            $currentMessage .= "Fokus pada evaluasi hasil kuesioner kepuasan mahasiswa dan berikan rekomendasi perbaikan yang konkret.\n";

            $messages[] = [
                'role' => 'user',
                'content' => $currentMessage
            ];

            // Call AI service using Chat Completions API with conversation history
            $aiResult = $aiService->generateChat($messages, [
                'max_tokens' => 8192,
                'temperature' => 0.7
            ]);

            if (!$aiResult['success'] || empty($aiResult['text'])) {
                Log::error('AI returned empty response for Kuesioner', [
                    'prompt_length' => strlen($userPrompt),
                    'files_count' => count($filesContext),
                    'images_count' => count($imageContents),
                    'messages_count' => count($messages),
                    'conversation_turns' => count(array_filter($messages, fn($m) => ($m['role'] ?? '') !== 'system')),
                    'error' => $aiResult['error'] ?? 'Unknown error',
                    'provider' => $aiResult['provider'] ?? 'unknown'
                ]);

                // More specific error messages
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

            // ===== CACHE DISABLED FOR KUESIONER =====
            // Cache save dinonaktifkan untuk Laporan Kuesioner agar selalu fresh
            Log::info('Cache save SKIPPED for Kuesioner - Always generating fresh response', [
                'provider' => $aiResult['provider'],
                'model' => $aiResult['model']
            ]);

            Log::info('AI Prompt successful for Kuesioner', [
                'prompt_length' => strlen($userPrompt),
                'response_length' => strlen($aiResponse),
                'files_count' => count($filesContext),
                'images_count' => count($imageContents),
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
                'ai_sections' => 'nullable|string', // JSON string
            ]);

            $laporanId = $request->input('laporan_id');
            $aiPreviewDraft = $request->input('ai_preview_draft');
            $aiSectionsJson = $request->input('ai_sections', '[]');

            // Parse sections from JSON
            $sections = [];
            try {
                $sectionsArray = json_decode($aiSectionsJson, true);
                if (is_array($sectionsArray)) {
                    // Convert sections array to associative array
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

            // Find laporan
            $laporan = LaporanBulanan::findOrFail($laporanId);

            // Save preview data
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
     * Uses template with placeholders if template is selected
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

            // Check access
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
                'template_id' => $laporan->template_id
            ]);

            // Step 1: Generate AI content with placeholder structure
            // Use generateLaporanWithPlaceholders if template is selected
            if ($laporan->template_id) {
                Log::info('Using template with placeholders', ['template_id' => $laporan->template_id]);

                $hasilAI = $this->laporanService->generateLaporanWithPlaceholders(
                    $laporan->periode,
                    null,
                    $laporan->template_id
                );
            } else {
                // Fallback to regular generation without placeholder structure
                Log::info('No template selected, using regular generation');

                $hasilAI = $this->laporanService->generateLaporan(
                    $laporan->periode,
                    null,
                    null
                );
            }

            // Parse hasil_laporan from AI response
            $hasilLaporan = $hasilAI['hasil_laporan'] ?? [];
            $aggregatedData = $hasilAI['aggregated_data'] ?? [];

            // Save to database
            $laporan->update([
                'hasil_laporan' => $hasilLaporan,
                'aggregated_data' => $aggregatedData,
                'status' => 'completed',
                'ai_preview_draft' => $aiPreviewData,
            ]);

            Log::info('Saved AI-generated laporan data to database', [
                'laporan_id' => $laporanId,
            ]);

            // Step 2: Generate Word document
            if ($laporan->template_id) {
                // Use template-based generation (fill placeholders)
                Log::info('Generating Word from template with placeholders', [
                    'template_id' => $laporan->template_id
                ]);

                $wordGenerated = $this->generateWordFromTemplateWithPlaceholders(
                    $laporan->fresh(),
                    $hasilLaporan
                );
            } else {
                // Use simple Word generation
                Log::info('Generating Word without template');

                $wordGenerationService = app(\App\Services\KuesioneWordGenerationService::class);
                $wordGenerated = $wordGenerationService->generateWordDocument($laporan->fresh());
            }

            if (!$wordGenerated) {
                throw new \Exception('Gagal membuat file Word document');
            }

            // Refresh laporan to get updated file_word path
            $laporan = $laporan->fresh();

            // Check if file exists
            if (!$laporan->file_word || !file_exists(storage_path('app/' . $laporan->file_word))) {
                throw new \Exception('File Word gagal dibuat');
            }

            $filePath = storage_path('app/' . $laporan->file_word);
            $fileName = 'Laporan_Kuesioner_' . $laporan->periode . '_' . time() . '.docx';

            Log::info('Word document generated successfully from AI preview for Kuesioner', [
                'laporan_id' => $laporanId,
                'file_path' => $laporan->file_word,
            ]);

            // Return the file as download
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
        // Use the new Word Generation Service which handles templates properly
        $wordGenerationService = app(\App\Services\KuesioneWordGenerationService::class);
        return $wordGenerationService->generateWordDocument($laporan);
    }

    /**
     * Extract placeholder values from hasil_laporan JSON
     * Maps JSON keys to template placeholder names
     */
    private function extractPlaceholdersFromLaporan($hasilLaporan)
    {
        $placeholders = [];

        // Map hasil_laporan JSON keys to template placeholders
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

                // Handle different data types
                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }

                $placeholders[$placeholderKey] = (string)$value;
            } else {
                // Provide empty value for missing placeholders
                $placeholders[$placeholderKey] = '';
            }
        }

        return $placeholders;
    }

    /**
     * Extract template structure (placeholder - implement based on your template system)
     */
    private function extractTemplateStructure($templateId)
    {
        if (!$templateId) return null;

        try {
            $template = TemplateLaporan::find($templateId);
            if (!$template) return null;

            // This is a placeholder - implement based on your template structure
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
        // Normalize title to key format
        $key = strtolower(trim($title));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);
        $key = trim($key, '_');

        return $key;
    }

    /**
     * Get Kuesioner Kepuasan Mahasiswa data from database for AI context
     *
     * @param string|null $periode Format: YYYY-MM (e.g., "2026-06")
     * @return string Formatted context string for AI
     */
    private function getKuesioneDataFromDatabase($periode = null)
    {
        try {
            $user = Auth::user();
            $prodiKode = $user->prodi->kode_prodi ?? 'TRPL';

            // Parse periode to get semester and tahun ajaran
            if ($periode) {
                $year  = (int) substr($periode, 0, 4);
                $month = (int) substr($periode, 5, 2);

                if ($month <= 6) {
                    $semester    = 2; // Genap
                    $tahunAjaran = ($year - 1) . '/' . $year;
                } else {
                    $semester    = 1; // Ganjil
                    $tahunAjaran = $year . '/' . ($year + 1);
                }
            } else {
                $periodeAktif = \App\Models\PeriodeAkademik::where('is_active', true)->first();
                if ($periodeAktif) {
                    $semester    = $periodeAktif->semester;
                    $tahunAjaran = $periodeAktif->tahun_ajaran;
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
                }
            }

            $prodiLabel = $prodiKode === 'TRPL' ? 'Teknik Rekayasa Perangkat Lunak'
                        : ($prodiKode === 'TI'   ? 'Teknologi Informasi'
                        :                          'Teknik Elektro');

            $context  = "=== DATA KUESIONER KEPUASAN MAHASISWA DARI MONITORING SISTEM ===\n\n";
            $context .= "Program Studi: {$prodiLabel}\n";
            $context .= "Semester: " . ($semester == 1 ? 'Ganjil' : 'Genap') . " {$tahunAjaran}\n";
            $context .= "Periode Pelaporan: {$periode}\n\n";

            // ------------------------------------------------------------------
            // Fetch data from kuesioner_uploads table
            // Filter by semester and user's prodi menggunakan user relationship
            // ------------------------------------------------------------------
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
                // Group data by tingkat
                $dataByTingkat = $uploads->groupBy('tingkat');

                $context .= "## I. HASIL KUESIONER\n\n";
                $context .= "FORMAT DATA: Data ini akan digunakan untuk mengisi placeholder di template laporan\n\n";

                // Process each tingkat (I, II, III, IV)
                for ($tingkat = 1; $tingkat <= 4; $tingkat++) {
                    $context .= "### TINGKAT " . $this->numberToRoman($tingkat) . "\n\n";

                    $kuesioneData = $dataByTingkat->get($tingkat, collect());

                    if ($kuesioneData->isNotEmpty()) {
                        // Tabel Hasil Kuesioner
                        $context .= "**{{HASIL_KUESIONER_TINGKAT_" . $this->numberToRoman($tingkat) . "}}**\n\n";
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

                        // Tabel Masukan/Saran
                        $context .= "**{{MASUKAN_SARAN_TINGKAT_" . $this->numberToRoman($tingkat) . "}}**\n\n";
                        $context .= "| No | Kode Matakuliah | Nama Matakuliah | Dosen Pengampu | Masukan/Saran |\n";
                        $context .= "|----|----------------|-----------------|----------------|---------------|\n";

                        foreach ($kuesioneData as $i => $row) {
                            $no = $i + 1;
                            $kodeMK = $row->kode_matakuliah ?? '-';
                            $namaMK = $row->nama_matakuliah ?? '-';
                            $dosen = $row->dosen_pengampu ?? '-';

                            // Extract masukan/saran from hasil_analisis
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

                            // Jika masih kosong, gunakan keterangan default
                            if ($masukanSaran === '-' || empty(trim($masukanSaran))) {
                                if ($row->index_kepuasan >= 3.5) {
                                    $masukanSaran = 'Pertahankan kualitas pembelajaran yang sudah baik';
                                } elseif ($row->index_kepuasan >= 3.0) {
                                    $masukanSaran = 'Tingkatkan interaksi dosen-mahasiswa dan perbaiki metode pembelajaran';
                                } else {
                                    $masukanSaran = 'Perlu perbaikan signifikan dalam metode pembelajaran dan penyampaian materi';
                                }
                            }

                            $context .= "| {$no} | {$kodeMK} | {$namaMK} | {$dosen} | {$masukanSaran} |\n";
                        }

                        $context .= "\n";

                        // Statistik tingkat
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
                        $context .= "**{{HASIL_KUESIONER_TINGKAT_" . $this->numberToRoman($tingkat) . "}}**\n\n";
                        $context .= "Tidak ada data kuesioner untuk Tingkat " . $this->numberToRoman($tingkat) . ".\n\n";

                        $context .= "**{{MASUKAN_SARAN_TINGKAT_" . $this->numberToRoman($tingkat) . "}}**\n\n";
                        $context .= "Tidak ada masukan atau saran untuk Tingkat " . $this->numberToRoman($tingkat) . ".\n\n";
                    }
                }

                // Overall Statistics
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

            // Add title
            $periodeObj = Carbon::createFromFormat('Y-m', $laporan->periode);
            $bulanTahun = $periodeObj->locale('id')->translatedFormat('F Y');

            $section->addTitle("LAPORAN MONITORING KUESIONER KEPUASAN MAHASISWA", 1);
            $section->addTitle("Periode: {$bulanTahun}", 2);
            $section->addTextBreak(2);

            $hasilLaporan = $laporan->hasil_laporan ?? [];

            // Ringkasan Eksekutif
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

            // Statistik Utama
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

            // Insight Utama
            if (isset($hasilLaporan['insight_utama']) && !empty($hasilLaporan['insight_utama'])) {
                $section->addTitle("INSIGHT UTAMA", 1);
                foreach ($hasilLaporan['insight_utama'] as $idx => $insight) {
                    $section->addListItem($insight, 0, null, null, null);
                }
                $section->addTextBreak(2);
            }

            // Rekomendasi
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

            // Save Word file
            $fileName = 'laporan_kuesioner_' . $laporan->periode . '_' . time() . '.docx';
            $filePath = 'laporan_kuesioner/' . $fileName;
            $fullPath = storage_path('app/' . $filePath);

            // Create directory if not exists
            $directory = dirname($fullPath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($fullPath);

            // Update laporan
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

    /**
     * Display a listing of templates
     */
    public function templateIndex()
    {
        $templates = TemplateLaporan::where('jenis_laporan', 'kuesioner')
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('gkm.laporan-kuesioner.template.index', compact('templates'));
    }

    /**
     * Show the form for uploading a new template
     */
    public function templateUpload()
    {
        return view('gkm.laporan-kuesioner.template.upload');
    }

    /**
     * Store a newly uploaded template
     */
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

            // Process template to vector DB
            try {
                $this->laporanService->processTemplateToVectorDB($template->id);
                $message = 'Template berhasil diupload dan diindeks ke vector database!';
            } catch (\Exception $e) {
                Log::warning('Template uploaded but indexing failed', [
                    'template_id' => $template->id,
                    'error' => $e->getMessage(),
                ]);
                $message = 'Template berhasil diupload, tetapi gagal diindeks. Silakan klik tombol Reindex.';
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

    /**
     * Download template file
     */
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

    /**
     * Toggle template active status
     */
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

    /**
     * Reindex template for search/RAG
     */
    public function templateReindex($id)
    {
        try {
            $template = TemplateLaporan::findOrFail($id);

            // Process template to vector DB
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

    /**
     * Delete template
     */
    public function templateDestroy($id)
    {
        try {
            $template = TemplateLaporan::findOrFail($id);

            // Delete file from storage
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
