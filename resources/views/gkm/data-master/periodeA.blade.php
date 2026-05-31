@extends('layouts.app')

@section('page-title', 'Master Periode Akademik')

@section('content')

<div style="padding: 1.5rem;">

    {{-- ALERT SUCCESS --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
            </button>
        </div>
    @endif

    {{-- ERROR VALIDATION --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">

            <strong>Terjadi kesalahan:</strong>

            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
            </button>

        </div>
    @endif


    {{-- INFO PERIODE AKTIF --}}
    @php
        $aktif = $data->where('is_active', true)->first();
    @endphp

    <div class="alert alert-info shadow-sm">

        <i class="bi bi-info-circle"></i>

        Periode Aktif :

        <strong>
            {{ $aktif
                ? $aktif->tahun_ajaran . '/' . ($aktif->tahun_ajaran + 1) . ' - ' . $aktif->semester_label
                : 'Belum ada periode aktif' }}
        </strong>

    </div>

    {{-- TABEL --}}
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">

    <strong class="mb-0">
        Daftar Periode Akademik
    </strong>

    <button class="btn btn-primary btn-sm"
            data-bs-toggle="modal"
            data-bs-target="#modalTambahPeriode">

        <i class="bi bi-plus-circle"></i>
        Tambah Periode
    </button>

</div>

        <div class="card-body table-responsive">

            <table class="table table-hover align-middle text-center">

                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Tahun Ajaran</th>
                        <th>Semester</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Status</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($data as $index => $item)

                        <tr class="{{ $item->is_active ? 'table-success' : '' }}">

                            <td>
                                {{ $index + 1 }}
                            </td>

                            <td>
                                {{ $item->tahun_ajaran }}/{{ $item->tahun_ajaran + 1 }}
                            </td>

                            <td>
                                {{ $item->semester_label }}
                            </td>

                            <td>
                                {{ \Carbon\Carbon::parse($item->start_date)->format('d M Y') }}
                            </td>

                            <td>
                                {{ $item->end_date
                                    ? \Carbon\Carbon::parse($item->end_date)->format('d M Y')
                                    : '-' }}
                            </td>

                            <td>

                                @if($item->is_active)

                                    <span class="badge bg-success">
                                        Aktif
                                    </span>

                                @else

                                    <span class="badge bg-secondary">
                                        Tidak Aktif
                                    </span>

                                @endif

                            </td>

                            <td>

                                @if(!$item->is_active)

                                    <form action="{{ route('gkm.data-master.periodeA.aktifkan', $item->id) }}"
                                          method="POST">

                                        @csrf

                                        <button class="btn btn-sm btn-warning"
                                                onclick="return confirm('Yakin ingin mengaktifkan periode ini?')">

                                            Jadikan Aktif
                                        </button>

                                    </form>

                                @else

                                    <span class="text-success fw-bold">
                                        Sedang Aktif
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="7">
                                Belum ada data periode
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>
    </div>

</div>

{{-- MODAL TAMBAH --}}
<div class="modal fade"
     id="modalTambahPeriode"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content border-0 shadow">

            <form action="{{ route('gkm.data-master.periodeA.store') }}"
                  method="POST">

                @csrf

                {{-- HEADER --}}
                <div class="modal-header text-white"
     style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">

                    <h5 class="modal-title text-white">
    Tambah Periode Akademik
</h5>

                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal">
                    </button>

                </div>

                {{-- BODY --}}
                <div class="modal-body">

                    <div class="row g-3">

                        {{-- Tahun Ajaran --}}
                        <div class="col-md-6">

                            <label class="form-label">
                                Tahun Ajaran
                            </label>

                            <input type="number"
                                   name="tahun_ajaran"
                                   value="{{ old('tahun_ajaran') }}"
                                   class="form-control"
                                   placeholder="Contoh: 2025"
                                   min="2000"
                                   max="2100"
                                   required>

                            <small class="text-muted">
                                Contoh: 2025 untuk tahun ajaran 2025/2026
                            </small>

                        </div>

                        {{-- Semester --}}
                        <div class="col-md-6">

                            <label class="form-label">
                                Semester
                            </label>

                            <select name="semester"
                                    class="form-select"
                                    required>

                                <option value=""
                                        disabled
                                        {{ old('semester') ? '' : 'selected' }}>

                                    -- Pilih Semester --

                                </option>

                                <option value="1"
                                    {{ old('semester') == '1' ? 'selected' : '' }}>

                                    Ganjil

                                </option>

                                <option value="2"
                                    {{ old('semester') == '2' ? 'selected' : '' }}>

                                    Genap

                                </option>

                            </select>

                        </div>

                        {{-- Tanggal Mulai --}}
                        <div class="col-md-6">

                            <label class="form-label">
                                Tanggal Mulai
                            </label>

                            <input type="date"
                                   name="start_date"
                                   value="{{ old('start_date') }}"
                                   class="form-control"
                                   required>

                        </div>

                        {{-- Tanggal Selesai --}}
                        <div class="col-md-6">

                            <label class="form-label">
                                Tanggal Selesai
                            </label>

                            <input type="date"
                                   name="end_date"
                                   value="{{ old('end_date') }}"
                                   class="form-control">

                        </div>

                    </div>

                </div>

                {{-- FOOTER --}}
                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-light border"
                            data-bs-dismiss="modal">

                        Batal

                    </button>

                    <button type="submit"
                            class="btn btn-primary">

                        Simpan Periode

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection