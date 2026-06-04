<?php

namespace App\Http\Controllers\GKM;

/**
 * Extension methods untuk LaporanKuesioneController
 * Tambahkan methods ini ke LaporanKuesioneController.php
 */
trait LaporanKuesioneControllerExtension
{
    /**
     * Smart AI Prompt Handler dengan Auto-Detection
     * - Deteksi prompt sederhana → Generate langsung dari database
     * - Deteksi file upload/prompt kompleks → Gunakan AI + RAG + OCR
     */
    public function aiPromptSmart(Request $request)
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

            // 1. ANALISIS PROMPT TYPE
            $prompt = strtolower(trim($request->input('prompt')));
            $hasFiles = $request->hasFile('file_referensi');
            $conversationHistory = $request->input('conversation_history', []);

            // Keywords untuk auto-generate tanpa AI
            $autoGenerateKeywords = [
                'buatkan laporan kuesioner',
                'generate laporan kuesioner',
                'buat laporan',
                'buatkan laporan',
                'bikin laporan kuesioner',
                'generate laporan',
            ];

            $isSimpleGenerateRequest = false;
            foreach ($autoGenerateKeywords as $keyword) {
                if (str_contains($prompt, $keyword) && !$hasFiles && empty($conversationHistory)) {
                    $isSimpleGenerateRequest = true;
                    break;
                }
            }

            Log::info('Smart AI Prompt Detection', [
                'is_simple' => $isSimpleGenerateRequest,
                'has_files' => $hasFiles,
                'has_history' => !empty($conversationHistory),
                'prompt_preview' => substr($prompt, 0, 100)
            ]);

            // 2. ROUTING: Jalur Cepat atau Jalur AI
            if ($isSimpleGenerateRequest) {
                return $this->generateDirectFromDatabase($request);
            } else {
                return $this->generateWithAI($request);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', $e->validator->errors()->all()),
                'errors' => $e->validator->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Smart AI Prompt failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate laporan langsung dari database (BYPASS AI)
     * Untuk prompt sederhana seperti "Buatkan Laporan Kuesioner"
     */
    private function generateDirectFromDatabase(Request $request)
    {
        try {
            $periode = $request->input('periode');
            $templateId = $request->input('template_id');
            $user = Auth::user();
            $prodiId = $user->prodi_id;

            Log::info('🚀 Direct Database Generation (Bypassing AI)', [
                'periode' => $periode,
                'user_id' => $user->id,
                'prodi_id' => $prodiId
            ]);

            // Validate periode
            if (!$periode) {
                throw new \Exception('Periode harus diisi');
            }

            // Create laporan record
            $periodeObj = Carbon::createFromFormat('Y-m', $periode);

            $laporan = LaporanBulanan::create([
                'user_id' => $user->id,
                'periode' => $periode,
                'bulan' => $periodeObj->month,
                'tahun' => $periodeObj->year,
                'judul_laporan' => 'Laporan Kuesioner Kepuasan Mahasiswa ' . $periodeObj->locale('id')->format('F Y'),
                'template_id' => $templateId,
                'status' => 'pending',
            ]);

            Log::info('Laporan record created', ['laporan_id' => $laporan->id]);

            // Generate Word document langsung dari database
            $wordService = app(\App\Services\KuesioneWordGenerationService::class);

            // Set minimal hasil_laporan (method marker)
            $laporan->hasil_laporan = [
                'method' => 'direct_database',
                'ai_bypassed' => true,
                'generated_at' => now()->toISOString()
            ];
            $laporan->save();

            // Generate Word (service akan query database sendiri)
            $wordGenerated = $wordService->generateWordDocument($laporan);

            if ($wordGenerated) {
                $laporan->status = 'completed';
                $laporan->save();

                $downloadUrl = route('gkm.laporan-kuesioner.download', [
                    'id' => $laporan->id,
                    'format' => 'word'
                ]);

                // Get statistics from database for response
                $year = (int) substr($periode, 0, 4);
                $month = (int) substr($periode, 5, 2);
                $semester = ($month <= 6) ? 2 : 1;

                $uploads = \App\Models\KuesioneUpload::query()
                    ->where('semester', $semester)
                    ->when($prodiId, function($q) use ($user) {
                        $q->whereHas('user', function($uq) use ($user) {
                            $uq->where('prodi_id', $user->prodi_id);
                        });
                    })
                    ->get();

                $totalMK = $uploads->count();
                $avgIndex = $uploads->count() > 0 ? round($uploads->avg('index_kepuasan'), 2) : 0;

                $response = "✅ **LAPORAN KUESIONER BERHASIL DIBUAT!**\n\n";
                $response .= "Laporan telah di-generate **langsung dari database** monitoring kuesioner (tanpa AI delay).\n\n";
                $response .= "## 📊 Ringkasan Data\n\n";
                $response .= "- **Total Mata Kuliah**: {$totalMK}\n";
                $response .= "- **Indeks Kepuasan Rata-rata**: {$avgIndex}\n";
                $response .= "- **Periode**: " . $periodeObj->locale('id')->format('F Y') . "\n\n";
                $response .= "## 📄 File Laporan\n\n";
                $response .= "Laporan Word (.docx) telah dibuat lengkap dengan:\n";
                $response .= "- ✅ Pendahuluan (Tujuan, Waktu, Ruang Lingkup)\n";
                $response .= "- ✅ Hasil Kuesioner per Tingkat (I, II, III, IV)\n";
                $response .= "- ✅ Tabel Matakuliah dan Indeks Kepuasan\n";
                $response .= "- ✅ Masukan/Saran per Matakuliah\n";
                $response .= "- ✅ Kesimpulan dan Rekomendasi\n\n";
                $response .= "📥 **[Download Laporan Word]({$downloadUrl})**\n\n";
                $response .= "File sudah siap digunakan! Jika Anda ingin modifikasi atau tambahan, silakan chat lagi dengan instruksi spesifik.";

                Log::info('✅ Direct generation successful', [
                    'laporan_id' => $laporan->id,
                    'file_word' => $laporan->file_word
                ]);

                return response()->json([
                    'success' => true,
                    'response' => $response,
                    'model_info' => 'Direct Database (No AI)',
                    'cached' => false,
                    'auto_generated' => true,
                    'laporan_id' => $laporan->id,
                    'download_url' => $downloadUrl,
                    'method' => 'direct_database'
                ]);
            } else {
                throw new \Exception('Failed to generate Word document');
            }

        } catch (\Exception $e) {
            Log::error('❌ Direct generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate laporan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate dengan AI Agent (RAG + OCR)
     * Untuk file upload atau chat interaktif
     */
    private function generateWithAI(Request $request)
    {
        try {
            $aiService = app(\App\Services\UnifiedAIService::class);
            $textExtraction = app(\App\Services\TextExtractionService::class);
            $ocrService = app(\App\Services\OCRService::class);

            $userPrompt = $request->input('prompt');
            $conversationHistory = $request->input('conversation_history', []);
            $templateId = $request->input('template_id');
            $periode = $request->input('periode');

            Log::info('🤖 AI-powered generation with RAG + OCR', [
                'has_files' => $request->hasFile('file_referensi'),
                'prompt_length' => strlen($userPrompt),
                'history_count' => count($conversationHistory)
            ]);

            // Build context dari database
            $databaseContext = '';
            if ($periode) {
                $databaseContext = $this->getKuesioneDataFromDatabase($periode);
                Log::info('Database context loaded', [
                    'context_length' => strlen($databaseContext)
                ]);
            }

            // Extract file content jika ada upload
            $filesContext = [];
            $ocrTexts = [];

            if ($request->hasFile('file_referensi')) {
                $files = $request->file('file_referensi');

                foreach ($files as $index => $file) {
                    $fileName = $file->getClientOriginalName();
                    $fileExtension = strtolower($file->getClientOriginalExtension());

                    Log::info('Processing uploaded file', [
                        'index' => $index,
                        'filename' => $fileName,
                        'extension' => $fileExtension
                    ]);

                    // Process image dengan OCR
                    if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $imagePath = $file->store('temp_uploads', 'local');
                        $fullImagePath = storage_path('app/' . $imagePath);

                        $ocrResult = $ocrService->extractText($fullImagePath);

                        if ($ocrResult['success'] && !empty($ocrResult['text'])) {
                            $ocrText = is_array($ocrResult['text'])
                                ? json_encode($ocrResult['text'])
                                : (string)$ocrResult['text'];

                            $ocrTexts[] = [
                                'filename' => $fileName,
                                'text' => $ocrText,
                                'method' => $ocrResult['method'],
                                'confidence' => $ocrResult['confidence'] ?? null
                            ];

                            Log::info('OCR successful', [
                                'filename' => $fileName,
                                'text_length' => strlen($ocrText),
                                'method' => $ocrResult['method']
                            ]);
                        }

                        // Cleanup
                        if (file_exists($fullImagePath)) {
                            @unlink($fullImagePath);
                        }
                    } else {
                        // Process document file
                        $filePath = $file->store('temp_uploads', 'local');
                        $fullPath = storage_path('app/' . $filePath);

                        $result = $textExtraction->extractFromFile($fullPath);
                        $filesContext[] = [
                            'filename' => $fileName,
                            'content' => $result['text'] ?? '',
                            'type' => $fileExtension
                        ];

                        Log::info('Document extraction successful', [
                            'filename' => $fileName,
                            'content_length' => strlen($result['text'] ?? '')
                        ]);

                        // Cleanup
                        if (file_exists($fullPath)) {
                            @unlink($fullPath);
                        }
                    }
                }
            }

            // Build AI system context
            $systemContext = "Anda adalah AI Assistant untuk Gugus Kendali Mutu (GKM) Institut Teknologi Del.\n\n";
            $systemContext .= "Tugas Anda: Membantu membuat dan menganalisis LAPORAN KUESIONER KEPUASAN MAHASISWA.\n\n";

            if (!empty($filesContext) || !empty($ocrTexts)) {
                $systemContext .= "⚠️ User telah mengupload file. Analisis file tersebut dan berikan insight yang mendalam.\n\n";
            }

            if (!empty($databaseContext)) {
                $systemContext .= "DATA KUESIONER DARI DATABASE:\n";
                $systemContext .= $databaseContext . "\n\n";
            }

            $systemContext .= "FORMAT RESPONSE:\n";
            $systemContext .= "- Gunakan Bahasa Indonesia formal dan profesional\n";
            $systemContext .= "- Berikan response dalam format markdown yang rapi\n";
            $systemContext .= "- Jika diminta analisis, berikan insight yang mendalam\n";
            $systemContext .= "- Jika diminta modifikasi laporan, berikan instruksi yang jelas\n\n";

            // Add file context
            if (!empty($filesContext)) {
                $systemContext .= "FILE DOKUMEN YANG DIUPLOAD:\n";
                foreach ($filesContext as $fc) {
                    $systemContext .= "📄 **{$fc['filename']}** (Type: {$fc['type']})\n";
                    $systemContext .= "```\n" . substr($fc['content'], 0, 2000) . "\n```\n\n";
                }
            }

            if (!empty($ocrTexts)) {
                $systemContext .= "🖼️ HASIL OCR DARI GAMBAR:\n";
                foreach ($ocrTexts as $ocr) {
                    $systemContext .= "Gambar: **{$ocr['filename']}** (Method: {$ocr['method']})\n";
                    $systemContext .= "Text:\n```\n" . substr($ocr['text'], 0, 2000) . "\n```\n\n";
                }
            }

            // Build messages array
            $messages = [
                ['role' => 'system', 'content' => $systemContext]
            ];

            // Add conversation history
            foreach ($conversationHistory as $msg) {
                if (isset($msg['role']) && isset($msg['content'])) {
                    $messages[] = $msg;
                }
            }

            // Add current prompt
            $messages[] = ['role' => 'user', 'content' => $userPrompt];

            // Call AI
            $aiResult = $aiService->generateChat($messages, [
                'max_tokens' => 4000,
                'temperature' => 0.7
            ]);

            if (!$aiResult['success'] || empty($aiResult['text'])) {
                throw new \Exception('AI tidak menghasilkan response: ' . ($aiResult['error'] ?? 'Unknown error'));
            }

            Log::info('✅ AI generation successful', [
                'response_length' => strlen($aiResult['text']),
                'model' => $aiResult['model'] ?? 'unknown'
            ]);

            return response()->json([
                'success' => true,
                'response' => $aiResult['text'],
                'model_info' => $aiResult['model'] ?? 'AI Agent with RAG',
                'cached' => false,
                'auto_generated' => false,
                'method' => 'ai_agent',
                'files_processed' => count($filesContext) + count($ocrTexts)
            ]);

        } catch (\Exception $e) {
            Log::error('❌ AI generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses dengan AI: ' . $e->getMessage()
            ], 500);
        }
    }
}
