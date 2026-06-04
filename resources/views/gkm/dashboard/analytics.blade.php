@extends('layouts.app')

@section('page-title', 'Dashboard Analytics GKM')

@section('content')

<div style="padding: 1.5rem; background: #f4f6f9; min-height: 100vh;">

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h3 class="fw-bold mb-1">Dashboard Analytics GKM</h3>
                    <p class="text-muted mb-0">
                        Selamat datang, <strong>{{ $user->name }}</strong>
                        • Periode {{ $periode }}
                    </p>
                </div>
                <div class="mt-3 mt-md-0">
                    <a href="{{ route('gkm.dashboard') }}" class="btn btn-outline-secondary shadow-sm">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <form method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tahun Akademik</label>
                        <select name="tahun" class="form-select">
                            <option value="">Semua Tahun</option>
                            @foreach($listTahun as $item)
                                <option value="{{ $item }}" {{ $tahun == $item ? 'selected' : '' }}>{{ $item }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Semester</label>
                        <select name="semester" class="form-select">
                            <option value="">Semua Semester</option>
                            @foreach($listSemester as $item)
                                <option value="{{ $item }}" {{ $semester == $item ? 'selected' : '' }}>{{ $item }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                    <button class="btn btn-primary w-100" type="button" id="btnSearch">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <small class="text-muted">Rata-rata Kepatuhan</small>
                    <h2 class="fw-bold text-primary mt-2">{{ $stats['avg_kepatuhan'] ?? 0 }}%</h2>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <small class="text-muted">Total Mata Kuliah</small>
                    <h2 class="fw-bold text-success mt-2">{{ $stats['total_mata_kuliah'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <small class="text-muted">Total Dosen</small>
                    <h2 class="fw-bold text-warning mt-2">{{ $stats['total_dosen'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <small class="text-muted">Belum Upload</small>
                    <h2 class="fw-bold text-danger mt-2">{{ $stats['jumlah_belum_upload'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
    </div>

    @if(($stats['total_records'] ?? 0) === 0)
        <div class="alert alert-warning">Tidak ada data monitoring perkuliahan untuk filter yang dipilih.</div>
    @endif

    <div class="row mb-4">
        <div class="col-lg-8 mb-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-semibold mb-0">Kepatuhan per Tingkat</h5>
                        <span class="badge bg-primary">Analitik</span>
                    </div>
                    <canvas id="chartTingkat" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <h5 class="fw-semibold mb-4">Distribusi Status</h5>
                    <canvas id="chartStatus" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-semibold mb-0">Trend Kepatuhan</h5>
                <span class="badge bg-success">Trend</span>
            </div>
            <canvas id="trendChart" height="80"></canvas>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-6 mb-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <h5 class="fw-semibold mb-4">Top Mata Kuliah</h5>
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode MK</th>
                                    <th>Nama Mata Kuliah</th>
                                    <th>Kepatuhan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topCourses as $item)
                                <tr>
                                    <td>{{ $item['kode_mk'] }}</td>
                                    <td>{{ $item['nama_matkul'] }}</td>
                                    <td>{{ $item['avg_kepatuhan'] }}%</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <h5 class="fw-semibold mb-4 text-danger">Mata Kuliah Perlu Perhatian</h5>
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode MK</th>
                                    <th>Nama Mata Kuliah</th>
                                    <th>Kepatuhan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bottomCourses as $item)
                                <tr>
                                    <td>{{ $item['kode_mk'] }}</td>
                                    <td>{{ $item['nama_matkul'] }}</td>
                                    <td>{{ $item['avg_kepatuhan'] }}%</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.icon-box {
    width: 70px;
    height: 70px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    new Chart(document.getElementById('chartTingkat'), {
        type: 'bar',
        data: {
            labels: @json($groupByTingkat->keys()),
            datasets: [{
                label: 'Rata-rata Kepatuhan',
                data: @json($groupByTingkat->values()),
                backgroundColor: '#0d6efd',
                borderRadius: 10,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });

    new Chart(document.getElementById('chartStatus'), {
        type: 'doughnut',
        data: {
            labels: @json($statusDistribution->keys()),
            datasets: [{
                data: @json($statusDistribution->values()),
                backgroundColor: ['#198754', '#ffc107', '#dc3545', '#0d6efd']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: @json($trendSemester->keys()),
            datasets: [{
                label: 'Trend Kepatuhan',
                data: @json($trendSemester->values()),
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.15)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
});
</script>

@endsection
