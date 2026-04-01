@extends('layouts.app')

@section('page-title', 'Arsip Laporan')

@section('styles')
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .arsip-container {
        background-color: #f5f7fa;
        min-height: calc(100vh - 80px);
        padding: 30px;
    }

    .header-section {
        background: white;
        border-radius: 0.375rem;
        padding: 30px;
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

    .btn-buat-laporan {
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

    .btn-buat-laporan:hover {
        color: white;
        transform: translateY(-2px);
        opacity: 0.9;
    }

    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border-radius: 0.375rem;
        padding: 25px;
        border: 1px solid #e0e0e0;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        border-color: #1e3c72;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--card-color);
    }

    .stat-card.total {
        --card-color: #3498db;
    }

    .stat-card.triwulan {
        --card-color: #2ecc71;
    }

    .stat-card.semester {
        --card-color: #f39c12;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
        font-size: 24px;
        color: white;
        background: var(--card-color);
    }

    .stat-number {
        font-size: 32px;
        font-weight: 700;
        color: #212529;
        margin-bottom: 5px;
    }

    .stat-label {
        color: #495057;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .stat-change {
        font-size: 12px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .stat-change.positive {
        color: #2ecc71;
    }

    .stat-change.negative {
        color: #e74c3c;
    }

    .main-content-card {
        background: white;
        border-radius: 0.375rem;
        border: 1px solid #e0e0e0;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        overflow: hidden;
        transition: box-shadow 0.15s ease-in-out;
    }

    .main-content-card:hover {
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

    .content-actions {
        display: flex;
        gap: 10px;
    }

    .btn-filter, .btn-export {
        padding: 8px 16px;
        border: 1px solid #e9ecef;
        background: white;
        color: #495057;
        border-radius: 6px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .btn-filter:hover, .btn-export:hover {
        border-color: #1e3c72;
        color: #1e3c72;
    }

    .table-container {
        overflow-x: auto;
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .custom-table th {
        background: #f8f9fa;
        padding: 20px;
        text-align: left;
        font-weight: 600;
        color: #495057;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        border: none;
        border-bottom: 1px solid #e5e7eb;
    }

    .custom-table td {
        padding: 24px 20px;
        border-bottom: 1px solid #f1f3f4;
        vertical-align: middle;
    }

    .custom-table tr:hover {
        background: #f8f9fa;
    }

    .laporan-name {
        font-weight: 600;
        font-size: 15px;
        line-height: 1.4;
    }

    .laporan-date {
        font-size: 14px;
        font-weight: 400;
    }

    .badge-tipe {
        padding: 6px 14px;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 600;
        text-transform: capitalize;
        letter-spacing: 0.3px;
    }

    .badge-triwulan {
        background: #fef3c7;
        color: #d97706;
    }

    .badge-semester {
        background: #dbeafe;
        color: #2563eb;
    }

    .badge-status {
        padding: 6px 14px;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 600;
        text-transform: capitalize;
        letter-spacing: 0.3px;
    }

    .badge-selesai {
        background: #dcfce7;
        color: #16a34a;
    }

    .badge-proses {
        background: #fef3c7;
        color: #d97706;
    }

    .btn-detail {
        padding: 8px 16px;
        background: transparent;
        color: #2563eb;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .btn-detail:hover {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .pagination-container {
        padding: 20px 30px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: white;
        min-height: 60px;
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
        background: #1e3a8a;
        color: white;
        border-color: #1e3a8a;
    }

    .page-btn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }

    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    @media (max-width: 768px) {
        .arsip-container {
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
        
        .stats-cards {
            grid-template-columns: 1fr;
        }
        
        .content-header {
            flex-direction: column;
            align-items: stretch;
            gap: 15px;
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
<div class="arsip-container">
    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <div class="header-left">
                <h4>Daftar Arsip</h4>
                <p class="text-muted">Kelola dan pantau semua data arsip laporan strategis secara real-time.</p>
            </div>
            <div class="header-right">
                <div class="search-box">
                    <input type="text" placeholder="Cari laporan..." value="{{ $search ?? '' }}" id="searchInput">
                    <i class="bi bi-search"></i>
                </div>
                <a href="{{ route('gjm.buat-laporan.index') }}" class="btn-buat-laporan">
                    <i class="bi bi-plus-circle"></i>
                    Buat Laporan
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-cards">
        <div class="stat-card total">
            <div class="stat-icon">
                <i class="bi bi-file-earmark-text"></i>
            </div>
            <div class="stat-number">{{ $laporan->total() }}</div>
            <div class="stat-label">Total Laporan</div>
            <div class="stat-change positive">
                <i class="bi bi-arrow-up"></i>
                +12%
            </div>
        </div>
        
        <div class="stat-card triwulan">
            <div class="stat-icon">
                <i class="bi bi-calendar-range"></i>
            </div>
            <div class="stat-number">{{ $laporan->where('jenis_laporan', 'triwulan')->count() }}</div>
            <div class="stat-label">Laporan Triwulan</div>
            <div class="stat-change positive">
                <i class="bi bi-arrow-up"></i>
                +5%
            </div>
        </div>
        
        <div class="stat-card semester">
            <div class="stat-icon">
                <i class="bi bi-calendar-month"></i>
            </div>
            <div class="stat-number">{{ $laporan->where('jenis_laporan', 'semester')->count() }}</div>
            <div class="stat-label">Laporan Semester</div>
            <div class="stat-change negative">
                <i class="bi bi-arrow-down"></i>
                -2%
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content-card">
        <div class="content-header">
            <h5>Log Aktivitas Terbaru</h5>
            <div class="content-actions">
                <button class="btn-filter">
                    <i class="bi bi-funnel"></i>
                    Filter
                </button>
                <button class="btn-export">
                    <i class="bi bi-download"></i>
                    Export
                </button>
            </div>
        </div>

        <div class="table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>NAMA LAPORAN</th>
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
                            <div class="laporan-name">
                                {{ $item->ringkasan_mutu_institusi ?? 'Laporan ' . $item->getJenisLaporanLabel() }}
                            </div>
                        </td>
                        <td>
                            <div class="laporan-date">
                                {{ $item->created_at ? $item->created_at->format('d M Y') : '-' }}
                            </div>
                        </td>
                        <td>
                            <span class="badge-tipe badge-{{ $item->jenis_laporan }}">
                                {{ $item->getJenisLaporanLabel() }}
                            </span>
                        </td>
                        <td>
                            <span class="badge-status badge-{{ $item->status_laporan == 'draft' ? 'proses' : 'selesai' }}">
                                {{ $item->status_laporan == 'draft' ? 'Proses' : 'Selesai' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('gjm.buat-laporan.show', $item->id) }}" class="btn-detail">
                                LIHAT DETAIL
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="bi bi-file-earmark-text"></i>
                                <p class="mb-0">Belum ada laporan yang dibuat</p>
                                <small class="text-muted">Klik "Buat Laporan" untuk membuat laporan pertama</small>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($laporan->hasPages() || $laporan->total() > 0)
        <div class="pagination-container">
            <div class="pagination-info text-muted">
                Menampilkan {{ $laporan->count() }} dari {{ $laporan->total() }} data
            </div>
            <div class="pagination-nav">
                @if($laporan->hasPages())
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
                @else
                    <span class="page-btn active">1</span>
                @endif
            </div>
        </div>
        @endif
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

    // Filter and Export buttons
    document.querySelector('.btn-filter').addEventListener('click', function() {
        alert('Filter functionality akan segera tersedia');
    });

    document.querySelector('.btn-export').addEventListener('click', function() {
        alert('Export functionality akan segera tersedia');
    });
});
</script>
@endsection
