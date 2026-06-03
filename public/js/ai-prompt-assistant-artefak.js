/**
 * AI Prompt Assistant untuk Laporan Artefak
 * Menyediakan fitur AI assistant dengan conversation history untuk membantu pengguna dalam membuat laporan artefak
 * Version: 1.0.0
 * Last Updated: 2026-06-02
 */

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
    let maxConversationHistory = 10;
    let maxConversationTurns = 15;

    // Add welcome message
    addMessage('ai', `<p>Halo! Saya AI Assistant untuk membantu Anda membuat laporan artefak. Saya dapat membantu dengan:</p>
        <ul>
            <li>Memberikan saran konten laporan artefak RPS dan Materi</li>
            <li>Menganalisis data monitoring dalam periode yang dipilih</li>
            <li>Membantu struktur laporan yang sesuai standar</li>
            <li>Memberikan template dan format yang tepat</li>
            <li>Melakukan iterasi dan perbaikan draft</li>
        </ul>
        <p><strong>Tips:</strong> Anda bisa mengatakan "ubah bagian X" atau "perbaiki Y" untuk melakukan revisi!</p>`);

    // Attachment button click handler
    if (btnAttachment) {
        btnAttachment.addEventListener('click', function () {
            // Show menu untuk pilih file type
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

            // Style menu items
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

            // Remove existing menu if any
            const existingMenu = document.querySelector('.attachment-menu');
            if (existingMenu) existingMenu.remove();

            // Add menu to DOM
            btnAttachment.parentElement.appendChild(menu);

            // Handle menu item clicks
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

            // Close menu when clicking outside
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
        allAttachmentsList.innerHTML = '';
        let hasAttachments = false;

        // Display file referensi
        if (fileReferensi.files.length > 0) {
            Array.from(fileReferensi.files).forEach((file, index) => {
                hasAttachments = true;
                const chip = createFileChip(file, 'file', index);
                allAttachmentsList.appendChild(chip);
            });
        }

        // Display OCR images
        if (ocrImages.files.length > 0) {
            Array.from(ocrImages.files).forEach((file, index) => {
                hasAttachments = true;
                const chip = createImageChip(file, 'image', index);
                allAttachmentsList.appendChild(chip);
            });
        }

        allAttachmentsDisplay.style.display = hasAttachments ? 'block' : 'none';
    }

    function createFileChip(file, type, index) {
        const chip = document.createElement('div');
        chip.className = 'file-chip';
        chip.innerHTML = `
            <i class="bi bi-file-earmark"></i>
            <span class="file-name">${file.name}</span>
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
                <img src="${e.target.result}" alt="${file.name}">
                <span class="file-name">${file.name}</span>
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
        const dt = new DataTransfer();

        Array.from(input.files).forEach((file, i) => {
            if (i !== index) dt.items.add(file);
        });

        input.files = dt.files;
        displayAttachments();
        updateAttachmentButtonState();
    }

    function updateAttachmentButtonState() {
        const hasFiles = fileReferensi.files.length > 0 || ocrImages.files.length > 0;
        if (hasFiles) {
            btnAttachment.classList.add('has-files');
        } else {
            btnAttachment.classList.remove('has-files');
        }
    }

    async function sendMessage() {
        if (isProcessing) return;

        const message = promptInput.value.trim();
        const hasFiles = fileReferensi.files.length > 0 || ocrImages.files.length > 0;

        // Validation
        if (!message || message.length === 0) {
            if (hasFiles) {
                addMessage('system', '<strong>⚠️ INSTRUKSI WAJIB DIISI!</strong><br><br>Anda telah mengupload file, tetapi belum memberikan instruksi.<br><br><strong>Silakan ketik instruksi Anda terlebih dahulu</strong>, misalnya:<br>• "Analisis dokumen ini dan buat ringkasan"<br>• "Buat laporan berdasarkan data yang diupload"<br>• "Ekstrak informasi penting dari dokumen"');
            } else {
                addMessage('system', 'Silakan masukkan pesan atau pertanyaan Anda.');
            }
            return;
        }

        if (message.length < 5) {
            addMessage('system', '<strong>⚠️ Instruksi terlalu singkat!</strong><br><br>Silakan berikan instruksi yang lebih jelas dan spesifik (minimal 5 karakter).');
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
        typingIndicator.classList.add('show');
        scrollToBottom();

        try {
            // Prepare form data
            const formData = new FormData();
            formData.append('prompt', message);

            // Add conversation history
            const historyToSend = conversationHistory.slice(-maxConversationHistory);
            formData.append('conversation_history', JSON.stringify(historyToSend));

            // Add files
            if (fileReferensi.files.length > 0) {
                Array.from(fileReferensi.files).forEach((file, index) => {
                    formData.append(`file_referensi[${index}]`, file);
                });
            }

            if (ocrImages.files.length > 0) {
                Array.from(ocrImages.files).forEach((file, index) => {
                    formData.append(`ocr_images[${index}]`, file);
                });
            }

            // Add context
            const periode = document.getElementById('periode')?.value;
            const templateId = document.getElementById('template_id')?.value;

            if (periode) formData.append('periode', periode);
            if (templateId) formData.append('template_id', templateId);

            // Call AI service
            const response = await fetch('/gkm/laporan-artefak/ai-prompt', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: formData
            });

            typingIndicator.classList.remove('show');

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

            // Clear file inputs after successful send
            fileReferensi.value = '';
            ocrImages.value = '';
            displayAttachments();
            updateAttachmentButtonState();

            // Show warning if approaching limit
            if (conversationHistory.length >= maxConversationTurns - 3) {
                addMessage('system', `<i class="bi bi-info-circle"></i> Anda sudah melakukan ${conversationHistory.length} percakapan. Pertimbangkan untuk refresh halaman setelah ${maxConversationTurns - conversationHistory.length} pesan lagi.`);
            }

        } catch (error) {
            console.error('Error:', error);
            typingIndicator.classList.remove('show');

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

        messagesBox.appendChild(msgDiv);
        scrollToBottom();

        // Add to conversation history
        if (type === 'user' || type === 'ai') {
            conversationHistory.push({
                type: type,
                content: content
            });
        }
    }

    function scrollToBottom() {
        messagesBox.scrollTop = messagesBox.scrollHeight;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    console.log('AI Prompt Assistant Artefak initialized');
});
