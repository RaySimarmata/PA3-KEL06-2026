@extends('layouts.app')

@section('title', 'Evaluasi RAG - RAGAS Framework')

@section('styles')
    <style>
        /* GJM Dashboard Styles */
        .metric-box {
            border-radius: 8px;
            padding: 1.25rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            height: 100%;
            transition: transform 0.2s;
        }

        .metric-box:hover {
            transform: translateY(-3px);
        }

        .metric-box.faithfulness {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .metric-box.hallucination {
            background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
        }

        .metric-box.precision {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .metric-box.recall {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .metric-box.f1 {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .metric-box.overall {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }

        .metric-label {
            font-size: 0.875rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .badge-metric {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .badge-excellent {
            background: #d4edda;
            color: #155724;
        }

        .badge-good {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-fair {
            background: #fff3cd;
            color: #856404;
        }

        .badge-poor {
            background: #f8d7da;
            color: #721c24;
        }

        .dataset-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            border-left: 4px solid #1e3c72;
            margin-bottom: 0.75rem;
            transition: all 0.2s;
        }

        .dataset-item:hover {
            transform: translateX(3px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .dataset-item .number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e3c72;
        }

        .dataset-item .label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .dataset-item .description {
            font-size: 0.875rem;
            color: #6c757d;
        }

        .chart-wrapper {
            height: 350px;
            position: relative;
        }

        .info-banner {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }

        .info-banner i {
            font-size: 1.25rem;
            margin-right: 0.5rem;
        }

        .info-banner.warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-left: 4px solid #ff9800;
        }

        .info-banner.success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-left: 4px solid #28a745;
        }

        .progress-custom {
            background-color: #e9ecef;
            border-radius: 4px;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <!-- Welcome Section - GJM Style -->
        <div class="mb-4">
            <h3>Selamat Datang, {{ Auth::user()->name ?? 'Admin' }}</h3>
            <p class="text-muted mb-0">Evaluasi RAG - RAGAS Framework | {{ date('F Y') }}</p>
        </div>

        <!-- Page Header with Action Buttons -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Evaluasi RAG - RAGAS Framework</h5>
                            <p class="text-muted mb-0 small">Pengujian komprehensif kualitas Retrieval-Augmented Generation
                                pada sistem AI Assistant</p>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-success me-2" onclick="syncFromCache()">
                                <i class="bi bi-arrow-repeat"></i> Sinkronisasi Data
                            </button>
                            <button class="btn btn-sm btn-primary" onclick="downloadReport()">
                                <i class="bi bi-file-earmark-pdf"></i> Generate Laporan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Banner -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="info-banner" id="infoBanner">
                    <i class="bi bi-info-circle-fill"></i>
                    <strong>Tentang RAGAS Framework:</strong>
                    Framework evaluasi open-source yang menyediakan serangkaian metrik terstandar untuk mengukur performa
                    sistem RAG secara menyeluruh, meliputi kualitas proses retrieval dan generation.
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="text-center py-5">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Memuat data evaluasi RAGAS...</p>
        </div>

        <!-- Content Container -->
        <div id="contentContainer" style="display: none;">
            <!-- Summary Metrics Cards - GJM Style -->
            <div class="row mb-4" id="summaryMetrics"></div>

            <!-- Charts Row -->
            <div class="row mb-4">
                <!-- Category Distribution (Pie Chart) -->
                <div class="col-md-6 col-lg-6 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-secondary mb-3">
                                Distribusi Kategori Pengujian
                            </h6>
                            <div class="chart-wrapper">
                                <canvas id="categoryPieChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Metrics Comparison (Bar Chart) -->
                <div class="col-md-6 col-lg-6 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-secondary mb-3">
                                Perbandingan Metrik RAGAS
                            </h6>
                            <div class="chart-wrapper">
                                <canvas id="metricsBarChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Metrics Row - GJM Style Overview Cards -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-secondary mb-3">Rekap Evaluasi RAGAS</h6>
                            <div id="rekapEvaluasi">
                                <p class="mb-2">Total Skenario: <strong id="totalScenarios">0</strong></p>
                                <p class="mb-2">Rata-rata Faithfulness: <strong id="avgFaithfulness">0%</strong></p>
                                <p class="mb-0">Rata-rata F1 Score: <strong id="avgF1Score">0%</strong></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-secondary mb-3">Temuan Aktif</h6>
                            <p class="mb-0 h4" id="activeFindings">0</p>
                            <small class="text-muted">Temuan dalam proses closure</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-secondary mb-3">Diskusi & Catatan:</h6>
                            <ul class="mb-0 small" id="notesList">
                                <li>Load data evaluasi...</li>
                            </ul>
                            <a href="#" class="link-primary text-decoration-none mt-2 d-inline-block">Lihat Detail
                                ></a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-secondary mb-3">Distribusi Rating</h6>
                            <div class="mt-3" id="ratingDistribution">
                                <small>Loading...</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dataset Composition -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="card-title text-secondary mb-3">
                        <i class="bi bi-database text-primary"></i>
                        Komposisi Dataset Knowledge Base
                    </h6>
                    <p class="text-muted small mb-3">Dataset terdiri dari dokumen nyata yang diperoleh melalui integrasi API
                        CIS, unggahan file institusi, serta data historis sistem.</p>
                    <div id="datasetComposition"></div>
                </div>
            </div>

            <!-- Detailed Results Table -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="card-title text-secondary mb-3">
                        Hasil Evaluasi Per Skenario Pengujian
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>NO</th>
                                    <th>PERTANYAAN / SKENARIO</th>
                                    <th>KATEGORI</th>
                                    <th>FAITH.</th>
                                    <th>HALLU.</th>
                                    <th>PREC.</th>
                                    <th>RECALL</th>
                                    <th>F1</th>
                                </tr>
                            </thead>
                            <tbody id="scenariosTable"></tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted" id="tableInfo">Menampilkan Data dari total halaman</small>
                    </div>
                </div>
            </div>

            <!-- Metrics Explanation -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="card-title text-secondary mb-3">
                        <i class="bi bi-book text-primary"></i>
                        Penjelasan Metrik RAGAS
                    </h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="dataset-item">
                                <div class="label"><i class="bi bi-check-circle"></i> Faithfulness</div>
                                <div class="description">
                                    Mengukur kesesuaian jawaban dengan dokumen sumber. Setiap pernyataan dalam jawaban harus
                                    dapat ditelusuri ke dokumen konteks.
                                    <br><strong>Formula:</strong> |Pernyataan Didukung| / |Total Pernyataan|
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="dataset-item">
                                <div class="label"><i class="bi bi-exclamation-triangle"></i> Hallucination Rate</div>
                                <div class="description">
                                    Mengukur tingkat informasi yang dibuat model tanpa dasar dari dokumen. Semakin rendah
                                    semakin baik.
                                    <br><strong>Formula:</strong> 1 - Faithfulness
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="dataset-item">
                                <div class="label"><i class="bi bi-bullseye"></i> Context Precision</div>
                                <div class="description">
                                    Mengukur ketepatan konteks yang diambil. Seberapa banyak konteks yang benar-benar
                                    diperlukan untuk menjawab pertanyaan.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="dataset-item">
                                <div class="label"><i class="bi bi-search"></i> Context Recall</div>
                                <div class="description">
                                    Mengukur kemampuan sistem menemukan seluruh informasi relevan dari knowledge base. Tidak
                                    ada informasi penting yang terlewat.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="dataset-item">
                                <div class="label"><i class="bi bi-calculator"></i> F1 Score</div>
                                <div class="description">
                                    Mengukur keseimbangan antara Precision dan Recall. Sistem yang baik harus tepat (high
                                    precision) sekaligus lengkap (high recall).
                                    <br><strong>Formula:</strong> 2 × (Precision × Recall) / (Precision + Recall)
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="dataset-item">
                                <div class="label"><i class="bi bi-star"></i> RAGAS Score</div>
                                <div class="description">
                                    Skor keseluruhan yang menggabungkan semua metrik RAGAS untuk memberikan penilaian
                                    komprehensif terhadap kualitas sistem RAG.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analisis Hasil -->
            <div class="card border-0 shadow-sm mb-4" id="analysisSection">
                <div class="card-body">
                    <h6 class="card-title text-secondary mb-3">
                        <i class="bi bi-graph-up-arrow text-primary"></i>
                        Analisis Hasil Evaluasi
                    </h6>
                    <div class="alert alert-success border-0 mb-3">
                        <h6 class="mb-2"><i class="bi bi-check-circle"></i> Kesimpulan Utama:</h6>
                        <p class="mb-0 small" id="analysisConclusion">
                            <!-- Dynamic content will be inserted here -->
                        </p>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card border-success">
                                <div class="card-body">
                                    <h6 class="mb-3 text-success">
                                        <i class="bi bi-check-circle"></i> Kelebihan Sistem
                                    </h6>
                                    <ul class="small mb-0" id="analysisStrengths">
                                        <!-- Dynamic content will be inserted here -->
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card border-warning">
                                <div class="card-body">
                                    <h6 class="mb-3 text-warning">
                                        <i class="bi bi-exclamation-triangle"></i> Area Pengembangan
                                    </h6>
                                    <ul class="small mb-0" id="analysisLimitations">
                                        <!-- Dynamic content will be inserted here -->
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-info mt-3">
                        <div class="card-body">
                            <h6 class="mb-3 text-info">
                                <i class="bi bi-lightbulb"></i> Rekomendasi Peningkatan
                            </h6>
                            <ul class="small mb-0" id="analysisRecommendations">
                                <!-- Dynamic content will be inserted here -->
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Arsip Laporan - GJM Style -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title text-secondary mb-3">Arsip Laporan Evaluasi</h6>
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
                            <tbody id="reportArchive">
                                <tr>
                                    <td>1</td>
                                    <td>Laporan_Evaluasi_RAGAS_{{ date('Y_m_d') }}</td>
                                    <td>{{ date('F Y') }}</td>
                                    <td><button class="btn btn-sm btn-primary" onclick="downloadReport()">Unduh
                                            PDF</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted" id="archiveInfo">Menampilkan Data 1 dari total 1 laporan</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <script>
        let categoryPieChart = null;
        let metricsBarChart = null;
        // Register DataLabels plugin (plugin also auto-registers when loaded,
        // but explicit registration ensures availability in older setups)
        if (typeof Chart !== 'undefined' && typeof ChartDataLabels !== 'undefined') {
            try {
                Chart.register(ChartDataLabels);
            } catch (e) {
                /* already registered */
            }
        }

        // Helper: pick contrasting color (black/white) for label over slice
        function contrastColor(hex) {
            if (!hex) return '#000';
            const h = hex.replace('#', '');
            const r = parseInt(h.substring(0, 2), 16);
            const g = parseInt(h.substring(2, 4), 16);
            const b = parseInt(h.substring(4, 6), 16);
            const yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
            return (yiq >= 128) ? '#000' : '#fff';
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadRAGASData();
        });

        function loadRAGASData() {
            const loadingState = document.getElementById('loadingState');
            const contentContainer = document.getElementById('contentContainer');

            if (loadingState) loadingState.style.display = 'block';
            if (contentContainer) contentContainer.style.display = 'none';

            console.log('Fetching RAGAS data from:', '{{ route('gjm.evaluasi.ragas.get-data') }}');

            fetch('{{ route('gjm.evaluasi.ragas.get-data') }}', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(result => {
                    console.log('Result:', result);
                    if (result.success) {
                        try {
                            // Show data source info
                            const infoBanner = document.getElementById('infoBanner');
                            if (infoBanner) {
                                if (result.data.is_seeder_data) {
                                    infoBanner.className = 'info-banner warning';
                                    infoBanner.innerHTML = `
                                        <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                                        <strong>PERINGATAN:</strong> ${result.data.message}
                                        <br><small>Data evaluasi saat ini menggunakan data seeder untuk demonstrasi. Upload template laporan dan buat laporan untuk mengisi knowledge base dengan data real.</small>
                                    `;
                                } else {
                                    infoBanner.className = 'info-banner success';
                                    infoBanner.innerHTML = `
                                        <i class="bi bi-database-check text-success"></i>
                                        <strong>Data Real dari AI Cache:</strong> ${result.data.message}
                                        <br><small>Data evaluasi diambil dari cache MongoDB berdasarkan penggunaan AI Assistant oleh user.
                                        <a href="#" onclick="syncFromCache(); return false;" class="text-primary"><u>Refresh data</u></a> untuk sinkronisasi terbaru.</small>
                                    `;
                                }
                            }

                            renderSummaryMetrics(result.data.summary);
                            renderAdditionalMetrics(result.data);
                            renderDatasetComposition(result.data.dataset);
                            renderScenariosTable(result.data.scenarios, result.data.summary?.ragas_score);
                            renderCategoryDistribution(result.data.category_distribution);
                            renderMetricsComparison(result.data.metrics_comparison);
                            renderAnalysis(result.data.analysis || null);

                            if (loadingState) loadingState.style.display = 'none';
                            if (contentContainer) contentContainer.style.display = 'block';
                        } catch (renderError) {
                            console.error('Render error:', renderError);
                            if (loadingState) {
                                loadingState.innerHTML =
                                    '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Error saat merender data: ' +
                                    renderError.message + '</div>';
                            }
                        }
                    } else {
                        if (loadingState) {
                            loadingState.innerHTML = '<div class="alert alert-danger">Error: ' + result.message +
                                '</div>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const loadingState = document.getElementById('loadingState');
                    if (loadingState) {
                        loadingState.innerHTML =
                            '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Terjadi kesalahan: ' +
                            error.message +
                            '<br><small>Silakan refresh halaman atau hubungi administrator.</small></div>';
                    }
                });
        }

        function renderSummaryMetrics(summary) {
            const container = document.getElementById('summaryMetrics');
            if (!container) return;

            const metrics = [{
                    label: 'Faithfulness',
                    value: summary.faithfulness,
                    class: 'faithfulness',
                    icon: 'check-circle'
                },
                {
                    label: 'Hallucination',
                    value: summary.hallucination_rate,
                    class: 'hallucination',
                    icon: 'exclamation-triangle'
                },
                {
                    label: 'Context Precision',
                    value: summary.context_precision,
                    class: 'precision',
                    icon: 'bullseye'
                },
                {
                    label: 'Context Recall',
                    value: summary.context_recall,
                    class: 'recall',
                    icon: 'search'
                },
                {
                    label: 'F1 Score',
                    value: summary.f1_score,
                    class: 'f1',
                    icon: 'calculator'
                },
                {
                    label: 'RAGAS Score',
                    value: summary.ragas_score,
                    class: 'overall',
                    icon: 'star-fill'
                }
            ];

            container.innerHTML = metrics.map(m => `
                <div class="col-md-4 col-lg-2 mb-3">
                    <div class="metric-box ${m.class}">
                        <i class="bi bi-${m.icon}" style="font-size: 2rem; opacity: 0.8;"></i>
                        <div class="metric-value">${m.value}%</div>
                        <div class="metric-label">${m.label}</div>
                    </div>
                </div>
            `).join('');
        }

        function renderAdditionalMetrics(data) {
            // Update rekap evaluasi
            const totalScenarios = document.getElementById('totalScenarios');
            const avgFaithfulness = document.getElementById('avgFaithfulness');
            const avgF1Score = document.getElementById('avgF1Score');

            if (totalScenarios) totalScenarios.textContent = data.total_tests || 0;
            if (avgFaithfulness) avgFaithfulness.textContent = (data.summary?.faithfulness || 0) + '%';
            if (avgF1Score) avgF1Score.textContent = (data.summary?.f1_score || 0) + '%';

            // Update temuan aktif
            const activeFindings = document.getElementById('activeFindings');
            if (activeFindings) activeFindings.textContent = data.active_findings || 0;

            // Update notes list
            const notesList = document.getElementById('notesList');
            if (notesList && data.notes) {
                notesList.innerHTML = data.notes.map(note => `<li>${note}</li>`).join('');
            } else if (notesList) {
                notesList.innerHTML = '<li>Belum ada catatan diskusi</li>';
            }

            // Update rating distribution
            const ratingDistribution = document.getElementById('ratingDistribution');
            if (ratingDistribution && data.rating_distribution) {
                ratingDistribution.innerHTML = `
                    <ul class="list-unstyled small">
                        <li><span class="badge bg-success">■</span> Excellent (≥90%): ${data.rating_distribution.excellent || 0}</li>
                        <li><span class="badge bg-info">■</span> Good (80-89%): ${data.rating_distribution.good || 0}</li>
                        <li><span class="badge bg-warning">■</span> Fair (70-79%): ${data.rating_distribution.fair || 0}</li>
                        <li><span class="badge bg-danger">■</span> Poor (<70%): ${data.rating_distribution.poor || 0}</li>
                    </ul>
                `;
            } else if (ratingDistribution) {
                ratingDistribution.innerHTML = '<small>Data rating tidak tersedia</small>';
            }
        }

        function renderDatasetComposition(dataset) {
            const container = document.getElementById('datasetComposition');
            if (!container) return;

            const totalDocs = dataset.reduce((sum, item) => sum + item.jumlah, 0);

            container.innerHTML = `
                <div class="row">
                    ${dataset.map(item => `
                                <div class="col-md-6 mb-3">
                                    <div class="dataset-item">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="label">${item.jenis}</div>
                                            <div class="number">${item.jumlah.toLocaleString()}</div>
                                        </div>
                                        <div class="description">${item.keterangan}</div>
                                        <div class="progress-custom mt-2" style="height: 8px;">
                                            <div class="progress-bar" style="width: ${(item.jumlah/totalDocs)*100}%; background: #667eea; height: 8px; border-radius: 4px;"></div>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                </div>
                <div class="alert alert-info border-0 mt-3">
                    <strong>Total Dataset:</strong> ±${totalDocs.toLocaleString()} dokumen & data
                </div>
            `;
        }

        function renderScenariosTable(scenarios, ragasScore) {
            const tbody = document.getElementById('scenariosTable');
            if (!tbody) return;

            tbody.innerHTML = scenarios.map(s => `
                <tr>
                    <td style="font-weight: 600;">${s.no}</td>
                    <td>${s.question}</td>
                    <td><span class="badge bg-secondary">${s.kategori}</span></td>
                    <td>${getBadge(s.faithfulness)}</td>
                    <td>${getBadge(s.hallucination, true)}</td>
                    <td>${getBadge(s.precision)}</td>
                    <td>${getBadge(s.recall)}</td>
                    <td>${getBadge(s.f1)}</td>
                </tr>
            `).join('');

            // Add average row
            if (scenarios.length > 0) {
                const avgFaith = (scenarios.reduce((sum, s) => sum + s.faithfulness, 0) / scenarios.length).toFixed(2);
                const avgHallu = (scenarios.reduce((sum, s) => sum + s.hallucination, 0) / scenarios.length).toFixed(2);
                const avgPrec = (scenarios.reduce((sum, s) => sum + s.precision, 0) / scenarios.length).toFixed(2);
                const avgRecall = (scenarios.reduce((sum, s) => sum + s.recall, 0) / scenarios.length).toFixed(2);
                const avgF1 = (scenarios.reduce((sum, s) => sum + s.f1, 0) / scenarios.length).toFixed(2);

                // Baris Rata-rata
                tbody.innerHTML += `
                    <tr style="background: #f8f9fa; font-weight: 700;">
                        <td colspan="3" style="text-align: right;">Rata-rata</td>
                        <td>${getBadge(parseFloat(avgFaith))}</td>
                        <td>${getBadge(parseFloat(avgHallu), true)}</td>
                        <td>${getBadge(parseFloat(avgPrec))}</td>
                        <td>${getBadge(parseFloat(avgRecall))}</td>
                        <td>${getBadge(parseFloat(avgF1))}</td>
                    </tr>
                `;

                // Baris RAGAS Score (diletakkan PERSIS di bawah Rata-rata, kolom FAITH. / kolom ke-4)
                if (typeof ragasScore !== 'undefined' && ragasScore !== null) {
                    let rVal = parseFloat(ragasScore);
                    if (!isNaN(rVal) && rVal > 1) rVal = rVal / 100;

                    tbody.innerHTML += `
                        <tr style="background: #eef2ff; font-weight: 700;">
                            <td colspan="3" style="text-align: right;">RAGAS Score</td>
                            <td>${getBadge(isNaN(rVal) ? 0 : rVal)}</td>
                            <td colspan="4"></td>
                        </tr>
                    `;
                }
            }

            // Update table info
            const tableInfo = document.getElementById('tableInfo');
            if (tableInfo) {
                tableInfo.textContent = `Menampilkan Data ${scenarios.length} dari total ${scenarios.length} skenario`;
            }
        }

        function renderCategoryDistribution(distribution) {
            const ctx = document.getElementById('categoryPieChart');
            if (!ctx) return;

            const labels = distribution.map(d => d.kategori);
            const data = distribution.map(d => d.count);
            const colors = [
                '#667eea', '#764ba2', '#f093fb', '#4facfe',
                '#43e97b', '#fa709a', '#fee140', '#30cfd0'
            ];

            if (categoryPieChart) categoryPieChart.destroy();

            categoryPieChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} tests (${percentage}%)`;
                                }
                            }
                        },
                        datalabels: {
                            formatter: function(value, ctx) {
                                const dataArr = ctx.dataset.data;
                                const total = dataArr.reduce((a, b) => a + b, 0);
                                const percentage = total ? ((value / total) * 100).toFixed(1) : '0.0';
                                return percentage + '%';
                            },
                            color: '#fff',
                            font: {
                                weight: '700',
                                size: 12
                            },
                            clamp: true
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }

        function renderMetricsComparison(metrics) {
            const ctx = document.getElementById('metricsBarChart');
            if (!ctx) return;

            const labels = metrics.map(m => m.metric);
            const data = metrics.map(m => m.value);

            if (metricsBarChart) metricsBarChart.destroy();

            metricsBarChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Score (%)',
                        data: data,
                        backgroundColor: [
                            'rgba(17, 153, 142, 0.8)',
                            'rgba(79, 172, 254, 0.8)',
                            'rgba(240, 147, 251, 0.8)',
                            'rgba(102, 126, 234, 0.8)',
                            'rgba(250, 112, 154, 0.8)',
                            'rgba(254, 225, 64, 0.8)'
                        ],
                        borderColor: [
                            'rgba(17, 153, 142, 1)',
                            'rgba(79, 172, 254, 1)',
                            'rgba(240, 147, 251, 1)',
                            'rgba(102, 126, 234, 1)',
                            'rgba(250, 112, 154, 1)',
                            'rgba(254, 225, 64, 1)'
                        ],
                        borderWidth: 2,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Score: ${context.parsed.y.toFixed(2)}%`;
                                }
                            }
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'end',
                            formatter: function(value) {
                                // value is already percentage                                return value.toFixed(1) + '%';
                            },
                            color: '#000',
                            font: {
                                weight: '700',
                                size: 11
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }

        function getBadge(value, reverse = false) {
            const percentage = (value * 100).toFixed(1);
            let badgeClass = '';

            if (reverse) {
                // For hallucination, lower is better
                if (value <= 0.10) badgeClass = 'badge-excellent';
                else if (value <= 0.15) badgeClass = 'badge-good';
                else if (value <= 0.20) badgeClass = 'badge-fair';
                else badgeClass = 'badge-poor';
            } else {
                // For other metrics, higher is better
                if (value >= 0.90) badgeClass = 'badge-excellent';
                else if (value >= 0.80) badgeClass = 'badge-good';
                else if (value >= 0.70) badgeClass = 'badge-fair';
                else badgeClass = 'badge-poor';
            }

            return `<span class="badge-metric ${badgeClass}">${percentage}%</span>`;
        }

        function renderAnalysis(analysis) {
            const analysisSection = document.getElementById('analysisSection');

            if (!analysis) {
                if (analysisSection) analysisSection.style.display = 'none';
                return;
            }

            if (analysisSection) analysisSection.style.display = 'block';

            const conclusionEl = document.getElementById('analysisConclusion');
            if (conclusionEl && analysis.conclusion) {
                conclusionEl.innerHTML = analysis.conclusion;
            }

            const strengthsEl = document.getElementById('analysisStrengths');
            if (strengthsEl && analysis.strengths && analysis.strengths.length > 0) {
                strengthsEl.innerHTML = analysis.strengths.map(s => `<li>${s}</li>`).join('');
            } else if (strengthsEl) {
                strengthsEl.innerHTML = '<li>Sistem sudah berfungsi dengan baik</li>';
            }

            const limitationsEl = document.getElementById('analysisLimitations');
            if (limitationsEl && analysis.limitations && analysis.limitations.length > 0) {
                limitationsEl.innerHTML = analysis.limitations.map(l => `<li>${l}</li>`).join('');
            } else if (limitationsEl) {
                limitationsEl.innerHTML = '<li>Performa dalam batas normal</li>';
            }

            const recommendationsEl = document.getElementById('analysisRecommendations');
            if (recommendationsEl && analysis.recommendations && analysis.recommendations.length > 0) {
                recommendationsEl.innerHTML = analysis.recommendations.map(r => `<li>${r}</li>`).join('');
            } else if (recommendationsEl) {
                recommendationsEl.innerHTML = '<li>Lanjutkan monitoring secara berkala</li>';
            }
        }

        function downloadReport() {
            window.location.href = '{{ route('gjm.evaluasi.ragas.download-report') }}';
        }

        function syncFromCache() {
            if (confirm('Sinkronisasi data evaluasi terbaru dari AI cache MongoDB?')) {
                const infoBanner = document.getElementById('infoBanner');
                if (infoBanner) {
                    infoBanner.innerHTML = '<i class="bi bi-hourglass-split"></i> Sedang menyinkronkan data...';
                }

                fetch('{{ route('gjm.evaluasi.ragas.sync-cache') }}', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            alert(`✅ Berhasil! ${result.synced} data baru ditambahkan.`);
                            location.reload();
                        } else {
                            alert('Error: ' + result.message);
                        }
                    })
                    .catch(error => {
                        console.error('Sync error:', error);
                        alert('Gagal menyinkronkan data: ' + error.message);
                    });
            }
        }
    </script>
@endsection
