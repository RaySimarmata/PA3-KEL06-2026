@extends('layouts.app')

@section('page-title', 'Buat Laporan')

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Header Card -->
        <div class="filter-card mb-4">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-file-earmark-text" style="color: #5B9BD5; font-size: 2rem;"></i>
                <div>
                    <h5 class="mb-1" style="font-weight: 600; color: #333;">Buat Laporan GJM</h5>
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">Pilih jenis laporan yang ingin Anda buat</p>
                </div>
            </div>
        </div>

        <!-- Pilihan Jenis Laporan -->
        <div class="row">
            <!-- Buat Laporan Triwulan -->
            <div class="col-md-6 mb-4">
                <a href="{{ route('gjm.buat-laporan.triwulan.index') }}" class="text-decoration-none">
                    <div class="monitoring-card" style="cursor: pointer; transition: all 0.3s ease; border-left: 4px solid #5B9BD5;">
                        <div style="padding: 2rem;">
                            <div class="d-flex align-items-start gap-3">
                                <i class="bi bi-calendar3" style="font-size: 3rem; color: #5B9BD5;"></i>
                                <div>
                                    <h5 class="mb-2" style="font-weight: 600; color: #333;">Buat Laporan Triwulan</h5>
                                    <p class="text-muted mb-3" style="font-size: 0.875rem;">
                                        Buat laporan triwulan (3 bulanan) dengan bantuan AI Assistant
                                    </p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge bg-primary">Triwulan I (Jan-Mar)</span>
                                        <span class="badge bg-primary">Triwulan II (Apr-Jun)</span>
                                        <span class="badge bg-primary">Triwulan III (Jul-Sep)</span>
                                        <span class="badge bg-primary">Triwulan IV (Okt-Des)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Buat Laporan Semester -->
            <div class="col-md-6 mb-4">
                <a href="{{ route('gjm.buat-laporan.semester.index') }}" class="text-decoration-none">
                    <div class="monitoring-card" style="cursor: pointer; transition: all 0.3s ease; border-left: 4px solid #28a745;">
                        <div style="padding: 2rem;">
                            <div class="d-flex align-items-start gap-3">
                                <i class="bi bi-calendar-range" style="font-size: 3rem; color: #28a745;"></i>
                                <div>
                                    <h5 class="mb-2" style="font-weight: 600; color: #333;">Buat Laporan Semester</h5>
                                    <p class="text-muted mb-3" style="font-size: 0.875rem;">
                                        Buat laporan semester (6 bulanan) dengan bantuan AI Assistant
                                    </p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge bg-success">Semester Ganjil (Agu-Jan)</span>
                                        <span class="badge bg-success">Semester Genap (Feb-Jul)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Buat Laporan VMTS -->
            <div class="col-md-6 mb-4">
                <a href="{{ route('gjm.buat-laporan.vmts.index') }}" class="text-decoration-none">
                    <div class="monitoring-card" style="cursor: pointer; transition: all 0.3s ease; border-left: 4px solid #dc3545;">
                        <div style="padding: 2rem;">
                            <div class="d-flex align-items-start gap-3">
                                <i class="bi bi-file-earmark-text" style="font-size: 3rem; color: #dc3545;"></i>
                                <div>
                                    <h5 class="mb-2" style="font-weight: 600; color: #333;">Buat Laporan VMTS</h5>
                                    <p class="text-muted mb-3" style="font-size: 0.875rem;">
                                        Buat laporan Visi Misi Tujuan Sasaran dengan bantuan AI Assistant
                                    </p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge bg-danger">Laporan Tahunan</span>
                                        <span class="badge bg-danger">Analisis VMTS</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Info Card -->
        <div class="monitoring-card mb-4" style="border-left: 4px solid #ffc107;">
            <div style="padding: 1.5rem;">
                <div class="d-flex align-items-start">
                    <i class="bi bi-lightbulb" style="color: #ffc107; font-size: 2rem; margin-right: 1rem;"></i>
                    <div>
                        <h6 class="mb-2" style="font-weight: 600; color: #333;">Fitur AI Assistant</h6>
                        <ul class="mb-0" style="font-size: 0.875rem; color: #495057; line-height: 1.8;">
                            <li>Upload multiple files (DOCX, PDF, TXT, Excel, JPG, PNG)</li>
                            <li>AI Vision untuk analisis gambar dokumentasi kegiatan</li>
                            <li>Integrasi otomatis dengan data laporan GKM bulanan</li>
                            <li>Chat interaktif dengan AI untuk revisi laporan</li>
                            <li>Generate laporan Word (.docx) dengan template yang dapat dikustomisasi</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .monitoring-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
    </style>
@endsection
