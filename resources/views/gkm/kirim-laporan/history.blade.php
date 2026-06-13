@extends('layouts.app')

@section('page-title', 'Riwayat Pengiriman Laporan')

@section('content')
    <div style="padding: 1.5rem;">

        {{-- Header Card --}}
        <div class="filter-card mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="mb-1 fw-semibold" style="color: #333;">
                        Riwayat Pengiriman Laporan
                    </h5>
                    <p class="text-muted mb-0 small">
                        Riwayat pengiriman email laporan yang dikirim melalui modul Kirim Laporan.
                    </p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <form method="GET" action="{{ route('gkm.kirim-laporan.history') }}" class="d-flex gap-2">
                        <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()"
                            style="min-width: 140px;">
                            <option value="desc" {{ request('sort', 'desc') == 'desc' ? 'selected' : '' }}>Terbaru</option>
                            <option value="asc" {{ request('sort') == 'asc' ? 'selected' : '' }}>Terlama</option>
                        </select>
                    </form>
                    {{-- <a href="{{ route('gkm.kirim-laporan.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a> --}}
                </div>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="monitoring-card">
            <div class="monitoring-header">
                <h6 class="mb-0">Riwayat Pengiriman Laporan</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-monitoring">
                    <thead>
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 15%;">Tanggal Kirim</th>
                            <th style="width: 25%;">Subjek</th>
                            <th style="width: 20%;">Penerima</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 15%;">Keterangan</th>
                            <th style="width: 10%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $index => $item)
                            <tr>
                                <td class="code-mk">{{ $history->firstItem() + $index }}</td>
                                <td class="text-secondary">
                                    {{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}
                                </td>
                                <td class="nama-mk">{{ \Illuminate\Support\Str::limit($item->subject, 40) }}</td>
                                <td class="dosen-name">{{ \Illuminate\Support\Str::limit($item->recipients, 40) }}</td>
                                <td>
                                    @if ($item->status === 'success')
                                        <span class="badge-gkm success">Terkirim</span>
                                    @elseif($item->status === 'partial')
                                        <span class="badge-gkm warning">Sebagian</span>
                                    @else
                                        <span class="badge-gkm danger">Gagal</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($item->error_message)
                                        <span class="text-sm text-danger" title="{{ $item->error_message }}">
                                            {{ \Illuminate\Support\Str::limit($item->error_message, 30) }}
                                        </span>
                                    @else
                                        <span class="text-sm" style="color: #28a745;">Email berhasil dikirim</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#detailModal{{ $item->id }}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                        <p class="mt-2 mb-0">Belum ada riwayat pengiriman laporan</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($history->hasPages())
            <div class="mt-4 d-flex justify-content-center">
                {{ $history->links() }}
            </div>
        @endif
    </div>

    {{-- Modal Detail --}}
    @foreach ($history as $item)
        <div class="modal fade" id="detailModal{{ $item->id }}" tabindex="-1"
            aria-labelledby="detailModalLabel{{ $item->id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title" id="detailModalLabel{{ $item->id }}">
                            <i class="bi bi-envelope-open"></i> Detail Pengiriman Laporan
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="fw-bold text-muted small">Tanggal Kirim:</label>
                                <p class="mb-0">{{ \Carbon\Carbon::parse($item->created_at)->format('d F Y, H:i') }} WIB</p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="fw-bold text-muted small">Penerima:</label>
                                <p class="mb-0">{{ $item->recipients }}</p>
                            </div>
                        </div>

                        @if ($item->cc)
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="fw-bold text-muted small">CC:</label>
                                    <p class="mb-0">{{ $item->cc }}</p>
                                </div>
                            </div>
                        @endif

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="fw-bold text-muted small">Subjek:</label>
                                <p class="mb-0">{{ $item->subject }}</p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="fw-bold text-muted small">Status:</label>
                                <div>
                                    @if ($item->status === 'success')
                                        <span class="badge bg-success">Terkirim</span>
                                    @elseif($item->status === 'partial')
                                        <span class="badge bg-warning text-dark">Sebagian</span>
                                    @else
                                        <span class="badge bg-danger">Gagal</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="fw-bold text-muted small">Jumlah Terkirim:</label>
                                <p class="mb-0">{{ $item->sent_count }} dari {{ $item->recipient_count }} penerima</p>
                            </div>
                        </div>

                        @if ($item->attachment_names && count((array) $item->attachment_names) > 0)
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="fw-bold text-muted small">Lampiran:</label>
                                    <p class="mb-0">
                                        {{ is_array($item->attachment_names) ? implode(', ', $item->attachment_names) : $item->attachment_names }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="fw-bold text-muted small">Isi Pesan:</label>
                                <div class="border rounded p-3 bg-light" style="max-height: 400px; overflow-y: auto;">
                                    @if ($item->message)
                                        <pre style="white-space: pre-wrap; font-family: inherit; margin: 0; font-size: 0.875rem;">{{ $item->message }}</pre>
                                    @else
                                        <em class="text-muted">Isi pesan tidak tersedia</em>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if ($item->error_message)
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="fw-bold text-danger small">Pesan Error:</label>
                                    <div class="alert alert-danger mb-0">
                                        {{ $item->error_message }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (!empty($item->recipient_statuses))
                            <div class="row">
                                <div class="col-md-12">
                                    <label class="fw-bold text-muted small">Status Per Penerima:</label>
                                    <ul class="mb-0" style="font-size: 0.875rem;">
                                        @foreach ($item->recipient_statuses as $recipient)
                                            <li>
                                                {{ $recipient['email'] }} &mdash;
                                                <span class="{{ $recipient['status'] === 'success' ? 'text-success' : 'text-danger' }}">
                                                    {{ $recipient['status'] === 'success' ? 'Terkirim' : 'Gagal' }}
                                                </span>
                                                @if (!empty($recipient['error']))
                                                    <small class="text-danger">({{ $recipient['error'] }})</small>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
