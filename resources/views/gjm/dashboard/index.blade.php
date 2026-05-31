@extends('layouts.app')

@section('page-title', 'Dashboard GJM')

@section('content')

<div style="padding: 1.5rem; background: #f4f6f9; min-height: 100vh;">

    <!-- HEADER -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center flex-wrap">

                <div>

                    <h3 class="fw-bold mb-1">
                        Dashboard Analytics GJM
                    </h3>

                    <p class="text-muted mb-0">

                        Selamat datang,
                        <strong>{{ $user->name }}</strong>

                        •

                        Periode {{ $periode }}

                    </p>

                </div>

                <div class="mt-3 mt-md-0">

                    <form
                        action="{{ route('gjm.analisis') }}"
                        method="POST">

                        @csrf

                        <button
                            type="submit"
                            class="btn btn-primary shadow-sm">

                            <i class="bi bi-cpu me-1"></i>
                            Jalankan Analisis Spark

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

    <!-- FILTER -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body">

            <form method="GET">

                <div class="row g-3 align-items-end">

                    <!-- PRODI -->
                    <div class="col-md-4">

                        <label class="form-label fw-semibold">
                            Program Studi
                        </label>

                        <select
                            name="prodi"
                            class="form-select">

                            <option value="SEMUA">
                                Semua Prodi
                            </option>

                            @foreach($listProdi as $item)

                                <option
                                    value="{{ $item }}"
                                    {{ request('prodi') == $item ? 'selected' : '' }}>

                                    {{ $item }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                    <!-- TAHUN -->
                    <div class="col-md-3">

                        <label class="form-label fw-semibold">
                            Tahun
                        </label>

                        <select
                            name="tahun"
                            class="form-select">

                            <option value="">
                                Semua Tahun
                            </option>

                            @foreach($listTahun as $item)

                                <option
                                    value="{{ $item }}"
                                    {{ request('tahun') == $item ? 'selected' : '' }}>

                                    {{ $item }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                    <!-- SEMESTER -->
                    <div class="col-md-3">

                        <label class="form-label fw-semibold">
                            Semester
                        </label>

                        <select
                            name="semester"
                            class="form-select">

                            <option value="">
                                Semua Semester
                            </option>

                            @foreach($listSemester as $item)

                                <option
                                    value="{{ $item }}"
                                    {{ request('semester') == $item ? 'selected' : '' }}>

                                    {{ $item == 1 ? 'Gasal' : 'Genap' }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                    <!-- BUTTON -->
                    <div class="col-md-2">

                        <button class="btn btn-success w-100">

                            <i class="bi bi-funnel-fill me-1"></i>
                            Filter

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

    <!-- KPI -->
    <div class="row mb-4">

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card border-0 shadow-sm rounded-4 h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <small class="text-muted">
                                Rata-rata Fakultas
                            </small>

                            <h2 class="fw-bold text-primary mt-2">

                                {{ $stats['rata_fakultas'] ?? 0 }}

                            </h2>

                        </div>

                        <div class="icon-box bg-primary-subtle">

                            <i class="bi bi-bar-chart-line text-primary"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card border-0 shadow-sm rounded-4 h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <small class="text-muted">
                                Total Responden
                            </small>

                            <h2 class="fw-bold text-success mt-2">

                                {{ $stats['total_responden'] ?? 0 }}

                            </h2>

                        </div>

                        <div class="icon-box bg-success-subtle">

                            <i class="bi bi-people-fill text-success"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card border-0 shadow-sm rounded-4 h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <small class="text-muted">
                                Kepuasan Mahasiswa
                            </small>

                            <h2 class="fw-bold text-warning mt-2">

                                {{ $stats['kepuasan_mahasiswa'] ?? 0 }}%

                            </h2>

                        </div>

                        <div class="icon-box bg-warning-subtle">

                            <i class="bi bi-emoji-smile text-warning"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card border-0 shadow-sm rounded-4 h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <small class="text-muted">
                                Matkul Bermasalah
                            </small>

                            <h2 class="fw-bold text-danger mt-2">

                                {{ $stats['matkul_bermasalah'] ?? 0 }}

                            </h2>

                        </div>

                        <div class="icon-box bg-danger-subtle">

                            <i class="bi bi-exclamation-triangle text-danger"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- CHART -->
    <div class="row mb-4">

        <!-- PERFORMA PRODI -->
        <div class="col-lg-8 mb-3">

            <div class="card border-0 shadow-sm rounded-4 h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-4">

                        <h5 class="fw-semibold mb-0">
                            Performa Program Studi
                        </h5>

                        <span class="badge bg-primary">
                            Analytics
                        </span>

                    </div>

                    <canvas id="chartProdi" height="110"></canvas>

                </div>

            </div>

        </div>

        <!-- DISTRIBUSI -->
        <div class="col-lg-4 mb-3">

            <div class="card border-0 shadow-sm rounded-4 h-100">

                <div class="card-body">

                    <h5 class="fw-semibold mb-4">
                        Distribusi Penilaian
                    </h5>

                    <canvas id="chartDistribusi"></canvas>

                </div>

            </div>

        </div>

    </div>

    <!-- TREND -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h5 class="fw-semibold mb-0">
                    Trend Semester
                </h5>

                <span class="badge bg-success">
                    Trend Analytics
                </span>

            </div>

            <canvas id="trendSemesterChart" height="80"></canvas>

        </div>

    </div>

    <!-- HEATMAP DOSEN -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h5 class="fw-semibold mb-0">
                    Heatmap Performa Dosen
                </h5>

                <span class="badge bg-dark">

                    {{ count($heatmapDosen) }} Dosen

                </span>

            </div>

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead class="table-light">

                        <tr>

                            <th>Dosen</th>

                            @foreach($listProdi as $prodi)

                                <th class="text-center">
                                    {{ $prodi }}
                                </th>

                            @endforeach

                        </tr>

                    </thead>

                    <tbody>

                        @foreach($heatmapDosen as $dosen => $prodis)

                            <tr>

                                <td class="fw-semibold">
                                    {{ $dosen }}
                                </td>

                                @foreach($listProdi as $prodi)

                                    @php

                                        $nilai =
                                            $prodis[$prodi] ?? null;

                                        $bg =
                                            $nilai >= 3.25
                                            ? '#198754'
                                            : (
                                                $nilai >= 2.75
                                                ? '#ffc107'
                                                : (
                                                    $nilai
                                                    ? '#dc3545'
                                                    : '#f8f9fa'
                                                )
                                            );

                                    @endphp

                                    <td
                                        class="text-center fw-bold"
                                        style="
                                            background: {{ $bg }};
                                            color:
                                                {{ $nilai ? 'white' : '#999' }};
                                        ">

                                        {{ $nilai ?? '-' }}

                                    </td>

                                @endforeach

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <!-- TOP & BOTTOM -->
    <div class="row mb-4">

        <!-- TOP DOSEN -->
        <div class="col-lg-6 mb-3">

            <div class="card border-0 shadow-sm rounded-4 h-100">

                <div class="card-body">

                    <h5 class="fw-semibold mb-4">
                        Top Dosen
                    </h5>

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead class="table-light">

                                <tr>

                                    <th>Dosen</th>
                                    <th>Avg</th>
                                    <th>MK</th>
                                    <th>Responden</th>

                                </tr>

                            </thead>

                            <tbody>

                                @foreach($topDosen as $item)

                                    <tr>

                                        <td class="fw-semibold">

                                            {{ $item['nama'] }}

                                        </td>

                                        <td>

                                            {{ $item['avg'] }}

                                        </td>

                                        <td>

                                            {{ $item['jumlah_matkul'] }}

                                        </td>

                                        <td>

                                            {{ $item['jumlah_responden'] }}

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

        <!-- BOTTOM DOSEN -->
        <div class="col-lg-6 mb-3">

            <div class="card border-0 shadow-sm rounded-4 h-100">

                <div class="card-body">

                    <h5 class="fw-semibold mb-4 text-danger">
                        Dosen Perlu Evaluasi
                    </h5>

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead class="table-light">

                                <tr>

                                    <th>Dosen</th>
                                    <th>Avg</th>
                                    <th>Kepuasan</th>
                                    <th>Status</th>

                                </tr>

                            </thead>

                            <tbody>

                                @foreach($dosenBermasalah as $item)

                                    <tr>

                                        <td class="fw-semibold">

                                            {{ $item['nama'] }}

                                        </td>

                                        <td>

                                            {{ $item['avg'] }}

                                        </td>

                                        <td>

                                            {{ $item['kepuasan'] }}%

                                        </td>

                                        <td>

                                            <span class="badge bg-danger">

                                                Evaluasi

                                            </span>

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- PERTANYAAN TERBURUK -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h5 class="fw-semibold mb-0">
                    Pertanyaan dengan Nilai Terendah
                </h5>

                <span class="badge bg-danger">

                    Critical Insight

                </span>

            </div>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead class="table-light">

                        <tr>

                            <th>Pertanyaan</th>
                            <th width="120">Rata-rata</th>

                        </tr>

                    </thead>

                    <tbody>

                        @foreach($pertanyaanTerburuk as $item)

                            <tr>

                                <td>

                                    {{ $item['pertanyaan'] }}

                                </td>

                                <td>

                                    <span class="badge bg-danger">

                                        {{ $item['avg'] }}

                                    </span>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <!-- INSIGHT -->
    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body">

            <h5 class="fw-semibold mb-4">
                Insight Otomatis
            </h5>

            @foreach($insight as $item)

                <div class="alert alert-warning border-0 shadow-sm">

                    <i class="bi bi-lightbulb me-2"></i>

                    {{ $item }}

                </div>

            @endforeach

        </div>

    </div>

</div>

<style>

.icon-box{
    width:70px;
    height:70px;
    border-radius:20px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:28px;
}

</style>

<script>

document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | CHART PRODI
    |--------------------------------------------------------------------------
    */
    new Chart(
        document.getElementById('chartProdi'),
        {

            type: 'bar',

            data: {

                labels:
                    @json($chartProdiLabel),

                datasets: [{

                    label: 'Rata-rata',

                    data:
                        @json($chartProdiData),

                    borderRadius: 10,

                    borderWidth: 1

                }]
            },

            options: {

                responsive: true,

                scales: {

                    y: {

                        beginAtZero: true,
                        max: 4
                    }
                }
            }
        }
    );

    /*
    |--------------------------------------------------------------------------
    | DISTRIBUSI
    |--------------------------------------------------------------------------
    */
    new Chart(
        document.getElementById('chartDistribusi'),
        {

            type: 'doughnut',

            data: {

                labels:
                    @json(array_keys($kategoriDistribusi)),

                datasets: [{

                    data:
                        @json(array_values($kategoriDistribusi))
                }]
            }
        }
    );

    /*
    |--------------------------------------------------------------------------
    | TREND
    |--------------------------------------------------------------------------
    */
    new Chart(
        document.getElementById('trendSemesterChart'),
        {

            type: 'line',

            data: {

                labels:
                    @json($trendSemester->keys()),

                datasets: [{

                    label: 'Trend Kepuasan',

                    data:
                        @json($trendSemester->values()),

                    tension: 0.4,

                    fill: false
                }]
            },

            options: {

                responsive: true,

                scales: {

                    y: {

                        beginAtZero: true,
                        max: 4
                    }
                }
            }
        }
    );

});

</script>

@endsection