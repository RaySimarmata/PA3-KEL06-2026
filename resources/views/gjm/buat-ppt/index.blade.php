@extends('layouts.app')

@section('page-title', 'Buat PPT Baru')

@section('content')
<div style="padding: 1.5rem;">
    <!-- Header -->
    <div class="monitoring-card mb-4" style="border-left: 4px solid #1e3c72;">
        <div style="padding: 1.5rem;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-file-ppt" style="color: #1e3c72; font-size: 2rem;"></i>
                    <div>
                        <h6 class="mb-1" style="font-weight: 600; color: #333;">Buat PPT Baru</h6>
                        <p class="text-muted mb-0" style="font-size: 0.875rem;">
                            Generate presentasi PowerPoint dari laporan Anda
                        </p>
                    </div>
                </div>
                <a href="{{ route('gjm.buat-ppt.archive') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-archive"></i> Arsip PPT
                </a>
            </div>
        </div>
    </div>

    <form id="pptForm">
        @csrf
        
        <div class="row">
            <!-- Pilih Laporan -->
            <div class="col-lg-7">
                <div class="monitoring-card mb-4">
                    <div class="monitoring-header">
                        <h6 class="mb-0">Pilih Laporan Sumber</h6>
                    </div>
                    
                    <div style="padding: 1.5rem;">
                        <!-- Search -->
                        <div class="mb-3">
                            <input type="text" class="form-control" placeholder="Cari laporan..." id="searchLaporan">
                        </div>

                        <!-- Laporan List -->
                        <div style="max-height: 450px; overflow-y: auto;" id="laporanList">
                            @forelse($laporan as $item)
                            <div class="laporan-item" data-id="{{ $item->id }}" onclick="selectLaporan(this, '{{ $item->id }}', '{{ addslashes($item->ringkasan_mutu_institusi ?? 'Laporan ' . $item->getJenisLaporanLabel()) }}')">
                                <input type="radio" name="laporan_id" value="{{ $item->id }}" id="laporan_{{ $item->id }}" class="laporan-radio">
                                <div class="laporan-content">
                                    <div class="d-flex gap-3">
                                        <div class="laporan-icon">
                                            <i class="bi bi-file-text"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1" style="font-size: 0.95rem; font-weight: 600;">
                                                {{ $item->ringkasan_mutu_institusi ?? 'Laporan ' . $item->getJenisLaporanLabel() }}
                                            </h6>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                {{ $item->getJenisLaporanLabel() }}
                                                @if($item->ajaran) • {{ $item->ajaran->tahun_ajaran }} @endif
                                                • {{ $item->updated_at ? $item->updated_at->format('d M Y') : '-' }}
                                            </div>
                                        </div>
                                        <div class="check-icon">
                                            <i class="bi bi-check-circle-fill"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #dee2e6;"></i>
                                <p class="text-muted mt-2">Belum ada laporan</p>
                                <a href="{{ route('gjm.buat-laporan.index') }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle"></i> Buat Laporan
                                </a>
                            </div>
                            @endforelse
                        </div>

                        @if($laporan->hasPages())
                        <div class="mt-3 pt-3 border-top">
                            {{ $laporan->links() }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Konfigurasi -->
            <div class="col-lg-5">
                <div class="monitoring-card mb-4">
                    <div class="monitoring-header">
                        <h6 class="mb-0">Konfigurasi Presentasi</h6>
                    </div>
                    
                    <div style="padding: 1.5rem;">
                        <!-- Selected Preview -->
                        <div class="alert alert-success" id="selectedPreview" style="display: none;">
                            <small class="d-block text-muted" style="font-size: 0.75rem;">Laporan Terpilih:</small>
                            <strong id="selectedTitle" style="font-size: 0.875rem;"></strong>
                        </div>

                        <!-- Judul Input -->
                        <div class="mb-4">
                            <label class="filter-label">Judul Presentasi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="judulPresentasi" name="judul_presentasi" 
                                   placeholder="Masukkan judul presentasi" 
                                   required rows="3" maxlength="200"></textarea>
                            <small class="text-muted">
                                <span id="charCount">0</span>/200 karakter
                            </small>
                        </div>
                        
                        <!-- Info -->
                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <div class="p-2 bg-light rounded text-center">
                                    <div class="text-muted" style="font-size: 0.7rem;">Format</div>
                                    <div style="font-size: 0.875rem; font-weight: 600;">PowerPoint</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 bg-light rounded text-center">
                                    <div class="text-muted" style="font-size: 0.7rem;">Slide</div>
                                    <div style="font-size: 0.875rem; font-weight: 600;">10-15</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Generate Button -->
                        <button type="submit" class="btn btn-primary w-100" id="generateBtn" disabled>
                            <span class="btn-content">
                                <i class="bi bi-magic"></i>
                                Generate Presentasi
                            </span>
                            <span class="btn-loading" style="display: none;">
                                <span class="spinner-border spinner-border-sm"></span>
                                Generating...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5>Generating Presentasi...</h5>
                    <p class="text-muted mb-0" id="progressText">Memproses konten...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    <h5 class="mt-3">Presentasi Berhasil Dibuat!</h5>
                    <p class="text-muted" id="successMessage">PPT Anda telah siap untuk diunduh</p>
                    <div class="d-flex gap-2 justify-content-center mt-4">
                        <a href="#" class="btn btn-success" id="downloadBtn">
                            <i class="bi bi-download"></i> Download PPT
                        </a>
                        <a href="{{ route('gjm.buat-ppt.archive') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-archive"></i> Lihat Arsip
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.laporan-item {
    margin-bottom: 0.75rem;
    cursor: pointer;
}

.laporan-radio {
    display: none;
}

.laporan-content {
    padding: 1rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.laporan-item:hover .laporan-content {
    border-color: #1e3c72;
    background: #f8f9fa;
}

.laporan-item.selected .laporan-content {
    border-color: #1e3c72;
    background: #e6f2ff;
}

.laporan-icon {
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

.check-icon {
    font-size: 1.5rem;
    color: #e9ecef;
    transition: all 0.3s ease;
}

.laporan-item.selected .check-icon {
    color: #28a745;
}

.btn-loading {
    display: none !important;
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

<script>
let selectedLaporanId = null;
let loadingModal = null;
let successModal = null;

function selectLaporan(element, id, title) {
    document.querySelectorAll('.laporan-item').forEach(item => {
        item.classList.remove('selected');
    });
    
    element.classList.add('selected');
    document.getElementById('laporan_' + id).checked = true;
    selectedLaporanId = id;
    
    const selectedPreview = document.getElementById('selectedPreview');
    const selectedTitle = document.getElementById('selectedTitle');
    selectedPreview.style.display = 'block';
    selectedTitle.textContent = title;
    
    const judulInput = document.getElementById('judulPresentasi');
    if (!judulInput.value) {
        judulInput.value = `Presentasi ${title}`;
        updateCharCount();
    }
    
    document.getElementById('generateBtn').disabled = false;
}

function updateCharCount() {
    const judulInput = document.getElementById('judulPresentasi');
    const charCount = document.getElementById('charCount');
    charCount.textContent = judulInput.value.length;
}

document.addEventListener('DOMContentLoaded', function() {
    loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
    successModal = new bootstrap.Modal(document.getElementById('successModal'));
    
    const judulInput = document.getElementById('judulPresentasi');
    judulInput.addEventListener('input', updateCharCount);

    // Search
    const searchInput = document.getElementById('searchLaporan');
    const laporanItems = document.querySelectorAll('.laporan-item');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        laporanItems.forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(searchTerm) ? 'block' : 'none';
        });
    });

    // Form submission
    const form = document.getElementById('pptForm');
    const generateBtn = document.getElementById('generateBtn');
    const progressText = document.getElementById('progressText');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        if (!selectedLaporanId) {
            alert('Silakan pilih laporan terlebih dahulu');
            return;
        }

        const judul = judulInput.value.trim();
        if (!judul || judul.length > 200) {
            alert('Judul presentasi tidak valid');
            return;
        }

        loadingModal.show();
        generateBtn.querySelector('.btn-content').style.display = 'none';
        generateBtn.querySelector('.btn-loading').style.display = 'inline-block';
        generateBtn.disabled = true;

        const progressMessages = [
            'Memproses konten...',
            'Menganalisis struktur...',
            'Membuat slide...',
            'Finalisasi presentasi...'
        ];
        
        let msgIndex = 0;
        const progressInterval = setInterval(() => {
            msgIndex = (msgIndex + 1) % progressMessages.length;
            progressText.textContent = progressMessages[msgIndex];
        }, 1500);

        try {
            const response = await fetch('{{ route("gjm.buat-ppt.generate") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();
            clearInterval(progressInterval);

            loadingModal.hide();
            
            if (data.success) {
                const successMessage = document.getElementById('successMessage');
                successMessage.textContent = `${data.ppt.slides_count || 'Beberapa'} slide telah dibuat`;
                
                const downloadBtn = document.getElementById('downloadBtn');
                downloadBtn.href = data.download_url;
                
                successModal.show();
                
                // Reset form
                form.reset();
                selectedLaporanId = null;
                document.querySelectorAll('.laporan-item').forEach(item => {
                    item.classList.remove('selected');
                });
                document.getElementById('selectedPreview').style.display = 'none';
                updateCharCount();
            } else {
                alert('Gagal generate PPT: ' + data.message);
            }
        } catch (error) {
            clearInterval(progressInterval);
            loadingModal.hide();
            alert('Terjadi kesalahan: ' + error.message);
        } finally {
            generateBtn.querySelector('.btn-content').style.display = 'inline-block';
            generateBtn.querySelector('.btn-loading').style.display = 'none';
            generateBtn.disabled = false;
        }
    });
});
</script>
@endsection
