@extends('layouts.app')

@section('page-title', 'Dashboard GKM')

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Welcome Section -->
        <div class="content-card mb-4">
            <h3 class="mb-1" style="font-weight: 600; color: #333;">Selamat Datang, {{ $user->name }} 👋</h3>
            <p class="text-secondary mb-0">Periode: {{ $periode }}</p>
        </div>

        <!-- Status Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card h-100" style="border-left: 4px solid #28a745;">
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="bi bi-file-earmark-check" style="font-size: 40px; color: #28a745;"></i>
                        </div>
                        <h6 class="text-secondary-gkm mb-2">Status Upload Materi</h6>
                        <div class="d-flex justify-content-around mt-3">
                            <div>
                                <h4 class="mb-0" style="color: #28a745;">{{ $stats['materi_uploaded'] }}</h4>
                                <small class="text-muted">Sudah Upload</small>
                            </div>
                            <div>
                                <h4 class="mb-0" style="color: #dc3545;">{{ $stats['materi_belum'] }}</h4>
                                <small class="text-muted">Belum Upload</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stats-card h-100" style="border-left: 4px solid #5B9BD5;">
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="bi bi-journal-check" style="font-size: 40px; color: #5B9BD5;"></i>
                        </div>
                        <h6 class="text-secondary-gkm mb-2">Status RPS</h6>
                        <div class="d-flex justify-content-around mt-3">
                            <div>
                                <h4 class="mb-0" style="color: #28a745;">{{ $stats['rps_lengkap'] }}</h4>
                                <small class="text-muted">Lengkap</small>
                            </div>
                            <div>
                                <h4 class="mb-0" style="color: #dc3545;">{{ $stats['rps_belum'] }}</h4>
                                <small class="text-muted">Belum Lengkap</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stats-card h-100" style="border-left: 4px solid #17a2b8;">
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="bi bi-envelope-check" style="font-size: 40px; color: #17a2b8;"></i>
                        </div>
                        <h6 class="text-secondary-gkm mb-2">Status Reminder</h6>
                        <h2 class="mb-2" style="color: #5B9BD5;">{{ $stats['reminders_pending'] }} <small
                                class="text-muted">dari 4</small></h2>
                        <small class="text-muted">Reminder Pending</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stats-card h-100" style="border-left: 4px solid #ffc107;">
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="bi bi-star-fill" style="font-size: 40px; color: #ffc107;"></i>
                        </div>
                        <h6 class="text-secondary-gkm mb-2">Status Kuisioner</h6>
                        <h4 class="mb-2" style="color: #ffc107;">Sedang Berjalan</h4>
                        <small class="text-muted">{{ $stats['kuisioner_pengisi'] }} Kuisioner Aktif</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="content-card mb-4">
            <h6 class="mb-3 font-semibold">Aksi Cepat</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <a href="{{ route('gkm.laporan-kuesioner.index') }}" class="btn btn-primary w-100 py-3">
                        <i class="bi bi-file-earmark-pdf"></i> Generate Laporan Bulanan
                    </a>
                </div>
                <div class="col-md-6 mb-3">
                    <a href="{{ route('gkm.monitoring-rps.ceklist') }}" class="btn-reminder w-100 py-3"
                        style="display: flex;">
                        <i class="bi bi-send"></i> Kirim Reminder Sekarang
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="content-card h-100">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-people" style="font-size: 30px; color: #5B9BD5;"></i>
                        <h6 class="ms-3 mb-0 font-semibold">Data Master</h6>
                    </div>
                    <p class="text-secondary mb-3">Kelola data dosen, mata kuliah, dan periode akademik</p>
                    <a href="{{ route('gkm.data-master.penugasan-dosen') }}" class="btn btn-outline-primary btn-sm">
                        Kelola Data <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="content-card h-100">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-clipboard-check" style="font-size: 30px; color: #5B9BD5;"></i>
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
                        <i class="bi bi-bell" style="font-size: 30px; color: #5B9BD5;"></i>
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
