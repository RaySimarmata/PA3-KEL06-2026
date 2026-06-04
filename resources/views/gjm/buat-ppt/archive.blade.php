@extends('layouts.app')

@section('page-title', 'Arsip PPT')

@section('content')
<div style="padding: 1.5rem;">
    <!-- Header Card -->
    <div class="filter-card mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="d-flex align-items-start gap-3">
                <div>
                    <h5 class="mb-1" style="font-weight: 600; color: #333;">Arsip PPT</h5>
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">Kelola dan unduh semua presentasi yang telah dibuat</p>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <div class="position-relative" style="width: 300px;">
                    <input type="text" class="form-control" placeholder="Cari PPT..." 
                           value="{{ $search ?? '' }}" id="searchInput">
                    <i class="bi bi-search position-absolute" 
                       style="right: 12px; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
                </div>
                <a href="{{ route('gjm.buat-ppt.index') }}" class="btn-reminder">
                    <i class="bi bi-plus-circle"></i>
                    <span>Buat PPT</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="monitoring-card">
        <div class="monitoring-header">
            <h6>Pratinjau Hasil</h6>
            <div style="margin-left: auto;" class="d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm active" id="gridView">
                    <i class="bi bi-grid"></i> Grid
                </button>
                <button class="btn btn-outline-primary btn-sm" id="listView">
                    <i class="bi bi-list"></i> List
                </button>
            </div>
        </div>

        <div style="padding: 1.5rem;">
            <div class="row" id="pptGrid">
                @forelse($pptArchive as $index => $item)
                <div class="col-md-4 mb-4 ppt-col">
                    <div class="content-card h-100" style="transition: all 0.3s ease;">
                        <div style="height: 200px; background: linear-gradient(135deg, {{ ['#667eea', '#ff9a56', '#56ab2f', '#ffecd2', '#4ecdc4'][$index % 5] }} 0%, {{ ['#764ba2', '#ff6b35', '#a8e6cf', '#fcb69f', '#44a08d'][$index % 5] }} 100%); border-radius: 8px 8px 0 0; position: relative; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                            <div style="text-align: center; color: white; padding: 20px; z-index: 1;">
                                <div style="font-size: 18px; font-weight: 700; margin-bottom: 8px; line-height: 1.3;">
                                    {{ $item->ringkasan_mutu_institusi ?? 'Laporan ' . $item->getJenisLaporanLabel() }}
                                </div>
                                <div style="font-size: 12px; opacity: 0.9;">
                                    {{ $item->getJenisLaporanLabel() }} {{ $item->ajaran ? $item->ajaran->tahun_ajaran : date('Y') }}
                                </div>
                            </div>
                            <div style="position: absolute; right: -20px; top: 50%; transform: translateY(-50%) rotate(15deg); width: 80px; height: 200px; background: rgba(255, 255, 255, 0.2); border-radius: 8px;"></div>
                        </div>
                        
                        <div style="padding: 1rem;">
                            <div class="code-mk mb-2">
                                {{ Str::limit($item->ringkasan_mutu_institusi ?? 'Laporan ' . $item->getJenisLaporanLabel(), 50) }}
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="text-secondary" style="font-size: 0.8rem;">
                                    <i class="bi bi-clock"></i>
                                    {{ $item->created_at ? $item->created_at->diffForHumans() : '-' }}
                                </div>
                                <span class="badge-gjm info">
                                    {{ $item->getJenisLaporanLabel() }}
                                </span>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary btn-sm flex-grow-1" onclick="previewPPT({{ $item->id }})">
                                    <i class="bi bi-eye"></i> Preview
                                </button>
                                <button class="btn btn-primary btn-sm flex-grow-1" onclick="downloadPPT({{ $item->id }})">
                                    <i class="bi bi-download"></i> Download
                                </button>
                                <button class="btn btn-outline-danger btn-sm" onclick="deletePPT({{ $item->id }}, '{{ addslashes($item->ringkasan_mutu_institusi) }}')" title="Hapus PPT">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="empty-state">
                        <i class="bi bi-file-ppt"></i>
                        <p>Belum ada PPT yang dibuat</p>
                        <small class="text-muted">Klik "Buat PPT" untuk membuat presentasi pertama</small>
                    </div>
                </div>
                @endforelse
            </div>
        </div>

        @if($pptArchive->hasPages() || $pptArchive->total() > 0)
        <div style="padding: 1.25rem 1.5rem; border-top: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items-center;">
            <div class="text-muted" style="font-size: 0.875rem;">
                Menampilkan {{ $pptArchive->count() }} dari {{ $pptArchive->total() }} PPT
            </div>
            <div>
                {{ $pptArchive->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

<style>
.content-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.btn-outline-danger:hover {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}

.btn-outline-danger {
    border-color: #dc3545;
    color: #dc3545;
}

.btn-outline-danger:focus {
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
</style>
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
    const gridView = document.getElementById('gridView');
    const listView = document.getElementById('listView');
    const pptGrid = document.getElementById('pptGrid');

    gridView.addEventListener('click', function() {
        gridView.classList.add('active');
        listView.classList.remove('active');
        pptGrid.querySelectorAll('.ppt-col').forEach(col => {
            col.className = 'col-md-4 mb-4 ppt-col';
        });
    });

    listView.addEventListener('click', function() {
        listView.classList.add('active');
        gridView.classList.remove('active');
        pptGrid.querySelectorAll('.ppt-col').forEach(col => {
            col.className = 'col-12 mb-3 ppt-col';
        });
    });
});

function previewPPT(id) {
    alert('Preview PPT dengan ID: ' + id);
}

function downloadPPT(id) {
    // Create a temporary link and trigger download
    const downloadUrl = `/gjm/buat-ppt/${id}/download`;
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function deletePPT(id, title) {
    // Show confirmation dialog
    if (!confirm(`Apakah Anda yakin ingin menghapus PPT "${title}"?\n\nTindakan ini tidak dapat dibatalkan.`)) {
        return;
    }

    // Show loading state
    const deleteBtn = event.target.closest('button');
    const originalContent = deleteBtn.innerHTML;
    deleteBtn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
    deleteBtn.disabled = true;

    // Send delete request
    fetch(`/gjm/buat-ppt/${id}/delete`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            alert('PPT berhasil dihapus!');
            
            // Remove the card from view or reload page
            location.reload();
        } else {
            // Show error message
            alert('Gagal menghapus PPT: ' + (data.message || 'Unknown error'));
            
            // Restore button
            deleteBtn.innerHTML = originalContent;
            deleteBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menghapus PPT');
        
        // Restore button
        deleteBtn.innerHTML = originalContent;
        deleteBtn.disabled = false;
    });
}
</script>
@endsection
