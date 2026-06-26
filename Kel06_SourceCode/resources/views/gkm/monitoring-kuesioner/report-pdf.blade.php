<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Analisis AI - Kuesioner</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        
        .header-title {
            text-align: center;
            padding: 15px;
            margin-bottom: 15px;
            border-bottom: 2px solid #5B9BD5;
        }
        
        .header-title h1 {
            font-size: 18px;
            color: #333;
            font-weight: 700;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        .header-title h2 {
            font-size: 14px;
            color: #5B9BD5;
            font-weight: 600;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .header-title h3 {
            font-size: 12px;
            color: #666;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .section {
            margin-bottom: 15px;
            border: 1px solid #e9ecef;
            page-break-inside: avoid;
        }
        
        .section-header {
            background: #f8f9fa;
            padding: 8px 12px;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            font-size: 11px;
            color: #333;
            text-transform: uppercase;
        }
        
        .section-content {
            padding: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        table th,
        table td {
            padding: 6px 8px;
            border: 1px solid #dee2e6;
            text-align: left;
            font-size: 10px;
        }
        
        table th {
            background: #f8f9fa;
            font-weight: 600;
            text-align: center;
        }
        
        table td.label {
            font-weight: 500;
            width: 25%;
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 600;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-primary {
            background: #cce5ff;
            color: #004085;
        }
        
        .stats-box {
            text-align: center;
            padding: 15px;
            margin-bottom: 10px;
            border: 2px solid #5B9BD5;
            border-radius: 5px;
        }
        
        .stats-box h1 {
            font-size: 28px;
            color: #5B9BD5;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stats-box p {
            font-size: 10px;
            color: #666;
        }
        
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .stats-grid-item {
            display: table-cell;
            width: 50%;
            padding: 5px;
        }
        
        .alert {
            padding: 10px 12px;
            border-radius: 3px;
            margin-bottom: 10px;
            border-left: 4px solid;
        }
        
        .alert-info {
            background: #d1ecf1;
            border-color: #0c5460;
            color: #0c5460;
        }
        
        .alert-success {
            background: #d4edda;
            border-color: #155724;
            color: #155724;
        }
        
        .comparison-box {
            padding: 12px;
            border-left: 4px solid;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        
        .comparison-box.positive {
            background: #f0fdf4;
            border-color: #28a745;
        }
        
        .comparison-box.negative {
            background: #fef2f2;
            border-color: #dc3545;
        }
        
        .comparison-box h4 {
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .comparison-box.positive h4 {
            color: #28a745;
        }
        
        .comparison-box.negative h4 {
            color: #dc3545;
        }
        
        .comparison-box p {
            font-size: 10px;
            margin-bottom: 5px;
        }
        
        .comparison-box .value {
            display: inline-block;
            padding: 3px 8px;
            background: white;
            border-radius: 3px;
            font-weight: 600;
            margin-top: 5px;
        }
        
        ol, ul {
            padding-left: 20px;
        }
        
        ol li, ul li {
            margin-bottom: 5px;
            font-size: 10px;
            line-height: 1.5;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Header Laporan -->
    <div class="header-title">
        <h1>Laporan Analisis Kuesioner Mahasiswa</h1>
        <h2>Pengembangan Agent Web Based Untuk Otomatisasi Administrasi GKM</h2>
        <h3>Fakultas Vokasi</h3>
    </div>

    <!-- Informasi Kuesioner -->
    <div class="section">
        <div class="section-header">
            Informasi Kuesioner
        </div>
        <div class="section-content">
            <table>
                <tr>
                    <td class="label">Nama Kuesioner</td>
                    <td>{{ $kuesioner->nama_file }}</td>
                    <td class="label">Total Responden</td>
                    <td><strong style="color: #5B9BD5;">{{ $kuesioner->total_responden }}</strong> orang</td>
                </tr>
                <tr>
                    <td class="label">Periode</td>
                    <td><span class="badge badge-info">{{ $kuesioner->periode }}</span></td>
                    <td class="label">Tanggal Analisis</td>
                    <td>{{ $kuesioner->updated_at->format('d/m/Y H:i:s') }}</td>
                </tr>
                <tr>
                    <td class="label">AI Agent</td>
                    <td colspan="3"><span class="badge badge-success">Sistem Otomatis GJM-GKM</span></td>
                </tr>
            </table>
        </div>
    </div>

    @if ($kuesioner->hasil_analisis)
        <!-- Statistik Utama -->
        @if (isset($kuesioner->hasil_analisis['statistik']))
            <div class="section">
                <div class="section-header">
                    Statistik Utama
                </div>
                <div class="section-content">
                    <div class="stats-grid">
                        <div class="stats-grid-item">
                            <div class="stats-box">
                                <h1>{{ number_format($kuesioner->hasil_analisis['statistik']['index_kepuasan'] ?? 0, 2) }}</h1>
                                <p>Index Kepuasan (skala 0-4)</p>
                            </div>
                        </div>
                        <div class="stats-grid-item">
                            <div class="stats-box">
                                @php
                                    $indexKepuasan = $kuesioner->hasil_analisis['statistik']['index_kepuasan'] ?? 0;
                                    $persenKepuasan = ($indexKepuasan / 4) * 100;
                                @endphp
                                <h1>{{ number_format($persenKepuasan, 2) }}%</h1>
                                <p>Persentase Kepuasan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Interpretasi Index Kepuasan -->
        @if (isset($kuesioner->hasil_analisis['interpretasi_index']))
            <div class="alert alert-info">
                <strong>Interpretasi Index Kepuasan:</strong><br>
                {{ $kuesioner->hasil_analisis['interpretasi_index'] }}
            </div>
        @endif

        <!-- Pertanyaan Tertinggi & Terendah -->
        @if (isset($kuesioner->hasil_analisis['statistik']['pertanyaan_tertinggi']) || isset($kuesioner->hasil_analisis['statistik']['pertanyaan_terendah']))
            <div class="section">
                <div class="section-header">
                    Aspek Tertinggi & Terendah
                </div>
                <div class="section-content">
                    @if (isset($kuesioner->hasil_analisis['statistik']['pertanyaan_tertinggi']))
                        @php $top = $kuesioner->hasil_analisis['statistik']['pertanyaan_tertinggi']; @endphp
                        <div class="comparison-box positive">
                            <h4>Nilai Tertinggi @if(isset($top['id'])) - {{ $top['id'] }}@endif</h4>
                            <p>{{ $top['teks'] ?? $top['id'] ?? '-' }}</p>
                            <div class="value" style="color: #28a745;">
                                <strong>{{ number_format($top['nilai'] ?? 0, 2) }}</strong> rata-rata
                            </div>
                        </div>
                    @endif
                    
                    @if (isset($kuesioner->hasil_analisis['statistik']['pertanyaan_terendah']))
                        @php $low = $kuesioner->hasil_analisis['statistik']['pertanyaan_terendah']; @endphp
                        <div class="comparison-box negative">
                            <h4>Nilai Terendah @if(isset($low['id'])) - {{ $low['id'] }}@endif</h4>
                            <p>{{ $low['teks'] ?? $low['id'] ?? '-' }}</p>
                            <div class="value" style="color: #dc3545;">
                                <strong>{{ number_format($low['nilai'] ?? 0, 2) }}</strong> rata-rata
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Statistik Per Pertanyaan -->
        @if (isset($kuesioner->hasil_analisis['statistik']['statistik_per_pertanyaan']) &&
                !empty($kuesioner->hasil_analisis['statistik']['statistik_per_pertanyaan']))
            <div class="section">
                <div class="section-header">
                    Statistik Per Pertanyaan
                </div>
                <div class="section-content">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40px;">No</th>
                                <th>Pertanyaan</th>
                                <th style="width: 50px;">TS</th>
                                <th style="width: 50px;">CS</th>
                                <th style="width: 50px;">S</th>
                                <th style="width: 50px;">SS</th>
                                <th style="width: 70px;">Rata-rata</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kuesioner->hasil_analisis['statistik']['statistik_per_pertanyaan'] as $pertanyaan => $stat)
                                <tr>
                                    <td style="text-align: center;">
                                        <span class="badge badge-info">{{ $pertanyaan }}</span>
                                    </td>
                                    <td>{{ $stat['pertanyaan'] ?? $pertanyaan }}</td>
                                    <td style="text-align: center;">
                                        <span class="badge badge-danger">{{ $stat['TS'] ?? 0 }}</span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge badge-warning">{{ $stat['CS'] ?? 0 }}</span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge badge-primary">{{ $stat['S'] ?? 0 }}</span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge badge-success">{{ $stat['SS'] ?? 0 }}</span>
                                    </td>
                                    <td style="text-align: center;">
                                        <strong style="color: #5B9BD5;">
                                            {{ number_format($stat['nilai_rata_rata'] ?? 0, 2) }}
                                        </strong>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="alert alert-info" style="margin-top: 10px;">
                        <strong>Keterangan:</strong> TS = Tidak Setuju (1), CS = Cukup Setuju (2), 
                        S = Setuju (3), SS = Sangat Setuju (4)
                    </div>
                </div>
            </div>
        @endif

        <!-- Distribusi Jawaban -->
        @if (isset($kuesioner->hasil_analisis['statistik']['distribusi_jawaban']))
            <div class="section">
                <div class="section-header">
                    Distribusi Jawaban
                </div>
                <div class="section-content">
                    <table>
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th>Jumlah</th>
                                <th>Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kuesioner->hasil_analisis['statistik']['distribusi_jawaban'] as $jawaban => $jumlah)
                                @php
                                    $total = array_sum($kuesioner->hasil_analisis['statistik']['distribusi_jawaban']);
                                    $persentase = $total > 0 ? round(($jumlah / $total) * 100, 1) : 0;
                                    $labelJawaban = match ($jawaban) {
                                        'SS' => 'Sangat Setuju',
                                        'S' => 'Setuju',
                                        'CS' => 'Cukup Setuju',
                                        'TS' => 'Tidak Setuju',
                                        default => $jawaban,
                                    };
                                    $badgeClass = match ($jawaban) {
                                        'SS' => 'badge-success',
                                        'S' => 'badge-primary',
                                        'CS' => 'badge-warning',
                                        'TS' => 'badge-danger',
                                        default => 'badge-info',
                                    };
                                @endphp
                                <tr>
                                    <td><span class="badge {{ $badgeClass }}">{{ $labelJawaban }}</span></td>
                                    <td style="text-align: center;"><strong>{{ $jumlah }}</strong></td>
                                    <td style="text-align: center;">{{ $persentase }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Ringkasan Analisis -->
        <div class="section">
            <div class="section-header">
                Ringkasan Analisis AI
            </div>
            <div class="section-content">
                <div class="alert alert-success">
                    {{ $kuesioner->hasil_analisis['ringkasan'] ?? 'Tidak tersedia' }}
                </div>
            </div>
        </div>

        <!-- Poin Positif -->
        @if (isset($kuesioner->hasil_analisis['poin_positif']))
            <div class="section">
                <div class="section-header">
                    Poin Positif
                </div>
                <div class="section-content">
                    <ol>
                        @foreach ($kuesioner->hasil_analisis['poin_positif'] as $poin)
                            <li>{{ $poin }}</li>
                        @endforeach
                    </ol>
                </div>
            </div>
        @endif

        <!-- Area Perbaikan -->
        @if (isset($kuesioner->hasil_analisis['area_perbaikan']))
            <div class="section">
                <div class="section-header">
                    Area yang Perlu Diperbaiki
                </div>
                <div class="section-content">
                    <ol>
                        @foreach ($kuesioner->hasil_analisis['area_perbaikan'] as $area)
                            <li>{{ $area }}</li>
                        @endforeach
                    </ol>
                </div>
            </div>
        @endif

        <!-- Rekomendasi -->
        @if (isset($kuesioner->hasil_analisis['rekomendasi']))
            <div class="section">
                <div class="section-header">
                    Rekomendasi Tindakan
                </div>
                <div class="section-content">
                    <ol>
                        @php
                            $rekomendasiList = $kuesioner->hasil_analisis['rekomendasi'];
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
        @endif
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Dokumen ini digenerate secara otomatis oleh Sistem AI GJM-GKM pada {{ date('d/m/Y H:i:s') }}</p>
        <p>&copy; {{ date('Y') }} Fakultas Vokasi - Sistem Otomatis GJM-GKM</p>
    </div>
</body>
</html>
