@extends('layouts.app')

@section('page-title', request('tipe') == 'semester' ? 'Buat Laporan Semester' : 'Buat Laporan Triwulan')

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Header Card -->
        <div class="filter-card mb-4">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-file-earmark-text" style="color: #5B9BD5; font-size: 2rem;"></i>
                <div>
                    <h5 class="mb-1" style="font-weight: 600; color: #333;">
                        {{ request('tipe') == 'semester' ? 'Buat Laporan Semester' : 'Buat Laporan Triwulan' }}
                    </h5>
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">Buat laporan strategis dengan mudah dan cepat</p>
                </div>
            </div>
        </div>

        <form id="laporanForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="tipe_laporan" id="tipe_laporan" value="{{ request('tipe', 'triwulan') }}">

            <div class="monitoring-card mb-4">
                <div class="monitoring-header">
                    <i class="bi bi-info-circle" style="color: #5B9BD5;"></i>
                    <h6>Informasi Laporan</h6>
                </div>
                <div style="padding: 1.5rem;">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="filter-label">Periode Laporan <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="periode_tanggal" required>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="filter-label">Program Studi <span class="text-danger">*</span></label>
                            <select class="form-select" name="program_studi" required>
                                <option value="">Pilih Program Studi</option>
                                <option value="Teknik Informatika">Teknik Informatika</option>
                                <option value="Sistem Informasi">Sistem Informasi</option>
                                <option value="Teknik Elektro">Teknik Elektro</option>
                                <option value="Teknik Mesin">Teknik Mesin</option>
                                <option value="Teknik Sipil">Teknik Sipil</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-4">
                            <label class="filter-label">Judul Laporan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="judul_laporan"
                                placeholder="Masukkan judul laporan" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="monitoring-card mb-4">
                <div class="monitoring-header">
                    <i class="bi bi-cloud-upload" style="color: #5B9BD5;"></i>
                    <h6>Dokumen Pendukung</h6>
                </div>
                <div style="padding: 1.5rem;">
                    <div class="mb-4">
                        <label class="filter-label">Upload Dokumen <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="dokumen" id="fileInput"
                            accept=".pdf,.doc,.docx,.xlsx,.xls,.jpg,.jpeg,.png,.gif,.bmp,.webp,.svg" required>
                        <small class="text-muted" style="font-size: 0.8rem;">
                            <i class="bi bi-info-circle"></i> Format: PDF, Word, Excel, atau Gambar (JPG, PNG, GIF, BMP, WebP, SVG) | Maksimal: 10 MB
                        </small>
                        <small class="text-muted" style="font-size: 0.8rem;">
                            <i class="bi bi-info-circle"></i> Upload Activities documentation, Meeting minutes, GKM reports,
                            Questionnaires (PDF, DOC, DOCX, XLS, XLSX)
                        </small>
                    </div>
                    <div id="uploadedFile" class="monitoring-card" style="display: none; border-left: 4px solid #28a745;">
                        <div style="padding: 1rem;" class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-file-earmark-check-fill" style="font-size: 2rem; color: #28a745;"></i>
                                <div>
                                    <div class="code-mk" id="fileName"></div>
                                    <div class="text-secondary" id="fileSize"></div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="removeFile">
                                <i class="bi bi-x-circle"></i> Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="monitoring-card mb-4">
                <div class="monitoring-header">
                    <i class="bi bi-file-earmark-text" style="color: #5B9BD5;"></i>
                    <h6>Template Laporan</h6>
                    <div style="margin-left: auto;">
                        <a href="#" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-file-earmark-text"></i> Kelola Template
                        </a>
                    </div>
                </div>
                <div style="padding: 1.5rem;">
                    <div class="row">
                        @forelse($templates as $template)
                            <div class="col-md-6 mb-3">
                                <div class="content-card" style="cursor: pointer; transition: all 0.3s ease;"
                                    onclick="selectTemplate(this)">
                                    <div class="d-flex align-items-start gap-3">
                                        <i class="bi bi-file-earmark-text" style="font-size: 2rem; color: #5B9BD5;"></i>
                                        <div class="flex-grow-1">
                                            <div class="code-mk">{{ $template->nama_template }}</div>
                                            <div class="text-secondary">
                                                {{ $template->deskripsi ?? 'Laporan penilaian evaluasi template' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <p>Belum ada template tersedia</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="monitoring-card mb-4">
                <div class="monitoring-header">
                    <i class="bi bi-pencil-square" style="color: #5B9BD5;"></i>
                    <h6>Instruksi Prompt</h6>
                </div>
                <div style="padding: 1.5rem;">
                    <div class="mb-3">
                        <label class="filter-label">Instruksi untuk AI <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="instruksi_prompt" rows="6" placeholder="Masukkan instruksi untuk AI..."
                            required>2c3xde5dTy6l8o9=</textarea>
                    </div>
                    <div class="alert-gjm info">
                        <strong><i class="bi bi-lightbulb"></i> Contoh Prompt:</strong><br>
                        <small>"Buat laporan triwulan monitoring mutu 1920s - Rangkuman : 1. Status upload materi 2.
                            Evaluasi dokumen 3. Temuan dari apa. Sebutkan perbaikan format akademis."</small>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <button type="submit" class="btn-reminder" id="generateBtn">
                    <i class="bi bi-robot"></i>
                    <span>Generate Laporan Baru</span>
                </button>
            </div>
        </form>

        <!-- Loading Spinner -->
        <div id="loadingSpinner" style="display: none; text-align: center; padding: 3rem;">
            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
            <p style="color: #495057; font-weight: 600;">Generating report...</p>
        </div>

        <!-- Report Result -->
        <div id="reportResult" class="monitoring-card mt-4" style="display: none;">
            <div class="monitoring-header" style="background: #e8f5e9; border-bottom-color: #28a745;">
                <i class="bi bi-file-earmark-check-fill" style="color: #28a745;"></i>
                <h6 style="color: #28a745;">Generated Report</h6>
            </div>
            <div style="padding: 1.5rem;">
                <div class="empty-state">
                    <i class="bi bi-file-earmark-text"></i>
                    <p>Pratinjau laporan akan muncul di sini...</p>
                </div>
                <div class="d-flex gap-2 justify-content-center mt-4">
                    <button type="button" class="btn btn-success" id="downloadPDF">
                        <i class="bi bi-file-pdf"></i> Download PDF
                    </button>
                    <button type="button" class="btn btn-primary" id="downloadPPT">
                        <i class="bi bi-file-ppt"></i> Download PPT
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .content-card:hover {
            border-color: #5B9BD5;
            transform: translateY(-2px);
        }

        .content-card.selected {
            border-color: #5B9BD5;
            background: #f0f7fc;
        }
    </style>
@endsection

@section('scripts')
    <script>
        function selectTemplate(element) {
            document.querySelectorAll('.content-card').forEach(card => {
                card.classList.remove('selected');
            });
            element.classList.add('selected');
        }

        document.addEventListener('DOMContentLoaded', function() {
            // File upload handling
            const fileInput = document.getElementById('fileInput');
            const uploadedFile = document.getElementById('uploadedFile');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');
            const removeFile = document.getElementById('removeFile');

            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    displayFile(this.files[0]);
                }
            });

            removeFile.addEventListener('click', function() {
                fileInput.value = '';
                uploadedFile.style.display = 'none';
                // Remove image preview if exists
                const imagePreview = document.getElementById('imagePreview');
                if (imagePreview) {
                    imagePreview.remove();
                }
            });

            function displayFile(file) {
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                uploadedFile.style.display = 'block';
                
                // Check if file is an image
                const imageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/webp', 'image/svg+xml'];
                if (imageTypes.includes(file.type)) {
                    // Remove existing preview if any
                    const existingPreview = document.getElementById('imagePreview');
                    if (existingPreview) {
                        existingPreview.remove();
                    }
                    
                    // Create image preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewDiv = document.createElement('div');
                        previewDiv.id = 'imagePreview';
                        previewDiv.className = 'mt-3';
                        previewDiv.innerHTML = `
                            <div style="border: 2px solid #5B9BD5; border-radius: 8px; padding: 1rem; background: #f8f9fa;">
                                <p class="mb-2" style="font-weight: 600; color: #333;">
                                    <i class="bi bi-image"></i> Preview Gambar:
                                </p>
                                <img src="${e.target.result}" 
                                     alt="Preview" 
                                     style="max-width: 100%; max-height: 400px; border-radius: 4px; display: block; margin: 0 auto;">
                            </div>
                        `;
                        uploadedFile.appendChild(previewDiv);
                    };
                    reader.readAsDataURL(file);
                }
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
            }

            // Form submission
            const form = document.getElementById('laporanForm');
            const generateBtn = document.getElementById('generateBtn');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const reportResult = document.getElementById('reportResult');

            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                const formData = new FormData(this);

                // Show loading
                generateBtn.disabled = true;
                loadingSpinner.style.display = 'block';
                reportResult.style.display = 'none';

                try {
                    const response = await fetch('{{ route('gjm.buat-laporan.generate') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });

                    const data = await response.json();

                    // Hide loading
                    loadingSpinner.style.display = 'none';
                    generateBtn.disabled = false;

                    if (data.success) {
                        // Show result
                        reportResult.style.display = 'block';

                        // Show success message
                        alert('Laporan berhasil di-generate!');
                    } else {
                        alert('Gagal generate laporan: ' + (data.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    loadingSpinner.style.display = 'none';
                    generateBtn.disabled = false;
                    alert('Terjadi kesalahan saat generate laporan');
                }
            });

            // Download buttons
            document.getElementById('downloadPDF').addEventListener('click', function() {
                alert('Download PDF akan segera tersedia');
            });

            document.getElementById('downloadPPT').addEventListener('click', function() {
                alert('Download PPT akan segera tersedia');
            });
        });
    </script>
@endsection
