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
        <div class="content-card mb-4">
            <h6 class="mb-3 font-semibold">Aksi Cepat</h6>
            <div class="row">
                <div class="col-12 col-sm-6 col-xl-3 mb-3">
                    <a href="{{ route('gkm.laporan-kuesioner.index') }}" class="btn btn-primary w-100 py-3">
                        <i class="bi bi-file-earmark-pdf"></i> Generate Laporan Bulanan
                    </a>
                </div>
                <div class="col-12 col-sm-6 col-xl-3 mb-3">
                    <a href="{{ route('gkm.laporan-kuesioner.create') }}" class="btn btn-primary w-100 py-3">
                        <i class="bi bi-file-earmark-text"></i> Generate Laporan Kuesioner
                    </a>
                </div>
                <div class="col-12 col-sm-6 col-xl-3 mb-3">
                    <a href="{{ route('gkm.monitoring-rps.ceklist') }}" class="btn-reminder w-100 py-3"
                        style="display: flex;">
                        <i class="bi bi-send"></i> Kirim Reminder RPS
                    </a>
                </div>
                <div class="col-12 col-sm-6 col-xl-3 mb-3">
                    <a href="{{ route('gkm.monitoring-perkuliahan.kirim-pengingat') }}" class="btn-reminder w-100 py-3"
                        style="display: flex;">
                        <i class="bi bi-send"></i> Kirim Reminder Materi Perkuliahan
                    </a>
                </div>
                <div class="col-12 col-sm-6 col-xl-3 mb-3">
                    <a href="{{ route('gkm.dashboard.analytics') }}" class="btn btn-info w-100 py-3"
                        style="display: flex; justify-content: center; align-items: center; gap: .5rem;">
                        <i class="bi bi-graph-up"></i> Analytics Monitoring
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
