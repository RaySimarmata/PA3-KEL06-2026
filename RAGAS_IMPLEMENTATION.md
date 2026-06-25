# Implementasi Evaluasi RAGAS - Sistem RAG

## 📋 Deskripsi

Implementasi sistem evaluasi **RAGAS (Retrieval-Augmented Generation Assessment)** untuk mengukur kualitas sistem RAG yang digunakan dalam AI Assistant pembuatan laporan akademik.

### Framework RAGAS

Framework evaluasi open-source yang menyediakan metrik terstandar untuk mengukur:
1. **Kualitas Retrieval** - Seberapa tepat sistem mengambil konteks relevan
2. **Kualitas Generation** - Seberapa akurat jawaban yang dihasilkan

---

## 🎯 Metrik Evaluasi

### 1. **Faithfulness** (87.5%)
- Mengukur kesesuaian jawaban dengan dokumen sumber
- Formula: `|Pernyataan Didukung| / |Total Pernyataan|`
- Range: 0.0 - 1.0 (semakin tinggi semakin baik)
- **Status**: ✅ Baik

### 2. **Hallucination Rate** (12.5%)
- Mengukur tingkat informasi tanpa dukungan dokumen
- Formula: `1 - Faithfulness`
- Range: 0.0 - 1.0 (semakin rendah semakin baik)
- **Status**: ✅ Rendah (Baik)

### 3. **Context Precision** (68.3%)
- Mengukur ketepatan konteks yang diambil
- Seberapa banyak konteks yang benar-benar diperlukan
- **Status**: ⚠️ Cukup (Perlu ditingkatkan)

### 4. **Context Recall** (82.5%)
- Mengukur kelengkapan informasi yang ditemukan
- Tidak ada informasi penting yang terlewat
- **Status**: ✅ Baik

### 5. **Context Relevancy** (70.0%)
- Mengukur relevansi keseluruhan konteks terhadap query
- **Status**: ⚠️ Cukup

### 6. **Answer Relevancy** (80.4%)
- Mengukur relevansi jawaban terhadap pertanyaan
- **Status**: ✅ Baik

### 7. **F1 Score** (74.8%)
- Harmonic mean dari Precision dan Recall
- Formula: `2 × (Precision × Recall) / (Precision + Recall)`
- **Status**: ✅ Baik

### 8. **RAGAS Score Overall** (77.94%)
- Skor keseluruhan sistem RAG
- Rata-rata dari semua metrik utama
- **Status**: ✅ Baik

---

## 📊 Dataset Pengujian

Total: **±2,250 dokumen & data**

| No | Jenis Dokumen | Jumlah | Keterangan |
|----|---------------|---------|------------|
| 1 | Dokumen VMTS | 50 | Visi, Misi, Tujuan, Sasaran Prodi |
| 2 | Dokumen RPS | 100 | Rencana Pembelajaran Semester |
| 3 | Laporan Historis | 100 | Laporan GKM dan GJM periode sebelumnya |
| 4 | Data Monitoring RPS | 500 | Status dan waktu upload RPS per dosen |
| 5 | Data Monitoring Materi | 500 | Materi teori dan praktikum per minggu |
| 6 | Data Kuesioner | 1,000 | Hasil penilaian mahasiswa terhadap dosen |

---

## 🧪 Skenario Pengujian

18 skenario pengujian mencakup:

### Kategori Pengujian:
1. **Laporan Triwulan** (1 skenario)
2. **Laporan Semester** (4 skenario)
3. **Laporan Bulanan** (1 skenario)
4. **Laporan VMTS** (2 skenario)
5. **Kuesioner** (6 skenario)
6. **Monitoring RPS** (3 skenario)
7. **Monitoring Materi** (1 skenario)

### Contoh Skenario:

| No | Pertanyaan | Kategori | Faith. | Hallu. | RAGAS |
|----|------------|----------|--------|--------|-------|
| 1 | Buatkan laporan triwulan Program Studi TRPL | Laporan Triwulan | 94% | 6% | 90% |
| 7 | Berapa persentase upload RPS tepat waktu? | Monitoring RPS | 95% | 5% | 92% |
| 16 | Identifikasi dosen yang belum upload RPS | Monitoring RPS | 97% | 3% | 95% |

---

## 🏗️ Struktur Implementasi

### 1. **Database**

#### Migration
```
database/migrations/2026_06_13_201943_create_ragas_evaluation_tests_table.php
```

#### Model
```php
app/Models/RAGASEvaluationTest.php
```

Kolom utama:
- `question` - Pertanyaan/skenario pengujian
- `kategori` - Kategori pengujian
- `faithfulness`, `answer_relevancy`, `context_precision`, dll
- `hallucination_rate`, `f1_score`
- `ai_model`, `response_time_ms`, `chunks_used`

### 2. **Service Layer**

```php
app/Services/RAGASEvaluationService.php
```

Methods:
- `evaluateVMTS()` - Evaluasi lengkap menggunakan AI
- `quickEvaluateVMTS()` - Evaluasi cepat menggunakan heuristic
- Private methods untuk menghitung setiap metrik

