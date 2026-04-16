@extends('layouts.app')

@section('page-title', 'Master Periode Akademik')

@section('content')
<div style="padding: 1.5rem;">

    {{-- ALERT --}}
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- ================= FORM TAMBAH ================= --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            <strong>Tambah Periode Akademik</strong>
        </div>

        <div class="card-body">
            <form action="{{ route('gkm.periode.store') }}" method="POST">
                @csrf

                <div class="row g-3">

                    {{-- Tahun Ajaran --}}
                    <div class="col-md-3">
                        <label class="form-label">Tahun Ajaran</label>
                        <input type="text" name="tahun_ajaran" 
                               class="form-control"
                               placeholder="Contoh: 2025/2026" required>
                    </div>

                    {{-- Semester --}}
                    <div class="col-md-3">
                        <label class="form-label">Semester</label>
                        <select name="semester" class="form-control" required>
                            <option value="">-- Pilih --</option>
                            <option value="1">Ganjil</option>
                            <option value="2">Genap</option>
                        </select>
                    </div>

                    {{-- Start Date --}}
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Mulai Semester</label>
                        <input type="date" name="start_date" 
                               class="form-control" required>
                    </div>

                    {{-- Aktif --}}
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <div class="form-check mt-2">
                            <input type="checkbox" 
                                   name="is_active" 
                                   value="1" 
                                   class="form-check-input">
                            <label class="form-check-label">
                                Jadikan Periode Aktif
                            </label>
                        </div>
                    </div>

                </div>

                <button class="btn btn-success mt-3">
                    Simpan Periode
                </button>
            </form>
        </div>
    </div>

    {{-- ================= TABEL ================= --}}
    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white">
            <strong>Daftar Periode Akademik</strong>
        </div>

        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover text-center align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Tahun Ajaran</th>
                        <th>Semester</th>
                        <th>Tanggal Mulai</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($data as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>

                            <td>{{ $item->tahun_ajaran }}</td>

                            <td>
                                {{ $item->semester == 1 ? 'Ganjil' : 'Genap' }}
                            </td>

                            <td>
                                {{ \Carbon\Carbon::parse($item->start_date)->format('d M Y') }}
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                Belum ada data periode
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

</div>
@endsection