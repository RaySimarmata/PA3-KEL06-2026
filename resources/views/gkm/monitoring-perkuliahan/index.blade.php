@extends('layouts.app')

@section('page-title', 'Monitoring Perkuliahan')

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Filter Section -->
        <div class="filter-card mb-4">
            <form method="GET" id="filterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="filter-label">SEMESTER</label>
                        <select name="semester" class="form-select">
                            <option value="">Semua Semester</option>
                            <option value="1" {{ $selectedSemester == '1' ? 'selected' : '' }}>Ganjil</option>
                            <option value="2" {{ $selectedSemester == '2' ? 'selected' : '' }}>Genap</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="filter-label">TAHUN AJARAN</label>
                        <select name="tahun_ajaran" class="form-select">
                            <option value="">Semua Tahun Ajaran</option>
                            <option value="2020" {{ $selectedTahunAjaran == '2020' ? 'selected' : '' }}>2020</option>
                            <option value="2021" {{ $selectedTahunAjaran == '2021' ? 'selected' : '' }}>2021</option>
                            <option value="2022" {{ $selectedTahunAjaran == '2022' ? 'selected' : '' }}>2022</option>
                            <option value="2023" {{ $selectedTahunAjaran == '2023' ? 'selected' : '' }}>2023</option>
                            <option value="2024" {{ $selectedTahunAjaran == '2024' ? 'selected' : '' }}>2024</option>
                            <option value="2025" {{ $selectedTahunAjaran == '2025' ? 'selected' : '' }}>2025</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="filter-label">TINGKAT</label>
                        <select name="tingkat" class="form-select">
    <option value="">Semua Tingkat </option>
    <option value="1" {{ $selectedTingkat == 1 ? 'selected' : '' }}>Tingkat 1</option>
    <option value="2" {{ $selectedTingkat == 2 ? 'selected' : '' }}>Tingkat 2</option>
    <option value="3" {{ $selectedTingkat == 3 ? 'selected' : '' }}>Tingkat 3</option>
    <option value="4" {{ $selectedTingkat == 4 ? 'selected' : '' }}>Tingkat 4</option>
</select>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill" style="padding: 0.6rem;">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <button type="button" class="btn btn-warning flex-fill" style="padding: 0.6rem;"
                                onclick="document.getElementById('refreshForm').submit()">
                                <i class="bi bi-arrow-clockwise"></i> Refresh Data
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Hidden form for refresh -->
            <form id="refreshForm" method="POST" action="{{ route('gkm.monitoring-perkuliahan.clear-cache') }}" style="display: none;">
                @csrf
                <input type="hidden" name="semester" value="{{ $selectedSemester }}">
                <input type="hidden" name="tahun_ajaran" value="{{ $selectedTahunAjaran }}">
            </form>

            <!-- Info message -->
            <div class="alert alert-info mt-3 mb-0"
                style="background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; font-size: 0.875rem;">
                <i class="bi bi-info-circle"></i>
                Silakan pilih Semester dan Tahun Ajaran, kemudian klik tombol Filter untuk menampilkan data monitoring
                Perkuliahan.
            </div>
        </div>

        <!-- Tabs and Monitoring Table -->
        <div class="monitoring-card">
            <!-- Tabs -->
            <div style="padding: 1.5rem 1.5rem 0 1.5rem;">
                <ul class="nav nav-tabs" id="materiTabs" role="tablist" style="border-bottom: 2px solid #e9ecef;">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="teori-tab" data-bs-toggle="tab" data-bs-target="#teori"
                            type="button" role="tab"
                            style="font-weight: 500; color: #6c757d; border: none; padding: 0.75rem 1.5rem;">
                            Materi Teori
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="praktikum-tab" data-bs-toggle="tab" data-bs-target="#praktikum"
                            type="button" role="tab"
                            style="font-weight: 500; color: #6c757d; border: none; padding: 0.75rem 1.5rem;">
                            Materi Praktikum
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="materiTabContent">
                <!-- Materi Teori Tab -->
                <div class="tab-pane fade show active" id="teori" role="tabpanel">
                    <div class="monitoring-header" style="border-top: none;">
                        <i class="bi bi-cloud-upload" style="color: #5B9BD5;"></i>
                        <h6>Monitoring Status</h6>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-monitoring">
                            <thead>
                                <tr>
                                    <th style="width: 8%;">Kode MK</th>
                                    <th style="width: 20%;">Nama Matakuliah</th>
                                    <th style="width: 15%;">Dosen Pengampu</th>
                                    <th colspan="16" class="text-center" style="background: #f8f9fa; font-size: 0.7rem;">
                                        WEEKS (W1 - W16)
                                    </th>
                                </tr>
                                <tr>
                                    <th colspan="3"></th>
                                    @for ($i = 1; $i <= 16; $i++)
                                        <th class="text-center" style="width: 3%; font-size: 0.7rem; padding: 0.5rem;">
                                            W{{ $i }}</th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>
                                @if (!$filterApplied)
                                    <tr>
                                        <td colspan="19" class="text-center py-5">
                                            <div class="empty-state">
                                                <i class="bi bi-funnel"></i>
                                                <p>Silakan pilih Semester dan Tahun Ajaran, kemudian klik Filter</p>
                                            </div>
                                        </td>
                                    </tr>
                                @elseif($noDataFromAPI)
                                    <tr>
                                        <td colspan="19" class="text-center py-5">
                                            <div class="empty-state">
                                                <i class="bi bi-exclamation-circle" style="color: #ffc107;"></i>
                                                <p style="margin-bottom: 0.5rem;">Tidak ada data matakuliah untuk periode ini</p>
                                                <small class="text-muted">
                                                    Semester: {{ $selectedSemester == 1 ? 'Ganjil' : 'Genap' }} | 
                                                    Tahun Ajaran: {{ $selectedTahunAjaran }}
                                                </small>
                                                <br>
                                                <small class="text-muted">Coba pilih semester atau tahun ajaran yang berbeda</small>
                                            </div>
                                        </td>
                                    </tr>
                                @else
                                    @forelse($materiTeori as $mk)
                                        <tr>
                                            <td class="code-mk">{{ $mk['kode'] }}</td>
                                            <td class="nama-mk">{{ $mk['nama'] }}</td>
                                            <td class="dosen-name">{{ $mk['dosen'] }}</td>
                                            @foreach ($mk['weeks'] as $status)
                                                <td class="text-center" style="padding: 0.5rem;">
                                                    @if ($status === 1)
    <span class="status-icon success" title="Tepat waktu">
        <i class="bi bi-check-lg"></i>
    </span>

