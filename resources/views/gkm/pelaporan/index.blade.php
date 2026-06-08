@extends('layouts.app')

@section('page-title', 'Pelaporan')

@section('content')
<div style="padding: 1.5rem;">
    <div class="row mb-4">
        <div class="col-12">
            <p class="text-muted">Generate Laporan Otomatis Menggunakan AI Agent</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-file-earmark-text" style="font-size: 40px; color: #1e3c72;"></i>
                    <h6 class="mt-3 mb-2">Laporan Bulanan</h6>
                    <p class="text-muted small mb-3">Generate laporan otomatis hasil monitoring RPS dan materi perkuliahan</p>
                    <a href="{{ route('gkm.pelaporan.artefak') }}" class="btn btn-primary btn-sm">Lihat Laporan</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-graph-up" style="font-size: 40px; color: #28a745;"></i>
                    <h6 class="mt-3 mb-2">Laporan Hasil Kuisioner</h6>
                    <p class="text-muted small mb-3">Generate laporan otomatis dari hasil kuisioner mahasiswa menggunakan AI agent</p>
                    <a href="{{ route('gkm.pelaporan.kuisioner') }}" class="btn btn-success btn-sm">Lihat Laporan</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">Arsip Laporan</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Jenis Laporan</th>
                                    <th>Periode</th>
                                    <th>Tanggal Generate</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>Laporan Artefak RPS & Materi</td>
                                    <td>Januari 2026</td>
                                    <td>05-02-2026</td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-outline-primary">Download</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>Laporan Kuisioner</td>
                                    <td>Januari 2026</td>
                                    <td>05-02-2026</td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-outline-primary">Download</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <nav class="mt-3">
                        <ul class="pagination mb-0">
                            <li class="page-item"><a class="page-link" href="#">Previous</a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">Next</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
