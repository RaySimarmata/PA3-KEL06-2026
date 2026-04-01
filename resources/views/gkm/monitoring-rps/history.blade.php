@extends('layouts.app')

@section('page-title', 'History Reminder RPS')

@section('content')
<div style="padding: 1.5rem;">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">History Reminder Upload RPS</h5>
                    <p class="text-muted mb-0">Riwayat pengiriman email reminder kepada dosen</p>
                </div>
                <a href="{{ route('gkm.monitoring-rps.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('gkm.monitoring-rps.history') }}">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Filter Dosen</label>
                        <select name="dosenId" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Dosen</option>
                            @foreach($dosenList as $dosen)
                                <option value="{{ $dosen->id }}" {{ $dosenId == $dosen->id ? 'selected' : '' }}>
                                    {{ $dosen->nama_lengkap }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Table History -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Tanggal Kirim</th>
                            <th>Penerima</th>
                            <th>Subjek</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logEmailList as $index => $log)
                        <tr>
                            <td>{{ $logEmailList->firstItem() + $index }}</td>
                            <td>{{ \Carbon\Carbon::parse($log->tanggal_pengiriman)->format('d M Y') }}</td>
                            <td>
                                <strong>{{ $log->penerima_email }}</strong>
                            </td>
                            <td>{{ $log->subjek }}</td>
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
                                <button class="btn btn-sm btn-outline-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#detailModal{{ $log->id }}">
                                    <i class="bi bi-eye"></i> Lihat Detail
                                </button>
                            </td>
                        </tr>

                        <!-- Modal Detail -->
                        <div class="modal fade" id="detailModal{{ $log->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Detail Email Reminder</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <strong>Tanggal Pengiriman:</strong>
                                            <p>{{ \Carbon\Carbon::parse($log->tanggal_pengiriman)->format('d F Y') }}</p>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Penerima:</strong>
                                            <p>{{ $log->penerima_email }}</p>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Subjek:</strong>
                                            <p>{{ $log->subjek }}</p>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Status:</strong>
                                            <p>
                                                @if($log->status_pengiriman == 'success')
                                                    <span class="badge bg-success">Terkirim</span>
                                                @elseif($log->status_pengiriman == 'failed')
                                                    <span class="badge bg-danger">Gagal</span>
                                                    @if($log->pesan_error)
                                                        <br><small class="text-danger">Error: {{ $log->pesan_error }}</small>
                                                    @endif
                                                @else
                                                    <span class="badge bg-warning">Pending</span>
                                                @endif
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Isi Pesan:</strong>
                                            <div class="border rounded p-3 bg-light">
                                                <pre style="white-space: pre-wrap; font-family: inherit; margin: 0;">{{ $log->isi_email }}</pre>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Percobaan Kirim:</strong>
                                            <p>{{ $log->percobaan_kirim }} kali</p>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                Belum ada history reminder
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logEmailList->hasPages())
            <div class="mt-3">
                {{ $logEmailList->appends(['dosenId' => $dosenId])->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- Summary -->
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-success">{{ $logEmailList->where('status_pengiriman', 'success')->count() }}</h3>
                    <p class="text-muted mb-0">Email Terkirim</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-danger">{{ $logEmailList->where('status_pengiriman', 'failed')->count() }}</h3>
                    <p class="text-muted mb-0">Email Gagal</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-primary">{{ $logEmailList->total() }}</h3>
                    <p class="text-muted mb-0">Total Email</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
