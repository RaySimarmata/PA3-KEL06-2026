@extends('layouts.app')

@section('page-title', 'Buat Laporan Semester')

@section('styles')
    <style>
        /* ===============================================================
                               AI PROMPT ASSISTANT — SEMESTER
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
            padding: 0.75rem 1.25rem;
            cursor: pointer;
            transition: all 0.2s;
            background: #f8fbff;
            text-align: center;
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
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

        /* Preview sections inside AI bubble */
        .preview-in-chat {
            margin-top: 1rem;
            border-top: 1px solid #e5e7eb;
            padding-top: 1rem;
        }

        .preview-section-mini {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            overflow: hidden;
        }

        .preview-section-mini-header {
            background: #f3f4f6;
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: #374151;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .preview-section-mini-body {
            padding: 0.75rem;
            font-size: 0.8rem;
            color: #4b5563;
            line-height: 1.6;
            max-height: 200px;
            overflow-y: auto;
            display: none;
        }

        .preview-section-mini-body.show {
            display: block;
        }

        /* Button inline styles */
        .btn-ask-ai-inline {
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex; /* Always visible */
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-left: 0.5rem;
        }

        .btn-ask-ai-inline:hover {
            transform: scale(1.05);
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
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

        .btn-attachment-inline {
            background: transparent;
            color: #6b7280;
            border: none;
            width: 36px;
            height: 36px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border-radius: 50%;
            margin-right: 0.25rem;
        }

        .btn-attachment-inline:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-attachment-inline.has-files {
            background: #dbeafe;
            color: #2563eb;
        }

        .btn-attachment-inline i {
            font-size: 1.1rem;
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
                            <div style="font-weight: 600; color: #333;">Buat Laporan Semester</div>
                            <p class="text-muted mb-0" style="font-size: 0.875rem;">Sistem akan membantu Anda menghasilkan
                                laporan semester strategis dengan mudah dan cepat</p>
                        </div>
                    </h5>
                </div>
                <a href="{{ route('gjm.buat-laporan.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-11 col-lg-12 mx-auto">
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
                                    <strong>File yang didukung:</strong> DOCX, PDF, JPG, PNG<br>
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
                    <input type="hidden" name="tipe_laporan" value="semester">
                    <input type="hidden" id="laporan_id" name="laporan_id" value="">
                    <input type="hidden" id="ai_preview_data" name="ai_preview_data" value="">

                    <!-- ===== INFORMASI LAPORAN ===== -->
                    <div class="monitoring-card mb-4">
                        <div class="monitoring-header">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-info-circle" style="color: #5B9BD5;"></i>
                                <h6 class="mb-0">Informasi Laporan</h6>
                            </div>
                            <a href="{{ route('gjm.template-laporan.semester.index') }}"
                                class="btn btn-sm btn-outline-primary btn-template-link">
                                <i class="bi bi-file-earmark-text me-1"></i> Kelola Template
                            </a>
                        </div>
                        <div style="padding: 1.5rem;">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="filter-label">Periode Laporan <span class="text-danger">*</span></label>
                                    <select class="form-select" name="periode_semester" id="periode_semester" required>
                                        <option value="">Pilih Periode Semester</option>
                                        <option value="ganjil">Semester Ganjil</option>
                                        <option value="genap">Semester Genap</option>
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
                                        <a href="{{ route('gjm.template-laporan.semester.index') }}"
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
                    <div class="monitoring-card mb-4" id="ai-prompt-section" style="overflow: hidden;">
                        <div class="monitoring-header">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-pencil-square" style="color: #5B9BD5;"></i>
                                <h6 class="mb-0">Instruksi & Referensi Laporan</h6>
                            </div>
                        </div>
                        <div style="padding: 1.5rem;">
                            <!-- Laporan Semester AI Assistant Interface -->
                            <style>
                                .ai-assistant-container {
                                    background: #1a2332;
                                    min-height: 600px;
                                    display: flex;
                                    flex-direction: column;
                                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                                    border-radius: 12px;
                                    overflow: hidden;
                                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                                    border: 1px solid #374151;
                                }

                                .ai-assistant-header {
                                    background: #1f2937;
                                    padding: 1rem 1.5rem;
                                    display: flex;
                                    align-items: center;
                                    justify-content: space-between;
                                    border-bottom: 1px solid #374151;
                                }

                                .ai-assistant-logo {
                                    display: flex;
                                    align-items: center;
                                    gap: 0.75rem;
                                }

                                .ai-assistant-icon {
                                    width: 40px;
                                    height: 40px;
                                    background: #2563eb;
                                    border-radius: 8px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    color: white;
                                    font-size: 1.25rem;
                                }

                                .ai-assistant-title {
                                    color: white;
                                    font-size: 1rem;
                                    font-weight: 600;
                                    margin: 0;
                                }

                                .ai-assistant-badge {
                                    background: #1e40af;
                                    color: #60a5fa;
                                    padding: 0.25rem 0.75rem;
                                    border-radius: 4px;
                                    font-size: 0.7rem;
                                    font-weight: 700;
                                    letter-spacing: 0.5px;
                                    margin-left: 0.5rem;
                                }

                                .ai-assistant-actions {
                                    display: flex;
                                    gap: 0.5rem;
                                }

                                .ai-assistant-btn-icon {
                                    background: transparent;
                                    border: none;
                                    color: #9ca3af;
                                    padding: 0.5rem;
                                    cursor: pointer;
                                    border-radius: 6px;
                                    transition: all 0.2s;
                                }

                                .ai-assistant-btn-icon:hover {
                                    background: #374151;
                                    color: white;
                                }

                                /* Prevent code display on button click */
                                .ai-assistant-btn-icon:focus,
                                .ai-assistant-btn-send:focus {
                                    outline: none;
                                    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.3);
                                }

                                .ai-assistant-btn-icon:active,
                                .ai-assistant-btn-send:active {
                                    transform: scale(0.95);
                                }

                                .ai-assistant-messages {
                                    flex: 1;
                                    overflow-y: auto;
                                    padding: 2rem 1.5rem;
                                    min-height: 400px;
                                }

                                /* Custom Scrollbar */
                                .ai-assistant-messages::-webkit-scrollbar {
                                    width: 6px;
                                }

                                .ai-assistant-messages::-webkit-scrollbar-track {
                                    background: rgba(255, 255, 255, 0.05);
                                    border-radius: 10px;
                                }

                                .ai-assistant-messages::-webkit-scrollbar-thumb {
                                    background: rgba(37, 99, 235, 0.3);
                                    border-radius: 10px;
                                }

                                .ai-assistant-messages::-webkit-scrollbar-thumb:hover {
                                    background: rgba(37, 99, 235, 0.5);
                                }

                                .ai-message-wrapper {
                                    display: flex;
                                    align-items: flex-start;
                                    gap: 1rem;
                                    margin-bottom: 1.5rem;
                                }

                                .ai-message-avatar {
                                    width: 40px;
                                    height: 40px;
                                    background: #2563eb;
                                    border-radius: 50%;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    flex-shrink: 0;
                                }

                                .ai-message-bubble {
                                    background: #2d3748;
                                    color: #e5e7eb;
                                    padding: 1rem 1.25rem;
                                    border-radius: 12px;
                                    max-width: 600px;
                                    font-size: 0.95rem;
                                    line-height: 1.6;
                                }

                                .ai-assistant-input-area {
                                    padding: 1.5rem;
                                    background: #1a2332;
                                    border-top: 1px solid #374151;
                                }

                                .ai-assistant-input-wrapper {
                                    max-width: 800px;
                                    margin: 0 auto;
                                    background: #2d3748;
                                    border: 1px solid #4b5563;
                                    border-radius: 12px;
                                    padding: 0.5rem;
                                    display: flex;
                                    align-items: center;
                                    gap: 0.5rem;
                                }

                                .ai-assistant-input {
                                    flex: 1;
                                    background: transparent;
                                    border: none;
                                    outline: none;
                                    color: #9ca3af;
                                    font-size: 0.95rem;
                                    padding: 0.5rem;
                                }

                                .ai-assistant-input::placeholder {
                                    color: #6b7280;
                                }

                                .ai-assistant-btn-send {
                                    background: #2563eb;
                                    border: none;
                                    color: white;
                                    width: 40px;
                                    height: 40px;
                                    border-radius: 8px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    cursor: pointer;
                                    transition: all 0.2s;
                                }

                                .ai-assistant-btn-send:hover {
                                    background: #1d4ed8;
                                }

                                .ai-assistant-footer-text {
                                    text-align: center;
                                    color: #6b7280;
                                    font-size: 0.75rem;
                                    margin-top: 0.75rem;
                                }
                            </style>

                            <!-- AI Chat Interface -->
                            <div class="ai-chat-wrapper">
                                <div class="ai-chat-header">
                                    <div class="ai-avatar">
                                        <i class="bi bi-stars"></i>
                                    </div>
                                    <div>
                                        <h6>AI Assistant - Laporan Semester</h6>
                                        <small>Siap membantu Anda membuat laporan</small>
                                    </div>
                                </div>

                                <!-- Messages Area -->
                                <div class="ai-messages" id="ai-messages">
                                    <!-- Welcome message will be added by JavaScript -->
                                </div>

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

                                <!-- Input Area -->
                                <div class="ai-input-area">
                                    <!-- Hidden File Inputs -->
                                    <input type="file" id="file_referensi_semester" name="file_referensi[]"
                                        accept=".docx,.pdf,.txt,.xlsx,.xls,.jpg,.jpeg,.png,.gif,.bmp,.webp" multiple
                                        style="display: none;">
                                    <input type="file" id="ocr_images_semester" name="ocr_images[]"
                                        accept=".jpg,.jpeg,.png,.pdf" multiple style="display: none;">

                                    <!-- Input Container with Attachment Icon Inside -->
                                    <div
                                        style="position: relative; display: flex; align-items: center; border: 1.5px solid #d1dff5; border-radius: 12px; background: #fff; padding: 0.5rem;">
                                        <!-- Attachment Button (Inside Left) -->
                                        <button type="button" class="btn-attachment-inline" id="btn-attachment"
                                            title="Lampirkan File & Gambar">
                                            <i class="bi bi-paperclip"></i>
                                        </button>

                                        <!-- Textarea -->
                                        <textarea class="ai-textarea-inline" id="ai-prompt-input" placeholder="Deskripsikan laporan semester yang ingin Anda buat..."></textarea>

                                        <!-- Send Button (Inside Right) - Hidden by default -->
                                        <button type="button" class="btn-ask-ai-inline" id="btn-ask-ai">
                                            <span class="arrow-icon">↑</span>
                                        </button>
                                    </div>

                                    <!-- Selected Files & Images Display (Combined) -->
                                    <div id="all-attachments-display" style="display: none; margin-top: 0.75rem;">
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;"
                                            id="all-attachments-list"></div>
                                    </div>

                                    <!-- OCR Processing Status -->
                                    <div id="ocr-processing-status"
                                        style="display: none; margin-top: 0.75rem; padding: 0.75rem; background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px; font-size: 0.875rem; color: #0369a1;">
                                        <i class="bi bi-hourglass-split me-2"></i>
                                        <strong>Memproses OCR...</strong> Mengekstrak teks dari gambar yang Anda upload.
                                    </div>
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

            const fileInput = document.getElementById('file_referensi_semester');
            const fileLabel = document.getElementById('file-label-semester');
            const fileNameEl = document.getElementById('file-name-semester');
            const aiPreviewData = document.getElementById('ai_preview_data');
            const laporanForm = document.getElementById('laporanForm');

            let currentPreviewText = '';
            let selectedFiles = []; // Array untuk multiple files
            let selectedOCRImages = []; // Array untuk OCR images
            let ocrExtractedText = ''; // Store OCR extracted text

            // ================================================================
            // Multiple File upload handling
            // ================================================================
            fileInput.addEventListener('change', function() {
                const files = Array.from(this.files);
                if (files.length === 0) return;

                // Add new files to selectedFiles array
                selectedFiles = [...selectedFiles, ...files];

                updateAllAttachmentsDisplay();

                // Give hint to user
                if (!promptInput.value.trim()) {
                    promptInput.placeholder =
                        `${selectedFiles.length + selectedOCRImages.length} file terpilih! Ketik instruksi Anda (opsional), lalu klik ↑ untuk mengirim`;
                    promptInput.focus();
                }
            });

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
                        'Contoh: Buatkan laporan semester dengan ringkasan evaluasi kurikulum, tingkat kelulusan, dan rekomendasi perbaikan berdasarkan laporan GJM yang saya upload...';
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
            // Process OCR Images (if needed)
            // ================================================================            // ================================================================
            // Process OCR Images
            // ================================================================
            async function processOCRImages(laporanId) {
                if (selectedOCRImages.length === 0 || !laporanId) return;

                const statusEl = document.getElementById('ocr-processing-status');
                statusEl.style.display = 'block';

                try {
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('laporan_id', laporanId);

                    selectedOCRImages.forEach(function(file) {
                        formData.append('images[]', file);
                    });

                    const response = await fetch('{{ route('gjm.buat-laporan.ocr.upload') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (data.success) {
                        ocrExtractedText = data.data.extracted_text || '';
                        const successCount = data.data.successful_images || 0;
                        const totalCount = data.data.images_count || selectedOCRImages.length;
                        const textLength = data.data.text_length || 0;
                        const procMs = data.data.processing_time_ms || 0;
                        const procSec = procMs > 0 ? Math.round(procMs / 1000) : '< 1';

                        // Different message depending on whether real OCR or GD fallback ran
                        let ocrMsg;
                        if (successCount > 0 && textLength > 0) {
                            ocrMsg =
                                `<strong>📸 OCR berhasil!</strong><br>` +
                                `✅ ${successCount} dari ${totalCount} gambar berhasil diproses<br>` +
                                `📝 ${textLength.toLocaleString()} karakter teks diekstrak<br>` +
                                `⏱️ Waktu proses: ${procSec}s<br><br>` +
                                `<em>Teks OCR telah diintegrasikan dengan AI. Ketik instruksi Anda untuk membuat laporan!</em>`;
                        } else {
                            // GD fallback — images registered but no text extracted (Tesseract not installed)
                            ocrMsg =
                                `<strong>📸 ${totalCount} gambar terdaftar!</strong><br>` +
                                `ℹ️ OCR otomatis tidak tersedia (Tesseract belum terinstall).<br>` +
                                `📌 Metadata gambar sudah diintegrasikan dengan AI.<br><br>` +
                                `<em>💡 Tips: Deskripsikan isi gambar dalam instruksi Anda agar AI dapat membantu lebih akurat.</em>`;
                        }
                        appendAIMessage(ocrMsg);

                        // Update prompt placeholder
                        if (!promptInput.value.trim()) {
                            promptInput.placeholder = successCount > 0 ?
                                `📸 OCR selesai! Ketik instruksi (misal: "Buat laporan berdasarkan data dan gambar yang saya upload")` :
                                `📸 ${totalCount} gambar terdaftar. Ketik instruksi dan deskripsikan isi gambar jika perlu.`;
                            promptInput.focus();
                        }

                        // Clear OCR images after successful processing
                        selectedOCRImages = [];
                        updateOCRImagesDisplay();
                        ocrLabel.classList.remove('has-file');
                        ocrNameEl.textContent = '📸 Upload Gambar untuk OCR (JPG/PNG/PDF) - Max 15 files';

                    } else {
                        appendAIMessage(`<strong>❌ OCR Error:</strong> ${data.message}`);
                    }
                } catch (error) {
                    appendAIMessage(`<strong>❌ OCR Error:</strong> ${error.message}`);
                } finally {
                    statusEl.style.display = 'none';
                }
            }

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

            <div class="bubble">${html}</div>`;
                messagesBox.appendChild(div);
                messagesBox.scrollTop = messagesBox.scrollHeight;
            }

            function appendAIMessageWithPreview(aiResponse, sections, cacheInfoHTML = '') {
                const div = document.createElement('div');
                div.className = 'msg-ai';

                // Hanya tampilkan tombol Generate Word tanpa preview sections
                let generateButtonHTML = '';
                if (sections && sections.length > 0) {
                    generateButtonHTML = `
                        <div style="margin-top: 1rem; border-top: 1px solid #e5e7eb; padding-top: 1rem;">
                            <button type="button" class="btn-generate-word-inline" onclick="generateWordFromChat()">
                                <i class="bi bi-file-earmark-word-fill"></i>
                                Generate Laporan Word
                            </button>
                        </div>
                    `;
                }

                div.innerHTML = `

                    <div class="bubble">
                        ${cacheInfoHTML}
                        ${renderMarkdown(aiResponse)}
                        ${generateButtonHTML}
                    </div>
                `;

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
            // Create Draft Laporan
            // ================================================================
            const btnCreateDraft = document.getElementById('btn-create-draft');
            btnCreateDraft.addEventListener('click', async function() {
                const periode = document.getElementById('periode_semester').value;
                const judul = document.getElementById('judul_laporan').value;

                if (!periode) {
                    alert('Pilih periode laporan terlebih dahulu.');
                    document.getElementById('periode_semester').focus();
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
                    formData.append('tipe_laporan', 'semester');
                    formData.append('periode_semester', periode);
                    formData.append('judul_laporan', judul);

                    const template = document.getElementById('template_id').value;
                    if (template) formData.append('template_id', template);

                    const response = await fetch(
                        '{{ route('gjm.buat-laporan.semester.create-draft') }}', {
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
                        document.getElementById('periode_semester').disabled = true;
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

                        // Process OCR images if any were uploaded before draft creation
                        if (selectedOCRImages.length > 0) {
                            processOCRImages(data.data.id);
                        }
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

                // Process OCR images first if any
                if (selectedOCRImages.length > 0) {
                    await processOCRImages(laporanId);
                }

                if (!prompt) {
                    promptInput.focus();
                    promptInput.style.border = '1.5px solid #ef4444';
                    setTimeout(() => promptInput.style.border = '', 1500);
                    return;
                }

                // Collect form values
                const periode = document.getElementById('periode_semester').value;
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
                    if (periode) formData.append('periode_semester', periode);
                    if (judul) formData.append('judul_laporan', judul);
                    if (template) formData.append('template_id', template);

                    // Append multiple files
                    selectedFiles.forEach((file, index) => {
                        formData.append(`file_referensi[${index}]`, file);
                    });

                    // Multi-turn: kirim draft sebelumnya agar AI bisa follow-up
                    if (currentPreviewText) formData.append('previous_draft', currentPreviewText);

                    const response = await fetch(
                    '{{ route('gjm.buat-laporan.semester.ai-prompt') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json'
                        },
                    });

                    const data = await response.json();
                    hideTyping();

                    if (data.success) {
                        // Parse sections from preview
                        const sections = parseMarkdownSections(data.preview);

                        // Save to hidden field for Word generation
                        currentPreviewText = data.preview;
                        aiPreviewData.value = data.preview;

                        // Show AI message with preview inside chat
                        appendAIMessageWithPreview(data.preview, sections);

                        // Reset files after successful send
                        selectedFiles = [];
                        fileInput.value = '';
                        updateFileDisplay();
                        fileNameEl.textContent =
                            'Lampirkan File & Gambar (DOCX/PDF/TXT/JPG/PNG) - Multiple files';
                        fileLabel.classList.remove('has-file');
                    } else {
                        const errorMessage = data.message || 'Gagal mendapatkan respons dari AI.';
                        appendAIMessage(
                            `<span style="color:#dc2626;">${errorMessage}</span>`
                        );
                    }
                } catch (err) {
                    hideTyping();
                    appendAIMessage(
                        `<span style="color:#dc2626;">⚠ Terjadi kesalahan koneksi: ${err.message}</span>`
                    );
                } finally {
                    btnAskAI.disabled = false;
                    btnAskAI.innerHTML = '<span class="arrow-icon">↑</span>';
                }
            });

            // ================================================================
            // GENERATE LAPORAN WORD from chat button
            // ================================================================
            window.generateWordFromChat = async function() {
                const laporanId = document.getElementById('laporan_id').value;
                const aiPreviewDataValue = document.getElementById('ai_preview_data').value;

                if (!laporanId) {
                    alert('Silakan klik "Buat Laporan Draft" terlebih dahulu untuk membuat laporan.');
                    document.getElementById('btn-create-draft').focus();
                    return;
                }

                if (!aiPreviewDataValue) {
                    alert('Silakan chat dengan AI terlebih dahulu untuk membuat draft laporan.');
                    promptInput.focus();
                    return;
                }

                // Disable button
                const btn = event.target.closest('.btn-generate-word-inline');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';
                }

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('laporan_id', laporanId);
                formData.append('ai_preview_data', aiPreviewDataValue);

                try {
                    const response = await fetch('{{ route('gjm.buat-laporan.semester.store') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Server error ' + response.status);
                    }

                    // Check if response is a file (Word document)
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/vnd.openxmlformats')) {
                        // Download file
                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;

                        // Get filename from Content-Disposition header or use default
                        const contentDisposition = response.headers.get('content-disposition');
                        let filename = 'Laporan_Semester_' + Date.now() + '.docx';
                        if (contentDisposition) {
                            const filenameMatch = contentDisposition.match(/filename="?(.+)"?/i);
                            if (filenameMatch) filename = filenameMatch[1];
                        }

                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);

                        // Show browser notification if supported
                        if ('Notification' in window && Notification.permission === 'granted') {
                            new Notification('Download Selesai', {
                                body: 'File ' + filename + ' berhasil didownload',
                                icon: '/favicon.ico'
                            });
                        }

                        // Re-enable button
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML =
                                '<i class="bi bi-file-earmark-word-fill"></i> Generate Laporan Word';
                        }

                        // Show success message in chat with file info
                        appendAIMessage(
                            '<p style="color:#16a34a;"><i class="bi bi-check-circle-fill"></i> <strong>Laporan Word berhasil di-generate dan didownload!</strong><br><small>📁 File tersimpan di folder <strong>Downloads</strong> Anda dengan nama: <strong>' +
                            filename + '</strong></small></p>');
                    } else {
                        // Response is JSON (error or redirect)
                        const data = await response.json();
                        if (data.success) {
                            appendAIMessage(
                                '<p style="color:#16a34a;"><i class="bi bi-check-circle-fill"></i> <strong>Laporan berhasil dibuat!</strong></p>'
                                );
                        } else {
                            throw new Error(data.message || 'Gagal membuat laporan');
                        }

                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML =
                                '<i class="bi bi-file-earmark-word-fill"></i> Generate Laporan Word';
                        }
                    }
                } catch (err) {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML =
                        '<i class="bi bi-file-earmark-word-fill"></i> Generate Laporan Word';
                    }
                    appendAIMessage(
                        '<p style="color:#dc2626;"><i class="bi bi-x-circle-fill"></i> <strong>Gagal generate laporan:</strong> ' +
                        err.message + '</p>');
                }
            };

            // ================================================================
            // Attachment Button Handler - Combined File & Image Upload
            // ================================================================
            const btnAttachment = document.getElementById('btn-attachment');
            const ocrInput = document.getElementById('ocr_images_semester');

            // Show file picker menu when attachment button is clicked
            btnAttachment.addEventListener('click', function() {
                // Create a simple menu to choose file type
                const menu = document.createElement('div');
                menu.style.cssText = `
                    position: absolute;
                    bottom: 60px;
                    left: 10px;
                    background: white;
                    border: 1px solid #d1d5db;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    z-index: 1000;
                    min-width: 200px;
                `;

                menu.innerHTML = `
                    <div style="padding: 0.5rem;">
                        <button type="button" class="attachment-menu-item" data-type="file">
                            <i class="bi bi-file-earmark"></i> File Dokumen
                        </button>
                        <button type="button" class="attachment-menu-item" data-type="image">
                            <i class="bi bi-image"></i> Gambar/Foto
                        </button>
                    </div>
                `;

                // Add menu styles
                const style = document.createElement('style');
                style.textContent = `
                    .attachment-menu-item {
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;
                        width: 100%;
                        padding: 0.6rem 0.75rem;
                        border: none;
                        background: transparent;
                        text-align: left;
                        cursor: pointer;
                        border-radius: 4px;
                        font-size: 0.875rem;
                        color: #374151;
                        transition: background 0.2s;
                    }
                    .attachment-menu-item:hover {
                        background: #f3f4f6;
                    }
                    .attachment-menu-item i {
                        font-size: 1rem;
                        color: #6b7280;
                    }
                `;
                document.head.appendChild(style);

                // Position menu relative to button
                const container = btnAttachment.closest('.ai-input-area');
                container.style.position = 'relative';
                container.appendChild(menu);

                // Handle menu item clicks
                menu.querySelectorAll('.attachment-menu-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const type = this.dataset.type;
                        if (type === 'file') {
                            fileInput.click();
                        } else if (type === 'image') {
                            ocrInput.click();
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
                }, 0);
            });

            // ================================================================
            // OCR Images upload handling
            // ================================================================
            ocrInput.addEventListener('change', function() {
                const files = Array.from(this.files);
                if (files.length === 0) return;

                // Add new OCR images to selectedOCRImages array
                selectedOCRImages = [...selectedOCRImages, ...files];

                updateAllAttachmentsDisplay();

                // Give hint to user
                if (!promptInput.value.trim()) {
                    promptInput.placeholder =
                        `${selectedFiles.length + selectedOCRImages.length} file terpilih! Ketik instruksi Anda (opsional), lalu klik ↑ untuk mengirim`;
                    promptInput.focus();
                }
            });

            // ================================================================
            // Toggle Send Button Visibility
            // ================================================================
            // Note: Button is always visible in this implementation
            // Keeping function for compatibility but not used
            function toggleSendButton() {
                // Button is always visible
                return;
            }

            // ================================================================
            // Update Combined Attachments Display
            // ================================================================
            function updateAllAttachmentsDisplay() {
                const display = document.getElementById('all-attachments-display');
                const list = document.getElementById('all-attachments-list');
                const btnAttachment = document.getElementById('btn-attachment');

                const totalFiles = selectedFiles.length + selectedOCRImages.length;

                if (totalFiles === 0) {
                    display.style.display = 'none';
                    btnAttachment.classList.remove('has-files');
                    return;
                }

                display.style.display = 'block';
                btnAttachment.classList.add('has-files');
                list.innerHTML = '';

                // Display regular files
                selectedFiles.forEach((file, index) => {
                    const chip = createFileChip(file, index, 'file');
                    list.appendChild(chip);
                });

                // Display OCR images with preview
                selectedOCRImages.forEach((file, index) => {
                    const chip = createImageChip(file, index);
                    list.appendChild(chip);
                });
            }

            // ================================================================
            // Create File Chip
            // ================================================================
            function createFileChip(file, index, type) {
                const chip = document.createElement('div');
                chip.className = 'file-chip';

                const icon = document.createElement('i');
                icon.className = 'bi ' + getFileIcon(file.name);

                const nameSpan = document.createElement('span');
                nameSpan.className = 'file-name';
                nameSpan.title = file.name;
                nameSpan.textContent = file.name;

                const removeSpan = document.createElement('span');
                removeSpan.className = 'remove-file';
                removeSpan.title = 'Hapus file';
                removeSpan.textContent = '×';
                removeSpan.onclick = function() {
                    removeFile(index);
                };

                chip.appendChild(icon);
                chip.appendChild(nameSpan);
                chip.appendChild(removeSpan);

                return chip;
            }

            // ================================================================
            // Create Image Chip with Preview
            // ================================================================
            function createImageChip(file, index) {
                const chip = document.createElement('div');
                chip.className = 'file-chip image-chip';

                // Create image preview
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.alt = file.name;
                img.onload = function() {
                    URL.revokeObjectURL(this.src);
                };

                const nameSpan = document.createElement('span');
                nameSpan.className = 'file-name';
                nameSpan.title = file.name;
                nameSpan.textContent = file.name;
                nameSpan.style.fontSize = '0.7rem';
                nameSpan.style.textAlign = 'center';

                const removeSpan = document.createElement('span');
                removeSpan.className = 'remove-file';
                removeSpan.title = 'Hapus gambar';
                removeSpan.textContent = '×';
                removeSpan.onclick = function() {
                    removeOCRImage(index);
                };

                chip.appendChild(img);
                chip.appendChild(nameSpan);
                chip.appendChild(removeSpan);

                return chip;
            }

            // ================================================================
            // Remove File
            // ================================================================
            window.removeFile = function(index) {
                selectedFiles.splice(index, 1);
                updateAllAttachmentsDisplay();

                if (!promptInput.value.trim() && (selectedFiles.length + selectedOCRImages.length) > 0) {
                    promptInput.placeholder =
                        `${selectedFiles.length + selectedOCRImages.length} file terpilih! Ketik instruksi Anda (opsional), lalu klik ↑ untuk mengirim`;
                } else if (selectedFiles.length === 0 && selectedOCRImages.length === 0) {
                    promptInput.placeholder = 'Deskripsikan laporan semester yang ingin Anda buat...';
                }
            };

            // ================================================================
            // Remove OCR Image
            // ================================================================
            window.removeOCRImage = function(index) {
                selectedOCRImages.splice(index, 1);
                updateAllAttachmentsDisplay();

                if (!promptInput.value.trim() && (selectedFiles.length + selectedOCRImages.length) > 0) {
                    promptInput.placeholder =
                        `${selectedFiles.length + selectedOCRImages.length} file terpilih! Ketik instruksi Anda (opsional), lalu klik ↑ untuk mengirim`;
                } else if (selectedFiles.length === 0 && selectedOCRImages.length === 0) {
                    promptInput.placeholder = 'Deskripsikan laporan semester yang ingin Anda buat...';
                }
            };

            // ================================================================
            // Initialize welcome message
            // ================================================================
            appendAIMessage(
                '<strong>Selamat datang di AI Assistant Laporan Semester!</strong><br>Saya siap membantu Anda membuat laporan semester yang komprehensif. Silakan:<br><br>1. Klik <strong>"Buat Laporan Draft"</strong> terlebih dahulu<br>2. Upload file referensi jika ada<br>3. Ketik instruksi atau deskripsi laporan yang diinginkan<br>4. Saya akan membantu generate draft laporan untuk Anda'
            );

        });
    </script>
@endsection
