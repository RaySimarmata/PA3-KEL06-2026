<?php

namespace App\Http\Controllers\GJM;

use App\Http\Controllers\Controller;
use App\Models\LaporanGJM;
use App\Models\TemplateLaporan;
use App\Services\LaporanSemesterService;
use App\Jobs\GenerateLaporanSemesterJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LaporanSemesterController extends Controller
{
    protected $laporanService;

    public function __construct(LaporanSemesterService $laporanService)
    {
        $this->laporanService = $laporanService;
    }

    /**
     * Show form to create new laporan semester
     */
    public function create()
    {
        $user = Auth::user();
        
        // Get available templates for semester
        $templates = TemplateLaporan::active()
            ->jenis('laporan_semester')
            ->get();
        
        return view('gjm.buat-laporan.semester', compact('user', 'templates'));
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

            $claudeService = app(\App\Services\ClaudeAIService::class);
            $textExtraction = app(\App\Services\TextExtractionService::class);

            $userPrompt = $request->input('prompt');
            $conversationHistory = $request->input('conversation_history', []);
            $templateId = $request->input('template_id');
            
            // Get template structure if template is selected
            $templateStructure = $this->extractTemplateStructure($templateId);
            
            // Get GKM monthly reports data for context
            $gkmData = $this->getGKMSemesterReports($request->input('periode_semester'));
            
            // System context for Semester reports
            $systemContext = "Anda adalah AI Assistant untuk Gugus Jaminan Mutu (GJM) Institut Teknologi Del.\n\n";
            $systemContext .= "Tugas Anda: Membantu membuat LAPORAN SEMESTER berdasarkan dokumen yang diupload dan instruksi user.\n\n";
            
            if ($templateStructure) {
                $systemContext .= "STRUKTUR TEMPLATE YANG HARUS DIIKUTI:\n";
                $systemContext .= $templateStructure . "\n\n";
                $systemContext .= "PENTING: Anda HARUS mengikuti struktur template di atas dengan KETAT. Gunakan markdown heading level 1 (#) untuk setiap bagian utama sesuai template.\n";
                $systemContext .= "Jangan menambah atau mengurangi bagian dari template. Isi setiap bagian dengan konten yang relevan berdasarkan dokumen yang diupload.\n\n";
            } else {
                // Default structure jika tidak ada template
                $systemContext .= "PENTING: Gunakan STRUKTUR WAJIB berikut dengan markdown heading level 1 (#):\n\n";
                $systemContext .= "# LATAR BELAKANG\n";
                $systemContext .= "[Jelaskan konteks dan alasan pembuatan laporan]\n\n";
                $systemContext .= "# DASAR\n";
                $systemContext .= "[Jelaskan dasar hukum dan kebijakan yang menjadi landasan]\n\n";
                $systemContext .= "# TUJUAN\n";
                $systemContext .= "[Jelaskan tujuan laporan dan kegiatan yang dilakukan]\n\n";
                $systemContext .= "# RUANG LINGKUP\n";
                $systemContext .= "[Jelaskan cakupan laporan dan area yang dibahas]\n\n";
                $systemContext .= "# PROGRAM KERJA\n";
                $systemContext .= "[Jelaskan program kerja yang dilaksanakan]\n\n";
                $systemContext .= "# PELAKSANAAN\n";
                $systemContext .= "[Jelaskan pelaksanaan program kerja dan capaiannya]\n\n";
                $systemContext .= "# HAMBATAN\n";
                $systemContext .= "[Jelaskan hambatan yang dihadapi]\n\n";
                $systemContext .= "# PEMECAHAN MASALAH\n";
                $systemContext .= "[Jelaskan solusi untuk mengatasi hambatan]\n\n";
                $systemContext .= "# EVALUASI\n";
                $systemContext .= "[Jelaskan evaluasi dan analisis capaian]\n\n";
                $systemContext .= "# SARAN\n";
                $systemContext .= "[Jelaskan saran dan rekomendasi untuk perbaikan]\n\n";
            }
            
            $systemContext .= "Gunakan Bahasa Indonesia formal dan profesional. Setiap bagian harus berisi konten yang substantif dan relevan.\n\n";

            // Extract file content if uploaded
            $filesContext = [];
            $imageContents = [];
            
            if ($request->hasFile('file_referensi')) {
                $files = $request->file('file_referensi');
                
                foreach ($files as $index => $file) {
                    $fileName = $file->getClientOriginalName();
                    $fileExtension = strtolower($file->getClientOriginalExtension());
                    
                    // Check if it's an image
                    if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        // Process image with Claude Vision
                        try {
                            $imageData = base64_encode(file_get_contents($file->getPathname()));
                            $mimeType = $file->getMimeType();
                            
                            $imageContents[] = [
                                'filename' => $fileName,
                                'data' => $imageData,
                                'mime_type' => $mimeType
                            ];
                            
                            Log::info('Image prepared for Vision API', [
                                'filename' => $fileName,
                                'size' => strlen($imageData),
                                'mime_type' => $mimeType
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

            // Build conversation messages
            $messages = [];
            
            // If there's conversation history, include it
            if (!empty($conversationHistory)) {
                foreach ($conversationHistory as $msg) {
                    $messages[] = [
                        'role' => $msg['role'] ?? 'user',
                        'content' => $msg['content'] ?? ''
                    ];
                }
            }

            // Add current user message
            $currentMessage = '';
            
            // Process uploaded documents
            if (!empty($filesContext)) {
                $currentMessage .= "Saya telah mengupload beberapa dokumen pendukung:\n\n";
                
                foreach ($filesContext as $fileData) {
                    $currentMessage .= "**{$fileData['filename']}** ({$fileData['type']}):\n";
                    $currentMessage .= "```\n" . substr($fileData['content'], 0, 15000) . "\n```\n\n";
                }
            }
            
            // Add GKM monthly reports context
            if (!empty($gkmData)) {
                $currentMessage .= "Data Laporan GKM Bulanan untuk periode semester ini:\n\n";
                $currentMessage .= $gkmData . "\n\n";
            }
            
            // Process uploaded images (will be handled by Claude Vision)
            if (!empty($imageContents)) {
                $currentMessage .= "Saya juga telah mengupload " . count($imageContents) . " gambar dokumentasi:\n";
                foreach ($imageContents as $imageData) {
                    $currentMessage .= "- {$imageData['filename']}\n";
                }
                $currentMessage .= "\nMohon analisis gambar-gambar tersebut dan sertakan informasi relevan dalam laporan.\n\n";
            }
            
            $currentMessage .= "Instruksi dari user: " . $userPrompt . "\n\n";
            
            if ($templateStructure) {
                $currentMessage .= "PENTING: Anda HARUS menghasilkan draft laporan yang mengikuti STRUKTUR TEMPLATE yang telah diberikan di system context.\n\n";
                $currentMessage .= "Gunakan semua dokumen dan gambar yang saya upload sebagai sumber data dan informasi untuk mengisi setiap bagian template.\n\n";
                $currentMessage .= "Setiap bagian harus berisi minimal 2-3 paragraf dengan konten yang substantif dan relevan berdasarkan dokumen yang diupload.\n";
            } else {
                $currentMessage .= "PENTING: Anda HARUS menghasilkan SEMUA 10 bagian berikut dengan konten yang substantif:\n\n";
                $currentMessage .= "1. # LATAR BELAKANG\n";
                $currentMessage .= "2. # DASAR\n";
                $currentMessage .= "3. # TUJUAN\n";
                $currentMessage .= "4. # RUANG LINGKUP\n";
                $currentMessage .= "5. # PROGRAM KERJA\n";
                $currentMessage .= "6. # PELAKSANAAN\n";
                $currentMessage .= "7. # HAMBATAN\n";
                $currentMessage .= "8. # PEMECAHAN MASALAH\n";
                $currentMessage .= "9. # EVALUASI\n";
                $currentMessage .= "10. # SARAN\n\n";
                $currentMessage .= "Jangan skip bagian manapun. Setiap bagian harus berisi minimal 2-3 paragraf dengan konten yang relevan.\n";
            }
            
            $currentMessage .= "Gunakan informasi dari semua dokumen dan gambar yang diupload untuk membuat laporan yang komprehensif dan akurat.\n";
            
            $messages[] = [
                'role' => 'user',
                'content' => $currentMessage
            ];

            // Call Claude AI with Vision support if images are present
            if (!empty($imageContents)) {
                $aiResponse = $claudeService->chatWithVision($systemContext, $messages, $imageContents, 4096);
            } else {
                $aiResponse = $claudeService->chat($systemContext, $messages, 4096);
            }

            // Validate AI response - ensure it's not empty or error message
            if (!$aiResponse) {
                Log::error('AI returned empty response', [
                    'prompt_length' => strlen($userPrompt),
                    'files_count' => count($filesContext),
                    'images_count' => count($imageContents)
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'AI tidak memberikan respons. Pastikan koneksi internet stabil dan layanan AI tersedia. Silakan coba lagi.'
                ], 500);
            }

            // Check if AI response indicates service unavailability
            if (str_contains(strtolower($aiResponse), 'sistem ai sedang tidak tersedia') || 
                str_contains(strtolower($aiResponse), 'semua layanan ai sedang tidak tersedia')) {
                
                Log::error('All AI services unavailable', [
                    'response_preview' => substr($aiResponse, 0, 200)
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Layanan AI sedang tidak tersedia. Silakan coba lagi dalam beberapa menit atau hubungi administrator.'
                ], 503);
            }

            Log::info('AI Prompt successful', [
                'prompt_length' => strlen($userPrompt),
                'response_length' => strlen($aiResponse),
                'files_count' => count($filesContext),
                'images_count' => count($imageContents),
                'has_template' => !empty($templateStructure)
            ]);

            return response()->json([
                'success' => true,
                'response' => $aiResponse,
                'model_info' => $claudeService->getModelInfo(),
            ]);

        } catch (\Exception $e) {
            Log::error('AI Prompt failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_prompt' => substr($userPrompt ?? '', 0, 100),
                'files_count' => count($filesContext ?? []),
                'images_count' => count($imageContents ?? [])
            ]);

            // Check if it's a specific AI service error
            if (str_contains($e->getMessage(), 'Rate limit') || 
                str_contains($e->getMessage(), 'quota') ||
                str_contains($e->getMessage(), 'API key')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Layanan AI mengalami masalah: ' . $e->getMessage()
                ], 500);
            }

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store new laporan semester (trigger generation)
     * Menggunakan draft dari AI Prompt Assistant untuk mengisi placeholder template
     * 
     * Dapat dipanggil dengan dua cara:
     * 1. Membuat laporan baru: periode_semester + judul_laporan
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
                    'periode_semester' => 'required|in:ganjil,genap',
                    'judul_laporan' => 'required|string|max:500',
                    'template_id' => 'nullable|exists:template_laporan,id',
                    'ai_preview_data' => 'nullable|string',
                    'mode' => 'nullable|in:sync,async',
                ]);

                $user = Auth::user();
                $periodeSemester = $request->periode_semester;
                $tahunAjaran = date('Y');
                $aiDraft = $request->input('ai_preview_data', '');

                // Determine periode dates first
                $periodeMulai = null;
                $periodeAkhir = null;
                $periodeLabel = '';
                
                if ($periodeSemester === 'ganjil') {
                    $periodeMulai = Carbon::create($tahunAjaran, 8, 1);
                    $periodeAkhir = Carbon::create($tahunAjaran + 1, 1, 31);
                    $periodeLabel = 'Semester Ganjil';
                } else {
                    $periodeMulai = Carbon::create($tahunAjaran, 2, 1);
                    $periodeAkhir = Carbon::create($tahunAjaran, 7, 31);
                    $periodeLabel = 'Semester Genap';
                }

                // Create laporan record
                $laporan = LaporanGJM::create([
                    'jenis_laporan' => 'semester',
                    'template_id' => $request->template_id,
                    'periode_mulai' => $periodeMulai,
                    'periode_akhir' => $periodeAkhir,
                    'ringkasan_mutu_institusi' => $request->judul_laporan . " - {$periodeLabel} {$tahunAjaran}",
                    'status_laporan' => 'draft',
                    'created_by' => $user->id,
                    'instruksi_prompt' => json_encode([
                        'periode' => $periodeLabel,
                        'periode_semester' => $periodeSemester,
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
                    $job = new GenerateLaporanSemesterJob($laporan->id);
                    $job->handle(app(LaporanSemesterService::class));
                    
                    // Reload laporan to get updated status
                    $laporan->refresh();
                    
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
                    Log::error('Sync laporan semester generation failed', [
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
                GenerateLaporanSemesterJob::dispatch($laporan->id);
                
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
            Log::error('Unexpected error in LaporanSemesterController@store', [
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
     * Get GKM monthly reports data for the specified semester period
     */
    private function getGKMSemesterReports($periodeSemester)
    {
        try {
            // Determine months based on semester
            $months = [];
            $currentYear = date('Y');
            
            if ($periodeSemester === 'ganjil') {
                // Semester Ganjil: Aug - Dec (previous year) + Jan (current year)
                $months = [8, 9, 10, 11, 12, 1];
                // For Jan, use current year; for Aug-Dec, use previous year
            } else {
                // Semester Genap: Feb - Jul
                $months = [2, 3, 4, 5, 6, 7];
            }

            $gkmReports = \App\Models\LaporanBulanan::where(function($query) use ($months, $currentYear, $periodeSemester) {
                if ($periodeSemester === 'ganjil') {
                    $query->where(function($q) use ($currentYear) {
                        $q->whereYear('created_at', $currentYear)->whereMonth('created_at', 1);
                    })->orWhere(function($q) use ($currentYear) {
                        $q->whereYear('created_at', $currentYear - 1)->whereIn(\DB::raw('MONTH(created_at)'), [8, 9, 10, 11, 12]);
                    });
                } else {
                    $query->whereYear('created_at', $currentYear)->whereIn(\DB::raw('MONTH(created_at)'), $months);
                }
            })
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
            Log::error('Failed to get GKM semester reports', [
                'periode_semester' => $periodeSemester,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }
}
