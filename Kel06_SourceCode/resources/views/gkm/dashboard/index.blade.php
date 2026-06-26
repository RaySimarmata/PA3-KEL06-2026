@extends('layouts.app')

@section('page-title', 'Dashboard GKM')

@section('styles')
    <style>
        .dashboard-shell {
            padding: clamp(0.75rem, 1.6vw, 1.25rem);
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

        .dashboard-shell .action-card {
            padding: 1.25rem;
        }

        .dashboard-shell .action-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 1rem;
        }

        .dashboard-shell .action-item {
            grid-column: span 12;
        }

        .dashboard-shell .action-item.wide {
            grid-column: span 12;
        }

        .dashboard-shell .action-btn {
            width: 100%;
            min-height: 82px;
            padding: 1rem 1.15rem;
            border-radius: 0.9rem;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 0.9rem;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
            box-shadow: 0 8px 20px rgba(30, 60, 114, 0.12);
            color: #ffffff;
        }

        .dashboard-shell .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(30, 60, 114, 0.16);
            filter: brightness(1.02);
        }

        .dashboard-shell .action-btn .action-icon {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
        }

        .dashboard-shell .action-btn .action-text {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.15rem;
            min-width: 0;
            color: #ffffff;
        }

        .dashboard-shell .action-btn .action-title {
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .dashboard-shell .action-btn .action-subtitle {
            font-size: 0.75rem;
            line-height: 1.2;
            opacity: 0.9;
            color: rgba(255, 255, 255, 0.9);
        }

        .dashboard-shell .action-btn.primary-dark {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: #fff;
        }

        .dashboard-shell .action-btn.primary-dark:hover {
            color: #fff;
        }

        .dashboard-shell .action-btn.primary-soft {
            background: linear-gradient(135deg, #5B9BD5 0%, #6fa8dc 100%);
            color: #fff;
        }

        .dashboard-shell .action-btn.primary-soft:hover {
            color: #fff;
        }

        .dashboard-shell .action-btn.success-gradient {
            background: linear-gradient(135deg, #28a745 0%, #34ce57 100%);
            color: #fff;
        }

        .dashboard-shell .action-btn.success-gradient:hover {
            color: #fff;
        }

        .dashboard-shell .action-btn.warning-gradient {
            background: linear-gradient(135deg, #06b6d4 0%, #22d3ee 100%);
            color: #fff;
        }

        .dashboard-shell .action-btn.warning-gradient:hover {
            color: #fff;
        }

        @media (min-width: 576px) {
            .dashboard-shell .action-item {
                grid-column: span 6;
            }
        }

        @media (min-width: 1200px) {
            .dashboard-shell .action-item {
                grid-column: span 3;
            }

            .dashboard-shell .action-item.wide {
                grid-column: span 3;
            }
        }

        @media (max-width: 575.98px) {
            .dashboard-shell .action-btn {
                min-height: 74px;
                padding: 0.95rem 1rem;
            }

            .dashboard-shell .action-btn .action-title {
                font-size: 0.9rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="dashboard-shell">
        <!-- Welcome Section -->
        <div class="content-card mb-4">
            <h3 class="hero-title mb-1" style="font-weight: 600; color: #333;">Selamat Datang, {{ $user->name }} </h3>
            <p class="text-secondary mb-0">Periode: {{ $periode }}</p>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stats-card h-100" style="border-left: 4px solid #5B9BD5;">
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="bi bi-clipboard-data stats-icon" style="color: #5B9BD5;"></i>
                        </div>
                        <h6 class="text-secondary-gkm mb-2">Total Laporan Kuisioner</h6>
                        <h2 class="mb-1" style="color: #5B9BD5;">{{ $totalQuestionnaires ?? 0 }}</h2>
                        <small class="text-muted">Jumlah total laporan kuisioner yang telah dibuat</small>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="stats-card h-100" style="border-left: 4px solid #28a745;">
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="bi bi-file-earmark-text stats-icon" style="color: #28a745;"></i>
                        </div>
                        <h6 class="text-secondary-gkm mb-2">Total Laporan Bulanan</h6>
                        <h2 class="mb-1" style="color: #28a745;">{{ $totalMonthlyReports ?? 0 }}</h2>
                        <small class="text-muted">Jumlah total laporan bulanan (GKM) yang telah dibuat</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="content-card mb-4 action-card">
            <h6 class="mb-3 font-semibold">Aksi Cepat</h6>
            <div class="action-grid">
                <div class="action-item">
                    <a href="{{ route('gkm.laporan-artefak.index') }}" class="action-btn primary-dark">
                        <span class="action-icon"><i class="bi bi-file-earmark-pdf"></i></span>
                        <span class="action-text">
                            <span class="action-title">Generate Laporan Bulanan</span>
                            <span class="action-subtitle">Susun laporan GKM bulanan</span>
                        </span>
                    </a>
                </div>

                <div class="action-item">
                    <a href="{{ route('gkm.laporan-kuesioner.index') }}" class="action-btn primary-soft">
                        <span class="action-icon"><i class="bi bi-file-earmark-text"></i></span>
                        <span class="action-text">
                            <span class="action-title">Generate Laporan Kuesioner</span>
                            <span class="action-subtitle">Susun laporan hasil kuesioner dengan cepat</span>
                        </span>
                    </a>
                </div>

                <div class="action-item">
                    <a href="{{ route('gkm.monitoring-rps.ceklist') }}" class="action-btn success-gradient">
                        <span class="action-icon"><i class="bi bi-send"></i></span>
                        <span class="action-text">
                            <span class="action-title">Kirim Reminder RPS</span>
                            <span class="action-subtitle">Pantau dan kirim pengingat RPS</span>
                        </span>
                    </a>
                </div>

                <div class="action-item">
                    <a href="{{ route('gkm.monitoring-perkuliahan.kirim-pengingat') }}" class="action-btn warning-gradient">
                        <span class="action-icon"><i class="bi bi-send"></i></span>
                        <span class="action-text">
                            <span class="action-title">Kirim Reminder Materi</span>
                            <span class="action-subtitle">Kirim pengingat materi perkuliahan</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Analytics Section -->
        <div class="content-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="mb-0 font-semibold">Analytics Monitoring</h6>
                
            </div>

            <!-- Filter -->
            <form method="GET" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="filter-label">Tahun Akademik</label>
                        <select name="tahun" class="form-select">
                            <option value="">Semua Tahun</option>
                            @if (isset($listTahun))
                                @foreach ($listTahun as $item)
                                    <option value="{{ $item }}" {{ $tahun == $item ? 'selected' : '' }}>
                                        {{ $item }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="filter-label">Semester</label>
                        <select name="semester" class="form-select">
                            <option value="">Semua Semester</option>
                            @if (isset($listSemester))
                                @foreach ($listSemester as $item)
                                    <option value="{{ $item }}" {{ $semester == $item ? 'selected' : '' }}>
                                        {{ $item }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-search me-1"></i> Cari
                        </button>
                    </div>
                </div>
            </form>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card" style="border-left: 4px solid #0d6efd;">
                        <small class="text-muted">Rata-rata Kepatuhan Upload Materi & RPS</small>
                        <h2 class="fw-bold text-primary mt-2">{{ $analyticsStats['avg_kepatuhan'] ?? 0 }}%</h2>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card" style="border-left: 4px solid #28a745;">
                        <small class="text-muted">Total Mata Kuliah</small>
                        <h2 class="fw-bold text-success mt-2">{{ $totalMatakuliahFromMaster ?? $analyticsStats['total_mata_kuliah'] ?? 0 }}</h2>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card" style="border-left: 4px solid #ffc107;">
                        <small class="text-muted">Total Dosen</small>
                        <h2 class="fw-bold text-warning mt-2">{{ $totalDosenFromMaster ?? $analyticsStats['total_dosen'] ?? 0 }}</h2>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card" style="border-left: 4px solid #dc3545;">
                        <small class="text-muted">Rata-rata Kepuasan Prodi</small>
                        <h2 class="fw-bold text-danger mt-2">{{ $kepuasanProdi ?? 0 }}%</h2>
                    </div>
                </div>
            </div>

            @if (($analyticsStats['total_records'] ?? 0) === 0)
                <div class="alert alert-warning mb-4">Tidak ada data monitoring perkuliahan untuk filter yang dipilih.
                </div>
            @endif

            <!-- Charts Row -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-3">
                    <div class="stats-card h-100">
                        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-3 gap-3">
                            <div>
                                <h6 class="fw-semibold mb-1">Kepatuhan per Mata Kuliah</h6>
                                <small class="text-muted">Insight: Bar warna hijau = kepatuhan tinggi (≥80%), merah = perlu perhatian (<50%). Hover bar untuk lihat dosen pengampu.</small>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <label for="filterTingkatMatakuliah" class="mb-0 text-muted">Filter Tingkat</label>
                                <select id="filterTingkatMatakuliah" class="form-select form-select-sm">
                                    <option value="">Semua Tingkat</option>
                                    @foreach(collect($groupByTingkat ?? [])->keys() as $tingkat)
                                        <option value="{{ $tingkat }}" {{ $tingkat == 1 ? 'selected' : '' }}>
                                            {{ $tingkat }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="sortOrderMatakuliah" class="mb-0 text-muted">Urutkan</label>
                                <select id="sortOrderMatakuliah" class="form-select form-select-sm">
                                    <option value="desc">Tertinggi ke Terendah</option>
                                    <option value="asc">Terendah ke Tertinggi</option>
                                </select>
                            </div>
                        </div>
                    <canvas id="chartMatakuliah" height="200"></canvas>
                </div>
            </div>

                <div class="col-lg-6 mb-3">
                    <div class="stats-card h-100">
                        <h6 class="fw-semibold mb-3">Ringkasan per Tingkat</h6>
                        <small class="text-muted">Rangkuman rata-rata kepatuhan per tingkat berdasarkan data monitoring prodi.</small>
                        <canvas id="chartTingkat" height="180"></canvas>
                    </div>
                </div>
            </div>

            <!-- Trend Chart -->
            <div class="stats-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-semibold mb-0">Trend Kepatuhan</h6>
                    <span class="badge bg-success">Trend</span>
                </div>
                <canvas id="trendChart" height="80"></canvas>
            </div>

                {{-- <div class="action-item analytics">
                    <a href="{{ route('gkm.dashboard.analytics') }}" class="action-btn info-bright">
                        <span class="action-icon"><i class="bi bi-graph-up"></i></span>
                        <span class="action-text">
                            <span class="action-title">Analytics Monitoring</span>
                            <span class="action-subtitle">Lihat tren dan performa monitoring</span>
                        </span>
                    </a>
                </div> --}}
            </div>
        </div>

        <!-- Quick Links -->
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="content-card h-100">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-journal-richtext quick-icon" style="color: #1e3c72;"></i>
                        <h6 class="ms-3 mb-0 font-semibold">Monitoring Materi</h6>
                    </div>
                    <p class="text-secondary mb-3">Monitor progres materi perkuliahan dan kirim pengingat</p>
                    <a href="{{ route('gkm.monitoring-perkuliahan.index') }}" class="btn btn-sm"
                        style="background-color: #1e3c72; color: white; border: none;">
                        Lihat Monitoring <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="content-card h-100">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-clipboard-check quick-icon" style="color: #1e3c72;"></i>
                        <h6 class="ms-3 mb-0 font-semibold">Monitoring RPS</h6>
                    </div>
                    <p class="text-secondary mb-3">Monitor status upload RPS dan materi dosen</p>
                    <a href="{{ route('gkm.monitoring-rps.index') }}" class="btn btn-sm"
                        style="background-color: #1e3c72; color: white; border: none;">
                        Lihat Monitoring <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="content-card h-100">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-bell quick-icon" style="color: #1e3c72;"></i>
                        <h6 class="ms-3 mb-0 font-semibold">Reminder Agent</h6>
                    </div>
                    <p class="text-secondary mb-3">Atur jadwal dan kirim reminder otomatis</p>
                    <a href="{{ route('gkm.reminder-agent.index') }}" class="btn btn-sm"
                        style="background-color: #1e3c72; color: white; border: none;">
                        Atur Reminder <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Chart Ringkasan per Tingkat
            const tingkatData = @json($groupByTingkat ?? collect());
            const tingkatLabels = Object.keys(tingkatData);
            const tingkatValues = Object.values(tingkatData);

            console.log('Tingkat Data:', tingkatData);
            console.log('Tingkat Labels:', tingkatLabels);
            console.log('Tingkat Values:', tingkatValues);

            if (tingkatLabels.length > 0) {
                new Chart(document.getElementById('chartTingkat'), {
                    type: 'bar',
                    data: {
                        labels: tingkatLabels.map(label => 'Tingkat ' + label),
                        datasets: [{
                            label: 'Ringkasan per Tingkat',
                            data: tingkatValues,
                            backgroundColor: '#0d6efd',
                            borderRadius: 10,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Ringkasan: ' + context.parsed.y + '%';
                                    }
                                }
                            }
                        }
                    }
                });
            } else {
                const canvas = document.getElementById('chartTingkat');
                const ctx = canvas.getContext('2d');
                ctx.font = '14px Arial';
                ctx.fillStyle = '#6c757d';
                ctx.textAlign = 'center';
                ctx.fillText('Tidak ada data untuk ditampilkan', canvas.width / 2, canvas.height / 2);
            }

            // Chart Kepatuhan per Mata Kuliah (Horizontal Bar)
            const matakuliahDetails = @json($matakuliahDetails ?? collect());
            const mkDosenMap = @json($dosenPerMatakuliah ?? collect());

            let matakuliahChart = null;

            function getBarColor(value) {
                if (value >= 80) {
                    return '#28a745';
                }

                if (value < 50) {
                    return '#dc3545';
                }

                return '#ffc107';
            }

            function formatMatakuliahChartData(details) {
                return {
                    labels: details.map(item => item.nama_matkul.length > 25 ? item.nama_matkul.substring(0, 23) + '...' : item.nama_matkul),
                    values: details.map(item => item.avg_kepatuhan),
                    backgroundColors: details.map(item => getBarColor(item.avg_kepatuhan)),
                    rawNames: details.map(item => item.nama_matkul),
                    dosenList: details.map(item => item.dosen.join(', ')),
                };
            }

            function renderMatakuliahChart(details) {
                const chartData = formatMatakuliahChartData(details);
                const ctxMK = document.getElementById('chartMatakuliah');

                if (matakuliahChart) {
                    matakuliahChart.data.labels = chartData.labels;
                    matakuliahChart.data.datasets[0].data = chartData.values;
                    matakuliahChart.data.datasets[0].backgroundColor = chartData.backgroundColors;
                    matakuliahChart.update();
                    return;
                }

                matakuliahChart = new Chart(ctxMK, {
                    type: 'bar',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: 'Kepatuhan Upload (%)',
                            data: chartData.values,
                            backgroundColor: chartData.backgroundColors,
                            borderRadius: 8,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            x: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const index = context.dataIndex;
                                        const value = context.parsed.x;
                                        const dosen = chartData.dosenList[index] || 'N/A';
                                        return [
                                            'Kepatuhan: ' + value + '%',
                                            'Dosen: ' + dosen
                                        ];
                                    }
                                }
                            }
                        }
                    }
                });
            }

            function applyMatakuliahFilter() {
                const filterElement = document.getElementById('filterTingkatMatakuliah');
                const sortElement = document.getElementById('sortOrderMatakuliah');
                const selectedTingkat = filterElement ? filterElement.value : '';
                const sortOrder = sortElement ? sortElement.value : 'desc';

                let filtered = selectedTingkat ? matakuliahDetails.filter(item => item.tingkat == selectedTingkat) : [...matakuliahDetails];
                filtered.sort((a, b) => {
                    return sortOrder === 'asc'
                        ? a.avg_kepatuhan - b.avg_kepatuhan
                        : b.avg_kepatuhan - a.avg_kepatuhan;
                });

                if (filtered.length > 0) {
                    renderMatakuliahChart(filtered);
                } else {
                    if (matakuliahChart) {
                        matakuliahChart.destroy();
                        matakuliahChart = null;
                    }
                    const canvas = document.getElementById('chartMatakuliah');
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.font = '14px Arial';
                    ctx.fillStyle = '#6c757d';
                    ctx.textAlign = 'center';
                    ctx.fillText('Tidak ada data untuk ditampilkan', canvas.width / 2, canvas.height / 2);
                }
            }

            const filterElement = document.getElementById('filterTingkatMatakuliah');
            const sortElement = document.getElementById('sortOrderMatakuliah');
            if (filterElement) {
                filterElement.addEventListener('change', applyMatakuliahFilter);
            }
            if (sortElement) {
                sortElement.addEventListener('change', applyMatakuliahFilter);
            }

            applyMatakuliahFilter();

            // Chart Trend Kepatuhan
            const trendData = @json($trendSemester ?? collect());
            const trendLabels = Object.keys(trendData).map(key => 'Semester ' + key);
            const trendValues = Object.values(trendData);

            console.log('Trend Data (raw):', trendData);
            console.log('Trend Labels:', trendLabels);
            console.log('Trend Values:', trendValues);
            console.log('Trend Labels length:', trendLabels.length);
            console.log('Trend Values length:', trendValues.length);

            if (trendLabels.length > 0 && trendValues.length > 0) {
                new Chart(document.getElementById('trendChart'), {
                    type: 'line',
                    data: {
                        labels: trendLabels,
                        datasets: [{
                            label: 'Trend Kepatuhan (%)',
                            data: trendValues,
                            borderColor: '#198754',
                            backgroundColor: 'rgba(25, 135, 84, 0.15)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#198754',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            },
                            x: {
                                ticks: {
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Kepatuhan: ' + context.parsed.y + '%';
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('Trend chart created successfully');
            } else {
                // Jika tidak ada data, tampilkan pesan
                const trendCanvas = document.getElementById('trendChart');
                const ctx = trendCanvas.getContext('2d');
                ctx.font = '16px Arial';
                ctx.fillStyle = '#6c757d';
                ctx.textAlign = 'center';
                ctx.fillText('Tidak ada data trend untuk ditampilkan', trendCanvas.width / 2, trendCanvas.height /
                    2);
                console.log('No trend data available');
            }
        });
    </script>
@endsection
