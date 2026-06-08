@extends('layouts.app')

@section('page-title', 'Arsip PPT')

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Header Card (sama seperti halaman lain) -->
        <div class="filter-card mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="mb-1 fw-semibold" style="color: #333;">
                        Arsip PPT
                    </h5>
                    <p class="text-muted mb-0 small">
                        Kelola dan unduh presentasi yang telah dibuat
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <form method="GET" class="d-flex gap-2">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Cari PPT..."
                                value="{{ $search ?? '' }}">
                        </div>
                    </form>
                    <a href="{{ route('gjm.buat-ppt.index') }}"
                        class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                        <i class="bi bi-plus-circle"></i> Buat PPT
                    </a>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="monitoring-card">
            <div class="monitoring-header">
                <h6 class="mb-0">Daftar Presentasi</h6>
                <span class="badge bg-primary">{{ $pptArchive->total() }} PPT</span>
            </div>

            <div style="padding: 1.5rem;">
                <div class="row" id="pptGrid">
                    @forelse($pptArchive as $item)
                        <div class="col-md-4 mb-4">
                            <div class="ppt-card">
                                <div class="ppt-header">
                                    <i class="bi bi-file-ppt"></i>
                                    <div class="ppt-title">
                                        {{ Str::limit($item->ringkasan_mutu_institusi ?? 'Laporan ' . $item->getJenisLaporanLabel(), 60) }}
                                    </div>
                                </div>

                                <div class="ppt-body">
                                    <div class="ppt-meta">
                                        <span class="badge bg-secondary">{{ $item->getJenisLaporanLabel() }}</span>
                                        @if ($item->ajaran)
                                            <span class="text-muted">{{ $item->ajaran->tahun_ajaran }}</span>
                                        @endif
                                    </div>

                                    <div class="ppt-date">
                                        <i class="bi bi-clock"></i>
                                        {{ $item->ppt_generated_at ? $item->ppt_generated_at->format('d M Y, H:i') : ($item->created_at ? $item->created_at->format('d M Y') : '-') }}
                                    </div>

                                    <div class="ppt-actions">
                                        <button
                                            class="btn btn-success w-100 mb-2 d-inline-flex align-items-center justify-content-center gap-1"
                                            onclick="downloadPPT({{ $item->id }})">
                                            <i class="bi bi-download"></i> Download
                                        </button>
                                        <button
                                            class="btn btn-danger w-100 d-inline-flex align-items-center justify-content-center gap-1"
                                            onclick="deletePPT({{ $item->id }}, '{{ addslashes($item->ringkasan_mutu_institusi ?? 'PPT ini') }}')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-inbox fs-1 text-muted"></i>
                                    <p class="mt-2 mb-0">Belum ada PPT yang dibuat</p>
                                    <a href="{{ route('gjm.buat-ppt.index') }}" class="btn btn-primary btn-sm mt-3">
                                        <i class="bi bi-plus-circle"></i> Buat PPT
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            @if ($pptArchive->hasPages())
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 px-3 pb-3">
                    <div class="text-muted small">
                        Menampilkan {{ $pptArchive->count() }} dari {{ $pptArchive->total() }} PPT
                    </div>
                    <div>
                        {{ $pptArchive->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <style>
        .ppt-card {
            background: white;
            border: none;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .ppt-card:hover {
            box-shadow: 0 12px 24px rgba(30, 60, 114, 0.15);
            transform: translateY(-4px);
        }

        .ppt-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 1.75rem 1.5rem;
            min-height: 130px;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            position: relative;
            overflow: hidden;
        }

        .ppt-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }

        .ppt-header i {
            font-size: 2.5rem;
            opacity: 0.9;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .ppt-title {
            font-weight: 600;
            font-size: 0.95rem;
            line-height: 1.4;
            position: relative;
            z-index: 1;
        }

        .ppt-body {
            padding: 1.25rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
        }

        .ppt-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
        }

        .ppt-meta .badge {
            background: linear-gradient(135deg, #5B9BD5 0%, #3a7bc8 100%);
            color: white;
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            border: none;
        }

        .ppt-meta .text-muted {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .ppt-date {
            font-size: 0.75rem;
            color: #6c757d;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 0.75rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .ppt-date i {
            color: #5B9BD5;
        }

        .ppt-actions {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .ppt-actions .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            padding: 0.6rem 1rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(40, 167, 69, 0.2);
        }

        .ppt-actions .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .ppt-actions .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            padding: 0.6rem 1rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(220, 53, 69, 0.2);
        }

        .ppt-actions .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .filter-card {
            background: white;
            border-radius: 16px;
            padding: 1.25rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
        }
    </style>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header" style="background: linear-gradient(135deg, #1e3c72, #2a5298); border: none;">
                    <h5 class="modal-title text-white">
                        <i class="bi bi-exclamation-triangle"></i> Konfirmasi Hapus
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 1.5rem;">
                    <p>Apakah Anda yakin ingin menghapus PPT <strong id="deletePPTName"></strong>?</p>
                    <p class="text-danger mb-0"><i class="bi bi-exclamation-triangle"></i> Tindakan ini tidak dapat
                        dibatalkan.</p>
                </div>
                <div class="modal-footer" style="background: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="bi bi-trash"></i> Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        let deleteModal = null;
        let currentDeleteId = null;

        document.addEventListener('DOMContentLoaded', function() {
            deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

            document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
                if (currentDeleteId) {
                    performDelete(currentDeleteId);
                }
            });
        });

        function downloadPPT(id) {
            const downloadUrl = `/gjm/buat-ppt/${id}/download`;
            window.location.href = downloadUrl;
        }

        function deletePPT(id, name) {
            currentDeleteId = id;
            document.getElementById('deletePPTName').textContent = name;
            deleteModal.show();
        }

        async function performDelete(id) {
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            const originalText = confirmBtn.innerHTML;

            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menghapus...';

            try {
                const response = await fetch(`/gjm/buat-ppt/${id}/delete`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    deleteModal.hide();
                    alert('PPT berhasil dihapus');
                    location.reload();
                } else {
                    alert('Gagal menghapus PPT: ' + data.message);
                }
            } catch (error) {
                alert('Terjadi kesalahan: ' + error.message);
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalText;
            }
        }
    </script>
@endsection
