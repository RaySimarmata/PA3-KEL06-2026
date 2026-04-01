@extends('layouts.app')

@section('page-title', 'Kelola Template Laporan')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4>Kelola Template Laporan</h4>
                    <p class="text-muted">Upload dan kelola template Word untuk AI Agent</p>
                </div>
                <div>
                    <a href="{{ route('gkm.laporan-kuesioner.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                    <a href="{{ route('gkm.laporan-kuesioner.template.upload') }}" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Upload Template Baru
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-info-circle text-info fs-3 me-3"></i>
                        <div>
                            <h6 class="mb-2">Tentang Template</h6>
                            <p class="mb-2">
                                Template adalah contoh laporan Word yang akan dipelajari oleh AI Agent. 
                                AI akan menganalisis struktur, format, dan gaya penulisan dari template untuk 
                                menghasilkan laporan yang konsisten.
                            </p>
                            <p class="mb-0 small text-muted">
                                <strong>Tips:</strong> Upload template dengan struktur yang jelas, gunakan heading, 
                                bullet points, dan format yang konsisten. AI akan meniru format ini dalam laporan yang dihasilkan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nama Template</th>
                                    <th>Deskripsi</th>
                                    <th>File</th>
                                    <th>Status</th>
                                    <th>Diupload</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($templates as $template)
                                <tr>
                                    <td>
                                        <strong>{{ $template->nama_template }}</strong>
                                        @if($template->is_active)
                                            <span class="badge bg-success ms-2">Active</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ Str::limit($template->deskripsi, 50) }}</small>
                                    </td>
                                    <td>
                                        <i class="bi bi-file-earmark-word text-primary"></i>
                                        {{ $template->nama_file }}
                                        <br>
                                        <small class="text-muted">{{ number_format($template->ukuran_file / 1024, 2) }} KB</small>
                                    </td>
                                    <td>
                                        @if($template->is_active)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary">Tidak Aktif</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $template->created_at->format('d/m/Y') }}
                                        <br>
                                        <small class="text-muted">oleh {{ $template->uploader->name ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('gkm.laporan-kuesioner.template.download', $template->id) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Download Template"
                                               data-bs-toggle="tooltip">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            
                                            <form action="{{ route('gkm.laporan-kuesioner.template.reindex', $template->id) }}" 
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-info"
                                                        title="Reindex ke Vector DB">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                            </form>
                                            
                                            <form action="{{ route('gkm.laporan-kuesioner.template.toggle', $template->id) }}" 
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-{{ $template->is_active ? 'warning' : 'success' }}"
                                                        title="{{ $template->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                    <i class="bi bi-{{ $template->is_active ? 'toggle-on' : 'toggle-off' }}"></i>
                                                </button>
                                            </form>
                                            
                                            <form action="{{ route('gkm.laporan-kuesioner.template.destroy', $template->id) }}" 
                                                  method="POST" class="d-inline"
                                                  onsubmit="return confirm('Yakin ingin menghapus template ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                        @if($template->is_indexed)
                                            <div class="mt-1">
                                                <small class="text-success">
                                                    <i class="bi bi-check-circle"></i> Indexed ({{ $template->total_chunks }} chunks)
                                                </small>
                                            </div>
                                        @else
                                            <div class="mt-1">
                                                <small class="text-warning">
                                                    <i class="bi bi-exclamation-circle"></i> Not indexed
                                                </small>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Belum ada template. <a href="{{ route('gkm.laporan-kuesioner.template.upload') }}">Upload template baru</a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $templates->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endpush
