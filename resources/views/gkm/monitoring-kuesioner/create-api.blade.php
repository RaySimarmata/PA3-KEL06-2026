@extends('layouts.app')

@section('page-title', 'Monitoring Kuesioner (API)')

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

    {{-- FILTER --}}
    <div class="filter-card mb-4">

        <form method="GET" id="filterForm">
            <div class="row g-3 align-items-end">

                {{-- TAHUN --}}
                <div class="col-md-3">
                    <label class="filter-label">Tahun Ajaran</label>
                    <select name="ta" class="form-select" required>
                        <option value="">Pilih Tahun</option>
                        @foreach($tahunList as $tahun)
                            <option value="{{ $tahun }}" {{ (int)$ta === (int)$tahun ? 'selected' : '' }}>
                                {{ $tahun }}/{{ $tahun + 1 }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- SEMESTER --}}
                <div class="col-md-3">
                    <label class="filter-label">Semester</label>
                    <select name="semester" class="form-select" required>
                        <option value="">Pilih Semester</option>
                        <option value="1" {{ ($semester ?? $semesterAktif) == 1 ? 'selected' : '' }}>
                            Ganjil
                        </option>
                        <option value="2" {{ ($semester ?? $semesterAktif) == 2 ? 'selected' : '' }}>
                            Genap
                        </option>
                    </select>
                </div>

                {{-- TINGKAT --}}
                <div class="col-md-3">
                    <label class="filter-label">Tingkat</label>
                    <select name="tingkat" class="form-select">
                        <option value="">Semua Tingkat</option>
                        <option value="1" {{ request('tingkat') == '1' ? 'selected' : '' }}>Tingkat 1</option>
                        <option value="2" {{ request('tingkat') == '2' ? 'selected' : '' }}>Tingkat 2</option>
                        <option value="3" {{ request('tingkat') == '3' ? 'selected' : '' }}>Tingkat 3</option>
                        <option value="4" {{ request('tingkat') == '4' ? 'selected' : '' }}>Tingkat 4</option>
                    </select>
                </div>

                {{-- BUTTON FILTER --}}
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100" style="padding: 0.6rem;">
                        <i class="bi bi-search"></i> Tampilkan Data
                    </button>
                </div>

            </div>
        </form>

        {{-- BUTTON ANALISIS SEMUA --}}
        @if(count($list) > 0)
            <div class="mt-3 pt-3 border-top">
                <form action="{{ route('gkm.monitoring-kuesioner.sync-semester') }}" method="POST" 
                      onsubmit="return confirm('Yakin ingin menganalisis semua kuesioner? Proses ini membutuhkan waktu.')">
                    @csrf
                    <input type="hidden" name="ta" value="{{ $ta }}">
                    <input type="hidden" name="semester" value="{{ $semester }}">
                    <input type="hidden" name="tingkat" value="{{ request('tingkat') }}">
                    
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-cpu"></i> Analisis Semua Kuesioner
                    </button>
                    <a href="{{ route('gkm.monitoring-kuesioner.index') }}" class="btn btn-secondary btn-kembali ms-2">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </form>
            </div>
        @endif
    </div>

    {{-- TABLE --}}
    <div class="monitoring-card">
        <div class="monitoring-header">
            <h6 style="text-transform: uppercase; letter-spacing: 0.5px;">Daftar Mata Kuliah</h6>
            @if(count($list) > 0)
                <div style="margin-left: auto;">
                    <span class="badge-gkm info" style="font-size: 0.9rem;">
                        {{ count($list) }} Mata Kuliah
                    </span>
                </div>
            @endif
        </div>

        <div class="table-responsive">
            @if(count($list) > 0)
                <table class="table table-monitoring">
                    <thead>
                        <tr>
                            <th style="width: 8%;">No</th>
                            <th style="width: 15%;">Kode MK</th>
                            <th style="width: 10%;">TA</th>
                            <th>Nama Mata Kuliah</th>
                            <th style="width: 15%;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $i => $item)
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td class="code-mk">{{ $item['kode_mk'] }}</td>
                                <td>
                                    <span class="badge-gkm primary">{{ $item['ta'] }}/{{ $item['ta'] + 1 }}</span>
                                </td>
                                <td>{{ $item['nama_mk'] }}</td>
                                <td class="text-center">
                                    <a href="{{ route('gkm.monitoring-kuesioner.listKuesioner', [
                                        'kode_mk' => $item['kode_mk'],
                                        'ta' => $item['ta']
                                    ]) }}" 
                                    class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-list-ul"></i> Lihat Kuesioner
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>Belum ada data ditampilkan</p>
                    <small>Silakan pilih Tahun Ajaran dan Semester terlebih dahulu</small>
                </div>
            @endif
        </div>
    </div>

</div>

<style>
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
@endsection