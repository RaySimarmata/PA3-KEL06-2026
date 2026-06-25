<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Laporan Evaluasi RAGAS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 10pt;
            line-height: 1.5;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }

        .header h1 {
            font-size: 18pt;
            color: #667eea;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 10pt;
            color: #666;
        }

        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 13pt;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 12px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e0e0e0;
        }

        .metrics-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .metric-row {
            display: table-row;
        }

        .metric-box {
            display: table-cell;
            width: 16.66%;
            text-align: center;
            padding: 15px 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin: 5px;
            border-radius: 5px;
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

        .metric-value {
            font-size: 20pt;
            font-weight: bold;
            display: block;
            margin: 5px 0;
        }

        .metric-label {
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9pt;
        }

        table thead {
            background-color: #667eea;
            color: white;
        }

        table thead th {
            padding: 8px;
            text-align: left;
            font-weight: 600;
            font-size: 8pt;
        }

        table tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #e0e0e0;
        }

        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table tbody tr.avg-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .badge {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 8pt;
            font-weight: 600;
            display: inline-block;
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

        .badge-kategori {
            background: #6c757d;
            color: white;
        }

        .info-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 3px;
            font-size: 9pt;
        }

        .dataset-list {
            margin: 10px 0;
        }

        .dataset-item {
            padding: 10px;
            margin-bottom: 8px;
            background: #f8f9fa;
            border-left: 3px solid #667eea;
            border-radius: 3px;
        }

        .dataset-item strong {
            color: #667eea;
            font-size: 11pt;
        }

        .dataset-item p {
            margin: 3px 0;
            font-size: 9pt;
            color: #666;
        }

        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            font-size: 8pt;
            color: #999;
        }

        .analysis-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            margin: 15px 0;
            border-radius: 3px;
            font-size: 9pt;
        }

        .analysis-box h4 {
            margin-bottom: 8px;
            color: #856404;
            font-size: 11pt;
        }

        .analysis-box ul {
            margin-left: 20px;
            margin-top: 5px;
        }

        .analysis-box li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>LAPORAN EVALUASI SISTEM RAG</h1>
        <p>Framework RAGAS (Retrieval-Augmented Generation Assessment)</p>
        <p>Fakultas Vokasi - Sistem Laporan Akademik</p>
        <p style="margin-top: 5px;">Tanggal: {{ $generated_at }}</p>
    </div>

    <!-- Info Banner -->
    <div class="info-box">
        <strong>Tentang RAGAS Framework:</strong><br>
        Framework evaluasi open-source yang menyediakan serangkaian metrik terstandar untuk mengukur performa sistem RAG secara menyeluruh, meliputi kualitas proses retrieval dan generation. Data evaluasi berdasarkan {{ $data['total_tests'] }} skenario pengujian.
    </div>

    <!-- Summary Metrics -->
    <div class="section">
        <h2 class="section-title">Rekapitulasi Hasil Evaluasi</h2>
        
        <table>
            <thead>
                <tr>
                    <th>Metrik</th>
                    <th style="text-align: center;">Nilai</th>
                    <th style="text-align: center;">Kategori</th>
                    <th>Deskripsi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Faithfulness</strong></td>
                    <td style="text-align: center;"><strong>{{ $data['summary']['faithfulness'] }}%</strong></td>
                    <td style="text-align: center;">
                        <span class="badge badge-{{ $data['summary']['faithfulness'] >= 90 ? 'excellent' : ($data['summary']['faithfulness'] >= 80 ? 'good' : 'fair') }}">
                            {{ $data['summary']['faithfulness'] >= 90 ? 'Excellent' : ($data['summary']['faithfulness'] >= 80 ? 'Good' : 'Fair') }}
                        </span>
                    </td>
                    <td>Kesesuaian jawaban dengan dokumen sumber</td>
                </tr>
                <tr>
                    <td><strong>Hallucination Rate</strong></td>
                    <td style="text-align: center;"><strong>{{ $data['summary']['hallucination_rate'] }}%</strong></td>
                    <td style="text-align: center;">
                        <span class="badge badge-{{ $data['summary']['hallucination_rate'] <= 10 ? 'excellent' : ($data['summary']['hallucination_rate'] <= 15 ? 'good' : 'fair') }}">
                            {{ $data['summary']['hallucination_rate'] <= 10 ? 'Excellent' : ($data['summary']['hallucination_rate'] <= 15 ? 'Good' : 'Fair') }}
                        </span>
                    </td>
                    <td>Tingkat informasi tanpa dukungan dokumen</td>
                </tr>
                <tr>
                    <td><strong>Context Precision</strong></td>
                    <td style="text-align: center;"><strong>{{ $data['summary']['context_precision'] }}%</strong></td>
                    <td style="text-align: center;">
                        <span class="badge badge-{{ $data['summary']['context_precision'] >= 90 ? 'excellent' : ($data['summary']['context_precision'] >= 70 ? 'good' : 'fair') }}">
                            {{ $data['summary']['context_precision'] >= 90 ? 'Excellent' : ($data['summary']['context_precision'] >= 70 ? 'Good' : 'Fair') }}
                        </span>
                    </td>
                    <td>Ketepatan konteks yang diambil</td>
                </tr>
                <tr>
                    <td><strong>Context Recall</strong></td>
                    <td style="text-align: center;"><strong>{{ $data['summary']['context_recall'] }}%</strong></td>
                    <td style="text-align: center;">
                        <span class="badge badge-{{ $data['summary']['context_recall'] >= 90 ? 'excellent' : ($data['summary']['context_recall'] >= 80 ? 'good' : 'fair') }}">
                            {{ $data['summary']['context_recall'] >= 90 ? 'Excellent' : ($data['summary']['context_recall'] >= 80 ? 'Good' : 'Fair') }}
                        </span>
                    </td>
                    <td>Kelengkapan informasi yang ditemukan</td>
                </tr>
                <tr>
                    <td><strong>F1 Score</strong></td>
                    <td style="text-align: center;"><strong>{{ $data['summary']['f1_score'] }}%</strong></td>
                    <td style="text-align: center;">
                        <span class="badge badge-{{ $data['summary']['f1_score'] >= 90 ? 'excellent' : ($data['summary']['f1_score'] >= 70 ? 'good' : 'fair') }}">
                            {{ $data['summary']['f1_score'] >= 90 ? 'Excellent' : ($data['summary']['f1_score'] >= 70 ? 'Good' : 'Fair') }}
                        </span>
                    </td>
                    <td>Keseimbangan precision dan recall</td>
                </tr>
                <tr style="background-color: #e8eaf6;">
                    <td><strong>RAGAS Score (Overall)</strong></td>
                    <td style="text-align: center;"><strong style="font-size: 12pt; color: #667eea;">{{ $data['summary']['ragas_score'] }}%</strong></td>
                    <td style="text-align: center;">
                        <span class="badge badge-good">Good</span>
                    </td>
                    <td><strong>Skor keseluruhan sistem RAG</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Dataset Composition -->
    <div class="section">
        <h2 class="section-title">Komposisi Dataset Knowledge Base</h2>
        <p style="margin-bottom: 10px; font-size: 9pt; color: #666;">Dataset terdiri dari dokumen nyata yang diperoleh melalui integrasi API CIS, unggahan file institusi, serta data historis sistem.</p>
        
        <div class="dataset-list">
            @foreach($data['dataset'] as $item)
            <div class="dataset-item">
                <strong>{{ $item['jumlah'] }} {{ $item['jenis'] }}</strong>
                <p>{{ $item['keterangan'] }}</p>
            </div>
            @endforeach
        </div>
        
        <div style="background: #d1ecf1; padding: 10px; margin-top: 10px; border-radius: 3px; text-align: center;">
            <strong style="color: #0c5460;">Total Dataset: ±{{ array_sum(array_column($data['dataset'], 'jumlah')) }} dokumen & data</strong>
        </div>
    </div>

    <!-- Page Break -->
    <div style="page-break-before: always;"></div>

    <!-- Detailed Results -->
    <div class="section">
        <h2 class="section-title">Hasil Evaluasi Per Skenario Pengujian</h2>
        <p style="margin-bottom: 10px; font-size: 9pt; color: #666;">{{ $data['total_tests'] }} skenario pengujian yang dirancang untuk mewakili berbagai kebutuhan pengguna sistem.</p>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">NO</th>
                    <th style="width: 35%;">PERTANYAAN / SKENARIO</th>
                    <th style="width: 12%;">KATEGORI</th>
                    <th style="width: 8%;">FAITH.</th>
                    <th style="width: 8%;">HALLU.</th>
                    <th style="width: 8%;">PREC.</th>
                    <th style="width: 8%;">RECALL</th>
                    <th style="width: 8%;">F1</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['scenarios'] as $scenario)
                <tr>
                    <td style="text-align: center;">{{ $scenario['no'] }}</td>
                    <td>{{ $scenario['question'] }}</td>
                    <td><span class="badge badge-kategori">{{ $scenario['kategori'] }}</span></td>
                    <td style="text-align: center;">
                        @php
                            $faith = $scenario['faithfulness'] * 100;
                            $badgeClass = $faith >= 90 ? 'excellent' : ($faith >= 80 ? 'good' : 'fair');
                        @endphp
                        <span class="badge badge-{{ $badgeClass }}">{{ number_format($faith, 1) }}%</span>
                    </td>
                    <td style="text-align: center;">
                        @php
                            $hallu = $scenario['hallucination'] * 100;
                            $badgeClass = $hallu <= 10 ? 'excellent' : ($hallu <= 15 ? 'good' : 'fair');
                        @endphp
                        <span class="badge badge-{{ $badgeClass }}">{{ number_format($hallu, 1) }}%</span>
                    </td>
                    <td style="text-align: center;">
                        @php
                            $prec = $scenario['precision'] * 100;
                            $badgeClass = $prec >= 90 ? 'excellent' : ($prec >= 70 ? 'good' : 'fair');
                        @endphp
                        <span class="badge badge-{{ $badgeClass }}">{{ number_format($prec, 1) }}%</span>
                    </td>
                    <td style="text-align: center;">
                        @php
                            $recall = $scenario['recall'] * 100;
                            $badgeClass = $recall >= 90 ? 'excellent' : ($recall >= 80 ? 'good' : 'fair');
                        @endphp
                        <span class="badge badge-{{ $badgeClass }}">{{ number_format($recall, 1) }}%</span>
                    </td>
                    <td style="text-align: center;">
                        @php
                            $f1 = $scenario['f1'] * 100;
                            $badgeClass = $f1 >= 90 ? 'excellent' : ($f1 >= 70 ? 'good' : 'fair');
                        @endphp
                        <span class="badge badge-{{ $badgeClass }}">{{ number_format($f1, 1) }}%</span>
                    </td>
                </tr>
                @endforeach
                <tr class="avg-row">
                    <td colspan="3" style="text-align: right;">Rata-rata</td>
                    <td style="text-align: center;">{{ number_format($data['summary']['faithfulness'], 1) }}%</td>
                    <td style="text-align: center;">{{ number_format($data['summary']['hallucination_rate'], 1) }}%</td>
                    <td style="text-align: center;">{{ number_format($data['summary']['context_precision'], 1) }}%</td>
                    <td style="text-align: center;">{{ number_format($data['summary']['context_recall'], 1) }}%</td>
                    <td style="text-align: center;">{{ number_format($data['summary']['f1_score'], 1) }}%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Analysis -->
    <div class="section">
        <h2 class="section-title">Analisis Hasil Evaluasi</h2>
        
        <div class="info-box">
            <strong>Kesimpulan Utama:</strong><br>
            Sistem RAG yang dikembangkan memiliki performa keseluruhan yang <strong>baik dengan RAGAS Score {{ $data['summary']['ragas_score'] }}%</strong>. 
            Nilai Faithfulness tinggi ({{ $data['summary']['faithfulness'] }}%) dan Hallucination Rate rendah ({{ $data['summary']['hallucination_rate'] }}%) menunjukkan sistem berhasil menghasilkan 
            laporan berbasis data nyata dengan tingkat keandalan informasi yang memadai.
        </div>

        <div class="analysis-box">
            <h4>Kelebihan Sistem:</h4>
            <ul>
                <li><strong>Faithfulness Tinggi ({{ $data['summary']['faithfulness'] }}%):</strong> Sebagian besar pernyataan dapat diverifikasi dari dokumen sumber</li>
                <li><strong>Context Recall Baik ({{ $data['summary']['context_recall'] }}%):</strong> Sistem mampu menemukan sebagian besar informasi relevan</li>
                <li><strong>Halusinasi Rendah ({{ $data['summary']['hallucination_rate'] }}%):</strong> Model tidak banyak mengarang informasi</li>
                <li><strong>F1 Score Baik ({{ $data['summary']['f1_score'] }}%):</strong> Keseimbangan antara ketepatan dan kelengkapan memadai</li>
            </ul>
        </div>

        <div class="analysis-box">
            <h4>Area Pengembangan:</h4>
            <ul>
                <li><strong>Context Precision ({{ $data['summary']['context_precision'] }}%):</strong> Masih ada konteks yang kurang relevan</li>
                <li><strong>Context Relevancy ({{ $data['summary']['context_relevancy'] }}%):</strong> Perlu penyempurnaan algoritma retrieval</li>
                <li><strong>Answer Relevancy ({{ $data['summary']['answer_relevancy'] }}%):</strong> Dapat ditingkatkan dengan prompt engineering</li>
                <li><strong>Rekomendasi:</strong> Penerapan HyDE (Hypothetical Document Embedding) atau cross-encoder untuk meningkatkan relevansi konteks</li>
            </ul>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Laporan Evaluasi RAGAS - Sistem Laporan Akademik Fakultas Vokasi</p>
        <p>Generated at: {{ $generated_at }} | Framework: RAGAS v1.0</p>
    </div>
</body>
</html>
