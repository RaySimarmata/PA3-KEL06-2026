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
                            <h5 class="mb-1 font-semibold" style="text-transform: uppercase; letter-spacing: 0.5px; color: #333;">
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

                <form action="{{ route('gkm.monitoring-kuesioner.store') }}" method="POST" enctype="multipart/form-data"
                    id="uploadForm">
                    @csrf

                    {{-- Hidden fields --}}
                    {{-- No longer needed with direct search --}}
                    {{-- <input type="hidden" name="tingkat" id="tingkat_hidden" value=""> --}}

                    <!-- Section 1: Informasi Kuesioner (PERTAMA) -->
                    <div class="monitoring-card mb-4">
                        <div class="monitoring-header">
                            <h6 style="text-transform: uppercase; letter-spacing: 0.5px;">Informasi Kuesioner</h6>
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

                    <!-- Section 2: Cari Matakuliah (KEDUA) -->
                    <div class="monitoring-card mb-4">
                        <div class="monitoring-header">
                            <h6 style="text-transform: uppercase; letter-spacing: 0.5px;">Cari Matakuliah</h6>
                        </div>
                        <div style="padding: 1.5rem;">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="filter-label">Cari Matakuliah <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white">
                                                <i class="bi bi-search"></i>
                                            </span>
                                            <input type="text" class="form-control" id="search_matkul"
                                                name="search_matkul" placeholder="Cari Kode MK, Nama, atau Dosen..."
                                                autocomplete="off">
                                        </div>

                                        <!-- Loading indicator -->
                                        <div id="search_loading"
                                            style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); display: none;">
                                            <div class="spinner-border spinner-border-sm" role="status"
                                                style="color: #5B9BD5;">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>

                                        <!-- Search results dropdown -->
                                        <div id="search_results" class="search-results-dropdown"
                                            style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #e0e0e0; max-height: 400px; overflow-y: auto; display: none; z-index: 1000; border-radius: 0.375rem; margin-top: 2px;">
                                        </div>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.8rem;">
                                        <i class="bi bi-info-circle"></i> Mulai ketik untuk mencari matakuliah
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Matakuliah Terpilih (KETIGA) -->
                    <div class="monitoring-card mb-4" id="selected_matkul_card" style="display: none;">
                        <div class="monitoring-header">
                            <h6 style="text-transform: uppercase; letter-spacing: 0.5px;">Matakuliah Terpilih</h6>
                        </div>
                        <div style="padding: 1.5rem;">
                            <div class="table-responsive">
                                <table class="table table-sm" style="margin-bottom: 0;">
                                    <tbody id="selected_matkul_display">
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" id="btn_ubah_matkul" class="btn btn-sm btn-outline-secondary mt-3">
                                <i class="bi bi-pencil"></i> Ubah Pilihan
                            </button>
                        </div>
                    </div>

                    <input type="hidden" name="selected_matkul" id="selected_matkul" value="">

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <!-- Section 4: Upload File -->
                    <div class="monitoring-card mb-4" id="upload_file_card" style="display: none;">
                        <div class="monitoring-header">
                            <h6 style="text-transform: uppercase; letter-spacing: 0.5px;">Upload File</h6>
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
                    <div class="monitoring-card mb-4" id="ai_info_card"
                        style="display: none; border-left: 4px solid #5B9BD5;">
                        <div style="padding: 1.5rem;">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-info-circle"
                                    style="color: #5B9BD5; font-size: 2rem; margin-right: 1rem;"></i>
                                <div>
                                    <h6 class="mb-2" style="font-weight: 600; color: #333;">Informasi AI Agent
                                    </h6>
                                    <p class="mb-0" style="font-size: 0.875rem; color: #495057; line-height: 1.6;">
                                        Setelah file diupload, sistem akan menggunakan <strong>AI Agent</strong>
                                        untuk
                                        menganalisis hasil kuesioner secara otomatis. AI akan memberikan insight
                                        tentang
                                        tingkat kepuasan mahasiswa, area yang perlu diperbaiki, dan rekomendasi
                                        tindakan.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between gap-2" id="action_buttons" style="display: none;">
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
