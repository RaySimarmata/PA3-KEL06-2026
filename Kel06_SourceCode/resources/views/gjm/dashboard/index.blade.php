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

        .dosen-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 18px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        }

        .dosen-card-toggle {
            width: 100%;
            border: 0;
            background: transparent;
            padding: 1rem 1.1rem;
            text-align: left;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .dosen-card-toggle:hover {
            background: #f8fafc;
        }

        .dosen-card-toggle:focus {
            outline: none;
        }

        .dosen-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: #f1f5f9;
            color: #334155;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .dosen-detail-panel {
            border-top: 1px solid rgba(15, 23, 42, 0.08);
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            padding: 1rem 1.1rem 1.1rem;
        }

        .dosen-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem 1rem;
        }

        .dosen-detail-item {
            padding: 0.8rem 0.9rem;
            border-radius: 14px;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
        }

        .dosen-detail-label {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #94a3b8;
            margin-bottom: 0.25rem;
        }

        .dosen-detail-value {
            font-weight: 600;
            color: #0f172a;
            word-break: break-word;
        }

        .dosen-card-accent-primary {
            border-left: 4px solid #0d6efd;
        }

        .dosen-card-accent-danger {
            border-left: 4px solid #dc3545;
        }

        @media (max-width: 576px) {
            .dosen-detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    <div class="dashboard-shell">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Welcome Section dengan tombol di kanan (sama seperti GKM) -->
        <div class="content-card mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h3 class="hero-title mb-1" style="font-weight: 600; color: #333;">
                        Selamat Datang, {{ $user->name }}
                    </h3>
                    <p class="text-secondary mb-0">Periode: {{ $periode }}</p>
                </div>
                <div class="d-flex gap-2">
                    <!-- <form action="{{ route('gjm.clear-cache') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-warning shadow-sm d-inline-flex align-items-center gap-2">
                            <i class="bi bi-arrow-clockwise"></i> Clear Cache
                        </button>
                    </form> -->
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
                                <option value="{{ $item }}" {{ (request('tahun', '2025') == $item) ? 'selected' : '' }}>
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
            {{-- <div class="col-lg-3 col-md-6 mb-3">
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
            </div> --}}
            {{-- <div class="col-lg-3 col-md-6 mb-3">
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
            </div> --}}
            <div class="col-lg-6 col-md-4 mb-5">
                <div class="stats-card h-100" style="border-left: 4px solid #ffc107;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Persentase Kepuasan Mahasiswa</small>
                            <h2 class="fw-bold text-warning mt-2">{{ $stats['kepuasan_mahasiswa'] ?? 0 }}%</h2>
                        </div>
                        <div class="icon-box bg-warning-subtle">
                            <i class="bi bi-emoji-smile text-warning stats-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-4 mb-5">
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

        {{-- <!-- Matkul Bermasalah -->
        <div class="stats-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-semibold mb-0">Mata Kuliah Bermasalah</h5>
                <span class="badge bg-danger">Problem List</span>
            </div>
            <div class="d-grid gap-3">
                @forelse ($matkulBermasalah as $item)
                    <div class="dosen-card dosen-card-accent-danger">
                        <div class="dosen-card-toggle">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <div class="dosen-pill mb-2">
                                        <i class="bi bi-journal-text"></i>
                                        {{ $item['kode_mk'] }}
                                    </div>
                                    <h6 class="fw-semibold mb-1">{{ $item['nama_matkul'] }}</h6>
                                    <div class="text-secondary small">{{ $item['dosen'] }}</div>
                                </div>
                                <div class="text-md-end">
                                    <span class="badge bg-warning text-dark">Avg {{ $item['avg'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-light border mb-0">Tidak ada mata kuliah bermasalah.</div>
                @endforelse
            </div>
        </div> --}}

        {{-- <!-- Heatmap Dosen -->
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
        </div> --}}

        <!-- Dosen Pengampu per Prodi (3 Kolom: TI, NM, TRPL) -->
        <div class="row mb-4">
            @php
                $prodiOrder = ['TI', 'NM', 'TRPL'];
                $prodiNames = [
                    'TI' => 'Teknik Informatika',
                    'NM' => 'Teknologi Komputer',
                    'TRPL' => 'Teknologi Rekayasa Perangkat Lunak'
                ];
            @endphp

            @foreach ($prodiOrder as $prodiKode)
                @php
                    $dosenList = $dosenPerProdi->get($prodiKode, collect());
                    $totalDosen = $dosenList->count();
                    $showLimit = 5;
                @endphp

                <div class="col-lg-4 mb-3">
                    <div class="stats-card h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-semibold mb-0">{{ $prodiKode }}</h5>
                            <span class="badge bg-primary">
                                {{ $totalDosen }} Dosen
                            </span>
                        </div>
                        <p class="text-muted small mb-3">{{ $prodiNames[$prodiKode] }}</p>

                        <div class="d-grid gap-3" id="dosenList{{ $prodiKode }}">
                            @forelse ($dosenList as $item)
                                <div class="dosen-card dosen-card-accent-primary dosen-item-{{ $prodiKode }} {{ $loop->index >= $showLimit ? 'dosen-hidden' : '' }}" 
                                     style="{{ $loop->index >= $showLimit ? 'display: none;' : '' }}">
                                    <button class="dosen-card-toggle" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#dosen{{ $prodiKode }}Detail{{ $loop->index }}" 
                                            aria-expanded="false" 
                                            aria-controls="dosen{{ $prodiKode }}Detail{{ $loop->index }}">
                                        <div class="d-flex flex-column gap-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <div class="dosen-pill mb-2">
                                                        <i class="bi bi-person-badge"></i>
                                                        {{ $item['inisial'] }}
                                                    </div>
                                                    <h6 class="fw-semibold mb-1">{{ $item['nama'] }}</h6>
                                                    @if (!empty($item['prodi_matkul_list']) && count($item['prodi_matkul_list']) > 1)
                                                        <div class="text-secondary small">
                                                            <i class="bi bi-arrow-left-right"></i> 
                                                            Mengajar di {{ count($item['prodi_matkul_list']) }} prodi
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <span class="badge bg-warning text-dark">Avg {{ $item['avg'] }}</span>
                                                <span class="badge bg-success">{{ $item['kepuasan'] }}%</span>
                                                <span class="badge bg-secondary">{{ $item['jumlah_matkul'] }} MK</span>
                                            </div>
                                        </div>
                                    </button>
                                    <div class="collapse" id="dosen{{ $prodiKode }}Detail{{ $loop->index }}">
                                        <div class="dosen-detail-panel">
                                            <div class="dosen-detail-grid">
                                                <div class="dosen-detail-item">
                                                    <span class="dosen-detail-label">Nama Lengkap</span>
                                                    <div class="dosen-detail-value">{{ $item['dosen_mysql']['nama_lengkap'] ?? $item['nama'] }}</div>
                                                </div>
                                                <div class="dosen-detail-item">
                                                    <span class="dosen-detail-label">NIDN</span>
                                                    <div class="dosen-detail-value">{{ $item['dosen_mysql']['nidn'] ?? '-' }}</div>
                                                </div>
                                                <div class="dosen-detail-item">
                                                    <span class="dosen-detail-label">Email</span>
                                                    <div class="dosen-detail-value">{{ $item['dosen_mysql']['email'] ?? '-' }}</div>
                                                </div>
                                                <div class="dosen-detail-item">
                                                    <span class="dosen-detail-label">Jabatan</span>
                                                    <div class="dosen-detail-value">{{ $item['dosen_mysql']['jabatan_akademik_desc'] ?? $item['dosen_mysql']['jabatan_akademik'] ?? '-' }}</div>
                                                </div>
                                                <div class="dosen-detail-item">
                                                    <span class="dosen-detail-label">Jenjang</span>
                                                    <div class="dosen-detail-value">{{ $item['dosen_mysql']['jenjang_pendidikan'] ?? '-' }}</div>
                                                </div>
                                                <div class="dosen-detail-item">
                                                    <span class="dosen-detail-label">Prodi Dosen</span>
                                                    <div class="dosen-detail-value">{{ $item['dosen_mysql']['prodi'] ?? '-' }}</div>
                                                </div>
                                                <div class="dosen-detail-item" style="grid-column: 1 / -1;">
                                                    <span class="dosen-detail-label">Mengajar di Prodi</span>
                                                    <div class="dosen-detail-value">
                                                        @if (!empty($item['prodi_matkul_list']))
                                                            @foreach ($item['prodi_matkul_list'] as $prodiMk)
                                                                <span class="badge bg-primary me-1 mb-1">{{ $prodiMk }}</span>
                                                            @endforeach
                                                        @else
                                                            -
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="dosen-detail-item" style="grid-column: 1 / -1;">
                                                    <span class="dosen-detail-label">Kode Mata Kuliah</span>
                                                    <div class="dosen-detail-value text-wrap">{{ !empty($item['kode_matkul']) ? implode(', ', $item['kode_matkul']) : '-' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="alert alert-light border mb-0 small">
                                    <i class="bi bi-info-circle me-2"></i>Belum ada data dosen untuk prodi {{ $prodiKode }}.
                                </div>
                            @endforelse
                        </div>

                        @if ($totalDosen > $showLimit)
                            <div class="text-center mt-3">
                                <button class="btn btn-outline-primary btn-sm w-100" 
                                        onclick="toggleShowMore{{ $prodiKode }}()"
                                        id="btnShowMore{{ $prodiKode }}">
                                    <i class="bi bi-chevron-down me-1"></i>
                                    Lihat Selengkapnya ({{ $totalDosen - $showLimit }} lainnya)
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <script>
            // Script untuk toggle show more/less setiap prodi
            @foreach ($prodiOrder as $prodiKode)
                @php
                    $dosenList = $dosenPerProdi->get($prodiKode, collect());
                    $totalDosen = $dosenList->count();
                @endphp
                @if ($totalDosen > 5)
                    let isExpanded{{ $prodiKode }} = false;
                    
                    function toggleShowMore{{ $prodiKode }}() {
                        const hiddenItems = document.querySelectorAll('.dosen-item-{{ $prodiKode }}.dosen-hidden');
                        const btn = document.getElementById('btnShowMore{{ $prodiKode }}');
                        
                        isExpanded{{ $prodiKode }} = !isExpanded{{ $prodiKode }};
                        
                        hiddenItems.forEach(item => {
                            if (isExpanded{{ $prodiKode }}) {
                                item.style.display = '';
                            } else {
                                item.style.display = 'none';
                            }
                        });
                        
                        if (isExpanded{{ $prodiKode }}) {
                            btn.innerHTML = '<i class="bi bi-chevron-up me-1"></i>Lihat Lebih Sedikit';
                        } else {
                            btn.innerHTML = '<i class="bi bi-chevron-down me-1"></i>Lihat Selengkapnya ({{ $totalDosen - 5 }} lainnya)';
                        }
                    }
                @endif
            @endforeach
        </script>

        <!-- Pertanyaan Terburuk
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
        </div> -->

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
