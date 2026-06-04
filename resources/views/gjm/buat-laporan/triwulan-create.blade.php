@extends('layouts.app')

@section('page-title', 'Generate Laporan Baru')

@section('styles')
    <!-- Cache Busting: Force reload CSS and JS - Version 1.2.0 -->
    <meta name="cache-version" content="1.2.0-{{ time() }}">
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
            background: linear-gradient(135deg, #1e3c72, #7c3aed);
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
            border-color: #1e3c72;
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.12);
            outline: none;
        }

        .ai-textarea::placeholder {
            color: #9ca3af;
        }

        .ai-input-area>div:first-child:focus-within {
            border-color: #1e3c72;
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.12);
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
            border-color: #1e3c72;
            background: #f6f7fb;
            color: #1e3c72;
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
            background: rgba(30, 60, 114, 0.08);
            border: 1px solid #1e3c72;
            border-radius: 8px;
            padding: 0.4rem 0.6rem;
            font-size: 0.75rem;
            color: #1e3c72;
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
            background: linear-gradient(135deg, #1e3c72 0%, #1e3c72 100%);
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
            box-shadow: 0 2px 8px rgba(30, 60, 114, 0.3);
            flex-shrink: 0;
        }

        .btn-ask-ai:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 60, 114, 0.4);
            background: linear-gradient(135deg, #1e3c72 0%, #1e3c72 100%);
        }

        .btn-ask-ai:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: 0 2px 8px rgba(30, 60, 114, 0.3);
        }

        .btn-ask-ai .arrow-icon {
            font-size: 1.3rem;
            font-weight: bold;
            color: #fff;
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
            background: rgba(30, 60, 114, 0.06);
            border-color: #1e3c72;
            color: #1e3c72;
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
            background: rgba(30, 60, 114, 0.06);
            color: #1e3c72;
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
            color: #1e3c72;
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
            color: #1e3c72;
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
            border-color: #1e3c72;
        }

        .text-primary {
            color: #1e3c72;
        }

        .hover\:bg-primary\/10:hover {
            background-color: rgba(30, 60, 114, 0.08);
        }

        .bg-primary {
            background-color: #1e3c72;
        }

        .text-white {
            color: white;
        }

        .hover\:bg-primary\/90:hover {
            background-color: rgba(30, 60, 114, 0.9);
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
<<<<<<< Updated upstream
        <div class="monitoring-card mb-4" style="border-left: 4px solid #5B9BD5;">
            <div style="padding: 1.5rem;">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-file-earmark-text" style="color: #5B9BD5; font-size: 2rem;"></i>
                        <div>
                            <h6 class="mb-1" style="font-weight: 600; color: #333;">Generate Laporan Triwulan Baru</h6>
                            <p class="text-muted mb-0" style="font-size: 0.875rem;">
                                AI Agent akan menganalisis data kegiatan dan monitoring mutu dalam periode triwulan yang dipilih
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('gjm.buat-laporan.triwulan.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
=======
        <div class="monitoring-card mb-4" style="border-left: 4px solid #1e3c72;">
>>>>>>> Stashed changes
        </div>

        <div class="filter-card mb-4 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 font-semibold" style="text-transform: uppercase; letter-spacing: 0.5px;">
                Generate Laporan Triwulan Baru
            </h5>
            <a href="{{ route('gjm.buat-laporan.triwulan.index') }}" class="btn btn-secondary btn-sm">
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
                                    <li>Ketik instruksi atau deskripsi laporan yang diinginkan</li>
                                    <li>Upload file referensi & gambar dokumentasi <em>(multiple files supported)</em></li>
                                    <li>Klik <strong>tombol Generate</strong> untuk melihat preview draft laporan</li>
                                    <li>Klik <strong>Generate Laporan Word</strong> untuk mengunduh file .docx</li>
                                </ol>
                                <div class="mt-3" style="font-size: 0.8rem; color: #6b7280;">
                                    <strong>File yang didukung:</strong> DOCX, PDF, JPG, PNG<br>
                                    <strong>Fitur AI Vision:</strong> Analisis gambar dokumentasi kegiatan, daftar hadir<br>
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
                                <h6 class="mb-0">Informasi Laporan</h6>
                            </div>
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
                                <h6 class="mb-0">Instruksi dan Referensi Laporan</h6>
                            </div>
                        </div>
                        <div style="padding: 1.5rem;">
                            <!-- Laporan Triwulan AI Assistant Interface -->
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
                                    background: #1e3c72;
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
                                    background: #1e3c72;
                                    color: #ffffff;
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
                                    box-shadow: 0 0 0 2px rgba(30, 60, 114, 0.3);
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
                                    background: rgba(30, 60, 114, 0.3);
                                    border-radius: 10px;
                                }

                                .ai-assistant-messages::-webkit-scrollbar-thumb:hover {
                                    background: rgba(30, 60, 114, 0.5);
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
                                    background: #1e3c72;
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
                                    background: #1e3c72;
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
                                    background: #1e3c72;
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
                                        <h6>AI Assistant - Laporan Triwulan</h6>
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
                                    <input type="file" id="file_referensi_triwulan" name="file_referensi[]"
                                        accept=".docx,.pdf,.txt,.xlsx,.xls,.jpg,.jpeg,.png,.gif,.bmp,.webp" multiple
                                        style="display: none;">
                                    <input type="file" id="ocr_images_triwulan" name="ocr_images[]"
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
                                        <textarea class="ai-textarea-inline" id="ai-prompt-input" placeholder="Deskripsikan website yang ingin Anda buat..."></textarea>

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
                                        style="display: none; margin-top: 0.75rem; padding: 0.75rem; background: #f7fafc; border: 1px solid #1e3c72; border-radius: 8px; font-size: 0.875rem; color: #1e3c72;">
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
    <!-- OCR Upload JavaScript -->
    <script src="{{ asset('js/ocr-upload.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ================================================================
            // DOM refs
            // ================================================================
            const promptInput = document.getElementById('ai-prompt-input');
            const btnAskAI = document.getElementById('btn-ask-ai');
            const messagesBox = document.getElementById('ai-messages');
            const typingIndicator = document.getElementById('typing-indicator');
            const aiPreviewData = document.getElementById('ai_preview_data');
            const laporanForm = document.getElementById('laporanForm');

            let currentPreviewText = '';
            let selectedFiles = []; // Array untuk multiple files
            let selectedOCRImages = []; // Array untuk OCR images
            let ocrExtractedText = ''; // Store OCR text
            let conversationHistory = []; // Store conversation for multi-turn chat

            // ================================================================
            // Attachment Button Handler - Combined File & Image Upload
            // ================================================================
            const btnAttachment = document.getElementById('btn-attachment');
            const fileInput = document.getElementById('file_referensi_triwulan');
            const ocrInput = document.getElementById('ocr_images_triwulan');

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
            // Multiple File upload handling
            // ================================================================
            fileInput.addEventListener('change', function() {
                const files = Array.from(this.files);
                if (files.length === 0) return;
                // Add new files to selectedFiles array
                selectedFiles = [...selectedFiles, ...files];

                updateAllAttachmentsDisplay();
                toggleSendButton();

                // Give hint to user
                if (!promptInput.value.trim()) {
                    promptInput.placeholder =
                        `${selectedFiles.length + selectedOCRImages.length} file terpilih! Ketik instruksi Anda (opsional), lalu klik ↑ untuk mengirim`;
                    promptInput.focus();
                }
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
                toggleSendButton();

                // Give hint to user - NO AUTO PROCESS
                if (!promptInput.value.trim()) {
                    promptInput.placeholder =
                        `${selectedFiles.length + selectedOCRImages.length} file terpilih! Ketik instruksi Anda (opsional), lalu klik ↑ untuk mengirim`;
                    promptInput.focus();
                }
            });

            // ================================================================
            // Toggle Send Button Visibility
            // ================================================================
            function toggleSendButton() {
                const btnSend = document.getElementById('btn-ask-ai');
                const totalFiles = selectedFiles.length + selectedOCRImages.length;

                if (totalFiles > 0) {
                    btnSend.style.display = 'inline-flex';
                } else {
                    btnSend.style.display = 'none';
                }
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
                toggleSendButton();

                if (!promptInput.value.trim() && (selectedFiles.length + selectedOCRImages.length) > 0) {
                    promptInput.placeholder =
                        `${selectedFiles.length + selectedOCRImages.length} file terpilih! Ketik instruksi Anda (opsional), lalu klik ↑ untuk mengirim`;
                } else if (selectedFiles.length === 0 && selectedOCRImages.length === 0) {
                    promptInput.placeholder = 'Deskripsikan website yang ingin Anda buat...';
                }
            };

            // ================================================================
            // Remove OCR Image
            // ================================================================
            window.removeOCRImage = function(index) {
                selectedOCRImages.splice(index, 1);
                updateAllAttachmentsDisplay();
                toggleSendButton();

                if (!promptInput.value.trim() && (selectedFiles.length + selectedOCRImages.length) > 0) {
                    promptInput.placeholder =
                        `${selectedFiles.length + selectedOCRImages.length} file terpilih! Ketik instruksi Anda (opsional), lalu klik ↑ untuk mengirim`;
                } else if (selectedFiles.length === 0 && selectedOCRImages.length === 0) {
                    promptInput.placeholder = 'Deskripsikan website yang ingin Anda buat...';
                }
            };

            // ================================================================
            // Process OCR Images
            // ================================================================
            async function processOCRImages(laporanId) {
                if (selectedOCRImages.length === 0 || !laporanId) return true;

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
                                `<strong>✅ Gambar Relevan & OCR Berhasil!</strong><br>` +
                                `📸 ${successCount} dari ${totalCount} gambar berhasil diproses<br>` +
                                `📝 ${textLength.toLocaleString()} karakter teks diekstrak<br>` +
                                `⏱️ Waktu proses: ${procSec}s<br>` +
                                `✓ Konten tervalidasi relevan dengan Laporan Triwulan<br><br>` +
                                `<em>Teks OCR diintegrasikan. Melanjutkan generate laporan...</em>`;
                        } else {
                            // GD fallback — images registered but no text extracted (Tesseract not installed)
                            ocrMsg =
                                `<strong>📸 ${totalCount} gambar terdaftar!</strong><br>` +
                                `ℹ️ OCR otomatis tidak tersedia (Tesseract belum terinstall).<br>` +
                                `📌 Metadata gambar sudah diintegrasikan. Melanjutkan generate laporan...`;
                        }
                        appendAIMessage(ocrMsg);

                        // Clear OCR images after successful processing
                        selectedOCRImages = [];
                        updateAllAttachmentsDisplay();

                        return true;

                    } else {
                        // Check if it's a validation error
                        if (data.validation_error) {
                            // Show validation error with better formatting
                            const errorMessage = data.message.replace(/\n/g, '<br>');
                            
                            appendAIMessage(`
                                <div style="background:#fef2f2;border-left:4px solid #dc2626;padding:16px;border-radius:8px;margin:8px 0;">
                                    <p style="color:#dc2626;margin:0 0 12px 0;font-weight:600;font-size:16px;">
                                        <i class="bi bi-exclamation-triangle-fill"></i> Gambar Tidak Relevan dengan Laporan Triwulan
                                    </p>
                                    <div style="background:white;padding:12px;border-radius:4px;margin-bottom:12px;">
                                        <p style="color:#991b1b;margin:0;font-size:14px;line-height:1.6;">
                                            ${errorMessage}
                                        </p>
                                    </div>
                                    <p style="color:#7f1d1d;margin:0;font-size:13px;">
                                        <strong>💡 Contoh gambar yang relevan:</strong><br>
                                        • Screenshot dashboard monitoring<br>
                                        • Foto dokumentasi kegiatan<br>
                                        • Data statistik/grafik<br>
                                        • Laporan kegiatan scan
                                    </p>
                                </div>
                            `);
                            
                            // Clear the invalid images
                            selectedOCRImages = [];
                            updateAllAttachmentsDisplay();
                            
                            // Also clear prompt input to prevent user from sending without valid images
                            promptInput.value = '';
                            promptInput.placeholder = '⚠️ Upload gambar yang relevan terlebih dahulu sebelum chat dengan AI';
                            
                            return false;
                        } else {
                            appendAIMessage(`<strong>❌ OCR Error:</strong> ${data.message}`);
                            return false;
                        }
                    }
                } catch (error) {
                    appendAIMessage(`<strong>❌ OCR Error:</strong> ${error.message}`);
                    return false;
                } finally {
                    statusEl.style.display = 'none';
                }
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
            // Append message bubble
            // ================================================================
            function appendUserMessage(text, files) {
                const div = document.createElement('div');
                div.className = 'msg-user';

                let fileChips = '';
                if (files && files.length > 0) {
                    fileChips = '<div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.5rem;">';

                    files.forEach(file => {
                        const isImage = /\.(jpg|jpeg|png|gif|bmp|webp)$/i.test(file.name);

                        if (isImage) {
                            // Create image thumbnail
                            const imageUrl = URL.createObjectURL(file);
                            fileChips += `
                                <div class="user-msg-image-chip">
                                    <img src="${imageUrl}" alt="${file.name}" onload="URL.revokeObjectURL(this.src)">
                                    <span class="image-name">${file.name}</span>
                                </div>
                            `;
                        } else {
                            // Create file chip
                            fileChips += `
                                <div class="user-msg-file-chip">
                                    <i class="bi ${getFileIcon(file.name)}"></i>
                                    <span>${file.name}</span>
                                </div>
                            `;
                        }
                    });

                    fileChips += '</div>';
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
            // SMART SECTION MERGE HELPERS
            // Detect if user asked to modify a specific section,
            // extract the modified section from AI response,
            // and merge it back into the last full draft.
            // ================================================================

            /**
             * Detect if user prompt is asking to modify a specific section.
             * Returns the normalized section name or null.
             */
            function detectSectionModifyRequest(prompt) {
                const sectionNames = [
                    'LATAR BELAKANG', 'DASAR', 'TUJUAN', 'RUANG LINGKUP',
                    'PROGRAM KERJA', 'PELAKSANAAN', 'HAMBATAN',
                    'PEMECAHAN MASALAH', 'EVALUASI', 'SARAN', 'PENUTUP'
                ];

                // Check if prompt starts with create/buat → full draft, no merge needed
                if (/^(buat|buatkan|generate)/i.test(prompt)) {
                    return null;
                }

                // Match patterns like "perbaiki LATAR BELAKANG", "ubah bagian EVALUASI", etc.
                const modifyMatch = prompt.match(
                    /(perbaiki|ubah|tingkatkan|lengkapi|ganti|update|revisi)(?:\s+(?:bagian|section))?\s+(.+)/i
                );

                if (!modifyMatch) return null;

                let requestedPart = modifyMatch[2].trim().toUpperCase();
                // Remove trailing modifiers
                requestedPart = requestedPart.replace(
                    /\s+(AGAR|LEBIH|BAGUS|DETAIL|LENGKAP|FORMAL|PROFESIONAL|KOMPREHENSIF|JADI|MENJADI|DENGAN|SUPAYA|BIAR).*$/i, ''
                ).trim();

                // Find the best matching section name
                for (const name of sectionNames) {
                    if (requestedPart.includes(name) || name.includes(requestedPart)) {
                        return name;
                    }
                }

                // Partial match (e.g. "LATAR" → "LATAR BELAKANG")
                for (const name of sectionNames) {
                    if (name.startsWith(requestedPart) || requestedPart.startsWith(name.split(' ')[0])) {
                        return name;
                    }
                }

                return requestedPart; // Return as-is if no known match
            }

            /**
             * Find the last full draft from conversationHistory.
             * A full draft has 5+ markdown sections.
             */
            function getLastFullDraftFromHistory() {
                for (let i = conversationHistory.length - 1; i >= 0; i--) {
                    const msg = conversationHistory[i];
                    if (msg.role !== 'assistant') continue;

                    const sectionMatches = msg.content.match(/^# [A-Z][A-Z\s]+$/gm);
                    const sectionCount = sectionMatches ? sectionMatches.length : 0;

                    if (sectionCount >= 5) {
                        console.log('[SmartMerge] Found full draft at history index', i, 'with', sectionCount, 'sections');
                        return msg.content;
                    }
                }

                // Also check currentPreviewText as fallback
                if (currentPreviewText) {
                    const sectionMatches = currentPreviewText.match(/^# [A-Z][A-Z\s]+$/gm);
                    const sectionCount = sectionMatches ? sectionMatches.length : 0;
                    if (sectionCount >= 5) {
                        console.log('[SmartMerge] Using currentPreviewText as full draft with', sectionCount, 'sections');
                        return currentPreviewText;
                    }
                }

                return null;
            }

            /**
             * Extract a specific section from the AI response.
             * Returns {title, content} or null.
             */
            function extractSectionFromResponse(response, sectionName) {
                // Pattern: # SECTION_NAME\n...content...\n(# NEXT_SECTION or end)
                const escapedName = sectionName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                const pattern = new RegExp(
                    '^#\\s+(' + escapedName + ')\\s*\\n([\\s\\S]*?)(?=^#\\s+[A-Z][A-Z\\s]*$|$)',
                    'mi'
                );
                const match = response.match(pattern);

                if (match) {
                    return { title: match[1].trim(), content: match[2].trim() };
                }

                // Fallback: if AI returned only one section or bare text
                const allSections = parseMarkdownSections(response);
                if (allSections.length === 1) {
                    return { title: allSections[0].title.trim(), content: allSections[0].content.trim() };
                }

                // If no sections found at all, treat entire response as the section content
                if (allSections.length === 0 && response.trim().length > 0) {
                    return { title: sectionName, content: response.trim() };
                }

                return null;
            }

            /**
             * Merge a modified section back into the full draft.
             * Replaces the matching section in fullDraft with the new content.
             */
            function mergeSectionIntoDraft(fullDraft, modifiedSection) {
                const { title, content } = modifiedSection;
                const escapedTitle = title.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

                // Try to find and replace the section
                const pattern = new RegExp(
                    '^#\\s+' + escapedTitle + '\\s*\\n[\\s\\S]*?(?=^#\\s+[A-Z][A-Z\\s]*$|$)',
                    'mi'
                );

                if (pattern.test(fullDraft)) {
                    console.log('[SmartMerge] Replacing section:', title);
                    return fullDraft.replace(pattern, '# ' + title + '\n' + content + '\n\n');
                }

                // Section not found in draft — append at the end
                console.log('[SmartMerge] Section not found, appending:', title);
                return fullDraft + '\n\n# ' + title + '\n' + content;
            }

            /**
             * Main merge function: given AI response + user prompt,
             * detect if merge is needed and return the merged full draft.
             */
            function smartMergeResponse(aiResponse, userPrompt) {
                const requestedSection = detectSectionModifyRequest(userPrompt);

                if (!requestedSection) {
                    // User asked for a full draft or non-section-modify action
                    console.log('[SmartMerge] No section modify detected, using response as-is');
                    return aiResponse;
                }

                console.log('[SmartMerge] Detected section modify request:', requestedSection);

                // Count sections in AI response
                const responseSections = parseMarkdownSections(aiResponse);
                if (responseSections.length >= 5) {
                    // AI already returned a full draft (all sections), no merge needed
                    console.log('[SmartMerge] AI returned full draft (' + responseSections.length + ' sections), no merge needed');
                    return aiResponse;
                }

                // AI returned only partial response (1-4 sections), need to merge
                const lastFullDraft = getLastFullDraftFromHistory();
                if (!lastFullDraft) {
                    console.warn('[SmartMerge] No previous full draft found, cannot merge');
                    return aiResponse;
                }

                // Extract the modified section from AI response
                const extracted = extractSectionFromResponse(aiResponse, requestedSection);
                if (!extracted) {
                    console.warn('[SmartMerge] Could not extract section from AI response');
                    return aiResponse;
                }

                console.log('[SmartMerge] Merging section "' + extracted.title + '" into full draft');
                const merged = mergeSectionIntoDraft(lastFullDraft, extracted);
                console.log('[SmartMerge] Merge complete. Sections in merged:', parseMarkdownSections(merged).length);
                return merged;
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
                    formData.append('periode_triwulan', periode);
                    formData.append('judul_laporan', judul);

                    const template = document.getElementById('template_id').value;
                    if (template) formData.append('template_id', template);

                    const response = await fetch('{{ route('gjm.buat-laporan.triwulan.create-draft') }}', {
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

                        // Activate OCR processing if images are selected
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

                // CRITICAL VALIDATION: File upload validation
                const hasFiles = selectedFiles.length > 0 || selectedOCRImages.length > 0;
                const hasOCRText = ocrExtractedText && ocrExtractedText.length > 0;
                const hasConversation = conversationHistory.length > 0;
                
                console.log('🔍 VALIDATION CHECK:', {
                    prompt: prompt,
                    promptLength: prompt.length,
                    hasFiles: hasFiles,
                    hasOCRText: hasOCRText,
                    hasConversation: hasConversation,
                    selectedFiles: selectedFiles.length,
                    selectedOCRImages: selectedOCRImages.length
                });

                // VALIDATION 1: MUST have files for first interaction
                if (!hasConversation && !hasFiles && !hasOCRText) {
                    console.warn('❌ VALIDATION FAILED: No files uploaded for first interaction');
                    appendAIMessage(`
                        <div style="background:#fee2e2;border-left:4px solid #dc2626;padding:15px;border-radius:6px;">
                            <p style="color:#dc2626;margin:0;font-weight:700;font-size:16px;">
                                <i class="bi bi-exclamation-triangle-fill"></i> UPLOAD FILE TERLEBIH DAHULU!
                            </p>
                            <p style="color:#dc2626;margin:10px 0 0 0;line-height:1.6;">
                                Untuk chat pertama dengan AI, Anda <strong>WAJIB upload file</strong> terlebih dahulu.
                            </p>
                            <p style="color:#dc2626;margin:10px 0 0 0;line-height:1.6;">
                                <strong>File yang didukung:</strong>
                            </p>
                            <ul style="color:#dc2626;margin:8px 0 0 20px;line-height:1.6;">
                                <li>📄 Dokumen: PDF, DOCX, TXT, XLSX</li>
                                <li>🖼️ Gambar: JPG, PNG, GIF, WEBP</li>
                            </ul>
                            <p style="color:#dc2626;margin:10px 0 0 0;line-height:1.6;">
                                <strong>Cara upload:</strong> Klik tombol 📎 (attachment) di sebelah kolom chat.
                            </p>
                            <p style="color:#dc2626;margin:10px 0 0 0;font-style:italic;">
                                Setelah upload file, baru Anda bisa chat dengan AI.
                            </p>
                        </div>
                    `);
                    
                    // Focus to attachment button or input
                    setTimeout(() => {
                        const attachBtn = document.getElementById('btn-attachment');
                        if (attachBtn) {
                            attachBtn.focus();
                        } else {
                            promptInput.focus();
                        }
                    }, 100);
                    return;
                }

                // VALIDATION 2: No instruction provided
                if (!prompt || prompt.length === 0) {
                    if (hasFiles || hasOCRText) {
                        console.warn('❌ VALIDATION FAILED: Files uploaded without instruction');
                        appendAIMessage(`
                            <div style="background:#fee2e2;border-left:4px solid #dc2626;padding:15px;border-radius:6px;">
                                <p style="color:#dc2626;margin:0;font-weight:700;font-size:16px;">
                                    <i class="bi bi-exclamation-triangle-fill"></i> INSTRUKSI WAJIB DIISI!
                                </p>
                                <p style="color:#dc2626;margin:10px 0 0 0;line-height:1.6;">
                                    Anda telah mengupload <strong>${selectedFiles.length + selectedOCRImages.length} file</strong>, 
                                    tetapi belum memberikan instruksi.
                                </p>
                                <p style="color:#dc2626;margin:10px 0 0 0;line-height:1.6;">
                                    <strong>Silakan ketik instruksi Anda terlebih dahulu</strong>, misalnya:
                                </p>
                                <ul style="color:#dc2626;margin:8px 0 0 20px;line-height:1.6;">
                                    <li>"Analisis dokumen ini dan buat ringkasan"</li>
                                    <li>"Buat laporan berdasarkan data yang diupload"</li>
                                    <li>"Ekstrak informasi penting dari dokumen"</li>
                                </ul>
                                <p style="color:#dc2626;margin:10px 0 0 0;font-style:italic;">
                                    File tidak akan diproses tanpa instruksi yang jelas.
                                </p>
                            </div>
                        `);
                        
                        // Focus back to input
                        setTimeout(() => {
                            promptInput.focus();
                        }, 100);
                        return;
                    } else {
                        console.warn('❌ VALIDATION FAILED: No instruction provided');
                        appendAIMessage(`
                            <div style="background:#fff3cd;border-left:4px solid #ffc107;padding:12px;border-radius:4px;">
                                <p style="color:#856404;margin:0;font-weight:600;">
                                    <i class="bi bi-info-circle-fill"></i> Silakan masukkan instruksi atau pertanyaan Anda
                                </p>
                            </div>
                        `);
                        
                        // Focus back to input
                        setTimeout(() => {
                            promptInput.focus();
                        }, 100);
                        return;
                    }
                }

                // VALIDATION 3: Instruction too short
                if (prompt && prompt.length < 5) {
                    console.warn('❌ VALIDATION FAILED: Instruction too short (' + prompt.length + ' chars)');
                    appendAIMessage(`
                        <div style="background:#fee2e2;border-left:4px solid #dc2626;padding:15px;border-radius:6px;">
                            <p style="color:#dc2626;margin:0;font-weight:700;">
                                <i class="bi bi-exclamation-triangle-fill"></i> Instruksi terlalu singkat!
                            </p>
                            <p style="color:#dc2626;margin:10px 0 0 0;line-height:1.6;">
                                Instruksi Anda hanya <strong>${prompt.length} karakter</strong>.<br>
                                Silakan berikan instruksi yang lebih jelas dan spesifik (minimal 5 karakter).
                            </p>
                            <p style="color:#dc2626;margin:10px 0 0 0;line-height:1.6;">
                                <strong>Contoh instruksi yang baik:</strong>
                            </p>
                            <ul style="color:#dc2626;margin:8px 0 0 20px;line-height:1.6;">
                                <li>"Analisis dokumen ini"</li>
                                <li>"Buat ringkasan"</li>
                                <li>"Ekstrak data penting"</li>
                            </ul>
                        </div>
                    `);
                    
                    // Focus back to input
                    setTimeout(() => {
                        promptInput.focus();
                    }, 100);
                    return;
                }
                
                console.log('✅ VALIDATION PASSED: Proceeding with request');

                // Tangkap file yang dipilih sebelum OCR memproses (karena OCR akan clear selectedOCRImages)
                const allFilesForBubble = [...selectedFiles, ...selectedOCRImages];

                // Jika ada gambar OCR, proses dulu LALU lanjut ke AI dalam satu klik
                if (selectedOCRImages.length > 0) {
                    const ocrOk = await processOCRImages(laporanId);
                    if (!ocrOk) return; // OCR gagal/ditolak — hentikan
                    // Jika OCR berhasil, lanjut otomatis ke AI prompt di bawah
                }

                // Collect form values
                const periode = document.getElementById('periode_triwulan').value;
                const judul = document.getElementById('judul_laporan').value;
                const template = document.getElementById('template_id').value;

                // Tampilkan bubble pesan user (dengan file yang sudah dipilih sebelumnya)
                appendUserMessage(prompt || '(File dikirim)', allFilesForBubble.length > 0 ? allFilesForBubble : null);
                promptInput.value = '';
                promptInput.style.height = 'auto';
                promptInput.placeholder = 'Ketik instruksi Anda...';

                // Langsung bersihkan chip attachment setelah file dikirim (tapi jangan reset conversation history)
                const currentFiles = [...selectedFiles]; // Simpan referensi file untuk request
                const currentOCRImages = [...selectedOCRImages];
                const currentOCRText = ocrExtractedText;
                
                selectedFiles = [];
                selectedOCRImages = [];
                ocrExtractedText = '';
                
                // Reset file inputs
                const fileInput = document.getElementById('file_referensi_triwulan');
                const ocrInput = document.getElementById('ocr_images_triwulan');
                if (fileInput) fileInput.value = '';
                if (ocrInput) ocrInput.value = '';
                
                // Update display to hide attachment chips
                updateAllAttachmentsDisplay();
                toggleSendButton();

                // Show typing
                showTyping();
                btnAskAI.disabled = true;
                btnAskAI.innerHTML =
                    '<span class="spinner-border spinner-border-sm"></span>';

                try {
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('prompt', prompt || 'Analisis file dan gambar yang saya upload');
                    formData.append('laporan_id', laporanId); // PENTING: Kirim laporan_id
                    if (periode) formData.append('periode_triwulan', periode);
                    if (template) formData.append('template_id', template);

                    // Add conversation history for multi-turn chat (send as individual items, not JSON string)
                    if (conversationHistory.length > 0) {
                        conversationHistory.forEach((msg, index) => {
                            formData.append(`conversation_history[${index}][role]`, msg.role);
                            formData.append(`conversation_history[${index}][content]`, msg.content);
                        });
                    }

                    // Add files (gunakan file yang sudah disimpan sebelumnya)
                    currentFiles.forEach(function(file) {
                        formData.append('file_referensi[]', file);
                    });

                    // Add OCR text if available
                    if (currentOCRText) {
                        formData.append('ocr_extracted_text', currentOCRText);
                    }

                    // Client-side timeout 120 detik agar loading tidak terus-menerus
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 120000);

                    let response;
                    try {
                        response = await fetch(
                            '{{ route('gjm.buat-laporan.triwulan.ai-prompt') }}', {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'Accept': 'application/json',
                                },
                                signal: controller.signal,
                            });
                    } catch (fetchErr) {
                        if (fetchErr.name === 'AbortError') {
                            throw new Error('⏱️ Request timeout (>2 menit). Server AI sedang sibuk. Coba kurangi ukuran file atau coba lagi nanti.');
                        }
                        throw fetchErr;
                    } finally {
                        clearTimeout(timeoutId);
                    }

                    const data = await response.json();

                    if (data.success) {
                        const aiResponseRaw = data.response;

                        // SMART MERGE: If user asked to modify a specific section,
                        // merge the AI's partial response into the last full draft
                        // so ALL sections remain visible.
                        const userPromptText = prompt || 'Analisis file dan gambar yang saya upload';
                        const aiResponse = smartMergeResponse(aiResponseRaw, userPromptText);

                        // Save to conversation history for multi-turn chat
                        conversationHistory.push({
                            role: 'user',
                            content: userPromptText
                        });
                        conversationHistory.push({
                            role: 'assistant',
                            content: aiResponse
                        });

                        // Parse sections for preview
                        const sections = parseMarkdownSections(aiResponse);

                        // Save to hidden field
                        currentPreviewText = aiResponse;
                        aiPreviewData.value = aiResponse;

                        // Show AI message with preview inside chat
                        appendAIMessageWithPreview(aiResponse, sections, '');

                        // Save AI preview to database for Word generation
                        saveAIPreviewToDatabase(laporanId, aiResponse, sections);

                        // (attachment sudah di-clear saat tombol kirim ditekan)
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
            // GENERATE LAPORAN WORD from chat button
            // ================================================================
            window.generateWordFromChat = async function() {
                const laporanId = document.getElementById('laporan_id').value;
                const aiPreviewDataValue = document.getElementById('ai_preview_data').value;

                if (!laporanId) {
                    alert('Silakan buat laporan draft terlebih dahulu.');
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
                    const response = await fetch('{{ route('gjm.buat-laporan.triwulan.store') }}', {
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
                    console.log('Response content-type:', contentType);

                    if (contentType && (contentType.includes('application/vnd.openxmlformats') ||
                            contentType.includes('application/octet-stream'))) {
                        // Download file
                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;

                        // Get filename from Content-Disposition header or use default
                        const contentDisposition = response.headers.get('content-disposition');
                        let filename = 'Laporan_Triwulan_' + Date.now() + '.docx';
                        if (contentDisposition) {
                            const filenameMatch = contentDisposition.match(
                                /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                            if (filenameMatch) {
                                filename = filenameMatch[1].replace(/['"]/g, '');
                            }
                        }

                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);

                        // Show success message in chat with file info
                        appendAIMessage(
                            '<p style="color:#16a34a;"><i class="bi bi-check-circle-fill"></i> <strong>Laporan Word berhasil di-generate dan didownload!</strong><br><small>📁 File tersimpan di folder <strong>Downloads</strong> Anda dengan nama: <strong>' +
                            filename + '</strong></small></p>');

                        // Show browser notification if supported
                        if ('Notification' in window && Notification.permission === 'granted') {
                            new Notification('Download Selesai', {
                                body: 'File ' + filename + ' berhasil didownload',
                                icon: '/favicon.ico'
                            });
                        } else if ('Notification' in window && Notification.permission !== 'denied') {
                            // Request permission for future notifications
                            Notification.requestPermission();
                        }

                    } else {
                        // Response is JSON (error or redirect)
                        const data = await response.json();
                        console.log('JSON response:', data);

                        if (data.success) {
                            appendAIMessage(
                                '<p style="color:#16a34a;"><i class="bi bi-check-circle-fill"></i> <strong>Laporan berhasil dibuat!</strong></p>'
                            );

                            // If there's a redirect URL, show option to view
                            if (data.redirect) {
                                appendAIMessage(
                                    '<p><a href="' + data.redirect +
                                    '" target="_blank" class="btn btn-sm btn-primary"><i class="bi bi-eye"></i> Lihat Laporan</a></p>'
                                );
                            }
                        } else {
                            throw new Error(data.message || 'Gagal membuat laporan');
                        }
                    }

                    // Re-enable button
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML =
                            '<i class="bi bi-file-earmark-word-fill"></i> Generate Laporan Word';
                    }

                } catch (err) {
                    console.error('Generate Word error:', err);

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
            // Initialize welcome message
            // ================================================================
            appendAIMessage(
                '<strong>Selamat datang di AI Assistant Laporan Triwulan!</strong><br>Saya siap membantu Anda membuat laporan triwulan yang komprehensif. Silakan:<br><br>1. Klik <strong>"Buat Laporan Draft"</strong> terlebih dahulu<br>2. Upload file referensi jika ada<br>3. Ketik instruksi atau deskripsi laporan yang diinginkan<br>4. Saya akan membantu generate draft laporan untuk Anda'
            );

        });
    </script>
@endsection
