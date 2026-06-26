@extends('layouts.app')

@section('page-title', 'Monitoring RPS')

@section('content')


{{-- <div style="padding: 1.5rem;">
    <div class="filter-card mb-4 d-flex justify-content-between align-items-center">
    <h5 class="mb-0 font-semibold" style="text-transform: uppercase; letter-spacing: 0.5px;">
        Monitoring Rencana Pembelajaran Semester (RPS)
    </h5>
    <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div> --}}

    <!-- Filter Section -->
    <div class="filter-card">
        <form method="GET" id="filterForm">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="filter-label">Semester</label>
                    <select name="semester" class="form-select">
                        <option value="">Semua Semester</option>
                        <option value="1" {{ request('semester') == '1' ? 'selected' : '' }}>Ganjil</option>
                        <option value="2" {{ request('semester') == '2' ? 'selected' : '' }}>Genap</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="filter-label">Tahun Ajaran</label>
                    <select name="tahun_ajaran" class="form-select">
                        <option value="">Semua Tahun Ajaran</option>
                        @if(isset($tahunAjaranList) && count($tahunAjaranList) > 0)
                            @foreach($tahunAjaranList as $ta)
                                <option value="{{ $ta['id_thn_ajaran'] }}" 
                                    {{ request('tahun_ajaran') == $ta['id_thn_ajaran'] ? 'selected' : '' }}>
                                    {{ $ta['nm_thn_ajaran'] }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="filter-label">Tingkat / Kelas</label>
                    <select name="tingkat" class="form-select">
                        <option value="">Semua Tingkat</option>
                        <option value="1" {{ request('tingkat') == '1' ? 'selected' : '' }}>Tingkat 1</option>
                        <option value="2" {{ request('tingkat') == '2' ? 'selected' : '' }}>Tingkat 2</option>
                        <option value="3" {{ request('tingkat') == '3' ? 'selected' : '' }}>Tingkat 3</option>

                        @if(optional(auth()->user()->prodi)->kode_prodi !== 'NM' && optional(auth()->user()->prodi)->kode_prodi !== 'TI')
                            <option value="4" {{ request('tingkat') == '4' ? 'selected' : '' }}>Tingkat 4</option>
                        @endif
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100" style="padding: 0.6rem;">
                        <i class="bi bi-search me-1"></i>
                         Cari
                    </button>
                </div>

                {{-- <div class="col-md-3">
                    <form method="POST" action="{{ route('gkm.monitoring-rps.clear-cache') }}">
                        @csrf
                        <button type="submit" class="btn btn-warning w-100" style="padding: 0.6rem;">
                            <i class="bi bi-arrow-clockwise"></i> Refresh Data
                        </button>
                    </form>
                </div> --}}

            </div>
        </form>

        @if(session('cache_cleared'))
        <div class="alert-app success mt-3 mb-0" data-auto-dismiss>
            <i class="bi bi-check-circle alert-app-icon"></i>
            <div class="alert-app-body">Cache berhasil dihapus. Data dosen akan dimuat ulang dari database.</div>
        </div>
        @endif

        @if(session('error'))
        <div class="alert-app danger mt-3 mb-0" data-auto-dismiss>
            <i class="bi bi-exclamation-triangle alert-app-icon"></i>
            <div class="alert-app-body">{{ session('error') }}</div>
        </div>
        @endif
    </div>

    <!-- Monitoring Table -->
    
    @if(isset($pagination))

    {{-- <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stats-card h-100" style="border-left: 4px solid #0d6efd;">
                <small class="text-muted">Kepatuhan RPS</small>
                <h2 class="fw-bold text-primary mt-2">{{ data_get($rpsCompliance, 'persentase_kepatuhan', 0) }}%</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card h-100" style="border-left: 4px solid #28a745;">
                <small class="text-muted">Total RPS</small>
                <h2 class="fw-bold text-success mt-2">{{ data_get($rpsCompliance, 'total_records', 0) }}</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card h-100" style="border-left: 4px solid #ffc107;">
                <small class="text-muted">Sudah Upload</small>
                <h2 class="fw-bold text-warning mt-2">{{ data_get($rpsCompliance, 'total_upload', 0) }}</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card h-100" style="border-left: 4px solid #dc3545;">
                <small class="text-muted">Belum Upload</small>
                <h2 class="fw-bold text-danger mt-2">{{ data_get($rpsCompliance, 'jumlah_belum_upload', 0) }}</h2>
            </div>
        </div>
    </div> --}}

    {{-- Field Kedua  --}}
    <div class="monitoring-card">
        <div class="monitoring-header">
            {{-- <i class="bi bi-eye"></i> --}}
            <h6>Monitoring Status</h6>
            <div style="margin-left: auto; display: flex; gap: 0.75rem;">
                <button type="button" 
                        class="btn btn-danger" 
                        style="padding: 0.6rem 1rem;"
                        onclick="window.location.href='{{ route('gkm.monitoring-rps.export', [
                            'semester' => request('semester'),
                            'tahun_ajaran' => request('tahun_ajaran'),
                            'tingkat' => request('tingkat')
                        ]) }}'">
                    <i class="bi bi-file-earmark-pdf text-white"></i> Download PDF
                </button>

                <a href="{{ route('gkm.monitoring-rps.ceklist') }}" class="btn-reminder">
                    <i class="bi bi-send-fill"></i>
                    <span>Kirim Reminder</span>
                </a>



            </div>
        </div>
    {{-- PDF --}}
    {{-- <div class="monitoring-card">
        <div class="col-md-3">
            <button type="button" 
            class="btn btn-danger flex-fill" 
            style="padding: 0.6rem;"
            onclick="window.location.href='{{ route('gkm.monitoring-rps.export', [
            'semester' => request('semester'),
            'tahun_ajaran' => request('tahun_ajaran'),
            'tingkat' => request('tingkat')
            ]) }}'">
            <i class="bi bi-file-earmark-pdf"></i> Download PDF
            </button>
        </div>





        <div class="monitoring-header">
            <h6>Monitoring Status</h6>
            <div style="margin-left: auto;">
                <a href="{{ route('gkm.monitoring-rps.ceklist') }}" class="btn-reminder">
                    <i class="bi bi-send-fill"></i>
                    <span>Kirim Reminder</span>
                </a>
            </div>
        </div> --}}

        <div class="table-responsive">
            <table class="table table-monitoring">
                <thead>
                    <tr>
                        <th>Kode MK</th>
                        <th>Nama Matakuliah</th>
                        <th>Dosen Pengampu</th>
                        <th class="text-center">RPS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pagination as $matkul)
                        <tr>
                            <td>{{ $matkul['kode_mk'] }}</td>
                            <td>{{ $matkul['nama_matkul'] }}</td>
                            <td>{{ $matkul['dosen_pengampu'] }}</td>
                            <td class="text-center">
                                @if(($matkul['status_rps'] ?? '') === 'SUDAH UPLOAD')
                                    <span class="status-icon success">
                                        <i class="bi bi-check-lg"></i>
                                    </span>
                                @elseif(($matkul['status_rps'] ?? '') === 'ERROR')
                                    <span class="status-icon warning">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </span>
                                @else
                                    <span class="status-icon danger">
                                        <i class="bi bi-x-lg"></i>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">Tidak ada data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="alert-app info mt-4">
        <i class="bi bi-info-circle alert-app-icon"></i>
        <div class="alert-app-body">Silakan pilih filter terlebih dahulu</div>
    </div>
    @endif

    <!-- Pagination -->
    @if(isset($pagination) && method_exists($pagination, 'links'))
    <div class="mt-4 d-flex justify-content-center">
        {{ $pagination->links() }}
    </div>
    @endif
</div>

<style>
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
document.getElementById('filterForm').addEventListener('submit', function() {
    const overlay = document.createElement('div');
    overlay.style = `
        position:fixed;top:0;left:0;width:100%;height:100%;
        background:rgba(0,0,0,0.7);display:flex;justify-content:center;align-items:center;z-index:9999;
    `;
    overlay.innerHTML = `
        <div style="background:white;padding:2rem;border-radius:10px;text-align:center;">
            <div class="spinner-border text-primary"></div>
            <p>Loading...</p>
        </div>
    `;
    document.body.appendChild(overlay);
});
</script>
@endsection