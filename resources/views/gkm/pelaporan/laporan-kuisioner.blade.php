@extends('layouts.app')

@section('page-title', 'Laporan Hasil Kuisioner')

@section('content')
<div style="padding: 1.5rem;">
    <div class="row mb-4">
        <div class="col-12">
            <p class="text-muted">Laporan hasil kuisioner mahasiswa terhadap perkuliahan</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="mb-3">Filter Laporan</h6>
                    <form method="GET" action="{{ route('gkm.pelaporan.kuisioner') }}">
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
                                <label class="form-label">Jenis Kuisioner</label>
                                <select name="jenis" class="form-select">
                                    <option value="">Semua Jenis</option>
                                    <option value="kepuasan">Kepuasan Mahasiswa</option>
                                    <option value="evaluasi">Evaluasi Dosen</option>
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
                    <h5 class="mb-0">Data Kuisioner</h5>
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
                                    <th>Judul Kuisioner</th>
                                    <th>Jenis</th>
                                    <th>Periode</th>
                                    <th>Total Responden</th>
                                    <th>Rata-rata Skor</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kuisioner as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->judul_kuisioner }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ $item->jenis_kuisioner }}</span>
                                    </td>
                                    <td>{{ $item->periode }}</td>
                                    <td>{{ $item->jawaban->count() }}</td>
                                    <td>
                                        @php
                                            $avgScore = $item->jawaban->avg('skor') ?? 0;
                                        @endphp
                                        <span class="badge {{ $avgScore >= 4 ? 'bg-success' : ($avgScore >= 3 ? 'bg-warning' : 'bg-danger') }}">
                                            {{ number_format($avgScore, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailModal{{ $item->id }}">
                                            <i class="bi bi-eye"></i> Detail
                                        </button>
                                        <button class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-bar-chart"></i> Analisis
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada data kuisioner</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h6 class="mb-0">Statistik Kepuasan Mahasiswa</h6>
                </div>
                <div class="card-body">
                    <canvas id="chartKepuasan" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h6 class="mb-0">Tren Evaluasi Dosen</h6>
                </div>
                <div class="card-body">
                    <canvas id="chartEvaluasi" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
