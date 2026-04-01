@extends('layouts.app')

@section('page-title', 'Arsip PPT')

@section('styles')
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .arsip-ppt-container {
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

    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 30px;
    }

    .header-left {
        flex: 1;
    }

    .header-left h4 {
        font-size: 28px;
        font-weight: 700;
        color: #212529;
        margin-bottom: 8px;
    }

    .header-left p {
        margin: 0;
        font-size: 16px;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-shrink: 0;
    }

    .search-box {
        position: relative;
        width: 300px;
    }

    .search-box input {
        width: 100%;
        padding: 12px 45px 12px 16px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .search-box input:focus {
        border-color: #1e3c72;
        outline: none;
    }

    .search-box i {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #495057;
    }

    .btn-buat-ppt {
        padding: 12px 24px;
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .btn-buat-ppt:hover {
        color: white;
        transform: translateY(-2px);
        opacity: 0.9;
    }

    .main-content {
        background: white;
        border-radius: 0.375rem;
        border: 1px solid #e0e0e0;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        overflow: hidden;
        transition: box-shadow 0.15s ease-in-out;
    }

    .main-content:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .content-header {
        padding: 24px 30px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .content-header h5 {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
    }

    .view-toggle {
        display: flex;
        gap: 8px;
    }

    .toggle-btn {
        padding: 8px 12px;
        border: 1px solid #e9ecef;
        background: white;
        color: #495057;
        border-radius: 6px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .toggle-btn.active {
        background: #1e3c72;
        color: white;
        border-color: #1e3c72;
    }

    .ppt-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        padding: 30px;
        max-width: 1000px;
        margin: 0 auto;
    }

    .ppt-card {
        background: white;
        border-radius: 0.375rem;
        border: 1px solid #e0e0e0;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        overflow: hidden;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .ppt-card:hover {
        transform: translateY(-4px);
        border-color: #1e3c72;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .ppt-preview {
        height: 200px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .ppt-preview.orange {
        background: linear-gradient(135deg, #ff9a56 0%, #ff6b35 100%);
    }

    .ppt-preview.green {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
    }

    .ppt-preview.pink {
        background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
    }

    .ppt-preview.blue {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .ppt-preview.teal {
        background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%);
    }

    .preview-content {
        text-align: center;
        color: white;
        padding: 20px;
    }

    .preview-title {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 8px;
        line-height: 1.3;
    }

    .preview-subtitle {
        font-size: 12px;
        opacity: 0.9;
        font-weight: 400;
    }

    .preview-decoration {
        position: absolute;
        right: -20px;
        top: 50%;
        transform: translateY(-50%) rotate(15deg);
        width: 80px;
        height: 200px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 8px;
    }

    .ppt-info {
        padding: 20px;
    }

    .ppt-title {
        font-size: 16px;
        font-weight: 600;
        color: #212529;
        margin-bottom: 8px;
        line-height: 1.4;
    }

    .ppt-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .ppt-date {
        font-size: 12px;
        color: #495057;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .ppt-type {
        padding: 4px 8px;
        background: #e3f2fd;
        color: #1976d2;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .ppt-actions {
        display: flex;
        gap: 8px;
    }

    .btn-action {
        flex: 1;
        padding: 8px 16px;
        border: 1px solid #e9ecef;
        background: white;
        color: #495057;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }

    .btn-action:hover {
        border-color: #1e3c72;
        color: #1e3c72;
        text-decoration: none;
    }

    .btn-action.primary {
        background: #1e3c72;
        color: white;
        border-color: #1e3c72;
    }

    .btn-action.primary:hover {
        background: #2a5298;
        color: white;
    }

    .empty-state {
        text-align: center;
        padding: 80px 20px;
    }

    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .pagination-container {
        padding: 20px 30px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: white;
    }

    .pagination-info {
        font-size: 14px;
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
    }

    .page-btn:hover:not(.disabled):not(.active) {
        border-color: #d1d5db;
        background: #f9fafb;
        color: #374151;
        text-decoration: none;
    }

    .page-btn.active {
        background: #1e3a8a;
        color: white;
        border-color: #1e3a8a;
    }

    .page-btn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }

    @media (max-width: 768px) {
        .arsip-ppt-container {
            padding: 20px;
        }
        
        .header-content {
            flex-direction: column;
            align-items: stretch;
            gap: 20px;
        }
        
        .header-right {
            flex-direction: column;
            align-items: stretch;
            gap: 15px;
        }
        
        .search-box {
            width: 100%;
        }
        
        .ppt-grid {
            grid-template-columns: 1fr;
            padding: 20px;
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
</style>
@endsection

@section('content')
<div class="arsip-ppt-container">
    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <div class="header-left">
                <h4>Hasil Pratinjau PPT Berhasil</h4>
                <p class="text-muted">Kelola dan unduh semua presentasi yang telah dibuat</p>
            </div>
            <div class="header-right">
                <div class="search-box">
                    <input type="text" placeholder="Cari PPT..." value="{{ $search ?? '' }}" id="searchInput">
                    <i class="bi bi-search"></i>
                </div>
                <a href="{{ route('gjm.buat-ppt.index') }}" class="btn-buat-ppt">
                    <i class="bi bi-plus-circle"></i>
                    Buat PPT
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-header">
            <h5>Pratinjau Hasil</h5>
            <div class="view-toggle">
                <button class="toggle-btn active">
                    <i class="bi bi-grid"></i>
                    Grid
                </button>
                <button class="toggle-btn">
                    <i class="bi bi-list"></i>
                    List
                </button>
            </div>
        </div>

        <div class="ppt-grid">
            @forelse($pptArchive as $index => $item)
            <div class="ppt-card">
                <div class="ppt-preview {{ ['orange', 'green', 'pink', 'blue', 'teal'][$index % 5] }}">
                    <div class="preview-content">
                        <div class="preview-title">
                            {{ $item->ringkasan_mutu_institusi ?? 'Laporan ' . $item->getJenisLaporanLabel() }}
                        </div>
                        <div class="preview-subtitle">
                            {{ $item->getJenisLaporanLabel() }} {{ $item->ajaran ? $item->ajaran->tahun_ajaran : date('Y') }}
                        </div>
                    </div>
                    <div class="preview-decoration"></div>
                </div>
                
                <div class="ppt-info">
                    <div class="ppt-title">
                        {{ Str::limit($item->ringkasan_mutu_institusi ?? 'Laporan ' . $item->getJenisLaporanLabel(), 50) }}
                    </div>
                    
                    <div class="ppt-meta">
                        <div class="ppt-date">
                            <i class="bi bi-clock"></i>
                            {{ $item->created_at ? $item->created_at->diffForHumans() : '-' }}
                        </div>
                        <div class="ppt-type">
                            {{ $item->getJenisLaporanLabel() }}
                        </div>
                    </div>
                    
                    <div class="ppt-actions">
                        <a href="#" class="btn-action" onclick="previewPPT({{ $item->id }})">
                            <i class="bi bi-eye"></i>
                            Preview
                        </a>
                        <a href="#" class="btn-action primary" onclick="downloadPPT({{ $item->id }})">
                            <i class="bi bi-download"></i>
                            Download
                        </a>
                    </div>
                </div>
            </div>
            @empty
            <div class="empty-state" style="grid-column: 1 / -1;">
                <i class="bi bi-file-ppt"></i>
                <p class="mb-0">Belum ada PPT yang dibuat</p>
                <small class="text-muted">Klik "Buat PPT" untuk membuat presentasi pertama</small>
            </div>
            @endforelse
        </div>

        @if($pptArchive->hasPages() || $pptArchive->total() > 0)
        <div class="pagination-container">
            <div class="pagination-info text-muted">
                Menampilkan {{ $pptArchive->count() }} dari {{ $pptArchive->total() }} PPT
            </div>
            <div class="pagination-nav">
                @if($pptArchive->hasPages())
                    @if($pptArchive->onFirstPage())
                        <span class="page-btn disabled">‹</span>
                    @else
                        <a href="{{ $pptArchive->previousPageUrl() }}" class="page-btn">‹</a>
                    @endif

                    @for($page = 1; $page <= $pptArchive->lastPage(); $page++)
                        @if($page == $pptArchive->currentPage())
                            <span class="page-btn active">{{ $page }}</span>
                        @else
                            <a href="{{ $pptArchive->url($page) }}" class="page-btn">{{ $page }}</a>
                        @endif
                    @endfor

                    @if($pptArchive->hasMorePages())
                        <a href="{{ $pptArchive->nextPageUrl() }}" class="page-btn">›</a>
                    @else
                        <span class="page-btn disabled">›</span>
                    @endif
                @else
                    <span class="page-btn active">1</span>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Summary Sidebar (Optional - can be added later) -->
<div class="summary-sidebar" style="display: none;">
    <div class="summary-content">
        <h6>Summary</h6>
        <div class="summary-item">
            <span>Nama File:</span>
            <span>Laporan_Bulanan_GJM.pptx</span>
        </div>
        <div class="summary-item">
            <span>Jumlah Slide:</span>
            <span>15</span>
        </div>
        <div class="summary-item">
            <span>Template:</span>
            <span>Minimalist White</span>
        </div>
        <button class="btn btn-primary btn-block">
            <i class="bi bi-download"></i>
            Download PPT
        </button>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const searchValue = this.value;
            const url = new URL(window.location);
            
            if (searchValue) {
                url.searchParams.set('search', searchValue);
            } else {
                url.searchParams.delete('search');
            }
            
            window.location.href = url.toString();
        }, 500);
    });

    // View toggle functionality
    const toggleBtns = document.querySelectorAll('.toggle-btn');
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            toggleBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Toggle between grid and list view
            const pptGrid = document.querySelector('.ppt-grid');
            if (this.textContent.trim().includes('List')) {
                pptGrid.style.gridTemplateColumns = '1fr';
                // Add list view styling here
            } else {
                pptGrid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(300px, 1fr))';
            }
        });
    });
});

function previewPPT(id) {
    // Implement PPT preview functionality
    alert('Preview PPT dengan ID: ' + id);
}

function downloadPPT(id) {
    // Implement PPT download functionality
    fetch(`/gjm/buat-ppt/download/${id}`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Download PPT akan segera dimulai');
        } else {
            alert('Gagal download PPT: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat download PPT');
    });
}
</script>
@endsection