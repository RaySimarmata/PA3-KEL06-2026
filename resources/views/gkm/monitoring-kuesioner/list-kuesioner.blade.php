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
            @if(isset($pagination) && $pagination->total() > 0)
                <table class="table table-monitoring">
                    <thead>
                        <tr>
                            <th style="width: 8%;">No</th>
                            <th>Judul Kuesioner</th>
                            <th style="width: 12%;" class="text-center">Semester</th>
                            <th style="width: 20%;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pagination as $i => $item)
                        <tr>
                            <td class="text-center">{{ ($pagination->currentPage() - 1) * $pagination->perPage() + $i + 1 }}</td>
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

                <!-- Pagination -->
                @if(method_exists($pagination, 'links') && $pagination->total() > $pagination->perPage())
                <div class="mt-4 d-flex justify-content-center">
                    {{ $pagination->links() }}
                </div>
                @endif
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
        border-left: none !important;
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
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
        border-left: none !important;
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
    }

    /* Hapus efek hover pada group button */
    .btn-group .btn-lihat-kuesioner:hover,
    .btn-group .btn-lihat-kuesioner:focus,
    .btn-group .btn-analisis-kuesioner:hover,
    .btn-group .btn-analisis-kuesioner:focus {
        z-index: auto !important;
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