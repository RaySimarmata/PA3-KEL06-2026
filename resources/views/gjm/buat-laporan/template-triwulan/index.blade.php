@extends('layouts.app')

@section('page-title', 'Kelola Template Laporan Triwulan')

@section('content')
<div style="padding: 1.5rem;">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert-app success mb-3" data-auto-dismiss>
            <i class="bi bi-check-circle-fill alert-app-icon"></i>
            <div class="alert-app-body">{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="alert-app danger mb-3" data-auto-dismiss>
            <i class="bi bi-exclamation-triangle-fill alert-app-icon"></i>
            <div class="alert-app-body">{{ session('error') }}</div>
        </div>
    @endif

    @if (isset($errors) && $errors->any())
        <div class="alert-app danger mb-3">
            <i class="bi bi-exclamation-triangle-fill alert-app-icon"></i>
            <div class="alert-app-body">
                <div class="alert-app-title">Terjadi kesalahan:</div>
                <ul class="mb-0" style="font-size:0.875rem; padding-left:1.25rem; margin-top:0.25rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Header Card -->
    <div class="filter-card mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="mb-1 d-flex align-items-center gap-2" style="font-weight: 600; color: #333;">
                    Kelola Template Laporan Triwulan
                </h5>
                <p class="text-muted mb-0" style="font-size: 0.875rem;">Upload dan kelola template Word untuk laporan triwulan GJM</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('gjm.buat-laporan.triwulan') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <a href="{{ route('gjm.buat-laporan.template-triwulan.upload') }}" class="btn-reminder">
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
                    <h6 class="mb-2" style="font-weight: 600; color: #333;">Tentang Template Triwulan</h6>
                    <p class="mb-2" style="font-size: 0.875rem; color: #495057; line-height: 1.6;">
                        Template triwulan adalah contoh laporan Word yang akan digunakan sebagai format standar untuk laporan triwulan GJM. 
                        Upload template dengan struktur yang jelas untuk hasil yang konsisten.
                    </p>
                    <p class="mb-0" style="font-size: 0.8rem; color: #6c757d;">
                        <strong>Tips:</strong> Template ini khusus untuk laporan triwulan (3 bulan). Gunakan heading, 
                        bullet points, dan format yang konsisten.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Template List -->
    <div class="monitoring-card">
        <div class="monitoring-header">
            <i class="bi bi-list-ul" style="color: #5B9BD5;"></i>
            <h6>Daftar Template Triwulan</h6>
        </div>

        <div class="table-responsive">
            <table class="table table-monitoring">
                <thead>
                    <tr>
                        <th style="width: 25%;">Nama Template</th>
                        <th style="width: 20%;">File</th>
                        <th style="width: 12%;" class="text-center">Status</th>
                        <th style="width: 15%;">Diupload</th>
                        <th style="width: 15%;">Diupload Oleh</th>
                        <th style="width: 13%;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-file-earmark-word-fill" style="color: #2B579A; font-size: 1.2rem;"></i>
                                    <div>
                                        <div style="font-weight: 500; color: #333;">{{ $template->nama_template }}</div>
                                        @if($template->is_active)
                                            <span class="badge bg-success" style="font-size: 0.7rem;">
                                                <i class="bi bi-check-circle"></i> Template Aktif
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <div class="text-secondary" style="font-size: 0.85rem;">
                                        <i class="bi bi-paperclip"></i> {{ $template->nama_file }}
                                    </div>
                                    <small class="text-muted" style="font-size: 0.75rem;">
                                        <i class="bi bi-hdd"></i> {{ number_format($template->ukuran_file / 1024, 2) }} KB
                                    </small>
                                </div>
                            </td>
                            <td class="text-center">
                                @if($template->is_active)
                                    <span class="badge bg-success">
                                        <i class="bi bi-toggle-on"></i> Aktif
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-toggle-off"></i> Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="text-secondary" style="font-size: 0.85rem;">
                                    <i class="bi bi-calendar3"></i> {{ $template->created_at->format('d/m/Y') }}
                                </div>
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    <i class="bi bi-clock"></i> {{ $template->created_at->format('H:i') }}
                                </small>
                            </td>
                            <td>
                                <div class="text-secondary" style="font-size: 0.85rem;">
                                    <i class="bi bi-person-circle"></i> {{ $template->uploader->name ?? 'System' }}
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('gjm.buat-laporan.template-triwulan.download', $template->id) }}" 
                                           class="btn btn-sm btn-outline-primary" 
                                           data-bs-toggle="tooltip"
                                           title="Download Template">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        
                                        <form action="{{ route('gjm.buat-laporan.template-triwulan.reindex', $template->id) }}" 
                                              method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" 
                                                    class="btn btn-sm btn-outline-info"
                                                    data-bs-toggle="tooltip"
                                                    title="Reindex ke Vector DB">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                        </form>
                                        
                                        <form action="{{ route('gjm.buat-laporan.template-triwulan.toggle', $template->id) }}" 
                                              method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" 
                                                    class="btn btn-sm btn-outline-{{ $template->is_active ? 'warning' : 'success' }}"
                                                    data-bs-toggle="tooltip"
                                                    title="{{ $template->is_active ? 'Nonaktifkan Template' : 'Aktifkan Template' }}">
                                                <i class="bi bi-{{ $template->is_active ? 'toggle-on' : 'toggle-off' }}"></i>
                                            </button>
                                        </form>
                                        
                                        <form action="{{ route('gjm.buat-laporan.template-triwulan.destroy', $template->id) }}" 
                                              method="POST" class="d-inline"
                                              onsubmit="AppConfirm.delete(this, 'Template &quot;{{ $template->nama_template }}&quot; akan dihapus permanen.'); return false;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="tooltip"
                                                    title="Hapus Template">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                    @if($template->is_indexed)
                                        <small class="badge bg-success" style="font-size: 0.7rem;">
                                            <i class="bi bi-check-circle"></i> Indexed ({{ $template->total_chunks }} chunks)
                                        </small>
                                    @else
                                        <small class="badge bg-warning text-dark" style="font-size: 0.7rem;">
                                            <i class="bi bi-exclamation-circle"></i> Not indexed
                                        </small>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="empty-state" style="padding: 3rem 1rem;">
                                    <i class="bi bi-inbox" style="font-size: 4rem; color: #dee2e6;"></i>
                                    <h6 class="mt-3 mb-2" style="color: #6c757d;">Belum Ada Template Triwulan</h6>
                                    <p class="text-muted mb-3" style="font-size: 0.875rem;">
                                        Hubungi administrator untuk menambahkan template
                                    </p>
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
    // Bootstrap 5 tooltip initialization
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>
@endpush
