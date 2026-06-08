@extends('layouts.app')

@section('page-title', 'Dashboard GJM')

@section('styles')
    <style>
        .dashboard-shell {
            padding: clamp(0.75rem, 1.6vw, 1.25rem);
            background: #f4f6f9;
            min-height: 100vh;
        }

        .dashboard-shell .content-card,
        .dashboard-shell .stats-card {
            padding: clamp(0.875rem, 1.4vw, 1.25rem);
        }

        .dashboard-shell .hero-title {
            font-size: clamp(1.5rem, 2.2vw, 2rem);
        }

        .dashboard-shell .stats-icon {
            font-size: clamp(1.85rem, 2.1vw, 2.3rem);
        }

        .dashboard-shell .quick-icon {
            font-size: clamp(1.25rem, 1.8vw, 1.8rem);
        }

        .icon-box {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .bg-primary-subtle {
            background-color: rgba(13, 110, 253, 0.1);
        }

        .bg-success-subtle {
            background-color: rgba(25, 135, 84, 0.1);
        }

        .bg-warning-subtle {
            background-color: rgba(255, 193, 7, 0.1);
        }

        .bg-danger-subtle {
            background-color: rgba(220, 53, 69, 0.1);
        }
    </style>
@endsection

@section('content')
    <div class="dashboard-shell">
        <!-- Welcome Section dengan tombol di kanan (sama seperti GKM) -->
        <div class="content-card mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h3 class="hero-title mb-1" style="font-weight: 600; color: #333;">
                        Selamat Datang, {{ $user->name }}
                    </h3>
                    <p class="text-secondary mb-0">Periode: {{ $periode }}</p>
                </div>
                <div>
                    <form action="{{ route('gjm.analisis') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary shadow-sm d-inline-flex align-items-center gap-2">
                            <i class="bi bi-graph-up"></i> Jalankan Analisis Spark
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="content-card mb-4">
            <form method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Program Studi</label>
                        <select name="prodi" class="form-select">
                            <option value="SEMUA">Semua Prodi</option>
                            @foreach ($listProdi as $item)
                                <option value="{{ $item }}" {{ request('prodi') == $item ? 'selected' : '' }}>
                                    {{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tahun</label>
                        <select name="tahun" class="form-select">
                            <option value="">Semua Tahun</option>
                            @foreach ($listTahun as $item)
                                <option value="{{ $item }}" {{ request('tahun') == $item ? 'selected' : '' }}>
                                    {{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Semester</label>
                        <select name="semester" class="form-select">
                            <option value="">Semua Semester</option>
                            @foreach ($listSemester as $item)
                                <option value="{{ $item }}" {{ request('semester') == $item ? 'selected' : '' }}>
                                    {{ $item == 1 ? 'Gasal' : 'Genap' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100 d-inline-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-funnel-fill"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- KPI Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card h-100" style="border-left: 4px solid #0d6efd;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Rata-rata Fakultas</small>
                            <h2 class="fw-bold text-primary mt-2">{{ $stats['rata_fakultas'] ?? 0 }}</h2>
                        </div>
                        <div class="icon-box bg-primary-subtle">
                            <i class="bi bi-bar-chart-line text-primary stats-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card h-100" style="border-left: 4px solid #28a745;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Total Responden</small>
                            <h2 class="fw-bold text-success mt-2">{{ $stats['total_responden'] ?? 0 }}</h2>
                        </div>
                        <div class="icon-box bg-success-subtle">
                            <i class="bi bi-people-fill text-success stats-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card h-100" style="border-left: 4px solid #ffc107;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Kepuasan Mahasiswa</small>
                            <h2 class="fw-bold text-warning mt-2">{{ $stats['kepuasan_mahasiswa'] ?? 0 }}%</h2>
                        </div>
                        <div class="icon-box bg-warning-subtle">
                            <i class="bi bi-emoji-smile text-warning stats-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card h-100" style="border-left: 4px solid #dc3545;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Matkul Bermasalah</small>
                            <h2 class="fw-bold text-danger mt-2">{{ $stats['matkul_bermasalah'] ?? 0 }}</h2>
                        </div>
                        <div class="icon-box bg-danger-subtle">
                            <i class="bi bi-exclamation-triangle text-danger stats-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-lg-8 mb-3">
                <div class="stats-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-semibold mb-0">Performa Program Studi</h5>
                        <span class="badge bg-primary">Analytics</span>
                    </div>
                    <canvas id="chartProdi" height="110"></canvas>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="stats-card h-100">
                    <h5 class="fw-semibold mb-4">Distribusi Penilaian</h5>
                    <canvas id="chartDistribusi"></canvas>
                </div>
            </div>
        </div>

        <!-- Trend Chart -->
        <div class="stats-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-semibold mb-0">Trend Semester</h5>
                <span class="badge bg-success">Trend Analytics</span>
            </div>
            <canvas id="trendSemesterChart" height="80"></canvas>
        </div>

        <!-- Heatmap Dosen -->
        <div class="stats-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-semibold mb-0">Heatmap Performa Dosen</h5>
                <span class="badge bg-dark">{{ count($heatmapDosen) }} Dosen</span>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Dosen</th>
                            @foreach ($listProdi as $prodi)
                                <th class="text-center">{{ $prodi }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($heatmapDosen as $dosen => $prodis)
                            <tr>
                                <td class="fw-semibold">{{ $dosen }}</td>
                                @foreach ($listProdi as $prodi)
                                    @php
                                        $nilai = $prodis[$prodi] ?? null;
                                        $bg =
                                            $nilai >= 3.25
                                                ? '#198754'
                                                : ($nilai >= 2.75
                                                    ? '#ffc107'
                                                    : ($nilai
                                                        ? '#dc3545'
                                                        : '#f8f9fa'));
                                    @endphp
                                    <td class="text-center fw-bold"
                                        style="background: {{ $bg }}; color: {{ $nilai ? 'white' : '#999' }};">
                                        {{ $nilai ?? '-' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top & Bottom Dosen -->
        <div class="row mb-4">
            <div class="col-lg-6 mb-3">
                <div class="stats-card h-100">
                    <h5 class="fw-semibold mb-4">Top Dosen</h5>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Dosen</th>
                                    <th>Avg</th>
                                    <th>MK</th>
                                    <th>Responden</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topDosen as $item)
                                    <tr>
                                        <td class="fw-semibold">{{ $item['nama'] }}</td>
                                        <td>{{ $item['avg'] }}</td>
                                        <td>{{ $item['jumlah_matkul'] }}</td>
                                        <td>{{ $item['jumlah_responden'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="stats-card h-100">
                    <h5 class="fw-semibold mb-4 text-danger">Dosen Perlu Evaluasi</h5>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Dosen</th>
                                    <th>Avg</th>
                                    <th>Kepuasan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dosenBermasalah as $item)
                                    <tr>
                                        <td class="fw-semibold">{{ $item['nama'] }}</td>
                                        <td>{{ $item['avg'] }}</td>
                                        <td>{{ $item['kepuasan'] }}%</td>
                                        <td><span class="badge bg-danger">Evaluasi</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pertanyaan Terburuk -->
        <div class="stats-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-semibold mb-0">Pertanyaan dengan Nilai Terendah</h5>
                <span class="badge bg-danger">Critical Insight</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Pertanyaan</th>
                            <th width="120">Rata-rata</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pertanyaanTerburuk as $item)
                            <tr>
                                <td>{{ $item['pertanyaan'] }}</td>
                                <td><span class="badge bg-danger">{{ $item['avg'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Insight -->
        <div class="stats-card">
            <h5 class="fw-semibold mb-4">Insight Otomatis</h5>
            @foreach ($insight as $item)
                <div class="alert alert-warning border-0 shadow-sm">
                    <i class="bi bi-lightbulb me-2"></i> {{ $item }}
                </div>
            @endforeach
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Chart Prodi
            new Chart(document.getElementById('chartProdi'), {
                type: 'bar',
                data: {
                    labels: @json($chartProdiLabel),
                    datasets: [{
                        label: 'Rata-rata',
                        data: @json($chartProdiData),
                        borderRadius: 10,
                        borderWidth: 1,
                        backgroundColor: '#0d6efd'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 4
                        }
                    }
                }
            });

            // Chart Distribusi
            new Chart(document.getElementById('chartDistribusi'), {
                type: 'doughnut',
                data: {
                    labels: @json(array_keys($kategoriDistribusi)),
                    datasets: [{
                        data: @json(array_values($kategoriDistribusi)),
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

            // Chart Trend
            new Chart(document.getElementById('trendSemesterChart'), {
                type: 'line',
                data: {
                    labels: @json($trendSemester->keys()),
                    datasets: [{
                        label: 'Trend Kepuasan',
                        data: @json($trendSemester->values()),
                        tension: 0.4,
                        fill: false,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.15)',
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#198754'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 4
                        }
                    }
                }
            });
        });
    </script>
@endsection
