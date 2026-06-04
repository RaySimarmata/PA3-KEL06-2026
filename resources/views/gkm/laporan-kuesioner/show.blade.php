@extends('layouts.app')

@section('page-title', 'Detail Laporan Kuesioner')

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Header Card -->
        <div class="filter-card mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 d-flex align-items-center gap-2" style="font-weight: 600; color: #333;">
                        <i class="bi bi-file-earmark-text" style="color: #5B9BD5;"></i>
                        Detail Laporan Kuesioner
                    </h5>
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">
                        {{ $laporan->formatted_periode }} - {{ $laporan->user->prodi->nama_prodi ?? '-' }}
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('gkm.laporan-kuesioner.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                    @if($laporan->status == 'completed' && $laporan->file_word)
                        <a href="{{ route('gkm.laporan-kuesioner.download', [$laporan->id, 'word']) }}" 
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
                            @if($laporan->updated_at && $laporan->status == 'completed')
                                <div class="col-md-6">
                                    <label class="filter-label">Selesai Diproses</label>
                                    <div class="text-secondary">
                                        {{ $laporan->updated_at->format('d F Y, H:i') }}
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
                                <h6 style="font-weight: 600; margin-bottom: 0.5rem;">
                                    <i class="bi bi-exclamation-triangle"></i> Error
                                </h6>
                                <p class="mb-0" style="font-size: 0.875rem;">{{ $laporan->error_message }}</p>
                                @if (str_contains($laporan->error_message, 'Tidak ada kuesioner'))
                                    <hr style="margin: 0.75rem 0;">
                                    <strong style="font-size: 0.875rem;">Langkah untuk mengatasi:</strong>
                                    <ol class="mb-0 mt-2" style="font-size: 0.875rem; padding-left: 1.25rem;">
                                        <li>Upload kuesioner mahasiswa terlebih dahulu di halaman <a
                                                href="{{ route('gkm.monitoring-kuesioner.index') }}"
                                                style="color: #5B9BD5; font-weight: 600;">Monitoring Kuesioner</a></li>
                                        <li>Pastikan kuesioner sudah dianalisis (status: Completed)</li>
                                        <li>Pastikan periode kuesioner sesuai dengan periode laporan
                                            ({{ $laporan->formatted_periode }})</li>
                                        <li>Setelah ada data kuesioner, hapus laporan ini dan generate ulang</li>
                                    </ol>
                                @endif
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
                @if($laporan->status == 'completed' && $laporan->konten)
                    <div class="monitoring-card mb-4">
                        <div class="monitoring-header">
                            <i class="bi bi-file-text" style="color: #5B9BD5;"></i>
                            <h6>Preview Konten Laporan</h6>
                        </div>
                        <div style="padding: 1.5rem;">
                            <div class="markdown-content" style="max-height: 600px; overflow-y: auto;">
                                {!! \Illuminate\Support\Str::markdown($laporan->konten) !!}
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Hasil Laporan JSON (for debugging/technical view) -->
                @if($laporan->status == 'completed' && $laporan->hasil_laporan)
                    @php
                        $hasil = $laporan->hasil_laporan;
                    @endphp

                    @if(isset($hasil['ringkasan_eksekutif']) || isset($hasil['insight_utama']) || isset($hasil['rekomendasi']))
                        <div class="monitoring-card mb-4">
                            <div class="monitoring-header">
                                <i class="bi bi-lightbulb" style="color: #5B9BD5;"></i>
                                <h6>Insight & Rekomendasi</h6>
                            </div>
                            <div style="padding: 1.5rem;">
                                <!-- Ringkasan -->
                                @if(isset($hasil['ringkasan_eksekutif']['overview']))
                                    <div class="mb-3">
                                        <label class="filter-label">Ringkasan Eksekutif</label>
                                        <p style="line-height: 1.6; color: #495057;">{{ $hasil['ringkasan_eksekutif']['overview'] }}</p>
                                    </div>
                                @endif

                                <!-- Insight Utama -->
                                @if(isset($hasil['insight_utama']) && is_array($hasil['insight_utama']) && count($hasil['insight_utama']) > 0)
                                    <div class="mb-3">
                                        <label class="filter-label">Insight Utama</label>
                                        <ul style="padding-left: 1.25rem; margin-bottom: 0;">
                                            @foreach(array_slice($hasil['insight_utama'], 0, 3) as $insight)
                                                <li style="margin-bottom: 0.5rem; color: #495057;">{{ $insight }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <!-- Rekomendasi -->
                                @if(isset($hasil['rekomendasi']) && is_array($hasil['rekomendasi']) && count($hasil['rekomendasi']) > 0)
                                    <div>
                                        <label class="filter-label">Rekomendasi</label>
                                        <ul style="padding-left: 1.25rem; margin-bottom: 0;">
                                            @foreach(array_slice($hasil['rekomendasi'], 0, 3) as $rekom)
                                                <li style="margin-bottom: 0.5rem; color: #495057;">
                                                    @if(is_array($rekom))
                                                        {{ $rekom['rekomendasi'] ?? json_encode($rekom) }}
                                                    @else
                                                        {{ $rekom }}
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
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
                            <label class="filter-label">Total Kuesioner</label>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge-gkm info" style="font-size: 1.5rem; padding: 0.5rem 1rem;">
                                    {{ $laporan->total_kuesioner ?? 0 }}
                                </span>
                                <span class="text-muted" style="font-size: 0.85rem;">matakuliah</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="filter-label">Total Responden</label>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge-gkm info" style="font-size: 1.5rem; padding: 0.5rem 1rem;">
                                    {{ $laporan->total_responden ?? 0 }}
                                </span>
                                <span class="text-muted" style="font-size: 0.85rem;">mahasiswa</span>
                            </div>
                        </div>
                        @if($laporan->index_kepuasan_rata_rata)
                            <div class="mb-3">
                                <label class="filter-label">Index Kepuasan</label>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge-gkm success" style="font-size: 1.5rem; padding: 0.5rem 1rem;">
                                        {{ number_format($laporan->index_kepuasan_rata_rata, 2) }}
                                    </span>
                                    <span class="text-muted" style="font-size: 0.85rem;">/ 4.00</span>
                                </div>
                            </div>
                        @endif
                        @if($laporan->persen_kepuasan_rata_rata)
                            <div>
                                <label class="filter-label">Persentase Kepuasan</label>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge-gkm success" style="font-size: 1.5rem; padding: 0.5rem 1rem;">
                                        {{ number_format($laporan->persen_kepuasan_rata_rata, 1) }}%
                                    </span>
                                </div>
                            </div>
                        @endif
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
                                <a href="{{ route('gkm.laporan-kuesioner.download', [$laporan->id, 'word']) }}" 
                                   class="btn btn-success">
                                    <i class="bi bi-download"></i> Download Word
                                </a>
                            @endif
                            
                            <a href="{{ route('gkm.laporan-kuesioner.index') }}" 
                               class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                            </a>
                            
                            <button type="button" 
                                    class="btn btn-outline-danger"
                                    onclick="confirmDelete({{ $laporan->id }}, '{{ $laporan->formatted_periode }}')">
                                <i class="bi bi-trash"></i> Hapus Laporan
                            </button>
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

@push('scripts')
<script>
function confirmDelete(laporanId, periode) {
    if (confirm(`Yakin ingin menghapus laporan periode ${periode}?`)) {
        // Build URL using route helper
        const url = '{{ route('gkm.laporan-kuesioner.destroy', ':id') }}'.replace(':id', laporanId);

        // Send DELETE request
        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.href = '{{ route('gkm.laporan-kuesioner.index') }}';
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghapus laporan');
        });
    }
}
</script>
@endpush

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

