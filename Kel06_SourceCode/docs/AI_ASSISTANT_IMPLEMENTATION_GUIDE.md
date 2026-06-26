# Panduan Implementasi AI Assistant untuk Laporan Artefak dan Semester

## Overview
Dokumen ini menjelaskan langkah-langkah untuk menambahkan fitur AI Assistant ke halaman:
1. Generate Laporan Artefak Baru (`gkm/laporan-artefak/create.blade.php`)
2. Generate Laporan Semester/Bulanan Baru (`gjm/buat-laporan/semester-create.blade.php`)

Fitur AI Assistant mengikuti pola implementasi yang sudah ada pada halaman Generate Laporan Triwulan.

## Referensi File
- **Template**: `resources/views/gjm/buat-laporan/triwulan-create.blade.php`
- **JavaScript**: `public/js/ai-prompt-assistant-triwulan.js`
- **Controller**: `app/Http/Controllers/GJM/LaporanTriwulanController.php` (method `aiPrompt`)

---

## Bagian 1: Update Blade View - Laporan Artefak

### File: `resources/views/gkm/laporan-artefak/create.blade.php`

#### Step 1: Tambahkan CSS di @section('styles')

Salin semua CSS dari `triwulan-create.blade.php` section styles (baris 6-885).
Ubah comment header dari `AI PROMPT ASSISTANT — TRIWULAN` menjadi `AI PROMPT ASSISTANT — ARTEFAK`.

```php
@section('styles')
    <style>
        /* ===============================================================
           AI PROMPT ASSISTANT — ARTEFAK
           =============================================================== */
        
        /* ... (salin semua CSS dari triwulan-create.blade.php) ... */
    </style>
@endsection
```

#### Step 2: Tambahkan AI Chat Interface HTML

Tambahkan section berikut **SEBELUM** closing tag `</form>` dan **SETELAH** form fields terakhir:

```php
<!-- ===== AI PROMPT ASSISTANT ===== -->
<div class="monitoring-card mb-4">
    <div class="monitoring-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-stars" style="color: #2563eb;"></i>
            <h6 class="mb-0">AI Prompt Assistant</h6>
        </div>
    </div>
    <div style="padding: 1.5rem;">
        <div class="alert-gkm info mb-3">
            <strong>💡 Cara Menggunakan AI Assistant:</strong><br>
            <ol style="margin: 0.5rem 0 0 0; padding-left: 1.25rem;">
                <li>Pilih periode dan template (opsional) terlebih dahulu</li>
                <li>Ketik instruksi Anda di kolom chat</li>
                <li>Upload file pendukung jika diperlukan (DOCX, PDF, gambar)</li>
                <li>Klik tombol panah (↑) untuk generate draft</li>
                <li>AI akan memberikan response dan Anda bisa melakukan iterasi</li>
            </ol>
        </div>

        <!-- AI Chat Interface -->
        <div class="ai-chat-wrapper">
            <div class="ai-chat-header">
                <div class="ai-avatar">
                    <i class="bi bi-stars"></i>
                </div>
                <div>
                    <h6>AI Assistant - Laporan Artefak</h6>
                    <small>Siap membantu Anda membuat laporan RPS & Materi</small>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="ai-messages" id="ai-messages">
                <!-- Welcome message will be added by JavaScript -->
            </div>

            <!-- Typing Indicator -->
            <div class="typing-indicator" id="typing-indicator">
                <div class="bubble">
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>

            <!-- Input Area -->
            <div class="ai-input-area">
                <!-- Hidden File Inputs -->
                <input type="file" id="file_referensi_artefak" name="file_referensi[]"
                    accept=".docx,.pdf,.txt,.xlsx,.xls,.jpg,.jpeg,.png,.gif,.bmp,.webp" multiple
                    style="display: none;">
                <input type="file" id="ocr_images_artefak" name="ocr_images[]"
                    accept=".jpg,.jpeg,.png,.pdf" multiple style="display: none;">

                <!-- Input Container with Attachment Icon Inside -->
                <div style="position: relative; display: flex; align-items: center; border: 1.5px solid #d1dff5; border-radius: 12px; background: #fff; padding: 0.5rem;">
                    <!-- Attachment Button (Inside Left) -->
                    <button type="button" class="btn-attachment-inline" id="btn-attachment"
                        title="Lampirkan File & Gambar">
                        <i class="bi bi-paperclip"></i>
                    </button>

                    <!-- Textarea -->
                    <textarea class="ai-textarea-inline" id="ai-prompt-input" 
                        placeholder="Deskripsikan laporan artefak yang ingin Anda buat..."></textarea>

                    <!-- Send Button (Inside Right) -->
                    <button type="button" class="btn-ask-ai-inline" id="btn-ask-ai">
                        <span class="arrow-icon">↑</span>
                    </button>
                </div>

                <!-- Selected Files & Images Display (Combined) -->
                <div id="all-attachments-display" style="display: none; margin-top: 0.75rem;">
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;"
                        id="all-attachments-list"></div>
                </div>

                <!-- OCR Processing Status -->
                <div id="ocr-processing-status"
                    style="display: none; margin-top: 0.75rem; padding: 0.75rem; background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px; font-size: 0.875rem; color: #0369a1;">
                    <i class="bi bi-hourglass-split me-2"></i>
                    <strong>Memproses OCR...</strong> Mengekstrak teks dari gambar yang Anda upload.
                </div>
            </div>
        </div>
    </div>
</div>
```

