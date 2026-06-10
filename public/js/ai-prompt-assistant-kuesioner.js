/**
 * AI Prompt Assistant untuk Laporan Kuesioner
 * Menyediakan fitur AI assistant dengan conversation history untuk membantu pengguna dalam membuat laporan kuesioner
 * Version: 1.2.0 - With Context Validation & Auto-Download Feature
 * Last Updated: 2026-06-03
 */

document.addEventListener('DOMContentLoaded', function () {
    const promptInput = document.getElementById('ai-prompt-input');
    const btnAskAI = document.getElementById('btn-ask-ai');
    const messagesBox = document.getElementById('ai-messages');
    const typingIndicator = document.getElementById('typing-indicator');
    const aiPreviewData = document.getElementById('ai_preview_data');
    const btnAttachment = document.getElementById('btn-attachment');
    const fileReferensi = document.getElementById('file_referensi_kuesioner');
    const ocrImages = document.getElementById('ocr_images_kuesioner');
    const allAttachmentsDisplay = document.getElementById('all-attachments-display');
    const allAttachmentsList = document.getElementById('all-attachments-list');
    const ocrProcessingStatus = document.getElementById('ocr-processing-status');

    let conversationHistory = [];
    let isProcessing = false;
    let maxConversationHistory = 10;
    let maxConversationTurns = 15;

    // ================================================================
    // VALIDASI KONTEKS LAPORAN KUESIONER - FRONTEND
    // ================================================================

    // Keyword yang DIIZINKAN (wajib ada minimal 1)
    const allowedKeywords = [
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

    // Keyword yang DILARANG (jika muncul tanpa allowed keyword)
    const rejectedKeywords = [
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
    const suspiciousShortPatterns = [
        /^(siapa|siapakah|apa|kenapa|mengapa|bagaimana|kapan|dimana|kemana)(\s+(paling|yang|itu|ini|dong|nih|sih|ya|kah))?$/i,
        /^(halo|hai|hey|hello|hi|yo|haii|hallo|helo)$/i,
        /^(apa kabar|kabar|gimana kabar|gmn kabar)$/i,
        /^(ngobrol|chat|yuk ngobrol|yuk chat)$/i,
        /^(kenalan|perkenalkan|kenal|mari kenalan)$/i,
        /^(ganteng|cantik|tampan|cakep|jelek|buruk)$/i,
        /^(danbel|dumbell|gym|fitness|olahraga)$/i,
        /^(hewan|binatang|kucing|anjing)$/i,
        /^(film|game|musik|lagu)$/i,
        /^(resep|masak|makanan)$/i,
        /^(berita|politik|news)$/i,
        /^(joke|lelucon|pantun|puisi)$/i,
        /^(cuaca|ramalan|zodiac)$/i,
    ];

    // Add welcome message with context warning
    addMessage('ai', `<div style="background: #e0f2fe; border-left: 4px solid #0284c7; padding: 16px; border-radius: 8px; margin-bottom: 16px;">
            <strong>🤖 Selamat datang di AI Assistant Laporan Kuesioner Kepuasan Mahasiswa!</strong><br><br>
            Saya adalah asisten khusus untuk membantu Anda membuat laporan kuesioner kepuasan mahasiswa.
        </div>

        <p><strong>✅ Saya dapat membantu Anda dengan:</strong></p>
        <ul>
            <li>📋 Pembuatan laporan kuesioner kepuasan mahasiswa</li>
            <li>📊 Analisis data kuesioner kepuasan mahasiswa</li>
            <li>📝 Struktur dan format laporan kuesioner</li>
            <li>✏️ Perbaikan dan revisi draft laporan</li>
            <li>❓ Pertanyaan terkait kuesioner dan survei kepuasan</li>
        </ul>

        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; border-radius: 8px; margin: 12px 0;">
            <strong>⚠️ PENTING!</strong><br>
            Saya HANYA bisa membantu dengan topik yang berkaitan dengan LAPORAN KUESIONER KEPUASAN MAHASISWA.<br>
            Pertanyaan di luar konteks ini (seperti siapa, kabar, ganteng, hewan, game, dll) akan otomatis ditolak.
        </div>

        <p><strong>💡 Contoh instruksi yang tepat:</strong></p>
        <ul>
            <li>"Buat laporan kuesioner untuk periode ini"</li>
            <li>"Analisis data kuesioner kepuasan mahasiswa"</li>
            <li>"Tampilkan hasil kuesioner per tingkat"</li>
            <li>"Ubah bagian ringkasan eksekutif"</li>
        </ul>

        <p><strong>🚫 Contoh yang akan ditolak:</strong></p>
        <ul>
            <li>"Siapa kamu?" / "Apa kabar?"</li>
            <li>"Ceritakan tentang kucing"</li>
            <li>"Gimana cara main game?"</li>
            <li>"Resep masakan enak"</li>
        </ul>

        <p>Silakan mulai dengan mengisi informasi laporan (periode, judul) lalu klik <strong>"Buat Laporan Draft"</strong> terlebih dahulu.</p>`);

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

    // File selection handlers
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

    // Send button click
    if (btnAskAI) {
        btnAskAI.addEventListener('click', sendMessage);
    }

    // Enter key to send
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
                addMessage('system', '<strong>⚠️ INSTRUKSI WAJIB DIISI!</strong><br><br>Anda telah mengupload file, tetapi belum memberikan instruksi.<br><br><strong>Silakan ketik instruksi Anda terlebih dahulu</strong>, misalnya:<br>• "Buat laporan kuesioner untuk periode ini"<br>• "Analisis data kuesioner kepuasan mahasiswa"');
            } else {
                addMessage('system', 'Silakan masukkan instruksi atau pertanyaan Anda.');
            }
            return;
        }

        if (message.length < 5) {
            addMessage('system', '<strong>⚠️ Instruksi terlalu singkat!</strong><br><br>Silakan berikan instruksi yang lebih jelas dan spesifik (minimal 5 karakter).');
            return;
        }

        const messageLower = message.toLowerCase();

        // Cek apakah ada rejected keyword tanpa allowed keyword
        const hasRejectedKeyword = rejectedKeywords.some(keyword => messageLower.includes(keyword));
        const hasAllowedKeyword = allowedKeywords.some(keyword => messageLower.includes(keyword));

        // Cek pola pertanyaan singkat yang mencurigakan
        let isSuspiciousShort = false;
        for (const pattern of suspiciousShortPatterns) {
            if (pattern.test(message.trim())) {
                isSuspiciousShort = true;
                break;
            }
        }

        // Jika ada keyword terlarang DAN tidak ada keyword yang diizinkan → TOLAK
        if (hasRejectedKeyword && !hasAllowedKeyword) {
            addMessage('system',
                '<div style="background: #fef2f2; border-left: 4px solid #dc2626; padding: 12px; border-radius: 8px;">' +
                '<strong>⚠️ Maaf, permintaan Anda di luar konteks!</strong><br><br>' +
                'Saya adalah <strong>AI Assistant Laporan Kuesioner Kepuasan Mahasiswa</strong>.<br><br>' +
                '<strong>Saya hanya dapat membantu dengan:</strong><br>' +
                '✅ Pembuatan laporan kuesioner kepuasan mahasiswa<br>' +
                '✅ Analisis data kuesioner kepuasan mahasiswa<br>' +
                '✅ Struktur dan format laporan kuesioner<br>' +
                '✅ Perbaikan dan revisi draft laporan<br><br>' +
                'Silakan ajukan pertanyaan yang terkait dengan <strong>Laporan Kuesioner</strong>.' +
                '</div>'
            );
            promptInput.value = '';
            return;
        }

        // Jika pola pertanyaan singkat yang mencurigakan dan tidak ada allowed keyword → TOLAK
        if (isSuspiciousShort && !hasAllowedKeyword) {
            addMessage('system',
                '<div style="background: #fef2f2; border-left: 4px solid #dc2626; padding: 12px; border-radius: 8px;">' +
                '<strong>⚠️ Maaf, permintaan Anda di luar konteks!</strong><br><br>' +
                'Saya adalah AI Assistant khusus untuk membantu membuat <strong>Laporan Kuesioner Kepuasan Mahasiswa</strong>.<br><br>' +
                'Silakan ajukan pertanyaan terkait pembuatan laporan kuesioner.' +
                '</div>'
            );
            promptInput.value = '';
            return;
        }

        // Cek pesan terlalu pendek (< 10 karakter) tanpa file
        if (message.length < 10 && !hasFiles && !hasAllowedKeyword) {
            addMessage('system',
                '<div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; border-radius: 8px;">' +
                '<strong>⚠️ Instruksi terlalu singkat!</strong><br><br>' +
                'Silakan berikan instruksi yang lebih spesifik terkait pembuatan <strong>Laporan Kuesioner Kepuasan Mahasiswa</strong>, misalnya:<br>' +
                '• "Buat laporan kuesioner untuk periode ini"<br>' +
                '• "Analisis data kuesioner kepuasan mahasiswa"<br>' +
                '• "Tampilkan hasil kuesioner per tingkat"' +
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
        promptInput.value = '';
        btnAskAI.disabled = true;

        // Add user message
        addMessage('user', message, hasFiles);

        // Show typing indicator
        if (typingIndicator) {
            typingIndicator.classList.add('show');
        }
        scrollToBottom();

        try {
            // Prepare form data
            const formData = new FormData();
            formData.append('prompt', message);

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

            if (periode) formData.append('periode', periode);
            if (templateId) formData.append('template_id', templateId);

            // Extract tipe_laporan from periode value (format: "ID-UTS" or "ID-UAS")
            let tipeLaporan = 'UTS';
            if (periode && periode.includes('-')) {
                const parts = periode.split('-');
                if (parts.length === 2 && (parts[1] === 'UTS' || parts[1] === 'UAS')) {
                    tipeLaporan = parts[1];
                }
            }
            formData.append('tipe_laporan', tipeLaporan);

            // DEBUG: Log what's being sent
            console.log('[AI Prompt Debug]', {
                prompt: message,
                periode: periode,
                tipe_laporan: tipeLaporan,
                template_id: templateId,
                message_length: message.length,
                has_allowed_keywords: allowedKeywords.some(kw => message.toLowerCase().includes(kw)),
                has_rejected_keywords: rejectedKeywords.some(kw => message.toLowerCase().includes(kw))
            });

            // Call AI service
            const response = await fetch('/gkm/laporan-kuesioner/ai-prompt', {
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
            addMessage('ai', data.response || 'Tidak ada response dari AI');

            // ===== AUTO-DOWNLOAD FEATURE =====
            // Jika auto_generated = true, otomatis trigger download
            if (data.auto_generated && data.download_url) {
                console.log('Auto-generated laporan detected. Auto-downloading...');

                // Show download notification
                addMessage('system', `<i class="bi bi-download"></i> <strong>File Word siap didownload!</strong><br>Klik tombol di bawah untuk mendownload:<br><br><a href="${data.download_url}" class="btn btn-sm btn-success" download><i class="bi bi-download"></i> Download Laporan Word</a>`);

                // Trigger auto-download after 1 second
                setTimeout(() => {
                    const downloadLink = document.createElement('a');
                    downloadLink.href = data.download_url;
                    downloadLink.download = '';
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);

                    console.log('Auto-download triggered successfully');

                    // Show success toast/notification
                    showSuccessToast('File Word berhasil di-generate dan sedang didownload ke perangkat Anda!');
                }, 1000);
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

        // Add to conversation history
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

    function showSuccessToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: slideInRight 0.3s ease;
        `;
        toast.innerHTML = `<i class="bi bi-check-circle"></i> ${message}`;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    document.body.removeChild(toast);
                }
            }, 300);
        }, 3000);

        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }

    console.log('AI Prompt Assistant Kuesioner initialized with context validation and auto-download feature');
});
