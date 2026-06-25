# Evaluasi RAG menggunakan Framework RAGAS

## Deskripsi
Fitur evaluasi RAG (Retrieval-Augmented Generation) menggunakan framework RAGAS yang menyediakan metrik terstandar untuk mengukur performa sistem RAG secara komprehensif.

## Lokasi Menu
Dashboard GJM → **Evaluasi → RAGAS**

## Fitur Utama

### 1. **Rekapitulasi Metrik RAGAS**
Menampilkan 6 metrik utama:
- **Faithfulness** (87.5%) - Kesesuaian jawaban dengan dokumen sumber
- **Hallucination Rate** (12.5%) - Tingkat informasi yang dibuat tanpa dasar dokumen
- **Context Precision** (68.3%) - Ketepatan konteks yang diambil
- **Context Recall** (82.5%) - Kemampuan menemukan semua informasi relevan
- **F1 Score** (74.8%) - Keseimbangan antara precision dan recall
- **RAGAS Score** (77.94%) - Skor keseluruhan kualitas sistem RAG

### 2. **Komposisi Dataset Knowledge Base**
Menampilkan 6 jenis dokumen yang digunakan:
- Dokumen VMTS (50 dokumen)
- Dokumen RPS (100 dokumen)
- Laporan Historis (100 dokumen)
- Data Monitoring RPS (500 record)
- Data Monitoring Materi Perkuliahan (500 record)
- Data Kuesioner Mahasiswa (1.000 record)

**Total: ±2.250 dokumen & data**

### 3. **Tabel Hasil Per Skenario**
18 skenario pengujian dengan kategori:
- Laporan Triwulan
- Laporan Semester
- Laporan VMTS
- Laporan Bulanan
- Monitoring RPS
- Monitoring Materi
- Kuesioner

### 4. **Penjelasan Metrik**
Setiap metrik dijelaskan dengan:
- Fungsi dan tujuan
- Formula perhitungan
- Interpretasi hasil

### 5. **Analisis Hasil**
- Kesimpulan utama
- Kelebihan sistem
- Area pengembangan
- Rekomendasi perbaikan

## File-file Terkait

### Backend
- **Controller**: `app/Http/Controllers/GJM/ModelEvaluationController.php`
  - `ragasIndex()` - Menampilkan halaman evaluasi RAGAS
  - `ragasGetData()` - Mengambil data evaluasi via AJAX
  - `getHardcodedRAGASData()` - Data hardcoded sesuai dokumentasi skripsi
  - `ragasDownloadReport()` - Download laporan (coming soon)

### Frontend
- **View**: `resources/views/gjm/evaluasi/ragas.blade.php`
  - Tampilan dashboard RAGAS
  - Tabel skenario pengujian
  - Chart dan visualisasi metrik
  - Analisis dan rekomendasi

### Routing
- **Routes**: `routes/web.php`
  ```php
  Route::prefix('evaluasi')->name('evaluasi.')->group(function () {
      Route::get('/ragas', [ModelEvaluationController::class, 'ragasIndex'])->name('ragas.index');
      Route::get('/ragas/get-data', [ModelEvaluationController::class, 'ragasGetData'])->name('ragas.get-data');
      Route::get('/ragas/download-report', [ModelEvaluationController::class, 'ragasDownloadReport'])->name('ragas.download-report');
  });
  ```

### Layout
- **Sidebar**: `resources/views/layouts/app.blade.php`
  - Menu "Evaluasi" dengan submenu "RAGAS"

## Data Sumber
Data evaluasi RAGAS diambil dari:
- **Tabel 5.x** - Hasil Evaluasi RAGAS Per Skenario Pengujian (Bab 5 Skripsi)
- **Tabel 5.x** - Rekapitulasi Hasil Evaluasi RAGAS
- **Tabel 5.x** - Komposisi Dataset Knowledge Base Sistem RAG

## Metrik RAGAS Detail

### 1. Faithfulness
```
Formula: |Pernyataan yang Didukung Dokumen| / |Total Pernyataan dalam Jawaban|
Range: 0-1 (semakin tinggi semakin baik)
Hasil: 87.5% (BAIK)
```

### 2. Hallucination Rate
```
Formula: 1 - Faithfulness
Range: 0-1 (semakin rendah semakin baik)
Hasil: 12.5% (BAIK)
```

### 3. Context Precision
```
Fungsi: Mengukur ketepatan konteks yang diambil
Range: 0-1 (semakin tinggi semakin baik)
Hasil: 68.3% (CUKUP)
```

### 4. Context Recall
```
Fungsi: Mengukur kemampuan menemukan informasi relevan
Range: 0-1 (semakin tinggi semakin baik)
Hasil: 82.5% (BAIK)
```

### 5. F1 Score
```
Formula: 2 × (Precision × Recall) / (Precision + Recall)
Range: 0-1 (semakin tinggi semakin baik)
Hasil: 74.8% (BAIK)
```

### 6. RAGAS Score (Overall)
```
Formula: Rata-rata dari semua metrik RAGAS
Range: 0-100% (semakin tinggi semakin baik)
Hasil: 77.94% (BAIK)
```

## Interpretasi Warna Badge

- 🟢 **Excellent** (Hijau): ≥ 90% (atau ≤ 10% untuk hallucination)
- 🔵 **Good** (Biru): 80-89% (atau 10-15% untuk hallucination)
- 🟡 **Fair** (Kuning): 70-79% (atau 15-20% untuk hallucination)
- 🔴 **Poor** (Merah): < 70% (atau > 20% untuk hallucination)

## Kategori Skenario Pengujian

1. **Laporan Triwulan** - Q1
2. **Monitoring RPS** - Q2, Q7, Q16
3. **Kuesioner** - Q3, Q5, Q6, Q9, Q15, Q18
4. **Laporan Semester** - Q4, Q12, Q14, Q17
5. **Laporan VMTS** - Q8, Q13
6. **Laporan Bulanan** - Q10
7. **Monitoring Materi** - Q11

## Cara Mengakses

1. Login sebagai **GJM** (Gugus Jaminan Mutu)
2. Buka menu **Evaluasi** di sidebar
3. Klik **RAGAS**
4. Data akan dimuat secara otomatis via AJAX

## Future Improvements

- [ ] Implementasi download PDF report
- [ ] Integrasi dengan data real-time dari database
- [ ] Grafik visualisasi untuk setiap metrik
- [ ] Perbandingan antar periode
- [ ] Export ke Excel
- [ ] Filter berdasarkan kategori skenario

## Referensi

Framework RAGAS: https://github.com/explodinggradients/ragas

Dokumentasi lengkap ada di Bab 5.2.4 Skripsi tentang "Evaluasi Sistem RAG Menggunakan RAGAS"
