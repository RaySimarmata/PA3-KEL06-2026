@extends('layouts.app')

@section('page-title', 'Laporan Kuesioner Bulanan')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4>Laporan Kuesioner Bulanan</h4>
                    <p class="text-muted">Laporan bulanan yang dihasilkan oleh AI Agent</p>
                </div>
                <div>
                    <a href="{{ route('gkm.laporan-kuesioner.template.index') }}" class="btn btn-outline-primary me-2">
                        <i class="bi bi-file-earmark-text"></i> Kelola Template
                    </a>
                    <a href="{{ route('gkm.laporan-kuesioner.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Generate Laporan Baru
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('gkm.laporan-kuesioner.index') }}">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Periode</label>
                                <select name="periode" class="form-select">
                                    <option value="">Semua Periode</option>
                                    @foreach($periodes as $p)
                                        <option value="{{ $p->periode }}" {{ request('periode') == $p->periode ? 'selected' : '' }}>
                                            {{ $p->bulan }} {{ $p->tahun }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                                    <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Sedang Diproses</option>
                                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                                    <option value="error" {{ request('status') == 'error' ? 'selected' : '' }}>Error</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- List Laporan -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Periode</th>
                                    <th>Program Studi</th>
                                    <th>Total Kuesioner</th>
                                    <th>Index Kepuasan</th>
                                    <th>Status</th>
                                    <th>Dibuat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($laporanList as $laporan)
                                <tr>
                                    <td>
                                        <strong>{{ $laporan->formatted_periode }}</strong>
                                    </td>
                                    <td>{{ $laporan->prodi->nama_prodi ?? '-' }}</td>
                                    <td>{{ $laporan->total_kuesioner }}</td>
                                    <td>
                                        @if($laporan->index_kepuasan_rata_rata)
                                            <span class="badge bg-primary">
                                                {{ number_format($laporan->index_kepuasan_rata_rata, 2) }}
                                            </span>
                                            <small class="text-muted">({{ number_format($laporan->persen_kepuasan_rata_rata, 1) }}%)</small>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $laporan->status_badge }}">
                                            {{ $laporan->status_label }}
                                        </span>
                                    </td>
                                    <td>{{ $laporan->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('gkm.laporan-kuesioner.show', $laporan->id) }}" 
                                               class="btn btn-sm btn-outline-primary"
                                               title="Lihat Detail">
                                                <i class="bi bi-eye"></i> Detail
                                            </a>
                                            
                                            @if($laporan->status == 'completed' && $laporan->file_word)
                                                <a href="{{ route('gkm.laporan-kuesioner.download', [$laporan->id, 'word']) }}" 
                                                   class="btn btn-sm btn-outline-success"
                                                   title="Download Word">
                                                    <i class="bi bi-download"></i> Word
                                                </a>
                                            @endif
                                            
                                            <form action="{{ route('gkm.laporan-kuesioner.destroy', $laporan->id) }}" 
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
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Belum ada laporan. <a href="{{ route('gkm.laporan-kuesioner.create') }}" class="fw-bold">Generate laporan baru</a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $laporanList->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
