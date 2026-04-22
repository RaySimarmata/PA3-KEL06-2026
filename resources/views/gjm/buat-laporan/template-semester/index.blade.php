@extends('layouts.app')

@section('page-title', 'Kelola Template Laporan Semester')

@section('content')
<div style="padding: 1.5rem;">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert-gjm success mb-4">
            <i class="bi bi-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert-gjm danger mb-4">
            <i class="bi bi-exclamation-triangle"></i>
            {{ session('error') }}
        </div>
    @endif

    <!-- Header Card -->
    <div class="filter-card mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="mb-1 d-flex align-items-center gap-2" style="font-weight: 600; color: #333;">
                    <i class="bi bi-file-earmark-word" style="color: #5B9BD5;"></i>
                    Kelola Template Laporan Semester
                </h5>
                <p class="text-muted mb-0" style="font-size: 0.875rem;">Upload dan kelola template Word untuk laporan semester GJM</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('gjm.buat-laporan.semester') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <a href="{{ route('gjm.buat-laporan.template-semester.upload') }}" class="btn-reminder">
                    <i class="bi bi-upload"></i>
                    <span>Upload Template Baru</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Info Card -->
    <div class="monitoring-card mb-4" style="border-left: 4px solid #28a745;">
        <div style="padding: 1.5rem;">
            <div class="d-flex align-items-start">
                <i class="bi bi-info-circle" style="color: #28a745; font-size: 2rem; margin-right: 1rem;"></i>
                <div>
                    <h6 class="mb-2" style="font-weight: 600; color: #333;">Tentang Template Semester</h6>
                    <p class="mb-2" style="font-size: 0.875rem; color: #495057; line-height: 1.6;">
                        Template semester adalah contoh laporan Word yang akan digunakan sebagai format standar untuk laporan semester GJM. 
                        Upload template dengan struktur yang jelas untuk hasil yang konsisten.
                    </p>
                    <p class="mb-0" style="font-size: 0.8rem; color: #6c757d;">
                        <strong>Tips:</strong> Template ini khusus untuk laporan semester (6 bulan). Gunakan heading, 
                        bullet points, dan format yang konsisten.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Template List -->
    <div class="monitoring-card">
        <div class="monitoring-header">
            <i class="bi bi-list-ul" style="color: #28a745;"></i>
            <h6>Daftar Template Semester</h6>
        </div>

        <div class="table-responsive">
            <table class="table table-monitoring">
                <thead>
                    <tr>
                        <th style="width: 30%;">Nama Template</th>
                        <th style="width: 20%;">File</th>
                        <th style="width: 15%;" class="text-center">Status</th>
                        <th style="width: 15%;">Diupload</th>
                        <th style="width: 20%;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="code-mk">{{ $template->nama_template }}</span>
                                    @if($template->is_active)
                                        <span class="badge-gjm success">Active</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-file-earmark-word" style="color: #28a745;"></i>
                                    <div>
                                        <div class="text-secondary" style="font-size: 0.85rem;">{{ $template->nama_file }}</div>
                                        <small class="text-muted" style="font-size: 0.75rem;">
                                            {{ number_format($template->ukuran_file / 1024, 2) }} KB
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                @if($template->is_active)
                                    <span class="badge-gjm success">Aktif</span>
                                @else
                                    <span class="badge-gjm" style="background: #e9ecef; color: #6c757d;">Tidak Aktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-secondary" style="font-size: 0.85rem;">
                                    {{ $template->created_at->format('d/m/Y') }}
                                </div>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('gjm.buat-laporan.template-semester.download', $template->id) }}" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="Download Template">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    
                                    <form action="{{ route('gjm.buat-laporan.template-semester.toggle', $template->id) }}" 
                                          method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" 
                                                class="btn btn-sm btn-outline-{{ $template->is_active ? 'warning' : 'success' }}"
                                                title="{{ $template->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            <i class="bi bi-{{ $template->is_active ? 'toggle-on' : 'toggle-off' }}"></i>
                                        </button>
                                    </form>
                                    
                                    <form action="{{ route('gjm.buat-laporan.template-semester.destroy', $template->id) }}" 
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
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <p>Belum ada template semester. Hubungi administrator untuk menambahkan template.</p>
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
