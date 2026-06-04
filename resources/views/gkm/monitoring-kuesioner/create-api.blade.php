@extends('layouts.app')

@section('page-title', 'Monitoring Kuesioner (API)')

@section('content')
<div style="padding: 1.5rem;">

    {{-- ALERT --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
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

                {{-- TAHUN --}}
                <div class="col-md-3">
                    <label class="form-label">Tahun Ajaran</label>
                    <select name="ta" class="form-select" required>
                        <option value="">Pilih Tahun</option>
                        @foreach($tahunList as $tahun)
                            <option value="{{ $tahun }}" {{ (int)$ta === (int)$tahun ? 'selected' : '' }}>
                                {{ $tahun }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- SEMESTER --}}
                <div class="col-md-3">
                    <label class="form-label">Semester</label>
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
                    <label class="form-label">Tingkat</label>
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
                    <button class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Tampilkan Mata Kuliah
                    </button>
                </div>

            </div>
        </form>

        {{-- BUTTON ANALISIS SEMUA --}}
        <div class="mt-3">
            <form action="{{ route('gkm.monitoring-kuesioner.sync-semester') }}" method="POST">
                @csrf
                <input type="hidden" name="ta" value="{{ $ta }}">
                <input type="hidden" name="semester" value="{{ $semester }}">
                <input type="hidden" name="tingkat" value="{{ request('tingkat') }}">
                
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-cpu"></i> Analisis Semua Kuesioner
                </button>
            </form>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="monitoring-card">
        <div class="monitoring-header">
            <i class="bi bi-book"></i>
            <h6>Daftar Mata Kuliah</h6>
        </div>

        <div class="table-responsive">
            @if(count($list) > 0)
                <table class="table table-monitoring">
                    <thead>
                        <tr>
                            <th style="width: 8%;">No</th>
                            <th style="width: 15%;">Kode MK</th>
                            <th>Nama Mata Kuliah</th>
                            <th style="width: 15%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $i => $item)
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td>
                                    <strong>{{ $item['kode_mk'] }}</strong>
                                    <br>
                                    <small class="text-muted">TA: {{ $item['ta'] }}</small>
                                </td>
                                <td>{{ $item['nama_mk'] }}</td>
                                <td class="text-center">
                                    <a href="{{ route('gkm.monitoring-kuesioner.listKuesioner', [
                                        'kode_mk' => $item['kode_mk'],
                                        'ta' => $item['ta']
                                    ]) }}" 
                                    class="btn btn-primary btn-sm">
                                        <i class="bi bi-list-ul"></i> Lihat Kuesioner
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-3 mb-0">Belum ada data ditampilkan</p>
                    <small>Silakan pilih Tahun Ajaran dan Semester terlebih dahulu</small>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection