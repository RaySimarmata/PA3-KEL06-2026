@extends('layouts.app')

@section('page-title')
    Detail Mata Kuliah
@endsection

@section('content')
    <div style="padding: 1.5rem;">

        {{-- ALERT --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- DETAIL MATKUL --}}
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white">
                <h5 class="mb-0">
                    Informasi Mata Kuliah
                </h5>
            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label class="text-muted small">
                            Kode Mata Kuliah
                        </label>

                        <h6 class="mb-0">
                            <span class="badge bg-dark">
                                {{ $matkul['kode_mk'] ?? '-' }}
                            </span>
                        </h6>
                    </div>

                    <div class="col-md-8 mb-3">
                        <label class="text-muted small">
                            Nama Mata Kuliah
                        </label>

                        <h6 class="mb-0">
                            {{ $matkul['nama_matkul'] ?? '-' }}
                        </h6>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="text-muted small">
                            SKS
                        </label>

                        <h6 class="mb-0">
                            {{ $matkul['sks'] ?? '-' }}
                        </h6>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="text-muted small">
                            Semester
                        </label>

                        <h6 class="mb-0">
                            {{ $semester == 1 ? 'Ganjil' : 'Genap' }}
                        </h6>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="text-muted small">
                            Tahun Ajaran
                        </label>

                        <h6 class="mb-0">
                            {{ $ta }}
                        </h6>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="text-muted small">
                            Tingkat
                        </label>

                        <h6 class="mb-0">

                            @php
                                $kodeMk = (string) ($matkul['kode_mk'] ?? '');
                                $tingkatMk = strlen($kodeMk) >= 5 ? substr($kodeMk, 3, 1) : '-';
                            @endphp

                            Tingkat {{ $tingkatMk }}

                        </h6>
                    </div>

                </div>

            </div>

        </div>

        {{-- DOSEN PENGAJAR --}}
        <div class="card border-0 shadow-sm">

            <div class="card-header bg-white d-flex justify-content-between align-items-center">

                <div>
                    <h5 class="mb-0">
                        Dosen Pengajar
                    </h5>

                    <small class="text-muted">
                        Daftar dosen pengampu mata kuliah
                    </small>
                </div>

                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahDosen">

                    <i class="bi bi-plus-circle"></i>
                    Tambah Dosen

                </button>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead class="table-light">

                            <tr>
                                <th width="5%">No</th>
                                <th>Nama Dosen</th>
                                <th>Pegawai ID</th>
                                <th width="12%">Aksi</th>
                            </tr>

                        </thead>

                        <tbody>

                            @forelse($dosenPengajar as $index => $dosen)
                                <tr>

                                    <td>
                                        {{ $index + 1 }}
                                    </td>

                                    <td>
                                        <strong>
                                            {{ $dosen->nama ?? '-' }}
                                        </strong>
                                    </td>

                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ $dosen->pegawai_id ?? '-' }}
                                        </span>
                                    </td>

                                    <td>

                                        <form action="{{ route('gkm.data-master.matakuliah.dosen.delete') }}"
                                            method="POST" onsubmit="return confirm('Yakin ingin menghapus dosen ini?')">

                                            @csrf
                                            @method('DELETE')

                                            <input type="hidden" name="pegawai_id" value="{{ $dosen->pegawai_id }}">
                                            <input type="hidden" name="kode_mk" value="{{ $matkul['kode_mk'] }}">

                                            <button class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i>
                                                Hapus
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="4" class="text-center text-muted py-4">

                                        Belum ada dosen pengajar

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

        {{-- BUTTON BACK --}}
        <div class="mt-4">

            <a href="{{ route('gkm.data-master.matakuliah', [
                'ta' => $ta,
                'semester' => $semester,
                'tingkat' => request('tingkat'),
            ]) }}"
                class="btn btn-secondary">

                <i class="bi bi-arrow-left"></i>
                Kembali

            </a>

        </div>

    </div>

    {{-- MODAL TAMBAH DOSEN --}}
    <div class="modal fade" id="modalTambahDosen" tabindex="-1">

        <div class="modal-dialog modal-lg">

            <div class="modal-content">

                <form action="{{ route('gkm.data-master.matakuliah.dosen.store') }}" method="POST">

                    @csrf

                    <div class="modal-header">

                        <h5 class="modal-title">
                            Tambah Dosen Pengajar
                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal">
                        </button>

                    </div>

                    <div class="modal-body">

                        <input type="hidden" name="kode_mk" value="{{ $matkul['kode_mk'] }}">

                        {{-- PILIH DOSEN --}}
                        <div class="mb-3">

                            <label class="form-label">
                                Pilih Dosen
                            </label>

                            <select class="form-select" id="selectDosen" name="select_dosen" required>

                                <option value="">
                                    -- Ketik / Pilih Dosen --
                                </option>

                                @foreach ($dosenList as $dosen)
                                    <option value="{{ $dosen['pegawai_id'] }}" data-nama="{{ $dosen['nama'] }}">

                                        {{ $dosen['nama'] }} ({{ $dosen['pegawai_id'] }})
                                    </option>
                                @endforeach

                            </select>

                        </div>

                        {{-- PEGAWAI ID --}}
                        <div class="mb-3">

                            <label class="form-label">
                                Pegawai ID
                            </label>

                            <input type="text" name="pegawai_id" id="pegawaiId" class="form-control" readonly required>

                        </div>

                        {{-- NAMA DOSEN --}}
                        <div class="mb-3">

                            <label class="form-label">
                                Nama Dosen
                            </label>

                            <input type="text" name="nama_dosen" id="namaDosen" class="form-control" readonly required>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">

                            Batal

                        </button>

                        <button class="btn btn-primary">

                            Simpan

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    {{-- SCRIPT --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {

            $('#selectDosen').select2({
                dropdownParent: $('#modalTambahDosen'),
                placeholder: "Ketik nama dosen...",
                width: '100%'
            });

            $('#selectDosen').on('change', function() {

                const selected = this.options[this.selectedIndex];

                $('#pegawaiId').val(selected.value || '');
                $('#namaDosen').val($(selected).data('nama') || '');

            });

        });
    </script>
@endsection
