@extends('layouts.app')

@section('page-title', 'Buat Laporan')

@section('styles')
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .buat-laporan-container {
        background-color: #f5f7fa;
        min-height: calc(100vh - 80px);
        padding: 30px;
    }

    .header-section {
        background: white;
        border-radius: 0.375rem;
        padding: 25px 30px;
        margin-bottom: 30px;
        border: 1px solid #e0e0e0;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: box-shadow 0.15s ease-in-out;
    }

    .header-section:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .header-section h4 {
        font-size: 24px;
        font-weight: 700;
        color: #212529;
        margin: 0;
    }

    .tab-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 30px;
    }

    .tab-btn {
        padding: 12px 28px;
        border: 1px solid #e9ecef;
        background: white;
        color: #495057;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .tab-btn.active {
        background: #1e3c72;
        color: white;
        border-color: #1e3c72;
    }

    .tab-btn:hover:not(.active) {
        background: #f8f9fa;
        transform: translateY(-2px);
    }

    .main-card {
        background: white;
        border-radius: 0.375rem;
        padding: 35px;
        border: 1px solid #e0e0e0;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: box-shadow 0.15s ease-in-out;
    }

    .main-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .form-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-bottom: 30px;
    }

    .left-section {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .right-section {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        font-weight: 600;
        color: #212529;
        margin-bottom: 10px;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-control,
    .form-select {
        padding: 12px 16px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #1e3c72;
        box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
        outline: none;
    }

    .upload-area {
        border: 2px dashed #cbd5e0;
        border-radius: 12px;
        padding: 40px;
        text-align: center;
        background: #f5f7fa;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }

    .upload-area:hover {
        border-color: #1e3c72;
        background: #f0f4f8;
    }

    .upload-area.dragover {
        border-color: #1e3c72;
        background: #e8f0fe;
    }

    .upload-icon {
        font-size: 48px;
        color: #1e3c72;
        margin-bottom: 15px;
    }

    .upload-text {
        font-size: 16px;
        color: #212529;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .upload-hint {
        font-size: 13px;
        color: #495057;
        margin-bottom: 15px;
    }

    .file-types {
        display: flex;
        justify-content: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .file-type-badge {
        background: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        color: #495057;
        border: 1px solid #e9ecef;
    }

    .uploaded-file {
        display: none;
        align-items: center;
        gap: 12px;
        padding: 15px;
        background: #e8f5e9;
        border-radius: 8px;
        margin-top: 15px;
    }

    .uploaded-file.show {
        display: flex;
    }

    .uploaded-file i {
        font-size: 24px;
        color: #4caf50;
    }

    .uploaded-file-info {
        flex: 1;
    }

    .uploaded-file-name {
        font-weight: 600;
        color: #212529;
        margin-bottom: 4px;
    }

    .uploaded-file-size {
        font-size: 12px;
        color: #495057;
    }

    .remove-file-btn {
        background: none;
        border: none;
        color: #dc3545;
        cursor: pointer;
        font-size: 20px;
        padding: 5px;
    }

    .template-section {
        margin-bottom: 30px;
    }

    .template-section h6 {
        font-weight: 600;
        color: #212529;
        margin-bottom: 15px;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .template-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        max-height: 250px;
        overflow-y: auto;
        padding-right: 10px;
    }

    .template-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px;
        background: #f5f7fa;
        border-radius: 8px;
        border: 2px solid transparent;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .template-item:hover {
        background: #e9ecef;
        border-color: #1e3c72;
    }

    .template-icon {
        font-size: 28px;
        color: #1e3c72;
    }

    .template-info {
        flex: 1;
    }

    .template-name {
        font-weight: 600;
        color: #212529;
        margin-bottom: 4px;
        font-size: 14px;
    }

    .template-desc {
        font-size: 12px;
        color: #495057;
    }

    .upload-template-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px;
        background: white;
        border: 2px dashed #cbd5e0;
        border-radius: 8px;
        color: #1e3c72;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 10px;
    }

    .upload-template-btn:hover {
        border-color: #1e3c72;
        background: #f0f4f8;
    }

    .prompt-section {
        margin-bottom: 30px;
    }

    .prompt-section h6 {
        font-weight: 600;
        color: #212529;
        margin-bottom: 15px;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .prompt-textarea {
        min-height: 150px;
        resize: vertical;
    }

    .prompt-example {
        background: #f5f7fa;
        padding: 15px;
        border-radius: 8px;
        margin-top: 10px;
        border-left: 4px solid #1e3c72;
    }

    .prompt-example-title {
        font-weight: 600;
        color: #212529;
        margin-bottom: 8px;
        font-size: 13px;
    }

    .prompt-example-text {
        font-size: 13px;
        color: #495057;
        font-style: italic;
        line-height: 1.6;
    }

    .action-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        padding-top: 20px;
        border-top: 2px solid #e9ecef;
    }

    .btn-generate {
        padding: 14px 32px;
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .btn-generate:hover {
        transform: translateY(-2px);
        opacity: 0.9;
    }

    .btn-generate:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .report-result {
        display: none;
        margin-top: 30px;
        padding: 30px;
        background: #f5f7fa;
        border-radius: 0.375rem;
        border: 1px solid #e0e0e0;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: box-shadow 0.15s ease-in-out;
    }

    .report-result:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .report-result.show {
        display: block;
    }

    .report-result h5 {
        font-weight: 700;
        color: #1e3c72;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .report-placeholder {
        text-align: center;
        padding: 60px 20px;
        color: #495057;
    }

    .report-placeholder i {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .download-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 25px;
    }

    .btn-download {
        padding: 12px 28px;
        border: 2px solid #1e3c72;
        background: white;
        color: #1e3c72;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-download:hover {
        background: #1e3c72;
        color: white;
        transform: translateY(-2px);
    }

    .loading-spinner {
        display: none;
        text-align: center;
        padding: 40px;
    }

    .loading-spinner.show {
        display: block;
    }

    .spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #1e3c72;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

    @media (max-width: 992px) {
        .form-section {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="buat-laporan-container">
    <div class="header-section">
        <h4>Buat Laporan Baru</h4>
        <p class="text-muted">Buat laporan strategis dengan mudah dan cepat</p>
    </div>

    <div class="tab-buttons">
        <button class="tab-btn active" data-tab="triwulan">Laporan Triwulan</button>
        <button class="tab-btn" data-tab="semester">Laporan Semester</button>
    </div>

    <div class="main-card">
        <form id="laporanForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="tipe_laporan" id="tipe_laporan" value="triwulan">

            <div class="form-section">
                <!-- Left Section -->
                <div class="left-section">
                    <!-- Informasi Laporan -->
                    <div class="form-group">
                        <label>Periode Laporan</label>
                        <input type="date" class="form-control" name="periode_tanggal" required>
                    </div>

                    <div class="form-group">
                        <label>Fakultas / Program Studi</label>
                        <select class="form-select" name="program_studi" required>
                            <option value="">Pilih Program Studi</option>
                            <option value="Teknik Informatika">Teknik Informatika</option>
                            <option value="Sistem Informasi">Sistem Informasi</option>
                            <option value="Teknik Elektro">Teknik Elektro</option>
                            <option value="Teknik Mesin">Teknik Mesin</option>
                            <option value="Teknik Sipil">Teknik Sipil</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Judul Laporan</label>
                        <input type="text" class="form-control" name="judul_laporan" 
                               placeholder="Masukkan judul laporan" required>
                    </div>

                    <!-- Sumber Daya Laporan -->
                    <div class="form-group">
                        <label>Dokumen Pendukung</label>
                        <div class="upload-area" id="uploadArea">
                            <input type="file" id="fileInput" name="dokumen" accept=".pdf,.doc,.docx,.xlsx,.xls" hidden>
                            <i class="bi bi-cloud-upload upload-icon"></i>
                            <div class="upload-text">Drag & Drop or Click to Upload</div>
                            <div class="upload-hint">Upload Activities documentation, Meeting minutes, GKM reports, Questionnaires</div>
                            <div class="file-types">
                                <span class="file-type-badge">laporan_input.pdf</span>
                                <span class="file-type-badge">monitoring_materi.xlsx</span>
                                <span class="file-type-badge">kuesioner_mahasiswa.xlsx</span>
                            </div>
                        </div>
                        <div class="uploaded-file" id="uploadedFile">
                            <i class="bi bi-file-earmark-check-fill"></i>
                            <div class="uploaded-file-info">
                                <div class="uploaded-file-name" id="fileName"></div>
                                <div class="uploaded-file-size" id="fileSize"></div>
                            </div>
                            <button type="button" class="remove-file-btn" id="removeFile">
                                <i class="bi bi-x-circle-fill"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Section -->
                <div class="right-section">
                    <!-- Template Laporan -->
                    <div class="template-section">
                        <h6>Template Laporan</h6>
                        <div class="template-list">
                            @forelse($templates as $template)
                            <div class="template-item">
                                <i class="bi bi-file-earmark-text template-icon"></i>
                                <div class="template-info">
                                    <div class="template-name">{{ $template->nama_template }}</div>
                                    <div class="template-desc">{{ $template->deskripsi ?? 'Laporan penilaian evaluasi template' }}</div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-inbox" style="font-size: 32px;"></i>
                                <p class="mt-2 mb-0">Belum ada template tersedia</p>
                            </div>
                            @endforelse
                        </div>
                        <button type="button" class="upload-template-btn">
                            <i class="bi bi-plus-circle"></i>
                            Upload Template Baru
                        </button>
                    </div>
                </div>
            </div>

            <!-- Instruksi Prompt -->
            <div class="prompt-section">
                <h6>Instruksi Prompt</h6>
                <textarea class="form-control prompt-textarea" name="instruksi_prompt" 
                          placeholder="Masukkan instruksi untuk AI..." required>2c3xde5dTy6l8o9=</textarea>
                <div class="prompt-example">
                    <div class="prompt-example-title">Contoh Prompt:</div>
                    <div class="prompt-example-text">
                        "Buat laporan triwulan monitoring mutu 1920s - Rangkuman : 1. Status upload materi 2. Evaluasi dokumen 3. Temuan dari apa. Sebutkan perbaikan format akademis."
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button type="submit" class="btn-generate" id="generateBtn">
                    <i class="bi bi-stars"></i>
                    Generate Report
                </button>
            </div>
        </form>

        <!-- Loading Spinner -->
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner"></div>
            <p style="color: #495057; font-weight: 600;">Generating report...</p>
        </div>

        <!-- Report Result -->
        <div class="report-result" id="reportResult">
            <h5>
                <i class="bi bi-file-earmark-check-fill"></i>
                Generated Report
            </h5>
            <div class="report-placeholder">
                <i class="bi bi-file-earmark-text"></i>
                <p>Pratinjau laporan akan muncul di sini...</p>
            </div>
            <div class="download-buttons">
                <button type="button" class="btn-download" id="downloadPDF">
                    <i class="bi bi-file-pdf"></i>
                    Download PDF
                </button>
                <button type="button" class="btn-download" id="downloadPPT">
                    <i class="bi bi-file-ppt"></i>
                    Download PPT
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tipeLaporanInput = document.getElementById('tipe_laporan');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            tabButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            tipeLaporanInput.value = this.dataset.tab;
        });
    });

    // File upload handling
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const uploadedFile = document.getElementById('uploadedFile');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const removeFile = document.getElementById('removeFile');

    uploadArea.addEventListener('click', () => fileInput.click());

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            displayFile(files[0]);
        }
    });

    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            displayFile(this.files[0]);
        }
    });

    removeFile.addEventListener('click', function() {
        fileInput.value = '';
        uploadedFile.classList.remove('show');
    });

    function displayFile(file) {
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        uploadedFile.classList.add('show');
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
        loadingSpinner.classList.add('show');
        reportResult.classList.remove('show');

        try {
            const response = await fetch('{{ route("gjm.buat-laporan.generate") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();

            // Hide loading
            loadingSpinner.classList.remove('show');
            generateBtn.disabled = false;

            if (data.success) {
                // Show result
                reportResult.classList.add('show');
                
                // Show success message
                alert('Laporan berhasil di-generate!');
            } else {
                alert('Gagal generate laporan: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error:', error);
            loadingSpinner.classList.remove('show');
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
