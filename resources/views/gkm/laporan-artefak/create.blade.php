@extends('layouts.app')

@section('page-title', 'Generate Laporan Baru')

@section('styles')
    <!-- Cache Busting: Force reload CSS and JS - Version 1.2.0 -->
    <meta name="cache-version" content="1.2.0-{{ time() }}">
    <style>
        /* ===============================================================
                                   AI PROMPT ASSISTANT — ARTEFAK
                                   =============================================================== */

        /* Button hover effect - icon turns white */
        .btn-template-link:hover i {
            color: #fff !important;
        }

        #ai-prompt-section {
            position: relative;
        }

        .ai-chat-wrapper {
            background: linear-gradient(135deg, #f8fbff 0%, #eef4fd 100%);
            border: 1.5px solid #1e3c72;
            border-radius: 16px;
            overflow: hidden;
        }

        .ai-chat-header {
            background: linear-gradient(135deg, #1e3c72 0%, #1e3c72 100%);
            color: #fff;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .ai-chat-header .ai-avatar {
            width: 38px;
            height: 38px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .ai-chat-header h6 {
            margin: 0;
            font-weight: 700;
            font-size: 0.95rem;
            color: #fff;
        }

        .ai-chat-header small {
            opacity: 0.95;
            font-size: 0.75rem;
            color: #fff;
        }

        .ai-messages {
            min-height: 180px;
            max-height: 420px;
            overflow-y: auto;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            scroll-behavior: smooth;
        }

        .ai-messages:empty::after {
            content: "Ketik instruksi Anda di bawah dan klik tombol panah untuk generate...";
            color: #9ca3af;
            font-size: 0.875rem;
            font-style: italic;
            text-align: center;
            margin: auto;
            padding: 2rem;
            display: block;
        }

        .msg-user {
            display: flex;
            justify-content: flex-end;
        }

        .msg-user .bubble {
            background: #1e3c72;
            color: #fff;
            border-radius: 18px 18px 4px 18px;
            padding: 0.75rem 1rem;
            max-width: 80%;
            font-size: 0.875rem;
            line-height: 1.6;
            word-break: break-word;
            white-space: pre-wrap;
        }

        .msg-ai {
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
        }

        .msg-ai .ai-icon {
            width: 30px;
            height: 30px;
            flex-shrink: 0;
            background: linear-gradient(135deg, #1e3c72, #1e3c72);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
        }

        .msg-ai .bubble {
            background: #fff;
            border: 1px solid #ede9f7;
            border-radius: 4px 18px 18px 18px;
            padding: 0.75rem 1rem;
            max-width: 90%;
            font-size: 0.875rem;
            line-height: 1.7;
            color: #1e293b;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
        }

        .msg-ai .bubble h1,
        .msg-ai .bubble h2,
        .msg-ai .bubble h3,
        .msg-ai .bubble h4 {
            font-size: 0.9rem;
            font-weight: 700;
            margin: 0.75rem 0 0.35rem;
            color: #1e293b;
        }

        .msg-ai .bubble p {
            margin: 0 0 0.5rem;
        }

        .msg-ai .bubble ul,
        .msg-ai .bubble ol {
            padding-left: 1.2rem;
            margin: 0.25rem 0;
        }

        .msg-ai .bubble li {
            margin-bottom: 0.2rem;
        }

        .msg-ai .bubble strong {
            color: #1e3c72;
        }

        .msg-ai .bubble hr {
            border-color: #ede9f7;
            margin: 0.75rem 0;
        }

        .typing-indicator {
            display: none;
            align-items: center;
            gap: 0.6rem;
        }

        .typing-indicator.show {
            display: flex;
        }

        .typing-dots span {
            display: inline-block;
            width: 7px;
            height: 7px;
            background: #93a3b8;
            border-radius: 50%;
            animation: typing-bounce 1.2s infinite;
        }

        .typing-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing-bounce {

            0%,
            60%,
            100% {
                transform: translateY(0);
            }

            30% {
                transform: translateY(-6px);
            }
        }

        .ai-input-area {
            border-top: 1px solid #ede9f7;
            padding: 1rem 1.25rem;
            background: #fff;
        }

        .ai-textarea-inline {
            border: none;
            outline: none;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            resize: none;
            width: 100%;
            min-height: 50px;
            max-height: 150px;
            overflow-y: auto;
            line-height: 1.6;
            color: #1e293b;
            background: transparent;
        }

        .ai-textarea-inline:focus {
            outline: none;
        }

        .ai-textarea-inline::placeholder {
            color: #9ca3af;
        }

        .ai-input-area>div:first-child:focus-within {
            border-color: #1e3c72;
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.12);
        }

        .btn-ask-ai-inline {
            background: linear-gradient(135deg, #1e3c72 0%, #1e3c72 100%);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-left: 0.5rem;
        }

        .btn-ask-ai-inline:hover {
            transform: scale(1.05);
            background: linear-gradient(135deg, #1e3c72 0%, #1e3c72 100%);
        }

        .btn-ask-ai-inline:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-ask-ai-inline .arrow-icon {
            font-size: 1.2rem;
            font-weight: bold;
            color: #fff;
        }

        /* Generate Word Button inside AI message */
        .btn-generate-word-inline {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 0.6rem 1.2rem;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            margin-top: 0.75rem;
            width: 100%;
            justify-content: center;
        }

        .btn-generate-word-inline:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(22, 163, 74, 0.35);
        }

        .btn-generate-word-inline:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
    </style>
@endsection

@section('content')
    <div style="padding: 1.5rem;">
        <div class="filter-card mb-4 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 font-semibold" style="text-transform: uppercase; letter-spacing: 0.5px;">
                Generate Laporan Bulanan Baru
            </h5>
            <a href="{{ route('gkm.laporan-artefak.index') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        <div class="row">
            <div class="col-xl-11 col-lg-12 mx-auto">
                <!-- Info Card -->
                <div class="monitoring-card mb-4" style="border-left: 4px solid #1e3c72;">
                    <div style="padding: 1.5rem;">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-lightbulb" style="color: #1e3c72; font-size: 2rem; margin-right: 1rem;"></i>
                            <div>
                                <h6 class="mb-2" style="font-weight: 600; color: #333;">Panduan Pembuatan Laporan</h6>
                                <ol class="mb-0" style="font-size: 0.875rem; color: #495057; line-height: 1.8;">
                                    <li>Isi informasi laporan (periode, judul, template)</li>
                                    <li>Klik <strong>"Buat Laporan Draft"</strong> untuk membuat draft laporan</li>
                                    <li>Ketik instruksi atau deskripsi laporan yang diinginkan</li>
                                    <li>Klik <strong>tombol Kirim (↑)</strong> untuk melihat preview draft laporan dari AI
                                    </li>
                                    <li>Klik <strong>Generate Laporan Word</strong> untuk mengunduh file .docx</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="laporanForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="tipe_laporan" value="artefak">
                    <input type="hidden" id="laporan_id" name="laporan_id" value="">
                    <input type="hidden" id="ai_preview_data" name="ai_preview_data" value="">

                    <!-- ===== INFORMASI LAPORAN ===== -->
                    <div class="monitoring-card mb-4">
                        <div class="monitoring-header">
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="mb-0">Informasi Laporan</h6>
                            </div>
                        </div>
                        <div style="padding: 1.5rem;">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="filter-label">Periode Laporan <span class="text-danger">*</span></label>
                                    <select class="form-select" name="periode" id="periode" required>
                                        <option value="">-- Pilih Periode --</option>
                                        @foreach ($periodes as $p)
                                            <option value="{{ $p['value'] }}"
                                                {{ old('periode') == $p['value'] ? 'selected' : '' }}>
                                                {{ $p['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label class="filter-label">Judul Laporan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="judul_laporan" id="judul_laporan"
                                        placeholder="Masukkan judul laporan" required>
                                </div>

                                <div class="col-md-12 mb-4">
                                    <label class="filter-label">Template Laporan</label>
                                    <select class="form-select" name="template_id" id="template_id">
                                        <option value="">-- Gunakan Format Default --</option>
                                        @foreach ($templates as $t)
                                            <option value="{{ $t->id }}">
                                                {{ $t->nama_template }}
                                                @if ($t->is_active)
                                                    ✓ Active
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-12 mb-0">
                                    <button type="button" id="btn-create-draft" class="btn btn-primary">
                                        Buat Draf Laporan
                                    </button>
                                    <small class="text-muted d-block mt-2" style="font-size: 0.8rem;">
                                        <i class="bi bi-info-circle"></i> Klik tombol ini terlebih dahulu untuk membuat
                                        laporan draft, kemudian Anda bisa mulai chat dengan AI.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== PROMPT ASSISTANT ===== -->
                    <div class="monitoring-card mb-4" id="ai-prompt-section" style="overflow: hidden;">
                        <div class="monitoring-header">
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="mb-0">Instruksi Laporan</h6>
                            </div>
                        </div>
                        <div style="padding: 1.5rem;">
                            <!-- AI Chat Interface -->
                            <div class="ai-chat-wrapper">
                                <div class="ai-chat-header">
                                    <div class="ai-avatar">
                                        <i class="bi bi-stars"></i>
                                    </div>
                                    <div>
                                        <h6>AI Assistant - Laporan Bulanan</h6>
                                        <small>Siap membantu Anda membuat laporan RPS dan Materi</small>
                                    </div>
                                </div>

                                <!-- Messages Area -->
                                <div id="sync-status-indicator" style="display: none; background: #dbeafe; border-bottom: 1px solid #93c5fd; padding: 0.75rem 1.25rem; font-size: 0.85rem; color: #1e40af;">
                                    <span id="sync-status-icon">📤</span>
                                    <span id="sync-status-text">Sedang menyinkronisasi ke database...</span>
                                </div>
                                
                                <div class="ai-messages" id="ai-messages"></div>

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

                                <!-- Input Area - Tanpa Attachment Button -->
                                <div class="ai-input-area">
                                    <div
                                        style="position: relative; display: flex; align-items: center; border: 1.5px solid #d1dff5; border-radius: 12px; background: #fff; padding: 0.5rem;">
                                        <textarea class="ai-textarea-inline" id="ai-prompt-input"
                                            placeholder="Deskripsikan laporan bulanan yang ingin Anda buat..." style="margin-left: 0.5rem;"></textarea>
                                        <button type="button" class="btn-ask-ai-inline" id="btn-ask-ai">
                                            <span class="arrow-icon">↑</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const promptInput = document.getElementById('ai-prompt-input');
            const btnAskAI = document.getElementById('btn-ask-ai');
            const messagesBox = document.getElementById('ai-messages');
            const typingIndicator = document.getElementById('typing-indicator');
            const aiPreviewData = document.getElementById('ai_preview_data');

            let currentPreviewText = '';
            let conversationHistory = [];

            // Auto-resize textarea
            promptInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 200) + 'px';
            });

            // Enter = send (Shift+Enter = newline)
            promptInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    btnAskAI.click();
                }
            });

            // Markdown renderer
            function renderMarkdown(text) {
                if (!text) return '';
                let html = text
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    .replace(/^### (.+)$/gm, '<h4>$1</h4>')
                    .replace(/^## (.+)$/gm, '<h3>$1</h3>')
                    .replace(/^# (.+)$/gm, '<h2>$1</h2>')
                    .replace(/\*\*\*(.+?)\*\*\*/g, '<strong><em>$1</em></strong>')
                    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.+?)\*/g, '<em>$1</em>')
                    .replace(/^---$/gm, '<hr>')
                    .replace(/^[\-\*] (.+)$/gm, '<li>$1</li>')
                    .replace(/^\d+\. (.+)$/gm, '<li>$1</li>')
                    .replace(/\n{2,}/g, '</p><p>')
                    .replace(/\n/g, '<br>');
                html = html.replace(/(<li>[\s\S]+?<\/li>)/g, '<ul>$1</ul>');
                return '<p>' + html + '</p>';
            }

            function appendUserMessage(text) {
                const div = document.createElement('div');
                div.className = 'msg-user';
                div.innerHTML = `<div class="bubble">${text.replace(/\n/g,'<br>')}</div>`;
                messagesBox.appendChild(div);
                messagesBox.scrollTop = messagesBox.scrollHeight;
            }

            function appendAIMessage(html) {
                const div = document.createElement('div');
                div.className = 'msg-ai';
                div.innerHTML = `<div class="bubble">${html}</div>`;
                messagesBox.appendChild(div);
                messagesBox.scrollTop = messagesBox.scrollHeight;
            }

            function appendAIMessageWithPreview(aiResponse, sections) {
                const div = document.createElement('div');
                div.className = 'msg-ai';
                let generateButtonHTML = '';
                // Show button if we have a response (not just if sections are detected)
                // This allows users to generate even if markdown parsing didn't capture sections
                if (aiResponse && aiResponse.trim().length > 100) {
                    generateButtonHTML = `
                        <div style="margin-top: 1rem; border-top: 1px solid #e5e7eb; padding-top: 1rem; display:flex; gap:8px;">
                            <button type="button" class="btn-generate-word-inline" onclick="generateWordFromChat()">
                                <i class="bi bi-file-earmark-word-fill"></i>
                                Generate Laporan Word
                            </button>
                        </div>
                    `;
                }

                div.innerHTML = `<div class="bubble">${renderMarkdown(aiResponse)}${generateButtonHTML}</div>`;
                messagesBox.appendChild(div);
                messagesBox.scrollTop = messagesBox.scrollHeight;
            }

            function fillSkeletonPlaceholders(text, sections) {
                if (!sections || typeof sections !== 'object') {
                    return text;
                }

                const placeholderMap = {
                    '@{{LATAR_BELAKANG}}': sections.latar_belakang || '',
                    '@{{DASAR_ACUAN}}': sections.dasar_acuan || '',
                    '@{{TUJUAN}}': sections.tujuan || '',
                    '@{{SASARAN}}': sections.sasaran || '',
                    '@{{WAKTU_PELAKSANAAN}}': sections.waktu_pelaksanaan || '',
                    '@{{RUANG}}': sections.ruang_lingkup || sections.ruang || '',
                    '@{{INSTRUMEN_PENGUKURAN}}': sections.instrumen_pengukuran || '',
                    '@{{PROGRAM_KERJA}}': sections.program_kerja || '',
                    '@{{PELAKSANAAN}}': sections.pelaksanaan || '',
                    '@{{HAMBATAN_PENJELASAN}}': sections.hambatan_dan_pemecahan_masalah || sections.hambatan_penjelasan || sections.hambatan || '',
                    '@{{HASIL_PEMERIKSAAN}}': sections.hasil_pemeriksaan || '',
                    '@{{ANALISIS_KETERCAPAIAN}}': sections.analisis_ketercapaian || '',
                    '@{{TINDAK_LANJUT}}': sections.tindak_lanjut || '',
                    '@{{KESIMPULAN_PENUTUP}}': sections.kesimpulan_penutup || sections.penutup || '',
                };

                let result = text;
                Object.entries(placeholderMap).forEach(([placeholder, value]) => {
                    if (value && typeof value === 'string') {
                        result = result.split(placeholder).join(value);
                    }
                });
                return result;
            }

            // (Removed: showFullPreview button/handler) full preview remains available for Word generation

            function showTyping() {
                typingIndicator.classList.add('show');
            }

            function hideTyping() {
                typingIndicator.classList.remove('show');
            }

            function parseMarkdownSections(text) {
                const lines = text.split('\n');
                const sections = [];
                let currentSection = null;
                lines.forEach(function(line) {
                    // Match h1, h2, and h3 headings (# , ## , ### )
                    if (line.match(/^#{1,3}\s/)) {
                        if (currentSection) sections.push(currentSection);
                        currentSection = {
                            title: line.replace(/^#{1,3}\s/, ''),
                            content: ''
                        };
                    } else if (currentSection) {
                        currentSection.content += line + '\n';
                    }
                });
                if (currentSection) sections.push(currentSection);
                return sections;
            }

            // Create Draft Laporan
            const btnCreateDraft = document.getElementById('btn-create-draft');
            btnCreateDraft.addEventListener('click', async function() {
                const periode = document.getElementById('periode').value;
                const judul = document.getElementById('judul_laporan').value;

                if (!periode) {
                    alert('Pilih periode laporan terlebih dahulu.');
                    document.getElementById('periode').focus();
                    return;
                }

                if (!judul) {
                    alert('Isi judul laporan terlebih dahulu.');
                    document.getElementById('judul_laporan').focus();
                    return;
                }

                this.disabled = true;
                const originalText = this.innerHTML;
                this.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2"></span>Membuat draft...';

                try {
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('periode', periode);
                    formData.append('judul_laporan', judul);

                    const template = document.getElementById('template_id').value;
                    if (template) formData.append('template_id', template);

                    const response = await fetch('{{ route('gkm.laporan-artefak.create-draft') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json'
                        },
                    });

                    const data = await response.json();

                    if (data.success && data.data && data.data.id) {
                        document.getElementById('laporan_id').value = data.data.id;
                        document.getElementById('periode').disabled = true;
                        document.getElementById('judul_laporan').disabled = true;
                        document.getElementById('template_id').disabled = true;

                        this.innerHTML = '<i class="bi bi-check-circle"></i> Laporan Draft Dibuat';
                        this.classList.add('btn-success');
                        this.classList.remove('btn-primary');

                        appendAIMessage(
                            '<strong>✅ Laporan draft berhasil dibuat!</strong><br>Sekarang Anda bisa mulai chat dengan AI. Ketik instruksi Anda.'
                        );
                        promptInput.focus();
                    } else {
                        throw new Error(data.message || 'Gagal membuat laporan draft');
                    }
                } catch (err) {
                    alert('Terjadi kesalahan: ' + err.message);
                    this.disabled = false;
                    this.innerHTML = originalText;
                }
            });

            // ASK AI
            btnAskAI.addEventListener('click', async function() {
                const prompt = promptInput.value.trim();
                let laporanId = document.getElementById('laporan_id').value;
                const judulLaporan = document.getElementById('judul_laporan').value.trim();
                const periode = document.getElementById('periode').value;
                const template = document.getElementById('template_id').value;

                if (!prompt) {
                    appendAIMessage(`
                        <div style="background:#fff3cd;border-left:4px solid #ffc107;padding:12px;border-radius:4px;">
                            <p style="color:#856404;margin:0;font-weight:600;">
                                <i class="bi bi-info-circle-fill"></i> Silakan masukkan instruksi atau pertanyaan Anda
                            </p>
                        </div>
                    `);
                    promptInput.focus();
                    return;
                }

                if (!laporanId && (!periode || !judulLaporan)) {
                    appendAIMessage(`
                        <div style="background:#fff3cd;border-left:4px solid #ffc107;padding:12px;border-radius:4px;">
                            <p style="color:#856404;margin:0;font-weight:600;">
                                <i class="bi bi-info-circle-fill"></i> Silakan pilih periode dan isi judul laporan terlebih dahulu, lalu klik "Buat Laporan Draft" atau kirim chat untuk membuat draft otomatis.
                            </p>
                        </div>
                    `);
                    document.getElementById('btn-create-draft').focus();
                    return;
                }

                appendUserMessage(prompt);
                promptInput.value = '';
                promptInput.style.height = 'auto';
                promptInput.placeholder = 'Ketik instruksi Anda...';

                showTyping();
                btnAskAI.disabled = true;
                btnAskAI.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                try {
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('prompt', prompt);
                    if (laporanId) {
                        formData.append('laporan_id', laporanId);
                    }
                    if (periode) formData.append('periode', periode);
                    if (judulLaporan) formData.append('judul_laporan', judulLaporan);
                    if (template) formData.append('template_id', template);

                    if (conversationHistory.length > 0) {
                        conversationHistory.forEach((msg, index) => {
                            formData.append(`conversation_history[${index}][role]`, msg.role);
                            formData.append(`conversation_history[${index}][content]`, msg
                                .content);
                        });
                    }

                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 120000);

                    let response;
                    try {
                        response = await fetch('{{ route('gkm.laporan-artefak.ai-prompt') }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json'
                            },
                            signal: controller.signal,
                        });
                    } catch (fetchErr) {
                        if (fetchErr.name === 'AbortError') {
                            throw new Error(
                                '⏱️ Request timeout (>2 menit). Server AI sedang sibuk. Coba lagi nanti.'
                            );
                        }
                        throw fetchErr;
                    } finally {
                        clearTimeout(timeoutId);
                    }

                    const data = await response.json();

                    if (data.success) {
                        let aiResponse = data.response;

                        if (!laporanId && data.laporan_id) {
                            laporanId = data.laporan_id;
                            document.getElementById('laporan_id').value = laporanId;
                            document.getElementById('periode').disabled = true;
                            document.getElementById('judul_laporan').disabled = true;
                            document.getElementById('template_id').disabled = true;
                            document.getElementById('btn-create-draft').innerHTML = '<i class="bi bi-check-circle"></i> Laporan Draft Dibuat';
                            document.getElementById('btn-create-draft').classList.add('btn-success');
                            document.getElementById('btn-create-draft').classList.remove('btn-primary');

                            appendAIMessage(
                                '<div style="background:#d1fae5;border-left:4px solid #10b981;padding:12px;border-radius:4px;margin-bottom:1rem;">' +
                                '<strong>✅ Draft laporan otomatis dibuat dari instruksi pertama.</strong><br>Silakan lanjutkan chat atau klik Generate Laporan Word jika sudah siap.</div>'
                            );
                        }

                        conversationHistory.push({
                            role: 'user',
                            content: prompt
                        });
                        conversationHistory.push({
                            role: 'assistant',
                            content: aiResponse
                        });

                        const serverSections = data.ai_sections || {};
                        if (Object.keys(serverSections).length > 0) {
                            aiResponse = fillSkeletonPlaceholders(aiResponse, serverSections);
                        }

                        const sections = Object.keys(serverSections).length > 0 ? serverSections : parseMarkdownSections(aiResponse);
                        currentPreviewText = aiResponse;
                        aiPreviewData.value = aiResponse;

                        // 🔄 Tampilkan sync status
                        showSyncStatus(true, data.sync_status);

                        appendAIMessageWithPreview(aiResponse, sections);
                        
                        // Jika sync berhasil, hapus indicator setelah 2 detik
                        if (data.sync_status && data.sync_status.success) {
                            setTimeout(hideSyncStatus, 2000);
                        }
                    } else {
                        appendAIMessage('<strong>❌ Error:</strong> ' + (data.message ||
                            'Terjadi kesalahan'));
                    }
                } catch (err) {
                    appendAIMessage('<strong>❌ Error:</strong> ' + err.message);
                } finally {
                    hideTyping();
                    btnAskAI.disabled = false;
                    btnAskAI.innerHTML = '<span class="arrow-icon">↑</span>';
                }
            });

            // Helper functions untuk sync status indicator
            function showSyncStatus(success = true, syncData = {}) {
                const indicator = document.getElementById('sync-status-indicator');
                const icon = document.getElementById('sync-status-icon');
                const text = document.getElementById('sync-status-text');
                
                if (success && syncData && syncData.success) {
                    icon.textContent = '✅';
                    text.textContent = 'Data sudah tersinkronisasi ke database';
                    indicator.style.background = '#dcfce7';
                    indicator.style.color = '#166534';
                } else {
                    icon.textContent = '⚠️';
                    text.textContent = 'Gagal menyinkronisasi ke database';
                    indicator.style.background = '#fee2e2';
                    indicator.style.color = '#991b1b';
                }
                
                indicator.style.display = 'block';
            }

            function hideSyncStatus() {
                const indicator = document.getElementById('sync-status-indicator');
                indicator.style.display = 'none';
            }

            async function saveAIPreviewToDatabase(laporanId, aiResponse, sections) {
                // ✅ DEPRECATED: Database sync sudah ditangani di backend melalui aiPrompt response
                // Fungsi ini tidak perlu lagi dipanggil secara terpisah
                console.log('✅ Database sync handled in backend aiPrompt endpoint');
            }

            window.generateWordFromChat = async function() {
                const laporanId = document.getElementById('laporan_id').value;
                let aiPreviewDataValue = document.getElementById('ai_preview_data').value;

                if (!laporanId) {
                    alert('Silakan buat laporan draft terlebih dahulu.');
                    return;
                }

                if (!aiPreviewDataValue) {
                    alert('Silakan chat dengan AI terlebih dahulu untuk membuat draft laporan.');
                    return;
                }

                const btn = event.target.closest('.btn-generate-word-inline');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';
                }

                try {
                    // 🔄 PENTING: Fetch laporan terbaru dari database sebelum generate
                    // Ini memastikan laporan yang di-download menggunakan versi terbaru dari chat
                    const laporanCheckResponse = await fetch(
                        `{{ route('gkm.laporan-artefak.api-get') }}?laporan_id=${laporanId}`,
                        {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        }
                    );

                    if (laporanCheckResponse.ok) {
                        const laporanData = await laporanCheckResponse.json();
                        if (laporanData.ai_preview_draft && laporanData.ai_preview_draft !== aiPreviewDataValue) {
                            console.warn('⚠️ Using newer version from database instead of UI');
                            aiPreviewDataValue = laporanData.ai_preview_draft;
                            document.getElementById('ai_preview_data').value = aiPreviewDataValue;
                        }
                    }

                    showSyncStatus(true, {success: true});

                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('laporan_id', laporanId);
                    formData.append('ai_preview_data', aiPreviewDataValue);

                    const response = await fetch('{{ route('gkm.laporan-artefak.generate-word') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                    });

                    if (!response.ok) throw new Error('Server error ' + response.status);

                    const contentType = response.headers.get('content-type');
                    if (contentType && (contentType.includes('application/vnd.openxmlformats') ||
                            contentType.includes('application/octet-stream'))) {
                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        const contentDisposition = response.headers.get('content-disposition');
                        let filename = 'Laporan_Artefak_' + Date.now() + '.docx';
                        if (contentDisposition) {
                            const filenameMatch = contentDisposition.match(
                                /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                            if (filenameMatch) filename = filenameMatch[1].replace(/['"]/g, '');
                        }
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                        appendAIMessage(
                            '<p style="color:#16a34a;"><i class="bi bi-check-circle-fill"></i> <strong>✅ Laporan Word berhasil di-generate dari versi database terbaru!</strong></p>'
                        );
                        setTimeout(hideSyncStatus, 3000);
                    } else {
                        const data = await response.json();
                        if (data.success) {
                            appendAIMessage(
                                '<p style="color:#16a34a;"><i class="bi bi-check-circle-fill"></i> <strong>Laporan berhasil dibuat!</strong></p>'
                            );
                        } else {
                            throw new Error(data.message || 'Gagal membuat laporan');
                        }
                    }
                } catch (err) {
                    hideSyncStatus();
                    appendAIMessage(
                        '<p style="color:#dc2626;"><i class="bi bi-x-circle-fill"></i> <strong>Error:</strong> ' +
                        err.message + '</p>');
                } finally {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML =
                            '<i class="bi bi-file-earmark-word-fill"></i> Generate Laporan Word';
                    }
                }
            };

            // Welcome message
            appendAIMessage(`
                <strong>Selamat datang di AI Assistant Laporan Bulanan!</strong><br>
                Saya siap membantu Anda membuat laporan bulanan yang komprehensif. Silakan:<br><br>
                1. Klik <strong>"Buat Laporan Draft"</strong> terlebih dahulu<br>
                2. Ketik instruksi atau deskripsi laporan yang diinginkan<br>
                3. Saya akan mengambil data dari database dan membantu generate draft laporan untuk Anda
            `);
        });
    </script>
@endsection
