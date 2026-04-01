@extends('layouts.app')

@section('page-title', 'Dashboard GKM')

@section('content')
<div class="container-fluid">
    <!-- Welcome Section -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h3 class="mb-1">Selamat Datang, {{ $user->name }} 👋</h3>
            <p class="text-muted mb-0">Periode: {{ date('F Y') }}</p>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-file-earmark-check" style="font-size: 40px; color: #28a745;"></i>
                    </div>
                    <h6 class="text-secondary mb-2">Status Upload Materi</h6>
                    <div class="d-flex justify-content-around mt-3">
                        <div>
                            <h4 class="text-success mb-0">12</h4>
                            <small class="text-muted">Sudah Upload</small>
                        </div>
                        <div>
                            <h4 class="text-danger mb-0">6</h4>
                            <small class="text-muted">Belum Upload</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-journal-check" style="font-size: 40px; color: #007bff;"></i>
                    </div>
                    <h6 class="text-secondary mb-2">Status RPS</h6>
                    <div class="d-flex justify-content-around mt-3">
                        <div>
                            <h4 class="text-success mb-0">15</h4>
                            <small class="text-muted">Lengkap</small>
                        </div>
                        <div>
                            <h4 class="text-danger mb-0">3</h4>
                            <small class="text-muted">Belum Lengkap</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-envelope-check" style="font-size: 40px; color: #17a2b8;"></i>
                    </div>
                    <h6 class="text-secondary mb-2">Status Reminder</h6>
                    <h2 class="text-primary mb-2">2 <small class="text-muted">dari 4</small></h2>
                    <small class="text-muted">Reminder Belum Pending</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-star-fill" style="font-size: 40px; color: #ffc107;"></i>
                    </div>
                    <h6 class="text-secondary mb-2">Status Kuisioner</h6>
                    <h4 class="text-warning mb-2">Sedang Berjalan</h4>
                    <small class="text-muted">Periode Aktif</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="mb-3">Aksi Cepat</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <a href="{{ route('gkm.pelaporan.index') }}" class="btn btn-primary w-100 py-3">
                        <i class="bi bi-file-earmark-pdf"></i> Generate Laporan Bulanan
                    </a>
                </div>
                <div class="col-md-6 mb-3">
                    <a href="{{ route('gkm.monitoring-rps.ceklist') }}" class="btn btn-success w-100 py-3">
                        <i class="bi bi-send"></i> Kirim Reminder Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-people" style="font-size: 30px; color: #1e3c72;"></i>
                        <h6 class="ms-3 mb-0">Data Master</h6>
                    </div>
                    <p class="text-muted small mb-3">Kelola data dosen, mata kuliah, dan periode akademik</p>
                    <a href="{{ route('gkm.data-master.index') }}" class="btn btn-outline-primary btn-sm">
                        Kelola Data <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-clipboard-check" style="font-size: 30px; color: #1e3c72;"></i>
                        <h6 class="ms-3 mb-0">Monitoring RPS</h6>
                    </div>
                    <p class="text-muted small mb-3">Monitor status upload RPS dan materi dosen</p>
                    <a href="{{ route('gkm.monitoring-rps.index') }}" class="btn btn-outline-primary btn-sm">
                        Lihat Monitoring <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-bell" style="font-size: 30px; color: #1e3c72;"></i>
                        <h6 class="ms-3 mb-0">Reminder Agent</h6>
                    </div>
                    <p class="text-muted small mb-3">Atur jadwal dan kirim reminder otomatis</p>
                    <a href="{{ route('gkm.reminder-agent.index') }}" class="btn btn-outline-primary btn-sm">
                        Atur Reminder <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
