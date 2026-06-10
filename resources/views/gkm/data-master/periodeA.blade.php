@extends('layouts.app')

@section('page-title', 'Data Master - Data Periode Akademik')

@section('content')

<div style="padding: 1.5rem;">

    <!-- Header -->
    <div class="filter-card mb-4 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 font-semibold" style="text-transform: uppercase; letter-spacing: 0.5px;">
            Data Periode Akademik
        </h5>
        <a href="{{ route('gkm.data-master.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    {{-- TIDAK ADA ALERT SUCCESS --}}

    {{-- ERROR VALIDATION --}}
    @if ($errors->any())
        <div class="alert-app danger mb-3">
            <i class="bi bi-exclamation-triangle-fill alert-app-icon"></i>
            <div class="alert-app-body">
                <div class="alert-app-title">Terjadi kesalahan:</div>
                <ul class="mb-0" style="font-size:0.875rem; padding-left:1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- INFO PERIODE AKTIF --}}
    @php
        $aktif = $data->where('is_active', true)->first();
    @endphp

    <div class="filter-card mb-4">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-info-circle" style="color: #1e3c72; font-size: 1.2rem;"></i>
            <div>
                <span class="text-muted">Periode Aktif:</span>
                <strong class="ms-2" style="color: #1e3c72;">
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
            <h6>Daftar Periode Akademik</h6>
            <div style="margin-left: auto;">
                <button class="btn-action-primary" data-bs-toggle="modal" data-bs-target="#modalTambahPeriode">
                    Tambah Periode
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
                                    <span class="badge-soft-primary">{{ $item->semester_label }}</span>
                                @else
                                    <span class="badge-soft-warning">{{ $item->semester_label }}</span>
                                @endif
                            </td>
                            <td class="text-secondary">{{ \Carbon\Carbon::parse($item->start_date)->format('d M Y') }}</td>
                            <td class="text-secondary">
                                {{ $item->end_date ? \Carbon\Carbon::parse($item->end_date)->format('d M Y') : '-' }}
                            </td>
                            <td class="text-center">
                                @if($item->is_active)
                                    <span class="badge-soft-success">
                                        <i class="bi bi-check-circle-fill"></i> Aktif
                                    </span>
                                @else
                                    <span class="badge-soft-danger">
                                        <i class="bi bi-x-circle-fill"></i> Tidak Aktif
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(!$item->is_active)
                                    <form action="{{ route('gkm.data-master.periodeA.aktifkan', $item->id) }}"
                                          method="POST" style="display: inline-block;">
                                        @csrf
                                        <button class="btn btn-sm btn-success"
                                                onclick="AppConfirm.ask(this.closest('form'), 'Aktifkan Periode?', 'Periode ini akan dijadikan periode aktif.'); return false;"
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
/* Highlight baris aktif */
.table-active-highlight {
    background-color: #e8f5e9 !important;
}

/* Badge soft dengan ukuran dan bentuk seperti badge-gkm asli */
.badge-soft-primary,
.badge-soft-warning,
.badge-soft-success,
.badge-soft-danger {
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.badge-soft-primary {
    background-color: rgba(13, 110, 253, 0.15);
    color: #0a58ca;
}

.badge-soft-warning {
    background-color: rgba(255, 193, 7, 0.2);
    color: #b85c00;
}

.badge-soft-success {
    background-color: rgba(25, 135, 84, 0.15);
    color: #146c43;
}

.badge-soft-danger {
    background-color: rgba(220, 53, 69, 0.15);
    color: #b02a37;
}
</style>

{{-- MODAL TAMBAH --}}
<div class="modal fade" id="modalTambahPeriode" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('gkm.data-master.periodeA.store') }}" method="POST">
                @csrf
                <div class="modal-header" style="background: linear-gradient(135deg, #5B9BD5 0%, #4a8bc2 100%);">
                    <h5 class="modal-title text-white">
                        <i class="bi bi-plus-circle"></i> Tambah Periode Akademik
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 1.5rem;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tahun Ajaran</label>
                            <input type="number" name="tahun_ajaran" value="{{ old('tahun_ajaran') }}"
                                   class="form-control" placeholder="Contoh: 2025" min="2000" max="2100" required>
                            <small class="text-muted"><i class="bi bi-info-circle"></i> Contoh: 2025 untuk tahun ajaran 2025/2026</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Semester</label>
                            <select name="semester" class="form-select" required>
                                <option value="" disabled {{ old('semester') ? '' : 'selected' }}>-- Pilih Semester --</option>
                                <option value="1" {{ old('semester') == '1' ? 'selected' : '' }}>Ganjil</option>
                                <option value="2" {{ old('semester') == '2' ? 'selected' : '' }}>Genap</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Mulai</label>
                            <input type="date" name="start_date" value="{{ old('start_date') }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Selesai</label>
                            <input type="date" name="end_date" value="{{ old('end_date') }}" class="form-control">
                            <small class="text-muted"><i class="bi bi-info-circle"></i> Opsional</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                         Batal
                    </button>
                    <button type="submit" class="btn-action-primary">
                         Simpan Periode
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL KONFIRMASI AKTIVASI PERIODE --}}
<div class="modal fade" id="modalKonfirmasiAktivasi" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background: linear-gradient(135deg, #5B9BD5 0%, #4a8bc2 100%);">

                <h5 class="modal-title text-white">
                    
                    Konfirmasi Aktivasi
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-arrow-repeat" style="font-size: 3rem; color:#5B9BD5 0%;"></i>
                <h5 class="mt-3 fw-semibold">Periode Berhasil Diaktifkan!</h5>
                <p class="text-muted mb-0">
                    Periode berhasil diaktifkan dan sync jadwal dosen dimulai.
                </p>
            </div>
        <div class="modal-footer justify-content-center">
            <button type="button" class="btn" data-bs-dismiss="modal" style="background-color: #5B9BD5; border-color: #5B9BD5; color: white;">
                <i class="bi bi-check-lg"></i> OK
            </button>
        </div>
        </div>
    </div>
</div>

@if(session('success') && str_contains(session('success'), 'berhasil diaktifkan'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('modalKonfirmasiAktivasi'));
        modal.show();
    });
</script>
@endif

@endsection