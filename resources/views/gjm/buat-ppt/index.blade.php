@extends('layouts.app')

@section('page-title', 'Buat PPT Baru')

@section('content')
<div style="padding: 1.5rem; max-width: 1400px; margin: 0 auto;">
    <!-- Header Card -->
    <div class="filter-card mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-file-ppt" style="color: #5B9BD5; font-size: 2rem;"></i>
                <div>
                    <h5 class="mb-1" style="font-weight: 600; color: #333;">Buat PPT Baru</h5>
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">Generate presentasi PowerPoint otomatis dari laporan Anda dengan AI</p>
                </div>
            </div>
            <div>
                <a href="{{ route('gjm.buat-ppt.archive') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-archive"></i> Arsip PPT
                </a>
            </div>
        </div>
    </div>

    <form id="pptForm">
        @csrf
        
        <div class="row g-4">
            <!-- Left Section - Pilih Laporan -->
            <div class="col-lg-7">
                <div class="modern-card">
                    <div class="card-header-modern">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-text-fill" style="color: #5B9BD5; font-size: 1.25rem;"></i>
                            <h6 class="mb-0" style="font-weight: 600;">Pilih Laporan Sumber</h6>
                        </div>
                        <span class="badge bg-primary">{{ $laporan->total() }} Tersedia</span>
                    </div>
                    
                    <div class="card-body-modern">
                        <!-- Search Bar -->
                        <div class="search-box mb-3">
                            <i class="bi bi-search search-icon"></i>
                            <input type="text" class="search-input" placeholder="Cari berdasarkan judul atau jenis laporan..." id="searchLaporan">
                        </div>

                        <!-- Laporan List -->
                        <div class="laporan-scroll-container" id="laporanList">
                            @forelse($laporan as $item)
                            <div class="laporan-card" data-id="{{ $item->id }}" onclick="selectLaporan(this, '{{ $item->id }}', '{{ addslashes($item->ringkasan_mutu_institusi ?? 'Laporan ' . $item->getJenisLaporanLabel()) }}')">
                                <input type="radio" name="laporan_id" value="{{ $item->id }}" id="laporan_{{ $item->id }}" class="laporan-radio">
                                <div class="laporan-card-content">
                                    <div class="d-flex gap-3">
                                        <div class="laporan-icon">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="laporan-title">
                                                {{ $item->ringkasan_mutu_institusi ?? 'Laporan ' . $item->getJenisLaporanLabel() }}
                                            </h6>
                                            <div class="laporan-meta">
                                                <span class="meta-item">
                                                    <i class="bi bi-calendar3"></i>
                                                    {{ $item->updated_at ? $item->updated_at->format('d M Y') : '-' }}
                                                </span>
                                                <span class="meta-divider">•</span>
                                                <span class="meta-item">
                                                    <i class="bi bi-tag"></i>
                                                    {{ $item->getJenisLaporanLabel() }}
                                                </span>
                                                @if($item->ajaran)
                                                <span class="meta-divider">•</span>
                                                <span class="meta-item">
                                                    <i class="bi bi-mortarboard"></i>
                                                    {{ $item->ajaran->tahun_ajaran }}
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="check-indicator">
                                            <i class="bi bi-check-circle-fill"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="empty-state-modern">
                                <div class="empty-icon">
                                    <i class="bi bi-inbox"></i>
                                </div>
                                <h6>Belum Ada Laporan</h6>
                                <p>Buat laporan terlebih dahulu untuk membuat presentasi</p>
                                <a href="{{ route('gjm.buat-laporan.index') }}" class="btn btn-primary btn-sm mt-2">
                                    <i class="bi bi-plus-circle"></i> Buat Laporan
                                </a>
                            </div>
                            @endforelse
                        </div>

                        @if($laporan->hasPages())
                        <div class="pagination-footer">
                            <small class="text-muted">
                                Menampilkan {{ $laporan->count() }} dari {{ $laporan->total() }} laporan
                            </small>
                            <div>
                                {{ $laporan->links() }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Section - Judul & Generate -->
            <div class="col-lg-5">
                <div class="modern-card sticky-card">
                    <div class="card-header-modern">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-pencil-square" style="color: #5B9BD5; font-size: 1.25rem;"></i>
                            <h6 class="mb-0" style="font-weight: 600;">Konfigurasi Presentasi</h6>
                        </div>
                    </div>
                    
                    <div class="card-body-modern">
                        <!-- Selected Laporan Preview -->
                        <div class="selected-preview" id="selectedPreview" style="display: none;">
                            <div class="d-flex align-items-start gap-2 mb-3">
                                <i class="bi bi-check-circle-fill text-success"></i>
                                <div class="flex-grow-1">
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">Laporan Terpilih:</small>
                                    <strong id="selectedTitle" style="font-size: 0.875rem; color: #333;"></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Judul Input -->
                        <div class="form-group-modern mb-4">
                            <label class="form-label-modern">
                                Judul Presentasi <span class="text-danger">*</span>
                            </label>
                            <small class="form-hint">Akan ditampilkan pada slide pembuka</small>
                            <textarea class="form-control-modern" id="judulPresentasi" name="judul_presentasi" 
                                   placeholder="Contoh: Laporan Evaluasi Kinerja Triwulan III - Fakultas Vokasi" 
                                   required rows="3"></textarea>
                            <div class="char-counter">
                                <span id="charCount">0</span>/200 karakter
                            </div>
                        </div>
                        
                        <!-- PPT Info Cards -->
                        <div class="info-cards-grid mb-4">
                            <div class="info-card-small">
                                <i class="bi bi-file-slides"></i>
                                <div>
                                    <div class="info-label">Format</div>
                                    <div class="info-value">PowerPoint</div>
                                </div>
                            </div>
                            <div class="info-card-small">
                                <i class="bi bi-clock-history"></i>
                                <div>
                                    <div class="info-label">Waktu</div>
                                    <div class="info-value">2-3 menit</div>
                                </div>
                            </div>
                            <div class="info-card-small">
                                <i class="bi bi-layout-text-window-reverse"></i>
                                <div>
                                    <div class="info-label">Slide</div>
                                    <div class="info-value">10-15 slide</div>
                                </div>
                            </div>
                            <div class="info-card-small">
                                <i class="bi bi-palette"></i>
                                <div>
                                    <div class="info-label">Template</div>
                                    <div class="info-value">Professional</div>
                                </div>
                            </div>
                        </div>

                        <!-- Features List -->
                        <div class="features-list mb-4">
                            <h6 class="features-title">
                                <i class="bi bi-stars"></i> Fitur AI Generation
                            </h6>
                            <ul class="features-items">
                                <li><i class="bi bi-check2"></i> Struktur slide otomatis</li>
                                <li><i class="bi bi-check2"></i> Desain profesional</li>
                                <li><i class="bi bi-check2"></i> Konten terstruktur</li>
                                <li><i class="bi bi-check2"></i> Siap presentasi</li>
                            </ul>
                        </div>
                        
                        <!-- Generate Button -->
                        <button type="submit" class="btn-generate" id="generateBtn" disabled>
                            <span class="btn-content">
                                <i class="bi bi-stars"></i>
                                <span>Generate Presentasi</span>
                            </span>
                            <span class="btn-loading" style="display: none;">
                                <span class="spinner-border spinner-border-sm"></span>
                                <span>Generating...</span>
                            </span>
                        </button>
                        
                        <p class="text-center text-muted mt-3" style="font-size: 0.75rem;">
                            <i class="bi bi-shield-check"></i> Presentasi akan disimpan otomatis
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Loading Modal -->
    <div class="loading-modal" id="loadingModal" style="display: none;">
        <div class="loading-content">
            <div class="loading-animation">
                <div class="loading-spinner"></div>
                <i class="bi bi-file-ppt loading-icon"></i>
            </div>
            <h5 class="loading-title">Generating Presentasi...</h5>
            <p class="loading-text">AI sedang membuat presentasi profesional untuk Anda</p>
            <div class="loading-progress">
                <div class="progress-bar-custom">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <small class="progress-text" id="progressText">Memproses konten...</small>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="success-modal" id="successModal" style="display: none;">
        <div class="success-content">
            <div class="success-animation">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <h5 class="success-title">Presentasi Berhasil Dibuat!</h5>
            <p class="success-text" id="successMessage">PPT Anda telah siap untuk diunduh</p>
            <div class="success-actions">
                <a href="#" class="btn btn-success btn-lg" id="downloadBtn">
                    <i class="bi bi-download"></i> Download PPT
                </a>
                <a href="{{ route('gjm.buat-ppt.archive') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-archive"></i> Lihat Arsip
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern Card */
.modern-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: all 0.3s ease;
}

