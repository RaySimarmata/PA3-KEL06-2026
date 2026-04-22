@extends('layouts.app')

@section('page-title', 'Arsip Laporan')

@section('content')
<div style="padding: 1.5rem;">
    <!-- Header Card -->
    <div class="filter-card mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-archive" style="color: #5B9BD5; font-size: 2rem;"></i>
                <div>
                    <h5 class="mb-1" style="font-weight: 600; color: #333;">Daftar Arsip Laporan</h5>
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">Kelola dan pantau semua data arsip laporan strategis secara real-time</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <div class="position-relative" style="width: 300px;">
                    <input type="text" class="form-control" placeholder="Cari laporan..." 
                           value="{{ $search ?? '' }}" id="searchInput">
                    <i class="bi bi-search position-absolute" 
                       style="right: 12px; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
                </div>
                <a href="{{ route('gjm.buat-laporan.semester') }}" class="btn-reminder">
                    <i class="bi bi-plus-circle"></i>
                    <span>Buat Laporan</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stats-card" style="border-left: 4px solid #3498db;">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-file-earmark-text" style="font-size: 2.5rem; color: #3498db;"></i>
                    <div>
                        <div class="stats-value" style="color: #3498db;">{{ $laporan->total() }}</div>
                        <div class="stats-label">Total Laporan</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stats-card" style="border-left: 4px solid #2ecc71;">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-calendar-range" style="font-size: 2.5rem; color: #2ecc71;"></i>
                    <div>
                        <div class="stats-value" style="color: #2ecc71;">{{ $laporan->where('jenis_laporan', 'triwulan')->count() }}</div>
                        <div class="stats-label">Laporan Triwulan</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stats-card" style="border-left: 4px solid #f39c12;">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-calendar-month" style="font-size: 2.5rem; color: #f39c12;"></i>
                    <div>
                        <div class="stats-value" style="color: #f39c12;">{{ $laporan->where('jenis_laporan', 'semester')->count() }}</div>
                        <div class="stats-label">Laporan Semester</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="monitoring-card">
        <div class="monitoring-header">
            <i class="bi bi-clock-history"></i>
            <h6>Log Aktivitas Terbaru</h6>
        </div>

        <div class="table-responsive">
            <table class="table table-monitoring">
                <thead>
                    <tr>
                        <th>JUDUL LAPORAN</th>
                        <th>TANGGAL DIBUAT</th>
                        <th>TIPE LAPORAN</th>
                        <th>STATUS</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($laporan as $item)
                    <tr>
                        <td>
                            <div class="code-mk">
                                {{ $item->ringkasan_mutu_institusi ?? 'Laporan ' . $item->getJenisLaporanLabel() }}
                            </div>
                        </td>
                        <td>
                            <div class="text-secondary">
                                {{ $item->created_at ? $item->created_at->format('d M Y') : '-' }}
                            </div>
                        </td>
                        <td>
                            <span class="badge-gjm {{ $item->jenis_laporan == 'triwulan' ? 'info' : 'primary' }}">
                                {{ $item->getJenisLaporanLabel() }}
                            </span>
                        </td>
                        <td>
                            <span class="badge-gjm {{ $item->status_laporan == 'draft' ? 'warning' : 'success' }}">
                                {{ $item->status_laporan == 'draft' ? 'Draft' : 'Selesai' }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('gjm.buat-laporan.show', $item->id) }}" 
                                   class="btn btn-outline-primary btn-sm">
                                    LIHAT DETAIL
                                </a>
                                <form action="{{ route('gjm.laporan-gjm.destroy', $item->id) }}" 
                                      method="POST" 
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus laporan ini?');"
                                      style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-trash"></i> HAPUS
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="bi bi-file-earmark-text"></i>
                                <p>Belum ada laporan yang dibuat</p>
                                <small class="text-muted">Klik "Buat Laporan" untuk membuat laporan pertama</small>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($laporan->hasPages() || $laporan->total() > 0)
        <div style="padding: 1.25rem 1.5rem; border-top: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center;">
            <div class="text-muted" style="font-size: 0.875rem;">
                Menampilkan {{ $laporan->count() }} dari {{ $laporan->total() }} data
            </div>
            <div>
                {{ $laporan->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

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
});
</script>
@endsection
