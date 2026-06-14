# Dashboard Visualization - Analitik Kuesioner Mahasiswa

## Overview

Hasil analisis yang tersimpan pada MongoDB ditampilkan kepada pengguna melalui **dashboard berbasis web** yang dikembangkan menggunakan Laravel dan Chart.js. Dashboard ini menyediakan visualisasi data yang interaktif dan mudah dipahami oleh pengguna baik dari **GKM (Gugus Kendali Mutu)** maupun **GJM (Gugus Jaminan Mutu)**.

Dashboard dirancang dengan prinsip:
- **User-Centric**: Interface intuitif untuk berbagai level user
- **Real-time**: Data updated dari MongoDB secara real-time
- **Interactive**: Chart dengan fitur zoom, filter, dan drill-down
- **Responsive**: Optimal di desktop, tablet, dan mobile

---

## 1. Technology Stack

### 1.1 Frontend Technologies

| Technology | Version | Purpose |
|------------|---------|---------|
| **Laravel Blade** | 10.x | Template engine untuk rendering HTML |
| **Chart.js** | 4.4.0 | Library JavaScript untuk visualisasi chart |
| **Bootstrap** | 5.3.0 | CSS framework untuk responsive design |
| **Bootstrap Icons** | 1.11.x | Icon library |
| **Alpine.js** | 3.x | Lightweight JavaScript framework |

### 1.2 Backend Technologies

| Technology | Version | Purpose |
|------------|---------|---------|
| **Laravel** | 10.x | Backend framework |
| **MongoDB PHP Extension** | latest | MongoDB database driver |
| **Laravel MongoDB** | 4.x | Eloquent integration untuk MongoDB |

### 1.3 Chart.js Setup

**File:** `resources/views/layouts/app.blade.php`

```html
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

<!-- Optional Plugins -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2"></script>
```

---

## 2. Dashboard Architecture

### 2.1 Dashboard Flow

```
┌────────────────────────────────────────────────────────────────┐
│                    DASHBOARD ARCHITECTURE                       │
├────────────────────────────────────────────────────────────────┤
│                                                                 │
│  USER (GKM/GJM)                                                │
│       ↓                                                         │
│  1. BROWSER REQUEST                                             │
│     └─ GET /gjm/dashboard?semester=1&tahun=2025/2026          │
│       ↓                                                         │
│  2. LARAVEL CONTROLLER                                          │
│     └─ DashboardController@index()                            │
│       ├─ Query MongoDB (hasil_analisis_lengkap)               │
│       ├─ Aggregate statistics                                  │
│       ├─ Prepare chart data                                    │
│       └─ Return view with data                                 │
│       ↓                                                         │
│  3. BLADE VIEW RENDER                                           │
│     └─ dashboard/index.blade.php                               │
│       ├─ Render HTML structure                                 │
│       ├─ Inject data to JavaScript                             │
│       └─ Initialize Chart.js                                   │
│       ↓                                                         │
│  4. CHART.JS VISUALIZATION                                      │
│     └─ JavaScript executes on browser                          │
│       ├─ Create charts from data                               │
│       ├─ Add interactivity (zoom, tooltip)                     │
│       └─ Render to canvas elements                             │
│       ↓                                                         │
│  5. USER INTERACTION                                            │
│     ├─ Filter data (periode, prodi)                            │
│     ├─ Hover for details (tooltip)                             │
│     ├─ Click chart for drill-down                              │
│     └─ Export report (PDF/Excel)                               │
│                                                                 │
└────────────────────────────────────────────────────────────────┘
```

---

## 3. Visualisasi Dashboard

### 3.1 Overview: 5 Visualisasi Utama

Dashboard menyediakan 5 jenis visualisasi utama yang menjawab key questions dari stakeholder:


| # | Visualisasi | Tipe Chart | Purpose | Key Insight |
|---|-------------|------------|---------|-------------|
| 1 | Tren Kepuasan Mahasiswa | Line Chart | Melihat perkembangan dari waktu ke waktu | Trend analysis |
| 2 | Top 5 Dosen Terbaik | Bar Chart (Horizontal) | Apresiasi dan benchmark | Best practices |
| 3 | Dosen yang Perlu Evaluasi | Bar Chart (Horizontal) | Identifikasi area perbaikan | Action items |
| 4 | Statistik Per Program Studi | Bar Chart (Vertical) | Perbandingan antar prodi | Comparative analysis |
| 5 | Insight AI | Card/Text | Rangkuman dan rekomendasi otomatis | AI-driven insights |

