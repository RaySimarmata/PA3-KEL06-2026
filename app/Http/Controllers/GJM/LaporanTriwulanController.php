<?php

namespace App\Http\Controllers\GJM;

use App\Http\Controllers\Controller;
use App\Models\LaporanGJM;
use App\Models\TemplateLaporan;
use App\Services\LaporanTriwulanService;
use App\Jobs\GenerateLaporanTriwulanJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class LaporanTriwulanController extends Controller
{
    protected $laporanService;

    public function __construct(LaporanTriwulanService $laporanService)
    {
        $this->laporanService = $laporanService;
    }

    /**
     * Show form to create new laporan triwulan
     */
    public function create()
    {
        $user = Auth::user();
        
        // Get available templates for triwulan
        $templates = TemplateLaporan::active()
            ->jenis('laporan_triwulan')
            ->get();
        
        return view('gjm.buat-laporan.triwulan-create', compact('user', 'templates'));
    }

    /**
     * Create draft laporan triwulan
     */
    public function createDraft(Request $request)
    {
        try {
            $request->validate([
                'judul_laporan' => 'required|string|max:255',
                'periode_triwulan' => 'required|in:1,2,3,4',
                'template_id' => 'nullable|exists:template_laporan,id',
            ]);

            $user = Auth::user();
            $periodeTriwulan = $request->periode_triwulan;
            $tahunAjaran = date('Y');

            // Determine periode dates based on triwulan
            $periodeMulai = null;
            $periodeAkhir = null;
            $periodeLabel = '';
            
            switch ($periodeTriwulan) {
                case '1':
                    $periodeMulai = Carbon::create($tahunAjaran, 1, 1);
                    $periodeAkhir = Carbon::create($tahunAjaran, 3, 31);
                    $periodeLabel = 'Triwulan I';
                    break;
                case '2':
                    $periodeMulai = Carbon::create($tahunAjaran, 4, 1);
                    $periodeAkhir = Carbon::create($tahunAjaran, 6, 30);
                    $periodeLabel = 'Triwulan II';
                    break;
                case '3':
                    $periodeMulai = Carbon::create($tahunAjaran, 7, 1);
                    $periodeAkhir = Carbon::create($tahunAjaran, 9, 30);
                    $periodeLabel = 'Triwulan III';
                    break;
                case '4':
                    $periodeMulai = Carbon::create($tahunAjaran, 10, 1);
                    $periodeAkhir = Carbon::create($tahunAjaran, 12, 31);
                    $periodeLabel = 'Triwulan IV';
                    break;
            }

            $laporan = LaporanGJM::create([
                'jenis_laporan' => 'triwulan',
                'template_id' => $request->template_id,
                'periode_mulai' => $periodeMulai,
                'periode_akhir' => $periodeAkhir,
                'ringkasan_mutu_institusi' => $request->judul_laporan . " - {$periodeLabel} {$tahunAjaran}",
                'status_laporan' => 'draft',
                'created_by' => $user->id,
                'instruksi_prompt' => json_encode([
                    'periode' => $periodeLabel,
                    'periode_triwulan' => $periodeTriwulan,
                    'tahun' => $tahunAjaran,
                    'judul' => $request->judul_laporan,
                ]),
            ]);

            Log::info('Draft Laporan Triwulan created', [
                'laporan_id' => $laporan->id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Draft laporan triwulan berhasil dibuat',
                'data' => [
                    'id' => $laporan->id,
                    'judul' => $laporan->ringkasan_mutu_institusi,
                    'periode' => $periodeLabel,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', $e->validator->errors()->all()),
                'errors' => $e->validator->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to create draft laporan triwulan', [
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
     * AI Prompt Assistant - Process user prompt + file
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
            ]);
            
            $userPrompt = $request->input('prompt');
            $hasFiles = $request->hasFile('file_referensi');
            
            // VALIDATION: Ensure user provides instruction when uploading files
            if ($hasFiles && empty(trim($userPrompt))) {
                Log::warning('AI Prompt validation failed: Files uploaded without instruction', [
                    'files_count' => count($request->file('file_referensi')),
                    'prompt_length' => strlen($userPrompt)
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Instruksi diperlukan! Anda telah mengupload file tetapi belum memberikan instruksi. Silakan ketik instruksi Anda, misalnya: "Analisis dokumen ini dan buat ringkasan" atau "Buat laporan berdasarkan data yang diupload".'
                ], 400);
            }
            
            // VALIDATION: Ensure prompt is meaningful (not just whitespace or very short)
            if (strlen(trim($userPrompt)) < 5) {
                Log::warning('AI Prompt validation failed: Prompt too short', [
                    'prompt' => $userPrompt,
                    'prompt_length' => strlen($userPrompt)
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Instruksi terlalu singkat. Silakan berikan instruksi yang lebih jelas dan spesifik (minimal 5 karakter).'
                ], 400);
            }

            $aiService = app(\App\Services\UnifiedAIService::class);
            $textExtraction = app(\App\Services\TextExtractionService::class);
            $ocrService = app(\App\Services\OCRService::class);
            $vectorDbService = app(\App\Services\VectorDatabaseService::class);
            $ragService = app(\App\Services\RAGRetrievalService::class);
            
            // Initialize ImageContentValidationService with OCRService
            $imageValidationService = new \App\Services\ImageContentValidationService($ocrService);

            $userPrompt = $request->input('prompt');
            $conversationHistory = $request->input('conversation_history', []);
            $templateId = $request->input('template_id');
            $laporanId = $request->input('laporan_id');
            
            // Debug logging
            Log::info('AI Prompt Request', [
                'prompt' => substr($userPrompt, 0, 100),
                'has_conversation_history' => !empty($conversationHistory),
                'history_count' => count($conversationHistory),
                'template_id' => $templateId,
                'laporan_id' => $laporanId
            ]);
            
            // Build context for caching
            $cacheContext = [
                'feature' => 'triwulan', // For evaluation tracking
                'type' => 'laporan_triwulan',
                'template_id' => $templateId,
                'periode_triwulan' => $request->input('periode_triwulan'),
                'has_files' => $request->hasFile('file_referensi'),
                'file_count' => $request->hasFile('file_referensi') ? count($request->file('file_referensi')) : 0,
            ];

            // Check cache first
            $cacheService = app(\App\Services\AICacheService::class);
            $cachedResponse = $cacheService->getCachedResponse($userPrompt, $cacheContext);
            
            if ($cachedResponse) {
                Log::info('AI Prompt: Using cached response', [
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
            
            // Get template structure if template is selected
            $templateStructure = $this->extractTemplateStructure($templateId);
            
            // Get GKM monthly reports data for context
            $gkmData = $this->getGKMTriwulanReports($request->input('periode_triwulan'));
            
            // System context for triwulan reports
            $systemContext = "Anda adalah AI Assistant untuk membuat LAPORAN TRIWULAN GJM Institut Teknologi Del.\n\n";
            
            $systemContext .= "❗ PENTING - JENIS LAPORAN:\n";
            $systemContext .= "- Anda sedang membuat LAPORAN TRIWULAN, BUKAN laporan VMTS!\n";
            $systemContext .= "- Laporan Triwulan = Laporan 3 bulanan untuk GJM (Gugus Jaminan Mutu)\n";
            $systemContext .= "- Fokus pada kegiatan monitoring mutu akademik dalam 1 triwulan\n";
            $systemContext .= "- JANGAN membuat laporan tentang VMTS (Visi, Misi, Tujuan, Sasaran)\n\n";
            
            $systemContext .= "CRITICAL - CONVERSATION AWARENESS:\n";
            $systemContext .= "- Anda HARUS melihat SEMUA pesan sebelumnya dalam conversation history\n";
            $systemContext .= "- Jika ada pesan dari 'assistant' sebelumnya, ITU ADALAH DRAFT ANDA SENDIRI\n";
            $systemContext .= "- JANGAN PERNAH bilang 'saya tidak punya informasi sebelumnya' jika ada history\n";
            $systemContext .= "- WAJIB gunakan draft dari pesan assistant sebelumnya sebagai BASIS\n\n";
            
            $systemContext .= "⚠️ ATURAN OUTPUT YANG SANGAT PENTING! ⚠️\n";
            $systemContext .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $systemContext .= "SELALU OUTPUT SEMUA 10 BAGIAN LENGKAP DENGAN KONTEN ASLI!\n";
            $systemContext .= "JANGAN PERNAH TULIS '[copy dari draft sebelumnya]' - COPY KONTEN ASLINYA!\n";
            $systemContext .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            
            $systemContext .= "ATURAN INSTRUKSI:\n\n";
            
            $systemContext .= "1. 'Buat laporan lengkap' → Buat draft BARU dengan SEMUA 10 bagian\n\n";
            
            $systemContext .= "2. 'Perbaiki/Ubah [bagian X]' → Output SEMUA 10 bagian:\n";
            $systemContext .= "   - Bagian X: TULIS KONTEN BARU yang diperbaiki\n";
            $systemContext .= "   - Bagian lain: COPY KONTEN ASLI dari draft sebelumnya (JANGAN tulis '[copy...]')\n";
            $systemContext .= "   - WAJIB output semua 10 bagian dengan konten lengkap!\n\n";
            
            $systemContext .= "3. 'Ubah [teks A] jadi [teks B]' → Output SEMUA 10 bagian:\n";
            $systemContext .= "   - Cari teks A di draft sebelumnya\n";
            $systemContext .= "   - Ganti dengan teks B\n";
            $systemContext .= "   - Output SEMUA bagian dengan konten lengkap\n";
            $systemContext .= "   - Bagian yang tidak berubah: COPY KONTEN ASLI (bukan '[copy...]')\n\n";
            
            $systemContext .= "4. Multiple changes (contoh: 'Ubah A jadi B dan ubah C jadi D'):\n";
            $systemContext .= "   - Lakukan SEMUA perubahan yang diminta\n";
            $systemContext .= "   - Output SEMUA 10 bagian dengan konten lengkap\n";
            $systemContext .= "   - Bagian yang tidak berubah: COPY KONTEN ASLI\n\n";
            
            $systemContext .= "✅ CONTOH OUTPUT YANG BENAR:\n";
            $systemContext .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $systemContext .= "User: 'Perbaiki EVALUASI'\n\n";
            $systemContext .= "AI Output:\n";
            $systemContext .= "# LATAR BELAKANG\n";
            $systemContext .= "Fakultas Vokasi Institut Teknologi Del memiliki peran penting dalam menjaga...\n";
            $systemContext .= "[KONTEN ASLI LENGKAP dari draft sebelumnya]\n\n";
            $systemContext .= "# DASAR\n";
            $systemContext .= "Pelaksanaan kegiatan ini didasarkan pada...\n";
            $systemContext .= "[KONTEN ASLI LENGKAP dari draft sebelumnya]\n\n";
            $systemContext .= "# TUJUAN\n";
            $systemContext .= "Kegiatan ini bertujuan untuk...\n";
            $systemContext .= "[KONTEN ASLI LENGKAP dari draft sebelumnya]\n\n";
            $systemContext .= "... [semua bagian lain dengan KONTEN ASLI LENGKAP]\n\n";
            $systemContext .= "# EVALUASI\n";
            $systemContext .= "Evaluasi dilakukan dengan membandingkan target program kerja...\n";
            $systemContext .= "[KONTEN BARU YANG DIPERBAIKI - INI YANG BERUBAH!]\n\n";
            $systemContext .= "# SARAN\n";
            $systemContext .= "Berdasarkan evaluasi di atas, disarankan untuk...\n";
            $systemContext .= "[KONTEN ASLI LENGKAP dari draft sebelumnya]\n";
            $systemContext .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            
            $systemContext .= "❌ CONTOH OUTPUT YANG SALAH:\n";
            $systemContext .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $systemContext .= "User: 'Perbaiki EVALUASI'\n\n";
            $systemContext .= "AI Output:\n";
            $systemContext .= "# LATAR BELAKANG\n";
            $systemContext .= "[copy dari draft sebelumnya - TIDAK BERUBAH]  ← SALAH! Harus konten asli!\n\n";
            $systemContext .= "# EVALUASI\n";
            $systemContext .= "[konten evaluasi yang diperbaiki]\n";
            $systemContext .= "← SALAH! Bagian lain tidak ada konten aslinya!\n";
            $systemContext .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            
            $systemContext .= "INGAT:\n";
            $systemContext .= "- SELALU output SEMUA 10 bagian dengan KONTEN LENGKAP\n";
            $systemContext .= "- JANGAN PERNAH tulis '[copy dari draft sebelumnya]'\n";
            $systemContext .= "- COPY KONTEN ASLI dari draft sebelumnya untuk bagian yang tidak berubah\n";
            $systemContext .= "- Hanya ubah bagian yang diminta user\n";
            $systemContext .= "- Jangan pernah output hanya 1 bagian!\n";
            $systemContext .= "- Jangan pernah skip konten dengan placeholder!\n\n";
            
            if ($templateStructure) {
                $systemContext .= "STRUKTUR TEMPLATE:\n";
                $systemContext .= $templateStructure . "\n\n";
            } else {
                $systemContext .= "STRUKTUR WAJIB (10 bagian):\n";
                $systemContext .= "# LATAR BELAKANG\n# DASAR\n# TUJUAN\n# RUANG LINGKUP\n# PROGRAM KERJA\n";
                $systemContext .= "# PELAKSANAAN\n# HAMBATAN\n# PEMECAHAN MASALAH\n# EVALUASI\n# SARAN\n\n";
            }
            
            $systemContext .= "FORMAT OUTPUT:\n";
            $systemContext .= "- Gunakan markdown heading level 1 (#) untuk judul bagian\n";
            $systemContext .= "- Gunakan Bahasa Indonesia formal\n";
            $systemContext .= "- WAJIB output SEMUA 10 bagian dengan KONTEN LENGKAP (bukan placeholder)\n\n";
            
            $systemContext .= "KONTEN LAPORAN TRIWULAN:\n";
            $systemContext .= "- Fokus pada kegiatan GJM (Gugus Jaminan Mutu) dalam periode triwulan\n";
            $systemContext .= "- Bahas monitoring perkuliahan, RPS, kuesioner mahasiswa\n";
            $systemContext .= "- Jelaskan program kerja GJM yang dilaksanakan\n";
            $systemContext .= "- Sertakan data dan capaian dalam periode triwulan\n";
            $systemContext .= "- JANGAN bahas tentang VMTS, visi misi, atau strategi jangka panjang\n\n";

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
                        // Process image with OCR first
                        try {
                            // Store image TEMPORARILY first for validation
                            $tempPath = $file->store('temp_validation', 'local');
                            $fullImagePath = storage_path('app/' . $tempPath);
                            
                            // VALIDATION: Check if image is relevant for Laporan Triwulan
                            Log::info('Starting image validation', [
                                'filename' => $fileName,
                                'path' => $fullImagePath,
                                'exists' => file_exists($fullImagePath)
                            ]);
                            
                            $validationResult = $imageValidationService->validateImageRelevance(
                                $fullImagePath,
                                'laporan_triwulan'
                            );
                            
                            Log::info('Image content validation', [
                                'filename' => $fileName,
                                'is_valid' => $validationResult['is_valid'],
                                'reason' => $validationResult['reason'],
                                'confidence' => $validationResult['confidence']
                            ]);
                            
                            // STRICT VALIDATION: Reject if not valid (regardless of confidence)
                            // Only allow if explicitly valid OR confidence is very low (< 0.5)
                            if (!$validationResult['is_valid'] && $validationResult['confidence'] >= 0.5) {
                                Log::warning('Image rejected due to irrelevant content', [
                                    'filename' => $fileName,
                                    'reason' => $validationResult['reason'],
                                    'confidence' => $validationResult['confidence']
                                ]);
                                
                                // Delete the temp file
                                if (file_exists($fullImagePath)) {
                                    @unlink($fullImagePath);
                                }
                                
                                return response()->json([
                                    'success' => false,
                                    'message' => "Gambar '{$fileName}' tidak relevan dengan Laporan Triwulan.\n\nAlasan: {$validationResult['reason']}\n\nSilakan upload gambar yang relevan seperti:\n• Dokumentasi kegiatan kampus\n• Daftar hadir\n• Grafik/chart data akademik\n• Screenshot sistem akademik\n• Dokumentasi monitoring mutu\n• Foto kegiatan perkuliahan"
                                ], 400);
                            }
                            
                            // If validation passed, move to permanent storage
                            $permanentPath = 'laporan_gjm/images/' . uniqid() . '_' . $fileName;
                            Storage::disk('local')->move($tempPath, $permanentPath);
                            $fullImagePath = storage_path('app/' . $permanentPath);
                            
                            // Log if allowed with low confidence
                            if (!$validationResult['is_valid'] && $validationResult['confidence'] < 0.5) {
                                Log::info('Image allowed with warning (very low confidence)', [
                                    'filename' => $fileName,
                                    'confidence' => $validationResult['confidence']
                                ]);
                            }
                            
                            // Extract text using OCR
                            $ocrResult = $ocrService->extractText($fullImagePath);
                            
                            if ($ocrResult['success'] && !empty($ocrResult['text'])) {
                                // Ensure text is string
                                $ocrText = is_array($ocrResult['text']) ? json_encode($ocrResult['text']) : (string)$ocrResult['text'];
                                
                                $ocrTexts[] = [
                                    'filename' => $fileName,
                                    'text' => $ocrText,
                                    'method' => $ocrResult['method'],
                                    'confidence' => $ocrResult['confidence'],
                                    'image_path' => $permanentPath
                                ];
                                
                                // Index OCR text to vector database if laporan_id exists
                                if ($laporanId) {
                                    $vectorDbService->indexDocument([
                                        'text' => $ocrText,
                                        'source_type' => 'laporan_gjm_ocr',
                                        'source_id' => $laporanId,
                                        'chunk_index' => $index,
                                        'metadata' => [
                                            'type' => 'ocr_image',
                                            'filename' => $fileName,
                                            'laporan_id' => $laporanId,
                                            'laporan_type' => 'triwulan',
                                            'ocr_method' => $ocrResult['method'],
                                            'confidence' => $ocrResult['confidence'],
                                            'image_path' => $permanentPath,
                                            'indexed_at' => now()->toIso8601String(),
                                        ]
                                    ]);
                                    
                                    Log::info('OCR text indexed to vector database', [
                                        'filename' => $fileName,
                                        'laporan_id' => $laporanId,
                                        'text_length' => strlen($ocrText),
                                        'image_path' => $permanentPath
                                    ]);
                                }
                            }
                            
                            // Also prepare for Claude Vision API
                            $imageData = base64_encode(file_get_contents($fullImagePath));
                            $mimeType = $file->getMimeType();
                            
                            $imageContents[] = [
                                'filename' => $fileName,
                                'data' => $imageData,
                                'mime_type' => $mimeType,
                                'path' => $permanentPath
                            ];
                            
                            Log::info('Image processed with OCR and Vision API', [
                                'filename' => $fileName,
                                'ocr_text_length' => isset($ocrText) ? strlen($ocrText) : 0,
                                'mime_type' => $mimeType,
                                'stored_at' => $permanentPath
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Image processing failed', [
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
                                'type' => $this->categorizeFile($fileName)
                            ];
                            
                            Log::info('Document extracted for AI prompt', [
                                'filename' => $fileName,
                                'size' => strlen($fileContent),
                                'type' => $this->categorizeFile($fileName)
                            ]);
                        } catch (\Exception $e) {
                            Log::error('File extraction failed', [
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

            // Build conversation messages with proper context
            $messages = [];
            
            // Add system context as first message
            $messages[] = [
                'role' => 'system',
                'content' => $systemContext
            ];
            
            // If there's conversation history, include it (for multi-turn conversation)
            // IMPORTANT: Limit conversation history to prevent token overflow
            if (!empty($conversationHistory)) {
                // Estimate tokens (rough: 1 token ≈ 4 characters)
                $maxHistoryTokens = 4000; // Reserve tokens for history
                $currentTokens = 0;
                $trimmedHistory = [];
                
                // Process history in reverse (keep most recent)
                $reversedHistory = array_reverse($conversationHistory);
                
                foreach ($reversedHistory as $msg) {
                    $role = $msg['role'] ?? 'user';
                    $content = $msg['content'] ?? '';
                    
                    // Skip empty messages
                    if (empty($content)) continue;
                    
                    // Normalize role (assistant -> ai)
                    if ($role === 'assistant' || $role === 'ai') {
                        $role = 'assistant';
                    }
                    
                    // Estimate tokens for this message
                    $messageTokens = (int) (strlen($content) / 4);
                    
                    // Check if adding this message would exceed limit
                    if ($currentTokens + $messageTokens > $maxHistoryTokens) {
                        Log::info('Conversation history truncated', [
                            'kept_messages' => count($trimmedHistory),
                            'total_messages' => count($conversationHistory),
                            'estimated_tokens' => $currentTokens
                        ]);
                        break;
                    }
                    
                    $trimmedHistory[] = [
                        'role' => $role,
                        'content' => $content
                    ];
                    $currentTokens += $messageTokens;
                }
                
                // Reverse back to chronological order
                $trimmedHistory = array_reverse($trimmedHistory);
                
                // Add to messages
                foreach ($trimmedHistory as $msg) {
                    $messages[] = $msg;
                }
                
                Log::info('Conversation history processed', [
                    'original_count' => count($conversationHistory),
                    'kept_count' => count($trimmedHistory),
                    'estimated_tokens' => $currentTokens
                ]);
            }

            // Add current user message
            $currentMessage = '';
            
            // Process uploaded documents
            if (!empty($filesContext)) {
                $currentMessage .= "DOKUMEN YANG DIUPLOAD:\n\n";
                
                foreach ($filesContext as $fileData) {
                    // Truncate very long content to prevent API limits
                    $content = $fileData['content'];
                    $maxFileContentLength = 12000; // Leave room for other parts of the message
                    
                    if (strlen($content) > $maxFileContentLength) {
                        $content = substr($content, 0, $maxFileContentLength) . "\n\n[DOKUMEN DIPOTONG - HANYA BAGIAN AWAL YANG DIPROSES]";
                        Log::info('File content truncated for AI prompt', [
                            'filename' => $fileData['filename'],
                            'original_length' => strlen(is_string($fileData['content']) ? $fileData['content'] : json_encode($fileData['content'])),
                            'truncated_length' => strlen($content)
                        ]);
                    }
                    
                    $currentMessage .= "**{$fileData['filename']}** ({$fileData['type']}):\n";
                    $currentMessage .= "```\n" . $content . "\n```\n\n";
                }
            }
            
            // Get RAG context from vector database if laporan_id exists
            $ragContext = '';
            if ($laporanId) {
                try {
                    $ragResults = $ragService->retrieveContext($userPrompt, [
                        'source_type' => 'laporan_gjm_ocr',
                        'source_id' => $laporanId,
                        'top_k' => 5
                    ]);
                    
                    if (!empty($ragResults)) {
                        $ragContext = "CONTEXT DARI GAMBAR SEBELUMNYA:\n\n";
                        foreach ($ragResults as $result) {
                            $ragContext .= "- " . $result['text'] . "\n";
                            $ragContext .= "  (Relevance: " . round($result['similarity'] * 100, 1) . "%)\n\n";
                        }
                        
                        Log::info('RAG context retrieved', [
                            'laporan_id' => $laporanId,
                            'results_count' => count($ragResults),
                            'top_similarity' => $ragResults[0]['similarity'] ?? 0
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('RAG retrieval failed', [
                        'laporan_id' => $laporanId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Add GKM monthly reports context
            if (!empty($gkmData)) {
                $currentMessage .= "DATA LAPORAN GKM BULANAN:\n\n";
                $currentMessage .= $gkmData . "\n\n";
            }
            
            // Add OCR texts from current upload
            if (!empty($ocrTexts)) {
                $currentMessage .= "TEKS DARI GAMBAR YANG DIUPLOAD:\n\n";
                foreach ($ocrTexts as $ocrData) {
                    $currentMessage .= "**{$ocrData['filename']}** (OCR: {$ocrData['method']}, Confidence: {$ocrData['confidence']}%):\n";
                    $currentMessage .= "```\n" . $ocrData['text'] . "\n```\n\n";
                }
            }
            
            // Add RAG context from previous uploads
            if (!empty($ragContext)) {
                $currentMessage .= $ragContext;
            }
            
            // Process uploaded images (will be handled by Claude Vision)
            if (!empty($imageContents)) {
                $currentMessage .= "GAMBAR DOKUMENTASI (" . count($imageContents) . " file):\n";
                foreach ($imageContents as $imageData) {
                    $currentMessage .= "- {$imageData['filename']}\n";
                }
                $currentMessage .= "\nAnalisis gambar dan sertakan informasi relevan dalam laporan.\n\n";
            }
            
            // Add user instruction - THIS IS THE KEY PART
            $currentMessage .= "===== INSTRUKSI USER =====\n";
            $currentMessage .= $userPrompt . "\n";
            $currentMessage .= "===== END INSTRUKSI =====\n\n";
            
            // CRITICAL: Remind AI about conversation history
            if (!empty($conversationHistory) && count($conversationHistory) > 0) {
                $currentMessage .= "⚠️ PENTING - CONVERSATION HISTORY:\n";
                $currentMessage .= "Ada " . count($conversationHistory) . " pesan sebelumnya dalam conversation history.\n";
                $currentMessage .= "Pesan terakhir dari assistant adalah DRAFT ANDA SENDIRI.\n";
                $currentMessage .= "WAJIB gunakan draft tersebut sebagai BASIS untuk perubahan.\n";
                $currentMessage .= "JANGAN bilang 'tidak ada informasi sebelumnya'!\n\n";
            }
            
            // Detect instruction type and provide specific guidance
            $instructionType = 'unknown';
            $affectedParts = [];
            
            if (preg_match('/^(buat|buatkan|generate)/i', $userPrompt)) {
                // CREATE NEW DRAFT
                $instructionType = 'create';
                $currentMessage .= "CATATAN: User meminta membuat draft BARU.\n";
                $currentMessage .= "Action: Buat draft LENGKAP dengan SEMUA 10 bagian.\n";
                
            } elseif (preg_match('/\s+dan\s+/i', $userPrompt)) {
                // MULTIPLE CHANGES - Detect "dan" keyword
                // Try to extract all change patterns
                $instructionType = 'multi_change';
                $changesList = [];
                
                // Split by "dan" to get individual instructions
                $parts = preg_split('/\s+dan\s+/i', $userPrompt);
                
                foreach ($parts as $part) {
                    $part = trim($part);
                    
                    // Pattern 1: "ubah X jadi Y"
                    if (preg_match('/(ubah|ganti|update|perbaiki|tingkatkan)\s+(.+?)(?:\s+jadi\s+|\s+menjadi\s+)(.+)/i', $part, $match)) {
                        $oldText = trim($match[2]);
                        $newText = trim($match[3]);
                        
                        // Try to detect section name
                        $sectionMap = [
                            'SARAN' => 'SARAN',
                            'PENUTUP' => 'PENUTUP', 
                            'EVALUASI' => 'EVALUASI',
                            'LATAR BELAKANG' => 'LATAR BELAKANG',
                            'DASAR' => 'DASAR',
                            'TUJUAN' => 'TUJUAN',
                            'RUANG LINGKUP' => 'RUANG LINGKUP',
                            'PROGRAM KERJA' => 'PROGRAM KERJA',
                            'PELAKSANAAN' => 'PELAKSANAAN',
                            'HAMBATAN' => 'HAMBATAN',
                            'PEMECAHAN MASALAH' => 'PEMECAHAN MASALAH'
                        ];
                        
                        $detectedSection = null;
                        $normalizedOldText = strtoupper($oldText);
                        foreach ($sectionMap as $key => $value) {
                            if (str_contains($normalizedOldText, $key)) {
                                $detectedSection = $key;
                                $affectedParts[] = $key;
                                // Remove section name from old text
                                $oldText = trim(preg_replace('/' . preg_quote($key, '/') . '/i', '', $oldText));
                                break;
                            }
                        }
                        
                        $changesList[] = [
                            'section' => $detectedSection,
                            'old' => $oldText,
                            'new' => $newText,
                            'type' => 'replace'
                        ];
                        
                    } 
                    // Pattern 2: "perbaiki/ubah bagian X"
                    elseif (preg_match('/(perbaiki|ubah|tingkatkan|lengkapi)(?:\s+bagian)?\s+(.+)/i', $part, $match)) {
                        $section = trim($match[2]);
                        
                        // Remove common modifiers
                        $section = preg_replace('/\s+(agar|lebih|bagus|detail|lengkap|formal|profesional|komprehensif|jadi|menjadi|dengan).*$/i', '', $section);
                        $section = trim($section);
                        
                        // Normalize section name
                        $sectionMap = [
                            'SARAN' => 'SARAN',
                            'PENUTUP' => 'PENUTUP', 
                            'EVALUASI' => 'EVALUASI',
                            'LATAR BELAKANG' => 'LATAR BELAKANG',
                            'DASAR' => 'DASAR',
                            'TUJUAN' => 'TUJUAN',
                            'RUANG LINGKUP' => 'RUANG LINGKUP',
                            'PROGRAM KERJA' => 'PROGRAM KERJA',
                            'PELAKSANAAN' => 'PELAKSANAAN',
                            'HAMBATAN' => 'HAMBATAN',
                            'PEMECAHAN MASALAH' => 'PEMECAHAN MASALAH'
                        ];
                        
                        $normalizedSection = strtoupper($section);
                        foreach ($sectionMap as $key => $value) {
                            if (str_contains($normalizedSection, $key) || str_contains($key, $normalizedSection)) {
                                $section = $key;
                                break;
                            }
                        }
                        
                        $affectedParts[] = $section;
                        $changesList[] = [
                            'section' => $section,
                            'old' => null,
                            'new' => null,
                            'type' => 'improve'
                        ];
                    }
                }
                
                if (!empty($changesList)) {
                    $currentMessage .= "CATATAN: User meminta melakukan BEBERAPA perubahan sekaligus.\n";
                    $currentMessage .= "Jumlah perubahan: " . count($changesList) . "\n";
                    $currentMessage .= "Action yang harus dilakukan:\n\n";
                    
                    foreach ($changesList as $i => $change) {
                        if ($change['type'] === 'replace') {
                            if ($change['section']) {
                                $currentMessage .= ($i + 1) . ". Di bagian '{$change['section']}': Cari '{$change['old']}' → Ganti jadi '{$change['new']}'\n";
                            } else {
                                $currentMessage .= ($i + 1) . ". Cari '{$change['old']}' → Ganti jadi '{$change['new']}'\n";
                            }
                        } elseif ($change['type'] === 'improve') {
                            $currentMessage .= ($i + 1) . ". Perbaiki/tingkatkan bagian '{$change['section']}'\n";
                        }
                    }
                    
                    $currentMessage .= "\n⚠️⚠️⚠️ CRITICAL - MULTIPLE CHANGES ⚠️⚠️⚠️\n";
                    $currentMessage .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    $currentMessage .= "User meminta " . count($changesList) . " perubahan sekaligus.\n";
                    $currentMessage .= "WAJIB lakukan SEMUA perubahan yang diminta!\n\n";
                    
                    $currentMessage .= "LANGKAH-LANGKAH:\n";
                    $currentMessage .= "1. Ambil draft LENGKAP dari pesan assistant sebelumnya\n";
                    $currentMessage .= "2. Lakukan SEMUA " . count($changesList) . " perubahan yang diminta\n";
                    $currentMessage .= "3. Output SEMUA 10 bagian dengan konten lengkap\n";
                    $currentMessage .= "4. Bagian yang tidak berubah: COPY konten asli (bukan placeholder)\n\n";
                    
                    $currentMessage .= "CONTOH OUTPUT YANG BENAR:\n";
                    $currentMessage .= "# LATAR BELAKANG\n[konten lengkap - mungkin ada perubahan]\n\n";
                    $currentMessage .= "# DASAR\n[konten lengkap - mungkin ada perubahan]\n\n";
                    $currentMessage .= "# TUJUAN\n[konten lengkap]\n\n";
                    $currentMessage .= "... [semua bagian lain dengan konten lengkap]\n";
                    $currentMessage .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
                    
                    $currentMessage .= "PENTING:\n";
                    $currentMessage .= "- Lihat draft di pesan assistant sebelumnya\n";
                    $currentMessage .= "- Lakukan SEMUA perubahan yang diminta\n";
                    $currentMessage .= "- Output SEMUA 10 bagian dengan konten lengkap\n";
                    $currentMessage .= "- JANGAN gunakan placeholder seperti '[copy dari draft sebelumnya]'\n\n";
                } else {
                    // Fallback to unknown if no changes detected
                    $instructionType = 'unknown';
                }
                
            } elseif (preg_match('/(ubah|ganti|update|perbaiki)\s+(.+?)\s+(?:jadi|menjadi)\s+(.+)/i', $userPrompt, $match)) {
                // SINGLE SPECIFIC TEXT CHANGE
                $instructionType = 'single_change';
                $oldText = trim($match[2]);
                $newText = trim($match[3]);
                
                // Try to detect section name
                $sectionMap = [
                    'SARAN' => 'SARAN',
                    'PENUTUP' => 'PENUTUP', 
                    'EVALUASI' => 'EVALUASI',
                    'LATAR BELAKANG' => 'LATAR BELAKANG',
                    'DASAR' => 'DASAR',
                    'TUJUAN' => 'TUJUAN',
                    'RUANG LINGKUP' => 'RUANG LINGKUP',
                    'PROGRAM KERJA' => 'PROGRAM KERJA',
                    'PELAKSANAAN' => 'PELAKSANAAN',
                    'HAMBATAN' => 'HAMBATAN',
                    'PEMECAHAN MASALAH' => 'PEMECAHAN MASALAH'
                ];
                
                $detectedSection = null;
                $normalizedOldText = strtoupper($oldText);
                foreach ($sectionMap as $key => $value) {
                    if (str_contains($normalizedOldText, $key)) {
                        $detectedSection = $key;
                        $affectedParts[] = $key;
                        // Remove section name from old text
                        $oldText = trim(preg_replace('/' . preg_quote($key, '/') . '/i', '', $oldText));
                        break;
                    }
                }
                
                $currentMessage .= "CATATAN: User meminta mengubah teks spesifik.\n";
                if ($detectedSection) {
                    $currentMessage .= "Bagian yang terdeteksi: {$detectedSection}\n";
                }
                $currentMessage .= "Action: Cari '{$oldText}' di draft sebelumnya → Ganti jadi '{$newText}'\n\n";
                
                $currentMessage .= "⚠️⚠️⚠️ CRITICAL - OUTPUT RULES ⚠️⚠️⚠️\n";
                $currentMessage .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                $currentMessage .= "LANGKAH-LANGKAH:\n";
                $currentMessage .= "1. Ambil draft LENGKAP dari pesan assistant sebelumnya\n";
                $currentMessage .= "2. Cari teks '{$oldText}' di draft tersebut\n";
                $currentMessage .= "3. Ganti dengan '{$newText}'\n";
                $currentMessage .= "4. Output SEMUA 10 bagian dengan konten lengkap\n";
                $currentMessage .= "5. Bagian yang tidak berubah: COPY konten asli (bukan placeholder)\n\n";
                
                $currentMessage .= "CONTOH OUTPUT YANG BENAR:\n";
                $currentMessage .= "# LATAR BELAKANG\n[konten lengkap - mungkin ada perubahan di sini]\n\n";
                $currentMessage .= "# DASAR\n[konten lengkap]\n\n";
                $currentMessage .= "... [semua bagian lain dengan konten lengkap]\n";
                $currentMessage .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
                
                $currentMessage .= "PENTING: Lihat draft di pesan assistant sebelumnya untuk menemukan teks yang akan diubah.\n";
                
            } elseif (preg_match('/(perbaiki|ubah|tingkatkan|lengkapi)(?:\s+agar)?(?:\s+lebih)?(?:\s+bagus)?(?:\s+detail)?(?:\s+lengkap)?\s+(.+)/i', $userPrompt, $match)) {
                // MODIFY SECTION - with flexible modifiers
                $instructionType = 'modify_section';
                $part = trim($match[2]);
                
                // Extract section name more intelligently
                // Remove common words that are not section names
                $part = preg_replace('/\s+(agar|lebih|bagus|detail|lengkap|formal|profesional|komprehensif)$/i', '', $part);
                $part = trim($part);
                
                // Normalize section names
                $sectionMap = [
                    'SARAN' => 'SARAN',
                    'PENUTUP' => 'PENUTUP', 
                    'EVALUASI' => 'EVALUASI',
                    'LATAR BELAKANG' => 'LATAR BELAKANG',
                    'DASAR' => 'DASAR',
                    'TUJUAN' => 'TUJUAN',
                    'RUANG LINGKUP' => 'RUANG LINGKUP',
                    'PROGRAM KERJA' => 'PROGRAM KERJA',
                    'PELAKSANAAN' => 'PELAKSANAAN',
                    'HAMBATAN' => 'HAMBATAN',
                    'PEMECAHAN MASALAH' => 'PEMECAHAN MASALAH'
                ];
                
                // Find matching section
                $normalizedPart = strtoupper($part);
                foreach ($sectionMap as $key => $value) {
                    if (str_contains($normalizedPart, $key) || str_contains($key, $normalizedPart)) {
                        $part = $key;
                        break;
                    }
                }
                
                $affectedParts[] = $part;
                
                $currentMessage .= "CATATAN: User meminta mengubah bagian '{$part}'.\n";
                $currentMessage .= "Action: Improve/modify bagian '{$part}' berdasarkan draft sebelumnya\n";
                $currentMessage .= "\n";
                $currentMessage .= "⚠️⚠️⚠️ CRITICAL - OUTPUT RULES ⚠️⚠️⚠️\n";
                $currentMessage .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                $currentMessage .= "OUTPUT FORMAT YANG BENAR:\n";
                $currentMessage .= "# {$part}\n";
                $currentMessage .= "[konten {$part} yang diperbaiki]\n";
                $currentMessage .= "\n";
                $currentMessage .= "JANGAN OUTPUT:\n";
                $currentMessage .= "❌ LATAR BELAKANG\n";
                $currentMessage .= "❌ DASAR\n";
                $currentMessage .= "❌ TUJUAN\n";
                $currentMessage .= "❌ RUANG LINGKUP\n";
                $currentMessage .= "❌ PROGRAM KERJA\n";
                $currentMessage .= "❌ PELAKSANAAN\n";
                $currentMessage .= "❌ HAMBATAN DAN PEMECAHAN MASALAH\n";
                $currentMessage .= "❌ EVALUASI\n";
                $currentMessage .= "❌ PENUTUP\n";
                $currentMessage .= "❌ SARAN (kecuali ini yang diminta)\n";
                $currentMessage .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                $currentMessage .= "\n";
                $currentMessage .= "HANYA output bagian '{$part}' saja!\n";
                $currentMessage .= "Jika Anda output lebih dari 1 bagian, itu adalah KESALAHAN FATAL!\n";
                
            } elseif (preg_match('/(tambah|tambahkan)\s+(.+)/i', $userPrompt)) {
                // ADD CONTENT
                $instructionType = 'add';
                $currentMessage .= "CATATAN: User meminta menambahkan konten.\n";
                $currentMessage .= "Action: Tambahkan konten tanpa mengubah yang sudah ada\n";
                $currentMessage .= "Output: Bagian yang ditambahkan\n";
                
            } elseif (preg_match('/(hapus|remove|buang)\s+(.+)/i', $userPrompt)) {
                // DELETE CONTENT
                $instructionType = 'delete';
                $currentMessage .= "CATATAN: User meminta menghapus konten.\n";
                $currentMessage .= "Action: Hapus bagian yang diminta\n";
                $currentMessage .= "Output: Konfirmasi penghapusan\n";
            }
            
            // Log instruction type for debugging
            Log::info('Instruction type detected', [
                'type' => $instructionType,
                'affected_parts' => $affectedParts,
                'user_prompt' => $userPrompt,
                'changes_count' => isset($changesList) ? count($changesList) : 0,
                'changes_detail' => isset($changesList) ? $changesList : null
            ]);
            
            $messages[] = [
                'role' => 'user',
                'content' => $currentMessage
            ];

            // Call AI service using Chat Completions API with conversation history
            // This properly supports multi-turn conversations
            // Add retry mechanism for better reliability
            $maxRetries = 2;
            $retryCount = 0;
            $aiResult = null;
            $lastError = null;
            
            while ($retryCount <= $maxRetries) {
                try {
                    $aiResult = $aiService->generateChat($messages, [
                        'max_tokens' => 8192,
                        'temperature' => 0.7
                    ]);
                    
                    // If successful, break the loop
                    if ($aiResult['success'] && !empty($aiResult['text'])) {
                        break;
                    }
                    
                    // If not successful but no exception, retry
                    $lastError = $aiResult['error'] ?? 'AI returned empty response';
                    $retryCount++;
                    if ($retryCount <= $maxRetries) {
                        Log::warning('AI generation failed, retrying...', [
                            'attempt' => $retryCount,
                            'error' => $lastError
                        ]);
                        usleep(1000000); // Wait 1 second before retry
                    }
                    
                } catch (\Exception $e) {
                    $lastError = $e->getMessage();
                    $retryCount++;
                    if ($retryCount <= $maxRetries) {
                        Log::warning('AI generation exception, retrying...', [
                            'attempt' => $retryCount,
                            'error' => $lastError
                        ]);
                        usleep(1000000); // Wait 1 second before retry
                    } else {
                        // Last retry failed, set error result
                        $aiResult = [
                            'success' => false,
                            'error' => $lastError,
                            'provider' => 'unknown'
                        ];
                    }
                }
            }
            
            if (!$aiResult || !$aiResult['success'] || empty($aiResult['text'])) {
                Log::error('AI returned empty response after all retries', [
                    'prompt_length' => strlen($userPrompt),
                    'files_count' => count($filesContext),
                    'images_count' => count($imageContents),
                    'messages_count' => count($messages),
                    'conversation_turns' => count(array_filter($messages, fn($m) => ($m['role'] ?? '') !== 'system')),
                    'error' => $lastError ?? ($aiResult['error'] ?? 'Unknown error'),
                    'provider' => $aiResult['provider'] ?? 'unknown',
                    'retries_attempted' => $retryCount
                ]);
                
                // More specific and helpful error messages
                $errorMessage = 'Layanan AI mengalami masalah. ';
                $errorDetails = $lastError ?? ($aiResult['error'] ?? '');
                
                if (str_contains($errorDetails, 'Rate limit') || str_contains($errorDetails, '429')) {
                    $errorMessage .= 'Terlalu banyak permintaan. Silakan tunggu 1-2 menit dan coba lagi.';
                } elseif (str_contains($errorDetails, 'token') || str_contains($errorDetails, 'context_length')) {
                    $errorMessage .= 'Percakapan terlalu panjang. Silakan klik tombol "Clear Conversation" dan mulai baru.';
                } elseif (str_contains($errorDetails, 'API key') || str_contains($errorDetails, 'authentication')) {
                    $errorMessage .= 'Konfigurasi API tidak valid. Silakan hubungi administrator.';
                } elseif (str_contains($errorDetails, 'timeout') || str_contains($errorDetails, 'timed out')) {
                    $errorMessage .= 'Request timeout. Silakan coba lagi dengan pesan yang lebih singkat.';
                } elseif (str_contains($errorDetails, 'network') || str_contains($errorDetails, 'connection')) {
                    $errorMessage .= 'Masalah koneksi jaringan. Silakan periksa koneksi internet Anda.';
                } else {
                    $errorMessage .= 'Silakan coba lagi. Jika masalah berlanjut, refresh halaman atau hubungi administrator.';
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'debug_info' => [
                        'messages_count' => count($messages),
                        'conversation_turns' => count(array_filter($messages, fn($m) => ($m['role'] ?? '') !== 'system')),
                        'provider' => $aiResult['provider'] ?? 'unknown',
                        'error_detail' => $errorDetails,
                        'retries_attempted' => $retryCount
                    ]
                ], 503);
            }
            
            $aiResponse = $aiResult['text'];
            
            // DISABLE BACKEND FILTER - Let frontend handle the merge
            // Frontend will extract requested section and merge with full draft
            // This ensures all sections are always visible to user
            
            // Log instruction type for debugging
            Log::info('AI Response received - NO BACKEND FILTER', [
                'instruction_type' => $instructionType,
                'requested_sections' => $affectedParts ?? [],
                'response_length' => strlen($aiResponse),
                'sections_in_response' => preg_match_all('/^#\s+[A-Z\s]+$/m', $aiResponse),
                'note' => 'Frontend will handle section merge'
            ]);

            // Cache the response for future use
            $responseTime = isset($aiResult['processing_time_ms']) ? $aiResult['processing_time_ms'] / 1000 : null;
            $cacheService->cacheResponse(
                $userPrompt,
                $cacheContext,
                $aiResponse,
                $aiResult['provider'],
                $aiResult['model'],
                $responseTime
            );

            // Create evaluation test entry for model evaluation tracking
            try {
                $evaluationService = app(\App\Services\AIEvaluationService::class);
                $evaluationService->createAIResponseTest([
                    'test_name' => 'Laporan Triwulan - ' . date('Y-m-d H:i:s'),
                    'feature' => 'triwulan',
                    'query' => $userPrompt,
                    'expected_response' => null, // No ground truth for user-generated content
                    'actual_response' => $aiResponse,
                ]);
                
                Log::info('AI Evaluation test created', [
                    'feature' => 'triwulan',
                    'prompt_length' => strlen($userPrompt),
                    'response_length' => strlen($aiResponse),
                ]);
            } catch (\Exception $e) {
                // Don't fail the request if evaluation logging fails
                Log::warning('Failed to create AI evaluation test', [
                    'error' => $e->getMessage(),
                    'feature' => 'triwulan'
                ]);
            }

            Log::info('AI Prompt successful', [
                'prompt_length' => strlen($userPrompt),
                'response_length' => strlen($aiResponse),
                'files_count' => count($filesContext),
                'images_count' => count($imageContents),
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
                'conversation_context' => [
                    'total_messages' => count($messages),
                    'is_continuation' => !empty($conversationHistory),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('AI Prompt failed', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'user_prompt' => substr($userPrompt ?? '', 0, 100),
                'files_count' => count($filesContext ?? []),
                'images_count' => count($imageContents ?? []),
                'messages_count' => count($messages ?? []),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            // Check if it's a specific AI service error
            $errorMessage = $e->getMessage();
            
            if (str_contains($errorMessage, 'Rate limit') || 
                str_contains($errorMessage, 'quota') ||
                str_contains($errorMessage, '429')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Layanan AI sedang sibuk (rate limit). Silakan tunggu 1-2 menit dan coba lagi.'
                ], 429);
            }
            
            if (str_contains($errorMessage, 'API key') ||
                str_contains($errorMessage, 'authentication') ||
                str_contains($errorMessage, 'unauthorized')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Konfigurasi API tidak valid. Silakan hubungi administrator.'
                ], 500);
            }
            
            if (str_contains($errorMessage, 'timeout') ||
                str_contains($errorMessage, 'timed out')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request timeout. Silakan coba lagi dengan pesan yang lebih singkat.'
                ], 504);
            }

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $errorMessage,
                'error_type' => get_class($e)
            ], 500);
        }
    }

    /**
     * Save AI Preview to Database
     * Called after user gets AI response to store it for Word generation
     */
    public function savePreview(Request $request)
    {
        try {
            $request->validate([
                'laporan_id' => 'required|exists:laporan_gjm,id',
                'ai_preview_draft' => 'required|string',
                'ai_sections' => 'nullable|string', // JSON string
                'ocr_images' => 'nullable|string', // JSON string of image paths
            ]);

            $laporanId = $request->input('laporan_id');
            $aiPreviewDraft = $request->input('ai_preview_draft');
            $aiSectionsJson = $request->input('ai_sections', '[]');
            $ocrImagesJson = $request->input('ocr_images', '[]');
            
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
                Log::warning('Failed to parse AI sections JSON', [
                    'laporan_id' => $laporanId,
                    'error' => $e->getMessage()
                ]);
            }

            // Parse OCR images from JSON
            $ocrImages = [];
            try {
                $ocrImagesArray = json_decode($ocrImagesJson, true);
                if (is_array($ocrImagesArray)) {
                    $ocrImages = $ocrImagesArray;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to parse OCR images JSON', [
                    'laporan_id' => $laporanId,
                    'error' => $e->getMessage()
                ]);
            }

            // Use AIPreviewCacheService to save
            $cacheService = app(\App\Services\AIPreviewCacheService::class);
            $success = $cacheService->saveAIPreview($laporanId, $aiPreviewDraft, $sections, $ocrImages);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'AI preview saved successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save AI preview'
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            Log::error('Save AI preview failed', [
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
        $title = strtolower(trim($title));
        
        $mapping = [
            'latar belakang' => 'latar_belakang',
            'dasar' => 'dasar',
            'tujuan' => 'tujuan',
            'ruang lingkup' => 'ruang_lingkup',
            'program kerja' => 'program_kerja',
            'pelaksanaan' => 'pelaksanaan',
            'hambatan' => 'hambatan',
            'pemecahan masalah' => 'pemecahan_masalah',
            'evaluasi' => 'evaluasi',
            'saran' => 'rekomendasi',
            'rekomendasi' => 'rekomendasi',
            'kesimpulan' => 'kesimpulan',
        ];
        
        return $mapping[$title] ?? str_replace(' ', '_', $title);
    }

    /**
     * Store new laporan triwulan (trigger generation)
     * Menggunakan draft dari AI Prompt Assistant untuk mengisi placeholder template
     * 
     * Dapat dipanggil dengan dua cara:
     * 1. Membuat laporan baru: periode_triwulan + judul_laporan
     * 2. Generate dari draft yang sudah ada: laporan_id + ai_preview_data
     */
    public function store(Request $request)
    {
        try {
            // Validate based on whether we're creating new or generating from existing
            if ($request->has('laporan_id') && !empty($request->laporan_id)) {
                // Mode: Generate dari laporan yang sudah ada
                $request->validate([
                    'laporan_id' => 'required|exists:laporan_gjm,id',
                    'ai_preview_data' => 'nullable|string',
                ]);
                
                $laporan = LaporanGJM::findOrFail($request->laporan_id);
                $aiDraft = $request->input('ai_preview_data', '');
                
            } else {
                // Mode: Buat laporan baru
                $request->validate([
                    'periode_triwulan' => 'required|in:1,2,3,4',
                    'judul_laporan' => 'required|string|max:500',
                    'template_id' => 'nullable|exists:template_laporan,id',
                    'ai_preview_data' => 'nullable|string',
                    'mode' => 'nullable|in:sync,async',
                ]);

                $user = Auth::user();
                $periodeTriwulan = $request->periode_triwulan;
                $tahunAjaran = date('Y');
                $aiDraft = $request->input('ai_preview_data', '');

                // Determine periode dates based on triwulan
                $periodeMulai = null;
                $periodeAkhir = null;
                $periodeLabel = '';
                
                switch ($periodeTriwulan) {
                    case '1':
                        $periodeMulai = Carbon::create($tahunAjaran, 1, 1);
                        $periodeAkhir = Carbon::create($tahunAjaran, 3, 31);
                        $periodeLabel = 'Triwulan I (Januari - Maret)';
                        break;
                    case '2':
                        $periodeMulai = Carbon::create($tahunAjaran, 4, 1);
                        $periodeAkhir = Carbon::create($tahunAjaran, 6, 30);
                        $periodeLabel = 'Triwulan II (April - Juni)';
                        break;
                    case '3':
                        $periodeMulai = Carbon::create($tahunAjaran, 7, 1);
                        $periodeAkhir = Carbon::create($tahunAjaran, 9, 30);
                        $periodeLabel = 'Triwulan III (Juli - September)';
                        break;
                    case '4':
                        $periodeMulai = Carbon::create($tahunAjaran, 10, 1);
                        $periodeAkhir = Carbon::create($tahunAjaran, 12, 31);
                        $periodeLabel = 'Triwulan IV (Oktober - Desember)';
                        break;
                }

                // Create laporan record
                $laporan = LaporanGJM::create([
                    'jenis_laporan' => 'triwulan',
                    'template_id' => $request->template_id,
                    'periode_mulai' => $periodeMulai,
                    'periode_akhir' => $periodeAkhir,
                    'ringkasan_mutu_institusi' => $request->judul_laporan . " - {$periodeLabel} {$tahunAjaran}",
                    'status_laporan' => 'draft',
                    'created_by' => $user->id,
                    'instruksi_prompt' => json_encode([
                        'periode' => $periodeLabel,
                        'periode_triwulan' => $periodeTriwulan,
                        'tahun' => $tahunAjaran,
                        'judul' => $request->judul_laporan,
                        'ai_draft' => $aiDraft,
                    ]),
                ]);
            }

            $mode = $request->input('mode', 'sync');

            if ($mode === 'sync') {
                // Generate langsung (synchronous)
                try {
                    $job = new GenerateLaporanTriwulanJob($laporan->id);
                    $job->handle(app(LaporanTriwulanService::class));
                    
                    // Reload laporan to get updated status
                    $laporan->refresh();
                    
                    // Check if file exists and return download
                    Log::info('Checking for generated file', [
                        'laporan_id' => $laporan->id,
                        'dokumen_hasil_path' => $laporan->dokumen_hasil_path,
                        'file_exists' => $laporan->dokumen_hasil_path ? Storage::exists($laporan->dokumen_hasil_path) : false
                    ]);
                    
                    if ($laporan->dokumen_hasil_path && Storage::exists($laporan->dokumen_hasil_path)) {
                        $filePath = Storage::path($laporan->dokumen_hasil_path);
                        $fileName = 'Laporan_Triwulan_' . $laporan->id . '.docx';
                        
                        Log::info('Returning file download', [
                            'laporan_id' => $laporan->id,
                            'file_path' => $filePath,
                            'file_name' => $fileName,
                            'file_size' => file_exists($filePath) ? filesize($filePath) : 0
                        ]);
                        
                        return response()->download($filePath, $fileName, [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ]);
                    }
                    
                    // Fallback to JSON if file not found
                    Log::warning('File not found, returning JSON response', [
                        'laporan_id' => $laporan->id,
                        'status_laporan' => $laporan->status_laporan,
                        'dokumen_hasil_path' => $laporan->dokumen_hasil_path
                    ]);
                    
                    if ($laporan->status_laporan === 'completed') {
                        return response()->json([
                            'success' => true,
                            'message' => 'Laporan berhasil di-generate!',
                            'redirect' => route('gjm.laporan-gjm.show', $laporan->id)
                        ]);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Laporan di-generate dengan fallback values. Silakan review dan edit manual.',
                            'redirect' => route('gjm.laporan-gjm.show', $laporan->id)
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Sync laporan triwulan generation failed', [
                        'laporan_id' => $laporan->id,
                        'error' => $e->getMessage()
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal generate laporan: ' . $e->getMessage(),
                        'redirect' => route('gjm.laporan-gjm.show', $laporan->id)
                    ], 500);
                }
            } else {
                // Generate dengan queue (asynchronous)
                GenerateLaporanTriwulanJob::dispatch($laporan->id);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Laporan sedang diproses oleh AI Agent. Halaman akan otomatis refresh.',
                    'redirect' => route('gjm.laporan-gjm.show', $laporan->id)
                ]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            Log::error('Unexpected error in LaporanTriwulanController@store', [
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
     * Extract structure from template Word document
     * Reads the template and identifies sections/placeholders
     */
    private function extractTemplateStructure($templateId)
    {
        if (!$templateId) {
            return null;
        }

        try {
            $template = TemplateLaporan::find($templateId);
            if (!$template || !$template->file_path) {
                return null;
            }

            $templatePath = storage_path('app/public/' . $template->file_path);
            if (!file_exists($templatePath)) {
                Log::warning('Template file not found', ['path' => $templatePath]);
                return null;
            }

            // Read template using PhpWord
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($templatePath);
            $structure = [];
            $currentSection = null;

            // Extract text from all sections
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $text = $this->extractTextFromElement($element);
                    if (!empty($text)) {
                        // Detect section headers (Roman numerals, numbers, or bold text)
                        if (preg_match('/^(I{1,3}V?|IV|V?I{0,3})\.\s*(.+)$/i', $text, $matches)) {
                            // Roman numeral section (e.g., "I. PENDAHULUAN")
                            $currentSection = trim($matches[2]);
                            $structure[] = "# " . strtoupper($currentSection);
                        } elseif (preg_match('/^([a-z])\.\s*(.+)$/i', $text, $matches)) {
                            // Sub-section (e.g., "a. Latar Belakang")
                            $subSection = trim($matches[2]);
                            $structure[] = "## " . ucwords(strtolower($subSection));
                        } elseif (preg_match('/^\d+\.\s*(.+)$/i', $text, $matches)) {
                            // Numbered section (e.g., "1. PENDAHULUAN")
                            $currentSection = trim($matches[1]);
                            $structure[] = "# " . strtoupper($currentSection);
                        }
                    }
                }
            }

            if (empty($structure)) {
                Log::info('No structure found in template, using default');
                return null;
            }

            $structureText = implode("\n", $structure);
            Log::info('Template structure extracted', [
                'template_id' => $templateId,
                'sections_found' => count($structure),
                'structure' => $structureText
            ]);

            return $structureText;

        } catch (\Exception $e) {
            Log::error('Failed to extract template structure', [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Extract text from PhpWord element recursively
     */
    private function extractTextFromElement($element)
    {
        $text = '';

        if (method_exists($element, 'getText')) {
            $elementText = $element->getText();
            // Handle different return types from getText()
            if (is_string($elementText)) {
                $text = $elementText;
            } elseif (is_object($elementText) && method_exists($elementText, '__toString')) {
                $text = (string) $elementText;
            }
        } elseif (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $childElement) {
                $text .= $this->extractTextFromElement($childElement);
            }
        } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
            // Handle TextRun elements specifically
            foreach ($element->getElements() as $textElement) {
                if (method_exists($textElement, 'getText')) {
                    $elementText = $textElement->getText();
                    if (is_string($elementText)) {
                        $text .= $elementText;
                    }
                }
            }
        }

        // Only trim if we have a string
        return is_string($text) ? trim($text) : '';
    }

    /**
     * Categorize uploaded file based on filename
     */
    private function categorizeFile($filename)
    {
        $filename = strtolower($filename);
        
        if (strpos($filename, 'laporan') !== false && strpos($filename, 'gjm') !== false) {
            return 'Laporan GJM Tahun Lalu';
        } elseif (strpos($filename, 'laporan') !== false && strpos($filename, 'gkm') !== false) {
            return 'Laporan GKM Bulanan';
        } elseif (strpos($filename, 'dokumentasi') !== false || strpos($filename, 'foto') !== false) {
            return 'Dokumentasi Foto Kegiatan';
        } elseif (strpos($filename, 'daftar') !== false && strpos($filename, 'hadir') !== false) {
            return 'Daftar Hadir Peserta';
        } elseif (strpos($filename, 'kuesioner') !== false || strpos($filename, 'kuisioner') !== false) {
            return 'Kuesioner/Kuisioner';
        } elseif (strpos($filename, 'reminder') !== false) {
            return 'Reminder/Pengingat';
        } elseif (strpos($filename, 'kalender') !== false) {
            return 'Kalender Akademik';
        } elseif (strpos($filename, 'sosialisasi') !== false) {
            return 'Dokumentasi Sosialisasi';
        } elseif (strpos($filename, 'vmts') !== false) {
            return 'Dokumen VMTS';
        } elseif (strpos($filename, 'grand') !== false && strpos($filename, 'opening') !== false) {
            return 'Dokumentasi Grand Opening';
        } else {
            return 'Dokumen Pendukung';
        }
    }

    /**
     * Get GKM monthly reports data for the specified triwulan period
    /**
     * Get GKM monthly reports data for the specified triwulan period
     */
    private function getGKMTriwulanReports($periodeTriwulan)
    {
        try {
            // Determine months based on triwulan
            $months = [];
            $currentYear = date('Y');
            
            switch ($periodeTriwulan) {
                case '1':
                    // Triwulan I: Jan - Mar
                    $months = [1, 2, 3];
                    break;
                case '2':
                    // Triwulan II: Apr - Jun
                    $months = [4, 5, 6];
                    break;
                case '3':
                    // Triwulan III: Jul - Sep
                    $months = [7, 8, 9];
                    break;
                case '4':
                    // Triwulan IV: Oct - Dec
                    $months = [10, 11, 12];
                    break;
                default:
                    return '';
            }

            $gkmReports = \App\Models\LaporanBulanan::whereYear('created_at', $currentYear)
                ->whereIn(\DB::raw('MONTH(created_at)'), $months)
                ->where('status', 'completed')
                ->with(['prodi'])
                ->get();

            if ($gkmReports->isEmpty()) {
                return '';
            }

            $summary = "Ringkasan Laporan GKM Bulanan:\n";
            foreach ($gkmReports as $report) {
                $month = $report->created_at->format('F Y');
                $prodi = $report->prodi->nama_prodi ?? 'Unknown';
                $summary .= "- {$month} ({$prodi}): {$report->ringkasan_kegiatan}\n";
            }

            return $summary;

        } catch (\Exception $e) {
            Log::error('Failed to get GKM triwulan reports', [
                'periode_triwulan' => $periodeTriwulan,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    /**
     * Save uploaded images metadata to laporan
     * Called from frontend after images are uploaded via aiPrompt
     */
    public function saveUploadedImages(Request $request)
    {
        try {
            $request->validate([
                'laporan_id' => 'required|exists:laporan_gjm,id',
                'images' => 'required|array',
                'images.*.path' => 'required|string',
                'images.*.filename' => 'required|string',
            ]);

            $laporanId = $request->input('laporan_id');
            $images = $request->input('images');

            $laporan = LaporanGJM::findOrFail($laporanId);

            // Prepare OCR data structure
            $ocrData = [
                'images' => $images,
                'images_count' => count($images),
                'saved_at' => now()->toIso8601String(),
            ];

            // Update laporan with OCR data
            $laporan->update([
                'ocr_data' => $ocrData,
                'has_ocr_data' => true,
            ]);

            Log::info('Uploaded images saved to laporan', [
                'laporan_id' => $laporanId,
                'images_count' => count($images),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Images saved successfully',
                'images_count' => count($images),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to save uploaded images', [
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
     * Display a listing of laporan triwulan
     */
    public function index(Request $request)
    {
        $query = LaporanGJM::where('jenis_laporan', 'triwulan')
            ->where('created_by', Auth::id())
            ->with('template')
            ->orderBy('created_at', 'desc');

        // Filter by periode
        if ($request->filled('periode')) {
            $query->whereRaw("JSON_EXTRACT(instruksi_prompt, '$.periode_triwulan') = ?", [$request->periode]);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status_laporan', $request->status);
        }

        $laporanList = $query->paginate(10);

        // Add formatted data for display
        $laporanList->getCollection()->transform(function ($laporan) {
            // instruksi_prompt sudah berupa array karena cast di model
            $instruksi = is_array($laporan->instruksi_prompt) ? $laporan->instruksi_prompt : [];
            $periodeTriwulan = $instruksi['periode_triwulan'] ?? null;
            $tahun = $instruksi['tahun'] ?? date('Y');
            
            // Format periode
            $periodeLabels = [
                '1' => 'Triwulan I (Januari - Maret)',
                '2' => 'Triwulan II (April - Juni)',
                '3' => 'Triwulan III (Juli - September)',
                '4' => 'Triwulan IV (Oktober - Desember)'
            ];
            
            // Jika periode_triwulan ada dan valid, gunakan label yang sesuai
            if ($periodeTriwulan && isset($periodeLabels[$periodeTriwulan])) {
                $laporan->formatted_periode = $periodeLabels[$periodeTriwulan] . ' - ' . $tahun;
            } else {
                // Fallback ke periode_mulai dan periode_akhir jika ada
                $laporan->formatted_periode = $laporan->getPeriodeLabel();
            }
            
            $laporan->judul_laporan = $instruksi['judul'] ?? $laporan->ringkasan_mutu_institusi;
            
            // Status badge
            switch ($laporan->status_laporan) {
                case 'completed':
                    $laporan->status_badge = 'success';
                    $laporan->status_label = 'Selesai';
                    break;
                case 'processing':
                    $laporan->status_badge = 'warning';
                    $laporan->status_label = 'Sedang Diproses';
                    break;
                case 'error':
                    $laporan->status_badge = 'danger';
                    $laporan->status_label = 'Error';
                    break;
                default:
                    $laporan->status_badge = 'info';
                    $laporan->status_label = 'Menunggu';
            }
            
            return $laporan;
        });

        // Generate periode list for filter
        $periodeList = [
            '1' => 'Triwulan I (Januari - Maret)',
            '2' => 'Triwulan II (April - Juni)',
            '3' => 'Triwulan III (Juli - September)',
            '4' => 'Triwulan IV (Oktober - Desember)'
        ];

        return view('gjm.buat-laporan.triwulan-index', compact('laporanList', 'periodeList'));
    }

    /**
     * Display the specified laporan
     */
    public function show($id)
    {
        $laporan = LaporanGJM::where('id', $id)
            ->where('jenis_laporan', 'triwulan')
            ->where('created_by', Auth::id())
            ->with('template')
            ->firstOrFail();

        return view('gjm.buat-laporan.triwulan-show', compact('laporan'));
    }

    /**
     * Download laporan in specified format
     */
    public function download($id, $format = 'word')
    {
        $laporan = LaporanGJM::where('id', $id)
            ->where('jenis_laporan', 'triwulan')
            ->where('created_by', Auth::id())
            ->firstOrFail();

        if ($format === 'word' && $laporan->file_word) {
            $filePath = storage_path('app/' . $laporan->file_word);
            
            if (file_exists($filePath)) {
                $instruksi = json_decode($laporan->instruksi_prompt, true);
                $periodeTriwulan = $instruksi['periode_triwulan'] ?? '1';
                $tahun = $instruksi['tahun'] ?? date('Y');
                $fileName = 'Laporan_Triwulan_' . $periodeTriwulan . '_' . $tahun . '.docx';
                
                return response()->download($filePath, $fileName);
            }
        }

        return redirect()->back()->with('error', 'File tidak ditemukan');
    }

    /**
     * Remove the specified laporan
     */
    public function destroy($id)
    {
        $laporan = LaporanGJM::where('id', $id)
            ->where('jenis_laporan', 'triwulan')
            ->where('created_by', Auth::id())
            ->firstOrFail();

        // Delete associated files
        if ($laporan->file_word) {
            Storage::delete($laporan->file_word);
        }

        $laporan->delete();

        return redirect()->route('gjm.buat-laporan.triwulan.index')
            ->with('success', 'Laporan berhasil dihapus');
    }
    
    /**
     * Filter AI response to only include requested sections
     * This is a post-processing step to ensure AI follows instructions
     * 
     * @param string $response Full AI response
     * @param array $requestedSections Array of section names that were requested
     * @return string Filtered response containing only requested sections
     */
    private function filterResponseToRequestedSections(string $response, array $requestedSections): string
    {
        // Normalize requested sections (uppercase, trim)
        $requestedSections = array_map(function($section) {
            return strtoupper(trim($section));
        }, $requestedSections);
        
        Log::info('Filtering response', [
            'requested_sections' => $requestedSections,
            'response_preview' => substr($response, 0, 200)
        ]);
        
        // Try multiple splitting strategies
        $filteredSections = [];
        
        // Strategy 1: Split by markdown headings (# SECTION)
        if (preg_match_all('/^#\s+([^\n]+)\n(.*?)(?=^#\s+|\z)/ms', $response, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $title = strtoupper(trim($match[1]));
                $content = trim($match[2]);
                
                // Check if this section was requested
                foreach ($requestedSections as $requested) {
                    if (str_contains($title, $requested) || str_contains($requested, $title) || 
                        levenshtein($title, $requested) <= 3) { // Allow small typos
                        $filteredSections[] = "# " . $match[1] . "\n" . $content;
                        Log::info('Section matched', ['title' => $title, 'requested' => $requested]);
                        break;
                    }
                }
            }
        }
        
        // Strategy 2: If no markdown headings found, try uppercase headings
        if (empty($filteredSections)) {
            if (preg_match_all('/^([A-Z\s]+)\n(.*?)(?=^[A-Z\s]+\n|\z)/ms', $response, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $title = strtoupper(trim($match[1]));
                    $content = trim($match[2]);
                    
                    // Skip if title is too short (likely not a section)
                    if (strlen($title) < 3) continue;
                    
                    foreach ($requestedSections as $requested) {
                        if (str_contains($title, $requested) || str_contains($requested, $title) || 
                            levenshtein($title, $requested) <= 3) {
                            $filteredSections[] = "# " . $match[1] . "\n" . $content;
                            Log::info('Section matched (uppercase)', ['title' => $title, 'requested' => $requested]);
                            break;
                        }
                    }
                }
            }
        }
        
        // Strategy 3: If still no sections found, search for keywords in text
        if (empty($filteredSections)) {
            foreach ($requestedSections as $requested) {
                // Look for the section name in the text
                if (preg_match('/(.{0,50}' . preg_quote($requested, '/') . '.{0,500})/i', $response, $match)) {
                    $filteredSections[] = "# " . $requested . "\n" . trim($match[1]);
                    Log::info('Section found by keyword search', ['requested' => $requested]);
                }
            }
        }
        
        // If we found filtered sections, return them
        if (!empty($filteredSections)) {
            $result = implode("\n\n", $filteredSections);
            Log::info('Filter successful', [
                'sections_found' => count($filteredSections),
                'result_length' => strlen($result)
            ]);
            return $result;
        }
        
        // Last resort: If no sections matched, try to extract just the improved content
        // Look for content that seems different from a standard template
        foreach ($requestedSections as $requested) {
            $pattern = '/(?:^|\n)(?:#\s*)?' . preg_quote($requested, '/') . '\s*\n(.*?)(?=\n(?:#\s*)?[A-Z\s]{3,}\n|\z)/is';
            if (preg_match($pattern, $response, $match)) {
                $content = trim($match[1]);
                if (strlen($content) > 50) { // Only if substantial content
                    Log::info('Last resort extraction successful', ['section' => $requested]);
                    return "# " . $requested . "\n" . $content;
                }
            }
        }
        
        // If all else fails, return original response with warning
        Log::warning('Filter failed - returning original response', [
            'requested_sections' => $requestedSections,
            'response_length' => strlen($response)
        ]);
        
        return $response;
    }
}


