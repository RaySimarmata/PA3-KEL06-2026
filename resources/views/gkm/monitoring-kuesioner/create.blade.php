@extends('layouts.app')

@section('page-title', 'Upload Kuesioner')

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-cloud-upload"></i> Upload File Kuesioner
                        </h5>
                    </div>
                    <div class="card-body p-3">
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong><i class="bi bi-exclamation-triangle"></i> Terjadi Kesalahan:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form action="{{ route('gkm.monitoring-kuesioner.store') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf

                            <!-- Section 1: Informasi Kuesioner -->
                            <div class="border rounded p-3 mb-3 bg-light">
                                <h6 class="text-primary mb-3">
                                    <i class="bi bi-file-text me-1"></i>Informasi Kuesioner
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="nama_file" class="form-label">
                                            Nama File Kuesioner <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="nama_file" name="nama_file"
                                            value="{{ old('nama_file') }}"
                                            placeholder="Contoh: Kuesioner Kepuasan Mahasiswa Semester Ganjil" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="periode" class="form-label">
                                            Periode <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="periode" name="periode" required>
                                            <option value="">Pilih Periode</option>
                                            @foreach ($periodes as $periode)
                                                <option value="{{ $periode }}"
                                                    {{ old('periode') == $periode ? 'selected' : '' }}>
                                                    {{ $periode }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Pilih periode akademik untuk kuesioner ini</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 2: Informasi Matakuliah -->
                            <div class="border rounded p-3 mb-3 bg-light">
                                <h6 class="text-primary mb-3">
                                    <i class="bi bi-book me-1"></i>Informasi Matakuliah (Opsional)
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="nama_matakuliah" class="form-label">
                                            Nama Matakuliah
                                        </label>
                                        <input type="text" class="form-control" id="nama_matakuliah"
                                            name="nama_matakuliah" value="{{ old('nama_matakuliah') }}"
                                            placeholder="Contoh: Pemrograman Web">
                                    </div>

                                    <div class="col-md-4">
                                        <label for="kode_matakuliah" class="form-label">
                                            Kode Matakuliah
                                        </label>
                                        <input type="text" class="form-control" id="kode_matakuliah"
                                            name="kode_matakuliah" value="{{ old('kode_matakuliah') }}"
                                            placeholder="Contoh: TIF101">
                                    </div>

                                    <div class="col-md-4">
                                        <label for="tingkat" class="form-label">
                                            Tingkat
                                        </label>
                                        <select class="form-select" id="tingkat" name="tingkat">
                                            <option value="">Pilih Tingkat</option>
                                            <option value="1" {{ old('tingkat') == '1' ? 'selected' : '' }}>1</option>
                                            <option value="2" {{ old('tingkat') == '2' ? 'selected' : '' }}>2</option>
                                            <option value="3" {{ old('tingkat') == '3' ? 'selected' : '' }}>3</option>
                                            <option value="4" {{ old('tingkat') == '4' ? 'selected' : '' }}>4</option>
                                        </select>
                                    </div>

                                    <div class="col-md-12">
                                        <label for="dosen_pengampu" class="form-label">
                                            Dosen Pengampu
                                        </label>
                                        <input type="text" class="form-control" id="dosen_pengampu" name="dosen_pengampu"
                                            value="{{ old('dosen_pengampu') }}" placeholder="Contoh: Dr. John Doe, M.Kom">
                                    </div>
                                </div>
                            </div>

                            <!-- Section 3: Upload File -->
                            <div class="border rounded p-3 mb-3 bg-light">
                                <h6 class="text-primary mb-3">
                                    <i class="bi bi-paperclip me-1"></i>Upload File
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label for="file_excel" class="form-label">
                                            File Excel Kuesioner <span class="text-danger">*</span>
                                        </label>
                                        <input type="file" class="form-control" id="file_excel" name="file_excel"
                                            accept=".xlsx,.xls" required>
                                        <small class="text-muted">Format: .xlsx atau .xls, Maksimal 10MB</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Informasi AI Agent -->
                            <div class="alert alert-info d-flex align-items-start mb-3" role="alert">
                                <i class="bi bi-info-circle-fill fs-5 me-2 flex-shrink-0"></i>
                                <div>
                                    <h6 class="alert-heading mb-1">Informasi AI Agent</h6>
                                    <p class="mb-0 small">
                                        Setelah file diupload, sistem akan menggunakan <strong>AI Agent</strong> untuk
                                        menganalisis hasil kuesioner secara otomatis. AI akan memberikan insight tentang
                                        tingkat kepuasan mahasiswa, area yang perlu diperbaiki, dan rekomendasi tindakan.
                                    </p>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                <a href="{{ route('gkm.monitoring-kuesioner.index') }}" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-1"></i>Kembali
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload me-1"></i>Upload & Proses dengan AI
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
