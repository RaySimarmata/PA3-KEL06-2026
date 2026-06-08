@extends('layouts.app')

@section('page-title', 'Laporan Bulanan')

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Filter Section -->
        <div class="filter-card mb-4">
            <form method="GET" action="{{ route('gkm.laporan-artefak.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="filter-label">Periode</label>
                        <select name="periode" class="form-select">
                            <option value="">Semua Periode</option>
                            @foreach ($periodes as $p)
                                <option value="{{ $p->periode }}"
                                    {{ request('periode') == $p->periode ? 'selected' : '' }}>
                                    {{ $p->bulan }} {{ $p->tahun }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="filter-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                            <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Sedang
                                Diproses</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai
                            </option>
                            <option value="error" {{ request('status') == 'error' ? 'selected' : '' }}>Error</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100" type="submit" onclick="performSearch()">
                            <i class="bi bi-search me-1"></i> Cari
                        </button>

                    </div>
                </div>
            </form>
        </div>

        <!-- Monitoring Table -->
        <div class="monitoring-card">
            <div class="monitoring-header">
                <h6>Laporan Bulanan</h6>
                <div style="margin-left: auto; display: flex; gap: 0.5rem;">
                    <a href="{{ route('gkm.laporan-artefak.template.index') }}" class="btn btn-sm btn-success"
                        style="background-color: #28a745; border-color: #28a745; transition: none;"
                        onmouseover="this.style.backgroundColor='#28a745'; this.style.borderColor='#28a745';"
                        onmouseout="this.style.backgroundColor='#28a745'; this.style.borderColor='#28a745';">
                        <i class="bi bi-file-earmark-text" style="color: white;"></i> Kelola Template
                    </a>
                    <a href="{{ route('gkm.laporan-artefak.create') }}" class="btn-reminder">
                        <i class="bi bi-plus-circle"></i>
                        <span>Generate Laporan Baru</span>
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-monitoring">
                    <thead>
                        <tr>
                            <th style="width: 18%;">Periode</th>
                            <th style="width: 12%;" class="text-center">Total RPS</th>
                            <th style="width: 12%;" class="text-center">Total Materi</th>
                            <th style="width: 15%;" class="text-center">Status</th>
                            <th style="width: 15%;">Dibuat</th>
                            <th style="width: 28%;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($laporanList as $laporan)
                            <tr>
                                <td class="code-mk">{{ $laporan->formatted_periode }}</td>
                                <td class="text-center">
                                    <span class="badge-gkm info">{{ $laporan->total_rps ?? 0 }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge-gkm info">{{ $laporan->total_materi ?? 0 }}</span>
                                </td>
                                <td class="text-center">
                                    <span
                                        class="badge-gkm {{ $laporan->status_badge == 'success' ? 'success' : ($laporan->status_badge == 'warning' ? 'warning' : ($laporan->status_badge == 'danger' ? 'danger' : 'info')) }}">
                                        {{ $laporan->status_label }}
                                    </span>
                                </td>
                                <td class="text-secondary" style="font-size: 0.85rem;">
                                    {{ $laporan->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('gkm.laporan-artefak.show', $laporan->id) }}"
                                            class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        @if ($laporan->status == 'completed' && $laporan->file_word)
                                            <a href="{{ route('gkm.laporan-artefak.download', [$laporan->id, 'word']) }}"
                                                class="btn btn-sm btn-outline-success" title="Download Word">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        @endif

                                        <form action="{{ route('gkm.laporan-artefak.destroy', $laporan->id) }}"
                                            method="POST" class="d-inline"
                                            onsubmit="return confirm('Yakin ingin menghapus laporan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <p>Belum ada laporan. <a href="{{ route('gkm.laporan-artefak.create') }}"
                                                style="color: #5B9BD5; font-weight: 600;">Generate laporan baru</a></p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if ($laporanList->hasPages())
            <div class="mt-4 d-flex justify-content-center">
                {{ $laporanList->links() }}
            </div>
        @endif
    </div>
@endsection
