@extends('layouts.app')

@section('page-title', 'Upload Kuesioner')

@section('content')
    <div style="padding: 1.5rem;">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Header Card -->
                <div class="filter-card mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 d-flex align-items-center gap-2" style="font-weight: 600; color: #333;">
                                <i class="bi bi-cloud-upload" style="color: #5B9BD5;"></i>
                                Upload File Kuesioner
                            </h5>
                            <p class="text-muted mb-0" style="font-size: 0.875rem;">Upload dan analisis kuesioner mahasiswa
                                dengan AI Agent</p>
                        </div>
                        <a href="{{ route('gkm.monitoring-kuesioner.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="alert-gkm danger mb-4">
                        <h6 style="font-weight: 600; margin-bottom: 0.5rem;">
                            <i class="bi bi-exclamation-triangle"></i> Terjadi Kesalahan
                        </h6>
                        <ul class="mb-0" style="font-size: 0.875rem;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('gkm.monitoring-kuesioner.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- Section 1: Informasi Kuesioner -->
                    <div class="monitoring-card mb-4">
                        <div class="monitoring-header">
                            <i class="bi bi-file-text" style="color: #5B9BD5;"></i>
                            <h6>Informasi Kuesioner</h6>
                        </div>
                        <div style="padding: 1.5rem;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="nama_file" class="filter-label">
                                        Nama File Kuesioner <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="nama_file" name="nama_file"
                                        value="{{ old('nama_file') }}"
                                        placeholder="Contoh: Kuesioner Kepuasan Mahasiswa Semester Ganjil" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="periode" class="filter-label">
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
                                    <small class="text-muted" style="font-size: 0.8rem;">
                                        <i class="bi bi-info-circle"></i> Pilih periode akademik untuk kuesioner ini
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Informasi Matakuliah -->
                    <div class="monitoring-card mb-4">
                        <div class="monitoring-header">
                            <i class="bi bi-book" style="color: #5B9BD5;"></i>
                            <h6>Informasi Matakuliah <span
                                    style="font-weight: 400; font-size: 0.85rem; color: #6c757d;"></span></h6>
                        </div>
                        <div style="padding: 1.5rem;">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="nama_matakuliah" class="filter-label">
                                        Nama Matakuliah
                                    </label>
                                    <input type="text" class="form-control" id="nama_matakuliah" name="nama_matakuliah"
                                        value="{{ old('nama_matakuliah') }}" placeholder="Contoh: Pemrograman Web">
                                </div>

                                <div class="col-md-4">
                                    <label for="kode_matakuliah" class="filter-label">
                                        Kode Matakuliah
                                    </label>
                                    <input type="text" class="form-control" id="kode_matakuliah" name="kode_matakuliah"
                                        value="{{ old('kode_matakuliah') }}" placeholder="Contoh: TIF101">
                                </div>

                                <div class="col-md-4">
                                    <label for="tingkat" class="filter-label">
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
                                    <label for="dosen_pengampu" class="filter-label">
                                        Dosen Pengampu
                                    </label>
                                    <input type="text" class="form-control" id="dosen_pengampu" name="dosen_pengampu"
                                        value="{{ old('dosen_pengampu') }}" placeholder="Contoh: Dr. John Doe, M.Kom">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Upload File -->
                    <div class="monitoring-card mb-4">
                        <div class="monitoring-header">
                            <i class="bi bi-paperclip" style="color: #5B9BD5;"></i>
                            <h6>Upload File</h6>
                        </div>
                        <div style="padding: 1.5rem;">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="file_excel" class="filter-label">
                                        File Excel Kuesioner <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control" id="file_excel" name="file_excel"
                                        accept=".xlsx,.xls" required>
                                    <small class="text-muted" style="font-size: 0.8rem;">
                                        <i class="bi bi-info-circle"></i> Format: .xlsx atau .xls, Maksimal 10MB
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi AI Agent -->
                    <div class="monitoring-card mb-4" style="border-left: 4px solid #5B9BD5;">
                        <div style="padding: 1.5rem;">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-info-circle"
                                    style="color: #5B9BD5; font-size: 2rem; margin-right: 1rem;"></i>
                                <div>
                                    <h6 class="mb-2" style="font-weight: 600; color: #333;">Informasi AI Agent</h6>
                                    <p class="mb-0" style="font-size: 0.875rem; color: #495057; line-height: 1.6;">
                                        Setelah file diupload, sistem akan menggunakan <strong>AI Agent</strong> untuk
                                        menganalisis hasil kuesioner secara otomatis. AI akan memberikan insight tentang
                                        tingkat kepuasan mahasiswa, area yang perlu diperbaiki, dan rekomendasi tindakan.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between gap-2">
                        <a href="{{ route('gkm.monitoring-kuesioner.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                        <button type="submit" class="btn-reminder">
                            <i class="bi bi-upload"></i>
                            <span>Upload & Proses dengan AI</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
