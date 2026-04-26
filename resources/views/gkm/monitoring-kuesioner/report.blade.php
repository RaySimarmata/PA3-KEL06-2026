@extends('layouts.app')

@section('page-title', 'Laporan Analisis AI - Kuesioner')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Laporan Analisis AI - {{ $kuesioner->nama_file }}</h4>
                        <div>
                            <button type="button" onclick="window.print(); return false;" class="btn btn-primary me-2">
                                <i class="bi bi-printer"></i> Print
                            </button>
                            <a href="{{ route('gkm.monitoring-kuesioner.show', $kuesioner->id) }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Header Laporan -->
                        <div class="text-center mb-4">
                            <h3>LAPORAN ANALISIS KUESIONER MAHASISWA</h3>
                            <h4>Pengembangan Agent Web Based Untuk Otomatisasi Administrasi GKM</h4>
                            <h5>Fakultas Vokasi</h5>
                            <hr>
                        </div>

                        <!-- Informasi Kuesioner -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="150"><strong>Nama Kuesioner:</strong></td>
                                        <td>{{ $kuesioner->nama_file }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Periode:</strong></td>
                                        <td>{{ $kuesioner->periode }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Program Studi:</strong></td>
                                        <td>{{ $kuesioner->user->prodi->nama_prodi ?? 'Unknown' }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="150"><strong>Total Responden:</strong></td>
                                        <td>{{ $kuesioner->total_responden }} orang</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal Analisis:</strong></td>
                                        <td>{{ $kuesioner->updated_at->format('d/m/Y H:i:s') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>AI Agent:</strong></td>
                                        <td>Sistem Otomatis GJM-GKM</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        @if ($kuesioner->hasil_analisis)
                            <!-- Statistik Utama -->
                            @if (isset($kuesioner->hasil_analisis['statistik']))
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h5>Statistik Utama</h5>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card bg-primary text-white text-center">
                                                    <div class="card-body">
                                                        <h2 style="color: white !important;">{{ number_format($kuesioner->hasil_analisis['statistik']['index_kepuasan'] ?? 0, 5) }}
                                                        </h2>
                                                        <p class="mb-0" style="color: white !important;">Index Kepuasan (skala 0-4)</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card bg-info text-white text-center">
                                                    <div class="card-body">
                                                        @php
                                                            $indexKepuasan =
                                                                $kuesioner->hasil_analisis['statistik'][
                                                                    'index_kepuasan'
                                                                ] ?? 0;
                                                            $persenKepuasan = ($indexKepuasan / 4) * 100;
                                                        @endphp
                                                        <h2 style="color: white !important;">{{ number_format($persenKepuasan, 2) }}%</h2>
                                                        <p class="mb-0" style="color: white !important;">Persen Kepuasan</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Interpretasi Index Kepuasan -->
                            @if (isset($kuesioner->hasil_analisis['interpretasi_index']))
                                <div class="mb-4">
                                    <h5>Interpretasi Index Kepuasan</h5>
                                    <div class="card border-primary">
                                        <div class="card-body">
                                            <p class="mb-0">{{ $kuesioner->hasil_analisis['interpretasi_index'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Pertanyaan Tertinggi & Terendah -->
                            @if (isset($kuesioner->hasil_analisis['statistik']['pertanyaan_tertinggi']) || isset($kuesioner->hasil_analisis['statistik']['pertanyaan_terendah']))
                                <div class="mb-4">
                                    <h5>Aspek Tertinggi & Terendah</h5>
                                    <div class="row">
                                        @if (isset($kuesioner->hasil_analisis['statistik']['pertanyaan_tertinggi']))
                                            @php $top = $kuesioner->hasil_analisis['statistik']['pertanyaan_tertinggi']; @endphp
                                            <div class="col-md-6">
                                                <div class="card border-success mb-2">
                                                    <div class="card-body">
                                                        <h6 class="text-success"><i class="bi bi-arrow-up-circle"></i> Nilai Tertinggi</h6>
                                                        @if(isset($top['id']))
                                                            <span class="badge bg-success me-1">{{ $top['id'] }}</span>
                                                        @endif
                                                        <p class="mb-1 mt-1"><strong>{{ $top['teks'] ?? $top['id'] ?? '-' }}</strong></p>
                                                        <span class="badge bg-success">Rata-rata: {{ number_format($top['nilai'] ?? 0, 5) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if (isset($kuesioner->hasil_analisis['statistik']['pertanyaan_terendah']))
                                            @php $low = $kuesioner->hasil_analisis['statistik']['pertanyaan_terendah']; @endphp
                                            <div class="col-md-6">
                                                <div class="card border-danger mb-2">
                                                    <div class="card-body">
                                                        <h6 class="text-danger"><i class="bi bi-arrow-down-circle"></i> Nilai Terendah</h6>
                                                        @if(isset($low['id']))
                                                            <span class="badge bg-danger me-1">{{ $low['id'] }}</span>
                                                        @endif
                                                        <p class="mb-1 mt-1"><strong>{{ $low['teks'] ?? $low['id'] ?? '-' }}</strong></p>
                                                        <span class="badge bg-danger">Rata-rata: {{ number_format($low['nilai'] ?? 0, 5) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Statistik Per Pertanyaan -->
                            @if (isset($kuesioner->hasil_analisis['statistik']['statistik_per_pertanyaan']) &&
                                    !empty($kuesioner->hasil_analisis['statistik']['statistik_per_pertanyaan']))
                                <div class="mb-4">
                                    <h5>Statistik Per Pertanyaan</h5>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-sm">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th class="text-center" width="60">No</th>
                                                            <th>Pertanyaan</th>
                                                            <th class="text-center">TS</th>
                                                            <th class="text-center">CS</th>
                                                            <th class="text-center">S</th>
                                                            <th class="text-center">SS</th>
                                                            <th class="text-center">Nilai Rata-rata</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($kuesioner->hasil_analisis['statistik']['statistik_per_pertanyaan'] as $pertanyaan => $stat)
                                                            <tr>
                                                                <td class="text-center"><span class="badge bg-secondary">{{ $pertanyaan }}</span></td>
                                                                <td>{{ $stat['pertanyaan'] ?? $pertanyaan }}</td>
                                                                <td class="text-center">{{ $stat['TS'] ?? 0 }}</td>
                                                                <td class="text-center">{{ $stat['CS'] ?? 0 }}</td>
                                                                <td class="text-center">{{ $stat['S'] ?? 0 }}</td>
                                                                <td class="text-center">{{ $stat['SS'] ?? 0 }}</td>
                                                                <td class="text-center">
                                                                    <strong>{{ number_format($stat['nilai_rata_rata'] ?? 0, 5) }}</strong>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <strong>Keterangan:</strong> TS = Tidak Setuju (1), CS = Cukup Setuju
                                                    (2), S = Setuju (3), SS = Sangat Setuju (4)
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="mb-4">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i>
                                        <strong>Informasi:</strong> Data statistik per pertanyaan tidak tersedia.
                                        Silakan upload ulang file kuesioner untuk mendapatkan analisis detail per
                                        pertanyaan.
                                    </div>
                                </div>
                            @endif

                            <!-- Distribusi Jawaban -->
                            @if (isset($kuesioner->hasil_analisis['statistik']['distribusi_jawaban']))
                                <div class="mb-4">
                                    <h5>Distribusi Jawaban</h5>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row">
                                                @foreach ($kuesioner->hasil_analisis['statistik']['distribusi_jawaban'] as $jawaban => $jumlah)
                                                    @php
                                                        $total = array_sum(
                                                            $kuesioner->hasil_analisis['statistik'][
                                                                'distribusi_jawaban'
                                                            ],
                                                        );
                                                        $persentase =
                                                            $total > 0 ? round(($jumlah / $total) * 100, 1) : 0;
                                                        $badgeClass = match ($jawaban) {
                                                            'SS' => 'bg-success',
                                                            'S' => 'bg-primary',
                                                            'CS' => 'bg-warning',
                                                            'TS' => 'bg-danger',
                                                            default => 'bg-secondary',
                                                        };
                                                        $labelJawaban = match ($jawaban) {
                                                            'SS' => 'Sangat Setuju',
                                                            'S' => 'Setuju',
                                                            'CS' => 'Cukup Setuju',
                                                            'TS' => 'Tidak Setuju',
                                                            default => $jawaban,
                                                        };
                                                    @endphp
                                                    <div class="col-md-3 mb-2">
                                                        <span class="badge {{ $badgeClass }} w-100 p-2">
                                                            {{ $labelJawaban }}: {{ $jumlah }}
                                                            ({{ $persentase }}%)
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Ringkasan Analisis -->
                            <div class="mb-4">
                                <h5>Ringkasan Analisis AI</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <p>{{ $kuesioner->hasil_analisis['ringkasan'] ?? 'Tidak tersedia' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Poin Positif -->
                            @if (isset($kuesioner->hasil_analisis['poin_positif']))
                                <div class="mb-4">
                                    <h5>Poin Positif</h5>
                                    <div class="card border-success">
                                        <div class="card-body">
                                            <ul class="list-unstyled">
                                                @foreach ($kuesioner->hasil_analisis['poin_positif'] as $poin)
                                                    <li><i
                                                            class="bi bi-check-circle text-success me-2"></i>{{ $poin }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Area Perbaikan -->
                            @if (isset($kuesioner->hasil_analisis['area_perbaikan']))
                                <div class="mb-4">
                                    <h5>Area yang Perlu Diperbaiki</h5>
                                    <div class="card border-warning">
                                        <div class="card-body">
                                            <ul class="list-unstyled">
                                                @foreach ($kuesioner->hasil_analisis['area_perbaikan'] as $area)
                                                    <li><i
                                                            class="bi bi-exclamation-triangle text-warning me-2"></i>{{ $area }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Rekomendasi -->
                            @if (isset($kuesioner->hasil_analisis['rekomendasi']))
    <div class="mb-4">
        <h5>Rekomendasi Tindakan</h5>
        <div class="card border-info">
            <div class="card-body">
                <ol>
                    @php
                        $rekomendasiList = $kuesioner->hasil_analisis['rekomendasi'];

                        // 🔥 kalau string → ubah jadi array
                        if (is_string($rekomendasiList)) {
                            $rekomendasiList = [$rekomendasiList];
                        }
                    @endphp

                    @foreach ($rekomendasiList as $rekomendasi)
                        <li>{{ $rekomendasi }}</li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
@endif  
                        @endif

                        <!-- Footer Laporan -->
                        <div class="mt-5 pt-4 border-top">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Catatan:</strong></p>
                                    <p class="small">
                                        Laporan ini dihasilkan secara otomatis oleh AI Agent sistem GJM-GKM menggunakan
                                        pendekatan RAG (Retrieval-Augmented Generation). Statistik dihitung dari data
                                        kuesioner
                                        yang telah diupload, kemudian dianalisis oleh AI untuk menghasilkan insight dan
                                        rekomendasi.
                                    </p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <p>Dihasilkan pada: {{ now()->format('d/m/Y H:i:s') }}</p>
                                    <p>Sistem: Pengembangan Agent Web Based GJM-GKM</p>
                                    <p class="small text-muted">Teknologi: RAG + AI Analysis</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {

            .btn,
            .card-header .btn {
                display: none !important;
            }

            .card {
                border: none !important;
                box-shadow: none !important;
            }

            body {
                background: white !important;
            }
        }
    </style>
@endsection
