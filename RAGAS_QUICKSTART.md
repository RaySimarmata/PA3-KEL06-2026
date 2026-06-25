# 🚀 RAGAS Quick Start Guide

## ⚡ Langkah Cepat (5 Menit)

### 1️⃣ Setup Data

```bash
# Seed data evaluasi (18 skenario)
php artisan db:seed --class=RAGASEvaluationSeeder
```

### 2️⃣ Akses Dashboard

Buka browser:
```
http://localhost:8000/gjm/evaluasi/ragas
```

**Anda akan melihat:**
- ✅ 6 metrik summary dengan warna
- 📊 Pie chart distribusi kategori
- 📊 Bar chart perbandingan metrik
- 📋 Tabel 18 skenario pengujian
- 📖 Penjelasan metrik RAGAS
- 📈 Analisis hasil

### 3️⃣ Download PDF Report

Klik tombol **"Unduh Laporan"** atau:
```
http://localhost:8000/gjm/evaluasi/ragas/download-report
```

---

## 📊 Quick View - Console

### Lihat Summary
```bash
php artisan ragas:report
```

Output:
```
╔════════════════════════════════════════════════════════════════╗
║         RAGAS EVALUATION REPORT - RAG SYSTEM QUALITY          ║
╚════════════════════════════════════════════════════════════════╝

📊 SUMMARY METRICS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Metric              Value      Status
Faithfulness        87.50%     ✅ Good
Hallucination Rate  12.50%     ✅ Good
Context Precision   68.30%     ⚠️  Fair
Context Recall      82.50%     ✅ Good
...
RAGAS SCORE         77.94%     ✅ Good
```

### Lihat Detail
```bash
php artisan ragas:report --detailed
```

### Export ke File
```bash
php artisan ragas:report --export=json
php artisan ragas:report --export=csv
```

---

## 🧪 Running Evaluations

### Quick Evaluation (Heuristic)
```bash
php artisan ragas:evaluate --quick --limit=5
```

Menggunakan keyword overlap untuk cepat evaluasi.

### Full Evaluation (AI-Based)
```bash
php artisan ragas:evaluate --limit=3
```

Menggunakan AI untuk evaluasi lebih akurat (lebih lambat).

### Filter by Category
```bash
php artisan ragas:evaluate --kategori="Kuesioner" --quick
```

---

## 📈 Memahami Hasil

### RAGAS Score: 77.94% ✅ **BAIK**

**Artinya:**
- Sistem RAG bekerja dengan baik
- Cocok untuk production
- Ada ruang untuk perbaikan

### Metrik Penting:

1. **Faithfulness: 87.5%** ✅
   - Jawaban sesuai dokumen sumber
   - Tidak banyak "mengarang"

2. **Hallucination: 12.5%** ✅
   - Hanya 12-13 dari 100 pernyataan tidak didukung
   - Tingkat keandalan tinggi

3. **Context Precision: 68.3%** ⚠️
   - Perlu perbaikan
   - Masih ambil konteks tidak relevan

4. **Context Recall: 82.5%** ✅
   - Menemukan informasi yang diperlukan
   - Tidak banyak yang terlewat

5. **F1 Score: 74.8%** ✅
   - Keseimbangan baik
   - Sistem tidak ekstrem

---

## 🎯 Use Cases

### 1. Monitor Quality
```bash
# Jalankan evaluasi berkala
php artisan ragas:evaluate --quick

# Check hasilnya
php artisan ragas:report
```

### 2. Compare Changes
```bash
# Before changes
php artisan ragas:report --export=json > before.json

# After changes
php artisan ragas:report --export=json > after.json

# Compare manually
```

### 3. Category Analysis
```bash
# Check kuesioner performance
php artisan ragas:report --kategori="Kuesioner" --detailed

# Check monitoring RPS performance
php artisan ragas:report --kategori="Monitoring RPS" --detailed
```

---

## 🔧 Integrasi ke Kode

### Basic Usage

```php
use App\Services\RAGASEvaluationService;
use App\Models\RAGASEvaluationTest;

// 1. Get service
$ragasService = app(RAGASEvaluationService::class);

// 2. Evaluate
$metrics = $ragasService->evaluateVMTS(
    question: "Buatkan laporan triwulan TRPL",
    answer: $aiGeneratedAnswer,
    contexts: $retrievedChunks
);

// 3. Save result
RAGASEvaluationTest::create([
    'question' => "Buatkan laporan triwulan TRPL",
    'kategori' => 'Laporan Triwulan',
    'actual_answer' => $aiGeneratedAnswer,
    'faithfulness' => $metrics['faithfulness'],
    'answer_relevancy' => $metrics['answer_relevancy'],
    // ... other metrics
    'status' => 'evaluated',
]);
```

### Quick Evaluation (Faster)

```php
// Untuk testing atau development
$metrics = $ragasService->quickEvaluateVMTS(
    question: $userQuestion,
    answer: $aiResponse,
    contexts: $chunks
);
```

---

## 📊 Best Practices

### ✅ DO:
- Run evaluasi setelah perubahan model/prompt
- Monitor metrics secara berkala
- Focus on Faithfulness & Hallucination
- Export results untuk analisis

### ❌ DON'T:
- Jangan abaikan Context Precision rendah
- Jangan run full evaluation terlalu sering (lambat)
- Jangan hapus historical data

---

## 🆘 Troubleshooting

### ❌ No data found
```bash
# Solution: Seed data
php artisan db:seed --class=RAGASEvaluationSeeder
```

### ❌ Dashboard tidak muncul data
1. Check database connection
2. Clear cache: `php artisan cache:clear`
3. Check logs: `storage/logs/laravel.log`

### ❌ PDF download error
1. Check dompdf installed: `composer show barryvdh/laravel-dompdf`
2. Check storage permissions
3. Clear config: `php artisan config:clear`

---

## 📞 Quick Help

### Commands Cheat Sheet
```bash
# Seed data
php artisan db:seed --class=RAGASEvaluationSeeder

# Quick evaluation
php artisan ragas:evaluate --quick --limit=5

# View report
php artisan ragas:report

# Detailed report
php artisan ragas:report --detailed

# Export report
php artisan ragas:report --export=json
```

### URLs
- Dashboard: `/gjm/evaluasi/ragas`
- Get Data API: `/gjm/evaluasi/ragas/get-data`
- Download PDF: `/gjm/evaluasi/ragas/download-report`

---

## 🎓 Next Steps

1. ✅ Setup dan lihat dashboard
2. ✅ Pahami metrik RAGAS
3. 🔧 Integrasikan ke AI Assistant
4. 📈 Monitor performa secara berkala
5. 🚀 Optimize berdasarkan hasil

---

## 📚 Dokumentasi Lengkap

Lihat: `RAGAS_IMPLEMENTATION.md` untuk:
- Technical details
- Architecture
- Advanced usage
- Full API reference

---

**Status**: ✅ Production Ready  
**Last Updated**: 13 Juni 2025  
**Quick Start Time**: ~5 menit  
**Difficulty**: 🟢 Easy