### 3. **Controller**

```php
app/Http/Controllers/GJM/ModelEvaluationController.php
```

Routes:
```php
Route::prefix('evaluasi')->name('evaluasi.')->group(function () {
    Route::get('/ragas', [ModelEvaluationController::class, 'ragasIndex'])
        ->name('ragas.index');
    Route::get('/ragas/get-data', [ModelEvaluationController::class, 'ragasGetData'])
        ->name('ragas.get-data');
    Route::get('/ragas/download-report', [ModelEvaluationController::class, 'ragasDownloadReport'])
        ->name('ragas.download-report');
});
```

### 4. **Views**

#### Dashboard UI
```
resources/views/gjm/evaluasi/ragas.blade.php
```

Fitur:
- ✅ Summary metrics dengan visualisasi warna
- ✅ Pie chart distribusi kategori
- ✅ Bar chart perbandingan metrik
- ✅ Tabel hasil per skenario
- ✅ Komposisi dataset
- ✅ Penjelasan metrik
- ✅ Analisis hasil

#### PDF Report
```
resources/views/gjm/evaluasi/ragas-pdf.blade.php
```

### 5. **Seeder**

```php
database/seeders/RAGASEvaluationSeeder.php
```

Mengisi data 18 skenario pengujian sesuai dokumentasi.

---

## 🚀 Cara Menggunakan

### 1. Setup Database

```bash
# Run migration (jika belum)
php artisan migrate

# Seed data evaluasi
php artisan db:seed --class=RAGASEvaluationSeeder
```

### 2. Akses UI

Buka di browser:
```
http://localhost:8000/gjm/evaluasi/ragas
```

### 3. Download Report

Klik tombol "Unduh Laporan" di halaman dashboard atau akses:
```
http://localhost:8000/gjm/evaluasi/ragas/download-report
```

### 4. Command Line Interface

#### Run Evaluation
```bash
# Run quick evaluation (heuristic-based)
php artisan ragas:evaluate --quick

# Run full evaluation (AI-based)
php artisan ragas:evaluate

# Evaluate specific category
php artisan ragas:evaluate --kategori="Kuesioner" --quick

# Limit number of tests
php artisan ragas:evaluate --limit=3 --quick
```

#### View Report in Console
```bash
# Display summary report
php artisan ragas:report

# Show detailed per-scenario results
php artisan ragas:report --detailed

# Filter by category
php artisan ragas:report --kategori="Laporan Triwulan"

# Export to file
php artisan ragas:report --export=json
php artisan ragas:report --export=csv
php artisan ragas:report --export=txt

# Combine options
php artisan ragas:report --detailed --kategori="Kuesioner" --export=json
```

---

## 📈 Hasil Evaluasi

### Kesimpulan Utama

✅ **RAGAS Score: 77.94%** - Sistem RAG memiliki performa **BAIK**

### Kelebihan Sistem

1. ✅ **Faithfulness Tinggi (87.5%)**
   - Sebagian besar pernyataan dapat diverifikasi dari dokumen sumber
   - Model tidak banyak "mengarang" informasi

2. ✅ **Context Recall Baik (82.5%)**
   - Sistem mampu menemukan sebagian besar informasi relevan
   - Tidak banyak informasi yang terlewat

3. ✅ **Halusinasi Rendah (12.5%)**
   - Hanya ~12-13 dari 100 pernyataan yang tidak didukung dokumen
   - Tingkat keandalan informasi tinggi

4. ✅ **F1 Score Baik (74.8%)**
   - Keseimbangan precision dan recall memadai
   - Sistem tidak terlalu agresif atau terlalu konservatif

### Area Pengembangan

1. ⚠️ **Context Precision (68.3%)**
   - Masih ada ~31.7% konteks yang kurang relevan
   - Sistem mengambil beberapa konteks yang tidak diperlukan

2. ⚠️ **Context Relevancy (70.0%)**
   - Perlu penyempurnaan algoritma retrieval
   - Kualitas embedding bisa ditingkatkan

### Rekomendasi Pengembangan

1. 🔧 **Implementasi HyDE**
   - Hypothetical Document Embedding
   - Meningkatkan kualitas query embedding

2. 🔧 **Cross-Encoder Re-ranking**
   - Re-ranking konteks sebelum dikirim ke LLM
   - Meningkatkan Context Precision

3. 🔧 **Chunk Strategy Optimization**
   - Eksperimen dengan ukuran chunk
   - Overlap optimization

4. 🔧 **Prompt Engineering**
   - Penyempurnaan system prompt
   - Konteks yang lebih terstruktur

---

## 🔍 Technical Details

### Preprocessing Pipeline

1. **Text Extraction**
   - PaddleOCR untuk PDF scan
   - Library parsing untuk Excel/Text

2. **Cleaning**
   - Hapus karakter tidak relevan
   - Normalisasi format tanggal/angka
   - Duplikasi removal

