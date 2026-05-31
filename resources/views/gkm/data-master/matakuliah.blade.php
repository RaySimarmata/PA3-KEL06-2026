@extends('layouts.app')

@section('page-title')
Master Mata Kuliah
@endsection

@section('content')

<div style="padding: 1.5rem;">

    {{-- ALERT --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h4 class="mb-1 fw-bold">
                Data Mata Kuliah
            </h4>

            <small class="text-muted">
                Daftar mata kuliah berdasarkan tahun ajaran & semester
            </small>
        </div>

        <div>
            <span class="badge bg-primary fs-6">
                Total:
                {{ count($matakuliahList) }}
            </span>
        </div>

    </div>

    {{-- FILTER --}}
    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <form method="GET">

                <div class="row g-3">

                    {{-- TA --}}
                    <div class="col-md-2">

                        <label class="form-label fw-semibold">
                            Tahun Ajaran
                        </label>

                        <input type="number"
                               name="ta"
                               class="form-control"
                               value="{{ $ta }}">

                    </div>

                    {{-- SEMESTER --}}
                    <div class="col-md-2">

                        <label class="form-label fw-semibold">
                            Semester
                        </label>

                        <select name="semester"
                                class="form-select">

                            <option value="1"
                                {{ $semester == 1 ? 'selected' : '' }}>

                                Ganjil

                            </option>

                            <option value="2"
                                {{ $semester == 2 ? 'selected' : '' }}>

                                Genap

                            </option>

                        </select>

                    </div>

                    {{-- TINGKAT --}}
                    <div class="col-md-2">
    <label class="form-label">Tingkat</label>

    <select name="tingkat" class="form-select">

        <option value="">
            Semua Tingkat
        </option>

        <option value="1"
            {{ $selectedTingkat == '1' ? 'selected' : '' }}>
            Tingkat 1
        </option>

        <option value="2"
            {{ $selectedTingkat == '2' ? 'selected' : '' }}>
            Tingkat 2
        </option>

        <option value="3"
            {{ $selectedTingkat == '3' ? 'selected' : '' }}>
            Tingkat 3
        </option>

        <option value="4"
            {{ $selectedTingkat == '4' ? 'selected' : '' }}>
            Tingkat 4
        </option>

    </select>
</div>

                    {{-- SEARCH --}}
                    <div class="col-md-4">

                        <label class="form-label fw-semibold">
                            Cari Mata Kuliah
                        </label>

                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Cari nama / kode mata kuliah"
                               value="{{ request('search') }}">

                    </div>

                    {{-- BUTTON --}}
                    <div class="col-md-2 d-flex align-items-end">

                        <button class="btn btn-primary w-100">

                            <i class="bi bi-search"></i>
                            Filter

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

    {{-- TABLE --}}
    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light">

                        <tr>

                            <th width="5%">
                                No
                            </th>

                            <th width="15%">
                                Kode MK
                            </th>

                            <th>
                                Nama Mata Kuliah
                            </th>

                            <th width="10%">
                                SKS
                            </th>

                            <th width="10%">
                                Semester
                            </th>

                            <th width="15%">
                                Aksi
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($matakuliahList as $index => $mk)

                            <tr>

                                {{-- NO --}}
                                <td>
                                    {{ $index + 1 }}
                                </td>

                                {{-- KODE MK --}}
                                <td>

                                    <span class="badge bg-dark px-3 py-2">

                                        {{ $mk['kode_mk'] ?? '-' }}

                                    </span>

                                </td>

                                {{-- NAMA MK --}}
                                <td>

                                    <div class="fw-semibold">

                                        {{ $mk['nama_matkul'] ?? '-' }}

                                    </div>

                                </td>


                                {{-- SKS --}}
                                <td>

                                    <span class="badge bg-success">

                                        {{ $mk['sks'] ?? '-' }} SKS

                                    </span>

                                </td>

                                {{-- SEMESTER --}}
                                <td>

                                    @if($semester == 1)

                                        <span class="badge bg-primary">
                                            Ganjil
                                        </span>

                                    @else

                                        <span class="badge bg-warning text-dark">
                                            Genap
                                        </span>

                                    @endif

                                </td>

                                {{-- AKSI --}}
                                <td>

                                   <a href="{{ route('gkm.data-master.matakuliah.detail', [
    'kodeMk' => $mk['kode_mk'],
    'ta' => $ta,
    'semester' => $semester
]) }}"
class="btn btn-sm btn-primary">
    Detail
</a>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="7"
                                    class="text-center text-muted py-5">

                                    <div class="mb-2">

                                        <i class="bi bi-database-x fs-1"></i>

                                    </div>

                                    Tidak ada data mata kuliah

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

@endsection