@extends('layouts.app')

@section('page-title', 'AI Assistant - Laporan VMTS')

@section('styles')
<style>
    .vmts-ai-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 1.5rem;
    }

    .vmts-header-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 2rem;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }

    .vmts-header-card h1 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .vmts-header-card p {
        font-size: 1rem;
        opacity: 0.95;
        margin: 0;
    }

    .vmts-main-content {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .vmts-chat-container {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: calc(100vh - 300px);
        min-height: 600px;
    }

    .vmts-chat-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 1.25rem 1.5rem;
        color: white;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .vmts-chat-header .icon {
        width: 48px;
        height: 48px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .vmts-chat-header .info h3 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .vmts-chat-header .info p {
        margin: 0;
        font-size: 0.85rem;
        opacity: 0.9;
    }

    .vmts-messages-area {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        background: #f8f9fa;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .vmts-messages-area:empty::before {
        content: "👋 Selamat datang! Upload file Excel (data survei) dan/atau file PDF/Word (template referensi), lalu berikan instruksi untuk membuat laporan VMTS.";
        display: block;
        text-align: center;
        color: #6c757d;
        font-style: italic;
        padding: 3rem 2rem;
        background: white;
        border-radius: 12px;
        border: 2px dashed #dee2e6;
    }

    .vmts-message {
        display: flex;
        gap: 0.75rem;
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .vmts-message.user {
        flex-direction: row-reverse;
    }

    .vmts-message-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.2rem;
    }

    .vmts-message.user .vmts-message-avatar {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .vmts-message.assistant .vmts-message-avatar {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }

    .vmts-message-content {
        max-width: 75%;
        background: white;
        padding: 1rem 1.25rem;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .vmts-message.user .vmts-message-content {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .vmts-message-content p {
        margin: 0 0 0.5rem 0;
    }

    .vmts-message-content p:last-child {
        margin-bottom: 0;
    }

    .vmts-message-content h1,
    .vmts-message-content h2,
    .vmts-message-content h3 {
        margin-top: 1rem;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .vmts-message-content h1 {
        font-size: 1.3rem;
    }

    .vmts-message-content h2 {
        font-size: 1.15rem;
    }

    .vmts-message-content h3 {
        font-size: 1rem;
    }

    .vmts-message-content table {
        width: 100%;
        border-collapse: collapse;
        margin: 1rem 0;
        font-size: 0.9rem;
    }

    .vmts-message-content table th,
    .vmts-message-content table td {
        border: 1px solid #dee2e6;
        padding: 0.5rem;
        text-align: left;
    }

    .vmts-message-content table th {
        background: #f8f9fa;
        font-weight: 600;
    }

    .vmts-message-content ul,
    .vmts-message-content ol {
        margin: 0.5rem 0;
        padding-left: 1.5rem;
    }

    .vmts-message-content li {
        margin-bottom: 0.25rem;
    }

    .vmts-file-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }

    .vmts-file-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(255, 255, 255, 0.2);
        padding: 0.4rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
    }

    .vmts-message.assistant .vmts-file-chip {
        background: #e9ecef;
        color: #495057;
    }

    .vmts-download-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        margin-top: 1rem;
        transition: all 0.3s ease;
    }

    .vmts-download-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }

    .vmts-download-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .vmts-input-area {
        background: white;
        border-top: 1px solid #e9ecef;
        padding: 1.25rem 1.5rem;
    }

    .vmts-input-wrapper {
        display: flex;
        gap: 0.75rem;
        align-items: flex-end;
    }

    .vmts-input-group {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .vmts-file-upload-area {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .vmts-file-upload-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: #f8f9fa;
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.9rem;
        color: #6c757d;
        transition: all 0.2s ease;
    }

    .vmts-file-upload-btn:hover {
        background: #e9ecef;
        border-color: #667eea;
        color: #667eea;
    }

    .vmts-file-upload-btn.has-files {
        background: #e7f3ff;
        border-color: #667eea;
        color: #667eea;
    }

    .vmts-uploaded-files {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .vmts-uploaded-file {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: #e7f3ff;
        padding: 0.4rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        color: #667eea;
    }

    .vmts-uploaded-file .remove {
        cursor: pointer;
        color: #dc3545;
        font-weight: bold;
    }

    .vmts-textarea {
        width: 100%;
        min-height: 80px;
        max-height: 200px;
        padding: 0.75rem 1rem;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        font-size: 0.95rem;
        resize: vertical;
        font-family: inherit;
        transition: border-color 0.2s ease;
    }

    .vmts-textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .vmts-send-btn {
        padding: 0.75rem 1.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .vmts-send-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .vmts-send-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .vmts-typing-indicator {
        display: none;
        align-items: center;
        gap: 0.75rem;
    }

    .vmts-typing-indicator.show {
        display: flex;
    }

    .vmts-typing-dots {
        display: flex;
        gap: 0.25rem;
    }

    .vmts-typing-dots span {
        width: 8px;
        height: 8px;
        background: #667eea;
        border-radius: 50%;
        animation: typing 1.4s infinite;
    }

    .vmts-typing-dots span:nth-child(2) {
        animation-delay: 0.2s;
    }

    .vmts-typing-dots span:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes typing {
        0%, 60%, 100% {
            transform: translateY(0);
        }
        30% {
            transform: translateY(-10px);
        }
    }

    .vmts-info-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .vmts-info-card h4 {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: #667eea;
    }

    .vmts-info-card ul {
        margin: 0;
        padding-left: 1.25rem;
    }

    .vmts-info-card li {
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
        color: #6c757d;
    }
</style>
@endsection

@section('content')
<div class="vmts-ai-container">
    <!-- Header -->
    <div class="vmts-header-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="bi bi-robot"></i> AI Assistant - Laporan VMTS</h1>
                <p>Upload Excel & Template, lalu biarkan AI menghasilkan laporan lengkap untuk Anda</p>
            </div>
            <a href="{{ route('gjm.buat-laporan.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-9">
            <!-- Chat Container -->
            <div class="vmts-chat-container">
                <!-- Chat Header -->
                <div class="vmts-chat-header">
                    <div class="icon">
                        <i class="bi bi-chat-dots"></i>
                    </div>
                    <div class="info">
                        <h3>Chat dengan AI Assistant</h3>
                        <p>Upload file dan berikan instruksi untuk membuat laporan</p>
                    </div>
                </div>

                <!-- Messages Area -->
                <div class="vmts-messages-area" id="messagesArea">
                    <!-- Messages will be added here dynamically -->
                </div>

                <!-- Typing Indicator -->
                <div class="vmts-typing-indicator" id="typingIndicator">
                    <div class="vmts-message-avatar" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div class="vmts-typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="vmts-input-area">
                    <form id="chatForm" enctype="multipart/form-data">
                        @csrf
                        <div class="vmts-input-wrapper">
                            <div class="vmts-input-group">
                                <!-- File Upload -->
                                <div class="vmts-file-upload-area">
                                    <label class="vmts-file-upload-btn" id="fileUploadBtn">
                                        <i class="bi bi-paperclip"></i>
                                        <span>Upload File</span>
                                        <input type="file" id="fileInput" name="files[]" multiple 
                                               accept=".xlsx,.xls,.pdf,.docx,.doc" style="display: none;">
                                    </label>
                                    <div class="vmts-uploaded-files" id="uploadedFiles"></div>
                                </div>

                                <!-- Text Input -->
                                <textarea class="vmts-textarea" id="messageInput" name="message" 
                                          placeholder="Contoh: Buatkan laporan analisis data survei VMTS berdasarkan file Excel yang saya upload, gunakan format dari template PDF yang saya sertakan..."
                                          required></textarea>
                            </div>

                            <button type="submit" class="vmts-send-btn" id="sendBtn">
                                <i class="bi bi-send-fill"></i>
                                <span>Kirim</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <!-- Info Card -->
            <div class="vmts-info-card mb-3">
                <h4><i class="bi bi-info-circle"></i> Panduan Penggunaan</h4>
                <ul>
                    <li><strong>Upload Excel:</strong> File data survei kuesioner VMTS</li>
                    <li><strong>Upload PDF/Word:</strong> Template referensi laporan sebelumnya</li>
                    <li><strong>Berikan Instruksi:</strong> Jelaskan laporan yang Anda inginkan</li>
                    <li><strong>AI Menganalisis:</strong> AI akan membaca dan menganalisis file</li>
                    <li><strong>Download Hasil:</strong> Klik tombol download untuk mendapatkan file Word</li>
                </ul>
            </div>

            <div class="vmts-info-card">
                <h4><i class="bi bi-file-earmark-check"></i> File yang Didukung</h4>
                <ul>
                    <li><strong>Excel:</strong> .xlsx, .xls (max 10MB)</li>
                    <li><strong>PDF:</strong> .pdf (max 10MB)</li>
                    <li><strong>Word:</strong> .docx, .doc (max 10MB)</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
    let conversationHistory = [];
    let uploadedFiles = [];
    let lastAIResponse = '';

    // File upload handling
    document.getElementById('fileInput').addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        uploadedFiles = files;
        
        const uploadedFilesContainer = document.getElementById('uploadedFiles');
        const fileUploadBtn = document.getElementById('fileUploadBtn');
        
        uploadedFilesContainer.innerHTML = '';
        
        if (files.length > 0) {
            fileUploadBtn.classList.add('has-files');
            
            files.forEach((file, index) => {
                const fileChip = document.createElement('div');
                fileChip.className = 'vmts-uploaded-file';
                fileChip.innerHTML = `
                    <i class="bi bi-file-earmark"></i>
                    <span>${file.name}</span>
                    <span class="remove" onclick="removeFile(${index})">&times;</span>
                `;
                uploadedFilesContainer.appendChild(fileChip);
            });
        } else {
            fileUploadBtn.classList.remove('has-files');
        }
    });

    function removeFile(index) {
        const dt = new DataTransfer();
        const input = document.getElementById('fileInput');
        const files = Array.from(input.files);
        
        files.forEach((file, i) => {
            if (i !== index) {
                dt.items.add(file);
            }
        });
        
        input.files = dt.files;
        input.dispatchEvent(new Event('change'));
    }

    // Chat form submission
    document.getElementById('chatForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value.trim();
        
        if (!message) return;
        
        // Add user message to chat
        addMessage('user', message, uploadedFiles.map(f => f.name));
        
        // Clear input
        messageInput.value = '';
        
        // Show typing indicator
        document.getElementById('typingIndicator').classList.add('show');
        
        // Disable send button
        const sendBtn = document.getElementById('sendBtn');
        sendBtn.disabled = true;
        
        // Prepare form data
        const formData = new FormData();
        formData.append('message', message);
        formData.append('conversation_history', JSON.stringify(conversationHistory));
        
        uploadedFiles.forEach(file => {
            formData.append('files[]', file);
        });
        
        try {
            const response = await fetch('{{ route("gjm.vmts-ai.chat") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Add AI response to chat
                addMessage('assistant', data.response);
                lastAIResponse = data.response;
                
                // Update conversation history
                conversationHistory.push({
                    role: 'user',
                    content: message
                });
                conversationHistory.push({
                    role: 'assistant',
                    content: data.response
                });
                
                // Clear uploaded files
                uploadedFiles = [];
                document.getElementById('fileInput').value = '';
                document.getElementById('uploadedFiles').innerHTML = '';
                document.getElementById('fileUploadBtn').classList.remove('has-files');
            } else {
                addMessage('assistant', '❌ Maaf, terjadi kesalahan: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            addMessage('assistant', '❌ Maaf, terjadi kesalahan saat memproses permintaan Anda.');
        } finally {
            // Hide typing indicator
            document.getElementById('typingIndicator').classList.remove('show');
            
            // Enable send button
            sendBtn.disabled = false;
        }
    });

    function addMessage(role, content, files = []) {
        const messagesArea = document.getElementById('messagesArea');
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `vmts-message ${role}`;
        
        const avatar = document.createElement('div');
        avatar.className = 'vmts-message-avatar';
        avatar.innerHTML = role === 'user' ? '<i class="bi bi-person-fill"></i>' : '<i class="bi bi-robot"></i>';
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'vmts-message-content';
        
        if (role === 'assistant') {
            // Parse markdown for AI responses
            contentDiv.innerHTML = marked.parse(content);
            
            // Add download button if response looks like a complete report
            if (content.length > 1000 && (content.includes('# ') || content.includes('## '))) {
                const downloadBtn = document.createElement('button');
                downloadBtn.className = 'vmts-download-btn';
                downloadBtn.innerHTML = '<i class="bi bi-download"></i> Download Laporan (Word)';
                downloadBtn.onclick = () => downloadWord(content);
                contentDiv.appendChild(downloadBtn);
            }
        } else {
            contentDiv.textContent = content;
            
            // Add file chips for user messages
            if (files.length > 0) {
                const fileChipsDiv = document.createElement('div');
                fileChipsDiv.className = 'vmts-file-chips';
                files.forEach(filename => {
                    const chip = document.createElement('div');
                    chip.className = 'vmts-file-chip';
                    chip.innerHTML = `<i class="bi bi-file-earmark"></i> ${filename}`;
                    fileChipsDiv.appendChild(chip);
                });
                contentDiv.appendChild(fileChipsDiv);
            }
        }
        
        messageDiv.appendChild(avatar);
        messageDiv.appendChild(contentDiv);
        
        messagesArea.appendChild(messageDiv);
        
        // Scroll to bottom
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    async function downloadWord(content) {
        const judul = prompt('Masukkan judul laporan:', 'Laporan Analisis Data Hasil Survei VMTS');
        if (!judul) return;
        
        const periode = prompt('Masukkan periode (contoh: 2025/2026):', '2025/2026');
        if (!periode) return;
        
        try {
            const response = await fetch('{{ route("gjm.vmts-ai.download-word") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    content: content,
                    judul: judul,
                    periode: periode
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Download file
                window.open(data.url, '_blank');
                
                // Show success message
                alert('✅ Laporan berhasil di-generate! File akan didownload otomatis.');
            } else {
                alert('❌ Gagal generate Word: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('❌ Terjadi kesalahan saat generate Word.');
        }
    }

    // Auto-resize textarea
    const textarea = document.getElementById('messageInput');
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 200) + 'px';
    });
</script>
@endsection
