@extends('layouts.app')

@section('page-title', 'Dashboard GJM')

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
                            <i class="bi bi-building" style="font-size: 40px; color: #28a745;"></i>
                        </div>
                        <h6 class="text-secondary-gkm mb-2">Total Program Studi</h6>
                        <h2 class="mb-2" style="color: #28a745;">{{ $stats['total_prodi'] }}</h2>
                        <small class="text-muted">Program Studi</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stats-card h-100" style="border-left: 4px solid #5B9BD5;">
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="bi bi-file-earmark-text" style="font-size: 40px; color: #5B9BD5;"></i>
                        </div>
                        <h6 class="text-secondary-gkm mb-2">Laporan GJM</h6>
                        <h2 class="mb-2" style="color: #5B9BD5;">{{ $stats['laporan_gjm'] }}</h2>
                        <small class="text-muted">Total Laporan</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stats-card h-100" style="border-left: 4px solid #17a2b8;">
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="bi bi-file-earmark-check" style="font-size: 40px; color: #17a2b8;"></i>
                        </div>
                        <h6 class="text-secondary-gkm mb-2">Template Aktif</h6>
                        <h2 class="mb-2" style="color: #17a2b8;">{{ $stats['template_aktif'] }}</h2>
                        <small class="text-muted">dari {{ $stats['template_total'] }} template</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stats-card h-100" style="border-left: 4px solid #ffc107;">
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="bi bi-gear" style="font-size: 40px; color: #ffc107;"></i>
                        </div>
                        <h6 class="text-secondary-gkm mb-2">Status Sistem</h6>
                        <h4 class="mb-2" style="color: #ffc107;">Aktif</h4>
                        <small class="text-muted">Sistem Berjalan Normal</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="content-card mb-4">
            <h6 class="mb-3 font-semibold">Aksi Cepat</h6>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <a href="{{ route('gjm.buat-laporan.index') }}" class="btn btn-primary w-100 py-3">
                        <i class="bi bi-file-earmark-plus"></i>
                        <div class="mt-1">Buat Laporan</div>
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="{{ route('gjm.buat-ppt.index') }}" class="btn btn-info w-100 py-3">
                        <i class="bi bi-file-ppt"></i>
                        <div class="mt-1">Generate PPT</div>
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="{{ route('gjm.laporan-gjm.index') }}" class="btn btn-success w-100 py-3">
                        <i class="bi bi-eye"></i>
                        <div class="mt-1">Lihat Laporan</div>
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="{{ route('gjm.kirim-laporan.index') }}" class="btn btn-warning w-100 py-3">
                        <i class="bi bi-send"></i>
                        <div class="mt-1">Kirim Laporan</div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="content-card h-100">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-file-earmark-text" style="font-size: 30px; color: #5B9BD5;"></i>
                        <h6 class="ms-3 mb-0 font-semibold">Buat Laporan</h6>
                    </div>
                    <p class="text-secondary mb-3">Buat laporan semester dan triwulan dengan AI</p>
                    <a href="{{ route('gjm.buat-laporan.index') }}" class="btn btn-outline-primary btn-sm">
                        Mulai Buat <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="content-card h-100">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-file-ppt" style="font-size: 30px; color: #5B9BD5;"></i>
                        <h6 class="ms-3 mb-0 font-semibold">Generate PPT</h6>
                    </div>
                    <p class="text-secondary mb-3">Generate presentasi PowerPoint otomatis</p>
                    <a href="{{ route('gjm.buat-ppt.index') }}" class="btn btn-outline-primary btn-sm">
                        Generate PPT <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="content-card h-100">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-eye" style="font-size: 30px; color: #5B9BD5;"></i>
                        <h6 class="ms-3 mb-0 font-semibold">Lihat Laporan</h6>
                    </div>
                    <p class="text-secondary mb-3">Lihat dan kelola laporan yang sudah dibuat</p>
                    <a href="{{ route('gjm.laporan-gjm.index') }}" class="btn btn-outline-primary btn-sm">
                        Lihat Laporan <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection