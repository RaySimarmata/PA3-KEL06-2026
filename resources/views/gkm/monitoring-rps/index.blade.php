@extends('layouts.app')

@section('page-title', 'Monitoring RPS & Materi')

@section('styles')
<style>
    .stats-card {
        border-left: 4px solid;
        transition: transform 0.2s;
    }
    .stats-card:hover {
        transform: translateY(-2px);
    }
    .stats-card.success {
        border-left-color: #28a745;
    }
    .stats-card.warning {
        border-left-color: #ffc107;
    }
    .stats-card.danger {
        border-left-color: #dc3545;
    }
    .filter-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .filter-section .form-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-size: 0.875rem;
        font-weight: 500;
    }
    .table-custom thead th {
        background-color: #f8f9fa;
        color: var(--primary-color);
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
        padding: 1rem;
    }
    .table-custom tbody td {
        padding: 1rem;
        vertical-align: middle;
    }
    .dosen-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    .dosen-name {
        font-weight: 600;
        color: #333;
    }
    .dosen-email {
        font-size: 0.875rem;
        color: #6c757d;
    }
    .mk-badge {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        margin: 0.15rem;
        background: #e9ecef;
        border-radius: 4px;
        font-size: 0.875rem;
        color: #495057;
    }
    .btn-primary {
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(30, 60, 114, 0.3);
    }
</style>
@endsection

@section('content')
<div style="padding: 1.5rem;">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">MONITORING MATERI & RPS</h4>
            <p class="text-muted mb-0">
                <i class="bi bi-calendar3"></i> Periode: {{ date('F Y') }} - Minggu ke-4
            </p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card stats-card success border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-success">0</h3>
                            <small class="text-muted">Sudah Upload Lengkap</small>
                        </div>
                        <div class="text-success" style="font-size: 2rem;">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stats-card warning border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-warning">{{ $dosenList->total() }}</h3>
                            <small class="text-muted">Sedang Proses</small>
                        </div>
                        <div class="text-warning" style="font-size: 2rem;">
                            <i class="bi bi-clock-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stats-card danger border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-danger">0</h3>
                            <small class="text-muted">Belum Upload</small>
                        </div>
                        <div class="text-danger" style="font-size: 2rem;">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Action Section -->
    <div class="filter-section">
        <div class="row g-3">
            <div class="col-md-7">
                <label class="form-label small text-muted mb-2">
                    <i class="bi bi-funnel"></i> Filter Dosen
                </label>
                <form method="GET">
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <select name="dosen_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Cari dosen atau mata kuliah...</option>
                            @foreach($dosenList as $dos)
                                <option value="{{ $dos->id }}" @if(request('dosen_id') == $dos->id) selected @endif>
                                    {{ $dos->nama_lengkap }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="col-md-5">
                <label class="form-label small text-muted mb-2">
                    <i class="bi bi-send"></i> Aksi Cepat
                </label>
                <a href="{{ route('gkm.monitoring-rps.ceklist') }}" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2" style="padding: 0.6rem 1rem;">
                    <i class="bi bi-send-fill"></i>
                    <span>Kirim Reminder ke yang Belum Upload</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Table Dosen per Mata Kuliah -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-custom table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 25%;">Dosen</th>
                            <th style="width: 30%;">Mata Kuliah</th>
                            <th style="width: 20%;">Status Upload Materi</th>
                            <th style="width: 20%;">Status RPS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dosenList as $i => $dos)
                            <tr>
                                <td class="text-center">{{ $dosenList->firstItem() + $i }}</td>
                                <td>
                                    <div class="dosen-info">
                                        <span class="dosen-name">{{ $dos->nama_lengkap }}</span>
                                        <span class="dosen-email">
                                            <i class="bi bi-envelope"></i> {{ $dos->kontak_email }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    @if($dos->matakuliah && $dos->matakuliah->count() > 0)
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($dos->matakuliah->take(3) as $mk)
                                                <span class="mk-badge">{{ $mk->nama_mk }}</span>
                                            @endforeach
                                            @if($dos->matakuliah->count() > 3)
                                                <span class="mk-badge" style="background: #dee2e6; font-weight: 600;">
                                                    +{{ $dos->matakuliah->count() - 3 }} lainnya
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">
                                            <i class="bi bi-dash-circle"></i> Belum ada mata kuliah
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-warning status-badge">
                                        <i class="bi bi-clock"></i> Sedang Proses
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-warning status-badge">
                                        <i class="bi bi-clock"></i> Belum Upload
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                        <p class="mt-2 mb-0">Tidak ada data dosen</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    @if($dosenList->hasPages())
    <div class="mt-4 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            Menampilkan {{ $dosenList->firstItem() }} - {{ $dosenList->lastItem() }} dari {{ $dosenList->total() }} dosen
        </div>
        <nav>
            {{ $dosenList->links() }}
        </nav>
    </div>
    @endif

    <!-- Legend -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <h6 class="mb-3">
                <i class="bi bi-info-circle"></i> Keterangan Status
            </h6>
            <div class="d-flex flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success status-badge">
                        <i class="bi bi-check-circle"></i> Lengkap
                    </span>
                    <small class="text-muted">Sudah upload semua</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-warning status-badge">
                        <i class="bi bi-clock"></i> Sedang Proses
                    </span>
                    <small class="text-muted">Dalam proses upload</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-danger status-badge">
                        <i class="bi bi-x-circle"></i> Belum Upload
                    </span>
                    <small class="text-muted">Belum ada upload</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
