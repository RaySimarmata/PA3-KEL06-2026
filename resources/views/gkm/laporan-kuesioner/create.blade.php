@extends('layouts.app')

@section('page-title', 'Generate Laporan Baru')

@section('styles')
    <!-- Cache Busting: Force reload CSS and JS - Version 1.2.0 -->
    <meta name="cache-version" content="1.2.0-{{ time() }}">
    <style>
        /* ===============================================================
               AI PROMPT ASSISTANT — KUESIONER
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

        .user-msg-file-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 0.4rem 0.6rem;
            font-size: 0.75rem;
            color: #fff;
            max-width: 200px;
        }

        .user-msg-file-chip i {
            font-size: 0.9rem;
        }

        .user-msg-file-chip span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-msg-image-chip {
            display: flex;
            flex-direction: column;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            padding: 0.3rem;
            max-width: 150px;
        }

        .user-msg-image-chip img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 0.3rem;
        }

        .user-msg-image-chip .image-name {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.9);
            text-align: center;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding: 0 0.2rem;
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
            padding: 0.75rem 3.5rem 0.75rem 1rem;
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

        .ai-textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
            outline: none;
        }

        .ai-textarea::placeholder {
            color: #9ca3af;
        }

        .ai-input-area>div:first-child:focus-within {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
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
            gap: 0.35rem;
            background: #e0f2fe;
            border: 1px solid #0891b2;
            border-radius: 8px;
            padding: 0.4rem 0.6rem;
            font-size: 0.75rem;
            color: #0e7490;
            max-width: 200px;
            position: relative;
        }

        .file-chip.image-chip {
            flex-direction: column;
            max-width: 120px;
            padding: 0.3rem;
        }

        .file-chip.image-chip img {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 0.25rem;
        }

        .file-chip .file-name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1;
        }

        .file-chip .remove-file {
            cursor: pointer;
            color: #dc2626;
            font-weight: bold;
            margin-left: 0.3rem;
            font-size: 1rem;
            line-height: 1;
            transition: color 0.2s;
        }

        .file-chip .remove-file:hover {
            color: #991b1b;
        }

        .file-chip.image-chip .remove-file {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
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

        .btn-ask-ai-inline {
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
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

        .btn-attachment {
            background: #f3f4f6;
            color: #6b7280;
            border: 1.5px solid #d1d5db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-bottom: 8px;
        }

        .btn-attachment:hover {
            background: #e5e7eb;
            border-color: #9ca3af;
            color: #374151;
        }

        .btn-attachment.has-files {
            background: #dbeafe;
            border-color: #3b82f6;
            color: #2563eb;
        }

        .btn-attachment i {
            font-size: 1.1rem;
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

        /* ===============================================================
                                                                                                                                       SETTINGS MODAL STYLES
                                                                                                                                       =============================================================== */

        /* Modal overlay */
        .fixed {
            position: fixed;
        }

        .inset-0 {
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
        }

        .bg-black\/40 {
            background-color: rgba(0, 0, 0, 0.4);
        }

        .backdrop-blur-sm {
            backdrop-filter: blur(4px);
        }

        .hidden {
            display: none;
        }

        .z-50 {
            z-index: 50;
        }

        /* Modal container */
        .max-w-lg {
            max-width: 32rem;
        }

        .mx-auto {
            margin-left: auto;
            margin-right: auto;
        }

        .mt-24 {
            margin-top: 6rem;
        }

        .mb-8 {
            margin-bottom: 2rem;
        }

        .bg-white {
            background-color: white;
        }

        .dark\:bg-slate-900 {
            background-color: #0f172a;
        }

        .border {
            border-width: 1px;
        }

        .border-slate-200 {
            border-color: #e2e8f0;
        }

        .dark\:border-slate-800 {
            border-color: #1e293b;
        }

        .rounded-2xl {
            border-radius: 1rem;
        }

        .shadow-2xl {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .max-h-\[80vh\] {
            max-height: 80vh;
        }

        .overflow-y-auto {
            overflow-y: auto;
        }

        /* Modal header */
        .p-4 {
            padding: 1rem;
        }

        .border-b {
            border-bottom-width: 1px;
        }

        .flex {
            display: flex;
        }

        .items-center {
            align-items: center;
        }

        .justify-between {
            justify-content: space-between;
        }

        .text-sm {
            font-size: 0.875rem;
        }

        .font-bold {
            font-weight: 700;
        }

        .p-2 {
            padding: 0.5rem;
        }

        .text-slate-500 {
            color: #64748b;
        }

        .hover\:text-primary:hover {
            color: #2563eb;
        }

        /* Modal body */
        .space-y-4>*+* {
            margin-top: 1rem;
        }

        .space-y-3>*+* {
            margin-top: 0.75rem;
        }

        .text-xs {
            font-size: 0.75rem;
        }

        .text-slate-600 {
            color: #475569;
        }

        .dark\:text-slate-300 {
            color: #cbd5e1;
        }

        .mt-1 {
            margin-top: 0.25rem;
        }

        .w-full {
            width: 100%;
        }

        .bg-transparent {
            background-color: transparent;
        }

        .border-slate-300 {
            border-color: #cbd5e1;
        }

        .dark\:border-slate-700 {
            border-color: #334155;
        }

        .rounded-lg {
            border-radius: 0.5rem;
        }

        .text-\[10px\] {
            font-size: 10px;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        .rounded {
            border-radius: 0.25rem;
        }

        .border-t {
            border-top-width: 1px;
        }

        .pt-4 {
            padding-top: 1rem;
        }

        .mb-3 {
            margin-bottom: 0.75rem;
        }

        /* Modal footer */
        .justify-end {
            justify-content: flex-end;
        }

        .px-4 {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .py-2 {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        .hover\:bg-slate-100:hover {
            background-color: #f1f5f9;
        }

        .dark\:hover\:bg-slate-800:hover {
            background-color: #1e293b;
        }

        .border-primary {
            border-color: #2563eb;
        }

        .text-primary {
            color: #2563eb;
        }

        .hover\:bg-primary\/10:hover {
            background-color: rgba(37, 99, 235, 0.1);
        }

        .bg-primary {
            background-color: #2563eb;
        }

        .text-white {
            color: white;
        }

        .hover\:bg-primary\/90:hover {
            background-color: rgba(37, 99, 235, 0.9);
        }

        .pb-4 {
            padding-bottom: 1rem;
        }
    </style>

    <!-- OCR Upload Styles -->
    <link rel="stylesheet" href="{{ asset('css/ocr-upload.css') }}">
@endsection

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Sub Header -->
        <div class="monitoring-card mb-4" style="border-left: 4px solid #5B9BD5;">
            <div style="padding: 1.5rem;">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-file-earmark-text" style="color: #5B9BD5; font-size: 2rem;"></i>
                        <div>
                            <h6 class="mb-1" style="font-weight: 600; color: #333;">Generate Laporan Kuesioner Baru</h6>
                            <p class="text-muted mb-0" style="font-size: 0.875rem;">
                                AI Agent akan menganalisis data kuesioner kepuasan mahasiswa dalam periode yang dipilih
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('gkm.laporan-kuesioner.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
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
                                    <li><em>(Opsional)</em> Upload file referensi & gambar dokumentasi jika diperlukan
                                        <em>(multiple files supported)</em></li>
                                    <li>Klik <strong>tombol Generate</strong> untuk melihat preview draft laporan</li>
                                    <li>Klik <strong>Generate Laporan Word</strong> untuk mengunduh file .docx</li>
                                </ol>
                                <div class="mt-3" style="font-size: 0.8rem; color: #6b7280;">
                                    <strong>File yang didukung:</strong> DOCX, PDF, JPG, PNG<br>
                                    <strong>Fitur AI Vision:</strong> Analisis gambar dokumentasi kegiatan, daftar hadir<br>
                                    <strong>Integrasi GKM:</strong> Data monitoring RPS dan Materi otomatis digunakan
                                    sebagai konteks
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="laporanForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="tipe_laporan" value="kuesioner">
                    <input type="hidden" id="laporan_id" name="laporan_id" value="">
                    <input type="hidden" id="ai_preview_data" name="ai_preview_data" value="">

                    <!-- ===== INFORMASI LAPORAN ===== -->
                    <div class="monitoring-card mb-4">
                        <div class="monitoring-header">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-info-circle" style="color: #5B9BD5;"></i>
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
                            @endif

                            <!-- Submit Buttons -->
                            <div class="d-flex justify-content-between gap-2">
                                <a href="{{ route('gkm.laporan-kuesioner.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Batal
                                </a>
                                <button type="submit" class="btn-reminder">
                                    <i class="bi bi-robot"></i>
                                    <span>Generate dengan AI Agent</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection