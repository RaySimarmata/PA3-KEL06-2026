@extends('layouts.app')

@section('page-title', 'Laporan Hasil Artefak Perkuliahan RPS dan Materi')

@section('content')
<div style="padding: 1.5rem;">
    <div class="row mb-4">
        <div class="col-12">
            <p class="text-muted">Laporan hasil monitoring artefak perkuliahan RPS dan materi</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="mb-3">Filter Laporan</h6>
                    <form method="GET" action="{{ route('gkm.pelaporan.artefak') }}">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Periode</label>
                                <select name="periode" class="form-select">
                                    <option value="">Semua Periode</option>
                                    <option value="2026-01">Januari 2026</option>
                                    <option value="2026-02">Februari 2026</option>
                                    <option value="2026-03">Maret 2026</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Dosen</label>
                                <select name="dosen" class="form-select">
                                    <option value="">Semua Dosen</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
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

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Data Artefak Perkuliahan</h5>
                    <button class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-pdf"></i> Generate Laporan PDF
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Dosen</th>
                                    <th>Mata Kuliah</th>
                                    <th>Jenis Artefak</th>
                                    <th>Status</th>
                                    <th>Tanggal Upload</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($laporan as $index => $item)
                                <tr>
                                    <td>{{ $laporan->firstItem() + $index }}</td>
                                    <td>{{ $item->dosen->nama_dosen ?? '-' }}</td>
                                    <td>{{ $item->materi->matakuliah->nama_matakuliah ?? '-' }}</td>
                                    <td>
                                        @if($item->materi->jenis_materi == 'RPS')
                                            <span class="badge bg-primary">RPS</span>
                                        @else
                                            <span class="badge bg-info">Materi</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->status_evaluasi == 'Sesuai')
                                            <span class="badge bg-success">Sesuai</span>
                                        @else
                                            <span class="badge bg-warning">Perlu Perbaikan</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->created_at->format('d-m-Y') }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailModal{{ $item->id }}">
                                            <i class="bi bi-eye"></i> Detail
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada data artefak</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($laporan->hasPages())
                    <div class="mt-3">
                        {{ $laporan->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
