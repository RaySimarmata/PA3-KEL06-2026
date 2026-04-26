/**
 * AI Prompt Assistant untuk Laporan Semester
 * Menyediakan fitur AI assistant untuk membantu pengguna dalam membuat laporan semester
 */

class AIPromptAssistantSemester {
    constructor() {
        this.isInitialized = false;
        this.currentConversation = [];
        this.isProcessing = false;
        this.init();
    }

    init() {
        if (this.isInitialized) return;

        this.createAssistantUI();
        this.bindEvents();
        this.isInitialized = true;
        console.log('AI Prompt Assistant Semester initialized');
    }

    createAssistantUI() {
        // Cek apakah UI sudah ada
        if (document.getElementById('ai-assistant-semester')) return;

        const assistantHTML = `
            <div id="ai-assistant-semester" class="ai-assistant-container" style="display: none;">
                <div class="ai-assistant-header">
                    <h4><i class="fas fa-robot"></i> AI Assistant - Laporan Semester</h4>
                    <button type="button" class="btn-close-assistant" onclick="aiAssistantSemester.toggleAssistant()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="ai-assistant-body">
                    <div class="ai-conversation" id="ai-conversation-semester">
                        <div class="ai-message ai-message-system">
                            <i class="fas fa-robot"></i>
                            <div class="message-content">
                                <p>Halo! Saya AI Assistant untuk membantu Anda membuat laporan semester. Saya dapat membantu dengan:</p>
                                <ul>
                                    <li>Memberikan saran konten laporan</li>
                                    <li>Membantu struktur laporan</li>
                                    <li>Menganalisis data semester</li>
                                    <li>Memberikan template dan format</li>
                                </ul>
                                <p>Silakan tanyakan apa yang Anda butuhkan!</p>
                            </div>
                        </div>
                    </div>
                    <div class="ai-input-container">
                        <div class="input-group">
                            <input type="text" id="ai-input-semester" class="form-control" 
                                   placeholder="Tanyakan sesuatu tentang laporan semester..." 
                                   onkeypress="if(event.key==='Enter') aiAssistantSemester.sendMessage()">
                            <button class="btn btn-primary" onclick="aiAssistantSemester.sendMessage()" id="ai-send-btn-semester">
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
            <button id="ai-floating-btn-semester" class="ai-floating-btn" onclick="aiAssistantSemester.toggleAssistant()" title="AI Assistant">
                <i class="fas fa-robot"></i>
            </button>
        `;
        document.body.insertAdjacentHTML('beforeend', floatingBtn);
    }

