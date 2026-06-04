@extends('layouts.app')

@section('page-title', 'Detail Kuesioner')

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Header Card -->
        <div class="filter-card mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">
                        {{ $kuesioner->nama_file }}
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @if ($kuesioner->status === 'completed')
                        <a href="{{ route('gkm.monitoring-kuesioner.report', $kuesioner->id) }}"
                            class="btn btn-lihat">
                            <i class="bi bi-eye"></i> Lihat
                        </a>
                    @endif
                    @if (in_array($kuesioner->status, ['error', 'uploaded']))
                        <form action="{{ route('gkm.monitoring-kuesioner.reprocess', $kuesioner->id) }}"
                            method="POST" class="d-inline"
                            onsubmit="return confirm('Yakin ingin memproses ulang kuesioner ini dengan AI?')">
                            @csrf
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-arrow-clockwise"></i> Proses Ulang
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('gkm.monitoring-kuesioner.index') }}" class="btn btn-secondary btn-kembali">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Informasi Kuesioner -->
                <div class="monitoring-card mb-4">
                    <div class="monitoring-header">
                        <h6 style="text-transform: uppercase; letter-spacing: 0.5px;">Informasi Kuesioner</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-monitoring mb-0">
                            <tbody>
                                <tr>
                                    <td style="width: 35%; font-weight: 500; color: #495057;">Nama File</td>
                                    <td style="color: #333;">{{ $kuesioner->nama_file }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 500; color: #495057;">Periode</td>
                                    <td>
                                        <span class="badge-gkm info">{{ $kuesioner->periode }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 500; color: #495057;">Nama Matakuliah</td>
                                    <td>
                                        @if($kuesioner->nama_matakuliah)
                                            {{ $kuesioner->nama_matakuliah }}
                                        @else
                                            @php
                                                // Try dari database
                                                $matkul = \App\Models\Matakuliah::where('kode_mk', $kuesioner->kode_matakuliah)->first();
                                                $namaMk = $matkul ? $matkul->nama_mk : null;
                                                
                                                // Jika tidak ada, extract dari judul
                                                if (!$namaMk && $kuesioner->nama_file) {
                                                    $judul = $kuesioner->nama_file;
                                                    
                                                    // Pattern 1: "Evaluasi Mata Kuliah [NAMA] UTS/UAS Semester"
                                                    if (preg_match('/Evaluasi Mata Kuliah\s+(.+?)\s+(?:UTS|UAS)\s+Semester/i', $judul, $matches)) {
                                                        $namaMk = trim($matches[1]);
                                                    }
                                                    // Pattern 2: Kode - Evaluasi Mata Kuliah [NAMA] UTS/UAS
                                                    elseif (preg_match('/\d+\s*-\s*Evaluasi Mata Kuliah\s+(.+?)\s+(?:UTS|UAS)/i', $judul, $matches)) {
                                                        $namaMk = trim($matches[1]);
                                                    }
                                                    // Pattern 3: Extract anything between "Kuliah" and "UTS/UAS/Semester"
                                                    elseif (preg_match('/Kuliah\s+(.+?)\s+(?:UTS|UAS|Semester)/i', $judul, $matches)) {
                                                        $namaMk = trim($matches[1]);
                                                    }
                                                }
                                            @endphp
                                            {{ $namaMk ?? '-' }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 500; color: #495057;">Kode Matakuliah</td>
                                    <td class="code-mk">{{ $kuesioner->kode_matakuliah ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 500; color: #495057;">Jenis Kuesioner</td>
                                    <td>
                                        @if($kuesioner->jenis_kuesioner)
                                            <span class="badge-gkm info">{{ $kuesioner->jenis_kuesioner }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 500; color: #495057;">Tingkat</td>
                                    <td>
                                        @if($kuesioner->tingkat)
                                            <span class="badge-gkm primary">{{ $kuesioner->tingkat }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 500; color: #495057;">Dosen Pengampu</td>
                                    <td>
                                        @php
                                            $dosen = \App\Models\Dosenn::where('pegawai_id', $kuesioner->pegawai_id)->first();
                                        @endphp
                                        {{ $dosen->nama ?? $kuesioner->dosen_pengampu ?? '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 500; color: #495057;">Total Responden</td>
                                    <td>
                                        <strong style="color: #5B9BD5;">{{ $kuesioner->total_responden }}</strong> orang
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 500; color: #495057;">Tanggal Upload</td>
                                    <td class="text-secondary" style="font-size: 0.85rem;">
                                        {{ $kuesioner->created_at->format('d/m/Y H:i:s') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- File Excel -->
                <div class="monitoring-card mb-4">
                    <div class="monitoring-header">
                        <h6 style="text-transform: uppercase; letter-spacing: 0.5px;">File Kuesioner</h6>
                    </div>
                    <div style="padding: 1.5rem;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <i class="bi bi-file-earmark-excel" style="font-size: 2rem; color: #28a745;"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="mb-1 fw-semibold">File Excel Kuesioner</p>
                                <small class="text-muted">{{ basename($kuesioner->file_path) }}</small>
                            </div>
                            <a href="{{ Storage::url($kuesioner->file_path) }}"
                                class="btn btn-outline-success" target="_blank">
                                <i class="bi bi-download"></i> Download
                            </a>
                        </div>
                    </div>
                </div>

                @if ($kuesioner->deskripsi)
                    <!-- Deskripsi -->
                    <div class="monitoring-card mb-4">
                        <div class="monitoring-header">
                            <h6 style="text-transform: uppercase; letter-spacing: 0.5px;">Deskripsi</h6>
                        </div>
                        <div style="padding: 1.5rem;">
                            <p class="mb-0" style="color: #495057; line-height: 1.6;">{{ $kuesioner->deskripsi }}</p>
                        </div>
                    </div>
                @endif

                @if ($kuesioner->status === 'completed' && $kuesioner->hasil_analisis)
                    <!-- Ringkasan Hasil Analisis AI -->
                    <div class="monitoring-card mb-4">
                        <div class="monitoring-header">
                            <h6 style="text-transform: uppercase; letter-spacing: 0.5px;">Ringkasan Hasil Analisis AI</h6>
                        </div>
                        <div style="padding: 1.5rem;">
                            <div class="alert-gkm success mb-3">
                                <h6 style="font-weight: 600; margin-bottom: 0.5rem;">
                                    <i class="bi bi-lightbulb"></i> Ringkasan
                                </h6>
                                <p class="mb-0" style="line-height: 1.6;">
                                    {{ $kuesioner->hasil_analisis['ringkasan'] ?? 'Tidak tersedia' }}
                                </p>
                            </div>

                            @if (isset($kuesioner->hasil_analisis['statistik']))
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="text-center" style="padding: 1.5rem; background: #f8f9fa; border-radius: 0.5rem;">
                                            <h2 class="mb-2" style="color: #5B9BD5; font-weight: 700;">
                                                {{ number_format($kuesioner->hasil_analisis['statistik']['index_kepuasan'] ?? 0, 2) }}
                                            </h2>
                                            <p class="mb-0 text-muted" style="font-size: 0.875rem;">
                                                Index Kepuasan (skala 0-4)
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-center" style="padding: 1.5rem; background: #f8f9fa; border-radius: 0.5rem;">
                                            @php
                                                $indexKepuasan = $kuesioner->hasil_analisis['statistik']['index_kepuasan'] ?? 0;
                                                $persenKepuasan = ($indexKepuasan / 4) * 100;
                                            @endphp
                                            <h2 class="mb-2" style="color: #28a745; font-weight: 700;">
                                                {{ number_format($persenKepuasan, 2) }}%
                                            </h2>
                                            <p class="mb-0 text-muted" style="font-size: 0.875rem;">
                                                Persentase Kepuasan
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <!-- Status Card -->
                <div class="monitoring-card mb-4" style="position: sticky; top: 1.5rem;">
                    <div class="monitoring-header">
                        <h6 style="text-transform: uppercase; letter-spacing: 0.5px;">Status Analisis AI</h6>
                    </div>
                    <div style="padding: 1.5rem;">
                        <div class="text-center mb-3">
                            @switch($kuesioner->status)
                                @case('uploaded')
                                    <div class="mb-3">
                                        <i class="bi bi-cloud-upload" style="font-size: 3rem; color: #17a2b8;"></i>
                                    </div>
                                    <span class="badge-gkm info" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                        <i class="bi bi-info-circle"></i> Uploaded
                                    </span>
                                    <p class="mt-3 mb-0 text-muted" style="font-size: 0.875rem;">
                                        File berhasil diupload dan menunggu pemrosesan
                                    </p>
                                @break

                                @case('processing')
                                    <div class="mb-3">
                                        <div class="spinner-border" style="width: 3rem; height: 3rem; color: #ffc107;" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                    <span class="badge-gkm warning" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                        <i class="bi bi-hourglass-split"></i> Processing
                                    </span>
                                    <p class="mt-3 mb-0 text-muted" style="font-size: 0.875rem;">
                                        AI Agent sedang menganalisis data kuesioner
                                    </p>
                                @break

                                @case('completed')
                                    <div class="mb-3">
                                        <i class="bi bi-check-circle" style="font-size: 3rem; color: #28a745;"></i>
                                    </div>
                                    <span class="badge-gkm success" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                        <i class="bi bi-check-circle"></i> Completed
                                    </span>
                                    <p class="mt-3 mb-0 text-muted" style="font-size: 0.875rem;">
                                        Analisis selesai dan laporan siap dilihat
                                    </p>
                                @break

                                @case('error')
                                    <div class="mb-3">
                                        <i class="bi bi-exclamation-triangle" style="font-size: 3rem; color: #dc3545;"></i>
                                    </div>
                                    <span class="badge-gkm danger" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                        <i class="bi bi-x-circle"></i> Error
                                    </span>
                                    <p class="mt-3 mb-0 text-muted" style="font-size: 0.875rem;">
                                        Terjadi kesalahan saat pemrosesan
                                    </p>
                                @break
                            @endswitch
                        </div>
                    </div>
                </div>

                @if ($kuesioner->status === 'processing')
                    <!-- Processing Info -->
                    <div class="alert-gkm warning mb-4">
                        <h6 style="font-weight: 600; margin-bottom: 0.5rem;">
                            <i class="bi bi-clock"></i> AI Sedang Memproses
                        </h6>
                        <p class="mb-0" style="font-size: 0.875rem; line-height: 1.6;">
                            AI Agent sedang menganalisis data kuesioner. Proses ini membutuhkan waktu beberapa menit.
                            Halaman akan otomatis refresh setiap 30 detik.
                        </p>
                    </div>
                @endif

                @if ($kuesioner->status === 'error')
                    <!-- Error Info -->
                    <div class="alert-gkm danger mb-4">
                        <h6 style="font-weight: 600; margin-bottom: 0.5rem;">
                            <i class="bi bi-exclamation-triangle"></i> Error dalam Analisis
                        </h6>
                        <p class="mb-2" style="font-size: 0.875rem; line-height: 1.6;">
                            Terjadi kesalahan saat memproses data dengan AI. Silakan coba proses ulang dengan klik
                            tombol "Proses Ulang" di atas.
                        </p>
                        @if (isset($kuesioner->hasil_analisis['error']))
                            <hr style="margin: 0.75rem 0;">
                            <small class="text-muted" style="font-size: 0.8rem;">
                                <strong>Detail error:</strong><br>
                                {{ $kuesioner->hasil_analisis['error'] }}
                            </small>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        /* Button Lihat - static style no hover effect */
        .btn-lihat {
            background: linear-gradient(135deg, #2c5282 0%, #4a7bb7 100%) !important;
            border: none !important;
            color: white !important;
            padding: 0.5rem 1.5rem !important;
            border-radius: 1rem !important;
            font-weight: 500 !important;
            letter-spacing: 0.5px !important;
            cursor: pointer !important;
            transition: none !important;
            box-shadow: 0 2px 4px rgba(44, 82, 130, 0.3) !important;
        }

        .btn-lihat:hover,
        .btn-lihat:focus,
        .btn-lihat:active,
        .btn-lihat:active:focus,
        .btn-lihat:visited {
            background: linear-gradient(135deg, #2c5282 0%, #4a7bb7 100%) !important;
            border: none !important;
            color: white !important;
            box-shadow: 0 2px 4px rgba(44, 82, 130, 0.3) !important;
            transform: none !important;
            cursor: pointer !important;
        }

        .btn-lihat i {
            margin-right: 0.5rem;
        }

        /* Disable hover/active effects on Kembali button */
        .btn-kembali {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            color: white !important;
            cursor: default !important;
        }

        .btn-kembali:hover,
        .btn-kembali:focus,
        .btn-kembali:active,
        .btn-kembali:active:focus,
        .btn-kembali:visited {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            color: white !important;
            box-shadow: none !important;
            cursor: default !important;
        }
    </style>

    <script>
        // Auto refresh untuk status processing
        @if ($kuesioner->status === 'processing')
            setTimeout(function() {
                location.reload();
            }, 30000); // Refresh setiap 30 detik
        @endif
    </script>
@endsection
