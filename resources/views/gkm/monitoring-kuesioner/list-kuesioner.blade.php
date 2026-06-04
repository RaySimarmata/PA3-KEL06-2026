@extends('layouts.app')

@section('page-title', 'Daftar Kuesioner')

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

    {{-- INFO CARD --}}
    <div class="filter-card mb-4">
        <div class="row g-3 align-items-center">
            <div class="col-md-3">
                <label class="form-label text-muted">Kode Mata Kuliah</label>
                <div class="fw-bold">{{ $kode_mk }}</div>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted">Tahun Ajaran</label>
                <div class="fw-bold">{{ $ta }}</div>
            </div>
            <div class="col-md-6 text-end">
                <a href="{{ route('gkm.monitoring-kuesioner.create-api') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="monitoring-card">
        <div class="monitoring-header">
            <i class="bi bi-list-check"></i>
            <h6>Daftar Kuesioner</h6>
        </div>

        <div class="table-responsive">
            @if(count($list) > 0)
                <table class="table table-monitoring">
                    <thead>
                        <tr>
                            <th style="width: 8%;">No</th>
                            <th>Judul Kuesioner</th>
                            <th style="width: 10%;" class="text-center">Semester</th>
                            <th style="width: 20%;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $i => $item)
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td>
                                <strong>{{ $item['judul'] }}</strong>
                                <br>
                                <small class="text-muted">
                                    Kode MK: {{ $item['kode_mk'] }} | 
                                    ID: {{ $item['kuesioner_id'] ?? '-' }}
                                </small>
                            </td>
                            <td class="text-center">
                                @if(isset($item['semester']))
                                    <span class="badge bg-info">
                                        {{ $item['semester'] == 1 ? 'Ganjil' : 'Genap' }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-2 justify-content-center">
                                    <a href="{{ route('gkm.monitoring-kuesioner.showa', $item['kuesioner_id']) }}"
                                       class="btn btn-primary"
                                       style="border-radius: 8px; padding: 8px 20px;">
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
                                                class="btn btn-success"
                                                style="border-radius: 8px; padding: 8px 20px;">
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
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-3 mb-0">Tidak ada kuesioner ditemukan</p>
                    <small>Silakan coba mata kuliah lain atau periode berbeda</small>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection