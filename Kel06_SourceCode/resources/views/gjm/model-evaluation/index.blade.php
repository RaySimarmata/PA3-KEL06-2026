@extends('layouts.app')

@section('page-title', 'Evaluasi AI Assistant')

@section('styles')
    <style>
        .metric-card {
            border-radius: 8px;
            padding: 1.5rem;
            height: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #e0e0e0;
            background: white;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .metric-card.primary {
            border-left: 4px solid #5B9BD5;
        }

        .metric-card.success {
            border-left: 4px solid #28a745;
        }

        .metric-card.info {
            border-left: 4px solid #17a2b8;
        }

        .metric-card.warning {
            border-left: 4px solid #ffc107;
        }

        .metric-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .metric-icon.primary { color: #5B9BD5; }
        .metric-icon.success { color: #28a745; }
        .metric-icon.info { color: #17a2b8; }
        .metric-icon.warning { color: #ffc107; }

        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 0.5rem 0;
            color: #333;
        }

        .metric-label {
            font-size: 0.875rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .metric-change {
            font-size: 0.875rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .filter-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }

        .feature-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            margin: 0.25rem;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .feature-badge i {
            font-size: 1rem;
        }

        .feature-badge.triwulan {
            background: #e3f2fd;
            color: #1976d2;
            border-color: #e3f2fd;
        }

        .feature-badge.semester {
            background: #f3e5f5;
            color: #7b1fa2;
            border-color: #f3e5f5;
        }

        .feature-badge.vmts {
            background: #e8f5e9;
            color: #388e3c;
            border-color: #e8f5e9;
        }

        .feature-badge:not(.triwulan):not(.semester):not(.vmts) {
            background: #f5f5f5;
            color: #333;
            border-color: #f5f5f5;
        }

        .feature-badge.active {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .feature-badge.triwulan.active {
            border-color: #1976d2;
            background: #1976d2;
            color: white;
        }

        .feature-badge.semester.active {
            border-color: #7b1fa2;
            background: #7b1fa2;
            color: white;
        }

        .feature-badge.vmts.active {
            border-color: #388e3c;
            background: #388e3c;
            color: white;
        }

        .feature-badge:not(.triwulan):not(.semester):not(.vmts).active {
            border-color: #5B9BD5;
            background: #5B9BD5;
            color: white;
        }

        .feature-badge:hover:not(.active) {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .chart-container {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }

        .performance-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .performance-table th {
            background: #5B9BD5;
            color: white;
            font-weight: 600;
            padding: 1rem;
            border: none;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.5px;
        }

        .performance-table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .info-box {
            background: #E3F2FD;
            border-left: 4px solid #5B9BD5;
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .info-box i {
            color: #5B9BD5;
            margin-right: 0.5rem;
        }

        .period-btn {
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            background: white;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .period-btn.active {
            background: #5B9BD5;
            color: white;
            border-color: #5B9BD5;
            box-shadow: 0 2px 8px rgba(91, 155, 213, 0.3);
        }

        .period-btn:hover:not(.active) {
            border-color: #5B9BD5;
            color: #5B9BD5;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .period-btn i {
            font-size: 1rem;
        }

        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: #5B9BD5;
        }

        .filter-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
@endsection

@section('content')
    <div style="padding: 1.5rem;">
        <!-- Header Card -->
        <div class="filter-card mb-4">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1" style="font-weight: 600; color: #333;">Evaluasi AI Assistant</h5>
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">
                        Monitoring performa AI di Laporan Triwulan, Semester, dan VMTS
                    </p>
                </div>
                <div>
                    <button class="btn btn-success" onclick="downloadReport()">
                        <i class="bi bi-download"></i> Unduh
                    </button>
                </div>
            </div>
        </div>

        <!-- Feature Differences Info Box (shown when VMTS filter is selected) -->
        <div class="info-box" id="vmtsInfoBox" style="display: none; background: #FFF3E0; border-left-color: #ffc107;">
            <i class="bi bi-exclamation-triangle" style="color: #ffc107;"></i>
            <strong>Perbedaan Fitur Laporan VMTS:</strong>
            <ul class="mb-0 mt-2" style="font-size: 0.875rem;">
                <li><strong>Laporan Triwulan & Semester:</strong> User upload template laporan langsung di halaman Generate Laporan, kemudian AI mengisi konten berdasarkan template tersebut.</li>
                <li><strong>Laporan VMTS:</strong> User upload file tahun lalu (PDF/Word) dan Excel hasil kuesioner di AI Assistant, kemudian AI menganalisis kedua file untuk membuat laporan baru. <em>Tidak ada upload template langsung di halaman Generate Laporan.</em></li>
                <li><strong>Cara Kerja VMTS:</strong> AI membaca data survei dari Excel + referensi laporan tahun sebelumnya, lalu menghasilkan analisis dan rekomendasi dalam format laporan lengkap.</li>
            </ul>
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="filter-label mb-3">
                        <i class="bi bi-calendar3"></i> Periode
                    </label>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="period-btn active" data-period="7days" onclick="setPeriod('7days')">
                            <i class="bi bi-calendar-week"></i> 7 Hari
                        </button>
                        <button class="period-btn" data-period="30days" onclick="setPeriod('30days')">
                            <i class="bi bi-calendar-month"></i> 30 Hari
                        </button>
                        <button class="period-btn" data-period="90days" onclick="setPeriod('90days')">
                            <i class="bi bi-calendar-range"></i> 90 Hari
                        </button>
                        <button class="period-btn" data-period="all" onclick="setPeriod('all')">
                            <i class="bi bi-calendar3"></i> Semua
                        </button>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="filter-label mb-3">
                        <i class="bi bi-filter"></i> Fitur
                    </label>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="feature-badge active" data-feature="all" onclick="setFeature('all')" title="Tampilkan semua fitur AI Assistant">
                            <i class="bi bi-grid-3x3-gap"></i> Semua Fitur
                        </span>
                        <span class="feature-badge triwulan" data-feature="triwulan" onclick="setFeature('triwulan')" title="AI Assistant dengan upload template langsung">
                            <i class="bi bi-file-earmark-text"></i> Laporan Triwulan
                        </span>
                        <span class="feature-badge semester" data-feature="semester" onclick="setFeature('semester')" title="AI Assistant dengan upload template langsung">
                            <i class="bi bi-file-earmark-bar-graph"></i> Laporan Semester
                        </span>
                        <span class="feature-badge vmts" data-feature="vmts" onclick="setFeature('vmts')" title="AI Assistant dengan analisis file Excel + referensi tahun lalu">
                            <i class="bi bi-file-earmark-check"></i> Laporan VMTS
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Feature Activity Status --}}
        <div class="row mb-4" id="featureStatusRow" style="display:none;">
            <div class="col-12">
                <div class="chart-container">
                    <h5 class="section-title">
                        <i class="bi bi-activity"></i> Status Aktivitas per Fitur AI
                    </h5>
                    <div class="row" id="featureStatusCards"></div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="text-center py-5">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Memuat data evaluasi...</p>
        </div>

        <!-- Content Container -->
        <div id="contentContainer" style="display: none;">
            <!-- Overview Metrics -->
            <div class="row mb-4" id="overviewMetrics"></div>

            <!-- Hyperparameter Tuning Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="chart-container">
                        <h5 class="section-title">
                            <i class="bi bi-sliders"></i> Hyperparameter Tuning & Optimization
                        </h5>
                        <div class="alert alert-info border-0" style="background: #E3F2FD;">
                            <strong><i class="bi bi-info-circle"></i> Metode Tuning:</strong>
                            <span id="tuningMethod"></span>
                        </div>

                        <div class="row">
                            <!-- Before Tuning -->
                            <div class="col-md-6 mb-3">
                                <div class="card border-0 shadow-sm" style="border-left: 4px solid #dc3545 !important;">
                                    <div class="card-header" style="background: #dc3545; border: none;">
                                        <h6 class="mb-0" style="color: white !important;">
                                            <i class="bi bi-circle" style="color: white !important;"></i> 
                                            Model Sebelum Tuning (Baseline)
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="mb-3" style="color: #333; font-weight: 600;">Parameters:</h6>
                                        <div id="beforeParameters"></div>
                                        <hr>
                                        <h6 class="mb-3" style="color: #333; font-weight: 600;">Metrics:</h6>
                                        <div id="beforeMetrics"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- After Tuning -->
                            <div class="col-md-6 mb-3">
                                <div class="card border-0 shadow-sm" style="border-left: 4px solid #28a745 !important;">
                                    <div class="card-header" style="background: #28a745; border: none;">
                                        <h6 class="mb-0" style="color: white !important;">
                                            <i class="bi bi-check-circle" style="color: white !important;"></i> 
                                            Model Setelah Tuning (Optimized)
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="mb-3" style="color: #333; font-weight: 600;">Parameters:</h6>
                                        <div id="afterParameters"></div>
                                        <hr>
                                        <h6 class="mb-3" style="color: #333; font-weight: 600;">Metrics:</h6>
                                        <div id="afterMetrics"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Improvements --}}
                        <div class="row mt-3" id="improvementsSection">
                            <div class="col-12">
                                <h6 class="mb-3" style="color: #333; font-weight: 600;">
                                    <i class="bi bi-graph-up-arrow"></i> Improvements After Tuning:
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover mb-0">
                                        <thead style="background: #f8f9fa;">
                                            <tr>
                                                <th style="color: #333; font-weight: 600;">Metric</th>
                                                <th style="color: #333; font-weight: 600;">Before</th>
                                                <th style="color: #333; font-weight: 600;">After</th>
                                                <th style="color: #333; font-weight: 600;">Change</th>
                                                <th style="color: #333; font-weight: 600;">% Change</th>
                                                <th style="color: #333; font-weight: 600;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="improvementsTable"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Laporan Stats fallback --}}
                        <div id="laporanStatsSection" style="display:none;" class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-warning border-0">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Data Cache Belum Cukup</strong> — Data di bawah diambil langsung dari laporan
                                    yang sudah dibuat:
                                    <div id="laporanStatsContent" class="mt-2"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Tuning Methods -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="mb-3" style="color: #333; font-weight: 600;">
                                    <i class="bi bi-gear-fill"></i> Metode Tuning yang Diterapkan:
                                </h6>
                                <div id="tuningMethodsDetail"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Critical Analysis & Evaluation -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="chart-container">
                        <h5 class="section-title">
                            <i class="bi bi-clipboard-data"></i> Evaluasi Model & Analisis Kritis
                        </h5>

                        <!-- Summary -->
                        <div class="alert alert-primary border-0" style="background: #E3F2FD;">
                            <h6 style="color: #333; font-weight: 600;">
                                <i class="bi bi-file-text"></i> Ringkasan Perbandingan:
                            </h6>
                            <p id="comparisonSummary" class="mb-0"></p>
                        </div>

                        <div class="row">
                            <!-- Critical Analysis -->
                            <div class="col-md-6 mb-3">
                                <div class="card border-0 shadow-sm" style="border-left: 4px solid #5B9BD5 !important;">
                                    <div class="card-header" style="background: #5B9BD5; color: white; border: none;">
                                        <h6 class="mb-0"><i class="bi bi-search"></i> Analisis Kritis</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul id="criticalAnalysisList" class="mb-0"></ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Strengths -->
                            <div class="col-md-6 mb-3">
                                <div class="card border-0 shadow-sm" style="border-left: 4px solid #28a745 !important;">
                                    <div class="card-header" style="background: #28a745; color: white; border: none;">
                                        <h6 class="mb-0"><i class="bi bi-check-circle"></i> Kelebihan Model</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="strengthsList"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Limitations -->
                            <div class="col-md-6 mb-3">
                                <div class="card border-0 shadow-sm" style="border-left: 4px solid #ffc107 !important;">
                                    <div class="card-header" style="background: #ffc107; color: #333; border: none;">
                                        <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Keterbatasan Model
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="limitationsList"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recommendations -->
                            <div class="col-md-6 mb-3">
                                <div class="card border-0 shadow-sm" style="border-left: 4px solid #17a2b8 !important;">
                                    <div class="card-header" style="background: #17a2b8; color: white; border: none;">
                                        <h6 class="mb-0"><i class="bi bi-lightbulb"></i> Rekomendasi</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="recommendationsList"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance by Feature -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="chart-container">
                        <h5 class="section-title">
                            <i class="bi bi-bar-chart"></i> Performa per Fitur
                        </h5>
                        
                        <!-- VMTS Feature Note -->
                        <div class="alert alert-info border-0" id="vmtsFeatureNote" style="display: none; font-size: 0.875rem; background: #E3F2FD;">
                            <i class="bi bi-info-circle"></i>
                            <strong>Catatan Laporan VMTS:</strong> Berbeda dengan Triwulan & Semester yang menggunakan template upload langsung, 
                            Laporan VMTS menggunakan pendekatan analisis file (Excel kuesioner + PDF/Word referensi tahun lalu) untuk menghasilkan laporan baru.
                        </div>
                        
                        <div class="performance-table">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>FITUR</th>
                                        <th>TOTAL REQUESTS</th>
                                        <th>AVG RESPONSE LENGTH</th>
                                        <th>CACHE EFFICIENCY</th>
                                        <th>PROVIDER</th>
                                        <th>MODEL</th>
                                    </tr>
                                </thead>
                                <tbody id="performanceTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage Timeline Chart -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="chart-container">
                        <h5 class="section-title">
                            <i class="bi bi-graph-up"></i> Timeline Penggunaan
                        </h5>
                        <canvas id="usageChart" height="80"></canvas>
                    </div>
                </div>
            </div>

            <!-- Cache Statistics -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="chart-container">
                        <h5 class="section-title">
                            <i class="bi bi-hdd-stack"></i> Statistik Cache
                        </h5>
                        <div id="cacheStats"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-container">
                        <h5 class="section-title">
                            <i class="bi bi-pie-chart"></i> Distribusi Cache
                        </h5>
                        <div style="max-width: 300px; margin: 0 auto;">
                            <canvas id="cacheChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ambiguity Evaluation Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="chart-container">
                        <h5 class="section-title">
                            <i class="bi bi-question-circle"></i> Evaluasi Ambiguity (Kejelasan Response)
                        </h5>
                        <div id="ambiguitySection"></div>
                    </div>
                </div>
            </div>

            <!-- RAGAS Evaluation Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="chart-container">
                        <h5 class="section-title">
                            <i class="bi bi-diagram-3"></i> RAGAS Evaluation (RAG System Quality)
                        </h5>
                        <div id="ragasSection"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/model-evaluation-ambiguity-ragas.js') }}"></script>
    <script>
        let currentPeriod = '7days';
        let currentFeature = 'all';
        let usageChart = null;
        let cacheChart = null;

        document.addEventListener('DOMContentLoaded', function() {
            loadData();
        });

        function setPeriod(period) {
            currentPeriod = period;
            document.querySelectorAll('.period-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`[data-period="${period}"]`).classList.add('active');
            loadData();
        }

        function setFeature(feature) {
            currentFeature = feature;
            document.querySelectorAll('.feature-badge').forEach(badge => {
                badge.classList.remove('active');
            });
            document.querySelector(`[data-feature="${feature}"]`).classList.add('active');
            
            // Show/hide VMTS info box based on selected feature
            const vmtsInfoBox = document.getElementById('vmtsInfoBox');
            if (feature === 'vmts') {
                vmtsInfoBox.style.display = 'block';
            } else {
                vmtsInfoBox.style.display = 'none';
            }
            
            loadData();
        }

        function loadData() {
            console.log('Loading data...', {
                period: currentPeriod,
                feature: currentFeature
            });
            document.getElementById('loadingState').style.display = 'block';
            document.getElementById('contentContainer').style.display = 'none';

            fetch(`{{ route('gjm.model-evaluation.get-data') }}?period=${currentPeriod}&feature=${currentFeature}`)
                .then(response => {
                    console.log('Response status:', response.status);

                    // Check if redirected to login
                    if (response.redirected && response.url.includes('/login')) {
                        window.location.href = '{{ route('login') }}';
                        return;
                    }

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(result => {
                    console.log('Result:', result);

                    if (result.success) {
                        // Check if data exists
                        if (!result.data) {
                            throw new Error('No data received from server');
                        }

                        renderOverviewMetrics(result.data.overview);
                        renderTuningSection(result.data.tuning);
                        renderEvaluationSection(result.data.comparison);
                        renderPerformanceTable(result.data.performance);
                        renderUsageChart(result.data.timeline);
                        renderCacheStats(result.data.cache);
                        renderCacheChart(result.data.cache);
                        
                        // Render Ambiguity and RAGAS metrics - ALWAYS render, even if no data
                        renderAmbiguityMetrics(result.data.ambiguity_metrics || { has_data: false });
                        renderRAGASMetrics(result.data.ragas_metrics || { has_data: false });

                        document.getElementById('loadingState').style.display = 'none';
                        document.getElementById('contentContainer').style.display = 'block';

                        // Render feature status panel
                        if (result.data.feature_status) {
                            renderFeatureStatus(result.data.feature_status);
                        }
                    } else {
                        throw new Error(result.message || 'Unknown error occurred');
                    }
                })
                .catch(error => {
                    console.error('Error loading data:', error);
                    document.getElementById('loadingState').innerHTML = `
                <div class="alert alert-danger">
                    <h5><i class="bi bi-exclamation-triangle"></i> Gagal Memuat Data</h5>
                    <p>${error.message}</p>
                    <p class="mb-0">
                        <small>Periksa browser console (F12) untuk detail lebih lanjut.</small><br>
                        <button class="btn btn-sm btn-primary mt-2" onclick="loadData()">
                            <i class="bi bi-arrow-clockwise"></i> Coba Lagi
                        </button>
                    </p>
                </div>
            `;
                });
        }

        function renderOverviewMetrics(overview) {
            const container = document.getElementById('overviewMetrics');

            // Check if overview data exists
            if (!overview || overview.total_requests === 0) {
                container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-info border-0" style="background: #E3F2FD;">
                    <h6 style="color: #333; font-weight: 600;"><i class="bi bi-info-circle"></i> Belum Ada Data</h6>
                    <p class="mb-2">Belum ada data AI Assistant yang tercatat. Data akan muncul setelah:</p>
                    <ul class="mb-2">
                        <li>User menggunakan fitur AI Assistant (Laporan Triwulan, Semester, atau VMTS)</li>
                        <li>Response AI di-cache ke database</li>
                    </ul>
                    <p class="mb-0">Silakan gunakan fitur AI Assistant terlebih dahulu untuk melihat evaluasi.</p>
                </div>
            </div>
        `;
                return;
            }

            // Show data source indicator
            let dataSourceBadge = '';
            if (overview.data_source === 'laporan_gjm') {
                dataSourceBadge = `
            <div class="col-12 mb-3">
                <div class="alert alert-warning border-0">
                    <i class="bi bi-database"></i> <strong>Sumber Data:</strong> ${overview.note || 'Data diambil dari laporan yang telah dibuat'}
                </div>
            </div>
        `;
            } else {
                dataSourceBadge = `
            <div class="col-12 mb-3">
                <div class="alert alert-success border-0" style="background: #d4edda;">
                    <i class="bi bi-check-circle"></i> <strong>Sumber Data:</strong> AI Response Cache (Data Real-time)
                </div>
            </div>
        `;
            }

            const metrics = [{
                    label: 'Total Permintaan',
                    value: overview.total_requests.toLocaleString(),
                    icon: 'send',
                    class: 'primary'
                },
                {
                    label: 'Cache Hits',
                    value: overview.cache_hits.toLocaleString(),
                    icon: 'lightning',
                    class: 'success'
                },
                {
                    label: 'Tingkat Cache Hit',
                    value: overview.cache_hit_rate.toFixed(1) + '%',
                    icon: 'percent',
                    class: 'info'
                },
                {
                    label: 'Tingkat Keberhasilan',
                    value: overview.success_rate.toFixed(1) + '%',
                    icon: 'check-circle',
                    class: 'warning'
                }
            ];

            let html = dataSourceBadge;
            metrics.forEach(metric => {
                html += `
            <div class="col-md-3 mb-3">
                <div class="metric-card ${metric.class}">
                    <div class="text-center">
                        <i class="bi bi-${metric.icon} metric-icon ${metric.class}"></i>
                        <div class="metric-label">${metric.label}</div>
                        <div class="metric-value">${metric.value}</div>
                    </div>
                </div>
            </div>
        `;
            });
            container.innerHTML = html;
        }

        function renderTuningSection(tuning) {
            // Check if tuning data exists
            if (!tuning || !tuning.before || !tuning.after) {
                console.error('Invalid tuning data:', tuning);
                return;
            }

            // Tuning method
            let tuningMethodText = tuning.method + ' - ' + tuning.description;
            if (tuning.data_note) {
                tuningMethodText += ' ⚠️ ' + tuning.data_note;
            }
            document.getElementById('tuningMethod').textContent = tuningMethodText;

            // Helper to safely format a metric value
            function fmtVal(v) {
                if (v === null || v === undefined) return 'N/A';
                if (typeof v === 'string') return v;
                if (typeof v === 'number') return v.toLocaleString();
                return String(v);
            }

            // Before parameters
            let beforeParamsHtml = '<ul class="list-unstyled">';
            if (tuning.before.parameters) {
                for (const [key, value] of Object.entries(tuning.before.parameters)) {
                    beforeParamsHtml += `<li><strong>${key}:</strong> <code>${value}</code></li>`;
                }
            }
            beforeParamsHtml += '</ul>';
            document.getElementById('beforeParameters').innerHTML = beforeParamsHtml;

            // Before metrics
            let beforeMetricsHtml = '<ul class="list-unstyled">';
            if (tuning.before.metrics) {
                for (const [key, value] of Object.entries(tuning.before.metrics)) {
                    beforeMetricsHtml += `<li><strong>${formatMetricLabel(key)}:</strong> ${fmtVal(value)}</li>`;
                }
            }
            beforeMetricsHtml += '</ul>';
            document.getElementById('beforeMetrics').innerHTML = beforeMetricsHtml;

            // After parameters
            let afterParamsHtml = '<ul class="list-unstyled">';
            if (tuning.after.parameters) {
                for (const [key, value] of Object.entries(tuning.after.parameters)) {
                    const isChanged = tuning.before.parameters && tuning.before.parameters[key] !== value;
                    afterParamsHtml +=
                        `<li><strong>${key}:</strong> <code class="${isChanged ? 'text-success' : ''}">${value}</code> ${isChanged ? '<i class="bi bi-arrow-up-circle-fill text-success"></i>' : ''}</li>`;
                }
            }
            afterParamsHtml += '</ul>';
            document.getElementById('afterParameters').innerHTML = afterParamsHtml;

            // After metrics
            let afterMetricsHtml = '<ul class="list-unstyled">';
            if (tuning.after.metrics) {
                for (const [key, value] of Object.entries(tuning.after.metrics)) {
                    afterMetricsHtml += `<li><strong>${formatMetricLabel(key)}:</strong> ${fmtVal(value)}</li>`;
                }
            }
            afterMetricsHtml += '</ul>';
            document.getElementById('afterMetrics').innerHTML = afterMetricsHtml;

            // Improvements table — hide if empty
            if (tuning.improvements && Object.keys(tuning.improvements).length > 0) {
                document.getElementById('improvementsSection').style.display = 'block';
                let improvementsHtml = '';
                for (const [key, data] of Object.entries(tuning.improvements)) {
                    const statusClass = data.improved ? 'success' : 'danger';
                    const statusIcon = data.improved ? 'arrow-up' : 'arrow-down';
                    const statusText = data.improved ? 'Improved' : 'Degraded';
                    improvementsHtml += `
                <tr>
                    <td><strong>${formatMetricLabel(key)}</strong></td>
                    <td>${fmtVal(data.before)}</td>
                    <td>${fmtVal(data.after)}</td>
                    <td>${data.change > 0 ? '+' : ''}${fmtVal(data.change)}</td>
                    <td>${data.percent_change > 0 ? '+' : ''}${fmtVal(data.percent_change)}%</td>
                    <td><span class="badge bg-${statusClass}"><i class="bi bi-${statusIcon}"></i> ${statusText}</span></td>
                </tr>
            `;
                }
                document.getElementById('improvementsTable').innerHTML = improvementsHtml;
            } else {
                document.getElementById('improvementsSection').style.display = 'none';
            }

            // Laporan stats fallback section
            if (tuning.laporan_stats) {
                document.getElementById('laporanStatsSection').style.display = 'block';
                const ls = tuning.laporan_stats;
                document.getElementById('laporanStatsContent').innerHTML = `
            <div class="row">
                <div class="col-md-3"><strong>Total Laporan AI:</strong> ${ls.total_laporan}</div>
                <div class="col-md-3"><strong>Laporan Selesai:</strong> ${ls.completed_laporan}</div>
                <div class="col-md-3"><strong>Success Rate:</strong> ${ls.success_rate}%</div>
                <div class="col-md-3"><strong>Rata-rata Panjang Draft:</strong> ${Number(ls.avg_preview_length).toLocaleString()} chars</div>
            </div>
            <p class="mt-2 mb-0 text-muted"><small>Gunakan fitur AI Assistant lebih banyak agar grafik before/after tuning tersedia.</small></p>
        `;
            } else {
                document.getElementById('laporanStatsSection').style.display = 'none';
            }

            // Tuning methods detail
            let methodsHtml = '<ul>';
            if (tuning.tuning_methods) {
                for (const [method, description] of Object.entries(tuning.tuning_methods)) {
                    methodsHtml += `<li><strong>${method}:</strong> ${description}</li>`;
                }
            }
            methodsHtml += '</ul>';
            document.getElementById('tuningMethodsDetail').innerHTML = methodsHtml;
        }

        function renderEvaluationSection(comparison) {
            // Check if comparison data exists
            if (!comparison) {
                console.error('Invalid comparison data:', comparison);
                return;
            }

            // Summary
            document.getElementById('comparisonSummary').textContent = comparison.summary || 'No summary available';

            // Critical analysis
            let analysisHtml = '';
            if (comparison.critical_analysis && Array.isArray(comparison.critical_analysis)) {
                comparison.critical_analysis.forEach(point => {
                    analysisHtml += `<li>${point}</li>`;
                });
            }
            document.getElementById('criticalAnalysisList').innerHTML = analysisHtml || '<li>No analysis available</li>';

            // Strengths - handle as array
            let strengthsHtml = '<ul>';
            if (comparison.strengths) {
                if (Array.isArray(comparison.strengths)) {
                    comparison.strengths.forEach(strength => {
                        strengthsHtml += `<li>${strength}</li>`;
                    });
                } else {
                    // Fallback for object format
                    for (const [title, description] of Object.entries(comparison.strengths)) {
                        strengthsHtml += `<li><strong>${title}:</strong> ${description}</li>`;
                    }
                }
            } else {
                strengthsHtml += '<li>No strengths data available</li>';
            }
            strengthsHtml += '</ul>';
            document.getElementById('strengthsList').innerHTML = strengthsHtml;

            // Limitations - handle as array
            let limitationsHtml = '<ul>';
            if (comparison.limitations) {
                if (Array.isArray(comparison.limitations)) {
                    comparison.limitations.forEach(limitation => {
                        limitationsHtml += `<li>${limitation}</li>`;
                    });
                } else {
                    // Fallback for object format
                    for (const [title, description] of Object.entries(comparison.limitations)) {
                        limitationsHtml += `<li><strong>${title}:</strong> ${description}</li>`;
                    }
                }
            } else {
                limitationsHtml += '<li>No limitations data available</li>';
            }
            limitationsHtml += '</ul>';
            document.getElementById('limitationsList').innerHTML = limitationsHtml;

            // Recommendations - handle as array
            let recommendationsHtml = '<ul>';
            if (comparison.recommendations) {
                if (Array.isArray(comparison.recommendations)) {
                    comparison.recommendations.forEach(recommendation => {
                        recommendationsHtml += `<li>${recommendation}</li>`;
                    });
                } else {
                    // Fallback for object format
                    for (const [title, description] of Object.entries(comparison.recommendations)) {
                        recommendationsHtml += `<li><strong>${title}:</strong> ${description}</li>`;
                    }
                }
            } else {
                recommendationsHtml += '<li>No recommendations available</li>';
            }
            recommendationsHtml += '</ul>';
            document.getElementById('recommendationsList').innerHTML = recommendationsHtml;
        }

        function formatMetricLabel(key) {
            const labels = {
                'avg_response_time': 'Avg Response Time (s)',
                'avg_response_length': 'Avg Response Length (chars)',
                'cache_hit_rate': 'Cache Hit Rate (%)',
                'quality_score': 'Quality Score',
                'total_requests': 'Total Requests'
            };
            return labels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }

        function renderPerformanceTable(performance) {
            const tbody = document.getElementById('performanceTableBody');
            const vmtsFeatureNote = document.getElementById('vmtsFeatureNote');

            // Show VMTS note if VMTS filter is selected or if VMTS data exists
            if (currentFeature === 'vmts' || (performance && performance.vmts)) {
                vmtsFeatureNote.style.display = 'block';
            } else {
                vmtsFeatureNote.style.display = 'none';
            }

            // Check if performance data exists
            if (!performance || Object.keys(performance).length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No performance data available</td></tr>';
                return;
            }

            let html = '';
            for (const [feature, metrics] of Object.entries(performance)) {
                const featureLabel = feature.charAt(0).toUpperCase() + feature.slice(1);
                html += `
            <tr>
                <td><strong>${featureLabel}</strong></td>
                <td>${(metrics.total_requests || 0).toLocaleString()}</td>
                <td>${(metrics.avg_response_length || 0).toLocaleString()} chars</td>
                <td><span class="badge bg-success">${metrics.cache_efficiency || 0}%</span></td>
                <td>${metrics.most_used_provider || 'N/A'}</td>
                <td><code>${metrics.most_used_model || 'N/A'}</code></td>
            </tr>
        `;
            }
            tbody.innerHTML = html;
        }

        function renderUsageChart(timeline) {
            console.log('renderUsageChart called with:', timeline);
            
            const canvas = document.getElementById('usageChart');
            
            if (!canvas) {
                console.error('usageChart canvas not found');
                return;
            }

            // Check if timeline data exists
            if (!timeline || timeline.length === 0) {
                console.warn('No timeline data, showing empty state');
                // Show empty state with better message
                canvas.parentElement.innerHTML = `
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle"></i><br>
                        <strong>Belum Ada Data Timeline</strong><br>
                        <small>Data timeline akan muncul setelah menggunakan fitur AI Assistant.</small>
                    </div>
                `;
                return;
            }

            console.log('Timeline data exists, rendering chart...');

            if (usageChart) {
                usageChart.destroy();
            }

            const ctx = canvas.getContext('2d');

            const labels = timeline.map(t => t.date);
            const newRequests = timeline.map(t => t.new_requests || 0);
            const cacheHits = timeline.map(t => t.cache_hits || 0);

            console.log('Chart data:', { labels, newRequests, cacheHits });

            usageChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Permintaan Baru',
                            data: newRequests,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            tension: 0.4
                        },
                        {
                            label: 'Cache Hits',
                            data: cacheHits,
                            borderColor: '#51CF66',
                            backgroundColor: 'rgba(81, 207, 102, 0.1)',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
            
            console.log('Chart rendered successfully');
        }

        function renderCacheStats(cache) {
            const container = document.getElementById('cacheStats');

            // Check if cache data exists
            if (!cache || cache.total_entries === 0) {
                container.innerHTML = `
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Belum Ada Data Cache</strong><br>
                        <small>Data cache akan muncul setelah menggunakan fitur AI Assistant (Laporan Triwulan, Semester, atau VMTS).</small>
                    </div>
                `;
                return;
            }

            // Calculate values dynamically
            const totalEntries = cache.total_entries || 0;
            const reusedEntries = cache.reused_entries || 0;
            const reuseRate = cache.reuse_rate || 0;
            const cacheSizeMB = cache.cache_size_mb || 0;

            container.innerHTML = `
        <div class="row g-3">
            <div class="col-6">
                <div class="stat-box p-3 rounded" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 text-white-50 small">Total Entri</p>
                            <h3 class="mb-0 text-white fw-bold">${totalEntries.toLocaleString()}</h3>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-database-fill text-white" style="font-size: 2rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-box p-3 rounded" style="background: linear-gradient(135deg, #51CF66 0%, #73E088 100%);">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 text-white-50 small">Entri Digunakan Ulang</p>
                            <h3 class="mb-0 text-white fw-bold">${reusedEntries.toLocaleString()}</h3>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-arrow-repeat text-white" style="font-size: 2rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-box p-3 rounded" style="background: linear-gradient(135deg, #4ECDC4 0%, #6FE7DD 100%);">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 text-white-50 small">Tingkat Penggunaan Ulang</p>
                            <h3 class="mb-0 text-white fw-bold">${reuseRate}%</h3>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-percent text-white" style="font-size: 2rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-box p-3 rounded" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 text-white-50 small">Ukuran Cache</p>
                            <h3 class="mb-0 text-white fw-bold">${cacheSizeMB.toFixed(2)} MB</h3>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-hdd-fill text-white" style="font-size: 2rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
        }

        function renderCacheChart(cache) {
            const canvas = document.getElementById('cacheChart');
            
            if (!canvas) {
                console.error('cacheChart canvas not found');
                return;
            }

            // Check if cache data exists
            if (!cache || !cache.total_entries || cache.total_entries === 0) {
                canvas.parentElement.innerHTML = `
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle"></i><br>
                        <strong>Belum Ada Data Cache</strong><br>
                        <small>Distribusi cache akan muncul setelah menggunakan fitur AI Assistant.</small>
                    </div>
                `;
                return;
            }

            if (cacheChart) {
                cacheChart.destroy();
            }

            const ctx = canvas.getContext('2d');

            // Calculate values dynamically
            const totalEntries = cache.total_entries || 0;
            const reusedEntries = cache.reused_entries || 0;
            const singleUse = totalEntries - reusedEntries;

            cacheChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Digunakan Ulang', 'Sekali Pakai'],
                    datasets: [{
                        data: [reusedEntries, singleUse],
                        backgroundColor: [
                            '#51CF66', // Green for reused
                            '#E0E7FF'  // Light purple for single use
                        ],
                        borderColor: [
                            '#51CF66',
                            '#C7D2FE'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12,
                                    weight: '600'
                                },
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${label}: ${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        function renderFeatureStatus(featureStatus) {
            const labels = {
                triwulan: {
                    name: 'Laporan Triwulan',
                    icon: 'file-earmark-text',
                    color: 'primary'
                },
                semester: {
                    name: 'Laporan Semester',
                    icon: 'file-earmark-bar-graph',
                    color: 'purple'
                },
                vmts: {
                    name: 'Laporan VMTS',
                    icon: 'file-earmark-check',
                    color: 'success'
                },
            };

            // Determine which features to display based on currentFeature filter
            let featuresToDisplay = [];
            if (currentFeature === 'all') {
                featuresToDisplay = ['triwulan', 'semester', 'vmts'];
            } else {
                featuresToDisplay = [currentFeature];
            }

            let html = '';
            for (const [feat, data] of Object.entries(featureStatus)) {
                // Skip if not in display list
                if (!featuresToDisplay.includes(feat)) {
                    continue;
                }

                const meta = labels[feat] || {
                    name: feat,
                    icon: 'file',
                    color: 'secondary'
                };
                const active = data.active;
                const hasCacheData = data.has_cache_data;
                const badgeColor = active ? (hasCacheData ? 'success' : 'warning') : 'secondary';
                const badgeText = active ? (hasCacheData ? 'Aktif (Cache)' : 'Aktif (Laporan)') : 'Belum Digunakan';
                const badgeIcon = active ? (hasCacheData ? 'check-circle-fill' : 'exclamation-circle-fill') : 'x-circle';

                html += `
            <div class="col-md-4 mb-3">
                <div class="card border-${active ? (hasCacheData ? 'success' : 'warning') : 'secondary'} h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-${meta.icon} text-${meta.color}"></i>
                                ${meta.name}
                            </h6>
                            <span class="badge bg-${badgeColor}">
                                <i class="bi bi-${badgeIcon}"></i> ${badgeText}
                            </span>
                        </div>
                        <div class="mt-2">
                            <div class="row g-1">
                                <div class="col-6">
                                    <small class="text-muted">Cache AI</small><br>
                                    <strong>${data.cache_count} entri</strong><br>
                                    <small class="text-muted">${data.last_used}</small>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Total Laporan</small><br>
                                    <strong>${data.laporan_count} laporan</strong><br>
                                    <small class="text-muted">${data.last_laporan}</small>
                                </div>
                            </div>
                        </div>
                        ${ !hasCacheData ? `<div class="mt-2"><small class="text-warning"><i class="bi bi-info-circle"></i> Gunakan fitur AI Assistant agar data cache muncul di evaluasi.</small></div>` : '' }
                    </div>
                </div>
            </div>
        `;
            }

            document.getElementById('featureStatusCards').innerHTML = html;
            document.getElementById('featureStatusRow').style.display = 'block';
        }

        function downloadReport() {
            window.location.href =
                `{{ route('gjm.model-evaluation.download-report') }}?period=${currentPeriod}&feature=${currentFeature}`;
        }
    </script>
@endsection