---

## 4. Visualisasi 1: Tren Kepuasan Mahasiswa

### 4.1 Deskripsi

Grafik **line chart** yang menampilkan perkembangan nilai kepuasan mahasiswa dari waktu ke waktu sehingga tren peningkatan atau penurunan dapat teridentifikasi dengan mudah.

### 4.2 Controller Logic

**File:** `app/Http/Controllers/GJM/DashboardController.php`

```php
public function getTrendKepuasan(Request $request)
{
    $prodi = $request->input('prodi', 'SEMUA');
    $tahunStart = $request->input('tahun_start', '2023/2024');
    $tahunEnd = $request->input('tahun_end', '2025/2026');
    
    // Query MongoDB
    $query = HasilAnalisisMongo::query();
    
    if ($prodi !== 'SEMUA') {
        $query->where('prodi', $prodi);
    }
    
    $query->whereBetween('tahun', [$tahunStart, $tahunEnd]);
    
    // Aggregate by periode
    $results = $query->raw(function($collection) {
        return $collection->aggregate([
            [
                '$group' => [
                    '_id' => [
                        'tahun' => '$tahun',
                        'semester' => '$semester'
                    ],
                    'rata_rata_kepuasan' => ['$avg' => '$persentase_kepuasan'],
                    'jumlah_kuesioner' => ['$sum' => 1]
                ]
            ],
            ['$sort' => ['_id.tahun' => 1, '_id.semester' => 1]]
        ]);
    });
    
    return response()->json($results);
}
```

### 4.3 Chart Implementation (Chart.js)

**File:** `resources/views/gjm/dashboard/index.blade.php`

```html
<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-graph-up"></i> Tren Kepuasan Mahasiswa</h5>
    </div>
    <div class="card-body">
        <canvas id="trendChart" height="80"></canvas>
    </div>
</div>

<script>
// Data dari controller
const trendData = @json($trendData);

// Transform data untuk Chart.js
const labels = trendData.map(d => `${d._id.tahun} Sem ${d._id.semester}`);
const values = trendData.map(d => d.rata_rata_kepuasan.toFixed(2));

// Create chart
const ctx = document.getElementById('trendChart').getContext('2d');
const trendChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Persentase Kepuasan (%)',
            data: values,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return `Kepuasan: ${context.parsed.y}%`;
                    }
                }
            },
            // Zoom plugin
            zoom: {
                zoom: {
                    wheel: { enabled: true },
                    pinch: { enabled: true },
                    mode: 'x'
                }
            }
        },
        scales: {
            y: {
                beginAtZero: false,
                min: 60,
                max: 100,
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            }
        }
    }
});
</script>
```

### 4.4 Output Example

```
📈 Tren Kepuasan Mahasiswa
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
100% ┤                                    ●─●
 90% ┤                         ●─●─●─●─●─●
 80% ┤              ●─●─●─●─●─●
 70% ┤    ●─●─●─●─●─●
 60% ┼────────────────────────────────────────
     2023/1  2023/2  2024/1  2024/2  2025/1  2025/2

Trend: ↗ Meningkat 15% dalam 2 tahun
```

---

## 5. Visualisasi 2: Top 5 Dosen Terbaik

### 5.1 Deskripsi

Visualisasi **horizontal bar chart** yang menampilkan dosen dengan nilai kuesioner tertinggi sebagai bentuk apresiasi dan referensi bagi program studi.

### 5.2 Controller Logic

```php
public function getTop5DosenTerbaik(Request $request)
{
    $periode = $request->input('periode', '2025/2026');
    $semester = $request->input('semester', '1');
    
    $results = HasilAnalisisMongo::raw(function($collection) use ($periode, $semester) {
        return $collection->aggregate([
            [
                '$match' => [
                    'tahun' => $periode,
                    'semester' => $semester
                ]
            ],
            [
                '$group' => [
                    '_id' => '$dosen_pengajar',
                    'rata_rata' => ['$avg' => '$persentase_kepuasan'],
                    'total_kuesioner' => ['$sum' => 1],
                    'total_responden' => ['$sum' => '$total_jawaban']
                ]
            ],
            ['$sort' => ['rata_rata' => -1]],
            ['$limit' => 5]
        ]);
    });
    
    return response()->json($results);
}
```

