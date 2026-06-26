@extends('layouts.app')

@section('page-title', 'Rekap Laporan')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">Rekap Laporan GKM TRPL Bulanan</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Kepatuhan Upload Materi</span>
                            <strong>80%</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" style="width: 80%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Kepatuhan RPS</span>
                            <strong>85%</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-info" style="width: 85%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Nilai Kuisioner Rata-rata</span>
                            <strong>3.5</strong>
                        </div>
                        <small class="text-muted">dari skala 5.0</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">Grafik Kepatuhan Upload Materi</h5>
                </div>
                <div class="card-body" style="height: 250px;">
                    <canvas id="complianceChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">Grafik Hasil Kuisioner</h5>
                </div>
                <div class="card-body" style="height: 250px;">
                    <canvas id="kuisionerChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">Evaluasi Kepatuhan per Mata Kuliah</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Mata Kuliah</th>
                                    <th>Kepatuhan Upload Materi</th>
                                    <th>Kepatuhan RPS</th>
                                    <th>Nilai Kuisioner</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>Pemrograman Berorientasi Objek</td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" style="width: 95%">95%</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-info" style="width: 100%">100%</div>
                                        </div>
                                    </td>
                                    <td><strong>4.2</strong></td>
                                    <td><span class="badge bg-success">Baik</span></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>Basis Data</td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" style="width: 75%">75%</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-info" style="width: 85%">85%</div>
                                        </div>
                                    </td>
                                    <td><strong>3.5</strong></td>
                                    <td><span class="badge bg-warning">Cukup</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
    const complianceCtx = document.getElementById('complianceChart').getContext('2d');
    const complianceChart = new Chart(complianceCtx, {
        type: 'bar',
        data: {
            labels: ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4', 'Minggu 4'],
            datasets: [{
                label: 'Kepatuhan Upload Materi (%)',
                data: [75, 82, 85, 80, 80],
                backgroundColor: '#1e3c72'
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
            }
        }
    });

    const kuisionerCtx = document.getElementById('kuisionerChart').getContext('2d');
    const kuisionerChart = new Chart(kuisionerCtx, {
        type: 'line',
        data: {
            labels: ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4', 'Minggu 4'],
            datasets: [{
                label: 'Rata-rata Nilai Kuisioner',
                data: [3.2, 3.3, 3.4, 3.5, 3.5],
                borderColor: '#1e3c72',
                backgroundColor: 'rgba(30, 60, 114, 0.1)',
                borderWidth: 2,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    min: 1,
                    max: 5
                }
            }
        }
    });
</script>
@endsection
@endsection