#### Step 3: Tambahkan JavaScript di @section('scripts')

```php
@section('scripts')
    <script src="{{ asset('js/ai-prompt-assistant-artefak.js') }}"></script>
@endsection
```

---

## Bagian 2: Update Blade View - Laporan Semester

### File: `resources/views/gjm/buat-laporan/semester-create.blade.php`

**File ini SUDAH MEMILIKI** AI Assistant styles dan HTML structure yang mirip dengan triwulan.

#### Yang perlu diverifikasi:

1. **CSS Styles** - Sudah ada di @section('styles')
2. **HTML AI Chat Interface** - Sudah ada di form
3. **JavaScript** - Perlu update untuk menggunakan inline implementation

#### Update JavaScript:

Ganti script yang ada di @section('scripts') dengan:

```php
@section('scripts')
    <!-- OCR Upload JavaScript -->
    <script src="{{ asset('js/ocr-upload.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AI Assistant
            const promptInput = document.getElementById('ai-prompt-input');
            const btnAskAI = document.getElementById('btn-ask-ai');
            const messagesBox = document.getElementById('ai-messages');
            const typingIndicator = document.getElementById('typing-indicator');
            const btnAttachment = document.getElementById('btn-attachment');
            const fileReferensi = document.getElementById('file_referensi_semester');
            const ocrImages = document.getElementById('ocr_images_semester');
            
            let conversationHistory = [];
            let isProcessing = false;
            
            // Add welcome message
            addMessage('ai', `<p>Halo! Saya AI Assistant untuk membantu Anda membuat laporan semester. Saya dapat membantu dengan:</p>
                <ul>
                    <li>Memberikan saran konten laporan semester</li>
                    <li>Menganalisis data kegiatan dalam periode semester yang dipilih</li>
                    <li>Membantu struktur laporan yang sesuai standar</li>
                    <li>Memberikan template dan format yang tepat</li>
                    <li>Melakukan iterasi dan perbaikan draft</li>
                </ul>
                <p><strong>Tips:</strong> Anda bisa mengatakan "ubah bagian X" atau "perbaiki Y" untuk melakukan revisi!</p>`);
            
            // Event handlers
            if (btnAskAI) {
                btnAskAI.addEventListener('click', sendMessage);
            }
            
            if (promptInput) {
                promptInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });
            }
            
            async function sendMessage() {
                if (isProcessing) return;
                
                const message = promptInput.value.trim();
                if (!message || message.length < 5) {
                    addMessage('system', 'Silakan masukkan instruksi yang lebih jelas (minimal 5 karakter).');
                    return;
                }
                
                isProcessing = true;
                promptInput.value = '';
                btnAskAI.disabled = true;
                
                addMessage('user', message);
                typingIndicator.classList.add('show');
                
                try {
                    const formData = new FormData();
                    formData.append('prompt', message);
                    formData.append('conversation_history', JSON.stringify(conversationHistory.slice(-10)));
                    
                    // Add context
                    const periodeSemester = document.getElementById('periode_semester')?.value;
                    const templateId = document.getElementById('template_id')?.value;
                    if (periodeSemester) formData.append('periode_semester', periodeSemester);
                    if (templateId) formData.append('template_id', templateId);
                    
                    // Add files if any
                    if (fileReferensi && fileReferensi.files.length > 0) {
                        Array.from(fileReferensi.files).forEach((file, index) => {
                            formData.append(`file_referensi[${index}]`, file);
                        });
                    }
                    
                    const response = await fetch('/gjm/laporan-semester/ai-prompt', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: formData
                    });
                    
                    typingIndicator.classList.remove('show');
                    
                    if (!response.ok) {
                        throw new Error('Terjadi kesalahan saat memanggil AI service');
                    }
                    
                    const data = await response.json();
                    
                    if (!data.success) {
                        throw new Error(data.message || 'AI service returned error');
                    }
                    
                    addMessage('ai', data.response || 'Tidak ada response dari AI');
                    
                } catch (error) {
                    console.error('Error:', error);
                    typingIndicator.classList.remove('show');
                    addMessage('system', '<strong>Error:</strong> ' + error.message);
                } finally {
                    isProcessing = false;
                    btnAskAI.disabled = false;
                }
            }
            
            function addMessage(type, content) {
                const msgDiv = document.createElement('div');
                msgDiv.className = `msg-${type}`;
                
                if (type === 'user') {
                    msgDiv.innerHTML = `<div class="bubble">${escapeHtml(content)}</div>`;
                } else if (type === 'ai') {
                    msgDiv.innerHTML = `
                        <div class="ai-icon"><i class="bi bi-stars"></i></div>
                        <div class="bubble">${content}</div>
                    `;
                } else if (type === 'system') {
                    msgDiv.innerHTML = `
                        <div class="ai-icon"><i class="bi bi-info-circle"></i></div>
                        <div class="bubble" style="background: #fef3c7; border-color: #f59e0b;">${content}</div>
                    `;
                }
                
                messagesBox.appendChild(msgDiv);
                messagesBox.scrollTop = messagesBox.scrollHeight;
                
                if (type === 'user' || type === 'ai') {
                    conversationHistory.push({ type, content });
                }
            }
            
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        });
    </script>
@endsection
```