@elseif ($status === 2)
    <span class="status-icon warning" title="Terlambat upload">
        <i class="bi bi-exclamation-triangle"></i>
    </span>

@elseif ($status === 0)
    <span class="status-icon danger" title="Belum upload">
        <i class="bi bi-x-lg"></i>
    </span>

@else
    <span class="status-icon secondary" title="Belum waktunya atau belum ada data">
        <i class="bi bi-dash-lg"></i>
    </span>
@endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="19" class="text-center py-5">
                                                <div class="empty-state">
                                                    <i class="bi bi-inbox"></i>
                                                    <p>Tidak ada data monitoring</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Materi Praktikum Tab -->
                <div class="tab-pane fade" id="praktikum" role="tabpanel">
                    <div class="monitoring-header" style="border-top: none;">
                        <i class="bi bi-cloud-upload" style="color: #5B9BD5;"></i>
                        <h6>Monitoring Status</h6>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-monitoring">
                            <thead>
                                <tr>
                                    <th style="width: 8%;">Kode MK</th>
                                    <th style="width: 20%;">Nama Matakuliah</th>
                                    <th style="width: 15%;">Dosen Pengampu</th>
                                    <th colspan="16" class="text-center"
                                        style="background: #f8f9fa; font-size: 0.7rem;">
                                        WEEKS (W1 - W16)
                                    </th>
                                </tr>
                                <tr>
                                    <th colspan="3"></th>
                                    @for ($i = 1; $i <= 16; $i++)
                                        <th class="text-center" style="width: 3%; font-size: 0.7rem; padding: 0.5rem;">
                                            W{{ $i }}</th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>
                                @if (!$filterApplied)
                                    <tr>
                                        <td colspan="19" class="text-center py-5">
                                            <div class="empty-state">
                                                <i class="bi bi-funnel"></i>
                                                <p>Silakan pilih Semester dan Tahun Ajaran, kemudian klik Filter</p>
                                            </div>
                                        </td>
                                    </tr>
                                @elseif($noDataFromAPI)
                                    <tr>
                                        <td colspan="19" class="text-center py-5">
                                            <div class="empty-state">
                                                <i class="bi bi-exclamation-circle" style="color: #ffc107;"></i>
                                                <p style="margin-bottom: 0.5rem;">Tidak ada data matakuliah untuk periode ini</p>
                                                <small class="text-muted">
                                                    Semester: {{ $selectedSemester == 1 ? 'Ganjil' : 'Genap' }} | 
                                                    Tahun Ajaran: {{ $selectedTahunAjaran }}
                                                </small>
                                                <br>
                                                <small class="text-muted">Coba pilih semester atau tahun ajaran yang berbeda</small>
                                            </div>
                                        </td>
                                    </tr>
                                @else
                                    @forelse($materiPraktikum as $mk)
                                        <tr>
                                            <td class="code-mk">{{ $mk['kode'] }}</td>
                                            <td class="nama-mk">{{ $mk['nama'] }}</td>
                                            <td class="dosen-name">{{ $mk['dosen'] }}</td>
                                            @foreach ($mk['weeks'] as $status)
                                                <td class="text-center" style="padding: 0.5rem;">
                                                    @if ($status === 1)
    {{-- Green: File sudah upload --}}
    <span class="status-icon success">
        <i class="bi bi-check-lg"></i>
    </span>
@else
    {{-- Red: File belum ada --}}
    <span class="status-icon danger">
        <i class="bi bi-x-lg"></i>
    </span>
@endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="19" class="text-center py-5">
                                                <div class="empty-state">
                                                    <i class="bi bi-inbox"></i>
                                                    <p>Tidak ada data monitoring praktikum</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Tab styling */
        .nav-tabs .nav-link {
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            color: #5B9BD5 !important;
            background-color: #f8f9fa;
        }

        .nav-tabs .nav-link.active {
            color: #5B9BD5 !important;
            background-color: transparent;
            border-bottom: 3px solid #5B9BD5 !important;
            font-weight: 600 !important;
        }

        /* Status icon adjustments for weekly view */
        .status-icon.warning {
            background-color: #fff3cd;
            color: #ffc107;
        }
    </style>
@endsection
