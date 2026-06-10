@extends('layouts.app')

@section('page-title', 'Monitoring Kuesioner')

@section('content')
    <div style="padding: 1.5rem;">

        {{-- ALERT --}}
        @if (session('success'))
            <div class="alert-gkm success mb-4">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
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

                    <div class="col-md-4">
                        <label class="filter-label">Periode</label>
                        <select name="periode" class="form-select">
                            <option value="">Semua Periode</option>
                            @foreach($periodeOptions as $periode)
                                <option value="{{ $periode }}" {{ request('periode') == $periode ? 'selected' : '' }}>{{ $periode }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="filter-label">Jenis Kuesioner</label>
                        <select name="jenis_kuesioner" class="form-select">
                            <option value="">Semua Jenis</option>
                            <option value="UTS" {{ request('jenis_kuesioner') == 'UTS' ? 'selected' : '' }}>UTS</option>
                            <option value="UAS" {{ request('jenis_kuesioner') == 'UAS' ? 'selected' : '' }}>UAS</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100" style="padding: 0.6rem;">
                            <i class="bi bi-search"></i> Cari
                        </button>

                    </div>

                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="monitoring-card">
            <div class="monitoring-header">
                <h6>Monitoring Kuesioner</h6>
                <div style="margin-left: auto;">
                    <a href="{{ route('gkm.monitoring-kuesioner.create-api') }}" class="btn-reminder"
                        style="background-color: #28a745; border-color: #28a745; transition: none;"
                        onmouseover="this.style.backgroundColor='#28a745'; this.style.borderColor='#28a745';"
                        onmouseout="this.style.backgroundColor='#28a745'; this.style.borderColor='#28a745';">
                        <i class="bi bi-cloud-download"></i>
                        <span>Ambil dari API</span>
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-monitoring">

                    <thead>
                        <tr>
                            <th style="width: 3%;">No</th>
                            <th style="width: 18%;">Nama File</th>
                            <th style="width: 10%;">Tahun Ajaran</th>
                            {{-- <th style="width: 15%;">Matakuliah</th>
                            <th style="width: 8%;">Kode</th> --}}
                            {{-- <th style="width: 5%;" class="text-center">Tkt</th> --}}
                            <th style="width: 6%;" class="text-center">Jenis</th>
                            <th style="width: 5%;" class="text-center">Resp.</th>
                            <th style="width: 6%;" class="text-center">Index</th>
                            {{-- <th style="width: 8%;" class="text-center">Status</th> --}}
                            <th style="width: 10%;">Tanggal Analisis</th>
                            <th style="width: 12%;" class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($kuesioners as $i => $k)

                            @php
                                $stat = $k->hasil_analisis['statistik'] ?? null;
                                $index = $stat['index_kepuasan'] ?? null;
                                $persen = $index ? ($index / 4) * 100 : null;

                                $badgeColor = 'secondary';
                                if ($index >= 3.5) {
                                    $badgeColor = 'success';
                                } elseif ($index >= 3.0) {
                                    $badgeColor = 'info';
                                } elseif ($index >= 2.5) {
                                    $badgeColor = 'warning';
                                } elseif ($index) {
                                    $badgeColor = 'danger';
                                }
                            @endphp

                            <tr>
                                <td class="text-center">{{ ($kuesioners->currentPage() - 1) * $kuesioners->perPage() + $i + 1 }}</td>

                                <td>
                                    <strong style="color: #333;">{{ Str::limit($k->nama_file, 50) }}</strong>
                                    @if ($k->dosen_pengampu || $k->pegawai_id)
                                        <br>
                                        <small class="text-muted">
                                            @php
                                                $dosen = \App\Models\Dosenn::where(
                                                    'pegawai_id',
                                                    $k->pegawai_id,
                                                )->first();
                                            @endphp
                                            <i class="bi bi-person"></i> {{ $dosen->nama ?? ($k->dosen_pengampu ?? '-') }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge-gkm info">{{ $k->periode }}</span>
                                </td>
                                {{-- <td>
                                    @if ($k->nama_matakuliah)
                                        {{ $k->nama_matakuliah }}
                                    @else
                                        @php
                                            $matkul = \App\Models\Matakuliah::where(
                                                'kode_mk',
                                                $k->kode_matakuliah,
                                            )->first();
                                        @endphp
                                        {{ $matkul->nama_mk ?? '-' }}
                                    @endif
                                </td>
                                <td class="code-mk">{{ $k->kode_matakuliah ?? '-' }}</td> --}}
                                {{-- <td class="text-center">
                                    <span class="badge-gkm primary">{{ $k->tingkat ?? '-' }}</span>
                                </td> --}}

                                {{-- JENIS KUESIONER --}}
                                <td class="text-center">
                                    @if ($k->jenis_kuesioner)
                                        @if ($k->jenis_kuesioner === 'UTS')
                                            <span class="badge-gkm warning">UTS</span>
                                        @elseif($k->jenis_kuesioner === 'UAS')
                                            <span class="badge-gkm success">UAS</span>
                                        @else
                                            <span class="badge-gkm secondary">{{ $k->jenis_kuesioner }}</span>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>

                                {{-- RESPONDEN --}}
                                <td class="text-center">
                                    <strong style="color: #5B9BD5;">{{ $k->total_responden }}</strong>
                                </td>

                                {{-- INDEX --}}
                                <td class="text-center">
                                    @if ($index)
                                        <span class="badge-gkm {{ $badgeColor }}">
                                            {{ number_format($index, 2) }}
                                        </span>
                                        <br>
                                        <small class="text-muted">{{ number_format($persen, 1) }}%</small>
                                    @else
                                        -
                                    @endif
                                </td>

                                {{-- STATUS
                                <td class="text-center">
                                    @switch($k->status)
                                        @case('uploaded')
                                            <span class="badge-gkm info">Uploaded</span>
                                        @break

                                        @case('processing')
                                            <span class="badge-gkm warning">Processing</span>
                                        @break

                                        @case('completed')
                                            <span class="badge-gkm success">Completed</span>
                                        @break

                                        @case('error')
                                            <span class="badge-gkm danger">Error</span>
                                        @break
                                    @endswitch
                                </td> --}}

                                <td class="text-secondary" style="font-size: 0.85rem;">
                                    {{ $k->created_at->format('d/m/Y H:i') }}
                                </td>

                                {{-- AKSI --}}
                                <td class="text-center">
                                    <div class="btn-group" role="group">

                                        <a href="{{ route('gkm.monitoring-kuesioner.show', $k->id) }}"
                                            class="btn btn-sm btn-lihat-kuesioner" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        @if ($k->status === 'completed')
                                            <a href="{{ route('gkm.monitoring-kuesioner.report', $k->id) }}"
                                                class="btn btn-sm btn-laporan-kuesioner" title="Lihat Laporan">
                                                <i class="bi bi-file-earmark-text"></i>
                                            </a>
                                        @endif

                                        <form action="{{ route('gkm.monitoring-kuesioner.destroy', $k->id) }}"
                                            method="POST" onsubmit="return confirm('Yakin hapus data kuesioner ini?')"
                                            class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-hapus-kuesioner" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>

                                    </div>
                                </td>

                            </tr>

                            @empty
                                <tr>
                                    <td colspan="12" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="bi bi-inbox"></i>
                                            <p>Belum ada data kuesioner. <a
                                                    href="{{ route('gkm.monitoring-kuesioner.create-api') }}"
                                                    style="color: #5B9BD5; font-weight: 600;">Ambil data dari API</a></p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
            </table>

            {{-- @if($kuesioners->hasPages())
                <div class="pagination-wrapper mt-3">
                    {{ $kuesioners->links() }}
                </div>
            @endif --}}
        </div>
    </div>

    <!-- Pagination - Outside the card -->
    @if(method_exists($kuesioners, 'links') && $kuesioners->total() > $kuesioners->perPage())
    <div class="mt-4 d-flex justify-content-center">
        {{ $kuesioners->links() }}
    </div>
    @endif

</div>

        <style>
            /* Tombol Lihat - Biru Tua, tidak berubah saat hover */
            .btn-lihat-kuesioner {
                background-color: #2c5282 !important;
                border-color: #2c5282 !important;
                color: white !important;
                transition: none !important;
            }

            .btn-lihat-kuesioner:hover,
            .btn-lihat-kuesioner:focus,
            .btn-lihat-kuesioner:active,
            .btn-lihat-kuesioner:active:focus {
                background-color: #2c5282 !important;
                border-color: #2c5282 !important;
                color: white !important;
                box-shadow: none !important;
                transform: none !important;
            }

            /* Tombol Laporan - Hijau, tidak berubah saat hover */
            .btn-laporan-kuesioner {
                background-color: #198754 !important;
                border-color: #198754 !important;
                color: white !important;
                transition: none !important;
            }

            .btn-laporan-kuesioner:hover,
            .btn-laporan-kuesioner:focus,
            .btn-laporan-kuesioner:active,
            .btn-laporan-kuesioner:active:focus {
                background-color: #198754 !important;
                border-color: #198754 !important;
                color: white !important;
                box-shadow: none !important;
                transform: none !important;
            }

            /* Tombol Hapus - Merah, tidak berubah saat hover */
            .btn-hapus-kuesioner {
                background-color: #dc3545 !important;
                border-color: #dc3545 !important;
                color: white !important;
                transition: none !important;
                border-left: none !important;
                border-top-left-radius: 0 !important;
                border-bottom-left-radius: 0 !important;
            }

            .btn-hapus-kuesioner:hover,
            .btn-hapus-kuesioner:focus,
            .btn-hapus-kuesioner:active,
            .btn-hapus-kuesioner:active:focus {
                background-color: #dc3545 !important;
                border-color: #dc3545 !important;
                color: white !important;
                box-shadow: none !important;
                transform: none !important;
                border-left: none !important;
                border-top-left-radius: 0 !important;
                border-bottom-left-radius: 0 !important;
            }

            /* Disable hover effects pada button group */
            .btn-group .btn:hover,
            .btn-group .btn:focus {
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

        <script>
            // Auto refresh untuk status processing
            setInterval(() => {
                if (document.querySelector('.badge-gkm.warning')) {
                    location.reload();
                }
            }, 30000);
        </script>

    @endsection
