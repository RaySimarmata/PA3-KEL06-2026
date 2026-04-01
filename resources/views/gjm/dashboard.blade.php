@extends('layouts.app')

@section('title', 'Dashboard GJM - Fakultas Vokasi')

@section('content')
<div class="container-fluid">
    <!-- Welcome Section -->
    <div class="mb-4">
        <h3>Selamat Datang, {{ $user->nama_user }} 👋</h3>
        <p class="text-muted mb-0">Laporan Bulan: {{ $periode }}</p>
    </div>

    <!-- Overview Cards -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title text-secondary mb-3">Rekap Laporan GKM TRPL Bulanan</h6>
                    <div>
                        <p class="mb-2">Kepatuhan Upload Materi: <strong>80%</strong></p>
                        <p class="mb-2">Kepatuhan RPS: <strong>85%</strong></p>
                        <p class="mb-0">Nilai Kuisioner Rata-rata: <strong>3.5</strong></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title text-secondary mb-3">Temuan Aktif</h6>
                    <p class="mb-0 h4">2</p>
                    <small class="text-muted">Temuan dalam proses closure</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title text-secondary mb-3">Diskusi Kelas:</h6>
                    <ul class="mb-0 small">
                        <li>Rata-rata nilai kuisioner di bawah 3.0</li>
                        <li>Basis Data: Dosen belum melengkapi RPS</li>
                    </ul>
                    <a href="#" class="link-primary text-decoration-none mt-2 d-inline-block">Lihat Detail ></a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title text-secondary mb-3">Grafik Hasil Kuisioner</h6>
                    <div class="mt-3">
                        <small>Distribusi Rating:</small>
                        <ul class="list-unstyled small mt-2">
                            <li><span class="badge bg-success">■</span> Buruk 1.0-2.0: 1</li>
                            <li><span class="badge bg-danger">■</span> Kurang 2.1-3.0: 4</li>
                            <li><span class="badge bg-warning">■</span> Cukup 3.1-3.0: 2</li>
                            <li><span class="badge bg-success">■</span> Baik 4.1-5.0: 5</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik Kepatuhan -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">Grafik Kepatuhan Upload Materi</h6>
                    <canvas id="grafikKepatuhan" height="80"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="card-title mb-3">Rencana Aksi</h6>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <button class="btn btn-sm btn-success w-100">
                            <i class="bi bi-file-earmark-pdf"></i> Generate Laporan Fakultas
                        </button>
                    </div>
                    <button class="btn btn-sm btn-secondary w-100">Export PDF</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Arsip Kuisioner -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h6 class="card-title mb-3">Arsip Laporan Kuisioner</h6>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama File</th>
                            <th>Periode</th>
                            <th>Unduh</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Laporan_Kuisioner_Januari_2026</td>
                            <td>Semester Genap 2025/2026 - Januari 2026</td>
                            <td><button class="btn btn-sm btn-primary">Unduh PDF</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-2">
                <small class="text-muted">Menampilkan Data 1 dari total 1 halaman</small>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('grafikKepatuhan').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4', 'Minggu 4'],
            datasets: [{
                label: 'Kepatuhan %',
                data: [75, 82, 85, 80, 80],
                backgroundColor: '#0d6efd'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            },
            plugins: {
                legend: {
                    display: true
                }
            }
        }
    });
});
</script>
@endsection
