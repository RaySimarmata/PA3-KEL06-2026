@extends('layouts.app')

@section('page-title', 'Monitoring Kuesioner')

@section('content')
<div style="padding: 1.5rem;">

    {{-- ALERT --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            @foreach($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- FILTER --}}
    <div class="filter-card mb-4">
        <form method="GET" id="filterForm">
            <div class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label>Periode</label>
                    <select name="periode" class="form-select" onchange="filterForm.submit()">
                        <option value="">Semua</option>
                        <option value="genap_2025">Genap 2025/2026</option>
                        <option value="ganjil_2025">Ganjil 2025/2026</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Status</label>
                    <select name="status" class="form-select" onchange="filterForm.submit()">
                        <option value="">Semua</option>
                        <option value="uploaded">Uploaded</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="error">Error</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Prodi</label>
                    <select name="prodi" class="form-select" onchange="filterForm.submit()">
                        <option value="">Semua</option>
                        <option value="trpl">TRPL</option>
                        <option value="si">SI</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <button class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>

            </div>
        </form>
    </div>

    {{-- TABLE --}}
    <div class="monitoring-card">
        <div class="monitoring-header">
            <i class="bi bi-clipboard-data"></i>
            <h6>Monitoring Kuesioner</h6>

            <div style="margin-left:auto; display:flex; gap:10px;">
                <a href="{{ route('gkm.monitoring-kuesioner.create') }}" class="btn btn-primary">
                    <i class="bi bi-upload" style="color: white;"></i> Excel
                </a>

                <a href="{{ route('gkm.monitoring-kuesioner.create-api') }}" class="btn btn-success">
                    <i class="bi bi-cloud-download" style="color: white;"></i> API
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-monitoring">

                <thead>
                    <tr>
                        <th style="width: 3%;">No</th>
                        <th style="width: 15%;">Nama File</th>
                        <th style="width: 8%;">Periode</th>
                        <th style="width: 15%;">Matakuliah</th>
                        <th style="width: 8%;">Kode</th>
                        <th style="width: 5%;" class="text-center">Tingkat</th>
                        <th style="width: 15%;">Dosen</th>
                        <th style="width: 8%;">Prodi</th>
                        <th style="width: 6%;" class="text-center">Sumber</th>
                        <th style="width: 5%;" class="text-center">Resp.</th>
                        <th style="width: 5%;" class="text-center">Index</th>
                        <th style="width: 5%;" class="text-center">%</th>
                        <th style="width: 8%;" class="text-center">Status</th>
                        <th style="width: 10%;">Tanggal</th>
                        <th style="width: 8%;" class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($kuesioners as $i => $k)

                @php
                    $stat = $k->hasil_analisis['statistik'] ?? null;
                    $index = $stat['index_kepuasan'] ?? null;
                    $persen = $index ? ($index / 4) * 100 : null;

                    $color = 'secondary';
                    if ($index >= 3.5) $color = 'success';
                    elseif ($index >= 3.0) $color = 'primary';
                    elseif ($index >= 2.5) $color = 'warning';
                    elseif ($index) $color = 'danger';
                @endphp

                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>

                    <td>{{ $k->nama_file }}</td>
                    <td>{{ $k->periode }}</td>
                    <td>{{ $k->nama_matakuliah ?? '-' }}</td>
                    <td class="code-mk">{{ $k->kode_matakuliah ?? '-' }}</td>
                    <td class="text-center">{{ $k->tingkat ?? '-' }}</td>
                    <td class="dosen-name">
                        {{ \App\Models\Dosenn::where('pegawai_id', $k->pegawai_id)->first()->nama ?? '-' }}
                    </td>
                    <td>{{ $k->user->prodi->nama_prodi ?? '-' }}</td>

                    {{-- SUMBER --}}
                    <td class="text-center">
                        @if($k->sumber_data === 'api')
                            <span class="badge bg-success">API</span>
                        @else
                            <span class="badge bg-secondary">Excel</span>
                        @endif
                    </td>

                    {{-- RESPONDEN --}}
                    <td class="text-center">
                        <span class="badge bg-info">
                            {{ $k->total_responden }}
                        </span>
                    </td>

                    {{-- INDEX --}}
                    <td class="text-center">
                        @if($index)
                            <span class="badge bg-{{ $color }}">
                                {{ number_format($index, 2) }}
                            </span>
                        @else
                            -
                        @endif
                    </td>

                    {{-- PERSEN --}}
                    <td class="text-center">
                        @if($persen)
                            {{ number_format($persen, 1) }}%
                        @else
                            -
                        @endif
                    </td>

                    {{-- STATUS --}}
                    <td class="text-center">
                        @switch($k->status)
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

                    <td>{{ $k->created_at->format('d/m/Y H:i') }}</td>

                    {{-- AKSI --}}
                    <td class="text-center">
                        <div class="btn-group">

                            <a href="{{ route('gkm.monitoring-kuesioner.showa', $k->id) }}"
                               class="btn btn-sm btn-outline-primary"
                               title="Lihat Detail">
                                <i class="bi bi-eye"></i>
                            </a>

                            @if($k->status === 'completed')
                                <a href="{{ route('gkm.monitoring-kuesioner.report', $k->id) }}"
                                   class="btn btn-sm btn-outline-success"
                                   title="Lihat Laporan">
                                    <i class="bi bi-file-earmark-text"></i>
                                </a>
                            @endif

                            <form action="{{ route('gkm.monitoring-kuesioner.destroy', $k->id) }}"
                                  method="POST"
                                  onsubmit="return confirm('Yakin hapus data kuesioner ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>

                        </div>
                    </td>

                </tr>

                @empty
                <tr>
                    <td colspan="15" class="text-center py-5">
                        <div class="text-muted">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-3 mb-0">Belum ada data kuesioner</p>
                            <small>Silakan upload file Excel atau ambil data dari API</small>
                        </div>
                    </td>
                </tr>
                @endforelse
                </tbody>

            </table>
        </div>
    </div>

</div>

<script>
setInterval(() => {
    if (document.querySelector('.bg-warning')) {
        location.reload();
    }
}, 30000);
</script>

@endsection