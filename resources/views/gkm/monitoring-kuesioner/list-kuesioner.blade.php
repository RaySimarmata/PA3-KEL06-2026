@extends('layouts.app')

@section('page-title', 'Detail Kuesioner')

@section('content')
    <div style="padding: 1.5rem;">

        {{-- ALERT --}}
        @if (session('success'))
            <div class="alert-gkm success mb-4">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert-gkm danger mb-4">
                <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            </div>
        @endif

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

        {{-- INFO CARD --}}
        <div class="filter-card mb-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label class="filter-label">Kode Mata Kuliah</label>
                    <div style="font-weight: 600; color: #333; font-size: 1.1rem;">{{ $kode_mk }}</div>
                </div>
                <div class="col-md-4">
                    <label class="filter-label">Tahun Ajaran</label>
                    <div>
                        <span class="badge-gkm primary"
                            style="font-size: 0.95rem;">{{ $ta }}/{{ $ta + 1 }}</span>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('gkm.monitoring-kuesioner.create-api') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="monitoring-card">

            <div class="table-responsive">
                @if (isset($pagination) && $pagination->total() > 0)
                    <table class="table table-monitoring">
                        <thead>
                            <tr>
                                <th style="width: 8%;">No</th>
                                <th>Judul Kuesioner</th>
                                <th style="width: 12%;" class="text-center">Semester</th>
                                <th style="width: 20%;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pagination as $i => $item)
                                <tr>
                                    <td class="text-center">
                                        {{ ($pagination->currentPage() - 1) * $pagination->perPage() + $i + 1 }}</td>
                                    <td>
                                        <strong style="color: #333;">{{ $item['judul'] }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="bi bi-book"></i> {{ $item['kode_mk'] }}
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        @if (isset($item['semester']))
                                            @if ($item['semester'] == 1)
                                                <span class="badge-gkm primary">Ganjil</span>
                                            @else
                                                <span class="badge-gkm warning">Genap</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('gkm.monitoring-kuesioner.showa', $item['kuesioner_id']) }}"
                                                class="btn btn-sm btn-lihat-kuesioner" title="Lihat Detail">
                                                <i class="bi bi-eye"></i> Lihat
                                            </a>

                                            <form action="{{ route('gkm.monitoring-kuesioner.processFromApi') }}"
                                                method="POST" class="d-inline form-analisis"
                                                id="form-analisis-{{ $item['kuesioner_id'] }}">
                                                @csrf
                                                <input type="hidden" name="kode_mk" value="{{ $item['kode_mk'] }}">
                                                <input type="hidden" name="ta" value="{{ $item['ta'] }}">
                                                <input type="hidden" name="kuesioner_id"
                                                    value="{{ $item['kuesioner_id'] }}">
                                                <input type="hidden" name="semester"
                                                    value="{{ $item['semester'] ?? null }}">

                                                <button type="button"
                                                    class="btn btn-sm btn-analisis-kuesioner btn-trigger-analisis"
                                                    data-form-id="form-analisis-{{ $item['kuesioner_id'] }}"
                                                    data-judul="{{ $item['judul'] }}" title="Analisis dengan AI">
                                                    <i class="bi bi-cpu"></i> Analisis
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    @if (method_exists($pagination, 'links') && $pagination->total() > $pagination->perPage())
                        <div class="mt-4 d-flex justify-content-center">
                            {{ $pagination->links() }}
                        </div>
                    @endif
                @else
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p>Tidak ada kuesioner ditemukan</p>
                        <small>Silakan coba mata kuliah lain atau periode berbeda</small>
                    </div>
                @endif
            </div>
        </div>

    </div>

    <style>
        /* Tombol Lihat - Warna biru tua solid, tidak berubah saat hover */
        .btn-lihat-kuesioner {
            background-color: #2c5282 !important;
            border-color: #2c5282 !important;
            color: white !important;
            transition: none !important;
        }

        .btn-lihat-kuesioner:hover,
        .btn-lihat-kuesioner:focus,
        .btn-lihat-kuesioner:active,
        .btn-lihat-kuesioner:active:focus,
        .btn-lihat-kuesioner.active {
            background-color: #2c5282 !important;
            border-color: #2c5282 !important;
            color: white !important;
            box-shadow: none !important;
        }

        /* Tombol Analisis - Warna hijau solid seperti btn-success, tidak berubah saat hover */
        .btn-analisis-kuesioner {
            background-color: #198754 !important;
            border-color: #198754 !important;
            color: white !important;
            transition: none !important;
            border-left: none !important;
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
        }

        .btn-analisis-kuesioner:hover,
        .btn-analisis-kuesioner:focus,
        .btn-analisis-kuesioner:active,
        .btn-analisis-kuesioner:active:focus,
        .btn-analisis-kuesioner.active {
            background-color: #198754 !important;
            border-color: #198754 !important;
            color: white !important;
            box-shadow: none !important;
            border-left: none !important;
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
        }

        /* Hapus efek hover pada group button */
        .btn-group .btn-lihat-kuesioner:hover,
        .btn-group .btn-lihat-kuesioner:focus,
        .btn-group .btn-analisis-kuesioner:hover,
        .btn-group .btn-analisis-kuesioner:focus {
            z-index: auto !important;
        }

        /* Custom Pagination Style - Modern & Clean */
        .pagination {
            gap: 0.5rem;
            margin: 0;
        }

        .pagination .page-item {
            margin: 0;
        }

        .pagination .page-link {
            color: #495057;
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            padding: 0.5rem 0.85rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
            min-width: 2.5rem;
            text-align: center;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .pagination .page-link:hover {
            background-color: #f8f9fa;
            border-color: #5B9BD5;
            color: #5B9BD5;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(91, 155, 213, 0.15);
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #5B9BD5 0%, #4a8bc2 100%);
            border-color: #5B9BD5;
            color: white !important;
            font-weight: 600;
            box-shadow: 0 3px 8px rgba(91, 155, 213, 0.3);
            transform: translateY(-1px);
        }

        .pagination .page-item.active .page-link:hover {
            background: linear-gradient(135deg, #4a8bc2 0%, #3d7aad 100%);
            transform: translateY(-1px);
        }

        .pagination .page-item.disabled .page-link {
            color: #ced4da;
            background-color: #f8f9fa;
            border-color: #e9ecef;
            cursor: not-allowed;
            box-shadow: none;
        }

        /* Arrow buttons special styling */
        .pagination .page-item:first-child .page-link,
        .pagination .page-item:last-child .page-link {
            font-weight: 600;
        }
    </style>

    {{-- Modal Konfirmasi Analisis AI --}}
    <div class="modal fade" id="modalKonfirmasiAnalisis" tabindex="-1" aria-labelledby="modalKonfirmasiLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 1rem; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.15);">
                <div class="modal-header border-0 pb-0" style="padding: 1.5rem 1.5rem 0.5rem;">
                    <div class="d-flex align-items-center gap-2">
                        <div
                            style="width: 40px; height: 40px; background: linear-gradient(135deg, #198754, #0f5132); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-cpu text-white" style="font-size: 1.1rem;"></i>
                        </div>
                        <h5 class="modal-title mb-0" id="modalKonfirmasiLabel" style="font-weight: 600; color: #333;">
                            Analisis dengan AI</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 1.25rem 1.5rem;">
                    <p class="mb-1" style="color: #555; font-size: 0.95rem;">Analisis kuesioner berikut menggunakan AI
                        Agent?</p>
                    <div id="modalJudulKuesioner"
                        style="background: #f8f9fa; border-left: 3px solid #198754; padding: 0.6rem 0.9rem; border-radius: 0.25rem; font-size: 0.875rem; color: #333; font-weight: 500; margin-top: 0.75rem;">
                    </div>
                    <p class="mt-3 mb-0" style="font-size: 0.825rem; color: #6c757d;">
                        <i class="bi bi-info-circle"></i> Proses analisis membutuhkan beberapa saat.
                    </p>
                </div>
                <div class="modal-footer border-0 pt-0" style="padding: 0.5rem 1.5rem 1.5rem;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                        style="border-radius: 0.5rem; padding: 0.5rem 1.25rem;">
                        Batal
                    </button>
                    <button type="button" id="btnKonfirmasiAnalisis" class="btn"
                        style="background: linear-gradient(135deg, #198754, #0f5132); color: white; border-radius: 0.5rem; padding: 0.5rem 1.5rem; font-weight: 500; border: none;">
                        <i class="bi bi-cpu me-1"></i> Ya, Analisis
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let targetFormId = null;

            // Trigger modal saat tombol Analisis diklik
            document.querySelectorAll('.btn-trigger-analisis').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    targetFormId = this.getAttribute('data-form-id');
                    const judul = this.getAttribute('data-judul');
                    document.getElementById('modalJudulKuesioner').textContent = judul;
                    const modal = new bootstrap.Modal(document.getElementById(
                        'modalKonfirmasiAnalisis'));
                    modal.show();
                });
            });

            // Submit form saat dikonfirmasi
            document.getElementById('btnKonfirmasiAnalisis').addEventListener('click', function() {
                if (targetFormId) {
                    document.getElementById(targetFormId).submit();
                }
            });
        });
    </script>

@endsection
