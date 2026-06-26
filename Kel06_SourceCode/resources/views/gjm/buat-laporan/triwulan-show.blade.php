@extends('layouts.app')

@section('page-title', 'Detail Laporan Triwulan')

@section('content')
    <div style="padding: 1.5rem;">
        <div class="filter-card mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1" style="font-weight: 600; color: #333;">
                        Detail Laporan Triwulan
                    </h5>
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">
                        {{ $laporan->ringkasan_mutu_institusi }}
                    </p>
                </div>
                <a href="{{ route('gjm.buat-laporan.triwulan.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <div class="monitoring-card">
            <div class="monitoring-header">
                <i class="bi bi-info-circle" style="color: #5B9BD5;"></i>
                <h6>Informasi Laporan</h6>
            </div>
            <div style="padding: 1.5rem;">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong>Status:</strong>
                        <span class="badge bg-{{ $laporan->status_laporan == 'completed' ? 'success' : ($laporan->status_laporan == 'processing' ? 'warning' : 'secondary') }}">
                            {{ ucfirst($laporan->status_laporan) }}
                        </span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Dibuat:</strong> {{ $laporan->created_at->format('d/m/Y H:i') }}
                    </div>
                    <div class="col-md-12 mb-3">
                        <strong>Periode:</strong> {{ $laporan->periode_mulai->format('d/m/Y') }} - {{ $laporan->periode_akhir->format('d/m/Y') }}
                    </div>
                </div>

                @if($laporan->status_laporan == 'completed' && $laporan->file_word)
                    <div class="mt-4">
                        <a href="{{ route('gjm.buat-laporan.triwulan.download', [$laporan->id, 'word']) }}" class="btn btn-success">
                            <i class="bi bi-download"></i> Download Word
                        </a>
                    </div>
                @endif
            </div>
        </div>

        @if($laporan->ai_preview_draft)
            <div class="monitoring-card mt-4">
                <div class="monitoring-header">
                    <i class="bi bi-file-text" style="color: #5B9BD5;"></i>
                    <h6>Preview Laporan</h6>
                </div>
                <div style="padding: 1.5rem;">
                    <div class="preview-content">
                        {!! \Illuminate\Support\Str::markdown($laporan->ai_preview_draft) !!}
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