.modern-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.card-header-modern {
    padding: 1.25rem 1.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-body-modern {
    padding: 1.5rem;
}

/* Search Box */
.search-box {
    position: relative;
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 1rem;
}

.search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.75rem;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: #5B9BD5;
    box-shadow: 0 0 0 4px rgba(91, 155, 213, 0.1);
}

/* Laporan Scroll Container */
.laporan-scroll-container {
    max-height: 500px;
    overflow-y: auto;
    padding-right: 0.5rem;
}

.laporan-scroll-container::-webkit-scrollbar {
    width: 6px;
}

.laporan-scroll-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.laporan-scroll-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

.laporan-scroll-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Laporan Card */
.laporan-card {
    position: relative;
    margin-bottom: 0.75rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.laporan-radio {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.laporan-card-content {
    padding: 1rem;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    background: white;
    transition: all 0.3s ease;
}

.laporan-card:hover .laporan-card-content {
    border-color: #5B9BD5;
    background: #f8f9fa;
    transform: translateX(4px);
}

.laporan-card.selected .laporan-card-content {
    border-color: #5B9BD5;
    background: linear-gradient(135deg, #f0f7ff 0%, #e6f2ff 100%);
    box-shadow: 0 4px 12px rgba(91, 155, 213, 0.2);
}

.laporan-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #5B9BD5 0%, #4a7ba7 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.laporan-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.laporan-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    font-size: 0.75rem;
    color: #6c757d;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.meta-divider {
    color: #dee2e6;
}

.check-indicator {
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 1.5rem;
    color: #e9ecef;
    transition: all 0.3s ease;
}

.laporan-card.selected .check-indicator {
    color: #28a745;
    transform: scale(1.2);
}

/* Empty State Modern */
.empty-state-modern {
    text-align: center;
    padding: 3rem 1rem;
}

.empty-icon {
    font-size: 4rem;
    color: #dee2e6;
    margin-bottom: 1rem;
}

.empty-state-modern h6 {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.empty-state-modern p {
    color: #6c757d;
    font-size: 0.875rem;
}

/* Pagination Footer */
.pagination-footer {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Sticky Card */
.sticky-card {
    position: sticky;
    top: 1.5rem;
}

/* Selected Preview */
.selected-preview {
    padding: 1rem;
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    border-radius: 12px;
    border-left: 4px solid #28a745;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Form Group Modern */
.form-group-modern {
    margin-bottom: 1.5rem;
}

.form-label-modern {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
}

.form-hint {
    display: block;
    font-size: 0.75rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.form-control-modern {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    resize: vertical;
}

.form-control-modern:focus {
    outline: none;
    border-color: #5B9BD5;
    box-shadow: 0 0 0 4px rgba(91, 155, 213, 0.1);
}

.char-counter {
    text-align: right;
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

/* Info Cards Grid */
.info-cards-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.info-card-small {
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border: 1px solid #e9ecef;
}

.info-card-small i {
    font-size: 1.5rem;
    color: #5B9BD5;
}

.info-label {
    font-size: 0.7rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 0.875rem;
    font-weight: 600;
    color: #333;
}

/* Features List */
.features-list {
    padding: 1rem;
    background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%);
    border-radius: 12px;
    border-left: 4px solid #ffc107;
}

.features-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.75rem;
}

.features-items {
    list-style: none;
    padding: 0;
    margin: 0;
}

.features-items li {
    font-size: 0.8rem;
    color: #495057;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.features-items li i {
    color: #28a745;
    font-size: 1rem;
}

/* Generate Button */
.btn-generate {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-generate:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-generate:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-content, .btn-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

/* Loading Modal */
.loading-modal, .success-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.loading-content, .success-content {
    background: white;
    border-radius: 20px;
    padding: 3rem 2rem;
    max-width: 500px;
    text-align: center;
    animation: scaleIn 0.3s ease;
}

@keyframes scaleIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.loading-animation {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto 2rem;
}

.loading-spinner {
    position: absolute;
    width: 100%;
    height: 100%;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #5B9BD5;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 3rem;
    color: #5B9BD5;
}

.loading-title, .success-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.5rem;
}

.loading-text, .success-text {
    color: #6c757d;
    margin-bottom: 2rem;
}

.loading-progress {
    margin-top: 2rem;
}

.progress-bar-custom {
    width: 100%;
    height: 8px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    width: 0%;
    transition: width 0.3s ease;
    animation: progressAnimation 3s ease-in-out infinite;
}

@keyframes progressAnimation {
    0%, 100% { width: 30%; }
    50% { width: 70%; }
}

.progress-text {
    display: block;
    color: #6c757d;
    font-size: 0.875rem;
}

/* Success Animation */
.success-animation {
    width: 120px;
    height: 120px;
    margin: 0 auto 2rem;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: successPulse 0.6s ease;
}

@keyframes successPulse {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.success-animation i {
    font-size: 4rem;
    color: white;
}

.success-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

/* Responsive */
@media (max-width: 991px) {
    .sticky-card {
        position: static;
    }
    
    .info-cards-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
let selectedLaporanId = null;

function selectLaporan(element, id, title) {
    // Remove selected class from all items
    document.querySelectorAll('.laporan-card').forEach(item => {
        item.classList.remove('selected');
    });
    
    // Add selected class to clicked item
    element.classList.add('selected');
    
    // Check the radio button
    document.getElementById('laporan_' + id).checked = true;
    selectedLaporanId = id;
    
    // Show selected preview
    const selectedPreview = document.getElementById('selectedPreview');
    const selectedTitle = document.getElementById('selectedTitle');
    selectedPreview.style.display = 'block';
    selectedTitle.textContent = title;
    
    // Auto-fill title if empty
    const judulInput = document.getElementById('judulPresentasi');
    if (!judulInput.value) {
        judulInput.value = `Presentasi ${title}`;
        updateCharCount();
    }
    
    // Enable generate button
    document.getElementById('generateBtn').disabled = false;
}

function updateCharCount() {
    const judulInput = document.getElementById('judulPresentasi');
    const charCount = document.getElementById('charCount');
    const length = judulInput.value.length;
    charCount.textContent = length;
    
    if (length > 200) {
        charCount.style.color = '#dc3545';
    } else if (length > 150) {
        charCount.style.color = '#ffc107';
    } else {
        charCount.style.color = '#6c757d';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Character counter
    const judulInput = document.getElementById('judulPresentasi');
    judulInput.addEventListener('input', updateCharCount);

    // Search functionality
    const searchInput = document.getElementById('searchLaporan');
    const laporanCards = document.querySelectorAll('.laporan-card');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        laporanCards.forEach(card => {
            const title = card.querySelector('.laporan-title').textContent.toLowerCase();
            const meta = card.querySelector('.laporan-meta').textContent.toLowerCase();
            
            if (title.includes(searchTerm) || meta.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });

    // Form submission
    const form = document.getElementById('pptForm');
    const generateBtn = document.getElementById('generateBtn');
    const loadingModal = document.getElementById('loadingModal');
    const successModal = document.getElementById('successModal');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        // Validate selection
        if (!selectedLaporanId) {
            alert('Silakan pilih laporan terlebih dahulu');
            return;
        }

        // Validate title
        const judul = judulInput.value.trim();
        if (!judul) {
            alert('Silakan masukkan judul presentasi');
            judulInput.focus();
            return;
        }

        if (judul.length > 200) {
            alert('Judul presentasi maksimal 200 karakter');
            judulInput.focus();
            return;
        }

        // Show loading modal
        loadingModal.style.display = 'flex';
        generateBtn.querySelector('.btn-content').style.display = 'none';
        generateBtn.querySelector('.btn-loading').style.display = 'flex';
        generateBtn.disabled = true;

        // Simulate progress
        let progress = 0;
        const progressMessages = [
            'Memproses konten...',
            'Menganalisis struktur...',
            'Membuat slide...',
            'Menerapkan desain...',
            'Finalisasi presentasi...'
        ];
        
        const progressInterval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            
            progressFill.style.width = progress + '%';
            const messageIndex = Math.floor(progress / 20);
            if (progressMessages[messageIndex]) {
                progressText.textContent = progressMessages[messageIndex];
            }
        }, 500);

        try {
            const response = await fetch('{{ route("gjm.buat-ppt.generate") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();

            // Clear progress interval
            clearInterval(progressInterval);
            progressFill.style.width = '100%';
            progressText.textContent = 'Selesai!';

            // Hide loading modal
            setTimeout(() => {
                loadingModal.style.display = 'none';
                
                if (data.success) {
                    // Show success modal
                    successModal.style.display = 'flex';
                    
                    // Update success message
                    const successMessage = document.getElementById('successMessage');
                    successMessage.textContent = `${data.ppt.slides_count} slide telah dibuat dengan desain profesional`;
                    
                    // Update download button
                    const downloadBtn = document.getElementById('downloadBtn');
                    downloadBtn.href = data.download_url;
                    
                    // Reset form after 3 seconds
                    setTimeout(() => {
                        form.reset();
                        selectedLaporanId = null;
                        document.querySelectorAll('.laporan-card').forEach(card => {
                            card.classList.remove('selected');
                        });
                        document.getElementById('selectedPreview').style.display = 'none';
                        updateCharCount();
                    }, 3000);
                } else {
                    alert('Gagal generate PPT: ' + (data.message || 'Unknown error'));
                }
            }, 500);

        } catch (error) {
            console.error('Error:', error);
            clearInterval(progressInterval);
            loadingModal.style.display = 'none';
            alert('Terjadi kesalahan saat generate PPT. Silakan coba lagi.');
        } finally {
            generateBtn.querySelector('.btn-content').style.display = 'flex';
            generateBtn.querySelector('.btn-loading').style.display = 'none';
            generateBtn.disabled = false;
        }
    });

    // Close success modal when clicking download or archive
    document.getElementById('downloadBtn').addEventListener('click', function() {
        setTimeout(() => {
            successModal.style.display = 'none';
        }, 500);
    });

    // Close modals when clicking outside
    [loadingModal, successModal].forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
});
</script>
@endsection