### 5.3 Chart Implementation

```html
<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-trophy"></i> Top 5 Dosen Terbaik</h5>
    </div>
    <div class="card-body">
        <canvas id="topDosenChart" height="100"></canvas>
    </div>
</div>

<script>
const topDosen = @json($topDosen);

const dosenLabels = topDosen.map(d => d._id || 'Unknown');
const dosenScores = topDosen.map(d => d.rata_rata.toFixed(2));

const topDosenChart = new Chart(
    document.getElementById('topDosenChart').getContext('2d'),
    {
        type: 'bar',
        data: {
            labels: dosenLabels,
            datasets: [{
                label: 'Kepuasan (%)',
                data: dosenScores,
                backgroundColor: [
                    '#10b981',
                    '#14b8a6',
                    '#06b6d4',
                    '#3b82f6',
                    '#6366f1'
                ],
                borderRadius: 8
            }]
        },
        options: {
            indexAxis: 'y',  // Horizontal bar
            responsive: true,
            plugins: {
                legend: { display: false },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    formatter: (value) => value + '%'
                }
            },
            scales: {
                x: {
                    beginAtZero: false,
                    min: 80,
                    max: 100
                }
            }
        }
    }
);
</script>
```

---

## 6. Visualisasi 3: Dosen yang Perlu Evaluasi

### 6.1 Deskripsi

Identifikasi dosen dengan nilai kuesioner di bawah standar (< 75%) yang memerlukan perhatian dan tindak lanjut.

### 6.2 Controller Logic

```php
public function getDosenPerluEvaluasi(Request $request)
{
    $periode = $request->input('periode', '2025/2026');
    $semester = $request->input('semester', '1');
    $threshold = 75.0;  // Standard minimum
    
    $results = HasilAnalisisMongo::raw(function($collection) use ($periode, $semester, $threshold) {
        return $collection->aggregate([
            [
                '$match' => [
                    'tahun' => $periode,
                    'semester' => $semester
                ]
            ],
            [
                '$group' => [
                    '_id' => '$dosen_pengajar',
                    'rata_rata' => ['$avg' => '$persentase_kepuasan'],
                    'total_kuesioner' => ['$sum' => 1]
                ]
            ],
            [
                '$match' => [
                    'rata_rata' => ['$lt' => $threshold]
                ]
            ],
            ['$sort' => ['rata_rata' => 1]],
            ['$limit' => 5]
        ]);
    });
    
    return response()->json($results);
}
```

### 6.3 Chart Implementation

```html
<div class="card border-warning">
    <div class="card-header bg-warning text-white">
        <h5><i class="bi bi-exclamation-triangle"></i> Dosen yang Perlu Evaluasi</h5>
    </div>
    <div class="card-body">
        <canvas id="evaluasiDosenChart" height="100"></canvas>
    </div>
</div>

<script>
const evaluasiDosen = @json($evaluasiDosen);

if (evaluasiDosen.length === 0) {
    document.getElementById('evaluasiDosenChart').parentElement.innerHTML = 
        '<div class="alert alert-success">Tidak ada dosen yang perlu evaluasi khusus. Semua di atas standar!</div>';
} else {
    const evalLabels = evaluasiDosen.map(d => d._id);
    const evalScores = evaluasiDosen.map(d => d.rata_rata.toFixed(2));
    
    new Chart(
        document.getElementById('evaluasiDosenChart').getContext('2d'),
        {
            type: 'bar',
            data: {
                labels: evalLabels,
                datasets: [{
                    label: 'Kepuasan (%)',
                    data: evalScores,
                    backgroundColor: '#ef4444',
                    borderRadius: 8
                }]
            },
            options: {
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        min: 0,
                        max: 100,
                        ticks: {
                            callback: (value) => value + '%'
                        }
                    }
                }
            }
        }
    );
}
</script>
```

---

## 7. Visualisasi 4: Statistik Per Program Studi

### 7.1 Deskripsi

Perbandingan kinerja antar program studi berdasarkan hasil kuesioner, memudahkan identifikasi program studi dengan performa terbaik dan yang perlu perhat

ian.

### 7.2 Controller Logic

