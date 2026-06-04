
@extends('layouts.app')

@section('page-title', 'Master Mata Kuliah')

@section('content')

<div style="padding: 1.5rem;">

    <!-- Header -->
    <div class="filter-card mb-4 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 font-semibold" style="text-transform: uppercase; letter-spacing: 0.5px;">
            Master Mata Kuliah
        </h5>
        <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    {{-- ALERT --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- FILTER --}}
    <div class="filter-card mb-4">
        <form method="GET">
            <div class="row g-3 align-items-end">
                {{-- TA --}}
                <div class="col-md-2">
                    <label class="filter-label">Tahun Ajaran</label>
                    <input type="number" name="ta" class="form-control" value="{{ $ta }}" min="2000" max="2100">
                </div>

                {{-- SEMESTER --}}
                <div class="col-md-2">
                    <label class="filter-label">Semester</label>
                    <select name="semester" class="form-select">
                        <option value="1" {{ $semester == 1 ? 'selected' : '' }}>Ganjil</option>
                        <option value="2" {{ $semester == 2 ? 'selected' : '' }}>Genap</option>
                    </select>
                </div>

                {{-- TINGKAT --}}
                <div class="col-md-2">
                    <label class="filter-label">Tingkat</label>
                    <select name="tingkat" class="form-select">
                        <option value="">Semua Tingkat</option>
                        <option value="1" {{ $selectedTingkat == '1' ? 'selected' : '' }}>Tingkat 1</option>
                        <option value="2" {{ $selectedTingkat == '2' ? 'selected' : '' }}>Tingkat 2</option>
                        <option value="3" {{ $selectedTingkat == '3' ? 'selected' : '' }}>Tingkat 3</option>
                        <option value="4" {{ $selectedTingkat == '4' ? 'selected' : '' }}>Tingkat 4</option>
                    </select>
                </div>

                {{-- SEARCH --}}
                <div class="col-md-4">
                    <label class="filter-label">Cari Mata Kuliah</label>
                    <div class="position-relative">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Cari nama / kode mata kuliah..." 
                                   value="{{ request('search') }}">
                        </div>
                    </div>
                </div>

                {{-- BUTTON --}}
        <div class="col-md-2">
            <label class="filter-label" style="opacity: 0;">Action</label>
            <button class="btn btn-primary w-100" type="button" id="btnSearch">
                <i class="bi bi-search me-1"></i> Cari
            </button>
        </div>
            </div>
        </form>

        <!-- Info Total -->
        <div class="mt-3 pt-3 border-top">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-info-circle" style="color: #5B9BD5;"></i>
                <span class="text-muted">Total Mata Kuliah:</span>
                <span class="badge-gkm primary" style="font-size: 0.95rem;">
                    {{ count($matakuliahList) }} Mata Kuliah
                </span>
            </div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="monitoring-card">
        <div class="monitoring-header">
            {{-- <i class="bi bi-book"></i> --}}
            <h6>Daftar Mata Kuliah</h6>
        </div>

        <div class="table-responsive">
            <table class="table table-monitoring">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 15%;">Kode MK</th>
                        <th style="width: 40%;">Nama Mata Kuliah</th>
                        <th style="width: 10%;">SKS</th>
                        <th style="width: 12%;">Semester</th>
                        <th style="width: 18%;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($matakuliahList as $index => $mk)
                        <tr>
                            {{-- NO --}}
                            <td>{{ $index + 1 }}</td>

                            {{-- KODE MK --}}
                            <td>
                                <span class="code-mk">{{ $mk['kode_mk'] ?? '-' }}</span>
                            </td>

                            {{-- NAMA MK --}}
                            <td class="nama-mk" style="text-transform: uppercase;">
                                {{ $mk['nama_matkul'] ?? '-' }}
                            </td>

                            {{-- SKS --}}
                            <td>
                                <span class="badge-gkm success">
                                    {{ $mk['sks'] ?? '-' }} SKS
                                </span>
                            </td>

                            {{-- SEMESTER --}}
                            <td>
                                @if($semester == 1)
                                    <span class="badge-gkm primary">Ganjil</span>
                                @else
                                    <span class="badge-gkm warning">Genap</span>
                                @endif
                            </td>

                            {{-- AKSI --}}
                            <td class="text-center">
                                <a href="{{ route('gkm.data-master.matakuliah.detail', [
                                    'kodeMk' => $mk['kode_mk'],
                                    'ta' => $ta,
                                    'semester' => $semester
                                ]) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mt-2 mb-0">Tidak ada data mata kuliah</p>
                                    <small>Coba ubah filter pencarian Anda</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection