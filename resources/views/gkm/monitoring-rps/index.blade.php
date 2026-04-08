@extends('layouts.app')

@section('page-title', 'Monitoring RPS')

@section('content')
<div style="padding: 1.5rem;">
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
                        @else
                            <option value="2020" {{ request('tahun_ajaran') == '2020' ? 'selected' : '' }}>2020</option>
                            <option value="2021" {{ request('tahun_ajaran') == '2021' ? 'selected' : '' }}>2021</option>
                            <option value="2022" {{ request('tahun_ajaran') == '2022' ? 'selected' : '' }}>2022</option>
                            <option value="2023" {{ request('tahun_ajaran') == '2023' ? 'selected' : '' }}>2023</option>
                            <option value="2024" {{ request('tahun_ajaran') == '2024' ? 'selected' : '' }}>2024</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100" style="padding: 0.6rem;">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
                <div class="col-md-3">
                    <form method="POST" action="{{ route('gkm.monitoring-rps.clear-cache') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-warning w-100" style="padding: 0.6rem;" 
                                onclick="return confirm('Refresh data dari API? Proses ini membutuhkan waktu.')">
                            <i class="bi bi-arrow-clockwise"></i> Refresh Data
                        </button>
                    </form>
                </div>
            </div>
        </form>
        
        @if(session('cache_cleared'))
        <div class="alert alert-success mt-3 mb-0">
            <i class="bi bi-check-circle"></i> Cache berhasil dihapus. Data telah diperbarui dari API.
        </div>
        @endif
        
        @if(session('error'))
        <div class="alert alert-danger mt-3 mb-0">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
        </div>
        @endif
        
        @if(request()->has('semester') && request()->has('tahun_ajaran') && request('semester') != '' && request('tahun_ajaran') != '')
            @if(isset($noDataFromAPI) && $noDataFromAPI)
            <div class="alert alert-warning mt-3 mb-0">
                <i class="bi bi-exclamation-circle"></i> 
                <strong>Tidak ada data matakuliah untuk filter yang dipilih.</strong>
                <br>
                <small>
                    Semester: <strong>{{ request('semester') == '1' ? 'Ganjil' : 'Genap' }}</strong>, 
                    Tahun Ajaran: <strong>{{ request('tahun_ajaran') }}</strong>
                </small>
                <br>
                <small class="text-muted">
                    Kemungkinan penyebab: Data belum tersedia di API untuk kombinasi filter ini. 
                    Silakan coba filter lain atau hubungi administrator jika masalah berlanjut.
                </small>
            </div>
            @endif
            
            @php
                $prodiKode = auth()->user()->prodi ? auth()->user()->prodi->kode_prodi : 'TRPL';
                $prodiIdMap = ['TRPL' => 4, 'TI' => 1, 'NM' => 3];
                $prodiId = $prodiIdMap[$prodiKode] ?? 4;
                $cacheKey = "monitoring_rps_{$prodiId}_" . request('semester', '1') . "_" . request('tahun_ajaran', '2020');
                $cacheExists = Cache::has($cacheKey);
                $cacheExpiry = $cacheExists ? Cache::get($cacheKey . '_time', now()) : null;
            @endphp
            
            <div class="alert alert-info mt-3 mb-0">
                <i class="bi bi-info-circle"></i> 
                @if($cacheExists)
                    Data di-cache dan akan diperbarui otomatis dalam 30 menit. Gunakan tombol "Refresh Data" untuk memperbarui sekarang.
                @else
                    Data akan di-cache selama 30 menit setelah dimuat. Gunakan tombol "Refresh Data" untuk memperbarui dari API.
                @endif
            </div>
        @endif
    </div>

    <!-- Monitoring Table - Only show after filter is applied -->
    @if(request()->has('semester') && request()->has('tahun_ajaran') && request('semester') != '' && request('tahun_ajaran') != '')
    <div class="monitoring-card">
        <div class="monitoring-header">
            <i class="bi bi-eye"></i>
            <h6>Monitoring Status</h6>
            <div style="margin-left: auto;">
                <a href="{{ route('gkm.monitoring-rps.ceklist') }}" class="btn-reminder">
                    <i class="bi bi-send-fill"></i>
                    <span>Kirim Reminder</span>
                </a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-monitoring">
                <thead>
                    <tr>
                        <th style="width: 15%;">Kode MK</th>
                        <th style="width: 35%;">Nama Matakuliah</th>
                        <th style="width: 35%;">Dosen Pengampu</th>
                        <th style="width: 15%;" class="text-center">RPS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pagination as $matkul)
                        <tr>
                            <td class="code-mk">{{ $matkul['kode_mk'] }}</td>
                            <td class="nama-mk">{{ $matkul['nama_matkul'] }}</td>
                            <td class="dosen-name">{{ $matkul['dosen_pengampu'] }}</td>
                            <td class="text-center">
                                @if(isset($matkul['status_rps']))
                                    @if($matkul['status_rps'] === 'SUDAH UPLOAD')
                                        <span class="status-icon success" title="RPS Sudah Upload">
                                            <i class="bi bi-check-lg"></i>
                                        </span>
                                    @elseif($matkul['status_rps'] === 'ERROR')
                                        <span class="status-icon warning" title="Error mengambil data">
                                            <i class="bi bi-exclamation-triangle"></i>
                                        </span>
                                    @else
                                        <span class="status-icon danger" title="RPS Belum Upload">
                                            <i class="bi bi-x-lg"></i>
                                        </span>
                                    @endif
                                @else
                                    <span class="status-icon danger" title="Status tidak tersedia">
                                        <i class="bi bi-question-lg"></i>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                    <p class="mt-2 mb-0">Tidak ada data monitoring</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
    <!-- Message when no filter is applied -->
    <div class="alert alert-info mt-4">
        <i class="bi bi-info-circle"></i> 
        Silakan pilih Semester dan Tahun Ajaran, kemudian klik tombol Filter untuk menampilkan data monitoring RPS.
    </div>
    @endif

    <!-- Pagination -->
    @if(isset($pagination) && $pagination->hasPages())
    <div class="mt-4 d-flex justify-content-center">
        {{ $pagination->links() }}
    </div>
    @endif
</div>

<script>
// Show loading overlay when filter form is submitted
document.getElementById('filterForm').addEventListener('submit', function() {
    showLoadingOverlay('Memuat data dari API...');
});

// Show loading overlay when refresh button is clicked
document.querySelectorAll('form[action*="clear-cache"]').forEach(form => {
    form.addEventListener('submit', function() {
        showLoadingOverlay('Memperbarui data dari API... Mohon tunggu, proses ini membutuhkan waktu.');
    });
});

function showLoadingOverlay(message) {
    const overlay = document.createElement('div');
    overlay.id = 'loadingOverlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    `;
    
    overlay.innerHTML = `
        <div style="background: white; padding: 2rem; border-radius: 10px; text-align: center; max-width: 400px;">
            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h5 class="mb-2">Mohon Tunggu</h5>
            <p class="text-muted mb-0">${message}</p>
        </div>
    `;
    
    document.body.appendChild(overlay);
}
</script>
@endsection
