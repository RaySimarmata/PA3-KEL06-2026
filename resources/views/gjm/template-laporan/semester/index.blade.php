@extends('layouts.app')

@section('page-title', 'Kelola Template Laporan Semester')

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Alert Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> 
                <strong>Perhatian!</strong> {{ session('warning')['message'] }}
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-danger" 
                            onclick="forceDeleteTemplate({{ session('warning')['template_id'] }}, '{{ session('warning')['type'] }}')">
                        <i class="bi bi-trash"></i> Hapus Paksa ({{ session('warning')['usage_count'] }} laporan akan kehilangan referensi template)
                    </button>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Header Card -->
        <div class="filter-card mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 d-flex align-items-center gap-2" style="font-weight: 600; color: #333;">
                        <i class="bi bi-file-earmark-word" style="color: #5B9BD5;"></i>
                        Kelola Template Laporan Semester
                    </h5>
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">Upload dan kelola template Word untuk Laporan Semester</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('gjm.buat-laporan.semester') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                    <a href="{{ route('gjm.template-laporan.semester.upload') }}" class="btn-reminder">
                        <i class="bi bi-upload"></i>
                        <span>Upload Template Baru</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Info Card -->
        <div class="monitoring-card mb-4" style="border-left: 4px solid #5B9BD5;">
            <div style="padding: 1.5rem;">
                <div class="d-flex align-items-start">
                    <i class="bi bi-info-circle" style="color: #5B9BD5; font-size: 2rem; margin-right: 1rem;"></i>
                    <div>
                        <h6 class="mb-2" style="font-weight: 600; color: #333;">Tentang Template</h6>
                        <p class="mb-2" style="font-size: 0.875rem; color: #495057; line-height: 1.6;">
                            Template adalah contoh laporan Word yang akan dipelajari oleh AI Agent. 
                            AI akan menganalisis struktur, format, dan gaya penulisan dari template untuk 
                            menghasilkan laporan yang konsisten.
                        </p>
                        <p class="mb-0" style="font-size: 0.8rem; color: #6c757d;">
                            <strong>Tips:</strong> Upload template dengan struktur yang jelas, gunakan heading, 
                            bullet points, dan format yang konsisten. AI akan meniru format ini dalam laporan yang dihasilkan.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Template List -->
        <div class="monitoring-card">
            <div class="monitoring-header">
                <i class="bi bi-list-ul" style="color: #5B9BD5;"></i>
                <h6>Daftar Template</h6>
            </div>

            <div class="table-responsive">
                <table class="table table-monitoring">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Nama Template</th>
                            <th style="width: 20%;">File</th>
                            <th style="width: 12%;" class="text-center">Status</th>
                            <th style="width: 15%;">Diupload</th>
                            <th style="width: 28%;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="code-mk">{{ $template->nama_template }}</span>
                                        @if($template->is_active)
                                            <span class="badge-gkm success" style="color: #28a745;">Active</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <i class="bi bi-file-earmark-word" style="color: #5B9BD5;"></i>
                                        <span class="text-secondary" style="font-size: 0.85rem;">{{ $template->nama_file }}</span>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.75rem;">
                                        {{ number_format($template->ukuran_file / 1024, 2) }} KB
                                    </small>
                                </td>
                                <td class="text-center">
                                    @if($template->is_active)
                                        <span class="badge-gkm success" style="color: #28a745;">Aktif</span>
                                    @else
                                        <span class="badge-gkm" style="background: #e9ecef; color: #6c757d;">Tidak Aktif</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-secondary" style="font-size: 0.85rem;">
                                        {{ $template->created_at->format('d/m/Y') }}
                                    </div>
                                    <small class="text-muted" style="font-size: 0.75rem;">
                                        oleh {{ $template->uploader->name ?? '-' }}
                                    </small>
                                </td>
                                <td>
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('gjm.template-laporan.download', $template->id) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Download Template">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            
                                            <form action="{{ route('gjm.template-laporan.reindex', ['id' => $template->id, 'type' => 'semester']) }}" 
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-info"
                                                        title="Reindex ke Vector DB">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                            </form>
                                            
                                            <form action="{{ route('gjm.template-laporan.toggle', ['id' => $template->id, 'type' => 'semester']) }}" 
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-{{ $template->is_active ? 'warning' : 'success' }}"
                                                        title="{{ $template->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                    <i class="bi bi-{{ $template->is_active ? 'toggle-on' : 'toggle-off' }}"></i>
                                                </button>
                                            </form>
                                            
                                            <form action="{{ route('gjm.template-laporan.destroy', ['id' => $template->id, 'type' => 'semester']) }}" 
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
                                            <small class="badge-gkm success" style="font-size: 0.7rem; color: #28a745;">
                                                <i class="bi bi-check-circle"></i> Indexed ({{ $template->total_chunks }} chunks)
                                            </small>
                                        @else
                                            <small class="badge-gkm warning" style="font-size: 0.7rem;">
                                                <i class="bi bi-exclamation-circle"></i> Not indexed
                                            </small>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <p>Belum ada template. <a href="{{ route('gjm.template-laporan.semester.upload') }}" style="color: #5B9BD5; font-weight: 600;">Upload template baru</a></p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($templates->hasPages())
            <div class="mt-4 d-flex justify-content-center">
                {{ $templates->links() }}
            </div>
        @endif
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

// Force delete template
function forceDeleteTemplate(templateId, type) {
    if (confirm('PERHATIAN: Template akan dihapus dan semua laporan yang menggunakan template ini akan kehilangan referensi template. Lanjutkan?')) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/gjm/template-laporan/${templateId}/${type}`;
        
        // CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        form.appendChild(csrfInput);
        
        // Method DELETE
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);
        
        // Force delete flag
        const forceInput = document.createElement('input');
        forceInput.type = 'hidden';
        forceInput.name = 'force_delete';
        forceInput.value = '1';
        form.appendChild(forceInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