    bindEvents() {
        // Event listener untuk input
        const input = document.getElementById('ai-input-semester');
        if (input) {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !this.isProcessing) {
                    this.sendMessage();
                }
            });
        }
    }

    toggleAssistant() {
        const assistant = document.getElementById('ai-assistant-semester');
        const floatingBtn = document.getElementById('ai-floating-btn-semester');

        if (assistant.style.display === 'none' || assistant.style.display === '') {
            assistant.style.display = 'flex';
            floatingBtn.style.display = 'none';
            // Focus pada input
            setTimeout(() => {
                document.getElementById('ai-input-semester')?.focus();
            }, 100);
        } else {
            assistant.style.display = 'none';
            floatingBtn.style.display = 'flex';
        }
    }

    async sendMessage() {
        if (this.isProcessing) return;

        const input = document.getElementById('ai-input-semester');
        const message = input.value.trim();

        if (!message) return;

        this.isProcessing = true;
        input.value = '';

        // Update UI
        this.updateSendButton(true);

        // Tambahkan pesan user
        this.addMessage('user', message);

        try {
            // Kirim ke backend
            const response = await this.callAIService(message);

            // Tambahkan response AI
            this.addMessage('ai', response);

        } catch (error) {
            console.error('Error calling AI service:', error);
            this.addMessage('ai', 'Maaf, terjadi kesalahan. Silakan coba lagi.');
        } finally {
            this.isProcessing = false;
            this.updateSendButton(false);
        }
    }

    async callAIService(message) {
        // Ambil context dari halaman
        const context = this.getPageContext();

        const response = await fetch('/api/ai-assistant/semester', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                message: message,
                context: context,
                conversation: this.currentConversation.slice(-10) // Ambil 10 pesan terakhir
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        return data.response || 'Tidak ada response dari AI';
    }

    getPageContext() {
        const context = {
            page: 'buat-laporan-semester',
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

        // Ambil data dari tabel jika ada
        const tables = document.querySelectorAll('table');
        if (tables.length > 0) {
            context.tables_count = tables.length;
        }

        // Ambil informasi periode akademik jika ada
        const periodeSelect = document.querySelector('select[name*="periode"]');
        if (periodeSelect && periodeSelect.value) {
            context.periode_akademik = periodeSelect.value;
        }

        return context;
    }

    addMessage(type, content) {
        const conversation = document.getElementById('ai-conversation-semester');
        const messageClass = type === 'user' ? 'ai-message-user' : 'ai-message-ai';
        const icon = type === 'user' ? 'fas fa-user' : 'fas fa-robot';

        const messageHTML = `
            <div class="ai-message ${messageClass}">
                <i class="${icon}"></i>
                <div class="message-content">
                    <p>${this.formatMessage(content)}</p>
                    <small class="message-time">${new Date().toLocaleTimeString()}</small>
                </div>
            </div>
        `;

        conversation.insertAdjacentHTML('beforeend', messageHTML);
        conversation.scrollTop = conversation.scrollHeight;

        // Simpan ke conversation history
        this.currentConversation.push({
            type: type,
            content: content,
            timestamp: new Date().toISOString()
        });
    }

    formatMessage(content) {
        // Format pesan dengan markdown sederhana
        return content
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code>$1</code>')
            .replace(/\n/g, '<br>');
    }

    updateSendButton(isLoading) {
        const btn = document.getElementById('ai-send-btn-semester');
        const input = document.getElementById('ai-input-semester');

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

    // Method untuk mendapatkan saran berdasarkan konteks
    getSuggestions() {
        return [
            "Bagaimana struktur laporan semester yang baik?",
            "Apa saja komponen yang harus ada dalam laporan semester?",
            "Bagaimana cara menganalisis data perkuliahan semester?",
            "Template laporan semester seperti apa yang direkomendasikan?",
            "Bagaimana cara membuat ringkasan eksekutif yang efektif?",
            "Apa saja indikator kinerja yang perlu dilaporkan?",
            "Bagaimana cara menyajikan data statistik dalam laporan?",
            "Tips untuk membuat laporan yang mudah dipahami?"
        ];
    }

    // Method untuk menampilkan saran
    showSuggestions() {
        const suggestions = this.getSuggestions();
        const randomSuggestions = suggestions.sort(() => 0.5 - Math.random()).slice(0, 3);

        const suggestionsHTML = randomSuggestions.map(suggestion =>
            `<button class="btn btn-outline-primary btn-sm suggestion-btn" onclick="aiAssistantSemester.useSuggestion('${suggestion}')">${suggestion}</button>`
        ).join('');

        this.addMessage('ai', `Berikut beberapa saran pertanyaan:<br><div class="suggestions-container mt-2">${suggestionsHTML}</div>`);
    }

    useSuggestion(suggestion) {
        document.getElementById('ai-input-semester').value = suggestion;
        this.sendMessage();
    }

    // Method untuk clear conversation
    clearConversation() {
        const conversation = document.getElementById('ai-conversation-semester');
        conversation.innerHTML = `
            <div class="ai-message ai-message-system">
                <i class="fas fa-robot"></i>
                <div class="message-content">
                    <p>Percakapan telah dihapus. Silakan mulai percakapan baru!</p>
                </div>
            </div>
        `;
        this.currentConversation = [];
    }
}

// Inisialisasi global
let aiAssistantSemester;

// Inisialisasi ketika DOM ready
document.addEventListener('DOMContentLoaded', function () {
    aiAssistantSemester = new AIPromptAssistantSemester();
});

// Export untuk penggunaan di tempat lain jika diperlukan
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AIPromptAssistantSemester;
}