```php
public function getStatistikPerProdi(Request $request)
{
    $periode = $request->input('periode', '2025/2026');
    $semester = $request->input('semester', '1');
    
    $results = HasilAnalisisMongo::raw(function($collection) use ($periode, $semester) {
        return $collection->aggregate([
            [
                '$match' => [
                    'tahun' => $periode,
                    'semester' => $semester
                ]
            ],
            [
                '$group' => [
                    '_id' => '$prodi',
                    'rata_rata_kepuasan' => ['$avg' => '$persentase_kepuasan'],
                    'total_kuesioner' => ['$sum' => 1],
                    'total_responden' => ['$sum' => '$total_jawaban'],
                    'sentiment_positive' => [
                        '$sum' => [
                            '$cond' => [
                                ['$eq' => ['$sentiment', 'positive']],
                                1, 0
                            ]
                        ]
                    ]
                ]
            ],
            ['$sort' => ['_id' => 1]]
        ]);
    });
    
    return response()->json($results);
}
```

### 7.3 Chart Implementation

```html
<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-diagram-3"></i> Perbandingan Per Program Studi</h5>
    </div>
    <div class="card-body">
        <canvas id="prodiChart" height="80"></canvas>
    </div>
</div>

<script>
const prodiData = @json($prodiData);

const prodiLabels = prodiData.map(d => d._id);
const prodiScores = prodiData.map(d => d.rata_rata_kepuasan.toFixed(2));

new Chart(
    document.getElementById('prodiChart').getContext('2d'),
    {
        type: 'bar',
        data: {
            labels: prodiLabels,
            datasets: [{
                label: 'Kepuasan Mahasiswa (%)',
                data: prodiScores,
                backgroundColor: '#8b5cf6',
                borderRadius: 8,
                barThickness: 60
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        afterLabel: function(context) {
                            const index = context.dataIndex;
                            const data = prodiData[index];
                            return [
                                `Kuesioner: ${data.total_kuesioner}`,
                                `Responden: ${data.total_responden}`,
                                `Positive: ${data.sentiment_positive}`
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    min: 70,
                    max: 100,
                    ticks: {
                        callback: (value) => value + '%'
                    }
                }
            }
        }
    }
);
</script>
```

---

## 8. Visualisasi 5: Insight Otomatis Berbasis AI

### 8.1 Deskripsi

Rangkuman analisis dan rekomendasi yang dihasilkan secara otomatis oleh AI Assistant berdasarkan pola data kuesioner.

### 8.2 Controller Logic

```php
public function getAIInsights(Request $request)
{
    $periode = $request->input('periode', '2025/2026');
    $semester = $request->input('semester', '1');
    $prodi = $request->input('prodi', 'SEMUA');
    
    // Collect statistics
    $stats = $this->getStatisticsForAI($periode, $semester, $prodi);
    
    // Call AI service
    $aiService = app(\App\Services\UnifiedAIService::class);
    
    $prompt = "Berdasarkan data kuesioner berikut:\n" .
              "- Total Kuesioner: {$stats['total_kuesioner']}\n" .
              "- Rata-rata Kepuasan: {$stats['rata_rata']}%\n" .
              "- Trend: {$stats['trend']}\n" .
              "- Top Issues: " . implode(', ', $stats['top_issues']) . "\n\n" .
              "Berikan 3 insight utama dan 3 rekomendasi actionable.";
    
    $response = $aiService->generateChat([
        ['role' => 'system', 'content' => 'Anda adalah AI analyst untuk evaluasi pembelajaran.'],
        ['role' => 'user', 'content' => $prompt]
    ]);
    
    return response()->json([
        'insights' => $this->parseAIInsights($response['text']),
        'generated_at' => now()
    ]);
}
```

### 8.3 Display Implementation

