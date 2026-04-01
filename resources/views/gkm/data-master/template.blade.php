@extends('layouts.app')

@section('page-title')
Template Laporan Materi {{ $user->prodi ? $user->prodi->kode_prodi : "" }}
@endsection

@section('content')
<div style="padding: 1.5rem;">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Template Laporan Materi</h5>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadTemplateModal">
                + Upload Template
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Template</th>
                            <th>Jenis File</th>
                            <th>Ukuran</th>
                            <th>Tanggal Upload</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templateList as $index => $template)
                        <tr>
                            <td>{{ $templateList->firstItem() + $index }}</td>
                            <td>
                                {{ $template->nama_template }}
                                @if($template->deskripsi)
                                <br><small class="text-muted">{{ $template->deskripsi }}</small>
                                @endif
                            </td>
                            <td><span class="badge bg-info">{{ $template->jenis_file }}</span></td>
                            <td>{{ number_format($template->ukuran_file / 1024, 2) }} KB</td>
                            <td>{{ \Carbon\Carbon::parse($template->created_at)->format('d-m-Y') }}</td>
                            <td>
                                <a href="{{ route('gkm.data-master.template.download', $template->id) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download"></i> Download
                                </a>
                                <form action="{{ route('gkm.data-master.template.destroy', $template->id) }}" 
                                      method="POST" 
                                      class="d-inline"
                                      onsubmit="return confirm('Yakin ingin menghapus template ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">Belum ada template yang diupload</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($templateList->hasPages())
            <div class="mt-3">
                {{ $templateList->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Upload Template -->
<div class="modal fade" id="uploadTemplateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('gkm.data-master.template.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Upload Template Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Template <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('nama_template') is-invalid @enderror" 
                               name="nama_template" value="{{ old('nama_template') }}" 
                               placeholder="Contoh: Template Laporan Materi 2026" required>
                        @error('nama_template')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">File Template <span class="text-danger">*</span></label>
                        <input type="file" class="form-control @error('file') is-invalid @enderror" 
                               name="file" 
                               accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx" 
                               required>
                        <small class="text-muted">
                            Format yang didukung: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX (Max: 10MB)
                        </small>
                        @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control @error('deskripsi') is-invalid @enderror" 
                                  name="deskripsi" rows="3" 
                                  placeholder="Deskripsi singkat tentang template ini">{{ old('deskripsi') }}</textarea>
                        @error('deskripsi')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert alert-info mb-0">
                        <small>
                            <i class="bi bi-info-circle"></i> 
                            Template yang diupload akan tersedia untuk didownload oleh dosen
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
