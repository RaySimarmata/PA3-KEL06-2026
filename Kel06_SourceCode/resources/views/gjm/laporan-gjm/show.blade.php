@extends('layouts.app')

@section('page-title', 'Detail Laporan')

@section('content')
<div class="container-fluid px-4">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                <div class="flex-grow-1">
                    <h3 class="mb-2 fw-bold text-dark">Detail Laporan</h3>
                    <p class="text-muted mb-0 fs-6">{{ $laporan->ringkasan_mutu_institusi ?? 'Laporan ' . $laporan->getJenisLaporanLabel() }}</p>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="{{ route('gjm.laporan-gjm.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                        <i class="bi bi-arrow-left"></i>
                        <span>Kembali</span>
                    </a>
                    @if($laporan->dokumen_hasil_path && $laporan->status_laporan === 'completed')
                    <a href="{{ route('gjm.buat-laporan.download.pdf', $laporan->id) }}" 
                       class="btn btn-success d-flex align-items-center gap-2">
                        <i class="bi bi-file-word"></i>
                        <span>Download Word</span>
                    </a>
                    @else
                    <button class="btn btn-secondary d-flex align-items-center gap-2" disabled>
                        <i class="bi bi-file-word"></i>
                        <span>Dokumen Belum Tersedia</span>
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-semibold">Informasi Laporan</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Jenis Laporan</label>
                            <p class="mb-0">
                                <span class="badge {{ $laporan->getStatusBadgeClass() }}">
                                    {{ $laporan->getJenisLaporanLabel() }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Status</label>
                            <p class="mb-0">
                                <span class="badge {{ $laporan->getStatusBadgeClass() }}">
                                    {{ ucfirst($laporan->status_laporan) }}
                                </span>
                            </p>
                        </div>
                    </div>

                    @if($laporan->ringkasan_mutu_institusi)
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted">Konten Laporan</label>
                        <div class="bg-light p-4 rounded" style="max-height: 600px; overflow-y: auto;">
                            <div style="white-space: pre-wrap; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.8;">{{ $laporan->ringkasan_mutu_institusi }}</div>
                        </div>
                    </div>
                    @endif

                    @if($laporan->analisis_kepatuhan)
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted">Analisis Kepatuhan</label>
                        <div class="bg-light p-3 rounded">
                            <p class="mb-0">{{ $laporan->analisis_kepatuhan }}</p>
                        </div>
                    </div>
                    @endif

                    @if($laporan->instruksi_prompt)
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-muted mb-3">
                            Instruksi Prompt AI
                        </label>
                        <div class="instruksi-prompt-card">
                            @php
                                $instruksi = json_decode($laporan->instruksi_prompt, true);
                            @endphp
                            
                            @if(is_array($instruksi))
                                <!-- Metadata Row -->
                                <div class="metadata-row">
                                    @if(isset($instruksi['periode']))
                                    <div class="metadata-item">
                                        <div class="metadata-label">Periode</div>
                                        <div class="metadata-value">{{ $instruksi['periode'] }}</div>
                                    </div>
                                    @endif
                                    
                                    @if(isset($instruksi['tahun']))
                                    <div class="metadata-item">
                                        <div class="metadata-label">Tahun Akademik</div>
                                        <div class="metadata-value">{{ $instruksi['tahun'] }}</div>
                                    </div>
                                    @endif
                                    
                                    @if(isset($instruksi['judul']))
                                    <div class="metadata-item">
                                        <div class="metadata-label">Judul Laporan</div>
                                        <div class="metadata-value">{{ $instruksi['judul'] }}</div>
                                    </div>
                                    @endif
                                </div>
                                
                                <!-- AI Draft Section -->
                                @if(isset($instruksi['ai_draft']) && !empty($instruksi['ai_draft']))
                                <div class="draft-section">
                                    <div class="draft-label">Draft yang Dihasilkan AI</div>
                                    <div style="max-height: 500px; overflow-y: auto; padding-right: 0.5rem;">
                                        <div class="markdown-content">
                                            {!! \Illuminate\Support\Str::markdown($instruksi['ai_draft']) !!}
                                        </div>
                                    </div>
                                </div>
                                @endif
                            @else
                                <!-- Fallback untuk format lama -->
                                <div class="bg-white rounded p-3">
                                    <p class="mb-0 font-monospace small text-muted">{{ $laporan->instruksi_prompt }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($laporan->temuan_utama)
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted">Temuan Utama</label>
                        <div class="bg-light p-3 rounded">
                            <p class="mb-0">{{ $laporan->temuan_utama }}</p>
                        </div>
                    </div>
                    @endif

                    @if($laporan->rekomendasi_perbaikan)
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted">Rekomendasi Perbaikan</label>
                        <div class="bg-light p-3 rounded">
                            <p class="mb-0">{{ $laporan->rekomendasi_perbaikan }}</p>
                        </div>
                    </div>
                    @endif

                    @if($laporan->rencana_tindakan)
                    <div class="mb-0">
                        <label class="form-label fw-semibold text-muted">Rencana Tindakan</label>
                        <div class="bg-light p-3 rounded">
                            <p class="mb-0">{{ $laporan->rencana_tindakan }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Status Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold">Status Laporan</h6>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-3">
                        <div class="mb-2">
                            <span class="badge {{ $laporan->getStatusBadgeClass() }} fs-6 px-3 py-2">
                                {{ ucfirst($laporan->status_laporan) }}
                            </span>
                        </div>
                        <small class="text-muted">
                            Dibuat: {{ $laporan->created_at->format('d M Y, H:i') }}
                        </small>
                    </div>

                    @if($laporan->createdBy)
                    <div class="border-top pt-3">
                        <label class="form-label fw-semibold text-muted small">Dibuat oleh</label>
                        <div class="d-flex align-items-center gap-2">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($laporan->createdBy->name) }}&background=1e3c72&color=fff" 
                                 alt="User" class="rounded-circle" width="32" height="32">
                            <div>
                                <p class="mb-0 fw-medium">{{ $laporan->createdBy->name }}</p>
                                <small class="text-muted">{{ $laporan->createdBy->role }}</small>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($laporan->reviewedBy)
                    <div class="border-top pt-3 mt-3">
                        <label class="form-label fw-semibold text-muted small">Direview oleh</label>
                        <div class="d-flex align-items-center gap-2">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($laporan->reviewedBy->name) }}&background=1e3c72&color=fff" 
                                 alt="User" class="rounded-circle" width="32" height="32">
                            <div>
                                <p class="mb-0 fw-medium">{{ $laporan->reviewedBy->name }}</p>
                                <small class="text-muted">{{ $laporan->tanggal_review ? $laporan->tanggal_review->format('d M Y') : '-' }}</small>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Statistics Card -->
            @if($laporan->jumlah_prodi_terlibat || $laporan->jumlah_laporan_gkm_diterima)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold">Statistik</h6>
                </div>
                <div class="card-body p-4">
                    @if($laporan->jumlah_prodi_terlibat)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Prodi Terlibat</span>
                        <span class="fw-bold">{{ $laporan->jumlah_prodi_terlibat }}</span>
                    </div>
                    @endif
                    @if($laporan->jumlah_laporan_gkm_diterima)
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Laporan GKM</span>
                        <span class="fw-bold">{{ $laporan->jumlah_laporan_gkm_diterima }}</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Actions Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold">Aksi</h6>
                </div>
                <div class="card-body p-4">
                    <div class="d-grid gap-2">
                        @if($laporan->dokumen_path)
                        <a href="{{ route('gjm.buat-laporan.download.pdf', $laporan->id) }}" 
                           class="btn btn-success">
                            <i class="bi bi-file-pdf me-2"></i>Download PDF
                        </a>
                        @endif
                        <button type="button" class="btn btn-outline-primary">
                            <i class="bi bi-file-ppt me-2"></i>Download PPT
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.form-label {
    color: #495057;
    margin-bottom: 0.5rem;
}

.badge {
    font-size: 0.875em;
}

@media (max-width: 576px) {
    .container-fluid {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
}
</style>
@endsection