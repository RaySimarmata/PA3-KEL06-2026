@extends('layouts.app')

@section('page-title', 'Laporan Analisis AI - Kuesioner')

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Header Card -->
        <div class="filter-card mb-4 no-print">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 font-semibold" style="text-transform: uppercase; letter-spacing: 0.5px; color: #333;">
                        Laporan Analisis AI - Kuesioner
                    </h5>
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">
                        {{ $kuesioner->nama_file }}
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary" id="btn-print-pdf"
                        style="pointer-events: auto; cursor: pointer;">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <a href="{{ route('gkm.monitoring-kuesioner.index', $kuesioner->id) }}"
                        class="btn btn-secondary btn-kembali">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <!-- Header Laporan -->
        <div class="monitoring-card mb-4">
            <div class="text-center" style="padding: 2rem;">
                <h3
                    style="color: #333; font-weight: 700; margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">
                    Laporan Analisis Kuesioner Mahasiswa
                </h3>
                <h5
                    style="color: #5B9BD5; font-weight: 600; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px;">
                    Pengembangan Agent Web Based Untuk Otomatisasi Administrasi GKM
                </h5>
                <h6 style="color: #666; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Fakultas Vokasi
                </h6>
            </div>
        </div>

        <!-- Informasi Kuesioner -->
        <div class="monitoring-card mb-4">
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e9ecef;">
                <h6 class="mb-0 d-flex align-items-center"
                    style="font-weight: 600; color: #333; text-transform: uppercase; letter-spacing: 0.5px;">
                    Informasi Kuesioner
                </h6>
            </div>

            <div class="table-responsive">
                <table class="table table-monitoring mb-0">
                    <tbody>
                        <tr>
                            <td style="width: 25%; font-weight: 500; color: #495057;">Nama Kuesioner</td>
                            <td style="width: 25%; color: #333;">{{ $kuesioner->nama_file }}</td>
                            <td style="width: 25%; font-weight: 500; color: #495057;">Total Responden</td>
                            <td style="width: 25%;">
                                <strong style="color: #5B9BD5;">{{ $kuesioner->total_responden }}</strong> orang
                            </td>
                        </tr>

                        <tr>
                            <td style="font-weight: 500; color: #495057;">Periode</td>
                            <td>
                                <span class="badge-gkm info">{{ $kuesioner->periode }}</span>
                            </td>
                            <td style="font-weight: 500; color: #495057;">Tanggal Analisis</td>
                            <td class="text-secondary" style="font-size: 0.85rem;">
                                {{ $kuesioner->updated_at->format('d/m/Y H:i:s') }}
                            </td>
                        </tr>

                        <tr>
                            <td style="font-weight: 500; color: #495057;">AI Agent</td>
                            <td colspan="3">
                                <span class="badge-gkm success">
                                    Sistem Otomatis GJM-GKM
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        @if ($kuesioner->hasil_analisis)
            <!-- Statistik Utama -->
            @if (isset($kuesioner->hasil_analisis['statistik']))
                <div class="monitoring-card mb-4">
                    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e9ecef;">
                        <h6 class="mb-0 d-flex align-items-center"
                            style="font-weight: 600; color: #333; text-transform: uppercase; letter-spacing: 0.5px;">
                            Statistik Utama
                        </h6>
                    </div>
                    <div style="padding: 1.5rem;">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-center"
                                    style="padding: 2rem; background: linear-gradient(135deg, #5B9BD5 0%, #6fa8dc 100%); border-radius: 0.75rem; color: white;">
                                    <i class="bi bi-trophy"
                                        style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.9;"></i>
                                    <h1 style="color: white; font-weight: 700; margin-bottom: 0.5rem; font-size: 2.5rem;">
                                        {{ number_format($kuesioner->hasil_analisis['statistik']['index_kepuasan'] ?? 0, 2) }}
                                    </h1>
                                    <p class="mb-0" style="color: white; font-size: 0.95rem; opacity: 0.95;">Index
                                        Kepuasan (skala 0-4)</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-center"
                                    style="padding: 2rem; background: linear-gradient(135deg, #28a745 0%, #34ce57 100%); border-radius: 0.75rem; color: white;">
                                    <i class="bi bi-percent"
                                        style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.9;"></i>
                                    @php
                                        $indexKepuasan = $kuesioner->hasil_analisis['statistik']['index_kepuasan'] ?? 0;
                                        $persenKepuasan = ($indexKepuasan / 4) * 100;
                                    @endphp
                                    <h1 style="color: white; font-weight: 700; margin-bottom: 0.5rem; font-size: 2.5rem;">
                                        {{ number_format($persenKepuasan, 2) }}%
                                    </h1>
                                    <p class="mb-0" style="color: white; font-size: 0.95rem; opacity: 0.95;">Persentase
                                        Kepuasan</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Interpretasi Index Kepuasan -->
            @if (isset($kuesioner->hasil_analisis['interpretasi_index']))
                <div class="alert-gkm info mb-4">
                    <h6 style="font-weight: 600; margin-bottom: 0.5rem;">
                        Interpretasi Index Kepuasan
                    </h6>
                    <p class="mb-0" style="line-height: 1.6;">
                        {{ $kuesioner->hasil_analisis['interpretasi_index'] }}
                    </p>
                </div>
            @endif

            <!-- Pertanyaan Tertinggi & Terendah -->
            @if (isset($kuesioner->hasil_analisis['statistik']['pertanyaan_tertinggi']) ||
                    isset($kuesioner->hasil_analisis['statistik']['pertanyaan_terendah']))
                <div class="monitoring-card mb-4">
                    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e9ecef;">
                        <h6 class="mb-0 d-flex align-items-center"
                            style="font-weight: 600; color: #333; text-transform: uppercase; letter-spacing: 0.5px;">
                            Aspek Tertinggi & Terendah
                        </h6>
                    </div>
                    <div style="padding: 1.5rem;">
                        <div class="row g-3">
                            @if (isset($kuesioner->hasil_analisis['statistik']['pertanyaan_tertinggi']))
                                @php $top = $kuesioner->hasil_analisis['statistik']['pertanyaan_tertinggi']; @endphp
                                <div class="col-md-6">
                                    <div
                                        style="padding: 1.5rem; background: #f0fdf4; border-left: 4px solid #28a745; border-radius: 0.5rem;">
                                        <div class="d-flex align-items-start mb-2">
                                            <i class="bi bi-arrow-up-circle"
                                                style="color: #28a745; font-size: 1.5rem; margin-right: 0.75rem;"></i>
                                            <div>
                                                <h6 style="color: #28a745; font-weight: 600; margin-bottom: 0.5rem;">Nilai
                                                    Tertinggi</h6>
                                                @if (isset($top['id']))
                                                    <span class="badge-gkm success mb-2">{{ $top['id'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <p class="mb-2" style="color: #333; font-weight: 500;">
                                            {{ $top['teks'] ?? ($top['id'] ?? '-') }}
                                        </p>
                                        <div
                                            style="padding: 0.5rem 1rem; background: white; border-radius: 0.375rem; display: inline-block;">
                                            <strong
                                                style="color: #28a745;">{{ number_format($top['nilai'] ?? 0, 2) }}</strong>
                                            <span class="text-muted" style="font-size: 0.875rem;">rata-rata</span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if (isset($kuesioner->hasil_analisis['statistik']['pertanyaan_terendah']))
                                @php $low = $kuesioner->hasil_analisis['statistik']['pertanyaan_terendah']; @endphp
                                <div class="col-md-6">
                                    <div
                                        style="padding: 1.5rem; background: #fef2f2; border-left: 4px solid #dc3545; border-radius: 0.5rem;">
                                        <div class="d-flex align-items-start mb-2">
                                            <i class="bi bi-arrow-down-circle"
                                                style="color: #dc3545; font-size: 1.5rem; margin-right: 0.75rem;"></i>
                                            <div>
                                                <h6 style="color: #dc3545; font-weight: 600; margin-bottom: 0.5rem;">Nilai
                                                    Terendah</h6>
                                                @if (isset($low['id']))
                                                    <span class="badge-gkm danger mb-2">{{ $low['id'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <p class="mb-2" style="color: #333; font-weight: 500;">
                                            {{ $low['teks'] ?? ($low['id'] ?? '-') }}
                                        </p>
                                        <div
                                            style="padding: 0.5rem 1rem; background: white; border-radius: 0.375rem; display: inline-block;">
                                            <strong
                                                style="color: #dc3545;">{{ number_format($low['nilai'] ?? 0, 2) }}</strong>
                                            <span class="text-muted" style="font-size: 0.875rem;">rata-rata</span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Statistik Per Pertanyaan -->
            @if (isset($kuesioner->hasil_analisis['statistik']['statistik_per_pertanyaan']) &&
                    !empty($kuesioner->hasil_analisis['statistik']['statistik_per_pertanyaan']))
                <div class="monitoring-card mb-4">
                    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e9ecef;">
                        <h6 class="mb-0 d-flex align-items-center"
                            style="font-weight: 600; color: #333; text-transform: uppercase; letter-spacing: 0.5px;">
                            Statistik Per Pertanyaan
                        </h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-monitoring">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th class="text-center" style="width: 60px;">No</th>
                                    <th>Pertanyaan</th>
                                    <th class="text-center" style="width: 80px;">TS</th>
                                    <th class="text-center" style="width: 80px;">CS</th>
                                    <th class="text-center" style="width: 80px;">S</th>
                                    <th class="text-center" style="width: 80px;">SS</th>
                                    <th class="text-center" style="width: 120px;">Rata-rata</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($kuesioner->hasil_analisis['statistik']['statistik_per_pertanyaan'] as $pertanyaan => $stat)
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge-gkm info">{{ $pertanyaan }}</span>
                                        </td>
                                        <td>{{ $stat['pertanyaan'] ?? $pertanyaan }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-danger">{{ $stat['TS'] ?? 0 }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning text-dark">{{ $stat['CS'] ?? 0 }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">{{ $stat['S'] ?? 0 }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success">{{ $stat['SS'] ?? 0 }}</span>
                                        </td>
                                        <td class="text-center">
                                            <strong style="color: #5B9BD5; font-size: 1.05rem;">
                                                {{ number_format($stat['nilai_rata_rata'] ?? 0, 2) }}
                                            </strong>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div style="padding: 0 1.5rem 1.5rem;">
                        <div class="alert-gkm info mb-0">
                            <small style="font-size: 0.875rem;">
                                <strong>Keterangan:</strong> TS = Tidak Setuju (1), CS = Cukup Setuju (2),
                                S = Setuju (3), SS = Sangat Setuju (4)
                            </small>
                        </div>
                    </div>
                </div>
            @else
                <div class="alert-gkm warning mb-4">
                    <h6 style="font-weight: 600; margin-bottom: 0.5rem;">
                        <i class="bi bi-info-circle"></i> Informasi
                    </h6>
                    <p class="mb-0" style="font-size: 0.875rem;">
                        Data statistik per pertanyaan tidak tersedia. Silakan upload ulang file kuesioner
                        untuk mendapatkan analisis detail per pertanyaan.
                    </p>
                </div>
            @endif

            <!-- Distribusi Jawaban -->
            @if (isset($kuesioner->hasil_analisis['statistik']['distribusi_jawaban']))
                <div class="monitoring-card mb-4">
                    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e9ecef;">
                        <h6 class="mb-0 d-flex align-items-center"
                            style="font-weight: 600; color: #333; text-transform: uppercase; letter-spacing: 0.5px;">
                            Distribusi Jawaban
                        </h6>
                    </div>
                    <div style="padding: 1.5rem;">
                        <div class="row g-3">
                            @foreach ($kuesioner->hasil_analisis['statistik']['distribusi_jawaban'] as $jawaban => $jumlah)
                                @php
                                    $total = array_sum($kuesioner->hasil_analisis['statistik']['distribusi_jawaban']);
                                    $persentase = $total > 0 ? round(($jumlah / $total) * 100, 1) : 0;
                                    $colorClass = match ($jawaban) {
                                        'SS' => 'success',
                                        'S' => 'primary',
                                        'CS' => 'warning',
                                        'TS' => 'danger',
                                        default => 'secondary',
                                    };
                                    $bgColor = match ($jawaban) {
                                        'SS' => 'linear-gradient(135deg, #28a745 0%, #34ce57 100%)',
                                        'S' => 'linear-gradient(135deg, #5B9BD5 0%, #6fa8dc 100%)',
                                        'CS' => 'linear-gradient(135deg, #ffc107 0%, #ffcd39 100%)',
                                        'TS' => 'linear-gradient(135deg, #dc3545 0%, #e4606d 100%)',
                                        default => 'linear-gradient(135deg, #6c757d 0%, #7d8693 100%)',
                                    };
                                    $labelJawaban = match ($jawaban) {
                                        'SS' => 'Sangat Setuju',
                                        'S' => 'Setuju',
                                        'CS' => 'Cukup Setuju',
                                        'TS' => 'Tidak Setuju',
                                        default => $jawaban,
                                    };
                                @endphp
                                <div class="col-md-3">
                                    <div class="text-center"
                                        style="padding: 1.5rem; background: {{ $bgColor }}; border-radius: 0.75rem; color: white;">
                                        <h6 class="mb-2" style="color: white; font-weight: 600; opacity: 0.95;">
                                            {{ $labelJawaban }}
                                        </h6>
                                        <h2 class="mb-1" style="color: white; font-weight: 700;">
                                            {{ $jumlah }}
                                        </h2>
                                        <p class="mb-0" style="color: white; font-size: 0.9rem; opacity: 0.9;">
                                            {{ $persentase }}%
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Ringkasan Analisis -->
            <div class="monitoring-card mb-4">
                <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e9ecef;">
                    <h6 class="mb-0 d-flex align-items-center"
                        style="font-weight: 600; color: #333; text-transform: uppercase; letter-spacing: 0.5px;">
                        Ringkasan Analisis AI
                    </h6>
                </div>
                <div style="padding: 1.5rem;">
                    <div class="alert-gkm success mb-0">
                        <p class="mb-0" style="line-height: 1.8;">
                            {{ $kuesioner->hasil_analisis['ringkasan'] ?? 'Tidak tersedia' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Poin Positif -->
            @if (isset($kuesioner->hasil_analisis['poin_positif']))
                <div class="monitoring-card mb-4">
                    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e9ecef;">
                        <h6 class="mb-0 d-flex align-items-center"
                            style="font-weight: 600; color: #333; text-transform: uppercase; letter-spacing: 0.5px;">
                            Poin Positif
                        </h6>
                    </div>
                    <div style="padding: 1.5rem;">
                        <ol class="mb-0" style="padding-left: 1.5rem;">
                            @foreach ($kuesioner->hasil_analisis['poin_positif'] as $poin)
                                <li class="mb-2" style="color: #333; line-height: 1.8;">{{ $poin }}</li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            @endif

            <!-- Area Perbaikan -->
            @if (isset($kuesioner->hasil_analisis['area_perbaikan']))
                <div class="monitoring-card mb-4">
                    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e9ecef;">
                        <h6 class="mb-0 d-flex align-items-center"
                            style="font-weight: 600; color: #333; text-transform: uppercase; letter-spacing: 0.5px;">
                            Area yang Perlu Diperbaiki
                        </h6>
                    </div>
                    <div style="padding: 1.5rem;">
                        <ol class="mb-0" style="padding-left: 1.5rem;">
                            @foreach ($kuesioner->hasil_analisis['area_perbaikan'] as $area)
                                <li class="mb-2" style="color: #333; line-height: 1.8;">{{ $area }}</li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            @endif

            <!-- Rekomendasi -->
            @if (isset($kuesioner->hasil_analisis['rekomendasi']))
                <div class="monitoring-card mb-4">
                    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e9ecef;">
                        <h6 class="mb-0 d-flex align-items-center"
                            style="font-weight: 600; color: #333; text-transform: uppercase; letter-spacing: 0.5px;">
                            Rekomendasi Tindakan
                        </h6>
                    </div>
                    <div style="padding: 1.5rem;">
                        <ol class="mb-0" style="padding-left: 1.5rem;">
                            @php
                                $rekomendasiList = $kuesioner->hasil_analisis['rekomendasi'];
                                if (is_string($rekomendasiList)) {
                                    $rekomendasiList = [$rekomendasiList];
                                }
                            @endphp

                            @foreach ($rekomendasiList as $rekomendasi)
                                <li class="mb-2" style="color: #333; line-height: 1.8;">
                                    {{ $rekomendasi }}
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            @endif

            <!-- Footer Laporan -->
            <div class="monitoring-card">
                <div style="padding: 1.5rem;">
                    <div class="row">
                        <div class="col-md-7">
                            <h6 style="font-weight: 600; color: #333; margin-bottom: 1rem;">
                                Catatan
                            </h6>
                            <p class="mb-0" style="font-size: 0.875rem; line-height: 1.6; color: #666;">
                                Laporan ini dihasilkan secara otomatis oleh AI Agent sistem GJM-GKM menggunakan
                                pendekatan RAG (Retrieval-Augmented Generation). Statistik dihitung dari data
                                kuesioner yang telah diupload, kemudian dianalisis oleh AI untuk menghasilkan
                                insight dan rekomendasi.
                            </p>
                        </div>
                        <div class="col-md-5">
                            <div style="padding: 1rem; background: #f8f9fa; border-radius: 0.5rem;">
                                <p class="mb-2" style="font-size: 0.875rem;">
                                    <strong>Dihasilkan pada:</strong><br>
                                    <span class="text-muted">{{ now()->format('d/m/Y H:i:s') }}</span>
                                </p>
                                <p class="mb-2" style="font-size: 0.875rem;">
                                    <strong>Sistem:</strong><br>
                                    <span class="text-muted">Pengembangan Agent Web Based GJM-GKM</span>
                                </p>
                                <p class="mb-0" style="font-size: 0.875rem;">
                                    <strong>Teknologi:</strong><br>
                                    <span class="badge-gkm info">RAG + AI Analysis</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>

    <style>
        @media print {

            .no-print,
            .btn,
            .monitoring-header .btn-reminder {
                display: none !important;
            }

            .monitoring-card,
            .filter-card {
                border: 1px solid #dee2e6 !important;
                box-shadow: none !important;
                page-break-inside: avoid;
            }

            body {
                background: white !important;
            }

            div[style*="border-bottom: 1px solid #e9ecef"] {
                border-bottom: 2px solid #5B9BD5 !important;
            }
        }

        /* Disable hover/active effects on Kembali button */
        .btn-kembali {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            color: white !important;
        }

        .btn-kembali:hover,
        .btn-kembali:focus,
        .btn-kembali:active,
        .btn-kembali:active:focus {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            color: white !important;
            box-shadow: none !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnPrintPdf = document.getElementById('btn-print-pdf');

            if (btnPrintPdf) {
                btnPrintPdf.addEventListener('click', function() {
                    // Tampilkan loading indicator
                    const originalHtml = this.innerHTML;
                    this.innerHTML = '<i class="bi bi-hourglass-split"></i> Membuat PDF...';
                    this.disabled = true;

                    // Redirect ke route print PDF
                    const kuesionerId = '{{ $kuesioner->id }}';
                    const printUrl = '{{ route('gkm.monitoring-kuesioner.print-pdf', ':id') }}'.replace(
                        ':id', kuesionerId);

                    // Buat window/tab baru untuk download
                    window.location.href = printUrl;

                    // Restore button setelah 2 detik
                    setTimeout(() => {
                        this.innerHTML = originalHtml;
                        this.disabled = false;
                    }, 2000);
                });
            }
        });
    </script>
    @endif
@endsection
