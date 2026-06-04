@extends('layouts.app')

@section('page-title', 'Detail Mata Kuliah')

@section('content')
    <div style="padding: 1.5rem;">

        <!-- Header -->
    <div class="filter-card mb-4 d-flex justify-content-between align-items-center">

        <h5 class="mb-0 font-semibold" style="text-transform: uppercase; letter-spacing: 0.5px;">
            DETAIL MATA KULIAH
        </h5>

        {{-- BUTTON BACK --}}
        <a href="{{ route('gkm.data-master.matakuliah', [
            'ta' => $ta,
            'semester' => $semester,
            'tingkat' => request('tingkat'),
        ]) }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>

    </div>


        {{-- ALERT --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- DETAIL MATKUL --}}
<div class="monitoring-card mb-4">
    <div class="monitoring-header">
        <h6 class="mb-0">Informasi Mata Kuliah</h6>
    </div>
    <div style="padding: 1.5rem;">
        <!-- Baris 1: Kode MK + Nama MK -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <label class="filter-label text-muted small mb-1">Kode Mata Kuliah</label>
                <div>
                    <span class="code-mk" style="font-size: 1.2rem; font-weight: 600;">
                        {{ $matkul['kode_mk'] ?? '-' }}
                    </span>
                </div>
            </div>
            <div class="col-md-9">
                <label class="filter-label text-muted small mb-1">Nama Mata Kuliah</label>
                <h5 class="mb-0 fw-semibold" style="text-transform: uppercase; color: black;">
                    {{ $matkul['nama_matkul'] ?? '-' }}
                </h5>
            </div>
        </div>

        <!-- Baris 2: SKS, Semester, Tahun Ajaran, Tingkat -->
        <div class="row g-4">
            <div class="col-md-3">
                <label class="filter-label text-muted small mb-1">SKS</label>
                <div>
                    <span class="badge-gkm success fs-6">
                        {{ $matkul['sks'] ?? '-' }} SKS
                    </span>
                </div>
            </div>
            <div class="col-md-3">
                <label class="filter-label text-muted small mb-1">Semester</label>
                <div>
                    @if($semester == 1)
                        <span class="badge-gkm primary fs-6">Ganjil</span>
                    @else
                        <span class="badge-gkm warning fs-6">Genap</span>
                    @endif
                </div>
            </div>
            <div class="col-md-3">
                <label class="filter-label text-muted small mb-1">Tahun Ajaran</label>
                <div>
                    <span class="fw-semibold fs-6">{{ $ta }}</span>
                </div>
            </div>
            <div class="col-md-3">
                <label class="filter-label text-muted small mb-1">Tingkat</label>
                <div>
                    @php
                        $kodeMk = (string) ($matkul['kode_mk'] ?? '');
                        $tingkatMk = strlen($kodeMk) >= 5 ? substr($kodeMk, 3, 1) : '-';
                    @endphp
                    <span class="badge-gkm info fs-6">
                        <i class="bi bi-mortarboard me-1"></i> Tingkat {{ $tingkatMk }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

        {{-- DOSEN PENGAJAR --}}
        <div class="monitoring-card">
            <div class="monitoring-header">
                {{-- <i class="bi bi-people"></i> --}}
                <h6>Dosen Pengajar</h6>
                <div style="margin-left: auto;">
                    <button class="btn-action-primary" data-bs-toggle="modal" data-bs-target="#modalTambahDosen">
                        <i class="bi bi-plus-circle"></i> Tambah Dosen
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-monitoring">
                    <thead>
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 50%;">Nama Dosen</th>
                            <th style="width: 25%;">Pegawai ID</th>
                            <th style="width: 20%;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dosenPengajar as $index => $dosen)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="fw-semibold">{{ $dosen->nama ?? '-' }}</td>
                                <td>
                                    <span class="badge-gkm secondary">
                                        {{ $dosen->pegawai_id ?? '-' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <form action="{{ route('gkm.data-master.matakuliah.dosen.delete') }}"
                                        method="POST" onsubmit="return confirm('Yakin ingin menghapus dosen ini?')" 
                                        style="display: inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="pegawai_id" value="{{ $dosen->pegawai_id }}">
                                        <input type="hidden" name="kode_mk" value="{{ $matkul['kode_mk'] }}">
                                        <button class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <p class="mt-2 mb-0">Belum ada dosen pengajar</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- BUTTON BACK
        <div class="mt-4">
            <a href="{{ route('gkm.data-master.matakuliah', [
                'ta' => $ta,
                'semester' => $semester,
                'tingkat' => request('tingkat'),
            ]) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

    </div> --}}

    {{-- MODAL TAMBAH DOSEN --}}
    <div class="modal fade" id="modalTambahDosen" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="{{ route('gkm.data-master.matakuliah.dosen.store') }}" method="POST">
                    @csrf

                    <div class="modal-header" style="background: linear-gradient(135deg, #5B9BD5 0%, #4a8bc2 100%);">
                        <h5 class="modal-title text-white">
                            {{-- <i class="bi bi-person-plus"></i> Tambah Dosen Pengajar --}}
                            Tambah Dosen Pengajar
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body" style="padding: 1.5rem;">
                        <input type="hidden" name="kode_mk" value="{{ $matkul['kode_mk'] }}">

                        {{-- PILIH DOSEN --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pilih Dosen</label>
                            <select class="form-select" id="selectDosen" name="select_dosen" required>
                                <option value="">-- Ketik / Pilih Dosen --</option>
                                @foreach ($dosenList as $dosen)
                                    <option value="{{ $dosen['pegawai_id'] }}" data-nama="{{ $dosen['nama'] }}">
                                        {{ $dosen['nama'] }} ({{ $dosen['pegawai_id'] }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> Ketik untuk mencari dosen
                            </small>
                        </div>

                        {{-- PEGAWAI ID --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pegawai ID</label>
                            <input type="text" name="pegawai_id" id="pegawaiId" class="form-control" readonly required>
                        </div>

                        {{-- NAMA DOSEN --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Dosen</label>
                            <input type="text" name="nama_dosen" id="namaDosen" class="form-control" readonly required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                            {{-- <i class="bi bi-x-circle"></i> Batal --}}
                            Batal
                        </button>
                        <button type="submit" class="btn-action-primary">
                            {{-- <i class="bi bi-save"></i> Simpan --}}
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
