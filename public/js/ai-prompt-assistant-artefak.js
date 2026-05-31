/**
 * AI Prompt Assistant untuk Laporan Artefak/VMTS
 * Menyediakan fitur AI assistant dengan conversation history untuk membantu pengguna dalam membuat laporan artefak
 */

class AIPromptAssistantArtefak {
    constructor() {
        this.isInitialized = false;
        this.currentConversation = [];
        this.isProcessing = false;
        this.maxConversationHistory = 30; // Increased to support 10+ turns
        this.init();
    }

    init() {
        if (this.isInitialized) return;

        this.createAssistantUI();
        this.bindEvents();
        this.isInitialized = true;
        console.log('AI Prompt Assistant Artefak initialized');
    }

    createAssistantUI() {
        // Cek apakah UI sudah ada
        if (document.getElementById('ai-assistant-artefak')) return;

        const assistantHTML = `
            <div id="ai-assistant-artefak" class="ai-assistant-container" style="display: none;">
                <div class="ai-assistant-header">
                    <h4><i class="fas fa-robot"></i> AI Assistant - Laporan Artefak/VMTS</h4>
                    <div class="ai-assistant-controls">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="aiAssistantArtefak.clearConversation()" title="Clear Conversation">
                            <i class="fas fa-trash"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info" onclick="aiAssistantArtefak.showSuggestions()" title="Show Suggestions">
                            <i class="fas fa-lightbulb"></i>
                        </button>
                        <button type="button" class="btn-close-assistant" onclick="aiAssistantArtefak.toggleAssistant()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="ai-assistant-body">
                    <div class="ai-conversation" id="ai-conversation-artefak">
                        <div class="ai-message ai-message-system">
                            <i class="fas fa-robot"></i>
                            <div class="message-content">
                                <p>Halo! Saya AI Assistant untuk membantu Anda membuat laporan artefak/VMTS. Saya dapat membantu dengan:</p>
                                <ul>
                                    <li>Menganalisis dokumen artefak (RPS, silabus, materi kuliah)</li>
                                    <li>Memberikan evaluasi kualitas artefak</li>
                                    <li>Menyusun laporan dengan struktur yang tepat</li>
                                    <li>Memberikan rekomendasi perbaikan</li>
                                    <li>Melakukan iterasi dan perbaikan draft</li>
                                </ul>
                                <p><strong>Tips:</strong> Upload dokumen artefak Anda dan saya akan menganalisisnya untuk membuat laporan yang komprehensif!</p>
                            </div>
                        </div>
                    </div>
                    <div class="ai-input-container">
                        <div class="file-upload-area" id="file-upload-area-artefak">
                            <input type="file" id="file-input-artefak" multiple 
                                   accept=".pdf,.doc,.docx,.txt,.xlsx,.xls,.jpg,.jpeg,.png,.gif,.webp"
                                   style="display: none;">
                            <div class="upload-zone" onclick="document.getElementById('file-input-artefak').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Upload dokumen artefak (opsional)</span>
                            </div>
                            <div class="uploaded-files" id="uploaded-files-artefak"></div>
                        </div>
                        <div class="input-group">
                            <input type="text" id="ai-input-artefak" class="form-control" 
                                   placeholder="Tanyakan sesuatu tentang laporan artefak atau minta analisis dokumen..." 
                                   onkeypress="if(event.key==='Enter') aiAssistantArtefak.sendMessage()">
                            <button class="btn btn-primary" onclick="aiAssistantArtefak.sendMessage()" id="ai-send-btn-artefak">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Tambahkan ke body
        document.body.insertAdjacentHTML('beforeend', assistantHTML);

        // Tambahkan floating button
        const floatingBtn = `
            <button id="ai-floating-btn-artefak" class="ai-floating-btn" onclick="aiAssistantArtefak.toggleAssistant()" title="AI Assistant Artefak">
                <i class="fas fa-robot"></i>
                <span class="floating-btn-label">AI Artefak</span>
            </button>
        `;
        document.body.insertAdjacentHTML('beforeend', floatingBtn);

        // Bind file upload events
        this.bindFileUploadEvents();
    }

    bindEvents() {
        // Event listener untuk input
        const input = document.getElementById('ai-input-artefak');
        if (input) {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !this.isProcessing) {
                    this.sendMessage();
                }
            });
        }
    }

    bindFileUploadEvents() {
        const fileInput = document.getElementById('file-input-artefak');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                this.handleFileUpload(e.target.files);
            });
        }

        // Drag and drop
        const uploadZone = document.querySelector('#file-upload-area-artefak .upload-zone');
        if (uploadZone) {
            uploadZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadZone.classList.add('drag-over');
            });

            uploadZone.addEventListener('dragleave', (e) => {
                e.preventDefault();
                uploadZone.classList.remove('drag-over');
            });

            uploadZone.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadZone.classList.remove('drag-over');
                this.handleFileUpload(e.dataTransfer.files);
            });
        }
    }

    handleFileUpload(files) {
        const uploadedFilesContainer = document.getElementById('uploaded-files-artefak');
        const fileArray = Array.from(files);

        fileArray.forEach(file => {
            // Validate file size (max 10MB)
            if (file.size > 10 * 1024 * 1024) {
                this.addMessage('system', `File ${file.name} terlalu besar (max 10MB)`);
                return;
            }

            // Create file display
            const fileElement = document.createElement('div');
            fileElement.className = 'uploaded-file';
            fileElement.innerHTML = `
                <i class="fas fa-file"></i>
                <span class="file-name">${file.name}</span>
                <span class="file-size">(${this.formatFileSize(file.size)})</span>
                <button type="button" class="btn-remove-file" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;

            // Store file reference
            fileElement._file = file;

            uploadedFilesContainer.appendChild(fileElement);
        });

        // Show success message
        if (fileArray.length > 0) {
            this.addMessage('system', `${fileArray.length} file berhasil diupload. Sekarang Anda bisa mengirim pesan untuk analisis.`);
        }
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    toggleAssistant() {
        const assistant = document.getElementById('ai-assistant-artefak');
        const floatingBtn = document.getElementById('ai-floating-btn-artefak');

        if (assistant.style.display === 'none' || assistant.style.display === '') {
            assistant.style.display = 'flex';
            floatingBtn.style.display = 'none';
            // Focus pada input
            setTimeout(() => {
                document.getElementById('ai-input-artefak')?.focus();
            }, 100);
        } else {
            assistant.style.display = 'none';
            floatingBtn.style.display = 'flex';
        }
    }

    async sendMessage() {
        if (this.isProcessing) return;

        const input = document.getElementById('ai-input-artefak');
        const message = input.value.trim();

        if (!message) {
            this.addMessage('system', 'Silakan masukkan pesan atau pertanyaan Anda.');
            return;
        }

        this.isProcessing = true;
        input.value = '';

        // Update UI
        this.updateSendButton(true);

        // Tambahkan pesan user
        this.addMessage('user', message);

        try {
            // Get uploaded files
            const uploadedFiles = this.getUploadedFiles();

            // Kirim ke backend
            const response = await this.callAIService(message, uploadedFiles);

            // Tambahkan response AI
            this.addMessage('assistant', response);

        } catch (error) {
            console.error('Error calling AI service:', error);
            this.addMessage('assistant', 'Maaf, terjadi kesalahan. Silakan coba lagi.');
        } finally {
            this.isProcessing = false;
            this.updateSendButton(false);
        }
    }

    getUploadedFiles() {
        const uploadedFilesContainer = document.getElementById('uploaded-files-artefak');
        const fileElements = uploadedFilesContainer.querySelectorAll('.uploaded-file');
        const files = [];

        fileElements.forEach(element => {
            if (element._file) {
                files.push(element._file);
            }
        });

        return files;
    }

    async callAIService(message, files = []) {
        // Prepare form data
        const formData = new FormData();
        formData.append('prompt', message);

        // Add conversation history (limit to last N messages to avoid token limits)
        const conversationHistory = this.currentConversation
            .slice(-this.maxConversationHistory)
            .map(msg => ({
                role: msg.type === 'user' ? 'user' : 'assistant',
                content: msg.content
            }));

        formData.append('conversation_history', JSON.stringify(conversationHistory));

        // Add files
        files.forEach((file, index) => {
            formData.append(`file_referensi[${index}]`, file);
        });

        // Add context from page
        const context = this.getPageContext();
        if (context.periode) {
            formData.append('periode', context.periode);
        }
        if (context.template_id) {
            formData.append('template_id', context.template_id);
        }

        const response = await fetch('/gkm/laporan-artefak/ai-prompt', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache'
            },
            body: formData
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'AI service returned error');
        }

        let aiResponse = data.response || 'Tidak ada response dari AI';

        // FRONTEND SAFETY FILTER: If user asked to modify only one section, ensure only that section is returned
        // This is a backup in case backend filter didn't work
        const modifySectionMatch = message.match(/(perbaiki|ubah|tingkatkan|lengkapi)(?:\s+agar)?(?:\s+lebih)?(?:\s+bagus)?(?:\s+detail)?(?:\s+lengkap)?\s+(.+)/i);
        if (modifySectionMatch) {
            let requestedSection = modifySectionMatch[2].trim();
            // Remove common modifiers
            requestedSection = requestedSection.replace(/\s+(agar|lebih|bagus|detail|lengkap|formal|profesional|komprehensif)$/i, '').trim();
            requestedSection = requestedSection.toUpperCase();

            console.log('Frontend filter: User requested section:', requestedSection);

            // Count how many sections are in the response
            const sectionMatches = aiResponse.match(/^#\s+[A-Z\s]+$/gm);
            const sectionCount = sectionMatches ? sectionMatches.length : 0;

            console.log('Frontend filter: Response contains', sectionCount, 'sections');

            // If response has multiple sections but user only asked for one, filter it
            if (sectionCount > 1) {
                console.log('Frontend filter: Multiple sections detected, applying filter...');
                const filteredResponse = this.filterToRequestedSection(aiResponse, requestedSection);
                if (filteredResponse && filteredResponse !== aiResponse) {
                    console.log('Frontend filter: Successfully filtered response');
                    aiResponse = filteredResponse;
                } else {
                    console.log('Frontend filter: Could not filter, using original response');
                }
            }
        }

        return aiResponse;
    }

    /**
     * Frontend-side filter to extract only the requested section
     * This is a safety backup in case backend filter fails
     */
    filterToRequestedSection(response, requestedSection) {
        // Normalize section name
        const sectionMap = {
            'SARAN': 'SARAN',
            'PENUTUP': 'PENUTUP',
            'EVALUASI': 'EVALUASI',
            'LATAR BELAKANG': 'LATAR BELAKANG',
            'DASAR': 'DASAR',
            'TUJUAN': 'TUJUAN',
            'RUANG LINGKUP': 'RUANG LINGKUP',
            'PROGRAM KERJA': 'PROGRAM KERJA',
            'PELAKSANAAN': 'PELAKSANAAN',
            'HAMBATAN': 'HAMBATAN',
            'PEMECAHAN MASALAH': 'PEMECAHAN MASALAH'
        };

        // Find matching section name
        let normalizedSection = requestedSection;
        for (const [key, value] of Object.entries(sectionMap)) {
            if (requestedSection.includes(key) || key.includes(requestedSection)) {
                normalizedSection = key;
                break;
            }
        }

        console.log('Frontend filter: Normalized section:', normalizedSection);

        // Try to extract the requested section using regex
        // Pattern 1: Markdown heading (# SECTION)
        const pattern1 = new RegExp(`^#\\s+${normalizedSection}\\s*\\n([\\s\\S]*?)(?=^#\\s+[A-Z\\s]+\\n|$)`, 'mi');
        let match = response.match(pattern1);

        if (match) {
            console.log('Frontend filter: Found section using pattern 1 (markdown)');
            return `# ${normalizedSection}\n${match[1].trim()}`;
        }

        // Pattern 2: Uppercase heading (SECTION\n)
        const pattern2 = new RegExp(`^${normalizedSection}\\s*\\n([\\s\\S]*?)(?=^[A-Z\\s]{3,}\\n|$)`, 'mi');
        match = response.match(pattern2);

        if (match) {
            console.log('Frontend filter: Found section using pattern 2 (uppercase)');
            return `# ${normalizedSection}\n${match[1].trim()}`;
        }

        console.log('Frontend filter: Could not extract section');
        return null;
    }

    getPageContext() {
        const context = {
            page: 'laporan-artefak',
            url: window.location.href,
            timestamp: new Date().toISOString()
        };

        // Ambil data dari form jika ada
        const forms = document.querySelectorAll('form');
        forms.forEach((form, index) => {
            const formData = new FormData(form);
            const formObject = {};
            for (let [key, value] of formData.entries()) {
                if (value && value.toString().trim()) {
                    formObject[key] = value;
                }
            }
            if (Object.keys(formObject).length > 0) {
                context[`form_${index}`] = formObject;
            }
        });

        // Ambil periode jika ada
        const periodeSelect = document.querySelector('select[name*="periode"]');
        if (periodeSelect && periodeSelect.value) {
            context.periode = periodeSelect.value;
        }

        // Ambil template_id jika ada
        const templateSelect = document.querySelector('select[name*="template"]');
        if (templateSelect && templateSelect.value) {
            context.template_id = templateSelect.value;
        }

        return context;
    }

    addMessage(type, content) {
        const conversation = document.getElementById('ai-conversation-artefak');
        let messageClass, icon;

        switch (type) {
            case 'user':
                messageClass = 'ai-message-user';
                icon = 'fas fa-user';
                break;
            case 'assistant':
                messageClass = 'ai-message-ai';
                icon = 'fas fa-robot';
                break;
            case 'system':
                messageClass = 'ai-message-system';
                icon = 'fas fa-info-circle';
                break;
            default:
                messageClass = 'ai-message-system';
                icon = 'fas fa-info-circle';
        }

        const messageHTML = `
            <div class="ai-message ${messageClass}">
                <i class="${icon}"></i>
                <div class="message-content">
                    <div class="message-text">${this.formatMessage(content)}</div>
                    <small class="message-time">${new Date().toLocaleTimeString()}</small>
                </div>
            </div>
        `;

        conversation.insertAdjacentHTML('beforeend', messageHTML);
        conversation.scrollTop = conversation.scrollHeight;

        // Simpan ke conversation history (exclude system messages)
        if (type !== 'system') {
            this.currentConversation.push({
                type: type,
                content: content,
                timestamp: new Date().toISOString()
            });

            // Limit conversation history
            if (this.currentConversation.length > this.maxConversationHistory * 2) {
                this.currentConversation = this.currentConversation.slice(-this.maxConversationHistory);
            }
        }
    }

    formatMessage(content) {
        // Format pesan dengan markdown sederhana
        return content
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code>$1</code>')
            .replace(/\n/g, '<br>')
            .replace(/^# (.*$)/gm, '<h3>$1</h3>')
            .replace(/^## (.*$)/gm, '<h4>$1</h4>')
            .replace(/^### (.*$)/gm, '<h5>$1</h5>');
    }

    updateSendButton(isLoading) {
        const btn = document.getElementById('ai-send-btn-artefak');
        const input = document.getElementById('ai-input-artefak');

        if (isLoading) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;
            input.disabled = true;
        } else {
            btn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            btn.disabled = false;
            input.disabled = false;
            input.focus();
        }
    }

    // Method untuk mendapatkan saran berdasarkan konteks artefak
    getSuggestions() {
        return [
            "Analisis dokumen RPS yang saya upload dan berikan evaluasi kualitasnya",
            "Buatkan laporan artefak berdasarkan dokumen yang telah diupload",
            "Bagaimana cara meningkatkan kualitas silabus mata kuliah?",
            "Evaluasi materi kuliah yang saya berikan dan beri saran perbaikan",
            "Apa saja komponen yang harus ada dalam RPS yang berkualitas?",
            "Buatkan rekomendasi untuk perbaikan dokumen pembelajaran",
            "Analisis kesesuaian antara RPS dan materi kuliah",
            "Bagaimana cara membuat soal evaluasi yang efektif?",
            "Berikan template untuk laporan evaluasi artefak",
            "Apa kriteria penilaian kualitas dokumen pembelajaran?"
        ];
    }

    // Method untuk menampilkan saran
    showSuggestions() {
        const suggestions = this.getSuggestions();
        const randomSuggestions = suggestions.sort(() => 0.5 - Math.random()).slice(0, 4);

        const suggestionsHTML = randomSuggestions.map(suggestion =>
            `<button class="btn btn-outline-primary btn-sm suggestion-btn mb-1 me-1" onclick="aiAssistantArtefak.useSuggestion('${suggestion.replace(/'/g, '\\\'')}')">${suggestion}</button>`
        ).join('');

        this.addMessage('system', `Berikut beberapa saran pertanyaan:<br><div class="suggestions-container mt-2">${suggestionsHTML}</div>`);
    }

    useSuggestion(suggestion) {
        document.getElementById('ai-input-artefak').value = suggestion;
        this.sendMessage();
    }

    // Method untuk clear conversation
    clearConversation() {
        const conversation = document.getElementById('ai-conversation-artefak');
        conversation.innerHTML = `
            <div class="ai-message ai-message-system">
                <i class="fas fa-robot"></i>
                <div class="message-content">
                    <p>Percakapan telah dihapus. Silakan mulai percakapan baru!</p>
                    <p>Upload dokumen artefak Anda dan saya akan membantu menganalisisnya.</p>
                </div>
            </div>
        `;
        this.currentConversation = [];

        // Clear uploaded files
        const uploadedFilesContainer = document.getElementById('uploaded-files-artefak');
        uploadedFilesContainer.innerHTML = '';
    }

    // Method untuk export conversation
    exportConversation() {
        const conversation = this.currentConversation.map(msg => ({
            role: msg.type,
            content: msg.content,
            timestamp: msg.timestamp
        }));

        const blob = new Blob([JSON.stringify(conversation, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `ai-conversation-artefak-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
}

// Inisialisasi global
let aiAssistantArtefak;

// Inisialisasi ketika DOM ready
document.addEventListener('DOMContentLoaded', function () {
    aiAssistantArtefak = new AIPromptAssistantArtefak();
});

// Export untuk penggunaan di tempat lain jika diperlukan
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AIPromptAssistantArtefak;
}