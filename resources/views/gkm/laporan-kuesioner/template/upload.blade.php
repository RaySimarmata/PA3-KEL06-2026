@extends('layouts.app')

@section('page-title', 'Upload Template Baru')

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Header Card -->
        <div class="filter-card mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 d-flex align-items-center gap-2" style="font-weight: 600; color: #333;">
                        Upload Template Laporan Baru
                    </h5>
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">Upload file Word (.docx) sebagai template untuk AI
                        Agent</p>
                </div>
                <a href="{{ route('gkm.laporan-kuesioner.template.index') }}" class="btn btn-secondary btn-sm">
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
                    <div class="alert-app danger mb-4">
                        <i class="bi bi-exclamation-triangle-fill alert-app-icon"></i>
                        <div class="alert-app-body">
                            <div class="alert-app-title">Terjadi Kesalahan</div>
                            <ul class="mb-0" style="font-size:0.875rem; padding-left:1.25rem;">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <!-- Form Card -->
                <div class="monitoring-card">
                    <div class="monitoring-header">
                        <h6>Form Upload Template</h6>
                    </div>
                    <div style="padding: 1.5rem;">
                        <form action="{{ route('gkm.laporan-kuesioner.template.store') }}" method="POST"
                            enctype="multipart/form-data" id="form-upload-template">
                            @csrf

                            <!-- Nama Template -->
                            <div class="mb-4">
                                <label for="nama_template" class="filter-label">
                                    Nama Template <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="nama_template" id="nama_template"
                                    class="form-control @error('nama_template') is-invalid @enderror"
                                    value="{{ old('nama_template') }}" placeholder="Contoh: Template Laporan Bulanan 2026"
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
                            <div class="d-flex justify-content-end gap-2">
                                <button type="submit" class="btn-reminder" id="btn-submit-template">
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('form-upload-template');
            const submitBtn = document.getElementById('btn-submit-template');
            const fileInput = document.getElementById('file_template');

            console.log('Form upload template loaded');
            console.log('Form:', form);
            console.log('Submit button:', submitBtn);
            console.log('File input:', fileInput);

            // Test button click
            if (submitBtn) {
                submitBtn.addEventListener('click', function(e) {
                    console.log('Submit button clicked!');
                    console.log('Button type:', this.type);

                    // Check if file is selected
                    if (fileInput && fileInput.files.length === 0) {
                        console.warn('No file selected');
                        alert('Silakan pilih file template terlebih dahulu');
                        e.preventDefault();
                        return false;
                    }

                    console.log('File selected:', fileInput.files[0].name);
                    console.log('Form will submit...');
                    // Let form submit naturally
                });
            }

            // Form submit event
            if (form) {
                form.addEventListener('submit', function(e) {
                    console.log('Form submit event triggered');
                    console.log('Form action:', this.action);
                    console.log('Form method:', this.method);

                    // Validate
                    if (!fileInput || fileInput.files.length === 0) {
                        console.error('Form validation failed: No file selected');
                        alert('Silakan pilih file template terlebih dahulu');
                        e.preventDefault();
                        return false;
                    }

                    const file = fileInput.files[0];
                    console.log('Submitting with file:', file.name, 'Size:', file.size, 'bytes');

                    // Check file size (10MB max)
                    if (file.size > 10 * 1024 * 1024) {
                        alert('Ukuran file terlalu besar! Maksimal 10 MB');
                        e.preventDefault();
                        return false;
                    }

                    // Show loading
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> <span>Uploading...</span>';

                    console.log('Form validation passed, submitting...');
                    // Let form submit
                });
            }

            // File input change
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    console.log('File selected:', this.files[0]);
                });
            }
        });
    </script>
@endpush
