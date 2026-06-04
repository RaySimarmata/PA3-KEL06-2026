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
            color: #ffffff; /* enforce white text for all action buttons */
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
            color: #ffffff; /* ensure title/subtitle inherit white */
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
            color: rgba(255,255,255,0.9);
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

        .dashboard-shell .action-btn.info-bright {
            background: linear-gradient(135deg, #10bfe8 0%, #22c5eb 100%);
            color: #ffffff;
        }

        .dashboard-shell .action-btn.info-bright:hover {
            color: #ffffff;
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

            .dashboard-shell .action-item.analytics {
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
                    <a href="{{ route('gkm.laporan-kuesioner.index') }}" class="action-btn primary-dark">
                        <span class="action-icon"><i class="bi bi-file-earmark-pdf"></i></span>
                        <span class="action-text">
                            <span class="action-title">Generate Laporan Bulanan</span>
                            <span class="action-subtitle">Buat ringkasan laporan GKM bulanan</span>
                        </span>
                    </a>
                </div>

                <div class="action-item">
                    <a href="{{ route('gkm.laporan-kuesioner.create') }}" class="action-btn primary-dark">
                        <span class="action-icon"><i class="bi bi-file-earmark-text"></i></span>
                        <span class="action-text">
                            <span class="action-title">Generate Laporan Kuesioner</span>
                            <span class="action-subtitle">Susun laporan hasil kuesioner dengan cepat</span>
                        </span>
                    </a>
                </div>

                <div class="action-item">
                    <a href="{{ route('gkm.monitoring-rps.ceklist') }}" class="action-btn primary-soft">
                        <span class="action-icon"><i class="bi bi-send"></i></span>
                        <span class="action-text">
                            <span class="action-title">Kirim Reminder RPS</span>
                            <span class="action-subtitle">Pantau dan kirim pengingat RPS</span>
                        </span>
                    </a>
                </div>

                <div class="action-item">
                    <a href="{{ route('gkm.monitoring-perkuliahan.kirim-pengingat') }}" class="action-btn primary-soft">
                        <span class="action-icon"><i class="bi bi-send"></i></span>
                        <span class="action-text">
                            <span class="action-title">Kirim Reminder Materi</span>
                            <span class="action-subtitle">Kirim pengingat materi perkuliahan</span>
                        </span>
                    </a>
                </div>

                <div class="action-item analytics">
                    <a href="{{ route('gkm.dashboard.analytics') }}" class="action-btn info-bright">
                        <span class="action-icon"><i class="bi bi-graph-up"></i></span>
                        <span class="action-text">
                            <span class="action-title">Analytics Monitoring</span>
                            <span class="action-subtitle">Lihat tren dan performa monitoring</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="content-card h-100">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-journal-richtext quick-icon" style="color: #5B9BD5;"></i>
                        <h6 class="ms-3 mb-0 font-semibold">Monitoring Materi</h6>
                    </div>
                    <p class="text-secondary mb-3">Monitor progres materi perkuliahan dan kirim pengingat</p>
                    <a href="{{ route('gkm.monitoring-perkuliahan.index') }}" class="btn btn-outline-primary btn-sm">
                        Lihat Monitoring <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="content-card h-100">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-clipboard-check quick-icon" style="color: #5B9BD5;"></i>
                        <h6 class="ms-3 mb-0 font-semibold">Monitoring RPS</h6>
                    </div>
                    <p class="text-secondary mb-3">Monitor status upload RPS dan materi dosen</p>
                    <a href="{{ route('gkm.monitoring-rps.index') }}" class="btn btn-outline-primary btn-sm">
                        Lihat Monitoring <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="content-card h-100">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-bell quick-icon" style="color: #5B9BD5;"></i>
                        <h6 class="ms-3 mb-0 font-semibold">Reminder Agent</h6>
                    </div>
                    <p class="text-secondary mb-3">Atur jadwal dan kirim reminder otomatis</p>
                    <a href="{{ route('gkm.reminder-agent.index') }}" class="btn btn-outline-primary btn-sm">
                        Atur Reminder <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
