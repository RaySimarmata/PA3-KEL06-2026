@extends('layouts.app')

@section('page-title', 'Log Pengiriman Email')

@section('styles')
<style>
    .pagination {
        margin: 0;
    }
    .pagination .page-link {
        color: var(--primary-color);
        border: 1px solid #dee2e6;
        padding: 0.5rem 0.75rem;
        margin: 0 2px;
        border-radius: 0.25rem;
    }
    .pagination .page-item.active .page-link {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        color: white;
    }
    .pagination .page-link:hover {
        background-color: #f8f9fa;
        color: var(--primary-color);
    }
    .pagination .page-item.disabled .page-link {
        color: #6c757d;
        background-color: #fff;
        border-color: #dee2e6;
    }
    
    /* Modal Styling */
    .modal-header {
        border-bottom: 2px solid var(--primary-color);
    }
    .modal-title {
        color: var(--primary-color);
        font-weight: 600;
    }
    .modal-body label {
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
        display: block;
    }
    .modal-body p {
        font-size: 0.95rem;
        color: #333;
    }
</style>
@endsection

@section('content')
<div style="padding: 1.5rem;">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-envelope-check"></i> Log Pengiriman Email Reminder
                    </h5>
                    <a href="{{ route('gkm.reminder-agent.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card border-0 bg-success bg-opacity-10">
                                <div class="card-body text-center">
                                    <h3 class="text-success mb-0">{{ $totalSuccess }}</h3>
                                    <small class="text-muted">Email Terkirim</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 bg-danger bg-opacity-10">
                                <div class="card-body text-center">
                                    <h3 class="text-danger mb-0">{{ $totalFailed }}</h3>
                                    <small class="text-muted">Email Gagal</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 bg-warning bg-opacity-10">
                                <div class="card-body text-center">
                                    <h3 class="text-warning mb-0">{{ $totalPending }}</h3>
                                    <small class="text-muted">Email Pending</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;">No</th>
                                    <th style="width: 15%;">Tanggal</th>
                                    <th style="width: 25%;">Subjek</th>
                                    <th style="width: 20%;">Penerima</th>
                                    <th style="width: 10%;">Status</th>
                                    <th style="width: 15%;">Keterangan</th>
                                    <th style="width: 10%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logEmailList as $index => $log)
                                <tr>
                                    <td>{{ $logEmailList->firstItem() + $index }}</td>
                                    <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <small>{{ Str::limit($log->subjek, 40) }}</small>
                                    </td>
                                    <td>{{ $log->penerima_email }}</td>
                                    <td>
                                        @if($log->status_pengiriman == 'success')
                                            <span class="badge bg-success">Terkirim</span>
                                        @elseif($log->status_pengiriman == 'failed')
                                            <span class="badge bg-danger">Gagal</span>
                                        @else
                                            <span class="badge bg-warning">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->pesan_error)
                                            <small class="text-danger" title="{{ $log->pesan_error }}">
                                                {{ Str::limit($log->pesan_error, 30) }}
                                            </small>
                                        @else
                                            <small class="text-success">Email berhasil dikirim</small>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#detailModal{{ $log->id }}">
                                            <i class="bi bi-eye"></i> Lihat Detail
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Belum ada log pengiriman email</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if($logEmailList->hasPages())
                    <div class="mt-4 d-flex justify-content-between align-items-center flex-wrap">
                        <div class="text-muted small mb-2 mb-md-0">
                            Menampilkan {{ $logEmailList->firstItem() }} - {{ $logEmailList->lastItem() }} dari {{ $logEmailList->total() }} log
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $logEmailList->links() }}
                        </nav>
                    </div>
                    @elseif($logEmailList->total() > 0)
                    <div class="mt-3 text-muted small">
                        Menampilkan {{ $logEmailList->total() }} log
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Email -->
@foreach($logEmailList as $log)
<div class="modal fade" id="detailModal{{ $log->id }}" tabindex="-1" aria-labelledby="detailModalLabel{{ $log->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="detailModalLabel{{ $log->id }}">
                    <i class="bi bi-envelope-open"></i> Detail Email Reminder
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="fw-bold text-muted small">Tanggal Pengiriman:</label>
                        <p class="mb-0">{{ $log->created_at->format('d F Y, H:i') }} WIB</p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="fw-bold text-muted small">Penerima:</label>
                        <p class="mb-0">{{ $log->penerima_email }}</p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="fw-bold text-muted small">Subjek:</label>
                        <p class="mb-0">{{ $log->subjek }}</p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="fw-bold text-muted small">Status:</label>
                        <div>
                            @if($log->status_pengiriman == 'success')
                                <span class="badge bg-success">Terkirim</span>
                            @elseif($log->status_pengiriman == 'failed')
                                <span class="badge bg-danger">Gagal</span>
                            @else
                                <span class="badge bg-warning">Pending</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="fw-bold text-muted small">Isi Pesan:</label>
                        <div class="border rounded p-3 bg-light" style="max-height: 400px; overflow-y: auto;">
                            @if($log->isi_email)
                                {!! nl2br(e($log->isi_email)) !!}
                            @else
                                <em class="text-muted">Isi email tidak tersedia</em>
                            @endif
                        </div>
                    </div>
                </div>

                @if($log->pesan_error)
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="fw-bold text-danger small">Pesan Error:</label>
                        <div class="alert alert-danger mb-0">
                            {{ $log->pesan_error }}
                        </div>
                    </div>
                </div>
                @endif

                <div class="row">
                    <div class="col-md-12">
                        <label class="fw-bold text-muted small">Percobaan Kirim:</label>
                        <p class="mb-0">{{ $log->percobaan_kirim }} kali</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection
