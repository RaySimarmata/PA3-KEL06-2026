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
                    <i class="bi bi-search"></i> Cari
                </button>
                </div>

            </div>
        </form>

        {{-- BUTTON ANALISIS SEMUA --}}
        @if(isset($pagination) && $pagination->total() > 0)
            <div class="mt-3 pt-3 border-top">
                <form action="{{ route('gkm.monitoring-kuesioner.sync-semester') }}" method="POST" 
                      onsubmit="return confirm('Yakin ingin menganalisis semua kuesioner? Proses ini membutuhkan waktu.')">
                    @csrf
                    <input type="hidden" name="ta" value="{{ $ta }}">
                    <input type="hidden" name="semester" value="{{ $semester }}">
                    <input type="hidden" name="tingkat" value="{{ request('tingkat') }}">
                    
                    <button type="submit" class="btn-analisis-semua">
                        Analisis Semua Kuesioner
                    </button>
                    <a href="{{ route('gkm.monitoring-kuesioner.index') }}" class="btn-kembali-api ms-2">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </form>
            </div>
        @endif
    </div>

    {{-- TABLE --}}
    <div class="monitoring-card">
        <div class="monitoring-header">
            <i class="bi bi-list-ul" style="color: #5B9BD5;"></i>
            <h6>Daftar Mata Kuliah</h6>
            @if(isset($pagination) && $pagination->total() > 0)
                <div style="margin-left: auto;">
                    <span class="badge-gkm info" style="font-size: 0.9rem;">
                        {{ $pagination->total() }} Mata Kuliah
                    </span>
                </div>
            @endif
        </div>

        <div class="table-responsive">
            @if(isset($pagination) && $pagination->total() > 0)
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
                        @foreach($pagination as $i => $item)
                            <tr>
                                <td class="text-center">{{ ($pagination->currentPage() - 1) * $pagination->perPage() + $i + 1 }}</td>
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
                                    class="btn btn-sm btn-lihat-kuesioner-api">
                                        {{-- <i class="bi bi-list-ul"></i> --}}

                                        Lihat Kuesioner
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Pagination -->
                @if(method_exists($pagination, 'links') && $pagination->total() > $pagination->perPage())
                <div class="mt-4 d-flex justify-content-center">
                    {{ $pagination->links() }}
                </div>
                @endif
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
    /* Tombol Lihat Kuesioner - Biru Tua, tidak berubah saat hover */
    .btn-lihat-kuesioner-api {
        background-color: #2c5282 !important;
        border-color: #2c5282 !important;
        color: white !important;
        transition: none !important;
    }

    .btn-lihat-kuesioner-api:hover,
    .btn-lihat-kuesioner-api:focus,
    .btn-lihat-kuesioner-api:active,
    .btn-lihat-kuesioner-api:active:focus {
        background-color: #2c5282 !important;
        border-color: #2c5282 !important;
        color: white !important;
        box-shadow: none !important;
        transform: none !important;
    }

    /* Tombol Analisis Semua - Hijau, tidak berubah saat hover */
    .btn-analisis-semua {
        background-color: #198754 !important;
        border: 1px solid #198754 !important;
        color: white !important;
        padding: 0.375rem 0.75rem;
        border-radius: 0.25rem;
        font-size: 1rem;
        transition: none !important;
        cursor: pointer;
        display: inline-block;
    }

    .btn-analisis-semua:hover,
    .btn-analisis-semua:focus,
    .btn-analisis-semua:active,
    .btn-analisis-semua:active:focus {
        background-color: #198754 !important;
        border-color: #198754 !important;
        color: white !important;
        box-shadow: none !important;
        transform: none !important;
    }

    /* Tombol Kembali - Abu-abu, tidak berubah saat hover */
    .btn-kembali-api {
        background-color: #6c757d !important;
        border: 1px solid #6c757d !important;
        color: white !important;
        padding: 0.375rem 0.75rem;
        border-radius: 0.25rem;
        font-size: 1rem;
        transition: none !important;
        text-decoration: none;
        display: inline-block;
    }

    .btn-kembali-api:hover,
    .btn-kembali-api:focus,
    .btn-kembali-api:active,
    .btn-kembali-api:active:focus,
    .btn-kembali-api:visited {
        background-color: #6c757d !important;
        border-color: #6c757d !important;
        color: white !important;
        box-shadow: none !important;
        transform: none !important;
        text-decoration: none;
    }

    /* Custom Pagination Style - Modern & Clean */
    .pagination {
        gap: 0.5rem;
        margin: 0;
    }

    .pagination .page-item {
        margin: 0;
    }

    .pagination .page-link {
        color: #495057;
        background-color: #ffffff;
        border: 1px solid #e0e0e0;
        padding: 0.5rem 0.85rem;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
        font-weight: 500;
        min-width: 2.5rem;
        text-align: center;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .pagination .page-link:hover {
        background-color: #f8f9fa;
        border-color: #5B9BD5;
        color: #5B9BD5;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(91, 155, 213, 0.15);
    }

    .pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #5B9BD5 0%, #4a8bc2 100%);
        border-color: #5B9BD5;
        color: white !important;
        font-weight: 600;
        box-shadow: 0 3px 8px rgba(91, 155, 213, 0.3);
        transform: translateY(-1px);
    }

    .pagination .page-item.active .page-link:hover {
        background: linear-gradient(135deg, #4a8bc2 0%, #3d7aad 100%);
        transform: translateY(-1px);
    }

    .pagination .page-item.disabled .page-link {
        color: #ced4da;
        background-color: #f8f9fa;
        border-color: #e9ecef;
        cursor: not-allowed;
        box-shadow: none;
    }

    /* Arrow buttons special styling */
    .pagination .page-item:first-child .page-link,
    .pagination .page-item:last-child .page-link {
        font-weight: 600;
    }
</style>
@endsection