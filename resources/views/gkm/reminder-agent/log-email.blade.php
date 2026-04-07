@extends('layouts.app')

@section('page-title', 'Log Pengiriman Email')

@section('content')
<div style="padding: 1.5rem;">
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stats-card" style="border-left: 4px solid #28a745;">
                <div class="stats-value" style="color: #28a745;">{{ $totalSuccess }}</div>
                <div class="stats-label">Email Terkirim</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card" style="border-left: 4px solid #dc3545;">
                <div class="stats-value" style="color: #dc3545;">{{ $totalFailed }}</div>
                <div class="stats-label">Email Gagal</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card" style="border-left: 4px solid #ffc107;">
                <div class="stats-value" style="color: #ffc107;">{{ $totalPending }}</div>
                <div class="stats-label">Email Pending</div>
            </div>
        </div>
    </div>

    <div class="monitoring-card">
        <div class="monitoring-header">
            <i class="bi bi-envelope-check"></i>
            <h6>Log Pengiriman Email Reminder</h6>
            <div style="margin-left: auto;">
                <a href="{{ route('gkm.reminder-agent.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-monitoring">
                <thead>
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
                        <td class="code-mk">{{ $logEmailList->firstItem() + $index }}</td>
                        <td class="text-secondary">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td class="nama-mk">{{ Str::limit($log->subjek, 40) }}</td>
                        <td class="dosen-name">{{ $log->penerima_email }}</td>
                        <td>
                            @if($log->status_pengiriman == 'success')
                                <span class="badge-gkm success">Terkirim</span>
                            @elseif($log->status_pengiriman == 'failed')
                                <span class="badge-gkm danger">Gagal</span>
                            @else
                                <span class="badge-gkm warning">Pending</span>
                            @endif
                        </td>
                        <td>
                            @if($log->pesan_error)
                                <span class="text-sm text-danger" title="{{ $log->pesan_error }}">
                                    {{ Str::limit($log->pesan_error, 30) }}
                                </span>
                            @else
                                <span class="text-sm" style="color: #28a745;">Email berhasil dikirim</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#detailModal{{ $log->id }}">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                <p class="mt-2 mb-0">Belum ada log pengiriman email</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    @if($logEmailList->hasPages())
    <div class="mt-4 d-flex justify-content-center">
        {{ $logEmailList->links() }}
    </div>
    @endif
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
