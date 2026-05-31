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
        $query = LaporanGKM::with(['user', 'prodi', 'template'])
            ->where('jenis_laporan', 'artefak')
            ->orderBy('created_at', 'desc');

        // Filter by periode
        if ($request->filled('periode')) {
            $query->where('periode', $request->periode);
        }

        // Filter by prodi (for GKM TRPL)
        $user = Auth::user();
        if ($user->prodi_id) {
            $query->where('prodi_id', $user->prodi_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $laporanList = $query->paginate(10);

        // Get available periodes for filter
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
        
        // Get active template directly from model
        $template = TemplateLaporan::where('jenis_template', 'laporan_artefak')
            ->where('is_active', true)
            ->first();
        
        // Get available templates
        $templates = TemplateLaporan::where('jenis_template', 'laporan_artefak')
            ->where('is_active', true)
            ->get();

        // Generate periode options (last 6 months)
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
     * AI Prompt Assistant - Process user prompt + file for Laporan Artefak
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
            
            // Get template structure if template is selected
            $templateStructure = $this->extractTemplateStructure($templateId);
            
            // System context for Artefak reports
            $systemContext = "Anda adalah AI Assistant untuk Gugus Kendali Mutu (GKM) Institut Teknologi Del.\n\n";
            $systemContext .= "Tugas Anda: Membantu membuat LAPORAN ARTEFAK/VMTS berdasarkan dokumen yang diupload dan instruksi user.\n\n";
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
                // Default structure untuk laporan artefak
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
            
            $systemContext .= "Fokus pada analisis artefak akademik seperti RPS, silabus, materi kuliah, dan dokumen pembelajaran.\n";
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
                        // Process document file
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
            
            // Process uploaded documents
            if (!empty($filesContext)) {
                $currentMessage .= "Saya telah mengupload beberapa dokumen artefak:\n\n";
                
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
                $currentMessage .= "Teks yang diekstrak dari gambar artefak:\n\n";
                foreach ($ocrTexts as $ocrData) {
                    $currentMessage .= "**{$ocrData['filename']}** (OCR Method: {$ocrData['method']}, Confidence: {$ocrData['confidence']}%):\n";
                    $currentMessage .= "```\n" . $ocrData['text'] . "\n```\n\n";
                }
            }
            
            $currentMessage .= "Instruksi dari user: " . $userPrompt . "\n\n";
            
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

            // Call AI service using Chat Completions API with conversation history
            $aiResult = $aiService->generateChat($messages, [
                'max_tokens' => 8192, // Increased token limit
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

            Log::info('AI Prompt successful for Artefak', [
                'prompt_length' => strlen($userPrompt),
                'response_length' => strlen($aiResponse),
                'files_count' => count($filesContext),
                'images_count' => count($imageContents),
                'messages_count' => count($messages),
                'has_template' => !empty($templateStructure),
                'provider' => $aiResult['provider'],
                'model' => $aiResult['model']
            ]);

            return response()->json([
                'success' => true,
                'response' => $aiResponse,
                'model_info' => $aiResult['provider'] . ' (' . $aiResult['model'] . ')',
                'cached' => false,
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
     * Extract template structure (placeholder - implement based on your template system)
     */
    private function extractTemplateStructure($templateId)
    {
        if (!$templateId) return null;
        
        try {
            $template = TemplateLaporan::find($templateId);
            if (!$template) return null;
            
            // This is a placeholder - implement based on your template structure
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
     * Store new laporan artefak (trigger generation)
     */
    public function store(Request $request)
    {
        $request->validate([
            'periode' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'template_id' => 'nullable|exists:template_laporan,id',
            'mode' => 'nullable|in:sync,async', // sync = langsung, async = queue
        ]);

        $user = Auth::user();
        $periode = $request->periode;
        $prodiId = $user->prodi_id;
        $mode = $request->input('mode', 'sync'); // Default: sync (langsung)

        // Check if laporan already exists
        $existing = LaporanGKM::where('periode', $periode)
            ->where('prodi_id', $prodiId)
            ->where('jenis_laporan', 'artefak')
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'Laporan untuk periode ini sudah ada. Silakan hapus terlebih dahulu jika ingin generate ulang.');
        }

        // Parse periode
        $periodeObj = Carbon::createFromFormat('Y-m', $periode);
        $bulan = $periodeObj->locale('id')->translatedFormat('F');
        $tahun = $periodeObj->year;

        // Create laporan record
        $laporan = LaporanGKM::create([
            'periode' => $periode,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'prodi_id' => $prodiId,
            'user_id' => $user->id,
            'template_id' => $request->template_id,
            'jenis_laporan' => 'artefak',
            'status' => 'pending',
        ]);

        if ($mode === 'sync') {
            // Generate langsung (synchronous) - tidak perlu queue worker
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
            // Generate dengan queue (asynchronous) - perlu queue worker
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
        $laporan = LaporanGKM::with(['user', 'prodi', 'template'])
            ->where('jenis_laporan', 'artefak')
            ->findOrFail($id);

        // Check access
        $user = Auth::user();
        if ($user->prodi_id && $laporan->prodi_id != $user->prodi_id) {
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

            // Check access
            $user = Auth::user();
            if ($user->prodi_id && $laporan->prodi_id != $user->prodi_id) {
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

        // Check access
        $user = Auth::user();
        if ($user->prodi_id && $laporan->prodi_id != $user->prodi_id) {
            abort(403, 'Unauthorized access');
        }

        // Delete files
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
            'deskripsi' => 'nullable|string',
            'contoh_konten' => 'nullable|string',
        ]);

        $user = Auth::user();
        $file = $request->file('file_template');

        // Validate that the uploaded file is a valid Word document
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

        // Store file
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('templates', $fileName, 'public');

        // Create template record
        $template = TemplateLaporan::create([
            'prodi_id' => $user->prodi_id,
            'nama_template' => $request->nama_template,
            'nama_file' => $file->getClientOriginalName(),
            'jenis_file' => $file->getClientOriginalExtension(),
            'file_path' => $filePath,
            'ukuran_file' => $file->getSize(),
            'deskripsi' => $request->deskripsi,
            'uploaded_by' => $user->id,
            'jenis_template' => 'laporan_artefak',
            'contoh_konten' => $request->contoh_konten,
            'is_active' => true,
        ]);

        // Process template structure ke vector DB
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

        // Check if template is being used
        $usageCount = LaporanGKM::where('template_id', $id)
            ->where('jenis_laporan', 'artefak')
            ->count();
            
        if ($usageCount > 0) {
            return redirect()->back()->with('error', "Template tidak dapat dihapus karena sedang digunakan oleh {$usageCount} laporan.");
        }

        // Delete file
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
            
            // Try multiple possible file paths
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
}