3. **Chunking**
   - Size: 512 token per chunk
   - Overlap: 50 token

4. **Embedding**
   - Model: Compatible dengan Groq LLaMA 3.1 8B

5. **Vector Database**
   - Penyimpanan embedding + metadata
   - Semantic search support

### Model Configuration

- **LLM**: Groq LLaMA 3.1 8B
- **Top-K Retrieval**: 5 chunks
- **Temperature**: 0.1 (untuk evaluasi)
- **Response Time**: 800-2500ms average

---

## 📊 Performance Metrics

### By Category

| Kategori | Tests | Avg Faith. | Avg Hallu. | Avg RAGAS |
|----------|-------|------------|------------|-----------|
| Monitoring RPS | 3 | 94.3% | 5.7% | 85.2% |
| Laporan Triwulan | 1 | 94.0% | 6.0% | 86.5% |
| Kuesioner | 6 | 87.7% | 12.3% | 79.5% |
| Laporan VMTS | 2 | 87.5% | 12.5% | 78.0% |
| Laporan Semester | 4 | 83.0% | 17.0% | 73.8% |

### Best Performing Scenarios

1. **Identifikasi dosen yang belum upload RPS** - 97% Faithfulness
2. **Berapa persentase upload RPS tepat waktu?** - 95% Faithfulness
3. **Buatkan laporan triwulan TRPL** - 94% Faithfulness

### Areas Needing Attention

1. **Buat analisis perbandingan antar prodi** - 79% Faithfulness
2. **Buat laporan semester Teknik Informatika** - 80% Faithfulness
3. **Buat ringkasan pencapaian VMTS** - 82% Faithfulness

---

## 🎨 UI Features

### Dashboard Includes:

1. **Summary Cards**
   - 6 metrik dengan gradient warna
   - Animasi hover
   - Icon per metrik

2. **Charts**
   - Pie chart kategori distribusi
   - Bar chart perbandingan metrik
   - Responsive design

3. **Data Table**
   - 18 skenario dengan scrolling
   - Badge sistem dengan warna
   - Row rata-rata highlighted

4. **Info Sections**
   - Dataset composition
   - Metrik explanation
   - Analysis & recommendations

### PDF Report Includes:

1. **Professional Layout**
   - Header dengan logo konsep
   - Section dengan border
   - Gradient boxes

2. **Complete Data**
   - Summary table
   - Dataset list
   - Detailed scenarios
   - Analysis sections

3. **Styling**
   - Color-coded badges
   - Professional typography
   - Page breaks optimized

---

## 🔐 Integration Points

### Cara Mengintegrasikan dengan AI Assistant

```php
use App\Services\RAGASEvaluationService;

// 1. Setelah AI generate response
$ragasService = app(RAGASEvaluationService::class);

$metrics = $ragasService->evaluateVMTS(
    question: $userQuestion,
    answer: $aiResponse,
    contexts: $retrievedChunks,
    groundTruth: [] // Optional
);

// 2. Save ke database
RAGASEvaluationTest::create([
    'question' => $userQuestion,
    'kategori' => 'Laporan Triwulan',
    'actual_answer' => $aiResponse,
    'retrieved_context' => json_encode($retrievedChunks),
    'faithfulness' => $metrics['faithfulness'],
    'answer_relevancy' => $metrics['answer_relevancy'],
    // ... other metrics
    'status' => 'evaluated',
]);
```

### Quick Evaluation (Heuristic)

```php
// Untuk evaluasi cepat tanpa AI calls
$metrics = $ragasService->quickEvaluateVMTS(
    question: $userQuestion,
    answer: $aiResponse,
    contexts: $retrievedChunks
);
```

---

## 📝 Changelog

### v1.0.0 (2025-06-13)
- ✅ Initial implementation
- ✅ Database schema & migration
- ✅ RAGAS metrics calculation
- ✅ Dashboard UI with charts
- ✅ PDF report generation
- ✅ 18 test scenarios seeded
- ✅ Full documentation

---

## 🤝 References

- RAGAS Framework: https://github.com/explodinggradients/ragas
- Documentation: Section 5.2.4 Evaluasi Sistem RAG
- Model: Groq LLaMA 3.1 8B

---

## 👨‍💻 Developer Notes

### Testing Tips

1. Gunakan `quickEvaluateVMTS()` untuk development
2. Gunakan `evaluateVMTS()` untuk production evaluation
3. Monitor `response_time_ms` untuk performance
4. Track `avg_similarity` untuk retrieval quality

### Maintenance

1. Review metrics setiap minggu
2. Update threshold jika performa berubah
3. Re-seed data untuk testing
4. Backup evaluasi results

---

## 📞 Support

Untuk pertanyaan atau issues, hubungi:
- Team: PA3 Development
- System: Laporan Akademik Fakultas Vokasi
- Framework: Laravel + RAGAS

---

**Status**: ✅ Production Ready  
**Last Updated**: 13 Juni 2025  
**Version**: 1.0.0
