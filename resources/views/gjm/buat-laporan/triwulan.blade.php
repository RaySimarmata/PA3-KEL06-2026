@extends('layouts.app')

@section('page-title', 'Buat Laporan Triwulan')

@section('styles')
    <style>
        /* ===============================================================
                           AI PROMPT ASSISTANT — TRIWULAN
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
            border: 1.5px solid #c9ddf5;
            border-radius: 16px;
            overflow: hidden;
        }

        .ai-chat-header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
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
            background: #2563eb;
            color: #fff;
            border-radius: 18px 18px 4px 18px;
            padding: 0.75rem 1rem;
            max-width: 80%;
            font-size: 0.875rem;
            line-height: 1.6;
            word-break: break-word;
            white-space: pre-wrap;
        }

        .msg-user .file-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 0.2rem 0.6rem;
            font-size: 0.75rem;
            margin-top: 0.4rem;
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
            background: linear-gradient(135deg, #2563eb, #7c3aed);
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
            color: #1d4ed8;
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

        .ai-textarea {
            border: 1.5px solid #d1dff5;
            border-radius: 12px;
            padding: 0.75rem 4rem 0.75rem 1rem;
            font-size: 0.875rem;
            resize: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            width: 100%;
            min-height: 80px;
            max-height: 200px;
            overflow-y: auto;
            line-height: 1.6;
            color: #1e293b;
        }

        .ai-textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
            outline: none;
        }

        .ai-textarea::placeholder {
            color: #9ca3af;
        }

        .file-upload-zone {
            border: 1.5px dashed #c9d9f5;
            border-radius: 10px;
            padding: 0.6rem 1rem;
            cursor: pointer;
            transition: all 0.2s;
            background: #f8fbff;
            text-align: center;
            font-size: 0.8rem;
            color: #64748b;
        }

        .file-upload-zone:hover {
            border-color: #2563eb;
            background: #eff6ff;
            color: #2563eb;
        }

        .file-upload-zone.has-file {
            border-color: #16a34a;
            background: #f0fdf4;
            color: #15803d;
        }

        .file-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            background: #e0f2fe;
            border: 1px solid #0891b2;
            border-radius: 6px;
            padding: 0.2rem 0.5rem;
            font-size: 0.7rem;
            color: #0e7490;
            max-width: 150px;
        }

        .file-chip .file-name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-chip .remove-file {
            cursor: pointer;
            color: #dc2626;
            font-weight: bold;
            margin-left: 0.2rem;
        }

        .file-chip .remove-file:hover {
            color: #991b1b;
        }

        .btn-ask-ai {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
            flex-shrink: 0;
        }

        .btn-ask-ai:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .btn-ask-ai:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        .btn-ask-ai .arrow-icon {
            font-size: 1.3rem;
            font-weight: bold;
            color: #fff;
        }

        #preview-panel {
            display: none;
            animation: slideDown 0.35s ease;
        }

        #preview-panel.show {
            display: block;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .preview-section-card {
            border: 1px solid #ede9f7;
            border-radius: 10px;
            margin-bottom: 0.75rem;
            overflow: hidden;
        }

        .preview-section-header {
            background: #f1f5f9;
            padding: 0.6rem 1rem;
            font-size: 0.85rem;
            font-weight: 700;
            color: #1e40af;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }

        .preview-section-body {
            padding: 1rem;
            font-size: 0.85rem;
            color: #374151;
            line-height: 1.7;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .generate-bar {
            background: linear-gradient(135deg, #0f172a 0%, #2d1b69 100%);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .generate-bar p {
            color: rgba(255, 255, 255, 0.75);
            font-size: 0.8rem;
            margin: 0;
        }

        .btn-generate-word {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 0.7rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-generate-word:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(22, 163, 74, 0.35);
        }

        .btn-generate-word:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
    </style>
@endsection

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Header Card -->
        <div class="filter-card mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 d-flex align-items-center gap-2" style="font-weight: 600; color: #333;">
                        <i class="bi bi-file-earmark-text" style="color: #5B9BD5; font-size: 2rem;"></i>
                        <div>
                            <div style="font-weight: 600; color: #333;">Buat Laporan Triwulan</div>
                            <p class="text-muted mb-0" style="font-size: 0.875rem;">Sistem akan membantu Anda menghasilkan
                                laporan triwulan strategis dengan mudah dan cepat</p>
                        </div>
                    </h5>
                </div>
                <a href="{{ route('gjm.buat-laporan.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-9 mx-auto">
                <!-- Info Card -->
                <div class="monitoring-card mb-4" style="border-left: 4px solid #5B9BD5;">
                    <div style="padding: 1.5rem;">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-lightbulb" style="color: #5B9BD5; font-size: 2rem; margin-right: 1rem;"></i>
                            <div>
                                <h6 class="mb-2" style="font-weight: 600; color: #333;">Panduan Pembuatan Laporan</h6>
                                <ol class="mb-0" style="font-size: 0.875rem; color: #495057; line-height: 1.8;">
                                    <li>Isi informasi laporan (periode, judul, template)</li>
                                    <li>Ketik instruksi atau deskripsi laporan yang diinginkan</li>
                                    <li>Upload file referensi & gambar dokumentasi <em>(multiple files supported)</em></li>
                                    <li>Sistem akan otomatis mengintegrasikan data laporan GKM bulanan</li>
                                    <li>Klik <strong>tombol Generate</strong> untuk melihat preview draft laporan</li>
                                    <li>Klik <strong>Generate Laporan Word</strong> untuk mengunduh file .docx</li>
                                </ol>
                                <div class="mt-3" style="font-size: 0.8rem; color: #6b7280;">
                                    <strong>File yang didukung:</strong> DOCX, PDF, TXT, Excel, JPG, PNG, GIF<br>
                                    <strong>Fitur AI Vision:</strong> Analisis gambar dokumentasi kegiatan, daftar hadir,
                                    kuesioner<br>
                                    <strong>Integrasi GKM:</strong> Data laporan bulanan otomatis digunakan sebagai konteks
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="laporanForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="tipe_laporan" value="triwulan">
                    <input type="hidden" id="laporan_id" name="laporan_id" value="">
                    <input type="hidden" id="ai_preview_data" name="ai_preview_data" value="">

                    <!-- ===== INFORMASI LAPORAN ===== -->
                    <div class="monitoring-card mb-4">
                        <div class="monitoring-header">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-info-circle" style="color: #5B9BD5;"></i>
                                <h6 class="mb-0">Informasi Laporan</h6>
                            </div>
                            <a href="{{ route('gjm.template-laporan.triwulan.index') }}"
                                class="btn btn-sm btn-outline-primary btn-template-link">
                                <i class="bi bi-file-earmark-text me-1"></i> Kelola Template
                            </a>
                        </div>
                        <div style="padding: 1.5rem;">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="filter-label">Periode Laporan <span class="text-danger">*</span></label>
                                    <select class="form-select" name="periode_triwulan" id="periode_triwulan" required>
                                        <option value="">Pilih Periode Triwulan</option>
                                        <option value="1">Triwulan I (Januari - Maret)</option>
                                        <option value="2">Triwulan II (April - Juni)</option>
                                        <option value="3">Triwulan III (Juli - September)</option>
                                        <option value="4">Triwulan IV (Oktober - Desember)</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label class="filter-label">Judul Laporan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="judul_laporan" id="judul_laporan"
                                        placeholder="Masukkan judul laporan" required>
                                </div>

                                <div class="col-md-12 mb-4">
                                    <label class="filter-label">Template Laporan <span class="text-muted"
                                            style="font-weight: 400;"></span></label>
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
                                    <small class="text-muted" style="font-size: 0.8rem;">
                                        <i class="bi bi-info-circle"></i> Jika dipilih, AI akan mempelajari format dari
                                        template ini.
                                        <a href="{{ route('gjm.template-laporan.triwulan.index') }}"
                                            style="color: #5B9BD5;">Kelola template</a>
                                    </small>
                                </div>

                                <div class="col-md-12 mb-0">
                                    <button type="button" id="btn-create-draft" class="btn btn-primary">
                                        <i class="bi bi-plus-circle"></i> Buat Laporan Draft
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
                    <div class="monitoring-card mb-4" id="ai-prompt-section">
                        <div class="monitoring-header">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-pencil-square" style="color: #5B9BD5;"></i>
                                <h6 class="mb-0">Instruksi & Referensi Laporan</h6>
                            </div>
                        </div>
                        <div style="padding: 1.25rem;">

                            <!-- Chat box -->
                            <div class="ai-chat-wrapper mb-3">
                                <!-- Header -->
                                <div class="ai-chat-header">
                                    <div class="ai-avatar"><i class="bi bi-chat-dots"></i></div>
                                    <div>
                                        <h6>Asisten Pembuatan Laporan</h6>
                                        <small>Upload multiple files & gambar. AI akan menganalisis dokumen, foto kegiatan,
                                            dan mengintegrasikan data GKM untuk hasil yang lebih akurat.</small>
                                    </div>
                                </div>

                                <!-- Messages -->
                                <div class="ai-messages" id="ai-messages">
                                </div>

                                <!-- Typing indicator -->
                                <div class="ai-messages pt-0" style="min-height:0;">
                                    <div class="msg-ai typing-indicator" id="typing-indicator">
                                        <div class="ai-icon"><i class="bi bi-robot"></i></div>
                                        <div class="bubble" style="padding:0.6rem 1rem;">
                                            <div class="typing-dots">
                                                <span></span><span></span><span></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Input -->
                                <div class="ai-input-area">
                                    <!-- Textarea with button inside -->
                                    <div style="position: relative; margin-bottom: 0.5rem;">
                                        <textarea id="ai-prompt-input" class="ai-textarea"
                                            placeholder="Contoh: Buatkan laporan triwulan dengan ringkasan evaluasi kurikulum, tingkat kelulusan, dan rekomendasi perbaikan berdasarkan laporan GJM yang saya upload..."
                                            rows="3"></textarea>
                                        <button type="button" id="btn-ask-ai" class="btn-ask-ai"
                                            title="Generate Laporan">
                                            <span class="arrow-icon">↑</span>
                                        </button>
                                    </div>

                                    <!-- Multiple File upload -->
                                    <div>
                                        <label for="file_referensi_triwulan" class="file-upload-zone"
                                            id="file-label-triwulan">
                                            <i class="bi bi-paperclip"></i>
                                            <span id="file-name-triwulan">Lampirkan File & Gambar (DOCX/PDF/TXT/JPG/PNG) -
                                                Multiple files</span>
                                        </label>
                                        <input type="file" id="file_referensi_triwulan" name="file_referensi[]"
                                            accept=".docx,.doc,.pdf,.txt,.xlsx,.xls,.jpg,.jpeg,.png,.gif,.bmp,.webp"
                                            multiple style="display:none;">
                                    </div>

                                    <!-- Selected files display -->
                                    <div id="selected-files-display" style="display:none; margin-top:0.5rem;">
                                        <div style="font-size:0.75rem; color:#64748b; margin-bottom:0.3rem;">File terpilih:
                                        </div>
                                        <div id="selected-files-list" style="display:flex; flex-wrap:wrap; gap:0.3rem;">
                                        </div>
                                    </div>
                                    <!-- AI Reading Status -->
                                    <div id="ai-reading-status"
                                        style="display:none; margin-top:0.5rem; padding:0.6rem 1rem; border-radius:8px; background:#eff6ff; border:1px solid #bfdbfe; font-size:0.8rem; color:#1d4ed8;">
                                        <span class="spinner-border spinner-border-sm me-2"
                                            style="width:14px;height:14px;"></span>
                                        <strong>AI sedang membaca file dan membuat summary...</strong> Hasil akan muncul di
                                        Preview Draft Laporan.
                                    </div>
                                </div>
                            </div>

                            <!-- ===== PREVIEW PANEL ===== -->
                            <div id="preview-panel">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i class="bi bi-eye" style="color:#2563eb; font-size:1.1rem;"></i>
                                    <h6 class="mb-0" style="font-weight:700; color:#1e40af;">Preview Draft Laporan</h6>
                                    <button type="button" id="btn-clear-preview"
                                        class="btn btn-sm btn-outline-secondary ms-auto" style="font-size:0.75rem;">
                                        <i class="bi bi-x-circle"></i> Bersihkan
                                    </button>
                                </div>

                                <div id="preview-sections"></div>

                                <!-- Generate bar -->
                                <div class="generate-bar mt-3">
                                    <div>
                                        <p><i class="bi bi-check-circle-fill" style="color:#4ade80;"></i> Draft siap! Klik
                                            tombol untuk membuat laporan Word menggunakan template yang dipilih.</p>
                                    </div>
                                    <button type="button" id="btn-generate-word" class="btn-generate-word">
                                        <i class="bi bi-file-earmark-word-fill"></i>
                                        Generate Laporan Word
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Bottom action -->
                    <div class="d-flex justify-content-start gap-2 mb-4">
                        <a href="{{ route('gjm.buat-laporan.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ================================================================
            // DOM refs
            // ================================================================
            const promptInput = document.getElementById('ai-prompt-input');
            const btnAskAI = document.getElementById('btn-ask-ai');
            const messagesBox = document.getElementById('ai-messages');
            const typingIndicator = document.getElementById('typing-indicator');
            const previewPanel = document.getElementById('preview-panel');
            const previewSections = document.getElementById('preview-sections');
            const btnGenerateWord = document.getElementById('btn-generate-word');
            const btnClearPreview = document.getElementById('btn-clear-preview');
            const fileInput = document.getElementById('file_referensi_triwulan');
            const fileLabel = document.getElementById('file-label-triwulan');
            const fileNameEl = document.getElementById('file-name-triwulan');
            const aiPreviewData = document.getElementById('ai_preview_data');
            const laporanForm = document.getElementById('laporanForm');

            let currentPreviewText = '';
            let selectedFiles = []; // Array untuk multiple files

            // ================================================================
            // Multiple File upload handling
            // ================================================================
            fileInput.addEventListener('change', function() {
                const files = Array.from(this.files);
                if (files.length === 0) return;
                // Add new files to selectedFiles array
                selectedFiles = [...selectedFiles, ...files];

                updateFileDisplay();

                // Update main label
                fileLabel.classList.add('has-file');
                fileNameEl.textContent = `${selectedFiles.length} file terpilih`;

                // Give hint to user
                if (!promptInput.value.trim()) {
                    promptInput.placeholder =
                        `${selectedFiles.length} file terpilih! Ketik instruksi Anda (misal: rangkum dan buat draft laporan triwulan), lalu klik ↑`;
                    promptInput.focus();
                }
            });

            function updateFileDisplay() {
                const display = document.getElementById('selected-files-display');
                const list = document.getElementById('selected-files-list');

                if (selectedFiles.length === 0) {
                    display.style.display = 'none';
                    return;
                }

                display.style.display = 'block';
                list.innerHTML = '';

                selectedFiles.forEach((file, index) => {
                    const chip = document.createElement('div');
                    chip.className = 'file-chip';

                    const icon = getFileIcon(file.name);

                    // Create icon element
                    const iconEl = document.createElement('i');
                    iconEl.className = 'bi ' + icon;

                    // Create file name span
                    const nameSpan = document.createElement('span');
                    nameSpan.className = 'file-name';
                    nameSpan.title = file.name;
                    nameSpan.textContent = file.name;

                    // Create remove button
                    const removeSpan = document.createElement('span');
                    removeSpan.className = 'remove-file';
                    removeSpan.title = 'Hapus file';
                    removeSpan.textContent = '×';
                    removeSpan.onclick = function() {
                        removeFile(index);
                    };

                    // Append all elements
                    chip.appendChild(iconEl);
                    chip.appendChild(nameSpan);
                    chip.appendChild(removeSpan);

                    list.appendChild(chip);
                });
            }

            function getFileIcon(fileName) {
                const ext = fileName.split('.').pop().toLowerCase();
                const iconMap = {
                    'pdf': 'bi-file-earmark-pdf',
                    'docx': 'bi-file-earmark-word',
                    'doc': 'bi-file-earmark-word',
                    'txt': 'bi-file-earmark-text',
                    'xlsx': 'bi-file-earmark-excel',
                    'xls': 'bi-file-earmark-excel',
                    'jpg': 'bi-file-earmark-image',
                    'jpeg': 'bi-file-earmark-image',
                    'png': 'bi-file-earmark-image',
                    'gif': 'bi-file-earmark-image',
                    'bmp': 'bi-file-earmark-image',
                    'webp': 'bi-file-earmark-image'
                };
                return iconMap[ext] || 'bi-file-earmark';
            }

            window.removeFile = function(index) {
                selectedFiles.splice(index, 1);
                updateFileDisplay();

                if (selectedFiles.length === 0) {
                    fileLabel.classList.remove('has-file');
                    fileNameEl.textContent = 'Lampirkan File & Gambar (DOCX/PDF/TXT/JPG/PNG) - Multiple files';
                    promptInput.placeholder =
                        'Contoh: Buatkan laporan triwulan dengan ringkasan evaluasi kurikulum, tingkat kelulusan, dan rekomendasi perbaikan berdasarkan laporan GJM yang saya upload...';
                } else {
                    fileNameEl.textContent = `${selectedFiles.length} file terpilih`;
                }
            };

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

            // ================================================================
            // Simple markdown → HTML renderer
            // ================================================================
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

            // ================================================================
            // Append message bubble
            // ================================================================
            function appendUserMessage(text, files) {
                const div = document.createElement('div');
                div.className = 'msg-user';

                let fileChips = '';
                if (files && files.length > 0) {
                    fileChips = files.map(file =>
                        `<div class="file-chip"><i class="bi ${getFileIcon(file.name)}"></i> ${file.name}</div>`
                    ).join('');
                }

                div.innerHTML = `
            <div class="bubble">
                ${text.replace(/\n/g,'<br>')}
                ${fileChips}
            </div>`;
                messagesBox.appendChild(div);
                messagesBox.scrollTop = messagesBox.scrollHeight;
            }

            function appendAIMessage(html) {
                const div = document.createElement('div');
                div.className = 'msg-ai';
                div.innerHTML = `
            <div class="ai-icon"><i class="bi bi-robot"></i></div>
            <div class="bubble">${html}</div>`;
                messagesBox.appendChild(div);
                messagesBox.scrollTop = messagesBox.scrollHeight;
            }

            function showTyping() {
                typingIndicator.classList.add('show');
            }

            function hideTyping() {
                typingIndicator.classList.remove('show');
            }

            // ================================================================
            // Render preview sections
            // ================================================================
            function renderPreview(sections, rawText) {
                previewSections.innerHTML = '';
                currentPreviewText = rawText;
                aiPreviewData.value = rawText;

                if (!sections || sections.length === 0) {
                    previewSections.innerHTML = `<div class="preview-section-card">
                <div class="preview-section-body">${renderMarkdown(rawText)}</div>
            </div>`;
                } else {
                    sections.forEach(function(sec, idx) {
                        const card = document.createElement('div');
                        card.className = 'preview-section-card';
                        card.innerHTML = `
                    <div class="preview-section-header" data-idx="${idx}">
                        <span>${sec.title}</span>
                        <i class="bi bi-chevron-down" id="chevron-${idx}"></i>
                    </div>
                    <div class="preview-section-body" id="body-${idx}">
                        ${renderMarkdown(sec.content)}
                    </div>`;
                        previewSections.appendChild(card);
                    });

                    // Accordion toggle
                    previewSections.querySelectorAll('.preview-section-header').forEach(function(hdr) {
                        hdr.addEventListener('click', function() {
                            const idx = this.dataset.idx;
                            const body = document.getElementById('body-' + idx);
                            const chev = document.getElementById('chevron-' + idx);
                            const open = body.style.display !== 'none';
                            body.style.display = open ? 'none' : 'block';
                            chev.className = open ? 'bi bi-chevron-right' : 'bi bi-chevron-down';
                        });
                    });
                }

                previewPanel.classList.add('show');
                previewPanel.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }

            // ================================================================
            // Create Draft Laporan
            // ================================================================
            const btnCreateDraft = document.getElementById('btn-create-draft');
            btnCreateDraft.addEventListener('click', async function() {
                const periode = document.getElementById('periode_triwulan').value;
                const judul = document.getElementById('judul_laporan').value;

                if (!periode) {
                    alert('Pilih periode laporan terlebih dahulu.');
                    document.getElementById('periode_triwulan').focus();
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
                    formData.append('tipe_laporan', 'triwulan');
                    formData.append('periode_triwulan', periode);
                    formData.append('judul_laporan', judul);

                    const template = document.getElementById('template_id').value;
                    if (template) formData.append('template_id', template);

                    const response = await fetch('{{ route('gjm.buat-laporan.store') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (data.success && data.data && data.data.id) {
                        // Simpan laporan_id ke hidden field
                        document.getElementById('laporan_id').value = data.data.id;

                        // Disable periode, judul, template fields
                        document.getElementById('periode_triwulan').disabled = true;
                        document.getElementById('judul_laporan').disabled = true;
                        document.getElementById('template_id').disabled = true;

                        // Update button
                        this.innerHTML = '<i class="bi bi-check-circle"></i> Laporan Draft Dibuat';
                        this.classList.add('btn-success');
                        this.classList.remove('btn-primary');

                        // Show success message
                        appendAIMessage(
                            '<strong>✅ Laporan draft berhasil dibuat!</strong><br>Sekarang Anda bisa mulai chat dengan AI. Upload file referensi dan ketik instruksi Anda.'
                            );

                        // Focus ke prompt input
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

            // ================================================================
            // ASK AI — main action (multi-turn supported)
            // ================================================================
            btnAskAI.addEventListener('click', async function() {
                const prompt = promptInput.value.trim();
                const laporanId = document.getElementById('laporan_id').value;

                // Check if laporan_id exists
                if (!laporanId) {
                    alert('Silakan klik "Buat Laporan Draft" terlebih dahulu untuk membuat laporan.');
                    document.getElementById('btn-create-draft').focus();
                    return;
                }

                if (!prompt) {
                    promptInput.focus();
                    promptInput.style.border = '1.5px solid #ef4444';
                    setTimeout(() => promptInput.style.border = '', 1500);
                    return;
                }

                // Collect form values
                const periode = document.getElementById('periode_triwulan').value;
                const judul = document.getElementById('judul_laporan').value;
                const template = document.getElementById('template_id').value;

                // Append user bubble
                appendUserMessage(prompt, selectedFiles.length > 0 ? selectedFiles : null);
                promptInput.value = '';
                promptInput.style.height = 'auto';

                // Show typing
                showTyping();
                btnAskAI.disabled = true;
                btnAskAI.innerHTML =
                    '<span class="spinner-border spinner-border-sm"></span>';

                try {
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('prompt', prompt);
                    formData.append('laporan_id', laporanId); // PENTING: Kirim laporan_id
                    if (periode) formData.append('periode_triwulan', periode);
                    if (template) formData.append('template_id', template);

                    // Add files
                    selectedFiles.forEach(function(file) {
                        formData.append('file_referensi[]', file);
                    });

                    const response = await fetch('{{ route('gjm.buat-laporan.triwulan.ai-prompt') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (data.success) {
                        const aiResponse = data.response;
                        appendAIMessage(renderMarkdown(aiResponse));

                        // Parse sections for preview
                        const sections = parseMarkdownSections(aiResponse);
                        renderPreview(sections, aiResponse);

                        // Save AI preview to database for Word generation
                        saveAIPreviewToDatabase(laporanId, aiResponse, sections);

                        // Clear files after successful upload
                        selectedFiles = [];
                        updateFileDisplay();
                        fileLabel.classList.remove('has-file');
                        fileNameEl.textContent =
                            'Lampirkan File & Gambar (DOCX/PDF/TXT/JPG/PNG) - Multiple files';
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

            // ================================================================
            // Save AI Preview to Database
            // ================================================================
            async function saveAIPreviewToDatabase(laporanId, aiResponse, sections) {
                if (!laporanId) {
                    console.warn('No laporan ID available to save AI preview');
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('laporan_id', laporanId);
                    formData.append('ai_preview_draft', aiResponse);
                    formData.append('ai_sections', JSON.stringify(sections));

                    const response = await fetch('{{ route('gjm.buat-laporan.triwulan.save-preview') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (data.success) {
                        console.log('AI preview saved to database successfully');
                    } else {
                        console.warn('Failed to save AI preview to database:', data.message);
                    }
                } catch (error) {
                    console.error('Error saving AI preview to database:', error);
                }
            }

            // ================================================================
            // Parse markdown sections
            // ================================================================
            function parseMarkdownSections(text) {
                const lines = text.split('\n');
                const sections = [];
                let currentSection = null;

                lines.forEach(function(line) {
                    if (line.match(/^# /)) {
                        if (currentSection) sections.push(currentSection);
                        currentSection = {
                            title: line.replace(/^# /, ''),
                            content: ''
                        };
                    } else if (currentSection) {
                        currentSection.content += line + '\n';
                    }
                });

                if (currentSection) sections.push(currentSection);
                return sections;
            }

            // ================================================================
            // Clear preview
            // ================================================================
            btnClearPreview.addEventListener('click', function() {
                previewPanel.classList.remove('show');
                previewSections.innerHTML = '';
                currentPreviewText = '';
                aiPreviewData.value = '';
            });

            // ================================================================
            // Generate Word Document
            // ================================================================
            btnGenerateWord.addEventListener('click', async function() {
                const laporanId = document.getElementById('laporan_id').value;
                const aiPreviewData = document.getElementById('ai_preview_data').value;

                if (!laporanId) {
                    alert('Silakan buat laporan draft terlebih dahulu.');
                    document.getElementById('btn-create-draft').focus();
                    return;
                }

                if (!aiPreviewData) {
                    alert('Silakan chat dengan AI terlebih dahulu untuk membuat draft laporan.');
                    promptInput.focus();
                    return;
                }

                this.disabled = true;
                this.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('laporan_id', laporanId);
                formData.append('ai_preview_data', aiPreviewData);

                try {
                    const response = await fetch('{{ route('gjm.buat-laporan.triwulan.store') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        const err = await response.json().catch(() => ({}));
                        throw new Error(err.message || 'Server error ' + response.status);
                    }

                    const data = await response.json();
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        alert('Error: ' + (data.message || 'Gagal membuat laporan'));
                        this.disabled = false;
                        this.innerHTML =
                            '<i class="bi bi-file-earmark-word-fill"></i> Generate Laporan Word';
                    }
                } catch (err) {
                    alert('Terjadi kesalahan: ' + err.message);
                    this.disabled = false;
                    this.innerHTML =
                        '<i class="bi bi-file-earmark-word-fill"></i> Generate Laporan Word';
                }
            });

        });
    </script>
@endsection