---

## Bagian 3: Update Controller - Laporan Artefak

### File: `app/Http/Controllers/GKM/LaporanArtefakController.php`

Tambahkan method baru:

```php
use App\Services\ClaudeAIService;
use Illuminate\Support\Facades\Log;

// ... existing code ...

/**
 * Handle AI prompt untuk generate laporan artefak
 */
public function aiPrompt(Request $request)
{
    try {
        $prompt = $request->input('prompt');
        $conversationHistory = json_decode($request->input('conversation_history', '[]'), true);
        
        // Get context from form
        $periode = $request->input('periode');
        $templateId = $request->input('template_id');
        
        // Build context string
        $contextParts = [];
        $contextParts[] = "Ini adalah request untuk membuat Laporan Artefak (RPS dan Materi Perkuliahan).";
        
        if ($periode) {
            $contextParts[] = "Periode: " . $periode;
        }
        
        if ($templateId) {
            $template = \App\Models\TemplateLaporan::find($templateId);
            if ($template) {
                $contextParts[] = "Template: " . $template->nama_template;
                if ($template->template_content) {
                    $contextParts[] = "Format Template:\n" . $template->template_content;
                }
            }
        }
        
        // Get monitoring data context if periode is selected
        if ($periode) {
            try {
                // Add monitoring RPS dan Materi data as context
                $monitoringData = $this->getMonitoringDataForPeriode($periode);
                if ($monitoringData) {
                    $contextParts[] = "Data Monitoring:\n" . $monitoringData;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to get monitoring data: ' . $e->getMessage());
            }
        }
        
        $context = implode("\n\n", $contextParts);
        
        // Call AI service
        $aiService = app(ClaudeAIService::class);
        $response = $aiService->generateLaporanWithConversation(
            $prompt,
            $context,
            $conversationHistory
        );
        
        return response()->json([
            'success' => true,
            'response' => $response,
            'cached' => false
        ]);
        
    } catch (\Exception $e) {
        Log::error('AI Prompt Error (Artefak): ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Layanan AI mengalami masalah: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Helper method to get monitoring data for specific periode
 */
private function getMonitoringDataForPeriode($periode)
{
    // Implement logic to fetch monitoring RPS & Materi data
    // Example implementation:
    
    $monitoringRPS = \App\Models\MonitoringSnapshot::where('periode', $periode)
        ->where('type', 'rps')
        ->get();
    
    $monitoringMateri = \App\Models\MonitoringSnapshot::where('periode', $periode)
        ->where('type', 'materi')
        ->get();
    
    $summary = "Total RPS: " . $monitoringRPS->count() . "\n";
    $summary .= "Total Materi: " . $monitoringMateri->count() . "\n";
    
    // Add more detailed statistics as needed
    
    return $summary;
}
```

