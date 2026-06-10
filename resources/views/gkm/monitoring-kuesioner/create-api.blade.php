@extends('layouts.app')

@section('page-title', 'Monitoring Kuesioner (API)')

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

        {{-- FILTER --}}
        <div class="filter-card mb-4">

            <form method="GET" id="filterForm">
                <div class="row g-3 align-items-end">

                    {{-- TAHUN --}}
                    <div class="col-md-3">
                        <label class="filter-label">Tahun Ajaran</label>
                        <select name="ta" class="form-select" required>
                            <option value="">Pilih Tahun</option>
                            @foreach ($tahunList as $tahun)
                                <option value="{{ $tahun }}" {{ (int) $ta === (int) $tahun ? 'selected' : '' }}>
                                    {{ $tahun }}/{{ $tahun + 1 }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- SEMESTER --}}
                    <div class="col-md-3">
                        <label class="filter-label">Semester</label>
                        <select name="semester" class="form-select" required>
                            <option value="">Pilih Semester</option>
                            <option value="1" {{ ($semester ?? $semesterAktif) == 1 ? 'selected' : '' }}>
                                Ganjil
                            </option>
                            <option value="2" {{ ($semester ?? $semesterAktif) == 2 ? 'selected' : '' }}>
                                Genap
                            </option>
                        </select>
                    </div>

                    {{-- TINGKAT --}}
                    <div class="col-md-3">
                        <label class="filter-label">Tingkat</label>
                        <select name="tingkat" class="form-select">
                            <option value="">Semua Tingkat</option>
                            <option value="1" {{ request('tingkat') == '1' ? 'selected' : '' }}>Tingkat 1</option>
                            <option value="2" {{ request('tingkat') == '2' ? 'selected' : '' }}>Tingkat 2</option>
                            <option value="3" {{ request('tingkat') == '3' ? 'selected' : '' }}>Tingkat 3</option>
                            <option value="4" {{ request('tingkat') == '4' ? 'selected' : '' }}>Tingkat 4</option>
                        </select>
                    </div>

                    {{-- BUTTON FILTER --}}
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100" style="padding: 0.6rem;">
                            <i class="bi bi-search"></i> Cari
                        </button>
                    </div>

                </div>
            </form>

            {{-- BUTTON ANALISIS SEMUA --}}
            @if (isset($pagination) && $pagination->total() > 0)
                <div class="mt-3 pt-3 border-top">
                    <form action="{{ route('gkm.monitoring-kuesioner.sync-semester') }}" method="POST"
                        id="formAnalisisSemua">
                        @csrf
                        <input type="hidden" name="ta" value="{{ $ta }}">
                        <input type="hidden" name="semester" value="{{ $semester }}">
                        <input type="hidden" name="tingkat" value="{{ request('tingkat') }}">

                        <button type="button" class="btn-analisis-semua" onclick="showAnalisisModal()">
                            <i class="bi bi-cpu"></i> Analisis Semua Kuesioner
                        </button>
                        <a href="{{ route('gkm.monitoring-kuesioner.index') }}" class="btn-kembali-api">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </form>
                </div>
            @endif
        </div>

        {{-- TABLE --}}
        <div class="monitoring-card">
            <div class="monitoring-header">
                <i class="bi bi-list-ul" style="color: #5B9BD5;"></i>
                <h6>Daftar Mata Kuliah</h6>
                @if (isset($pagination) && $pagination->total() > 0)
                    <div style="margin-left: auto;">
                        <span class="badge-gkm info" style="font-size: 0.9rem;">
                            {{ $pagination->total() }} Mata Kuliah
                        </span>
                    </div>
                @endif
            </div>

            <div class="table-responsive">
                @if (isset($pagination) && $pagination->total() > 0)
                    <table class="table table-monitoring">
                        <thead>
                            <tr>
                                <th style="width: 8%;">No</th>
                                <th style="width: 15%;">Kode MK</th>
                                <th style="width: 10%;">TA</th>
                                <th>Nama Mata Kuliah</th>
                                <th style="width: 15%;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pagination as $i => $item)
                                <tr>
                                    <td class="text-center">
                                        {{ ($pagination->currentPage() - 1) * $pagination->perPage() + $i + 1 }}</td>
                                    <td class="code-mk">{{ $item['kode_mk'] }}</td>
                                    <td>
                                        <span class="badge-gkm primary">{{ $item['ta'] }}/{{ $item['ta'] + 1 }}</span>
                                    </td>
                                    <td>{{ $item['nama_mk'] }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('gkm.monitoring-kuesioner.listKuesioner', [
                                            'kode_mk' => $item['kode_mk'],
                                            'ta' => $item['ta'],
                                        ]) }}"
                                            class="btn btn-sm btn-lihat-kuesioner-api">
                                            {{-- <i class="bi bi-list-ul"></i> --}}

                                            Lihat Kuesioner
                                        </a>
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
                        <p>Belum ada data ditampilkan</p>
                        <small>Silakan pilih Tahun Ajaran dan Semester terlebih dahulu</small>
                    </div>
                @endif
            </div>
        </div>

    </div>

    <style>
        /* Tombol Lihat Kuesioner - Biru Tua, tidak berubah saat hover */
        .btn-lihat-kuesioner-api {
            background-color: #2c5282 !important;
            border-color: #2c5282 !important;
            color: white !important;
            transition: none !important;
        }

        .btn-lihat-kuesioner-api:hover,
        .btn-lihat-kuesioner-api:focus,
        .btn-lihat-kuesioner-api:active,
        .btn-lihat-kuesioner-api:active:focus {
            background-color: #2c5282 !important;
            border-color: #2c5282 !important;
            color: white !important;
            box-shadow: none !important;
            transform: none !important;
        }

        /* Tombol Analisis Semua - Hijau, tidak berubah saat hover */
        .btn-analisis-semua {
            background-color: #198754 !important;
            border: 1px solid #198754 !important;
            color: white !important;
            padding: 0.45rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            line-height: 1.5;
            transition: none !important;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .btn-analisis-semua:hover,
        .btn-analisis-semua:focus,
        .btn-analisis-semua:active,
        .btn-analisis-semua:active:focus {
            background-color: #198754 !important;
            border-color: #198754 !important;
            color: white !important;
            box-shadow: none !important;
            transform: none !important;
        }

        /* Tombol Kembali - Abu-abu, tidak berubah saat hover */
        .btn-kembali-api {
            background-color: #6c757d !important;
            border: 1px solid #6c757d !important;
            color: white !important;
            padding: 0.45rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            line-height: 1.5;
            transition: none !important;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .btn-kembali-api:hover,
        .btn-kembali-api:focus,
        .btn-kembali-api:active,
        .btn-kembali-api:active:focus,
        .btn-kembali-api:visited {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            color: white !important;
            box-shadow: none !important;
            transform: none !important;
            text-decoration: none;
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
    {{-- Modal Konfirmasi Analisis Semua --}}
    <div id="modalAnalisis"
        style="display:none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center;">
        {{-- Backdrop --}}
        <div onclick="hideAnalisisModal()"
            style="position:absolute; inset:0; background:rgba(0,0,0,0.45); backdrop-filter:blur(2px);"></div>

        {{-- Dialog --}}
        <div
            style="position:relative; background:#fff; border-radius:1rem; padding:2rem; max-width:420px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.2); animation: modalFadeIn .2s ease;">
            {{-- Icon --}}
            <div style="text-align:center; margin-bottom:1.25rem;">
                <div
                    style="display:inline-flex; align-items:center; justify-content:center; width:64px; height:64px; border-radius:50%; background:linear-gradient(135deg,#198754,#20c997);">
                    <i class="bi bi-cpu-fill" style="font-size:1.75rem; color:white;"></i>
                </div>
            </div>

            {{-- Title --}}
            <h5 style="text-align:center; font-weight:700; color:#1a1a2e; margin-bottom:0.5rem;">Analisis Semua Kuesioner?
            </h5>

            {{-- Body --}}
            <p style="text-align:center; color:#6c757d; font-size:0.9rem; margin-bottom:1.5rem; line-height:1.6;">
                Proses ini akan menganalisis seluruh kuesioner menggunakan AI.<br>
                <strong style="color:#495057;">Harap tunggu, proses ini membutuhkan waktu.</strong>
            </p>

            {{-- Buttons --}}
            <div style="display:flex; gap:0.75rem;">
                <button onclick="hideAnalisisModal()"
                    style="flex:1; padding:0.6rem; border:1px solid #dee2e6; border-radius:0.5rem; background:#f8f9fa; color:#495057; font-weight:600; cursor:pointer; font-size:0.95rem; transition:background .15s;">
                    <i class="bi bi-x-lg"></i> Batal
                </button>
                <button onclick="submitAnalisis()"
                    style="flex:1; padding:0.6rem; border:none; border-radius:0.5rem; background:linear-gradient(135deg,#198754,#20c997); color:white; font-weight:600; cursor:pointer; font-size:0.95rem; transition:opacity .15s;">
                    <i class="bi bi-cpu"></i> Ya, Analisis
                </button>
            </div>
        </div>
    </div>

    <style>
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(.95) translateY(-8px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
    </style>

    <script>
        function showAnalisisModal() {
            const modal = document.getElementById('modalAnalisis');
            modal.style.display = 'flex';
        }

        function hideAnalisisModal() {
            const modal = document.getElementById('modalAnalisis');
            modal.style.display = 'none';
        }

        function submitAnalisis() {
            const btn = event.currentTarget;
            btn.disabled = true;
            btn.innerHTML =
                '<span style="display:inline-flex;align-items:center;gap:.4rem;"><svg style="animation:spin 1s linear infinite;width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-opacity=".3"/><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/></svg> Memproses...</span>';
            document.getElementById('formAnalisisSemua').submit();
        }

        // Tutup modal dengan Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') hideAnalisisModal();
        });
    </script>

    <style>
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>

@endsection
