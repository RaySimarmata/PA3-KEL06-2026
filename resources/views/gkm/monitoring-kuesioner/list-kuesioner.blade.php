@extends('layouts.app')

@section('page-title', 'Detail Kuesioner')

@section('content')
<div style="padding: 1.5rem;">

    {{-- ALERT --}}
    @if(session('success'))
        <div class="alert-gkm success mb-4">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert-gkm danger mb-4">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert-gkm danger mb-4">
            <h6 style="font-weight: 600; margin-bottom: 0.5rem;">
                <i class="bi bi-exclamation-triangle"></i> Terjadi Kesalahan
            </h6>
            <ul class="mb-0" style="font-size: 0.875rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- INFO CARD --}}
    <div class="filter-card mb-4">
        <div class="row g-3 align-items-center">
            <div class="col-md-4">
                <label class="filter-label">Kode Mata Kuliah</label>
                <div style="font-weight: 600; color: #333; font-size: 1.1rem;">{{ $kode_mk }}</div>
            </div>
            <div class="col-md-4">
                <label class="filter-label">Tahun Ajaran</label>
                <div>
                    <span class="badge-gkm primary" style="font-size: 0.95rem;">{{ $ta }}/{{ $ta + 1 }}</span>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <a href="{{ route('gkm.monitoring-kuesioner.create-api') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="monitoring-card">

        <div class="table-responsive">
            @if(count($list) > 0)
                <table class="table table-monitoring">
                    <thead>
                        <tr>
                            <th style="width: 8%;">No</th>
                            <th>Judul Kuesioner</th>
                            <th style="width: 12%;" class="text-center">Semester</th>
                            <th style="width: 15%;" class="text-center">ID</th>
                            <th style="width: 20%;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $i => $item)
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td>
                                <strong style="color: #333;">{{ $item['judul'] }}</strong>
                                <br>
                                <small class="text-muted">
                                    <i class="bi bi-book"></i> {{ $item['kode_mk'] }}
                                </small>
                            </td>
                            <td class="text-center">
                                @if(isset($item['semester']))
                                    @if($item['semester'] == 1)
                                        <span class="badge-gkm primary">Ganjil</span>
                                    @else
                                        <span class="badge-gkm warning">Genap</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge-gkm info">{{ $item['kuesioner_id'] ?? '-' }}</span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('gkm.monitoring-kuesioner.showa', $item['kuesioner_id']) }}"
                                       class="btn btn-sm btn-lihat-kuesioner"
                                       title="Lihat Detail">
                                        <i class="bi bi-eye"></i> Lihat
                                    </a>

                                    <form action="{{ route('gkm.monitoring-kuesioner.processFromApi') }}" 
                                          method="POST" 
                                          class="d-inline"
                                          onsubmit="return confirm('Analisis kuesioner ini dengan AI?')">
                                        @csrf
                                        <input type="hidden" name="kode_mk" value="{{ $item['kode_mk'] }}">
                                        <input type="hidden" name="ta" value="{{ $item['ta'] }}">
                                        <input type="hidden" name="kuesioner_id" value="{{ $item['kuesioner_id'] }}">
                                        <input type="hidden" name="semester" value="{{ $item['semester'] ?? null }}">
                                        
                                        <button type="submit" 
                                                class="btn btn-sm btn-analisis-kuesioner"
                                                title="Analisis dengan AI">
                                            <i class="bi bi-cpu"></i> Analisis
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>Tidak ada kuesioner ditemukan</p>
                    <small>Silakan coba mata kuliah lain atau periode berbeda</small>
                </div>
            @endif
        </div>
    </div>

</div>

<style>
    /* Tombol Lihat - Warna biru tua solid, tidak berubah saat hover */
    .btn-lihat-kuesioner {
        background-color: #2c5282 !important;
        border-color: #2c5282 !important;
        color: white !important;
        transition: none !important;
    }

    .btn-lihat-kuesioner:hover,
    .btn-lihat-kuesioner:focus,
    .btn-lihat-kuesioner:active,
    .btn-lihat-kuesioner:active:focus,
    .btn-lihat-kuesioner.active {
        background-color: #2c5282 !important;
        border-color: #2c5282 !important;
        color: white !important;
        box-shadow: none !important;
    }

    /* Tombol Analisis - Warna hijau solid seperti btn-success, tidak berubah saat hover */
    .btn-analisis-kuesioner {
        background-color: #198754 !important;
        border-color: #198754 !important;
        color: white !important;
        transition: none !important;
    }

    .btn-analisis-kuesioner:hover,
    .btn-analisis-kuesioner:focus,
    .btn-analisis-kuesioner:active,
    .btn-analisis-kuesioner:active:focus,
    .btn-analisis-kuesioner.active {
        background-color: #198754 !important;
        border-color: #198754 !important;
        color: white !important;
        box-shadow: none !important;
    }

    /* Hapus efek hover pada group button */
    .btn-group .btn-lihat-kuesioner:hover,
    .btn-group .btn-lihat-kuesioner:focus,
    .btn-group .btn-analisis-kuesioner:hover,
    .btn-group .btn-analisis-kuesioner:focus {
        z-index: auto !important;
    }
</style>

@endsection