/**
 * AI Prompt Assistant untuk Laporan semester
 * Menyediakan fitur AI assistant dengan conversation history untuk membantu pengguna dalam membuat laporan semester
 * Version: 1.1.0 - Added file upload validation
 * Last Updated: 2026-05-27
 */

class AIPromptAssistantSemester {
    constructor() {
        this.isInitialized = false;
        this.currentConversation = [];
        this.isProcessing = false;
        this.maxConversationHistory = 10; // Reduced from 30 to 10 for token safety
        this.maxConversationTurns = 15; // Maximum total turns before forcing clear
        this.init();

        // Log version for debugging
        console.log('AI Prompt Assistant semester v1.1.0 - File Upload Validation Enabled');
    }

    init() {
        if (this.isInitialized) return;

        this.createAssistantUI();
        this.bindEvents();
        this.isInitialized = true;
        console.log('AI Prompt Assistant semester initialized');
    }

    createAssistantUI() {
        // Cek apakah UI sudah ada
        if (document.getElementById('ai-assistant-semester')) return;

        const assistantHTML = `
            <div id="ai-assistant-semester" class="ai-assistant-container" style="display: none;">
                <div class="ai-assistant-header">
                    <h4><i class="fas fa-robot"></i> AI Assistant - Laporan semester</h4>
                    <div class="ai-assistant-controls">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="aiAssistantsemester.clearConversation()" title="Clear Conversation">
                            <i class="fas fa-trash"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info" onclick="aiAssistantsemester.showSuggestions()" title="Show Suggestions">
                            <i class="fas fa-lightbulb"></i>
                        </button>
                        <button type="button" class="btn-close-assistant" onclick="aiAssistantsemester.toggleAssistant()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="ai-assistant-body">
                    <div class="ai-conversation" id="ai-conversation-semester">
                        <div class="ai-message ai-message-system">
                            <i class="fas fa-robot"></i>
                            <div class="message-content">
                                <p>Halo! Saya AI Assistant untuk membantu Anda membuat laporan semester. Saya dapat membantu dengan:</p>
                                <ul>
                                    <li>Memberikan saran konten laporan semester</li>
                                    <li>Membantu struktur laporan yang sesuai standar</li>
                                    <li>Menganalisis data dan dokumen pendukung</li>
                                    <li>Memberikan template dan format yang tepat</li>
                                    <li>Melakukan iterasi dan perbaikan draft</li>
                                </ul>
                                <p><strong>Tips:</strong> Anda bisa mengatakan "ubah bagian X" atau "perbaiki Y" untuk melakukan revisi!</p>
                            </div>
                        </div>
                    </div>
                    <div class="ai-input-container">
                        <div class="file-upload-area" id="file-upload-area-semester">
                            <input type="file" id="file-input-semester" multiple 
                                   accept=".pdf,.doc,.docx,.txt,.xlsx,.xls,.jpg,.jpeg,.png,.gif,.webp"
                                   style="display: none;">
                            <div class="upload-zone" onclick="document.getElementById('file-input-semester').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Upload dokumen pendukung (opsional)</span>
                            </div>
                            <div class="uploaded-files" id="uploaded-files-semester"></div>
                        </div>
                        <div class="input-group">
                            <input type="text" id="ai-input-semester" class="form-control" 
                                   placeholder="Tanyakan sesuatu tentang laporan semester..." 
                                   onkeypress="if(event.key==='Enter') aiAssistantsemester.sendMessage()">
                            <button class="btn btn-primary" onclick="aiAssistantsemester.sendMessage()" id="ai-send-btn-semester">
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
            <button id="ai-floating-btn-semester" class="ai-floating-btn" onclick="aiAssistantsemester.toggleAssistant()" title="AI Assistant semester">
                <i class="fas fa-robot"></i>
                <span class="floating-btn-label">AI semester</span>
            </button>
        `;
        document.body.insertAdjacentHTML('beforeend', floatingBtn);

        // Bind file upload events
        this.bindFileUploadEvents();
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

    bindFileUploadEvents() {
        const fileInput = document.getElementById('file-input-semester');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                this.handleFileUpload(e.target.files);
            });
        }

        // Drag and drop
        const uploadZone = document.querySelector('#file-upload-area-semester .upload-zone');
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

    async handleFileUpload(files) {
        const uploadedFilesContainer = document.getElementById('uploaded-files-semester');
        const fileArray = Array.from(files);

        // Show loading indicator
        this.addMessage('system', '<i class="fas fa-spinner fa-spin"></i> Memvalidasi gambar yang diupload...');

        // Prepare form data for validation
        const formData = new FormData();
        const imageFiles = [];

        fileArray.forEach((file, index) => {
            // Validate file size (max 10MB)
            if (file.size > 10 * 1024 * 1024) {
                this.addMessage('system', `❌ File ${file.name} terlalu besar (max 10MB)`);
                return;
            }

            // Only validate image files
            if (file.type.startsWith('image/')) {
                formData.append(`images[${index}]`, file);
                imageFiles.push(file);
            } else {
                // Non-image files are accepted without validation
                this.displayUploadedFile(file, uploadedFilesContainer);
            }
        });

        // If there are image files, validate them
        if (imageFiles.length > 0) {
            try {
                // Get laporan_id from page context
                const laporanId = this.getLaporanId();
                if (!laporanId) {
                    throw new Error('Laporan ID tidak ditemukan');
                }

                formData.append('laporan_id', laporanId);
                formData.append('report_type', 'laporan_semester');

                // Call validation endpoint
                const response = await fetch('/gjm/ocr/upload', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Display accepted images
                    imageFiles.forEach(file => {
                        this.displayUploadedFile(file, uploadedFilesContainer);
                    });

                    // Show success message
                    let successMsg = `✅ ${data.stats.accepted} gambar berhasil divalidasi dan diupload.`;

                    // Show warning if some images were rejected
                    if (data.rejected_images && data.rejected_images.length > 0) {
                        successMsg += `<br><br>⚠️ ${data.rejected_images.length} gambar ditolak:<br>`;
                        data.rejected_images.forEach(rejected => {
                            successMsg += `<br>• <strong>${rejected.filename}</strong><br>`;
                            successMsg += `  Alasan: ${rejected.reason}<br>`;
                            successMsg += `  Confidence: ${rejected.confidence}`;
                        });
                    }

                    successMsg += `<br><br><strong>💡 Jangan lupa:</strong> Berikan instruksi di kolom chat untuk memproses gambar yang diupload.`;

                    this.addMessage('system', successMsg);

                    // Focus on input
                    setTimeout(() => {
                        const input = document.getElementById('ai-input-semester');
                        if (input) {
                            input.focus();
                            input.placeholder = '⚠️ Ketik instruksi untuk memproses gambar yang diupload...';
                        }
                    }, 500);

                } else {
                    // All images rejected or error
                    let errorMsg = `❌ ${data.message || 'Validasi gambar gagal'}`;

                    if (data.rejected_images && data.rejected_images.length > 0) {
                        errorMsg += '<br><br>📋 Detail Penolakan:<br>';
                        data.rejected_images.forEach(rejected => {
                            errorMsg += `<br>• <strong>${rejected.filename}</strong><br>`;
                            errorMsg += `  ${rejected.reason}<br>`;
                            errorMsg += `  Confidence: ${rejected.confidence}`;
                        });
                    }

                    this.addMessage('system', errorMsg);
                }

            } catch (error) {
                console.error('Image validation error:', error);

                // On validation error, still allow upload but show warning
                imageFiles.forEach(file => {
                    this.displayUploadedFile(file, uploadedFilesContainer);
                });

                this.addMessage('system', `⚠️ Validasi gambar gagal: ${error.message}<br><br>Gambar tetap diupload, tetapi mungkin tidak relevan dengan Laporan semester.`);
            }
        }

        // Show success message for non-image files
        const nonImageCount = fileArray.length - imageFiles.length;
        if (nonImageCount > 0) {
            this.addMessage('system', `✅ ${nonImageCount} dokumen non-gambar berhasil diupload.`);
        }
    }

    displayUploadedFile(file, container) {
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

        container.appendChild(fileElement);
    }

    getLaporanId() {
        // Try to get from URL
        const urlParams = new URLSearchParams(window.location.search);
        let laporanId = urlParams.get('id');

        // Try to get from form
        if (!laporanId) {
            const form = document.querySelector('form[action*="laporan-semester"]');
            if (form) {
                const idInput = form.querySelector('input[name="id"]');
                if (idInput) {
                    laporanId = idInput.value;
                }
            }
        }

        // Try to get from page context
        if (!laporanId && window.laporanContext) {
            laporanId = window.laporanContext.id;
        }

        return laporanId;
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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

        // Get uploaded files
        const uploadedFiles = this.getUploadedFiles();
        const hasFiles = uploadedFiles.length > 0;

        // DEBUG: Log validation check
        console.log('🔍 VALIDATION CHECK:', {
            message: message,
            messageLength: message.length,
            hasFiles: hasFiles,
            filesCount: uploadedFiles.length
        });

        // CRITICAL VALIDATION: If no message provided
        if (!message || message.length === 0) {
            console.warn('❌ VALIDATION FAILED: No message provided');
            if (hasFiles) {
                this.addMessage('system', '<strong>⚠️ INSTRUKSI WAJIB DIISI!</strong><br><br>Anda telah mengupload <strong>' + uploadedFiles.length + ' file</strong>, tetapi belum memberikan instruksi.<br><br><strong>Silakan ketik instruksi Anda terlebih dahulu</strong>, misalnya:<br>• "Analisis dokumen ini dan buat ringkasan"<br>• "Buat laporan berdasarkan data yang diupload"<br>• "Ekstrak informasi penting dari dokumen"<br><br><em>File tidak akan diproses tanpa instruksi yang jelas.</em>');

                // Focus back to input
                setTimeout(() => {
                    input.focus();
                }, 100);
            } else {
                this.addMessage('system', 'Silakan masukkan pesan atau pertanyaan Anda.');
            }
            return; // STOP execution
        }

        // VALIDATION: Instruction too short
        if (message.length < 5) {
            console.warn('❌ VALIDATION FAILED: Message too short (' + message.length + ' chars)');
            this.addMessage('system', '<strong>⚠️ Instruksi terlalu singkat!</strong><br><br>Instruksi Anda hanya <strong>' + message.length + ' karakter</strong>.<br>Silakan berikan instruksi yang lebih jelas dan spesifik (minimal 5 karakter).<br><br>Contoh instruksi yang baik:<br>• "Analisis dokumen ini"<br>• "Buat ringkasan"<br>• "Ekstrak data penting"');

            // Focus back to input
            setTimeout(() => {
                input.focus();
            }, 100);
            return; // STOP execution
        }

        console.log('✅ VALIDATION PASSED: Proceeding with request');

        // Check if conversation is too long
        const conversationTurns = this.currentConversation.length;
        if (conversationTurns >= this.maxConversationTurns) {
            this.addMessage('system', `<strong>Percakapan terlalu panjang!</strong><br>Anda telah melakukan ${conversationTurns} percakapan. Untuk performa optimal, silakan klik tombol <i class="fas fa-trash"></i> (Clear Conversation) dan mulai percakapan baru.`);
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

            // Kirim ke backend dengan retry
            const response = await this.callAIServiceWithRetry(message, uploadedFiles);

            // Tambahkan response AI
            this.addMessage('assistant', response);

            // Show warning if approaching limit
            const newTurnCount = this.currentConversation.length;
            if (newTurnCount >= this.maxConversationTurns - 3) {
                this.addMessage('system', `<i class="fas fa-info-circle"></i> Anda sudah melakukan ${newTurnCount} percakapan. Pertimbangkan untuk clear conversation setelah ${this.maxConversationTurns - newTurnCount} pesan lagi untuk performa optimal.`);
            }

        } catch (error) {
            console.error('Error calling AI service:', error);

            // Show user-friendly error message with more details
            let errorMessage = '<strong>Error:</strong> ';
            const errorStr = error.message || String(error);

            if (errorStr.includes('Rate limit') || errorStr.includes('429')) {
                errorMessage += 'Layanan AI sedang sibuk (rate limit). Silakan tunggu 1-2 menit dan coba lagi.';
            } else if (errorStr.includes('token') || errorStr.includes('context_length')) {
                errorMessage += 'Percakapan terlalu panjang. Silakan klik tombol <i class="fas fa-trash"></i> (Clear Conversation) dan mulai baru.';
            } else if (errorStr.includes('API key') || errorStr.includes('authentication')) {
                errorMessage += 'Konfigurasi API tidak valid. Silakan hubungi administrator.';
            } else if (errorStr.includes('timeout') || errorStr.includes('timed out')) {
                errorMessage += 'Request timeout. Silakan coba lagi dengan pesan yang lebih singkat atau tunggu beberapa saat.';
            } else if (errorStr.includes('network') || errorStr.includes('connection') || errorStr.includes('Failed to fetch')) {
                errorMessage += 'Masalah koneksi jaringan. Silakan periksa koneksi internet Anda dan coba lagi.';
            } else if (errorStr.includes('Layanan AI mengalami masalah')) {
                // Server already provided a user-friendly message
                errorMessage = errorStr;
            } else {
                errorMessage += 'Terjadi kesalahan. Silakan coba lagi. Jika masalah berlanjut, refresh halaman (F5).';
            }

            this.addMessage('system', errorMessage);

            // Log for debugging
            console.log('Error details:', {
                message: errorStr,
                conversation_length: this.currentConversation.length,
                timestamp: new Date().toISOString()
            });

        } finally {
            this.isProcessing = false;
            this.updateSendButton(false);
        }
    }

    async callAIServiceWithRetry(message, files = [], maxRetries = 2) {
        let lastError = null;

        for (let attempt = 0; attempt <= maxRetries; attempt++) {
            try {
                if (attempt > 0) {
                    console.log(`Retry attempt ${attempt}/${maxRetries}`);
                    // Wait before retry
                    await new Promise(resolve => setTimeout(resolve, 1000 * attempt));
                }

                const response = await this.callAIService(message, files);
                return response; // Success!

            } catch (error) {
                lastError = error;
                console.error(`Attempt ${attempt + 1} failed:`, error.message);

                // If it's the last attempt, throw the error
                if (attempt === maxRetries) {
                    throw lastError;
                }
            }
        }

        throw lastError;
    }

    getUploadedFiles() {
        const uploadedFilesContainer = document.getElementById('uploaded-files-semester');
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
        // Keep more recent messages for better context
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
        if (context.periode_semester) {
            formData.append('periode_semester', context.periode_semester);
        }
        if (context.template_id) {
            formData.append('template_id', context.template_id);
        }

        console.log('Sending AI request:', {
            message_length: message.length,
            files_count: files.length,
            conversation_history_length: conversationHistory.length,
            context: context
        });

        const response = await fetch('/gjm/laporan-semester/ai-prompt', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache'
            },
            body: formData
        });

        console.log('AI response status:', response.status);

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            const errorMessage = errorData.message || `HTTP error! status: ${response.status}`;
            console.error('AI request failed:', errorData);
            throw new Error(errorMessage);
        }

        const data = await response.json();
        console.log('AI response data:', {
            success: data.success,
            response_length: data.response?.length || 0,
            cached: data.cached,
            model_info: data.model_info
        });

        if (!data.success) {
            throw new Error(data.message || 'AI service returned error');
        }

        let aiResponse = data.response || 'Tidak ada response dari AI';

        // SMART SECTION MERGE: Detect if user asked to modify specific section(s)
        const modifySectionMatch = message.match(/(perbaiki|ubah|tingkatkan|lengkapi|ganti)(?:\s+bagian)?\s+(.+?)(?:\s+(?:agar|jadi|menjadi|dengan)|\s*$)/i);
        if (modifySectionMatch) {
            let requestedSection = modifySectionMatch[2].trim();
            // Remove common modifiers
            requestedSection = requestedSection.replace(/\s+(agar|lebih|bagus|detail|lengkap|formal|profesional|komprehensif|jadi|menjadi|dengan).*$/i, '').trim();

            console.log('Smart merge: User requested section:', requestedSection);

            // Count how many sections are in the response
            const sectionMatches = aiResponse.match(/^#\s+[A-Z\s]+$/gm);
            const sectionCount = sectionMatches ? sectionMatches.length : 0;

            console.log('Smart merge: Response contains', sectionCount, 'sections');

            // ALWAYS try to merge if user asked to modify a section
            // This ensures all sections are visible even if AI only returned one
            console.log('Smart merge: User asked to modify section, attempting merge...');

            // Get the last full draft from conversation history
            const lastFullDraft = this.getLastFullDraft();

            if (lastFullDraft) {
                console.log('Smart merge: Found last full draft, extracting and merging...');

                // Extract the requested section from AI response
                const extractedSection = this.extractRequestedSection(aiResponse, requestedSection);

                if (extractedSection) {
                    console.log('Smart merge: Successfully extracted section:', extractedSection.title);

                    // Merge: Replace the section in last draft with the new one
                    aiResponse = this.mergeSectionIntoDraft(lastFullDraft, extractedSection);

                    console.log('Smart merge: Merge complete, new response length:', aiResponse.length);
                } else {
                    console.log('Smart merge: Could not extract section from AI response');

                    // If AI response is just one section without heading, add heading and merge
                    if (sectionCount === 0 || sectionCount === 1) {
                        console.log('Smart merge: AI returned single section, adding heading and merging...');
                        const extractedSection = {
                            title: requestedSection.toUpperCase(),
                            content: aiResponse.replace(/^#\s+[^\n]+\n/, '').trim() // Remove heading if exists
                        };
                        aiResponse = this.mergeSectionIntoDraft(lastFullDraft, extractedSection);
                        console.log('Smart merge: Merge complete with added heading');
                    } else {
                        console.log('Smart merge: Using full AI response (could not extract)');
                    }
                }
            } else {
                console.log('Smart merge: No previous draft found');
                console.warn('⚠️ User asked to modify section but no full draft exists!');
                console.warn('⚠️ Please create a full draft first with "Buat laporan lengkap"');

                // Show warning to user
                this.addMessage('system', '⚠️ <strong>Peringatan:</strong> Tidak ada draft lengkap sebelumnya. Untuk hasil terbaik, buat draft lengkap terlebih dahulu dengan perintah "Buat laporan semester lengkap", lalu ubah bagian yang diinginkan.');
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

    /**
     * Extract a specific section from AI response
     * Returns {title, content} or null if not found
     */
    extractRequestedSection(response, requestedSection) {
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
        let normalizedSection = requestedSection.toUpperCase();
        for (const [key, value] of Object.entries(sectionMap)) {
            if (normalizedSection.includes(key) || key.includes(normalizedSection)) {
                normalizedSection = key;
                break;
            }
        }

        console.log('Extract section: Normalized section:', normalizedSection);

        // Try to extract the requested section using regex
        // Pattern 1: Markdown heading (# SECTION)
        const pattern1 = new RegExp(`^#\\s+(${normalizedSection})\\s*\\n([\\s\\S]*?)(?=^#\\s+[A-Z\\s]+\\n|$)`, 'mi');
        let match = response.match(pattern1);

        if (match) {
            console.log('Extract section: Found using pattern 1 (markdown)');
            return {
                title: match[1].trim(),
                content: match[2].trim()
            };
        }

        // Pattern 2: Uppercase heading (SECTION\n)
        const pattern2 = new RegExp(`^(${normalizedSection})\\s*\\n([\\s\\S]*?)(?=^[A-Z\\s]{3,}\\n|$)`, 'mi');
        match = response.match(pattern2);

        if (match) {
            console.log('Extract section: Found using pattern 2 (uppercase)');
            return {
                title: match[1].trim(),
                content: match[2].trim()
            };
        }

        console.log('Extract section: Could not extract section');
        return null;
    }

    /**
     * Get the last full draft from conversation history
     * Returns the most recent assistant message that contains multiple sections
     */
    getLastFullDraft() {
        // Search backwards through conversation history
        for (let i = this.currentConversation.length - 1; i >= 0; i--) {
            const msg = this.currentConversation[i];

            // Only look at assistant messages
            if (msg.type !== 'assistant') continue;

            // Check if this message contains multiple sections (likely a full draft)
            const sectionMatches = msg.content.match(/^#\s+[A-Z\s]+$/gm);
            const sectionCount = sectionMatches ? sectionMatches.length : 0;

            // If it has 5+ sections, it's likely a full draft
            if (sectionCount >= 5) {
                console.log('Found last full draft at index', i, 'with', sectionCount, 'sections');
                return msg.content;
            }
        }

        console.log('No full draft found in conversation history');
        return null;
    }

    /**
     * Merge a modified section into the full draft
     * Replaces the section in the draft with the new content
     */
    mergeSectionIntoDraft(fullDraft, modifiedSection) {
        const { title, content } = modifiedSection;

        console.log('Merging section:', title);

        // Try to find and replace the section in the draft
        // Pattern 1: Markdown heading (# SECTION)
        const pattern1 = new RegExp(`^#\\s+${title}\\s*\\n[\\s\\S]*?(?=^#\\s+[A-Z\\s]+\\n|$)`, 'mi');

        if (pattern1.test(fullDraft)) {
            console.log('Merge: Found section using pattern 1, replacing...');
            const replacement = `# ${title}\n${content}\n\n`;
            return fullDraft.replace(pattern1, replacement);
        }

        // Pattern 2: Uppercase heading (SECTION\n)
        const pattern2 = new RegExp(`^${title}\\s*\\n[\\s\\S]*?(?=^[A-Z\\s]{3,}\\n|$)`, 'mi');

        if (pattern2.test(fullDraft)) {
            console.log('Merge: Found section using pattern 2, replacing...');
            const replacement = `# ${title}\n${content}\n\n`;
            return fullDraft.replace(pattern2, replacement);
        }

        console.log('Merge: Could not find section in draft, appending...');
        // If section not found, append it at the end
        return fullDraft + `\n\n# ${title}\n${content}`;
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

        // Ambil informasi periode semester jika ada
        const periodeSelect = document.querySelector('select[name*="periode_semester"]');
        if (periodeSelect && periodeSelect.value) {
            context.periode_semester = periodeSelect.value;
        }

        // Ambil template_id jika ada
        const templateSelect = document.querySelector('select[name*="template"]');
        if (templateSelect && templateSelect.value) {
            context.template_id = templateSelect.value;
        }

        return context;
    }

    addMessage(type, content) {
        const conversation = document.getElementById('ai-conversation-semester');
        let messageClass, icon, systemClass = '';

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
                // Detect error type from content
                if (content.includes('Error:') || content.includes('error') || content.includes('gagal')) {
                    systemClass = ' error';
                    icon = 'fas fa-exclamation-triangle';
                } else if (content.includes('berhasil') || content.includes('Success')) {
                    systemClass = ' success';
                    icon = 'fas fa-check-circle';
                } else if (content.includes('tunggu') || content.includes('wait')) {
                    systemClass = ' info';
                    icon = 'fas fa-clock';
                }
                break;
            default:
                messageClass = 'ai-message-system';
                icon = 'fas fa-info-circle';
        }

        const messageHTML = `
            <div class="ai-message ${messageClass}${systemClass}">
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

            // Aggressive trimming - keep only recent messages
            if (this.currentConversation.length > this.maxConversationHistory) {
                // Keep only the most recent messages
                const recentMessages = this.currentConversation.slice(-this.maxConversationHistory);
                this.currentConversation = recentMessages;

                console.log('Conversation history aggressively trimmed', {
                    'kept_messages': recentMessages.length,
                    'max_limit': this.maxConversationHistory
                });

                // Show warning to user
                if (this.currentConversation.length >= this.maxConversationHistory - 2) {
                    this.addMessage('system', `<i class="fas fa-exclamation-triangle"></i> Riwayat percakapan dipangkas untuk menghemat token. Hanya ${this.maxConversationHistory} pesan terakhir yang disimpan.`);
                }
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
            "Buatkan draft laporan semester lengkap dengan semua bagian",
            "Bagaimana struktur laporan semester yang baik?",
            "Apa saja komponen yang harus ada dalam laporan semester?",
            "Bagaimana cara menganalisis data perkuliahan semester?",
            "Berikan template laporan semester yang sesuai standar",
            "Bagaimana cara membuat ringkasan eksekutif yang efektif?",
            "Apa saja indikator kinerja yang perlu dilaporkan?",
            "Bagaimana cara menyajikan data statistik dalam laporan?",
            "Tips untuk membuat laporan yang mudah dipahami?",
            "Ubah bagian LATAR BELAKANG menjadi lebih komprehensif"
        ];
    }

    // Method untuk menampilkan saran
    showSuggestions() {
        const suggestions = this.getSuggestions();
        const randomSuggestions = suggestions.sort(() => 0.5 - Math.random()).slice(0, 4);

        const suggestionsHTML = randomSuggestions.map(suggestion =>
            `<button class="btn btn-outline-primary btn-sm suggestion-btn mb-1 me-1" onclick="aiAssistantsemester.useSuggestion('${suggestion.replace(/'/g, '\\\'')}')">${suggestion}</button>`
        ).join('');

        this.addMessage('system', `Berikut beberapa saran pertanyaan:<br><div class="suggestions-container mt-2">${suggestionsHTML}</div>`);
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
                    <p>Anda bisa upload dokumen pendukung dan saya akan membantu menganalisisnya untuk laporan semester.</p>
                </div>
            </div>
        `;
        this.currentConversation = [];

        // Clear uploaded files
        const uploadedFilesContainer = document.getElementById('uploaded-files-semester');
        uploadedFilesContainer.innerHTML = '';
    }
}

// Inisialisasi global
let aiAssistantsemester;

// Inisialisasi ketika DOM ready
document.addEventListener('DOMContentLoaded', function () {
    aiAssistantsemester = new AIPromptAssistantSemester();
});

// Export untuk penggunaan di tempat lain jika diperlukan
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AIPromptAssistantSemester;
}