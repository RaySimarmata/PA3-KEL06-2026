@extends('layouts.app')

@section('page-title', 'Master Periode Akademik')

@section('content')

<div style="padding: 1.5rem;">

    <!-- Header -->
    <div class="filter-card mb-4">
        <h5 class="mb-0 font-semibold" style="text-transform: uppercase; letter-spacing: 0.5px;">
            Master Periode Akademik
        </h5>
    </div>

    {{-- ALERT SUCCESS --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ERROR VALIDATION --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><i class="bi bi-exclamation-triangle"></i> Terjadi kesalahan:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- INFO PERIODE AKTIF --}}
    @php
        $aktif = $data->where('is_active', true)->first();
    @endphp

    <div class="filter-card mb-4">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-info-circle" style="color: #5B9BD5; font-size: 1.2rem;"></i>
            <div>
                <span class="text-muted">Periode Aktif:</span>
                <strong class="ms-2" style="color: #2c3e50;">
                    {{ $aktif
                        ? $aktif->tahun_ajaran . '/' . ($aktif->tahun_ajaran + 1) . ' - ' . $aktif->semester_label
                        : 'Belum ada periode aktif' }}
                </strong>
            </div>
        </div>
    </div>

    {{-- TABEL --}}
    <div class="monitoring-card">
        <div class="monitoring-header">
            <i class="bi bi-calendar3"></i>
            <h6>Daftar Periode Akademik</h6>
            <div style="margin-left: auto;">
                <button class="btn-action-primary" data-bs-toggle="modal" data-bs-target="#modalTambahPeriode">
                    <i class="bi bi-plus-circle"></i> Tambah Periode
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-monitoring">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 20%;">Tahun Ajaran</th>
                        <th style="width: 15%;">Semester</th>
                        <th style="width: 15%;">Tanggal Mulai</th>
                        <th style="width: 15%;">Tanggal Selesai</th>
                        <th style="width: 12%;" class="text-center">Status</th>
                        <th style="width: 18%;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $index => $item)
                        <tr class="{{ $item->is_active ? 'table-active-highlight' : '' }}">
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-semibold">{{ $item->tahun_ajaran }}/{{ $item->tahun_ajaran + 1 }}</td>
                            <td>
                                @if($item->semester_label == 'Ganjil')
                                    <span class="badge-gkm primary">{{ $item->semester_label }}</span>
                                @else
                                    <span class="badge-gkm warning">{{ $item->semester_label }}</span>
                                @endif
                            </td>
                            <td class="text-secondary">{{ \Carbon\Carbon::parse($item->start_date)->format('d M Y') }}</td>
                            <td class="text-secondary">
                                {{ $item->end_date ? \Carbon\Carbon::parse($item->end_date)->format('d M Y') : '-' }}
                            </td>
                            <td class="text-center">
                                @if($item->is_active)
                                    <span class="badge-gkm success">
                                        <i class="bi bi-check-circle"></i> Aktif
                                    </span>
                                @else
                                    <span class="badge-gkm secondary">Tidak Aktif</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(!$item->is_active)
                                    <form action="{{ route('gkm.data-master.periodeA.aktifkan', $item->id) }}"
                                          method="POST" style="display: inline-block;">
                                        @csrf
                                        <button class="btn btn-sm btn-warning"
                                                onclick="return confirm('Yakin ingin mengaktifkan periode ini?')"
                                                style="padding: 0.4rem 0.8rem;">
                                            <i class="bi bi-toggle-on"></i> Aktifkan
                                        </button>
                                    </form>
                                @else
                                    <span class="text-success fw-bold">
                                        <i class="bi bi-check-lg"></i> Sedang Aktif
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mt-2 mb-0">Belum ada data periode akademik</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<style>
.table-active-highlight {
    background-color: #e8f5e9 !important;
}
</style>

{{-- MODAL TAMBAH --}}
<div class="modal fade" id="modalTambahPeriode" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('gkm.data-master.periodeA.store') }}" method="POST">
                @csrf

                {{-- HEADER --}}
                <div class="modal-header" style="background: linear-gradient(135deg, #5B9BD5 0%, #4a8bc2 100%);">
                    <h5 class="modal-title text-white">
                        <i class="bi bi-plus-circle"></i> Tambah Periode Akademik
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                {{-- BODY --}}
                <div class="modal-body" style="padding: 1.5rem;">
                    <div class="row g-3">
                        {{-- Tahun Ajaran --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tahun Ajaran</label>
                            <input type="number" name="tahun_ajaran" value="{{ old('tahun_ajaran') }}"
                                   class="form-control" placeholder="Contoh: 2025" min="2000" max="2100" required>
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> Contoh: 2025 untuk tahun ajaran 2025/2026
                            </small>
                        </div>

                        {{-- Semester --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Semester</label>
                            <select name="semester" class="form-select" required>
                                <option value="" disabled {{ old('semester') ? '' : 'selected' }}>-- Pilih Semester --</option>
                                <option value="1" {{ old('semester') == '1' ? 'selected' : '' }}>Ganjil</option>
                                <option value="2" {{ old('semester') == '2' ? 'selected' : '' }}>Genap</option>
                            </select>
                        </div>

                        {{-- Tanggal Mulai --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Mulai</label>
                            <input type="date" name="start_date" value="{{ old('start_date') }}" 
                                   class="form-control" required>
                        </div>

                        {{-- Tanggal Selesai --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Selesai</label>
                            <input type="date" name="end_date" value="{{ old('end_date') }}" class="form-control">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> Opsional
                            </small>
                        </div>
                    </div>
                </div>

                {{-- FOOTER --}}
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Batal
                    </button>
                    <button type="submit" class="btn-action-primary">
                        <i class="bi bi-save"></i> Simpan Periode
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection