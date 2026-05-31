/**
 * Render Ambiguity Metrics Section
 */
function renderAmbiguityMetrics(ambiguityData) {
    const container = document.getElementById('ambiguitySection');

    if (!ambiguityData || !ambiguityData.has_data) {
        // Check if VMTS filter is active
        const isVMTSFilter = (typeof currentFeature !== 'undefined' && currentFeature === 'vmts');

        let message = ambiguityData?.message || 'Gunakan fitur evaluasi untuk menambahkan test cases dengan evaluasi ambiguity.';

        if (isVMTSFilter) {
            container.innerHTML = `
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Evaluasi Ambiguity untuk Laporan VMTS</strong><br>
                    <p class="mb-2 mt-2">Laporan VMTS menggunakan pendekatan analisis file (Excel kuesioner + PDF/Word referensi tahun lalu) yang berbeda dari Triwulan & Semester.</p>
                    <p class="mb-0"><strong>Status:</strong> ${message}</p>
                </div>
            `;
        } else {
            container.innerHTML = `
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Belum Ada Data Evaluasi Ambiguity</strong><br>
                    ${message}
                </div>
            `;
        }
        return;
    }

    const qualityLevel = ambiguityData.quality_level;
    const distribution = ambiguityData.distribution;

    let html = `
        <!-- Overview Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-${qualityLevel.color}">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Tes</h6>
                        <h3 class="mb-0">${ambiguityData.total_tests}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-${qualityLevel.color}">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Rata-rata Skor Ambiguity</h6>
                        <h3 class="mb-0">${ambiguityData.avg_ambiguity} <small>/5</small></h3>
                        <span class="badge bg-${qualityLevel.color}">${qualityLevel.label}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-${qualityLevel.color}">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Kasus Ambiguity Tinggi</h6>
                        <h3 class="mb-0">${ambiguityData.high_ambiguity_count}</h3>
                        <small class="text-muted">${ambiguityData.ambiguity_rate}% dari total</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-${qualityLevel.color}">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Level Kualitas</h6>
                        <h3 class="mb-0"><i class="bi bi-${getAmbiguityIcon(qualityLevel.level)}"></i></h3>
                        <span class="badge bg-${qualityLevel.color}">${qualityLevel.level}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quality Description -->
        <div class="alert alert-${qualityLevel.color}">
            <strong><i class="bi bi-info-circle"></i> Penilaian:</strong> ${qualityLevel.description}
        </div>

        <!-- Distribution Chart -->
        <div class="row">
            <div class="col-md-6">
                <h6 class="mb-3"><i class="bi bi-bar-chart"></i> Distribusi Skor Ambiguity</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Skor</th>
                                <th>Level</th>
                                <th>Jumlah</th>
                                <th>Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td><span class="badge bg-success">Sangat Jelas</span></td>
                                <td>${distribution.very_clear}</td>
                                <td>${((distribution.very_clear / ambiguityData.total_tests) * 100).toFixed(1)}%</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td><span class="badge bg-info">Cukup Jelas</span></td>
                                <td>${distribution.mostly_clear}</td>
                                <td>${((distribution.mostly_clear / ambiguityData.total_tests) * 100).toFixed(1)}%</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td><span class="badge bg-warning">Sedang</span></td>
                                <td>${distribution.moderate}</td>
                                <td>${((distribution.moderate / ambiguityData.total_tests) * 100).toFixed(1)}%</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td><span class="badge bg-danger">Agak Ambigu</span></td>
                                <td>${distribution.somewhat_ambiguous}</td>
                                <td>${((distribution.somewhat_ambiguous / ambiguityData.total_tests) * 100).toFixed(1)}%</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td><span class="badge bg-dark">Sangat Ambigu</span></td>
                                <td>${distribution.very_ambiguous}</td>
                                <td>${((distribution.very_ambiguous / ambiguityData.total_tests) * 100).toFixed(1)}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-6">
                <h6 class="mb-3"><i class="bi bi-lightbulb"></i> Panduan Interpretasi</h6>
                <div class="alert alert-info">
                    <strong>Interpretasi Skor:</strong>
                    <ul class="mb-0 mt-2">
                        <li><strong>1-2:</strong> Respons jelas dan dapat ditindaklanjuti</li>
                        <li><strong>3:</strong> Dapat diterima dengan perbaikan kecil yang diperlukan</li>
                        <li><strong>4-5:</strong> Memerlukan peningkatan kejelasan yang signifikan</li>
                    </ul>
                </div>
            </div>
        </div>
    `;

    container.innerHTML = html;
}

/**
 * Get ambiguity icon
 */
function getAmbiguityIcon(level) {
    const icons = {
        'Excellent': 'check-circle-fill',
        'Good': 'check-circle',
        'Fair': 'exclamation-circle',
        'Poor': 'x-circle-fill'
    };
    return icons[level] || 'question-circle';
}

/**
 * Render RAGAS Metrics Section
 */
function renderRAGASMetrics(ragasData) {
    const container = document.getElementById('ragasSection');

    if (!ragasData || !ragasData.has_data) {
        // Check if VMTS filter is active
        const isVMTSFilter = (typeof currentFeature !== 'undefined' && currentFeature === 'vmts');

        let message = ragasData?.message || 'Gunakan fitur evaluasi untuk menambahkan test cases dengan RAG context.';

        if (isVMTSFilter) {
            container.innerHTML = `
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>RAGAS Evaluation untuk Laporan VMTS</strong><br>
                    <p class="mb-2 mt-2">Laporan VMTS menggunakan sistem analisis file yang berbeda:</p>
                    <ul class="mb-2">
                        <li><strong>Input:</strong> File Excel (data kuesioner) + PDF/Word (laporan tahun lalu)</li>
                        <li><strong>Proses:</strong> AI menganalisis kedua file untuk membuat laporan baru</li>
                        <li><strong>Output:</strong> Laporan lengkap dengan analisis dan rekomendasi</li>
                    </ul>
                    <p class="mb-0"><strong>Status:</strong> ${message}</p>
                    <div class="alert alert-warning mt-3 mb-0">
                        <small><i class="bi bi-lightbulb"></i> <strong>Catatan:</strong> RAGAS evaluation mengukur kualitas sistem RAG (Retrieval-Augmented Generation). Untuk VMTS yang menggunakan analisis file langsung, metrik ini mungkin tidak sepenuhnya applicable.</small>
                    </div>
                </div>
            `;
        } else {
            container.innerHTML = `
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Belum Ada Data Evaluasi RAGAS</strong><br>
                    ${message}
                </div>
            `;
        }
        return;
    }

    const qualityLevel = ragasData.quality_level;
    const metrics = ragasData.metrics;

    // Show data source info if combined
    let dataSourceInfo = '';
    if (ragasData.data_source === 'combined (ai_evaluation_tests + laporan_gjm)') {
        dataSourceInfo = `
            <div class="alert alert-success border-0 mb-3" style="background: #E8F5E9;">
                <i class="bi bi-check-circle"></i>
                <strong>Data Gabungan dari Semua Fitur:</strong>
                <ul class="mb-0 mt-2">
                    <li><strong>Laporan Triwulan & Semester:</strong> ${ragasData.breakdown.total_triwulan_semester} laporan selesai (${ragasData.breakdown.from_tests_with_ragas} dengan RAGAS score)</li>
                    <li><strong>Laporan VMTS:</strong> ${ragasData.breakdown.total_vmts} laporan selesai (${ragasData.breakdown.from_laporans_with_ragas} dengan RAGAS score)</li>
                    <li><strong>Total:</strong> ${ragasData.total_tests} data points untuk evaluasi</li>
                    <li><strong>Metrics dari:</strong> ${ragasData.tests_with_ragas} laporan yang sudah dievaluasi RAGAS</li>
                </ul>
            </div>
        `;
    } else if (ragasData.data_source === 'laporan_gjm') {
        dataSourceInfo = `
            <div class="alert alert-info border-0 mb-3" style="background: #E3F2FD;">
                <i class="bi bi-info-circle"></i>
                <strong>Data dari Laporan VMTS:</strong> Metrics diambil dari ${ragasData.total_tests} laporan VMTS yang telah dibuat.
            </div>
        `;
    }

    let html = `
        ${dataSourceInfo}

        <!-- Overview Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-${qualityLevel.color}">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Tes</h6>
                        <h3 class="mb-0">${ragasData.total_tests}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-${qualityLevel.color}">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Skor RAGAS Keseluruhan</h6>
                        <h3 class="mb-0">${ragasData.overall_percentage}%</h3>
                        <span class="badge bg-${qualityLevel.color}">${qualityLevel.level}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Rata-rata Chunks Digunakan</h6>
                        <h3 class="mb-0">${ragasData.rag_stats.avg_chunks_used}</h3>
                        <small class="text-muted">per query</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Rata-rata Similarity</h6>
                        <h3 class="mb-0">${(ragasData.rag_stats.avg_similarity * 100).toFixed(1)}%</h3>
                        <small class="text-muted">relevansi konteks</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quality Description -->
        <div class="alert alert-${qualityLevel.color}">
            <strong><i class="bi bi-info-circle"></i> Penilaian:</strong> ${qualityLevel.description}
        </div>

        <!-- RAGAS Metrics Detail -->
        <div class="row mb-4">
            <div class="col-12">
                <h6 class="mb-3"><i class="bi bi-speedometer2"></i> Rincian Metrik RAGAS</h6>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Metrik</th>
                                <th>Deskripsi</th>
                                <th>Rata-rata</th>
                                <th>Min</th>
                                <th>Max</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${renderRAGASMetricRow('Faithfulness', metrics.faithfulness)}
                            ${renderRAGASMetricRow('Answer Relevancy', metrics.answer_relevancy)}
                            ${renderRAGASMetricRow('Context Precision', metrics.context_precision)}
                            ${renderRAGASMetricRow('Context Recall', metrics.context_recall)}
                            ${renderRAGASMetricRow('Context Relevancy', metrics.context_relevancy)}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quality Distribution -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="mb-3"><i class="bi bi-pie-chart"></i> Distribusi Kualitas</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Level Kualitas</th>
                                <th>Jumlah</th>
                                <th>Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge bg-success">Sangat Baik (≥80%)</span></td>
                                <td>${ragasData.quality_distribution.excellent}</td>
                                <td>${((ragasData.quality_distribution.excellent / ragasData.total_tests) * 100).toFixed(1)}%</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-info">Baik (60-80%)</span></td>
                                <td>${ragasData.quality_distribution.good}</td>
                                <td>${((ragasData.quality_distribution.good / ragasData.total_tests) * 100).toFixed(1)}%</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning">Cukup (40-60%)</span></td>
                                <td>${ragasData.quality_distribution.fair}</td>
                                <td>${((ragasData.quality_distribution.fair / ragasData.total_tests) * 100).toFixed(1)}%</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-danger">Kurang (<40%)</span></td>
                                <td>${ragasData.quality_distribution.poor}</td>
                                <td>${((ragasData.quality_distribution.poor / ragasData.total_tests) * 100).toFixed(1)}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-6">
                <h6 class="mb-3"><i class="bi bi-lightbulb"></i> Rekomendasi</h6>
                ${renderRAGASRecommendations(ragasData.recommendations)}
            </div>
        </div>
    `;

    container.innerHTML = html;
}

/**
 * Render RAGAS metric row
 */
function renderRAGASMetricRow(name, metric) {
    const percentage = (metric.avg * 100).toFixed(1);
    const statusClass = metric.avg >= 0.8 ? 'success' : metric.avg >= 0.6 ? 'info' : metric.avg >= 0.4 ? 'warning' : 'danger';
    const statusIcon = metric.avg >= 0.7 ? 'check-circle-fill' : 'exclamation-circle-fill';

    return `
        <tr>
            <td><strong>${name}</strong></td>
            <td><small>${metric.description}</small></td>
            <td><span class="badge bg-${statusClass}">${percentage}%</span></td>
            <td>${(metric.min * 100).toFixed(1)}%</td>
            <td>${(metric.max * 100).toFixed(1)}%</td>
            <td><i class="bi bi-${statusIcon} text-${statusClass}"></i></td>
        </tr>
    `;
}

/**
 * Render RAGAS recommendations
 */
function renderRAGASRecommendations(recommendations) {
    // Ensure recommendations is an array
    if (!recommendations || !Array.isArray(recommendations) || recommendations.length === 0) {
        return '<p class="text-muted">Tidak ada rekomendasi khusus saat ini.</p>';
    }

    let html = '<div class="list-group">';
    recommendations.forEach((rec, index) => {
        const badgeClass = rec.metric === 'Overall' ? 'success' : 'warning';
        html += `
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1"><span class="badge bg-${badgeClass}">${rec.metric}</span></h6>
                </div>
                <p class="mb-1"><strong>Masalah:</strong> ${rec.issue}</p>
                <p class="mb-0"><strong>Tindakan:</strong> ${rec.action}</p>
            </div>
        `;
    });
    html += '</div>';
    return html;
}
