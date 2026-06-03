@extends('layouts.app')

@section('content')
<div class="container">

    <h4 class="mb-4">Monitoring Kuesioner (API)</h4>

    {{-- 🔥 ALERT SUCCESS --}}
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- 🔥 ALERT ERROR --}}
    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    {{-- 🔥 VALIDATION ERROR --}}
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif
{{-- 🔎 FILTER --}}
<div class="card mb-4">
    <div class="card-body">

        <form method="GET" class="row g-3">

            {{-- TAHUN --}}
            <div class="col-md-3">
                <label class="form-label">Tahun Ajaran</label>

                <select name="ta" class="form-select" required>

                    <option value="">-- Pilih Tahun --</option>

                    @foreach($tahunList as $tahun)

                        <option value="{{ $tahun }}"
                            {{ (int)$ta === (int)$tahun ? 'selected' : '' }}>

                            {{ $tahun }}

                        </option>

                    @endforeach

                </select>
            </div>

            {{-- SEMESTER --}}
            <div class="col-md-3">
                <label class="form-label">Semester</label>

                <select name="semester" class="form-select" required>

                    <option value="">-- Pilih Semester --</option>

                    <option value="1"
                        {{ ($semester ?? $semesterAktif) == 1 ? 'selected' : '' }}>
                        Ganjil
                    </option>

                    <option value="2"
                        {{ ($semester ?? $semesterAktif) == 2 ? 'selected' : '' }}>
                        Genap
                    </option>

                </select>
            </div>

            {{-- TINGKAT --}}
            <div class="col-md-3">
                <label class="form-label">Tingkat</label>

                <select name="tingkat" class="form-select">

                    <option value="">Semua Tingkat</option>

                    <option value="1"
                        {{ request('tingkat') == '1' ? 'selected' : '' }}>
                        Tingkat 1
                    </option>

                    <option value="2"
                        {{ request('tingkat') == '2' ? 'selected' : '' }}>
                        Tingkat 2
                    </option>

                    <option value="3"
                        {{ request('tingkat') == '3' ? 'selected' : '' }}>
                        Tingkat 3
                    </option>

                    <option value="4"
                        {{ request('tingkat') == '4' ? 'selected' : '' }}>
                        Tingkat 4
                    </option>

                </select>
            </div>

            {{-- BUTTON FILTER --}}
            <div class="col-md-3 d-flex align-items-end">

                <button class="btn btn-primary w-100">
                    🔍 Tampilkan Mata Kuliah
                </button>

            </div>

        </form>

        {{-- 🔥 BUTTON SYNC DIPISAH --}}
        <div class="mt-3">

            <form action="{{ route('gkm.monitoring-kuesioner.sync-semester') }}"
                  method="POST">

                @csrf

                {{-- biar ikut filter yg dipilih --}}
                <input type="hidden" name="ta" value="{{ $ta }}">
                <input type="hidden" name="semester" value="{{ $semester }}">
                <input type="hidden" name="tingkat" value="{{ request('tingkat') }}">

                <button class="btn btn-success">
                    📊 Analisis Semua Kuesioner
                </button>

            </form>

        </div>

    </div>
</div>
    {{-- 📋 TABEL --}}
    <div class="card">
        <div class="card-body">

            @if(count($list) > 0)

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">No</th>
                                <th>Kode MK</th>
                                <th>Nama Mata Kuliah</th>
                                <th style="width: 180px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($list as $i => $item)
                                <tr>
                                    <td>{{ $i + 1 }}</td>

                                    <td>
                                        <strong>{{ $item['kode_mk'] }}</strong>
                                        <br>
                                        <small class="text-muted">TA: {{ $item['ta'] }}</small>
                                    </td>

                                    <td>{{ $item['nama_mk'] }}</td>

                                    <td>
                                        <a href="{{ route('gkm.monitoring-kuesioner.listKuesioner', [
                                            'kode_mk' => $item['kode_mk'],
                                            'ta' => $item['ta']
                                        ]) }}" 
                                        class="btn btn-info btn-sm w-100">
                                            📋 Lihat Kuesioner
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @else

                <div class="text-center text-muted py-4">
                    <p class="mb-0">📭 Belum ada data ditampilkan</p>
                    <small>Silakan pilih Tahun Ajaran dan Semester terlebih dahulu</small>
                </div>

            @endif

        </div>
    </div>

</div>
@endsection