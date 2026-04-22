@extends('layouts.app')

@section('page-title', 'Upload Template Triwulan Baru')

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Header Card -->
        <div class="filter-card mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 d-flex align-items-center gap-2" style="font-weight: 600; color: #333;">
                        <i class="bi bi-upload" style="color: #5B9BD5;"></i>
                        Upload Template Laporan Triwulan Baru
                    </h5>
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">Upload file Word (.docx) sebagai template untuk laporan triwulan GJM</p>
                </div>
                <a href="{{ route('gjm.buat-laporan.template-triwulan.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <!-- Error Messages -->
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="border-left: 4px solid #dc3545;">
                        <h6 style="font-weight: 600; margin-bottom: 0.5rem;">
                            <i class="bi bi-exclamation-triangle-fill"></i> Terjadi Kesalahan
                        </h6>
                        <ul class="mb-0" style="font-size: 0.875rem;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Info Card -->
                <div class="monitoring-card mb-4" style="border-left: 4px solid #5B9BD5;">
                    <div style="padding: 1.5rem;">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-info-circle" style="color: #5B9BD5; font-size: 2rem; margin-right: 1rem;"></i>
                            <div>
                                <h6 class="mb-2" style="font-weight: 600; color: #333;">Panduan Upload Template Triwulan</h6>
                                <ul class="mb-0" style="font-size: 0.875rem; color: #495057; line-height: 1.8;">
                                    <li>Format file: Word (.docx atau .doc)</li>
                                    <li>Ukuran maksimal: 10 MB</li>
                                    <li>Template khusus untuk laporan triwulan (3 bulan)</li>
                                    <li>Gunakan struktur yang jelas dengan heading dan subheading</li>
                                    <li><strong>Pastikan file Word dapat dibuka dengan normal</strong> sebelum diupload</li>
                                    <li>Jika file berasal dari luar (email, download), coba buka dan save ulang di Word terlebih dahulu</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="monitoring-card">
                    <div class="monitoring-header">
                        <i class="bi bi-upload" style="color: #5B9BD5;"></i>
                        <h6>Form Upload Template Triwulan</h6>
                    </div>
                    <div style="padding: 1.5rem;">
                        <form action="{{ route('gjm.buat-laporan.template-triwulan.store') }}" 
                              method="POST" 
                              enctype="multipart/form-data"
                              id="uploadForm">
                            @csrf

                            <!-- Nama Template -->
                            <div class="mb-4">
                                <label for="nama_template" class="filter-label">
                                    Nama Template <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       name="nama_template" 
                                       id="nama_template" 
                                       class="form-control @error('nama_template') is-invalid @enderror" 
                                       value="{{ old('nama_template') }}"
                                       placeholder="Contoh: Template Laporan Triwulan GJM 2026"
                                       required>
                                @error('nama_template')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted" style="font-size: 0.8rem;">
                                    <i class="bi bi-lightbulb"></i> Berikan nama yang deskriptif untuk memudahkan identifikasi
                                </small>
                            </div>

                            <!-- File Upload -->
                            <div class="mb-4">
                                <label for="file" class="filter-label">
                                    File Template <span class="text-danger">*</span>
                                </label>
                                <input type="file" 
                                       name="file" 
                                       id="file" 
                                       class="form-control @error('file') is-invalid @enderror" 
                                       accept=".doc,.docx"
                                       required
                                       onchange="validateFile(this)">
                                @error('file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted" style="font-size: 0.8rem;">
                                    <i class="bi bi-info-circle"></i> Format: .docx atau .doc | Maksimal: 10 MB
                                </small>
                                <div id="fileInfo" class="mt-2" style="display: none;">
                                    <div class="alert alert-info py-2 px-3" style="font-size: 0.85rem;">
                                        <i class="bi bi-file-earmark-word"></i>
                                        <strong>File dipilih:</strong> <span id="fileName"></span> 
                                        (<span id="fileSize"></span>)
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="d-flex justify-content-between gap-2">
                                <a href="{{ route('gjm.buat-laporan.template-triwulan.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Batal
                                </a>
                                <button type="submit" class="btn-reminder" id="submitBtn">
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
function validateFile(input) {
    const file = input.files[0];
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    
    if (file) {
        // Check file size (10 MB = 10485760 bytes)
        if (file.size > 10485760) {
            alert('⚠️ Ukuran file terlalu besar!\n\nMaksimal ukuran file adalah 10 MB.\nFile Anda: ' + (file.size / 1048576).toFixed(2) + ' MB');
            input.value = '';
            fileInfo.style.display = 'none';
            return false;
        }
        
        // Check file extension
        const allowedExtensions = ['doc', 'docx'];
        const fileExtension = file.name.split('.').pop().toLowerCase();
        
        if (!allowedExtensions.includes(fileExtension)) {
            alert('⚠️ Format file tidak valid!\n\nHanya file Word (.doc atau .docx) yang diperbolehkan.');
            input.value = '';
            fileInfo.style.display = 'none';
            return false;
        }
        
        // Display file info
        fileName.textContent = file.name;
        fileSize.textContent = (file.size / 1024).toFixed(2) + ' KB';
        fileInfo.style.display = 'block';
    } else {
        fileInfo.style.display = 'none';
    }
}

// Form submission handling
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    const fileInput = document.getElementById('file');
    
    if (!fileInput.files[0]) {
        e.preventDefault();
        alert('⚠️ Silakan pilih file template terlebih dahulu!');
        return false;
    }
    
    // Disable submit button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading...';
});
</script>
@endpush
