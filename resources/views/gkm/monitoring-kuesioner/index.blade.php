@extends('layouts.app')

@section('page-title', 'Monitoring Kuesioner')

@section('content')
    <div style="padding: 1.5rem;">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                @foreach($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Filter Section -->
        <div class="filter-card mb-4">
            <form method="GET" id="filterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="filter-label">Periode</label>
                        <select name="periode" class="form-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="">Semua Periode</option>
                            <option value="genap_2025">Genap 2025/2026</option>
                            <option value="ganjil_2025">Ganjil 2025/2026</option>
                            <option value="genap_2024">Genap 2024/2025</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="filter-label">Status</label>
                        <select name="status" class="form-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="">Semua Status</option>
                            <option value="uploaded">Uploaded</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="error">Error</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="filter-label">Program Studi</label>
                        <select name="prodi" class="form-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="">Semua Prodi</option>
                            <option value="trpl">TRPL</option>
                            <option value="si">Sistem Informasi</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100" style="padding: 0.6rem;">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Monitoring Table -->
        <div class="monitoring-card">
            <div class="monitoring-header">
                <i class="bi bi-clipboard-data" style="color: #5B9BD5;"></i>
                <h6>Monitoring Kuesioner Mahasiswa</h6>
                <div style="margin-left: auto;">
                    <a href="{{ route('gkm.monitoring-kuesioner.create') }}" class="btn-reminder">
                        <i class="bi bi-plus-circle"></i>
                        <span>Upload Kuesioner</span>
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-monitoring">
                    <thead>
                        <tr>
                            <th style="width: 3%;">No</th>
                            <th style="width: 12%;">Nama File</th>
                            <th style="width: 8%;">Periode</th>
                            <th style="width: 15%;">Matakuliah</th>
                            <th style="width: 8%;">Kode MK</th>
                            <th style="width: 5%;">Tingkat</th>
                            <th style="width: 12%;">Dosen Pengampu</th>
                            <th style="width: 8%;">Prodi</th>
                            <th style="width: 8%;" class="text-center">Responden</th>
                            <th style="width: 10%;" class="text-center">Status AI</th>
                            <th style="width: 8%;">Tanggal</th>
                            <th style="width: 8%;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kuesioners as $index => $kuesioner)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td class="nama-mk">{{ $kuesioner->nama_file }}</td>
                                <td class="text-secondary">{{ $kuesioner->periode }}</td>
                                <td class="nama-mk">{{ $kuesioner->nama_matakuliah ?? '-' }}</td>
                                <td class="code-mk">{{ $kuesioner->kode_matakuliah ?? '-' }}</td>
                                <td class="text-center">{{ $kuesioner->tingkat ?? '-' }}</td>
                                <td class="dosen-name">{{ $kuesioner->dosen_pengampu ?? '-' }}</td>
                                <td class="text-secondary">{{ $kuesioner->user->prodi->nama_prodi ?? 'Unknown' }}</td>
                                <td class="text-center">
                                    <span class="badge-gkm info">{{ $kuesioner->total_responden }}</span>
                                </td>
                                <td class="text-center">
                                    @switch($kuesioner->status)
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
                                </td>
                                <td class="text-secondary" style="font-size: 0.85rem;">
                                    {{ $kuesioner->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('gkm.monitoring-kuesioner.show', $kuesioner->id) }}" 
                                           class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($kuesioner->status === 'completed')
                                            <a href="{{ route('gkm.monitoring-kuesioner.report', $kuesioner->id) }}" 
                                               class="btn btn-sm btn-outline-success" title="Lihat Laporan">
                                                <i class="bi bi-file-earmark-text"></i>
                                            </a>
                                        @endif
                                        <form action="{{ route('gkm.monitoring-kuesioner.destroy', $kuesioner->id) }}" 
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
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
                                        <p>Belum ada data kuesioner</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    // Auto refresh untuk status processing
    setInterval(function() {
        if (document.querySelector('.badge-gkm.warning')) {
            location.reload();
        }
    }, 30000); // Refresh setiap 30 detik jika ada status processing
    </script>
@endsection