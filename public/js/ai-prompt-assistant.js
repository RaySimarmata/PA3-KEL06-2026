/**
 * AI Prompt Assistant
 * Untuk halaman Buat Laporan Triwulan & Semester
 *
 * Fitur:
 * - Upload file (tidak langsung terkirim)
 * - Ketik instruksi
 * - Klik tombol ↑ → AI membaca file + instruksi → tampilkan summary di Preview
 * - Follow-up conversation untuk revisi draft
 */

class AIPromptAssistant {
    constructor(config) {
        this.apiEndpoint = config.apiEndpoint;
        this.csrfToken = config.csrfToken;

        // DOM elements
        this.promptInput = document.getElementById('ai-prompt-input');
        this.btnAskAI = document.getElementById('btn-ask-ai');
        this.messagesBox = document.getElementById('ai-messages');
        this.typingIndicator = document.getElementById('typing-indicator');
        this.previewPanel = document.getElementById('preview-panel');
        this.previewSections = document.getElementById('preview-sections');
        this.btnGenerateWord = document.getElementById('btn-generate-word');
        this.btnClearPreview = document.getElementById('btn-clear-preview');
        this.fileInput = document.getElementById(config.fileInputId);
        this.fileLabel = document.getElementById(config.fileLabelId);
        this.fileNameEl = document.getElementById(config.fileNameId);
        this.aiPreviewData = document.getElementById('ai_preview_data');
        this.aiReadingStatus = document.getElementById('ai-reading-status');

        // State
        this.currentFile = null;
        this.currentPreviewText = '';
        this.conversationHistory = [];

        this.init();
    }

    init() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        // File upload - hanya tampilkan nama, TIDAK auto-process
        this.fileInput.addEventListener('change', () => this.handleFileSelect());

        // Textarea auto-resize
        this.promptInput.addEventListener('input', () => this.autoResizeTextarea());

        // Enter to send (Shift+Enter for newline)
        this.promptInput.addEventListener('keydown', (e) => this.handleKeyDown(e));

        // Send button
        this.btnAskAI.addEventListener('click', () => this.sendPrompt());

        // Clear preview
        this.btnClearPreview.addEventListener('click', () => this.clearPreview());

        // Generate Word button
        this.btnGenerateWord.addEventListener('click', () => this.generateWord());
    }

    handleFileSelect() {
        if (!this.fileInput.files[0]) return;

        this.currentFile = this.fileInput.files[0];
        this.fileNameEl.textContent = '📎 ' + this.currentFile.name;
        this.fileLabel.classList.add('has-file');

        // Beri hint ke user untuk mengisi instruksi
        if (!this.promptInput.value.trim()) {
            this.promptInput.placeholder = 'File terpilih! Ketik instruksi Anda (misal: rangkum dan buat draft laporan), lalu klik ↑';
            this.promptInput.focus();
        }
    }

    autoResizeTextarea() {
        this.promptInput.style.height = 'auto';
        this.promptInput.style.height = Math.min(this.promptInput.scrollHeight, 200) + 'px';
    }

    handleKeyDown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            this.sendPrompt();
        }
    }

    async sendPrompt() {
        const promptText = this.promptInput.value.trim();

        if (!promptText) {
            alert('Silakan ketik instruksi Anda terlebih dahulu.');
            this.promptInput.focus();
            return;
        }

        // Disable button
        this.btnAskAI.disabled = true;
        this.promptInput.disabled = true;

        // Show user message
        this.appendUserMessage(promptText, this.currentFile ? this.currentFile.name : null);

        // Clear input
        this.promptInput.value = '';
        this.promptInput.style.height = 'auto';

        // Show typing indicator
        this.showTyping();

        // Show reading status if file is attached
        if (this.currentFile) {
            this.aiReadingStatus.style.display = 'block';
        }

        try {
            // Prepare FormData
            const formData = new FormData();
            formData.append('prompt', promptText);

            if (this.currentFile) {
                formData.append('file', this.currentFile);
            }

            // Include conversation history for follow-up
            if (this.conversationHistory.length > 0) {
                formData.append('conversation_history', JSON.stringify(this.conversationHistory));
            }

            // Call API
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                },
                body: formData
            });

            const data = await response.json();

            this.hideTyping();
            this.aiReadingStatus.style.display = 'none';

            if (data.success) {
                const aiResponse = data.response;

                // Add to conversation history
                this.conversationHistory.push({
                    role: 'user',
                    content: promptText
                });
                this.conversationHistory.push({
                    role: 'assistant',
                    content: aiResponse
                });

                // Show AI response in chat
                this.appendAIMessage(this.renderMarkdown(aiResponse));

                // Show in preview panel
                this.renderPreview(aiResponse);

                // Clear file after first send (user can upload again for follow-up)
                this.currentFile = null;
                this.fileInput.value = '';
                this.fileNameEl.textContent = '📎 Lampirkan File Dokumen (DOCX/PDF/TXT) — isi instruksi lalu klik ↑';
                this.fileLabel.classList.remove('has-file');

            } else {
                this.appendAIMessage(`<p style="color:#dc2626;">❌ ${data.message || 'Terjadi kesalahan'}</p>`);
            }

        } catch (error) {
            console.error('AI Prompt error:', error);
            this.hideTyping();
            this.aiReadingStatus.style.display = 'none';
            this.appendAIMessage(`<p style="color:#dc2626;">❌ Gagal menghubungi AI: ${error.message}</p>`);
        } finally {
            this.btnAskAI.disabled = false;
            this.promptInput.disabled = false;
            this.promptInput.focus();
        }
    }

    appendUserMessage(text, fileName) {
        const div = document.createElement('div');
        div.className = 'msg-user';
        div.innerHTML = `
            <div class="bubble">
                ${text.replace(/\n/g, '<br>')}
                ${fileName ? `<div class="file-chip"><i class="bi bi-paperclip"></i> ${fileName}</div>` : ''}
            </div>`;
        this.messagesBox.appendChild(div);
        this.messagesBox.scrollTop = this.messagesBox.scrollHeight;
    }

    appendAIMessage(html) {
        const div = document.createElement('div');
        div.className = 'msg-ai';
        div.innerHTML = `
            <div class="ai-icon"><i class="bi bi-robot"></i></div>
            <div class="bubble">${html}</div>`;
        this.messagesBox.appendChild(div);
        this.messagesBox.scrollTop = this.messagesBox.scrollHeight;
    }

    showTyping() {
        this.typingIndicator.classList.add('show');
        this.messagesBox.scrollTop = this.messagesBox.scrollHeight;
    }

    hideTyping() {
        this.typingIndicator.classList.remove('show');
    }

    renderMarkdown(text) {
        if (!text) return '';

        let html = text
            // Escape HTML first
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            // Headers
            .replace(/^#### (.+)$/gm, '<h4>$1</h4>')
            .replace(/^### (.+)$/gm, '<h3>$1</h3>')
            .replace(/^## (.+)$/gm, '<h2>$1</h2>')
            .replace(/^# (.+)$/gm, '<h1>$1</h1>')
            // Bold & italic
            .replace(/\*\*\*(.+?)\*\*\*/g, '<strong><em>$1</em></strong>')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            // Horizontal rule
            .replace(/^---$/gm, '<hr>')
            // Unordered list items
            .replace(/^[\-\*] (.+)$/gm, '<li>$1</li>')
            // Ordered list items
            .replace(/^\d+\. (.+)$/gm, '<li>$1</li>')
            // Paragraphs
            .replace(/\n{2,}/g, '</p><p>')
            .replace(/\n/g, '<br>');

        // Wrap orphan <li> in <ul>
        html = html.replace(/(<li>[\s\S]+?<\/li>)/g, '<ul>$1</ul>');

        return '<p>' + html + '</p>';
    }

    renderPreview(rawText) {
        this.currentPreviewText = rawText;
        this.aiPreviewData.value = rawText;

        // Parse sections from markdown
        const sections = this.parseSections(rawText);

        this.previewSections.innerHTML = '';

        if (sections.length === 0) {
            // No sections found, show as single block
            this.previewSections.innerHTML = `
                <div class="preview-section-card">
                    <div class="preview-section-body">${this.renderMarkdown(rawText)}</div>
                </div>`;
        } else {
            // Render each section
            sections.forEach((section, idx) => {
                const card = document.createElement('div');
                card.className = 'preview-section-card';
                card.innerHTML = `
                    <div class="preview-section-header" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'block' : 'none'">
                        <span>${section.title}</span>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                    <div class="preview-section-body">${this.renderMarkdown(section.content)}</div>`;
                this.previewSections.appendChild(card);
            });
        }

        // Show preview panel
        this.previewPanel.classList.add('show');
    }

    parseSections(text) {
        const sections = [];
        const lines = text.split('\n');
        let currentSection = null;

        for (const line of lines) {
            // Check for markdown headers (# ## ###)
            const headerMatch = line.match(/^(#{1,3})\s+(.+)$/);

            if (headerMatch) {
                // Save previous section
                if (currentSection) {
                    sections.push(currentSection);
                }

                // Start new section
                currentSection = {
                    title: headerMatch[2].trim(),
                    content: ''
                };
            } else if (currentSection) {
                currentSection.content += line + '\n';
            }
        }

        // Save last section
        if (currentSection) {
            sections.push(currentSection);
        }

        return sections;
    }

    clearPreview() {
        if (!confirm('Hapus preview dan mulai dari awal?')) return;

        this.previewPanel.classList.remove('show');
        this.previewSections.innerHTML = '';
        this.currentPreviewText = '';
        this.aiPreviewData.value = '';
        this.messagesBox.innerHTML = '';
        this.conversationHistory = [];
        this.currentFile = null;
        this.fileInput.value = '';
        this.fileNameEl.textContent = '📎 Lampirkan File Dokumen (DOCX/PDF/TXT) — isi instruksi lalu klik ↑';
        this.fileLabel.classList.remove('has-file');
        this.promptInput.value = '';
        this.promptInput.placeholder = 'Contoh: Buatkan laporan dengan format formal. Fokus pada capaian mutu dan rekomendasi perbaikan...';
    }

    generateWord() {
        // This will be handled by existing form submission
        // The preview data is already in hidden input
        alert('Fitur Generate Word akan menggunakan draft yang sudah ada di preview.');
    }
}

// Export for use in blade templates
window.AIPromptAssistant = AIPromptAssistant;