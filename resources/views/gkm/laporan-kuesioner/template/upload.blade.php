@extends('layouts.app')

@section('page-title', 'Upload Template Baru')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4>Upload Template Laporan Baru</h4>
                    <p class="text-muted">Upload file Word (.docx) sebagai template untuk AI Agent</p>
                </div>
                <a href="{{ route('gkm.laporan-kuesioner.template.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Info Card -->
            <div class="card mb-4 border-info">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-info-circle text-info fs-3 me-3"></i>
                        <div>
                            <h6 class="mb-2">Panduan Upload Template</h6>
                            <ul class="mb-0 small">
                                <li>Format file: Word (.docx atau .doc)</li>
                                <li>Ukuran maksimal: 10 MB</li>
                                <li>Gunakan struktur yang jelas dengan heading dan subheading</li>
                                <li>AI akan mempelajari format, struktur, dan gaya penulisan</li>
                                <li><strong>Pastikan file Word dapat dibuka dengan normal</strong> sebelum diupload</li>
                                <li>Jika file berasal dari luar (email, download), coba buka dan save ulang di Word terlebih dahulu</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h6 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Terjadi Kesalahan</h6>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <!-- Form Card -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-upload"></i> Form Upload Template</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('gkm.laporan-kuesioner.template.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Nama Template -->
                        <div class="mb-4">
                            <label for="nama_template" class="form-label">
                                Nama Template <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   name="nama_template" 
                                   id="nama_template" 
                                   class="form-control @error('nama_template') is-invalid @enderror" 
                                   value="{{ old('nama_template') }}"
                                   placeholder="Contoh: Template Laporan Bulanan 2026"
                                   required>
                            @error('nama_template')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- File Upload -->
                        <div class="mb-4">
                            <label for="file_template" class="form-label">
                                File Template <span class="text-danger">*</span>
                            </label>
                            <input type="file" 
                                   name="file_template" 
                                   id="file_template" 
                                   class="form-control @error('file_template') is-invalid @enderror" 
                                   accept=".doc,.docx"
                                   required>
                            @error('file_template')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Format: .docx atau .doc | Maksimal: 10 MB
                            </small>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('gkm.laporan-kuesioner.template.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload"></i> Upload Template
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
