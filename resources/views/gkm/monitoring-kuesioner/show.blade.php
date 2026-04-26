@extends('layouts.app')

@section('page-title', 'Detail Kuesioner')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Detail Kuesioner: {{ $kuesioner->nama_file }}</h4>
                    <div>
                        @if($kuesioner->status === 'completed')
                            <a href="{{ route('gkm.monitoring-kuesioner.report', $kuesioner->id) }}" 
                               class="btn btn-success me-2">
                                <i class="bi bi-file-earmark-text"></i> Lihat Laporan AI
                            </a>
                        @endif
                        {{-- @if(in_array($kuesioner->status, ['error', 'uploaded']))
                            <form action="{{ route('gkm.monitoring-kuesioner.reprocess', $kuesioner->id) }}" 
                                  method="POST" class="d-inline"
                                  onsubmit="return confirm('Yakin ingin memproses ulang kuesioner ini dengan AI?')">
                                @csrf
                                <button type="submit" class="btn btn-warning me-2">
                                    <i class="bi bi-arrow-clockwise"></i> Proses Ulang dengan AI
                                </button>
                            </form>
                        @endif --}}
                        <a href="{{ route('gkm.monitoring-kuesioner.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Nama File:</strong></td>
                                    <td>{{ $kuesioner->nama_file }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Periode:</strong></td>
                                    <td>{{ $kuesioner->periode }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Nama Matakuliah:</strong></td>
                                    <td>{{ $kuesioner->nama_matakuliah ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Kode Matakuliah:</strong></td>
                                    <td>{{ $kuesioner->kode_matakuliah ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tingkat:</strong></td>
                                    <td>{{ $kuesioner->tingkat ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Dosen Pengampu:</strong></td>
                                    <td>{{ $kuesioner->dosen_pengampu ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Program Studi:</strong></td>
                                    <td>{{ $kuesioner->user->prodi->nama_prodi ?? 'Unknown' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Total Responden:</strong></td>
                                    <td>{{ $kuesioner->total_responden }} orang</td>
                                </tr>
                                <tr>
                                    <td><strong>Status AI Analysis:</strong></td>
                                    <td>
                                        @switch($kuesioner->status)
                                            @case('uploaded')
                                                <span class="badge bg-info">Uploaded</span>
                                                @break
                                            @case('processing')
                                                <span class="badge bg-warning">Processing</span>
                                                @break
                                            @case('completed')
                                                <span class="badge bg-success">Completed</span>
                                                @break
                                            @case('error')
                                                <span class="badge bg-danger">Error</span>
                                                @break
                                        @endswitch
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Tanggal Upload:</strong></td>
                                    <td>{{ $kuesioner->created_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            @if($kuesioner->deskripsi)
                                <div class="mb-3">
                                    <strong>Deskripsi:</strong>
                                    <p class="mt-2">{{ $kuesioner->deskripsi }}</p>
                                </div>
                            @endif
                            
                            <div class="mb-3">
                                <strong>File Excel:</strong>
                                <div class="mt-2">
                                    <a href="{{ Storage::url($kuesioner->file_path) }}" 
                                       class="btn btn-outline-primary btn-sm" target="_blank">
                                        <i class="bi bi-download"></i> Download File
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($kuesioner->status === 'processing')
                        <div class="alert alert-warning">
                            <h6><i class="bi bi-clock"></i> AI Sedang Memproses</h6>
                            <p class="mb-0">
                                AI Agent sedang menganalisis data kuesioner. Proses ini membutuhkan waktu beberapa menit. 
                                Halaman akan otomatis refresh setiap 30 detik.
                            </p>
                        </div>
                    @endif

                    @if($kuesioner->status === 'completed' && $kuesioner->hasil_analisis)
                        <div class="mt-4">
                            <h5>Ringkasan Hasil Analisis AI</h5>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6>Ringkasan:</h6>
                                            <p>{{ $kuesioner->hasil_analisis['ringkasan'] ?? 'Tidak tersedia' }}</p>
                                            
                                            @if(isset($kuesioner->hasil_analisis['statistik']))
                                                <div class="row mt-3">
                                                    <div class="col-md-6">
                                                        <div class="text-center">
                                                            <h4 class="text-primary">{{ number_format($kuesioner->hasil_analisis['statistik']['index_kepuasan'] ?? 0, 2) }}</h4>
                                                            <small>Index Kepuasan (skala 0-4)</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="text-center">
                                                            @php
                                                                $indexKepuasan = $kuesioner->hasil_analisis['statistik']['index_kepuasan'] ?? 0;
                                                                $persenKepuasan = ($indexKepuasan / 4) * 100;
                                                            @endphp
                                                            <h4 class="text-info">{{ number_format($persenKepuasan, 2) }}%</h4>
                                                            <small>Persen Kepuasan</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($kuesioner->status === 'error')
                        <div class="alert alert-danger">
                            <h6><i class="bi bi-exclamation-triangle"></i> Error dalam Analisis</h6>
                            <p class="mb-2">
                                Terjadi kesalahan saat memproses data dengan AI. Silakan coba proses ulang dengan klik tombol "Proses Ulang dengan AI" di atas.
                            </p>
                            @if(isset($kuesioner->hasil_analisis['error']))
                                <small class="text-muted">Detail error: {{ $kuesioner->hasil_analisis['error'] }}</small>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto refresh untuk status processing
@if($kuesioner->status === 'processing')
setInterval(function() {
    location.reload();
}, 30000); // Refresh setiap 30 detik
@endif
</script>
@endsection