```html
<div class="card border-primary">
    <div class="card-header bg-primary text-white">
        <h5><i class="bi bi-robot"></i> AI Insights & Recommendations</h5>
    </div>
    <div class="card-body">
        <div id="aiInsightsContainer">
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Generating insights...</p>
            </div>
        </div>
    </div>
</div>

<script>
// Fetch AI insights
fetch('/api/dashboard/ai-insights?periode={{$periode}}&semester={{$semester}}')
    .then(response => response.json())
    .then(data => {
        const container = document.getElementById('aiInsightsContainer');
        
        let html = '<div class="insights-list">';
        
        // Key Insights
        html += '<h6 class="fw-bold mb-3"><i class="bi bi-lightbulb"></i> Key Insights:</h6>';
        html += '<ul class="list-unstyled">';
        data.insights.key_insights.forEach(insight => {
            html += `<li class="mb-2"><i class="bi bi-check-circle text-success"></i> ${insight}</li>`;
        });
        html += '</ul>';
        
        // Recommendations
        html += '<h6 class="fw-bold mb-3 mt-4"><i class="bi bi-clipboard-check"></i> Recommendations:</h6>';
        html += '<ul class="list-unstyled">';
        data.insights.recommendations.forEach(rec => {
            html += `<li class="mb-2"><i class="bi bi-arrow-right-circle text-primary"></i> ${rec}</li>`;
        });
        html += '</ul>';
        
        html += '</div>';
        
        container.innerHTML = html;
    })
    .catch(error => {
        document.getElementById('aiInsightsContainer').innerHTML = 
            '<div class="alert alert-warning">Failed to generate insights. Please try again.</div>';
    });
</script>
```

---

## 9. Interactive Features

### 9.1 Filter & Real-time Update

```html
<!-- Filter Panel -->
<div class="card mb-4">
    <div class="card-body">
        <form id="filterForm">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Periode</label>
                    <select name="periode" class="form-select" onchange="updateDashboard()">
                        <option value="2023/2024">2023/2024</option>
                        <option value="2024/2025">2024/2025</option>
                        <option value="2025/2026" selected>2025/2026</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-select" onchange="updateDashboard()">
                        <option value="1">Ganjil</option>
                        <option value="2">Genap</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Program Studi</label>
                    <select name="prodi" class="form-select" onchange="updateDashboard()">
                        <option value="SEMUA">Semua Prodi</option>
                        <option value="D4 TRPL">D4 TRPL</option>
                        <option value="D4 TI">D4 TI</option>
                        <option value="D3 NM">D3 NM</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-primary w-100" onclick="updateDashboard()">
                        <i class="bi bi-arrow-clockwise"></i> Update
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function updateDashboard() {
    const formData = new FormData(document.getElementById('filterForm'));
    const params = new URLSearchParams(formData);
    
    // Show loading
    document.querySelectorAll('canvas').forEach(canvas => {
        canvas.style.opacity = '0.5';
    });
    
    // Fetch updated data
    fetch('/api/dashboard/update?' + params.toString())
        .then(response => response.json())
        .then(data => {
            // Update all charts
            updateTrendChart(data.trend);
            updateTopDosenChart(data.topDosen);
            updateEvaluasiChart(data.evaluasi);
            updateProdiChart(data.prodi);
            
            // Remove loading
            document.querySelectorAll('canvas').forEach(canvas => {
                canvas.style.opacity = '1';
            });
        });
}
</script>
```

### 9.2 Zoom & Pan Feature

```javascript
// Enable zoom plugin
Chart.register(zoomPlugin);

const chartOptions = {
    plugins: {
        zoom: {
            zoom: {
                wheel: {
                    enabled: true,
                    speed: 0.1
                },
                pinch: {
                    enabled: true
                },
                mode: 'x'
            },
            pan: {
                enabled: true,
                mode: 'x'
            }
        }
    }
};
```

### 9.3 Tooltip Customization

```javascript
const tooltip = {
    enabled: true,
    backgroundColor: 'rgba(0, 0, 0, 0.8)',
    titleColor: '#fff',
    bodyColor: '#fff',
    borderColor: '#3b82f6',
    borderWidth: 1,
    callbacks: {
        title: function(tooltipItems) {
            return `${tooltipItems[0].label}`;
        },
        label: function(context) {
            return `Kepuasan: ${context.parsed.y}%`;
        },
        afterLabel: function(context) {
            // Additional info
            return [
                `Responden: ${context.dataset.responden[context.dataIndex]}`,
                `Kuesioner: ${context.dataset.jumlah[context.dataIndex]}`
            ];
        }
    }
};
```

---

## 10. Export & Report Generation

### 10.1 Export Chart as Image

```javascript
function exportChartAsImage(chartId, filename) {
    const canvas = document.getElementById(chartId);
    const url = canvas.toDataURL('image/png');
    
    const link = document.createElement('a');
    link.download = filename + '.png';
    link.href = url;
    link.click();
}
```

