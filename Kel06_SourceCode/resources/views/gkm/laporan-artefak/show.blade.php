@extends('layouts.app')

@section('page-title', 'Detail Laporan Bulanan')

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Header Card -->
        <div class="filter-card mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 d-flex align-items-center gap-2" style="font-weight: 600; color: #333;">
                        <i class="bi bi-file-earmark-text" style="color: #5B9BD5;"></i>
                        Detail Laporan Bulanan
                    </h5>
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">
                        {{ $laporan->formatted_periode }} - {{ $laporan->prodi->nama_prodi ?? '-' }}
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('gkm.laporan-artefak.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                    @if($laporan->status == 'completed' && $laporan->file_word)
                        <a href="{{ route('gkm.laporan-artefak.download', [$laporan->id, 'word']) }}" 
                           class="btn btn-success">
                            <i class="bi bi-download"></i> Download Word
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Status Card -->
                <div class="monitoring-card mb-4">
                    <div class="monitoring-header">
                        <i class="bi bi-info-circle" style="color: #5B9BD5;"></i>
                        <h6>Status Laporan</h6>
                    </div>
                    <div style="padding: 1.5rem;">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="filter-label">Status</label>
                                <div>
                                    <span class="badge-gkm {{ $laporan->status_badge == 'success' ? 'success' : ($laporan->status_badge == 'warning' ? 'warning' : ($laporan->status_badge == 'danger' ? 'danger' : 'info')) }}" 
                                          style="font-size: 1rem; padding: 0.5rem 1rem;">
                                        {{ $laporan->status_label }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="filter-label">Dibuat</label>
                                <div class="text-secondary">
                                    {{ $laporan->created_at->format('d F Y, H:i') }}
                                </div>
                            </div>
                            @if($laporan->generated_at)
                                <div class="col-md-6">
                                    <label class="filter-label">Selesai Diproses</label>
                                    <div class="text-secondary">
                                        {{ \Carbon\Carbon::parse($laporan->generated_at)->format('d F Y, H:i') }}
                                    </div>
                                </div>
                            @endif
                            @if($laporan->template)
                                <div class="col-md-6">
                                    <label class="filter-label">Template</label>
                                    <div class="text-secondary">
                                        {{ $laporan->template->nama_template }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if($laporan->status == 'error' && $laporan->error_message)
                            <div class="alert-gkm danger mt-3">
                                <strong>Error:</strong> {{ $laporan->error_message }}
                            </div>
                        @endif

                        @if($laporan->status == 'processing')
                            <div class="alert-gkm info mt-3">
                                <i class="bi bi-hourglass-split"></i>
                                <strong>Sedang Diproses:</strong> AI Agent sedang menganalisis data dan membuat laporan. 
                                Halaman akan otomatis refresh setiap 10 detik.
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Content Preview -->
                @if($laporan->status == 'completed' && $laporan->konten_laporan)
                    <div class="monitoring-card mb-4">
                        <div class="monitoring-header">
                            <i class="bi bi-file-text" style="color: #5B9BD5;"></i>
                            <h6>Preview Konten Laporan</h6>
                        </div>
                        <div style="padding: 1.5rem;">
                            <div class="markdown-content" style="max-height: 600px; overflow-y: auto;">
                                {!! \Illuminate\Support\Str::markdown($laporan->konten_laporan) !!}
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <!-- Statistics Card -->
                <div class="monitoring-card mb-4">
                    <div class="monitoring-header">
                        <i class="bi bi-bar-chart" style="color: #5B9BD5;"></i>
                        <h6>Statistik</h6>
                    </div>
                    <div style="padding: 1.5rem;">
                        <div class="mb-3">
                            <label class="filter-label">Total RPS</label>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge-gkm info" style="font-size: 1.5rem; padding: 0.5rem 1rem;">
                                    {{ $laporan->total_rps ?? 0 }}
                                </span>
                                <span class="text-muted" style="font-size: 0.85rem;">matakuliah</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="filter-label">Total Materi</label>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge-gkm info" style="font-size: 1.5rem; padding: 0.5rem 1rem;">
                                    {{ $laporan->total_materi ?? 0 }}
                                </span>
                                <span class="text-muted" style="font-size: 0.85rem;">matakuliah</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions Card -->
                <div class="monitoring-card">
                    <div class="monitoring-header">
                        <i class="bi bi-gear" style="color: #5B9BD5;"></i>
                        <h6>Aksi</h6>
                    </div>
                    <div style="padding: 1.5rem;">
                        <div class="d-grid gap-2">
                            @if($laporan->status == 'completed' && $laporan->file_word)
                                <a href="{{ route('gkm.laporan-artefak.download', [$laporan->id, 'word']) }}" 
                                   class="btn btn-success">
                                    <i class="bi bi-download"></i> Download Word
                                </a>
                            @endif
                            
                            <a href="{{ route('gkm.laporan-artefak.index') }}" 
                               class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                            </a>
                            
                            <form action="{{ route('gkm.laporan-artefak.destroy', $laporan->id) }}" 
                                  method="POST"
                                  onsubmit="AppConfirm.delete(this, 'Laporan ini akan dihapus permanen.'); return false;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="bi bi-trash"></i> Hapus Laporan
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($laporan->status == 'processing')
        <script>
            // Auto refresh every 10 seconds if still processing
            setTimeout(function() {
                location.reload();
            }, 10000);
        </script>
    @endif
@endsection

@push('styles')
<style>
.markdown-content {
    font-size: 0.95rem;
    line-height: 1.6;
}

.markdown-content h1,
.markdown-content h2,
.markdown-content h3 {
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    font-weight: 600;
    color: #333;
}

.markdown-content h1 {
    font-size: 1.5rem;
    border-bottom: 2px solid #5B9BD5;
    padding-bottom: 0.5rem;
}

.markdown-content h2 {
    font-size: 1.25rem;
}

.markdown-content h3 {
    font-size: 1.1rem;
}

.markdown-content ul,
.markdown-content ol {
    margin-left: 1.5rem;
    margin-bottom: 1rem;
}

.markdown-content li {
    margin-bottom: 0.5rem;
}

.markdown-content p {
    margin-bottom: 1rem;
}

.markdown-content table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1rem;
}

.markdown-content table th,
.markdown-content table td {
    border: 1px solid #dee2e6;
    padding: 0.5rem;
}

.markdown-content table th {
    background-color: #f8f9fa;
    font-weight: 600;
}
</style>
@endpush


