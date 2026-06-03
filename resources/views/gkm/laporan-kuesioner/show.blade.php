@extends('layouts.app')

@section('page-title', 'Detail Laporan')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4>Detail Laporan Bulanan</h4>
                    <p class="text-muted">{{ $laporan->formatted_periode }}</p>
                </div>
                <div>
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
    </div>

    <!-- Status Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-{{ $laporan->status_badge }}">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="mb-1">
                                Status: <span class="badge bg-{{ $laporan->status_badge }}">{{ $laporan->status_label }}</span>
                            </h5>
                            @if($laporan->status == 'pending')
                                <p class="mb-0 text-muted">Laporan sedang menunggu untuk diproses...</p>
                            @elseif($laporan->status == 'processing')
                                <p class="mb-0 text-muted">
                                    <i class="bi bi-hourglass-split"></i> AI Agent sedang menganalisis data dan menghasilkan laporan...
                                </p>
                            @elseif($laporan->status == 'completed')
                                <p class="mb-0 text-success">
                                    <i class="bi bi-check-circle"></i> Laporan berhasil digenerate dan siap didownload
                                </p>
                            @elseif($laporan->status == 'error')
                                <p class="mb-0 text-danger">
                                    <i class="bi bi-exclamation-triangle"></i> <strong>Terjadi error:</strong> {{ $laporan->error_message }}
                                </p>
                                @if(str_contains($laporan->error_message, 'Tidak ada kuesioner'))
                                    <div class="alert alert-info mt-3 mb-0">
                                        <strong>Langkah untuk mengatasi:</strong>
                                        <ol class="mb-0 mt-2">
                                            <li>Upload kuesioner mahasiswa terlebih dahulu di halaman <a href="{{ route('gkm.monitoring-kuesioner.index') }}" class="alert-link"><strong>Monitoring Kuesioner</strong></a></li>
                                            <li>Pastikan kuesioner sudah dianalisis (status: Completed)</li>
                                            <li>Pastikan periode kuesioner sesuai dengan periode laporan ({{ $laporan->formatted_periode }})</li>
                                            <li>Setelah ada data kuesioner, hapus laporan ini dan generate ulang</li>
                                        </ol>
                                    </div>
                                @endif
                            @endif
                        </div>
                        @if(in_array($laporan->status, ['pending', 'processing']))
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($laporan->status == 'completed')
    <!-- Statistik Utama -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Kuesioner</h6>
                    <h2 class="mb-0">{{ $laporan->total_kuesioner }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Responden</h6>
                    <h2 class="mb-0">{{ $laporan->total_responden }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Index Kepuasan</h6>
                    <h2 class="mb-0">{{ number_format($laporan->index_kepuasan_rata_rata, 2) }}</h2>
                    <small class="text-muted">skala 0-4</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Persen Kepuasan</h6>
                    <h2 class="mb-0">{{ number_format($laporan->persen_kepuasan_rata_rata, 1) }}%</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Hasil Laporan -->
    @if($laporan->hasil_laporan)
        @php
            $hasil = $laporan->hasil_laporan;
        @endphp

        <!-- Ringkasan Eksekutif -->
        @if(isset($hasil['ringkasan_eksekutif']))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-file-text"></i> Ringkasan Eksekutif</h5>
                    </div>
                    <div class="card-body">
                        <p>{{ $hasil['ringkasan_eksekutif']['overview'] ?? '' }}</p>
                        
                        @if(isset($hasil['ringkasan_eksekutif']['highlight_positif']))
                        <div class="mb-3">
                            <h6 class="text-success"><i class="bi bi-check-circle"></i> Highlight Positif:</h6>
                            <ul>
                                @foreach($hasil['ringkasan_eksekutif']['highlight_positif'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        @if(isset($hasil['ringkasan_eksekutif']['highlight_negatif']))
                        <div class="mb-3">
                            <h6 class="text-warning"><i class="bi bi-exclamation-triangle"></i> Highlight Negatif:</h6>
                            <ul>
                                @foreach($hasil['ringkasan_eksekutif']['highlight_negatif'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        @if(isset($hasil['ringkasan_eksekutif']['trend']))
                        <div>
                            <h6>Trend:</h6>
                            <span class="badge bg-info">{{ ucfirst($hasil['ringkasan_eksekutif']['trend']) }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Insight Utama -->
        @if(isset($hasil['insight_utama']) && is_array($hasil['insight_utama']))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-lightbulb"></i> Insight Utama</h5>
                    </div>
                    <div class="card-body">
                        <ol>
                            @foreach($hasil['insight_utama'] as $insight)
                                <li class="mb-2">{{ $insight }}</li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Rekomendasi -->
        @if(isset($hasil['rekomendasi']) && is_array($hasil['rekomendasi']))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Rekomendasi Strategis</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="60%">Rekomendasi</th>
                                        <th width="15%">Prioritas</th>
                                        <th width="20%">Timeline</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($hasil['rekomendasi'] as $idx => $rekom)
                                    <tr>
                                        <td>{{ $idx + 1 }}</td>
                                        <td>
                                            @if(is_array($rekom))
                                                {{ $rekom['rekomendasi'] ?? $rekom }}
                                            @else
                                                {{ $rekom }}
                                            @endif
                                        </td>
                                        <td>
                                            @if(is_array($rekom) && isset($rekom['prioritas']))
                                                @php
                                                    $badgeColor = match(strtolower($rekom['prioritas'])) {
                                                        'high' => 'danger',
                                                        'medium' => 'warning',
                                                        'low' => 'secondary',
                                                        default => 'secondary'
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $badgeColor }}">{{ $rekom['prioritas'] }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if(is_array($rekom) && isset($rekom['timeline']))
                                                {{ $rekom['timeline'] }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Top 5 Tertinggi & Terendah -->
        <div class="row mb-4">
            @if(isset($hasil['top_5_tertinggi']))
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bi bi-trophy"></i> Top 5 Kuesioner Tertinggi</h6>
                    </div>
                    <div class="card-body">
                        <ol>
                            @foreach($hasil['top_5_tertinggi'] as $item)
                                <li class="mb-2">
                                    @if(is_array($item))
                                        {{ $item['nama'] ?? $item['kuesioner'] ?? '' }}
                                        @if(isset($item['nama_matakuliah']))
                                            <br><small class="text-muted">{{ $item['nama_matakuliah'] }} ({{ $item['kode_matakuliah'] ?? '-' }})</small>
                                        @endif
                                        <span class="badge bg-success">{{ $item['index_kepuasan'] ?? $item['index'] ?? '' }}</span>
                                    @else
                                        {{ $item }}
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>
            @endif

            @if(isset($hasil['top_5_terendah']))
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Top 5 Kuesioner Terendah</h6>
                    </div>
                    <div class="card-body">
                        <ol>
                            @foreach($hasil['top_5_terendah'] as $item)
                                <li class="mb-2">
                                    @if(is_array($item))
                                        {{ $item['nama'] ?? $item['kuesioner'] ?? '' }}
                                        @if(isset($item['nama_matakuliah']))
                                            <br><small class="text-muted">{{ $item['nama_matakuliah'] }} ({{ $item['kode_matakuliah'] ?? '-' }})</small>
                                        @endif
                                        <span class="badge bg-warning text-dark">{{ $item['index_kepuasan'] ?? $item['index'] ?? '' }}</span>
                                    @else
                                        {{ $item }}
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Detail Semua Kuesioner -->
        @if(isset($hasil['top_5_tertinggi']) || isset($hasil['top_5_terendah']))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bi bi-list-ul"></i> Detail Semua Kuesioner</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="20%">Nama Kuesioner</th>
                                        <th width="15%">Matakuliah</th>
                                        <th width="8%">Kode MK</th>
                                        <th width="5%">Tingkat</th>
                                        <th width="15%">Dosen Pengampu</th>
                                        <th width="8%">Responden</th>
                                        <th width="10%">Index</th>
                                        <th width="10%">Persen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        // Get kuesioner data from aggregated data in service
                                        $kuesioneData = [];
                                        
                                        // Try to get from top_5_tertinggi and top_5_terendah combined
                                        $allKuesioner = [];
                                        if(isset($hasil['top_5_tertinggi'])) {
                                            $allKuesioner = array_merge($allKuesioner, $hasil['top_5_tertinggi']);
                                        }
                                        if(isset($hasil['top_5_terendah'])) {
                                            $allKuesioner = array_merge($allKuesioner, $hasil['top_5_terendah']);
                                        }
                                        
                                        // Remove duplicates by id
                                        $uniqueKuesioner = [];
                                        $seenIds = [];
                                        foreach($allKuesioner as $k) {
                                            if(is_array($k) && isset($k['id']) && !in_array($k['id'], $seenIds)) {
                                                $uniqueKuesioner[] = $k;
                                                $seenIds[] = $k['id'];
                                            }
                                        }
                                        
                                        // Sort by index_kepuasan descending
                                        usort($uniqueKuesioner, function($a, $b) {
                                            return ($b['index_kepuasan'] ?? 0) <=> ($a['index_kepuasan'] ?? 0);
                                        });
                                    @endphp
                                    
                                    @forelse($uniqueKuesioner as $idx => $kuesioner)
                                    <tr>
                                        <td>{{ $idx + 1 }}</td>
                                        <td>{{ $kuesioner['nama'] ?? '-' }}</td>
                                        <td>
                                            @if(isset($kuesioner['nama_matakuliah']) && $kuesioner['nama_matakuliah'])
                                                {{ $kuesioner['nama_matakuliah'] }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($kuesioner['kode_matakuliah']) && $kuesioner['kode_matakuliah'])
                                                {{ $kuesioner['kode_matakuliah'] }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if(isset($kuesioner['tingkat']) && $kuesioner['tingkat'])
                                                {{ $kuesioner['tingkat'] }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($kuesioner['dosen_pengampu']) && $kuesioner['dosen_pengampu'])
                                                {{ $kuesioner['dosen_pengampu'] }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $kuesioner['responden'] ?? 0 }}</td>
                                        <td>
                                            <span class="badge bg-primary">
                                                {{ number_format($kuesioner['index_kepuasan'] ?? 0, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                {{ number_format($kuesioner['persen_kepuasan'] ?? 0, 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-3">
                                            <i class="bi bi-info-circle"></i> Data kuesioner tidak tersedia
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if(count($uniqueKuesioner) > 0 && !isset($uniqueKuesioner[0]['nama_matakuliah']))
                        <div class="alert alert-warning mb-0 mt-3">
                            <i class="bi bi-exclamation-triangle"></i> 
                            <strong>Catatan:</strong> Data matakuliah, kode MK, tingkat, dan dosen pengampu tidak tersedia untuk laporan ini. 
                            Silakan generate laporan baru untuk melihat data lengkap.
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif
    @endif
    @endif

    <!-- RAG Metadata (if available) -->
    @if($laporan->status == 'completed' && isset($hasil['rag_metadata']))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-cpu"></i> RAG System Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="mb-2">
                                <i class="bi bi-diagram-3 fs-2 text-info"></i>
                            </div>
                            <h6 class="text-muted mb-1">Method</h6>
                            <p class="mb-0">
                                <span class="badge bg-info">{{ strtoupper($hasil['rag_metadata']['method'] ?? 'RAG') }}</span>
                            </p>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="mb-2">
                                <i class="bi bi-file-earmark-text fs-2 text-primary"></i>
                            </div>
                            <h6 class="text-muted mb-1">Chunks Used</h6>
                            <p class="mb-0 fs-5">{{ $hasil['rag_metadata']['chunks_used'] ?? 0 }}</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="mb-2">
                                <i class="bi bi-percent fs-2 text-success"></i>
                            </div>
                            <h6 class="text-muted mb-1">Avg Similarity</h6>
                            <p class="mb-0 fs-5">{{ number_format(($hasil['rag_metadata']['avg_similarity'] ?? 0) * 100, 1) }}%</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="mb-2">
                                <i class="bi bi-database fs-2 text-warning"></i>
                            </div>
                            <h6 class="text-muted mb-1">Sources</h6>
                            <p class="mb-0 fs-5">{{ $hasil['rag_metadata']['sources'] ?? 0 }}</p>
                        </div>
                    </div>
                    <hr>
                    <p class="text-muted mb-0 small">
                        <i class="bi bi-info-circle"></i> 
                        Laporan ini dihasilkan menggunakan teknologi <strong>RAG (Retrieval-Augmented Generation)</strong> 
                        yang menganalisis {{ $hasil['rag_metadata']['chunks_used'] ?? 0 }} potongan teks dari 
                        {{ $hasil['rag_metadata']['sources'] ?? 0 }} sumber kuesioner dengan tingkat relevansi rata-rata 
                        {{ number_format(($hasil['rag_metadata']['avg_similarity'] ?? 0) * 100, 1) }}%.
                    </p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Metadata -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Informasi Laporan</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Periode</th>
                                    <td>{{ $laporan->formatted_periode }}</td>
                                </tr>
                                <tr>
                                    <th>Program Studi</th>
                                    <td>{{ $laporan->prodi->nama_prodi ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Template</th>
                                    <td>{{ $laporan->template->nama_template ?? 'Format Default' }}</td>
                                </tr>
                                <tr>
                                    <th>AI Model</th>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ env('LLM_PROVIDER', 'default') }} - {{ env('OLLAMA_MODEL') ?: env('GROQ_MODEL') ?: env('LLM_MODEL', 'unknown') }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Dibuat Oleh</th>
                                    <td>{{ $laporan->user->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Dibuat Pada</th>
                                    <td>{{ $laporan->created_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>Diupdate Pada</th>
                                    <td>{{ $laporan->updated_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>RAG Mode</th>
                                    <td>
                                        @if(env('VECTOR_DB_ENABLED', false))
                                            <span class="badge bg-success">Advanced RAG (Vector DB)</span>
                                        @else
                                            <span class="badge bg-info">Simple RAG</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(in_array($laporan->status, ['pending', 'processing']))
<script>
    // Auto refresh every 5 seconds if status is pending or processing
    setTimeout(function() {
        location.reload();
    }, 5000);
</script>
@endif
@endsection
