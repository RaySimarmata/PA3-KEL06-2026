# Evaluasi RAGAS - Updated dengan Database & Visualisasi

## 🎉 Update Terbaru

### ✅ Berbasis Data Real (Database)
- Data evaluasi sekarang disimpan di database table `ragas_evaluation_tests`
- 18 skenario pengujian sesuai dokumentasi sudah di-seed ke database
- Data bisa di-update secara dinamis

### 📊 Visualisasi Chart
1. **Pie Chart** - Distribusi Kategori Pengujian
   - Menampilkan proporsi setiap kategori
   - Interactive dengan tooltip
   - Warna gradient yang menarik

2. **Bar Chart** - Perbandingan Metrik RAGAS
   - Visualisasi 6 metrik utama
   - Skala 0-100%
   - Warna berbeda untuk setiap metrik

### 🗄️ Database Structure

**Table:** `ragas_evaluation_tests`

Kolom utama:
- `question` - Pertanyaan/skenario pengujian
- `kategori` - Kategori (Laporan Triwulan, Kuesioner, dll)
- `faithfulness` - Kesesuaian dengan dokumen (0-1)
- `answer_relevancy` - Relevansi jawaban (0-1)
- `context_precision` - Ketepatan konteks (0-1)
- `context_recall` - Kelengkapan konteks (0-1)
- `context_relevancy` - Relevansi konteks (0-1)
- `hallucination_rate` - Tingkat halusinasi (0-1)
- `f1_score` - Harmonic mean precision & recall (0-1)
- `chunks_used` - Jumlah chunk yang digunakan
- `avg_similarity` - Rata-rata similarity score
- `ai_model` - Model AI yang digunakan
- `response_time_ms` - Waktu respons (milliseconds)
- `status` - pending, evaluated, failed

### 🔧 Files Updated

#### 1. Migration
`database/migrations/2026_06_13_201943_create_ragas_evaluation_tests_table.php`
- Membuat struktur table dengan semua metrik RAGAS

#### 2. Model
`app/Models/RAGASEvaluationTest.php`
- Model dengan relationship dan scopes
- Computed property `ragas_score`
- Scopes: `evaluated()`, `byKategori()`

#### 3. Seeder
`database/seeders/RAGASEvaluationSeeder.php`
- 18 skenario pengujian dari dokumentasi
- Data lengkap dengan semua metrik

#### 4. Controller
`app/Http/Controllers/GJM/ModelEvaluationController.php`
- Method `getDatabaseRAGASData()` - Ambil data dari database
- Menghitung averages dan summary metrics
- Generate data untuk charts (category distribution & metrics comparison)
- Fallback ke hardcoded data jika database kosong

#### 5. View
`resources/views/gjm/evaluasi/ragas.blade.php`
- Tambah 2 chart containers (pie & bar)
- Chart.js integration
- Interactive tooltips
- Responsive layout

## 📈 Fitur Charts

### 1. Category Distribution (Doughnut Chart)
```javascript
- Labels: Nama kategori (Kuesioner, Monitoring RPS, dll)
- Data: Jumlah test per kategori
- Colors: Gradient colors untuk setiap kategori
- Tooltip: Menampilkan count & percentage
```

### 2. Metrics Comparison (Bar Chart)
```javascript
- Labels: 6 metrik RAGAS
- Data: Score dalam percentage (0-100%)
- Colors: Warna berbeda per metrik
- Horizontal grid lines untuk readability
```

## 🎨 Visualisasi Features

### Pie Chart
- **Interaktif**: Hover untuk detail
- **Responsive**: Menyesuaikan ukuran layar
- **Legend**: Posisi bottom dengan label jelas
- **Colors**: Gradient purple/blue/pink palette

### Bar Chart
- **Y-Axis**: 0-100% dengan grid lines
- **X-Axis**: Nama metrik
- **Border Radius**: Rounded bars untuk estetika
- **Tooltip**: Menampilkan exact percentage

## 🚀 Cara Menggunakan

### 1. Install Data
```bash
# Jalankan migration (sudah dijalankan)
php artisan migrate

# Seed data evaluasi
php artisan db:seed --class=RAGASEvaluationSeeder
```

### 2. Akses Halaman
1. Login sebagai **GJM**
2. Sidebar → **Evaluasi** → **RAGAS**
3. Data akan dimuat otomatis dari database

### 3. Menambah Data Baru
```php
use App\Models\RAGASEvaluationTest;

RAGASEvaluationTest::create([
    'question' => 'Pertanyaan baru...',
    'kategori' => 'Laporan Triwulan',
    'faithfulness' => 0.92,
    'answer_relevancy' => 0.89,
    'context_precision' => 0.85,
    'context_recall' => 0.90,
    'context_relevancy' => 0.87,
    'hallucination_rate' => 0.08,
    'f1_score' => 0.87,
    'chunks_used' => 5,
    'avg_similarity' => 0.88,
    'ai_model' => 'Groq LLaMA 3.1 8B',
    'response_time_ms' => 1500,
    'status' => 'evaluated'
]);
```

## 📊 Data Flow

```
Database (ragas_evaluation_tests)
    ↓
ModelEvaluationController::getDatabaseRAGASData()
    ↓
Calculate Averages & Summary
    ↓
Generate Chart Data (category_distribution, metrics_comparison)
    ↓
JSON Response to Frontend
    ↓
JavaScript renders:
    - Summary metrics cards
    - Pie chart (categories)
    - Bar chart (metrics)
    - Table (scenarios)
```

## 🎯 Metrik yang Dihitung

### Dari Database:
1. **Average Faithfulness** - Rata-rata dari semua test
2. **Average Hallucination** - Rata-rata tingkat halusinasi
3. **Average Context Precision** - Ketepatan konteks
4. **Average Context Recall** - Kelengkapan retrieval
5. **Average F1 Score** - Harmonic mean
6. **RAGAS Score** - Overall score (rata-rata 5 metrik utama)

### Per Kategori:
- Count tests per kategori
- Average faithfulness per kategori
- Average hallucination per kategori
- Average RAGAS score per kategori

## 🔄 Auto-Update

Sistem otomatis akan:
1. ✅ Mengambil data terbaru dari database
2. ✅ Menghitung ulang semua averages
3. ✅ Update chart secara real-time
4. ✅ Menampilkan jumlah test yang dievaluasi

## 📱 Responsive Design

- ✅ Desktop: 2 chart side by side
- ✅ Tablet: Chart stack vertical
- ✅ Mobile: Full width charts

## 🎨 Color Palette

### Metrics Cards:
- Faithfulness: Green gradient (#11998e → #38ef7d)
- Hallucination: Red-orange (#ee0979 → #ff6a00)
- Precision: Blue (#4facfe → #00f2fe)
- Recall: Pink (#f093fb → #f5576c)
- F1: Pink-yellow (#fa709a → #fee140)
- RAGAS Score: Purple (#667eea → #764ba2)

### Charts:
- Category Pie: 8 gradient colors
- Metrics Bar: Individual colors per metric

## 🔮 Future Enhancements

- [ ] Filter by date range
- [ ] Export charts as PNG/SVG
- [ ] Real-time updates via WebSocket
- [ ] Comparison between periods
- [ ] Drill-down by kategori
- [ ] Advanced analytics (trend analysis)

## 📝 Notes

- Data source ditampilkan di info box (Database vs Hardcoded)
- Jika database kosong, akan fallback ke hardcoded data
- Semua chart menggunakan Chart.js v4
- Bootstrap 5 untuk responsive grid

---

**Status:** ✅ Fully Implemented & Ready for Production

**Last Updated:** June 13, 2026
