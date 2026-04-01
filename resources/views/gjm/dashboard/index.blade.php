@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="mb-2">Selamat Datang, {{ auth()->user()->name }}</h2>
                    <p class="text-muted mb-0">Dashboard GJM - Laporan Bulan: {{ now()->format('F Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">Rekap Laporan GKM TRPL Bulanan</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Kepatuhan Upload Materi</span>
                            <strong>80%</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" style="width: 80%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Kepatuhan RPS</span>
                            <strong>85%</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-info" style="width: 85%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Nilai Kuisioner Rata-rata</span>
                            <strong>3.5</strong>
                        </div>
                        <small class="text-muted">dari skala 5.0</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">Temuan Aktif</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning border-0 mb-2">
                        <strong>2</strong> Temuan
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item border-0 px-0 py-2">
                            <i class="bi bi-exclamation-triangle text-warning"></i>
                            Diskusi Kelas: Rata-rata nilai kuisioner di bawah 3.0
                        </li>
                        <li class="list-group-item border-0 px-0 py-2">
                            <i class="bi bi-exclamation-triangle text-warning"></i>
                            Basis Data: Dosen belum melengkapi RPS
                        </li>
                    </ul>
                    <a href="{{ route('gjm.recap.index') }}" class="btn btn-link btn-sm p-0 mt-2">Lihat Detail ></a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">Grafik Hasil Kuisioner</h5>
                </div>
                <div class="card-body">
                    <div style="height: 250px;" id="chart-container">
                        <p class="text-muted text-center mt-5">Grafik akan ditampilkan di sini</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex gap-2">
                    <a href="{{ route('gjm.laporan.index') }}" class="btn btn-success">
                        <i class="bi bi-file-earmark-pdf"></i> Generate Laporan Fakultas
                    </a>
                    <a href="{{ route('gjm.validasi.index') }}" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Validasi Laporan GKM
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
