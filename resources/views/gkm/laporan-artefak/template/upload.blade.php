@extends('layouts.app')

@section('page-title', 'Upload Template Baru')

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Header Card -->
        <div class="filter-card mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 d-flex align-items-center gap-2" style="font-weight: 600; color: #333;">
                        <i class="bi bi-upload" style="color: #5B9BD5;"></i>
                        
                    </h5>
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">Upload file Word (.docx) sebagai template untuk AI
                        Agent</p>
                </div>
                <a href="{{ route('gkm.laporan-artefak.template.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <!-- Info Card -->
                <div class="monitoring-card mb-4" style="border-left: 4px solid #5B9BD5;">
                    <div style="padding: 1.5rem;">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-info-circle" style="color: #5B9BD5; font-size: 2rem; margin-right: 1rem;"></i>
                            <div>
                                <h6 class="mb-2" style="font-weight: 600; color: #333;">Panduan Upload Template</h6>
                                <ul class="mb-0" style="font-size: 0.875rem; color: #495057; line-height: 1.8;">
                                    <li>Format file: Word (.docx atau .doc)</li>
                                    <li>Ukuran maksimal: 10 MB</li>
                                    <li>Gunakan struktur yang jelas dengan heading dan subheading</li>
                                    <li>AI akan mempelajari format, struktur, dan gaya penulisan</li>
                                    <li><strong>Pastikan file Word dapat dibuka dengan normal</strong> sebelum diupload</li>
                                    <li>Jika file berasal dari luar (email, download), coba buka dan save ulang di Word
                                        terlebih dahulu</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="alert-gkm danger mb-4">
                        <h6 style="font-weight: 600; margin-bottom: 0.5rem;">
                            <i class="bi bi-exclamation-triangle"></i> Terjadi Kesalahan
                        </h6>
                        <ul class="mb-0" style="font-size: 0.875rem;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Form Card -->
                <div class="monitoring-card">
                    <div class="monitoring-header">
                        <i class="bi bi-upload" style="color: #5B9BD5;"></i>
                        <h6>Form Upload Template</h6>
                    </div>
                    <div style="padding: 1.5rem;">
                        <form action="{{ route('gkm.laporan-artefak.template.store') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf

                            <!-- Nama Template -->
                            <div class="mb-4">
                                <label for="nama_template" class="filter-label">
                                    Nama Template <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="nama_template" id="nama_template"
                                    class="form-control @error('nama_template') is-invalid @enderror"
                                    value="{{ old('nama_template') }}" placeholder="Contoh: Template Laporan Artefak 2026"
                                    required>
                                @error('nama_template')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- File Upload -->
                            <div class="mb-4">
                                <label for="file_template" class="filter-label">
                                    File Template <span class="text-danger">*</span>
                                </label>
                                <input type="file" name="file_template" id="file_template"
                                    class="form-control @error('file_template') is-invalid @enderror" accept=".doc,.docx"
                                    required>
                                @error('file_template')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted" style="font-size: 0.8rem;">
                                    <i class="bi bi-info-circle"></i> Format: .docx atau .doc | Maksimal: 10 MB
                                </small>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="d-flex justify-content-between gap-2">
                                <a href="{{ route('gkm.laporan-artefak.template.index') }}"
                                    class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Batal
                                </a>
                                <button type="submit" class="btn-reminder">
                                    <i class="bi bi-upload"></i>
                                    <span>Upload Template</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
