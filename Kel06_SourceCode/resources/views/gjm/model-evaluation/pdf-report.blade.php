<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Evaluasi AI Assistant</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #5B9BD5;
        }
        
        .header h1 {
            color: #5B9BD5;
            font-size: 20px;
            margin: 0 0 5px 0;
        }
        
        .header p {
            margin: 3px 0;
            font-size: 9px;
            color: #666;
        }
        
        .section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        
        .section-title {
            background: #5B9BD5;
            color: white;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .metrics-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .metric-box {
            display: table-cell;
            width: 25%;
            padding: 8px;
            text-align: center;
            border: 1px solid #ddd;
            background: #f9f9f9;
        }
        
        .metric-label {
            font-size: 8px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        
        .metric-value {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        table th {
            background: #5B9BD5;
            color: white;
            padding: 6px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
        }
        
        table td {
            padding: 5px 6px;
            border-bottom: 1px solid #ddd;
            font-size: 9px;
        }
        
        table tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .chart-placeholder {
            width: 100%;
            height: 150px;
            border: 2px dashed #5B9BD5;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f8ff;
            margin: 10px 0;
            text-align: center;
            color: #5B9BD5;
            font-size: 11px;
        }
        
        .pie-chart {
            width: 200px;
            height: 200px;
            margin: 10px auto;
        }
        
        .info-box {
            background: #E3F2FD;
            border-left: 4px solid #5B9BD5;
            padding: 8px 10px;
            margin: 8px 0;
            font-size: 9px;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        
        .badge-success { background: #28a745; color: white; }
        .badge-info { background: #17a2b8; color: white; }
        .badge-warning { background: #ffc107; color: #333; }
        .badge-danger { background: #dc3545; color: white; }
        
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #666;
            padding: 5px 0;
            border-top: 1px solid #ddd;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>LAPORAN EVALUASI AI ASSISTANT</h1>
        <p><strong>Periode:</strong> {{ $period }} | <strong>Fitur:</strong> {{ $feature }}</p>
        <p><strong>Dibuat:</strong> {{ $generated_at }}</p>
    </div>

    <!-- Overview Metrics -->
    <div class="section">
        <div class="section-title">RINGKASAN METRIK</div>
        
        @if($data['overview']['total_requests'] > 0)
        <div class="metrics-grid">
            <div class="metric-box">
                <div class="metric-label">Total Permintaan</div>
                <div class="metric-value">{{ number_format($data['overview']['total_requests']) }}</div>
            </div>
            <div class="metric-box">
                <div class="metric-label">Cache Hits</div>
                <div class="metric-value">{{ number_format($data['overview']['cache_hits']) }}</div>
            </div>
            <div class="metric-box">
                <div class="metric-label">Tingkat Cache Hit</div>
                <div class="metric-value">{{ $data['overview']['cache_hit_rate'] }}%</div>
            </div>
            <div class="metric-box">
                <div class="metric-label">Tingkat Keberhasilan</div>
                <div class="metric-value">{{ $data['overview']['success_rate'] }}%</div>
            </div>
        </div>
        @else
        <div class="info-box">
            <strong>Belum Ada Data:</strong> Belum ada data AI Assistant yang tercatat untuk periode dan fitur yang dipilih.
        </div>
        @endif
    </div>

    <!-- Timeline Chart -->
    @if(count($data['timeline']) > 0)
    <div class="section">
        <div class="section-title">GRAFIK TIMELINE PENGGUNAAN</div>
        @if(isset($data['timeline_chart']) && $data['timeline_chart'])
        <div style="text-align: center; margin: 15px 0;">
            <img src="{{ $data['timeline_chart'] }}" style="max-width: 100%; height: auto;" alt="Timeline Chart">
        </div>
        @else
        <div class="chart-placeholder">
            <div>
                <strong>Grafik Timeline Penggunaan AI Assistant</strong><br>
                <small>Menampilkan tren penggunaan dari {{ count($data['timeline']) }} hari data</small>
            </div>
        </div>
        @endif
        
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th style="text-align: right;">Permintaan Baru</th>
                    <th style="text-align: right;">Cache Hits</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['timeline'] as $item)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item->date)->format('d M Y') }}</td>
                    <td style="text-align: right;">{{ $item->new_requests }}</td>
                    <td style="text-align: right;">{{ $item->cache_hits }}</td>
                    <td style="text-align: right;"><strong>{{ $item->new_requests + $item->cache_hits }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Performance by Feature -->
    @if(count($data['performance']) > 0)
    <div class="section">
        <div class="section-title">PERFORMA PER FITUR</div>
        <table>
            <thead>
                <tr>
                    <th>Fitur</th>
                    <th style="text-align: right;">Total Requests</th>
                    <th style="text-align: right;">Avg Response Length</th>
                    <th style="text-align: right;">Cache Efficiency</th>
                    <th>Provider</th>
                    <th>Model</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['performance'] as $featureName => $metrics)
                <tr>
                    <td><strong>{{ ucfirst($featureName) }}</strong></td>
                    <td style="text-align: right;">{{ number_format($metrics['total_requests']) }}</td>
                    <td style="text-align: right;">{{ number_format($metrics['avg_response_length']) }}</td>
                    <td style="text-align: right;">{{ $metrics['cache_efficiency'] }}%</td>
                    <td>{{ $metrics['most_used_provider'] }}</td>
                    <td>{{ $metrics['most_used_model'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="page-break"></div>

    <!-- Cache Statistics -->
    @if($data['cache']['total_entries'] > 0)
    <div class="section">
        <div class="section-title">STATISTIK CACHE</div>
        
        <table>
            <tr>
                <td><strong>Total Entri</strong></td>
                <td style="text-align: right;">{{ number_format($data['cache']['total_entries']) }}</td>
            </tr>
            <tr>
                <td><strong>Entri Digunakan Ulang</strong></td>
                <td style="text-align: right;">{{ number_format($data['cache']['reused_entries']) }}</td>
            </tr>
            <tr>
                <td><strong>Tingkat Penggunaan Ulang</strong></td>
                <td style="text-align: right;">{{ $data['cache']['reuse_rate'] }}%</td>
            </tr>
            <tr>
                <td><strong>Total Reuses</strong></td>
                <td style="text-align: right;">{{ number_format($data['cache']['total_reuses']) }}</td>
            </tr>
            <tr>
                <td><strong>Ukuran Cache</strong></td>
                <td style="text-align: right;">{{ $data['cache']['cache_size_mb'] }} MB</td>
            </tr>
        </table>
        
        @if(isset($data['cache_pie_chart']) && $data['cache_pie_chart'])
        <div style="text-align: center; margin: 25px 0 15px 0;">
            <img src="{{ $data['cache_pie_chart'] }}" style="max-width: 100%; height: auto;" alt="Cache Distribution Chart">
        </div>
        @else
        <div class="chart-placeholder" style="margin-top: 25px;">
            <div>
                <strong>Diagram Distribusi Cache</strong><br>
                <small>Digunakan Ulang: {{ number_format($data['cache']['reused_entries']) }} | Sekali Pakai: {{ number_format($data['cache']['total_entries'] - $data['cache']['reused_entries']) }}</small>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Hyperparameter Tuning -->
    @if(isset($data['tuning']['before']) && isset($data['tuning']['after']))
    <div class="section">
        <div class="section-title">HYPERPARAMETER TUNING & OPTIMIZATION</div>
        
        <div class="info-box">
            <strong>Metode:</strong> {{ $data['tuning']['method'] }}<br>
            <strong>Deskripsi:</strong> {{ $data['tuning']['description'] }}
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Parameter/Metric</th>
                    <th style="text-align: center;">Before (Baseline)</th>
                    <th style="text-align: center;">After (Optimized)</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($data['tuning']['before']['parameters']))
                <tr>
                    <td colspan="3" style="background: #f0f0f0; font-weight: bold;">PARAMETERS</td>
                </tr>
                @foreach($data['tuning']['before']['parameters'] as $key => $value)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                    <td style="text-align: center;">{{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}</td>
                    <td style="text-align: center;">{{ is_bool($data['tuning']['after']['parameters'][$key]) ? ($data['tuning']['after']['parameters'][$key] ? 'Yes' : 'No') : $data['tuning']['after']['parameters'][$key] }}</td>
                </tr>
                @endforeach
                @endif
                
                @if(isset($data['tuning']['before']['metrics']))
                <tr>
                    <td colspan="3" style="background: #f0f0f0; font-weight: bold;">METRICS</td>
                </tr>
                @foreach($data['tuning']['before']['metrics'] as $key => $value)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                    <td style="text-align: center;">{{ is_numeric($value) ? number_format($value, 2) : $value }}</td>
                    <td style="text-align: center;">{{ is_numeric($data['tuning']['after']['metrics'][$key]) ? number_format($data['tuning']['after']['metrics'][$key], 2) : $data['tuning']['after']['metrics'][$key] }}</td>
                </tr>
                @endforeach
                @endif
            </tbody>
        </table>
    </div>
    @endif

    <div class="page-break"></div>

    <!-- RAGAS Metrics -->
    @if(isset($data['ragas_metrics']['has_data']) && $data['ragas_metrics']['has_data'])
    <div class="section">
        <div class="section-title">RAGAS EVALUATION (RAG SYSTEM QUALITY)</div>
        
        <div class="metrics-grid">
            <div class="metric-box">
                <div class="metric-label">Total Tes</div>
                <div class="metric-value">{{ $data['ragas_metrics']['total_tests'] }}</div>
            </div>
            <div class="metric-box">
                <div class="metric-label">Skor RAGAS</div>
                <div class="metric-value">{{ $data['ragas_metrics']['overall_percentage'] }}%</div>
            </div>
            <div class="metric-box">
                <div class="metric-label">Avg Chunks</div>
                <div class="metric-value">{{ $data['ragas_metrics']['rag_stats']['avg_chunks_used'] }}</div>
            </div>
            <div class="metric-box">
                <div class="metric-label">Avg Similarity</div>
                <div class="metric-value">{{ round($data['ragas_metrics']['rag_stats']['avg_similarity'] * 100, 1) }}%</div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Metrik</th>
                    <th>Deskripsi</th>
                    <th style="text-align: center;">Rata-rata</th>
                    <th style="text-align: center;">Min</th>
                    <th style="text-align: center;">Max</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['ragas_metrics']['metrics'] as $name => $metric)
                <tr>
                    <td><strong>{{ ucfirst(str_replace('_', ' ', $name)) }}</strong></td>
                    <td>{{ $metric['description'] }}</td>
                    <td style="text-align: center;">{{ round($metric['avg'] * 100, 1) }}%</td>
                    <td style="text-align: center;">{{ round($metric['min'] * 100, 1) }}%</td>
                    <td style="text-align: center;">{{ round($metric['max'] * 100, 1) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Ambiguity Metrics -->
    @if(isset($data['ambiguity_metrics']['has_data']) && $data['ambiguity_metrics']['has_data'])
    <div class="section">
        <div class="section-title">EVALUASI AMBIGUITY (KEJELASAN RESPONSE)</div>
        
        <div class="metrics-grid">
            <div class="metric-box">
                <div class="metric-label">Total Tes</div>
                <div class="metric-value">{{ $data['ambiguity_metrics']['total_tests'] }}</div>
            </div>
            <div class="metric-box">
                <div class="metric-label">Avg Ambiguity Score</div>
                <div class="metric-value">{{ $data['ambiguity_metrics']['avg_ambiguity'] }}/5</div>
            </div>
            <div class="metric-box">
                <div class="metric-label">High Ambiguity Cases</div>
                <div class="metric-value">{{ $data['ambiguity_metrics']['high_ambiguity_count'] }}</div>
            </div>
            <div class="metric-box">
                <div class="metric-label">Ambiguity Rate</div>
                <div class="metric-value">{{ $data['ambiguity_metrics']['ambiguity_rate'] }}%</div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Skor</th>
                    <th>Level</th>
                    <th style="text-align: right;">Jumlah</th>
                    <th style="text-align: right;">Persentase</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>Sangat Jelas</td>
                    <td style="text-align: right;">{{ $data['ambiguity_metrics']['distribution']['very_clear'] }}</td>
                    <td style="text-align: right;">{{ round(($data['ambiguity_metrics']['distribution']['very_clear'] / $data['ambiguity_metrics']['total_tests']) * 100, 1) }}%</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Cukup Jelas</td>
                    <td style="text-align: right;">{{ $data['ambiguity_metrics']['distribution']['mostly_clear'] }}</td>
                    <td style="text-align: right;">{{ round(($data['ambiguity_metrics']['distribution']['mostly_clear'] / $data['ambiguity_metrics']['total_tests']) * 100, 1) }}%</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>Sedang</td>
                    <td style="text-align: right;">{{ $data['ambiguity_metrics']['distribution']['moderate'] }}</td>
                    <td style="text-align: right;">{{ round(($data['ambiguity_metrics']['distribution']['moderate'] / $data['ambiguity_metrics']['total_tests']) * 100, 1) }}%</td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>Agak Ambigu</td>
                    <td style="text-align: right;">{{ $data['ambiguity_metrics']['distribution']['somewhat_ambiguous'] }}</td>
                    <td style="text-align: right;">{{ round(($data['ambiguity_metrics']['distribution']['somewhat_ambiguous'] / $data['ambiguity_metrics']['total_tests']) * 100, 1) }}%</td>
                </tr>
                <tr>
                    <td>5</td>
                    <td>Sangat Ambigu</td>
                    <td style="text-align: right;">{{ $data['ambiguity_metrics']['distribution']['very_ambiguous'] }}</td>
                    <td style="text-align: right;">{{ round(($data['ambiguity_metrics']['distribution']['very_ambiguous'] / $data['ambiguity_metrics']['total_tests']) * 100, 1) }}%</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Laporan Evaluasi AI Assistant - Fakultas Vokasi | Dibuat: {{ $generated_at }}</p>
    </div>
</body>
</html>
