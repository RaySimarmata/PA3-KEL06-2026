<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $laporan->getJenisLaporanLabel() }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
            margin: 40px;
        }
        
        h1 {
            color: #1e3c72;
            font-size: 20pt;
            margin-bottom: 10px;
            border-bottom: 3px solid #1e3c72;
            padding-bottom: 10px;
        }
        
        h2 {
            color: #2a5298;
            font-size: 16pt;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        
        h3 {
            color: #3d6bb3;
            font-size: 14pt;
            margin-top: 15px;
            margin-bottom: 8px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #1e3c72;
        }
        
        .header h1 {
            border: none;
            margin-bottom: 5px;
        }
        
        .header .subtitle {
            color: #666;
            font-size: 10pt;
            margin-top: 5px;
        }
        
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #1e3c72;
            padding: 15px;
            margin: 20px 0;
        }
        
        .info-box .label {
            font-weight: bold;
            color: #1e3c72;
            display: inline-block;
            width: 150px;
        }
        
        .content {
            white-space: pre-wrap;
            text-align: justify;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        table th {
            background-color: #1e3c72;
            color: white;
            padding: 10px;
            text-align: left;
        }
        
        table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $laporan->getJenisLaporanLabel() }}</h1>
        <div class="subtitle">
            Gugus Jaminan Mutu Fakultas Vokasi<br>
            Institut Teknologi Del
        </div>
    </div>

    <div class="info-box">
        <div><span class="label">Jenis Laporan:</span> {{ $laporan->getJenisLaporanLabel() }}</div>
        <div><span class="label">Program Studi:</span> {{ $laporan->program_studi ?? '-' }}</div>
        <div><span class="label">Periode:</span> 
            @if($laporan->periode_mulai)
                {{ \Carbon\Carbon::parse($laporan->periode_mulai)->format('d M Y') }}
                @if($laporan->periode_akhir && $laporan->periode_akhir != $laporan->periode_mulai)
                    - {{ \Carbon\Carbon::parse($laporan->periode_akhir)->format('d M Y') }}
                @endif
            @else
                -
            @endif
        </div>
        <div><span class="label">Status:</span> {{ ucfirst($laporan->status_laporan) }}</div>
        <div><span class="label">Dibuat:</span> {{ $laporan->created_at->format('d M Y, H:i') }}</div>
        @if($laporan->createdBy)
        <div><span class="label">Dibuat oleh:</span> {{ $laporan->createdBy->name }}</div>
        @endif
    </div>

    <div class="content">
{{ $laporan->ringkasan_mutu_institusi }}
    </div>

    @if($laporan->analisis_kepatuhan)
    <div class="page-break"></div>
    <h2>Analisis Kepatuhan</h2>
    <div class="content">
{{ $laporan->analisis_kepatuhan }}
    </div>
    @endif

    @if($laporan->temuan_utama)
    <h2>Temuan Utama</h2>
    <div class="content">
{{ $laporan->temuan_utama }}
    </div>
    @endif

    @if($laporan->rekomendasi_perbaikan)
    <h2>Rekomendasi Perbaikan</h2>
    <div class="content">
{{ $laporan->rekomendasi_perbaikan }}
    </div>
    @endif

    @if($laporan->rencana_tindakan)
    <h2>Rencana Tindakan</h2>
    <div class="content">
{{ $laporan->rencana_tindakan }}
    </div>
    @endif

    <div class="footer">
        <p>Dokumen ini di-generate secara otomatis oleh Sistem Informasi GJM<br>
        Institut Teknologi Del - {{ now()->format('Y') }}</p>
    </div>
</body>
</html>