---

## Bagian 4: Update Controller - Laporan Semester

### File: `app/Http/Controllers/GJM/LaporanSemesterController.php`

Tambahkan method (jika belum ada):

```php
use App\Services\ClaudeAIService;
use Illuminate\Support\Facades\Log;

/**
 * Handle AI prompt untuk generate laporan semester
 */
public function aiPrompt(Request $request)
{
    try {
        $prompt = $request->input('prompt');
        $conversationHistory = json_decode($request->input('conversation_history', '[]'), true);
        
        // Get context
        $periodeSemester = $request->input('periode_semester');
        $templateId = $request->input('template_id');
        
        // Build context
        $contextParts = [];
        $contextParts[] = "Ini adalah request untuk membuat Laporan Semester.";
        
        if ($periodeSemester) {
            $contextParts[] = "Periode: Semester " . ucfirst($periodeSemester);
        }
        
        if ($templateId) {
            $template = \App\Models\TemplateLaporan::find($templateId);
            if ($template && $template->template_content) {
                $contextParts[] = "Format Template:\n" . $template->template_content;
            }
        }
        
        $context = implode("\n\n", $contextParts);
        
        // Call AI service
        $aiService = app(ClaudeAIService::class);
        $response = $aiService->generateLaporanWithConversation(
            $prompt,
            $context,
            $conversationHistory
        );
        
        return response()->json([
            'success' => true,
            'response' => $response
        ]);
        
    } catch (\Exception $e) {
        Log::error('AI Prompt Error (Semester): ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Layanan AI mengalami masalah: ' . $e->getMessage()
        ], 500);
    }
}
```

---

## Bagian 5: Update Routes

### File: `routes/web.php`

Tambahkan routes berikut:

```php
// GKM - Laporan Artefak AI Prompt
Route::post('/gkm/laporan-artefak/ai-prompt', 
    [App\Http\Controllers\GKM\LaporanArtefakController::class, 'aiPrompt']
)->name('gkm.laporan-artefak.ai-prompt')->middleware('auth');

// GJM - Laporan Semester AI Prompt (jika belum ada)
Route::post('/gjm/laporan-semester/ai-prompt', 
    [App\Http\Controllers\GJM\LaporanSemesterController::class, 'aiPrompt']
)->name('gjm.laporan-semester.ai-prompt')->middleware('auth');
```

---

## Testing

### Test Laporan Artefak:
1. Buka `/gkm/laporan-artefak/create`
2. Pilih periode
3. Ketik instruksi di AI chat: "Buatkan outline laporan artefak untuk periode ini"
4. Klik tombol panah (↑)
5. Verifikasi response dari AI

### Test Laporan Semester:
1. Buka `/gjm/buat-laporan/semester-create`
2. Pilih periode semester
3. Ketik instruksi di AI chat: "Buatkan outline laporan semester"
4. Klik tombol panah (↑)
5. Verifikasi response dari AI

---

## Troubleshooting

### Problem: JavaScript error "Cannot read property of null"
**Solution**: Pastikan semua ID element sudah benar dan sesuai dengan yang ada di HTML

### Problem: AI response "API key tidak valid"
**Solution**: Cek file `.env` pastikan `CLAUDE_API_KEY` sudah diset dengan benar

### Problem: Error 404 pada route AI prompt
**Solution**: Jalankan `php artisan route:clear` dan `php artisan config:clear`

### Problem: File upload tidak berfungsi
**Solution**: Pastikan form memiliki attribute `enctype="multipart/form-data"`

---

## File yang Sudah Dibuat

- ✅ `public/js/ai-prompt-assistant-artefak.js`
- ✅ `scripts/add-ai-assistant-to-pages.ps1`
- ✅ `docs/AI_ASSISTANT_IMPLEMENTATION_GUIDE.md` (file ini)

---

## Kesimpulan

Dengan mengikuti panduan ini, Anda akan menambahkan fitur AI Assistant yang lengkap ke halaman Generate Laporan Artefak dan Semester, dengan fungsionalitas yang sama seperti pada halaman Generate Laporan Triwulan.

Fitur yang didapat:
- ✅ Chat interface dengan AI
- ✅ Conversation history
- ✅ File upload support
- ✅ Image upload dengan OCR (opsional)
- ✅ Context-aware responses
- ✅ Error handling
- ✅ Rate limiting awareness

---

**Last Updated**: 2026-06-02
**Version**: 1.0.0
