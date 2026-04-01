@extends('layouts.app')

@section('page-title', 'Buat PPT Baru')

@section('styles')
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .buat-ppt-container {
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
        margin: 0 0 8px 0;
    }

    .header-section p {
        margin: 0;
        font-size: 16px;
    }

    .main-card {
        background: #f5f7fa;
        border-radius: 12px;
        padding: 0;
        box-shadow: none;
        max-width: 1200px;
        margin: 0 auto;
    }

    .form-layout {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        padding: 30px;
        align-items: stretch;
        min-height: 500px;
    }

    .left-section {
        background: white;
        border-radius: 0.375rem;
        padding: 24px;
        border: 1px solid #e0e0e0;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        display: flex;
        flex-direction: column;
        transition: box-shadow 0.15s ease-in-out;
    }

    .left-section:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .right-section {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .section-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #f3f4f6;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-title i {
        color: #1e3c72;
        font-size: 20px;
    }

    .search-section {
        margin-bottom: 0;
        padding: 0;
        background: transparent;
        border: none;
        border-radius: 0;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .search-box {
        position: relative;
        margin-bottom: 20px;
    }

    .search-box input {
        width: 100%;
        padding: 14px 20px 14px 45px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-size: 14px;
        background: #f5f7fa;
        transition: all 0.3s ease;
        color: #212529;
    }

    .search-box input::placeholder {
        color: #6c757d;
        font-weight: 400;
    }

    .search-box input:focus {
        border-color: #1e3c72;
        background: white;
        box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
        outline: none;
    }

    .search-box i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        font-size: 16px;
    }

    .laporan-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 20px;
        flex: 1;
        overflow-y: auto;
        padding-right: 4px;
        min-height: 300px;
    }

    .laporan-list::-webkit-scrollbar {
        width: 6px;
    }

    .laporan-list::-webkit-scrollbar-track {
        background: #f1f3f4;
        border-radius: 3px;
    }

    .laporan-list::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 3px;
    }

    .laporan-list::-webkit-scrollbar-thumb:hover {
        background: #9ca3af;
    }

    .laporan-item {
        border: 1px solid #e0e0e0;
        border-radius: 0.375rem;
        padding: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        background: white;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .laporan-item:hover {
        border-color: #1e3c72;
        background: #f8fafc;
        transform: translateY(-1px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .laporan-item.selected {
        border-color: #1e3c72;
        background: linear-gradient(135deg, #f0f4f8 0%, #e6f2ff 100%);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .laporan-item input[type="radio"] {
        position: absolute;
        top: 16px;
        right: 16px;
        width: 18px;
        height: 18px;
        accent-color: #1e3c72;
    }

    .laporan-title {
        font-weight: 600;
        color: #212529;
        margin-bottom: 8px;
        font-size: 15px;
        padding-right: 35px;
        line-height: 1.4;
    }

    .laporan-meta {
        display: flex;
        gap: 12px;
        font-size: 12px;
        color: #495057;
        align-items: center;
    }

    .laporan-meta span {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .laporan-meta i {
        font-size: 11px;
    }

    .judul-section {
        background: white;
        border-radius: 0.375rem;
        padding: 24px;
        border: 1px solid #e0e0e0;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        position: relative;
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 500px;
        transition: box-shadow 0.15s ease-in-out;
    }

    .judul-section:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .form-group {
        margin-bottom: 20px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .form-label {
        font-weight: 600;
        margin-bottom: 8px;
        display: block;
        font-size: 14px;
    }

    .form-label .text-muted {
        font-weight: 400;
        font-size: 12px;
    }

    .judul-input {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-size: 14px;
        transition: all 0.3s ease;
        background: #f5f7fa;
        min-height: 120px;
        resize: vertical;
    }

    .judul-input:focus {
        border-color: #1e3c72;
        background: white;
        box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
        outline: none;
    }

    .judul-hint {
        font-size: 12px;
        margin-top: 6px;
        margin-bottom: 24px;
        font-style: italic;
        padding: 8px 12px;
        background: #f3f4f6;
        border-radius: 6px;
        border-left: 3px solid #1e3c72;
    }

    .generate-button-container {
        text-align: center;
        padding-top: 20px;
        border-top: 1px solid #f3f4f6;
        margin-top: auto;
    }

    .btn-generate {
        padding: 16px 32px;
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-width: 180px;
        justify-content: center;
    }

    .btn-generate:hover {
        transform: translateY(-2px);
        opacity: 0.9;
    }

    .btn-generate:active {
        transform: translateY(0);
    }

    .btn-generate:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .btn-generate i {
        font-size: 16px;
    }

    .info-section {
        background: #f8fafc;
        border-radius: 10px;
        padding: 20px;
        border: 1px solid #e2e8f0;
        margin-bottom: 20px;
    }

    .info-title {
        font-size: 16px;
        font-weight: 600;
        color: #212529;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-title i {
        color: #1e3c72;
        font-size: 18px;
    }

    .info-content {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 0;
    }

    .info-item i {
        color: #495057;
        font-size: 16px;
        width: 20px;
        text-align: center;
    }

    .info-item div {
        flex: 1;
    }

    .info-item strong {
        color: #212529;
        font-size: 13px;
        display: block;
        margin-bottom: 2px;
    }

    .info-item span {
        color: #495057;
        font-size: 12px;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.5;
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
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .result-section {
        display: none;
        margin-top: 30px;
        padding: 30px;
        background: #f5f7fa;
        border-radius: 0.375rem;
        border: 1px solid #e0e0e0;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: box-shadow 0.15s ease-in-out;
    }

    .result-section:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .result-section.show {
        display: block;
    }

    .result-section h5 {
        font-weight: 700;
        color: #1e3c72;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .result-placeholder {
        text-align: center;
        padding: 40px 20px;
        color: #495057;
    }

    .result-placeholder i {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .download-section {
        display: flex;
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
        text-decoration: none;
    }

    .btn-download:hover {
        background: #1e3c72;
        color: white;
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .buat-ppt-container {
            padding: 20px;
        }
        
        .form-layout {
            grid-template-columns: 1fr;
            gap: 20px;
            padding: 20px;
        }
        
        .laporan-meta {
            flex-direction: column;
            gap: 8px;
        }

        .pagination-container {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }

        .pagination-nav {
            justify-content: center;
        }
    }

    .pagination-container {
        padding: 20px 0;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
    }

    .pagination-info {
        font-size: 14px;
        flex-shrink: 0;
    }

    .pagination-nav {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .page-btn {
        width: 36px;
        height: 36px;
        border: 1px solid #e5e7eb;
        background: white;
        color: #6b7280;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        margin: 0 2px;
    }

    .page-btn:hover:not(.disabled):not(.active) {
        border-color: #d1d5db;
        background: #f9fafb;
        color: #374151;
        text-decoration: none;
    }

    .page-btn.active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }

    .page-btn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }
</style>
@endsection

@section('content')
<div class="buat-ppt-container">
    <div class="header-section">
        <h4>Buat PPT Baru Terpadu</h4>
        <p class="text-muted">Buat presentasi laporan otomatis dengan mudah</p>
    </div>

    <div class="main-card">
        <form id="pptForm">
            @csrf
            
            <div class="form-layout">
                <!-- Left Section - Pilih Laporan -->
                <div class="left-section">
                    <div class="search-section">
                        <h6 class="section-title">
                            <i class="bi bi-file-earmark-text"></i>
                            Pilih Laporan
                        </h6>
                        
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="text" placeholder="Cari laporan berdasarkan judul..." id="searchLaporan">
                        </div>

                        <div class="laporan-list" id="laporanList">
                            @forelse($laporan as $item)
                            <div class="laporan-item" data-id="{{ $item->id }}">
                                <input type="radio" name="laporan_id" value="{{ $item->id }}" id="laporan_{{ $item->id }}">
                                <div class="laporan-content">
                                    <div class="laporan-title">
                                        {{ $item->ringkasan_mutu_institusi ?? 'Laporan ' . $item->getJenisLaporanLabel() }}
                                    </div>
                                    <div class="laporan-meta">
                                        <span>
                                            <i class="bi bi-clock"></i>
                                            {{ $item->updated_at ? $item->updated_at->diffForHumans() : '-' }}
                                        </span>
                                        <span>
                                            <i class="bi bi-tag"></i>
                                            {{ $item->getJenisLaporanLabel() }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="empty-state">
                                <i class="bi bi-file-earmark-text"></i>
                                <p class="mb-0">Belum ada laporan yang tersedia</p>
                                <small class="text-muted">Buat laporan terlebih dahulu untuk membuat PPT</small>
                            </div>
                            @endforelse
                        </div>

                        @if($laporan->hasPages())
                        <div class="pagination-container">
                            <div class="pagination-info text-muted">
                                Menampilkan {{ $laporan->count() }} dari {{ $laporan->total() }} data
                            </div>
                            <div class="pagination-nav">
                                @if($laporan->onFirstPage())
                                    <span class="page-btn disabled">‹</span>
                                @else
                                    <a href="{{ $laporan->previousPageUrl() }}" class="page-btn">‹</a>
                                @endif

                                @for($page = 1; $page <= $laporan->lastPage(); $page++)
                                    @if($page == $laporan->currentPage())
                                        <span class="page-btn active">{{ $page }}</span>
                                    @else
                                        <a href="{{ $laporan->url($page) }}" class="page-btn">{{ $page }}</a>
                                    @endif
                                @endfor

                                @if($laporan->hasMorePages())
                                    <a href="{{ $laporan->nextPageUrl() }}" class="page-btn">›</a>
                                @else
                                    <span class="page-btn disabled">›</span>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Right Section - Judul & Generate Button -->
                <div class="right-section">
                    <!-- Judul Presentasi Section -->
                    <div class="judul-section">
                        <h6 class="section-title">
                            <i class="bi bi-pencil-square"></i>
                            Judul Laporan
                        </h6>
                        
                        <div class="form-group">
                            <label for="judulPresentasi" class="form-label">
                                Judul Laporan 
                                <small class="text-muted">(Muncul pada slide pertama)</small>
                            </label>
                            <textarea class="judul-input" id="judulPresentasi" name="judul_presentasi" 
                                   placeholder="Masukkan judul presentasi Anda..." required rows="3"></textarea>
                            <div class="judul-hint">
                                <i class="bi bi-lightbulb"></i>
                                <small class="text-muted">Contoh: "Laporan Evaluasi Kinerja Q1 2024 - Fakultas Teknik"</small>
                            </div>
                        </div>
                        
                        <!-- Additional Information Section -->
                        <div class="info-section">
                            <h6 class="info-title">
                                <i class="bi bi-info-circle"></i>
                                Informasi PPT
                            </h6>
                            <div class="info-content">
                                <div class="info-item">
                                    <i class="bi bi-file-slides"></i>
                                    <div>
                                        <strong>Format Output:</strong>
                                        <span>PowerPoint (.pptx)</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <i class="bi bi-clock"></i>
                                    <div>
                                        <strong>Waktu Proses:</strong>
                                        <span>2-3 menit</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <i class="bi bi-layout-text-window-reverse"></i>
                                    <div>
                                        <strong>Jumlah Slide:</strong>
                                        <span>10-15 slide</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <i class="bi bi-palette"></i>
                                    <div>
                                        <strong>Template:</strong>
                                        <span>Professional Blue</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Generate Button -->
                        <div class="generate-button-container">
                            <button type="submit" class="btn-generate" id="generateBtn">
                                <i class="bi bi-stars"></i>
                                Generate Laporan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Loading Spinner -->
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner"></div>
            <p style="color: #495057; font-weight: 600;">Generating PPT...</p>
        </div>

        <!-- Result Section -->
        <div class="result-section" id="resultSection">
            <h5>
                <i class="bi bi-file-ppt-fill"></i>
                PPT Generated Successfully
            </h5>
            <div class="result-placeholder">
                <i class="bi bi-file-ppt"></i>
                <p>Presentasi Anda telah berhasil dibuat</p>
            </div>
            <div class="download-section">
                <a href="#" class="btn-download" id="downloadBtn">
                    <i class="bi bi-download"></i>
                    Download PPT
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchLaporan');
    const laporanItems = document.querySelectorAll('.laporan-item');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        laporanItems.forEach(item => {
            const title = item.querySelector('.laporan-title').textContent.toLowerCase();
            if (title.includes(searchTerm)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });

    // Laporan selection
    laporanItems.forEach(item => {
        item.addEventListener('click', function() {
            // Remove selected class from all items
            laporanItems.forEach(i => i.classList.remove('selected'));
            
            // Add selected class to clicked item
            this.classList.add('selected');
            
            // Check the radio button
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Auto-fill title if empty
            const judulInput = document.getElementById('judulPresentasi');
            if (!judulInput.value) {
                const laporanTitle = this.querySelector('.laporan-title').textContent;
                judulInput.value = `Presentasi ${laporanTitle}`;
            }
        });
    });

    // Form submission
    const form = document.getElementById('pptForm');
    const generateBtn = document.getElementById('generateBtn');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const resultSection = document.getElementById('resultSection');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        // Validate selection
        const selectedLaporan = document.querySelector('input[name="laporan_id"]:checked');
        if (!selectedLaporan) {
            alert('Silakan pilih laporan terlebih dahulu');
            return;
        }

        // Show loading
        generateBtn.disabled = true;
        loadingSpinner.classList.add('show');
        resultSection.classList.remove('show');

        try {
            const response = await fetch('{{ route("gjm.buat-ppt.generate") }}', {
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
                resultSection.classList.add('show');
                
                // Show success message
                alert('PPT berhasil di-generate!');
            } else {
                alert('Gagal generate PPT: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error:', error);
            loadingSpinner.classList.remove('show');
            generateBtn.disabled = false;
            alert('Terjadi kesalahan saat generate PPT');
        }
    });

    // Download button
    document.getElementById('downloadBtn').addEventListener('click', function(e) {
        e.preventDefault();
        alert('Download PPT akan segera tersedia');
    });
});
</script>
@endsection