### 10.2 Export to PDF

```php
// Controller
public function exportDashboardPDF(Request $request)
{
    $data = $this->getDashboardData($request);
    
    $pdf = PDF::loadView('gjm.dashboard.pdf', $data);
    return $pdf->download('dashboard-' . now()->format('Y-m-d') . '.pdf');
}
```

### 10.3 Export to Excel

```php
use Maatwebsite\Excel\Facades\Excel;

public function exportDashboardExcel(Request $request)
{
    $data = $this->getDashboardData($request);
    
    return Excel::download(
        new DashboardExport($data),
        'dashboard-' . now()->format('Y-m-d') . '.xlsx'
    );
}
```

---

## 11. Performance Optimization

### 11.1 Lazy Loading Charts

```javascript
// Use Intersection Observer
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const chartId = entry.target.id;
            loadChart(chartId);
            observer.unobserve(entry.target);
        }
    });
});

document.querySelectorAll('.chart-container').forEach(container => {
    observer.observe(container);
});
```

### 11.2 Data Caching

```php
// Cache dashboard data for 5 minutes
public function index(Request $request)
{
    $cacheKey = 'dashboard_' . md5(json_encode($request->all()));
    
    $data = Cache::remember($cacheKey, 300, function() use ($request) {
        return [
            'trend' => $this->getTrendKepuasan($request),
            'topDosen' => $this->getTop5DosenTerbaik($request),
            'evaluasi' => $this->getDosenPerluEvaluasi($request),
            'prodi' => $this->getStatistikPerProdi($request)
        ];
    });
    
    return view('gjm.dashboard.index', $data);
}
```

### 11.3 Progressive Enhancement

```javascript
// Load critical charts first
window.addEventListener('load', () => {
    // Priority 1: KPI cards
    loadKPICards();
    
    // Priority 2: Main trend chart
    setTimeout(() => loadTrendChart(), 100);
    
    // Priority 3: Other charts
    setTimeout(() => {
        loadTopDosenChart();
        loadProdiChart();
    }, 500);
    
    // Priority 4: AI insights (async)
    setTimeout(() => loadAIInsights(), 1000);
});
```

---

## 12. Responsive Design

### 12.1 Mobile Optimization

```css
/* Responsive chart container */
.chart-container {
    position: relative;
    height: 300px;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .chart-container {
        height: 250px;
    }
    
    /* Stack charts vertically */
    .chart-grid {
        display: block;
    }
    
    /* Adjust font sizes */
    .chart-title {
        font-size: 0.9rem;
    }
}
```

### 12.2 Touch Gestures

```javascript
// Enable touch gestures for mobile
const chartOptions = {
    plugins: {
        zoom: {
            zoom: {
                wheel: { enabled: false }, // Disable on mobile
                pinch: { enabled: true },  // Enable pinch zoom
                mode: 'x'
            },
            pan: {
                enabled: true,
                mode: 'x'
            }
        }
    }
};
```

---

## 13. Accessibility

### 13.1 ARIA Labels

```html
<canvas 
    id="trendChart" 
    role="img" 
    aria-label="Line chart showing student satisfaction trend over time"
></canvas>
```

### 13.2 Keyboard Navigation

```javascript
// Add keyboard support
document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') {
        // Navigate to previous period
        navigatePeriod(-1);
    } else if (e.key === 'ArrowRight') {
        // Navigate to next period
        navigatePeriod(1);
    }
});
```

---

## 14. Kesimpulan

Dashboard visualization sistem kuesioner menyediakan:

✅ **5 Visualisasi Utama**: Tren, Top Dosen, Evaluasi, Prodi, AI Insights  
✅ **Interaktif**: Zoom, pan, filter, tooltip  
✅ **Real-time**: Data from MongoDB updated instantly  
✅ **Responsive**: Optimal di semua devices  
✅ **Export**: PDF, Excel, Image  
✅ **AI-Powered**: Automatic insights dan recommendations  

**Technology Stack:**
- Laravel 10.x (Backend)
- Chart.js 4.4.0 (Visualization)
- MongoDB (Data source)
- Bootstrap 5.3 (UI Framework)

---

**Dokumentasi dibuat:** 13 Juni 2026  
**Versi:** 1.0  
**Last Updated:** 13 Juni 2026
