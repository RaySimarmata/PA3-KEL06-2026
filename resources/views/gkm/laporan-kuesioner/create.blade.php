@extends('layouts.app')

@section('page-title', 'Generate Laporan Baru')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4>Generate Laporan Bulanan Baru</h4>
                    <p class="text-muted">AI Agent akan menganalisis semua kuesioner dalam periode yang dipilih</p>
                </div>
                <a href="{{ route('gkm.laporan-kuesioner.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Info Card -->
            <div class="card mb-4 border-info">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-info-circle text-info fs-3 me-3"></i>
                        <div>
                            <h6 class="mb-2">Cara Kerja AI Agent</h6>
                            <ol class="mb-0 small">
                                <li>Sistem mengumpulkan semua kuesioner yang sudah completed dalam periode yang dipilih</li>
                                <li>AI Agent menganalisis data menggunakan teknologi RAG (Retrieval-Augmented Generation)</li>
                                <li>Jika template tersedia, AI akan mempelajari format dan struktur template</li>
                                <li>AI menghasilkan laporan komprehensif dalam format Word (.docx)</li>
                                <li>Proses memakan waktu 1-3 menit tergantung jumlah data</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-plus"></i> Form Generate Laporan</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('gkm.laporan-kuesioner.store') }}" method="POST">
                        @csrf

                        <!-- Periode -->
                        <div class="mb-4">
                            <label for="periode" class="form-label">
                                Periode <span class="text-danger">*</span>
                            </label>
                            <select name="periode" id="periode" class="form-select @error('periode') is-invalid @enderror" required>
                                <option value="">-- Pilih Periode --</option>
                                @foreach($periodes as $p)
                                    <option value="{{ $p['value'] }}" {{ old('periode') == $p['value'] ? 'selected' : '' }}>
                                        {{ $p['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('periode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Pilih bulan dan tahun untuk laporan yang akan digenerate
                            </small>
                        </div>

                        <!-- Template -->
                        <div class="mb-4">
                            <label for="template_id" class="form-label">
                                Template Laporan <span class="text-muted">(Opsional)</span>
                            </label>
                            <select name="template_id" id="template_id" class="form-select @error('template_id') is-invalid @enderror">
                                <option value="">-- Gunakan Format Default --</option>
                                @foreach($templates as $t)
                                    <option value="{{ $t->id }}" {{ old('template_id', $template?->id) == $t->id ? 'selected' : '' }}>
                                        {{ $t->nama_template }}
                                        @if($t->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('template_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Jika dipilih, AI akan mempelajari format dari template ini. 
                                <a href="{{ route('gkm.laporan-kuesioner.template.index') }}">Kelola template</a>
                            </small>
                        </div>

                        <!-- Preview Info -->
                        @if($template)
                        <div class="alert alert-info">
                            <strong>Template Aktif:</strong> {{ $template->nama_template }}<br>
                            <small>{{ $template->deskripsi }}</small>
                        </div>
                        @endif

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('gkm.laporan-kuesioner.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-robot"></i> Generate dengan AI Agent
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
