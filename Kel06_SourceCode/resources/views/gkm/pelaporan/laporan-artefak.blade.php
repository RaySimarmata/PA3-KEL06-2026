@extends('layouts.app')

@section('page-title', 'Laporan Bulanan')

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Filter Section -->
        <div class="filter-card mb-4">
            <form method="GET" action="{{ route('gkm.pelaporan.artefak') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="filter-label">Periode</label>
                        <select name="periode" class="form-select">
                            <option value="">Semua Periode</option>
                            <option value="2026-01">Januari 2026</option>
                            <option value="2026-02">Februari 2026</option>
                            <option value="2026-03">Maret 2026</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="filter-label">Dosen</label>
                        <select name="dosen" class="form-select">
                            <option value="">Semua Dosen</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100" style="padding: 0.6rem;">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Monitoring Table -->
        <div class="monitoring-card">
            <div class="monitoring-header">
                <i class="bi bi-file-earmark-check" style="color: #5B9BD5;"></i>
                <h6>Data Artefak Perkuliahan</h6>
                <div style="margin-left: auto;">
                    <button class="btn-reminder">
                        <i class="bi bi-file-earmark-pdf"></i>
                        <span>Generate Laporan PDF</span>
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-monitoring">
                    <thead>
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 20%;">Dosen</th>
                            <th style="width: 25%;">Mata Kuliah</th>
                            <th style="width: 12%;" class="text-center">Jenis Artefak</th>
                            <th style="width: 12%;" class="text-center">Status</th>
                            <th style="width: 13%;">Tanggal Upload</th>
                            <th style="width: 13%;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($laporan as $index => $item)
                            <tr>
                                <td class="text-center">{{ $laporan->firstItem() + $index }}</td>
                                <td class="dosen-name">{{ $item->dosen->nama_dosen ?? '-' }}</td>
                                <td class="nama-mk">{{ $item->rps->matakuliah->nama_matakuliah ?? '-' }}</td>
                                <td class="text-center">
                                    @php
                                        $artefakLabel =
                                            $item->jenis_artefak === 'rps'
                                                ? 'RPS'
                                                : ($item->jenis_artefak === 'materi'
                                                    ? 'Materi'
                                                    : str_replace('_', ' ', ucfirst($item->jenis_artefak)));
                                    @endphp
                                    <span class="badge-gkm info">{{ $artefakLabel }}</span>
                                </td>
                                <td class="text-center">
                                    @if ($item->status_evaluasi == 'Sesuai')
                                        <span class="badge-gkm success">Sesuai</span>
                                    @else
                                        <span class="badge-gkm warning">Perlu Perbaikan</span>
                                    @endif
                                </td>
                                <td class="text-secondary" style="font-size: 0.85rem;">
                                    {{ $item->created_at->format('d/m/Y') }}
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#detailModal{{ $item->id }}" title="Lihat Detail">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <p>Belum ada data artefak</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if ($laporan->hasPages())
            <div class="mt-4 d-flex justify-content-center">
                {{ $laporan->links() }}
            </div>
        @endif
    </div>
@endsection
