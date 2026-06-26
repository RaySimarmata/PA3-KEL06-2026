@extends('layouts.app')

@section('page-title', 'Arsip Laporan')

@section('content')
<div style="padding: 1.5rem;">
    @if(session('success'))
    <div class="alert-gjm success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert-gjm danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Header Card -->
    <div class="filter-card mb-4">
        <div class="d-flex align-items-start gap-3">
            <i class="bi bi-archive" style="color: #5B9BD5; font-size: 2rem;"></i>
            <div>
                <h5 class="mb-1" style="font-weight: 600; color: #333;">Daftar Arsip Laporan</h5>
                <p class="text-muted mb-0" style="font-size: 0.875rem;">Kelola dan pantau semua data arsip laporan strategis secara real-time</p>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="monitoring-card" style="border-left: 4px solid #5B9BD5;">
                <div class="d-flex align-items-center justify-content-between p-3">
                    <div>
                        <i class="bi bi-file-earmark-text" style="color: #5B9BD5; font-size: 2rem;"></i>
                    </div>
                    <div class="text-end">
                        <h2 class="mb-0" style="font-weight: 700; color: #333;">{{ $totalLaporan }}</h2>
                        <p class="text-muted mb-0" style="font-size: 0.875rem;">TOTAL LAPORAN</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="monitoring-card" style="border-left: 4px solid #28a745;">
                <div class="d-flex align-items-center justify-content-between p-3">
                    <div>
                        <i class="bi bi-calendar-range" style="color: #28a745; font-size: 2rem;"></i>
                    </div>
                    <div class="text-end">
                        <h2 class="mb-0" style="font-weight: 700; color: #333;">{{ $laporanTriwulan }}</h2>
                        <p class="text-muted mb-0" style="font-size: 0.875rem;">LAPORAN TRIWULAN</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="monitoring-card" style="border-left: 4px solid #ffc107;">
                <div class="d-flex align-items-center justify-content-between p-3">
                    <div>
                        <i class="bi bi-calendar-month" style="color: #ffc107; font-size: 2rem;"></i>
                    </div>
                    <div class="text-end">
                        <h2 class="mb-0" style="font-weight: 700; color: #333;">{{ $laporanSemester }}</h2>
                        <p class="text-muted mb-0" style="font-size: 0.875rem;">LAPORAN SEMESTER</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card mb-4">
        <form method="GET" action="{{ route('gjm.laporan-gjm.index') }}" id="filterForm">
            <div class="row align-items-end">
                <div class="col-md-3 mb-3">
                    <label class="filter-label">Cari laporan...</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Cari laporan..." 
                               value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="filter-label">Jenis Laporan</label>
                    <select class="form-select" name="jenis_laporan">
                        <option value="">Semua</option>
                        <option value="triwulan" {{ request('jenis_laporan') == 'triwulan' ? 'selected' : '' }}>Triwulan</option>
                        <option value="semester" {{ request('jenis_laporan') == 'semester' ? 'selected' : '' }}>Semester</option>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="filter-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">Semua</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="filter-label">Tahun</label>
                    <select class="form-select" name="tahun">
                        <option value="">Semua</option>
                        @foreach($tahunList as $tahun)
                        <option value="{{ $tahun }}" {{ request('tahun') == $tahun ? 'selected' : '' }}>{{ $tahun }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                        <a href="{{ route('gjm.laporan-gjm.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Activity Log -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-clock-history" style="color: #5B9BD5;"></i>
            <span style="font-weight: 600; color: #333;">Log Aktivitas Terbaru</span>
        </div>
    </div>

    <!-- Table Card -->
    <div class="monitoring-card">
        <div class="table-responsive">
            <table class="table table-monitoring mb-0">
                <thead>
                    <tr>
                        <th>JUDUL LAPORAN</th>
                        <th>TANGGAL DIBUAT</th>
                        <th>TIPE LAPORAN</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($laporanList as $laporan)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-text" style="color: #5B9BD5; font-size: 1.25rem;"></i>
                                <div>
                                    <div class="code-mk">Laporan {{ $laporan->getPeriodeLabel() }}</div>
                                    <small class="text-muted">{{ $laporan->program_studi ?? 'Fakultas Vokasi' }}</small>
                                </div>
                            </div>
                        </td>
                        <td class="text-secondary">{{ $laporan->created_at->format('d M Y') }}</td>
                        <td>
                            <span class="badge-gjm {{ $laporan->jenis_laporan == 'triwulan' ? 'info' : 'warning' }}">
                                {{ $laporan->getJenisLaporanLabel() }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if($laporan->status_laporan == 'draft')
                                <span class="badge-gjm warning">Draft</span>
                            @elseif($laporan->status_laporan == 'processing')
                                <span class="badge-gjm info">Processing</span>
                            @elseif($laporan->status_laporan == 'completed')
                                <span class="badge-gjm success">Selesai</span>
                            @elseif($laporan->status_laporan == 'approved')
                                <span class="badge-gjm success">Approved</span>
                            @else
                                <span class="badge-gjm secondary">{{ ucfirst($laporan->status_laporan) }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="lihatDetail({{ $laporan->id }})"
                                        title="Lihat Detail">
                                    <i class="bi bi-eye"></i> LIHAT DETAIL
                                </button>
                                @if($laporan->file_laporan || $laporan->dokumen_path)
                                <a href="{{ route('gjm.laporan-gjm.download', $laporan->id) }}" 
                                   class="btn btn-sm btn-outline-success"
                                   title="Download">
                                    <i class="bi bi-download"></i>
                                </a>
                                @endif
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="hapusLaporan({{ $laporan->id }})"
                                        title="Hapus">
                                    <i class="bi bi-trash"></i> HAPUS
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <p>Belum ada laporan yang tersedia</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($laporanList->hasPages())
        <div class="d-flex justify-content-between align-items-center p-3 border-top">
            <div class="text-muted" style="font-size: 0.875rem;">
                Menampilkan {{ $laporanList->firstItem() }} dari {{ $laporanList->lastItem() }} data
            </div>
            <div>
                {{ $laporanList->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: #f8f9fa; border-bottom: 2px solid #5B9BD5;">
                <h5 class="modal-title" style="color: #333; font-weight: 600;">
                    <i class="bi bi-file-earmark-text" style="color: #5B9BD5;"></i> Detail Laporan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;" id="detailContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background: #f8f9fa;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function lihatDetail(id) {
    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    modal.show();
    
    fetch(`/gjm/laporan-gjm/${id}/detail`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const laporan = data.laporan;
                let html = `
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="filter-label">Periode</label>
                            <p class="mb-0" style="color: #495057; font-weight: 600;">${laporan.periode_label}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="filter-label">Jenis Laporan</label>
                            <p class="mb-0" style="color: #495057;">${laporan.jenis_laporan_label}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="filter-label">Program Studi</label>
                            <p class="mb-0" style="color: #495057;">${laporan.program_studi || '-'}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="filter-label">Status</label>
                            <p class="mb-0">
                                <span class="badge-gjm ${laporan.status_badge}">${laporan.status_label}</span>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="filter-label">Tanggal Dibuat</label>
                            <p class="mb-0" style="color: #495057;">${laporan.created_at}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="filter-label">Dibuat Oleh</label>
                            <p class="mb-0" style="color: #495057;">${laporan.created_by || '-'}</p>
                        </div>
                `;
                
                if (laporan.ringkasan_mutu_institusi) {
                    html += `
                        <div class="col-12 mb-3">
                            <label class="filter-label">Ringkasan Mutu Institusi</label>
                            <div class="p-3 bg-light rounded border">
                                <p class="mb-0" style="white-space: pre-wrap;">${laporan.ringkasan_mutu_institusi}</p>
                            </div>
                        </div>
                    `;
                }
                
                if (laporan.temuan_utama) {
                    html += `
                        <div class="col-12 mb-3">
                            <label class="filter-label">Temuan Utama</label>
                            <div class="p-3 bg-light rounded border">
                                <p class="mb-0" style="white-space: pre-wrap;">${laporan.temuan_utama}</p>
                            </div>
                        </div>
                    `;
                }
                
                if (laporan.rekomendasi_perbaikan) {
                    html += `
                        <div class="col-12 mb-3">
                            <label class="filter-label">Rekomendasi Perbaikan</label>
                            <div class="p-3 bg-light rounded border">
                                <p class="mb-0" style="white-space: pre-wrap;">${laporan.rekomendasi_perbaikan}</p>
                            </div>
                        </div>
                    `;
                }
                
                html += `</div>`;
                
                document.getElementById('detailContent').innerHTML = html;
            } else {
                document.getElementById('detailContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('detailContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> Gagal memuat detail laporan
                </div>
            `;
        });
}

function hapusLaporan(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus laporan ini?')) {
        return;
    }
    
    fetch(`/gjm/laporan-gjm/${id}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Laporan berhasil dihapus');
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Gagal menghapus laporan');
    });
}
</script>
@endsection
