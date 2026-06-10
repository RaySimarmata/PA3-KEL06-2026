@extends('layouts.app')

@section('page-title', 'Riwayat Pengiriman Laporan')

@section('content')
<div style="padding: 1.5rem;">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Riwayat Pengiriman Laporan</h5>
                    <p class="text-muted mb-0">Riwayat pengiriman email laporan yang dikirim melalui modul Kirim Laporan.</p>
                </div>
                <a href="{{ route('gkm.kirim-laporan.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('gkm.kirim-laporan.history') }}">
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Urutkan Berdasarkan</label>
                        <select name="sort" class="form-select" onchange="this.form.submit()">
                            <option value="desc" {{ request('sort', 'desc') == 'desc' ? 'selected' : '' }}>Terbaru</option>
                            <option value="asc" {{ request('sort') == 'asc' ? 'selected' : '' }}>Terlama</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

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
                        @forelse($history as $index => $item)
                        <tr>
                            <td>{{ $history->firstItem() + $index }}</td>
                            <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y H:i') }}</td>
                            <td>
                                <strong>{{ \Illuminate\Support\Str::limit($item->recipients, 60) }}</strong>
                            </td>
                            <td>{{ $item->subject }}</td>
                            <td>
                                @if($item->status === 'success')
                                    <span class="badge bg-success">Terkirim</span>
                                @elseif($item->status === 'partial')
                                    <span class="badge bg-warning">Sebagian</span>
                                @else
                                    <span class="badge bg-danger">Gagal</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#detailModal{{ $item->id }}">
                                    <i class="bi bi-eye"></i> Lihat Detail
                                </button>
                            </td>
                        </tr>

                        <div class="modal fade" id="detailModal{{ $item->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Detail Pengiriman</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <strong>Tanggal Kirim:</strong>
                                            <p>{{ \Carbon\Carbon::parse($item->created_at)->format('d F Y H:i') }}</p>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Penerima:</strong>
                                            <p>{{ $item->recipients }}</p>
                                        </div>
                                        <div class="mb-3">
                                            <strong>CC:</strong>
                                            <p>{{ $item->cc ?: '-' }}</p>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Subjek:</strong>
                                            <p>{{ $item->subject }}</p>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Status:</strong>
                                            <p>
                                                @if($item->status === 'success')
                                                    <span class="badge bg-success">Terkirim</span>
                                                @elseif($item->status === 'partial')
                                                    <span class="badge bg-warning">Sebagian</span>
                                                @else
                                                    <span class="badge bg-danger">Gagal</span>
                                                @endif
                                            </p>
                                            @if($item->error_message)
                                                <small class="text-danger">Error: {{ $item->error_message }}</small>
                                            @endif
                                        </div>
                                        <div class="mb-3">
                                            <strong>Isi Pesan:</strong>
                                            <div class="border rounded p-3 bg-light">
                                                <pre style="white-space: pre-wrap; font-family: inherit; margin: 0;">{{ $item->message }}</pre>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Jumlah Terkirim:</strong>
                                            <p>{{ $item->sent_count }} dari {{ $item->recipient_count }}</p>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Nama Lampiran:</strong>
                                            <p>{{ is_array($item->attachment_names) ? implode(', ', $item->attachment_names) : '-' }}</p>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Status Penerima:</strong>
                                            <ul class="mb-0">
                                                @foreach($item->recipient_statuses ?? [] as $recipient)
                                                    <li>{{ $recipient['email'] }} - {{ $recipient['status'] }}@if(!empty($recipient['error'])), {{ $recipient['error'] }}@endif</li>
                                                @endforeach
                                            </ul>
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
                                Belum ada riwayat pengiriman laporan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($history->hasPages())
            <div class="mt-3">
                {{ $history->links() }}
            </div>
            @endif
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-success">{{ $history->where('status', 'success')->count() }}</h3>
                    <p class="text-muted mb-0">Email Terkirim</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-danger">{{ $history->where('status', 'failed')->count() }}</h3>
                    <p class="text-muted mb-0">Email Gagal</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-primary">{{ $history->total() }}</h3>
                    <p class="text-muted mb-0">Total Email</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
