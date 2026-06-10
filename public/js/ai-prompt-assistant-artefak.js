/**
 * AI Prompt Assistant untuk Laporan Artefak
 * Menyediakan fitur AI assistant dengan conversation history untuk membantu pengguna dalam membuat laporan artefak
 * Version: 2.1.0 - Fixed context validation with positive keyword priority
 * Last Updated: 2026-06-09
 */

console.log('🚀 AI Prompt Assistant Artefak v2.1.0 loaded');

document.addEventListener('DOMContentLoaded', function () {
    const promptInput = document.getElementById('ai-prompt-input');
    const btnAskAI = document.getElementById('btn-ask-ai');
    const messagesBox = document.getElementById('ai-messages');
    const typingIndicator = document.getElementById('typing-indicator');
    const aiPreviewData = document.getElementById('ai_preview_data');
    const btnAttachment = document.getElementById('btn-attachment');
    const fileReferensi = document.getElementById('file_referensi_artefak');
    const ocrImages = document.getElementById('ocr_images_artefak');
    const allAttachmentsDisplay = document.getElementById('all-attachments-display');
    const allAttachmentsList = document.getElementById('all-attachments-list');
    const ocrProcessingStatus = document.getElementById('ocr-processing-status');

    let conversationHistory = [];
    let isProcessing = false;
    let maxConversationHistory = 15;  // Increased untuk menampung konteks laporan
    let maxConversationTurns = 20;

    // ================================================================
    // VALIDASI KONTEKS LAPORAN BULANAN (ARTEFAK) - FRONTEND
    // ================================================================

    // Keywords yang mengindikasikan KONTEKS VALID tentang laporan (prioritas tinggi)
    const laporanContextKeywords = [
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
    const clearlyOffTopicKeywords = [
        // Identitas personal
        'siapa kamu', 'namamu', 'nama kamu', 'kamu siapa',
        'siapa saya', 'namaku', 'nama saya', 'aku siapa',

        // Socializing yang jelas
        'kenalan yuk', 'mari berkenalan', 'mau kenalan',

        // Topik yang sangat tidak relevan
        'resep masakan', 'cara memasak', 'tutorial memasak',
        'film terbaru', 'game online', 'musik populer',
        'olahraga favorit', 'gym workout', 'fitness tips',
        'hewan peliharaan', 'merawat kucing', 'merawat anjing',
        'ramalan bintang', 'zodiac hari ini', 'horoskop',
        'berita politik', 'berita olahraga', 'berita entertainment',
        'cerita lucu', 'joke terbaru', 'pantun lucu',
    ];

    // Pola pertanyaan singkat yang PASTI tidak relevan (sangat spesifik)
    const clearlyOffTopicPatterns = [
        /^(halo|hai|hey|hello|hi|yo|haii|hallo|helo)(\s+saja)?$/i,
        /^(apa kabar|gimana kabar|gmn kabar)(\?)?$/i,
        /^(siapa (kamu|anda|saya|aku))(\?)?$/i,
        /^(nama (kamu|anda|saya|aku))(\?)?$/i,
        /^(kenalan|perkenalkan|kenal)(\s+(yuk|dong))?$/i,
        /^(cerita|dongeng|joke|lelucon|pantun|puisi)(\s+(dong|yuk))?$/i,
        /^(resep|masak|makanan|minuman)(\s+(apa|dong))?$/i,
        /^(cuaca|ramalan|zodiac|horoskop)(\s+(hari ini))?$/i,
    ];

    // Add welcome message with context warning and revision info
    addMessage('ai', `<div style="background: #e0f2fe; border-left: 4px solid #0284c7; padding: 16px; border-radius: 8px; margin-bottom: 16px;">
            <strong>🤖 Selamat datang di AI Assistant Laporan Bulanan (Artefak RPS & Materi)!</strong><br><br>
            Saya siap membantu Anda membuat dan merevisi laporan bulanan monitoring RPS dan Materi perkuliahan.
        </div>

        <p><strong>✅ Saya dapat membantu Anda dengan:</strong></p>
        <ul>
            <li>📋 Pembuatan laporan artefak RPS dan Materi</li>
            <li>📊 Analisis data monitoring RPS dan Materi</li>
            <li>📝 Struktur dan format laporan artefak</li>
            <li>✏️ <strong>Perbaikan dan revisi draft laporan</strong> (ubah bagian tertentu, tambah konten, hapus bagian)</li>
            <li>❓ Pertanyaan terkait RPS dan Materi perkuliahan</li>
        </ul>

        <div style="background: #dbeafe; border-left: 4px solid #2563eb; padding: 12px; border-radius: 8px; margin: 12px 0;">
            <strong>🔄 FITUR REVISI LAPORAN!</strong><br>
            Setelah draft laporan dibuat, Anda dapat langsung chat dengan AI untuk melakukan perbaikan:<br>
            • "Perbaiki bagian kesimpulan"<br>
            • "Ubah data pada tabel RPS"<br>
            • "Tambah rekomendasi tentang monitoring berkala"<br>
            • "Hapus bagian hambatan yang tidak relevan"
        </div>

        <p><strong>💡 Contoh instruksi yang tepat:</strong></p>
        <ul>
            <li>"Buat laporan artefak untuk periode ini"</li>
            <li>"Analisis data RPS dan Materi"</li>
            <li>"Perbaiki BAB 1 Pendahuluan"</li>
            <li>"Tambah tabel rekomendasi di bagian akhir"</li>
            <li>"Ubah statistik pada ringkasan eksekutif"</li>
        </ul>

        <div style="background: #dcfce7; border-left: 4px solid #16a34a; padding: 12px; border-radius: 8px; margin: 12px 0;">
            <strong>✨ CARA MENGGUNAKAN:</strong><br>
            1. Isi informasi laporan (periode, judul)<br>
            2. Klik <strong>"Buat Laporan Draft"</strong><br>
            3. Setelah draft dibuat, chat dengan AI untuk instruksi atau perbaikan<br>
            4. AI akan merespons sesuai permintaan Anda
        </div>

        <p><em>Catatan: AI assistant ini difokuskan untuk membantu pembuatan laporan artefak. Pertanyaan di luar konteks laporan mungkin tidak dapat dijawab.</em></p>`);

    // Attachment button click handler
    if (btnAttachment) {
        btnAttachment.addEventListener('click', function () {
            const menu = document.createElement('div');
            menu.className = 'attachment-menu';
            menu.style.cssText = `
                position: absolute;
                bottom: 100%;
                left: 0;
                background: white;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                padding: 0.5rem 0;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                z-index: 1000;
                margin-bottom: 0.5rem;
                min-width: 200px;
            `;
            menu.innerHTML = `
                <button type="button" class="attachment-menu-item" data-type="file">
                    <i class="bi bi-file-earmark"></i> Upload File (DOCX, PDF, Excel)
                </button>
                <button type="button" class="attachment-menu-item" data-type="image">
                    <i class="bi bi-image"></i> Upload Gambar (JPG, PNG)
                </button>
            `;

            const styleSheet = document.createElement('style');
            styleSheet.textContent = `
                .attachment-menu-item {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    width: 100%;
                    padding: 0.5rem 1rem;
                    border: none;
                    background: white;
                    text-align: left;
                    font-size: 0.875rem;
                    color: #374151;
                    cursor: pointer;
                    transition: background 0.2s;
                }
                .attachment-menu-item:hover {
                    background: #f3f4f6;
                }
            `;
            document.head.appendChild(styleSheet);

            const existingMenu = document.querySelector('.attachment-menu');
            if (existingMenu) existingMenu.remove();

            btnAttachment.parentElement.appendChild(menu);

            menu.querySelectorAll('.attachment-menu-item').forEach(item => {
                item.addEventListener('click', function () {
                    const type = this.getAttribute('data-type');
                    if (type === 'file') {
                        fileReferensi.click();
                    } else if (type === 'image') {
                        ocrImages.click();
                    }
                    menu.remove();
                });
            });

            setTimeout(() => {
                document.addEventListener('click', function closeMenu(e) {
                    if (!menu.contains(e.target) && e.target !== btnAttachment) {
                        menu.remove();
                        document.removeEventListener('click', closeMenu);
                    }
                });
            }, 10);
        });
    }

    if (fileReferensi) {
        fileReferensi.addEventListener('change', function (e) {
            displayAttachments();
            updateAttachmentButtonState();
        });
    }

    if (ocrImages) {
        ocrImages.addEventListener('change', function (e) {
            displayAttachments();
            updateAttachmentButtonState();
        });
    }

    if (btnAskAI) {
        btnAskAI.addEventListener('click', sendMessage);
    }

    if (promptInput) {
        promptInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }

    function displayAttachments() {
        if (!allAttachmentsList) return;

        allAttachmentsList.innerHTML = '';
        let hasAttachments = false;

        if (fileReferensi && fileReferensi.files.length > 0) {
            Array.from(fileReferensi.files).forEach((file, index) => {
                hasAttachments = true;
                const chip = createFileChip(file, 'file', index);
                allAttachmentsList.appendChild(chip);
            });
        }

        if (ocrImages && ocrImages.files.length > 0) {
            Array.from(ocrImages.files).forEach((file, index) => {
                hasAttachments = true;
                const chip = createImageChip(file, 'image', index);
                allAttachmentsList.appendChild(chip);
            });
        }

        if (allAttachmentsDisplay) {
            allAttachmentsDisplay.style.display = hasAttachments ? 'block' : 'none';
        }
    }

    function createFileChip(file, type, index) {
        const chip = document.createElement('div');
        chip.className = 'file-chip';
        chip.innerHTML = `
            <i class="bi bi-file-earmark"></i>
            <span class="file-name">${escapeHtml(file.name)}</span>
            <span class="remove-file" data-type="${type}" data-index="${index}">×</span>
        `;

        chip.querySelector('.remove-file').addEventListener('click', function () {
            removeAttachment(type, index);
        });

        return chip;
    }

    function createImageChip(file, type, index) {
        const chip = document.createElement('div');
        chip.className = 'file-chip image-chip';

        const reader = new FileReader();
        reader.onload = function (e) {
            chip.innerHTML = `
                <img src="${e.target.result}" alt="${escapeHtml(file.name)}">
                <span class="file-name">${escapeHtml(file.name)}</span>
                <span class="remove-file" data-type="${type}" data-index="${index}">×</span>
            `;

            chip.querySelector('.remove-file').addEventListener('click', function () {
                removeAttachment(type, index);
            });
        };
        reader.readAsDataURL(file);

        return chip;
    }

    function removeAttachment(type, index) {
        const input = type === 'file' ? fileReferensi : ocrImages;
        if (!input) return;

        const dt = new DataTransfer();

        Array.from(input.files).forEach((file, i) => {
            if (i !== index) dt.items.add(file);
        });

        input.files = dt.files;
        displayAttachments();
        updateAttachmentButtonState();
    }

    function updateAttachmentButtonState() {
        if (!btnAttachment) return;

        const hasFiles = (fileReferensi && fileReferensi.files.length > 0) ||
            (ocrImages && ocrImages.files.length > 0);
        if (hasFiles) {
            btnAttachment.classList.add('has-files');
        } else {
            btnAttachment.classList.remove('has-files');
        }
    }

    // Fungsi untuk mengecek apakah ini permintaan revisi
    function isRevisionRequest(message) {
        const revisionKeywords = [
            'perbaiki', 'ubah', 'edit', 'revisi', 'revisikan', 'ganti',
            'tambah', 'hapus', 'update', 'perbaharui', 'koreksi', 'betulkan',
            'tolong perbaiki', 'tolong ubah', 'tolong revisi', 'revisi bagian',
            'ubah bagian', 'perbaiki bagian', 'tambah di', 'hapus bagian',
            'perbaiki bab', 'ubah bab', 'revisi bab'
        ];
        const lowerMessage = message.toLowerCase();
        return revisionKeywords.some(keyword => lowerMessage.includes(keyword));
    }

    async function sendMessage() {
        if (isProcessing) return;

        const message = promptInput.value.trim();
        const hasFiles = (fileReferensi && fileReferensi.files.length > 0) ||
            (ocrImages && ocrImages.files.length > 0);

        // ================================================================
        // VALIDASI KONTEKS - FRONTEND
        // ================================================================

        if (!message || message.length === 0) {
            if (hasFiles) {
                addMessage('system', '<strong>⚠️ INSTRUKSI WAJIB DIISI!</strong><br><br>Anda telah mengupload file, tetapi belum memberikan instruksi.<br><br><strong>Silakan ketik instruksi Anda terlebih dahulu</strong>, misalnya:<br>• "Buat laporan artefak untuk periode ini"<br>• "Analisis data RPS dan Materi"<br>• "Perbaiki bagian kesimpulan"');
            } else {
                addMessage('system', 'Silakan masukkan instruksi atau pertanyaan Anda.');
            }
            return;
        }

        if (message.length < 5) {
            console.log('❌ Message too short:', message.length, 'chars');
            addMessage('system', '<strong>⚠️ Instruksi terlalu singkat!</strong><br><br>Silakan berikan instruksi yang lebih jelas dan spesifik (minimal 5 karakter).');
            return;
        }

        const messageLower = message.toLowerCase();
        const messageTrimmed = message.trim();

        console.log('🔍 Checking prompt:', message);
        console.log('📝 Lowercase:', messageLower);

        // Cek apakah user sudah punya draft laporan (lebih permisif jika sudah ada draft)
        const hasDraftLaporan = (aiPreviewData && aiPreviewData.value && aiPreviewData.value.length > 0) ||
            (document.getElementById('laporan_id') && document.getElementById('laporan_id').value);

        // Cek apakah ini permintaan revisi
        const isRevision = isRevisionRequest(message);

        // CEK PRIORITAS: Apakah ada konteks laporan yang valid?
        let hasLaporanContext = false;
        for (const keyword of laporanContextKeywords) {
            if (messageLower.includes(keyword)) {
                hasLaporanContext = true;
                console.log('✅ Valid laporan context detected:', keyword);
                break;
            }
        }

        // SPECIAL CHECK: Apakah ini permintaan "Buatkan Laporan Bulanan" yang jelas?
        const explicitLaporanKeywords = ['buatkan laporan', 'buat laporan', 'generate laporan', 'buatkan laporan bulanan', 'buat laporan bulanan', 'generate laporan bulanan'];
        let isExplicitLaporanRequest = false;
        for (const keyword of explicitLaporanKeywords) {
            if (messageLower.includes(keyword)) {
                isExplicitLaporanRequest = true;
                console.log('✅ EXPLICIT Laporan Bulanan request detected:', keyword);
                break;
            }
        }

        // JIKA ADA KONTEKS LAPORAN YANG VALID ATAU INI EXPLICIT LAPORAN REQUEST, LANGSUNG IZINKAN
        if (hasLaporanContext || isExplicitLaporanRequest) {
            // Langsung proceed ke AI, skip validasi off-topic
            console.log('✅ BYPASSING off-topic validation - Valid laporan context or explicit request found');
        } else {
            // Cek apakah ada keyword yang JELAS di luar topik
            let hasClearlyOffTopicKeyword = false;
            for (const keyword of clearlyOffTopicKeywords) {
                if (messageLower.includes(keyword)) {
                    hasClearlyOffTopicKeyword = true;
                    break;
                }
            }

            // Cek pola pertanyaan yang PASTI tidak relevan
            let matchesClearlyOffTopicPattern = false;
            for (const pattern of clearlyOffTopicPatterns) {
                if (pattern.test(messageTrimmed)) {
                    matchesClearlyOffTopicPattern = true;
                    break;
                }
            }

            // HANYA TOLAK jika memenuhi kriteria yang SANGAT JELAS di luar topik:
            // 1. Ada keyword/pattern yang jelas off-topic
            // 2. DAN tidak sedang melakukan revisi
            // 3. DAN belum ada draft laporan
            if ((hasClearlyOffTopicKeyword || matchesClearlyOffTopicPattern) && !isRevision && !hasDraftLaporan) {
                addMessage('system',
                    '<div style="background: #fef2f2; border-left: 4px solid #dc2626; padding: 12px; border-radius: 8px;">' +
                    '<strong>⚠️ Maaf, pertanyaan Anda di luar konteks!</strong><br><br>' +
                    'Saya adalah <strong>AI Assistant Laporan Bulanan (RPS dan Materi)</strong>.<br><br>' +
                    '<strong>Saya dapat membantu dengan:</strong><br>' +
                    '✅ Pembuatan laporan artefak RPS dan Materi<br>' +
                    '✅ Analisis data monitoring RPS dan Materi<br>' +
                    '✅ Struktur dan format laporan artefak<br>' +
                    '✅ Perbaikan dan revisi draft laporan<br><br>' +
                    'Silakan ajukan pertanyaan yang terkait dengan <strong>Laporan Bulanan (Artefak)</strong>.' +
                    '</div>'
                );
                promptInput.value = '';
                return;
            }
        }

        // Validasi pesan terlalu pendek HANYA untuk kasus yang sangat ekstrem (< 3 karakter)
        if (message.length < 3 && !hasFiles) {
            addMessage('system',
                '<div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; border-radius: 8px;">' +
                '<strong>⚠️ Instruksi terlalu singkat!</strong><br><br>' +
                'Silakan berikan instruksi yang lebih jelas.' +
                '</div>'
            );
            promptInput.value = '';
            return;
        }

        // Check conversation length
        if (conversationHistory.length >= maxConversationTurns) {
            addMessage('system', `<strong>Percakapan terlalu panjang!</strong><br>Anda telah melakukan ${conversationHistory.length} percakapan. Untuk performa optimal, refresh halaman dan mulai percakapan baru.`);
            return;
        }

        isProcessing = true;
        const userMessage = message;
        promptInput.value = '';
        btnAskAI.disabled = true;

        // Add user message
        addMessage('user', userMessage, hasFiles);

        // Show typing indicator
        if (typingIndicator) {
            typingIndicator.classList.add('show');
        }
        scrollToBottom();

        try {
            const formData = new FormData();
            formData.append('prompt', userMessage);

            // Add conversation history
            const historyToSend = conversationHistory.slice(-maxConversationHistory);
            formData.append('conversation_history', JSON.stringify(historyToSend));

            // Add files
            if (fileReferensi && fileReferensi.files.length > 0) {
                Array.from(fileReferensi.files).forEach((file, index) => {
                    formData.append(`file_referensi[${index}]`, file);
                });
            }

            if (ocrImages && ocrImages.files.length > 0) {
                Array.from(ocrImages.files).forEach((file, index) => {
                    formData.append(`ocr_images[${index}]`, file);
                });
            }

            // Add context
            const periode = document.getElementById('periode')?.value;
            const templateId = document.getElementById('template_id')?.value;
            const laporanId = document.getElementById('laporan_id')?.value;

            if (periode) formData.append('periode', periode);
            if (templateId) formData.append('template_id', templateId);
            if (laporanId) formData.append('laporan_id', laporanId);

            // Call AI service
            const response = await fetch('/gkm/laporan-artefak/ai-prompt', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: formData
            });

            if (typingIndicator) {
                typingIndicator.classList.remove('show');
            }

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Terjadi kesalahan saat memanggil AI service');
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'AI service returned error');
            }

            // Add AI response
            let responseText = data.response || 'Tidak ada response dari AI';

            // Jika ini adalah response revisi, tambahkan badge
            if (data.is_revision || isRevisionRequest(userMessage)) {
                responseText = `<div style="background: #dbeafe; border-left: 4px solid #2563eb; padding: 8px 12px; border-radius: 8px; margin-bottom: 16px;">
                        <strong>🔄 Revisi Laporan</strong><br>
                        Laporan telah diperbaiki sesuai permintaan Anda.
                    </div>
                    ${responseText}`;
            }

            addMessage('ai', responseText);

            // Simpan AI preview data ke hidden field jika ada
            if (data.ai_preview) {
                aiPreviewData.value = data.ai_preview;
            }

            // Clear file inputs after successful send
            if (fileReferensi) fileReferensi.value = '';
            if (ocrImages) ocrImages.value = '';
            displayAttachments();
            updateAttachmentButtonState();

            // Show warning if approaching limit
            if (conversationHistory.length >= maxConversationTurns - 3) {
                addMessage('system', `<i class="bi bi-info-circle"></i> Anda sudah melakukan ${conversationHistory.length} percakapan. Pertimbangkan untuk refresh halaman setelah ${maxConversationTurns - conversationHistory.length} pesan lagi.`);
            }

        } catch (error) {
            console.error('Error:', error);
            if (typingIndicator) {
                typingIndicator.classList.remove('show');
            }

            let errorMessage = '<strong>Error:</strong> ';
            const errorStr = error.message || String(error);

            if (errorStr.includes('Rate limit') || errorStr.includes('429')) {
                errorMessage += 'Layanan AI sedang sibuk. Silakan tunggu 1-2 menit dan coba lagi.';
            } else if (errorStr.includes('token') || errorStr.includes('context_length')) {
                errorMessage += 'Percakapan terlalu panjang. Silakan refresh halaman dan mulai baru.';
            } else if (errorStr.includes('API key')) {
                errorMessage += 'Konfigurasi API tidak valid. Silakan hubungi administrator.';
            } else if (errorStr.includes('timeout')) {
                errorMessage += 'Request timeout. Silakan coba lagi.';
            } else if (errorStr.includes('network') || errorStr.includes('Failed to fetch')) {
                errorMessage += 'Masalah koneksi jaringan. Silakan periksa koneksi internet Anda.';
            } else {
                errorMessage += 'Terjadi kesalahan. Silakan coba lagi.';
            }

            addMessage('system', errorMessage);

        } finally {
            isProcessing = false;
            btnAskAI.disabled = false;
        }
    }

    function addMessage(type, content, hasAttachments = false) {
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

        if (messagesBox) {
            messagesBox.appendChild(msgDiv);
            scrollToBottom();
        }

        // Add to conversation history (hanya untuk user dan AI, tidak untuk system)
        if (type === 'user' || type === 'ai') {
            conversationHistory.push({
                role: type === 'user' ? 'user' : 'assistant',
                content: content
            });
        }
    }

    function scrollToBottom() {
        if (messagesBox) {
            messagesBox.scrollTop = messagesBox.scrollHeight;
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    console.log('AI Prompt Assistant Artefak initialized with context validation and revision support (v2.0.0)');
});
