@extends('layouts.app')

@section('page-title', 'Detail Mata Kuliah')

@section('content')
    <div style="padding: 1.5rem;">

        <!-- Header -->
        <div class="filter-card mb-4">
            <h5 class="mb-0 font-semibold" style="text-transform: uppercase; letter-spacing: 0.5px;">
                Detail Mata Kuliah
            </h5>
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
                <i class="bi bi-info-circle"></i>
                <h6>Informasi Mata Kuliah</h6>
            </div>

            <div style="padding: 1.5rem;">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="filter-label">Kode Mata Kuliah</label>
                        <div class="mt-2">
                            <span class="code-mk" style="font-size: 1.1rem;">
                                {{ $matkul['kode_mk'] ?? '-' }}
                            </span>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <label class="filter-label">Nama Mata Kuliah</label>
                        <h6 class="mb-0 mt-2 fw-semibold" style="text-transform: uppercase;">
                            {{ $matkul['nama_matkul'] ?? '-' }}
                        </h6>
                    </div>

                    <div class="col-md-3">
                        <label class="filter-label">SKS</label>
                        <div class="mt-2">
                            <span class="badge-gkm success">
                                {{ $matkul['sks'] ?? '-' }} SKS
                            </span>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="filter-label">Semester</label>
                        <div class="mt-2">
                            @if($semester == 1)
                                <span class="badge-gkm primary">Ganjil</span>
                            @else
                                <span class="badge-gkm warning">Genap</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="filter-label">Tahun Ajaran</label>
                        <h6 class="mb-0 mt-2 fw-semibold">{{ $ta }}</h6>
                    </div>

                    <div class="col-md-3">
                        <label class="filter-label">Tingkat</label>
                        <div class="mt-2">
                            @php
                                $kodeMk = (string) ($matkul['kode_mk'] ?? '');
                                $tingkatMk = strlen($kodeMk) >= 5 ? substr($kodeMk, 3, 1) : '-';
                            @endphp
                            <span class="badge-gkm info">
                                <i class="bi bi-mortarboard"></i> Tingkat {{ $tingkatMk }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- DOSEN PENGAJAR --}}
        <div class="monitoring-card">
            <div class="monitoring-header">
                <i class="bi bi-people"></i>
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

        {{-- BUTTON BACK --}}
        <div class="mt-4">
            <a href="{{ route('gkm.data-master.matakuliah', [
                'ta' => $ta,
                'semester' => $semester,
                'tingkat' => request('tingkat'),
            ]) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

    </div>

    {{-- MODAL TAMBAH DOSEN --}}
    <div class="modal fade" id="modalTambahDosen" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="{{ route('gkm.data-master.matakuliah.dosen.store') }}" method="POST">
                    @csrf

                    <div class="modal-header" style="background: linear-gradient(135deg, #5B9BD5 0%, #4a8bc2 100%);">
                        <h5 class="modal-title text-white">
                            <i class="bi bi-person-plus"></i> Tambah Dosen Pengajar
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
                            <i class="bi bi-x-circle"></i> Batal
                        </button>
                        <button type="submit" class="btn-action-primary">
                            <i class="bi bi-save"></i> Simpan
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
