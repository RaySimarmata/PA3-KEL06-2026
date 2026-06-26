# Image Content Validation System

## Overview
Sistem validasi konten gambar untuk memastikan gambar yang diupload relevan dengan jenis laporan yang dibuat (Triwulan, Semester, VMTS).

## Fitur Utama

### 1. **Validasi Otomatis dengan OCR**
- Menggunakan OCR (Optical Character Recognition) untuk membaca teks dari gambar
- Menganalisis konten teks untuk menentukan relevansi dengan laporan
- Menolak gambar yang tidak relevan dengan confidence tinggi (≥70%)

### 2. **Keyword-Based Relevance Scoring**
Sistem menggunakan dua set keyword:

#### Relevant Keywords (Positif)
- **Akademik**: mahasiswa, dosen, perkuliahan, semester, mata kuliah, kelas, jadwal, absen, nilai, ujian
- **Monitoring**: monitoring, evaluasi, penilaian, kualitas, mutu, capaian, target, grafik, data, statistik
- **Institusi**: universitas, fakultas, prodi, kampus, akademik, pendidikan
- **GKM/GJM**: gkm, gjm, gugus, kendali, jaminan, mutu, rps, silabus, kurikulum
- **Dokumen**: tanggal, nama, nim, nidn, tanda tangan, stempel
- **Kegiatan**: kegiatan, rapat, pertemuan, workshop, seminar, pelatihan
- **Sistem**: sistem, aplikasi, portal, dashboard, form, report

#### Irrelevant Keywords (Negatif/Red Flags)
- **Brand**: apple, iphone, android, samsung, logo brand
- **Entertainment**: game, gaming, movie, film
- **Lifestyle**: fashion, food, recipe, travel, tourism
- **E-commerce**: shopping, product, sale, discount
- **Social Media**: instagram, facebook, twitter, tiktok

### 3. **Scoring Algorithm**
```php
// Base score: jumlah relevant keywords / 5 (max 1.0)
$baseScore = min($relevantCount / 5, 1.0);

// Penalty: setiap irrelevant keyword -0.2
$penalty = $irrelevantCount * 0.2;

// Final score
$score = max(0, $baseScore - $penalty);

// Boost untuk high-value keywords
$highValueKeywords = ['monitoring', 'laporan', 'triwulan', 'semester', 'gkm', 'gjm', 'mutu'];
// +0.1 untuk setiap high-value keyword
```

### 4. **Validation Thresholds**
- **Relevance Score ≥ 0.3**: Gambar diterima
- **Relevance Score < 0.3**: Gambar ditolak
- **Confidence ≥ 0.7**: Keputusan final (accept/reject)
- **Confidence < 0.7**: Gambar diterima dengan warning (uncertain)

## Alur Validasi

```
1. User upload gambar
   ↓
2. Gambar disimpan sementara
   ↓
3. OCR ekstraksi teks dari gambar
   ↓
4. Analisis konten teks:
   - Hitung relevant keywords
   - Hitung irrelevant keywords
   - Deteksi logo/simple image
   ↓
5. Hitung relevance score
   ↓
6. Tentukan validitas:
   - Score ≥ 0.3 → Valid
   - Score < 0.3 → Invalid
   ↓
7. Jika invalid dengan confidence ≥ 0.7:
   - Hapus gambar
   - Return error message
   ↓
8. Jika valid atau uncertain:
   - Lanjutkan proses
   - Return success dengan warning (jika ada)
```

## Contoh Penggunaan

### Backend (Controller)
```php
use App\Services\ImageContentValidationService;
use App\Services\OCRService;

$ocrService = new OCRService();
$validationService = new ImageContentValidationService($ocrService);

// Validate single image
$result = $validationService->validateImageRelevance(
    $imagePath,
    'laporan_triwulan'
);

if (!$result['is_valid'] && $result['confidence'] >= 0.7) {
    // Reject image
    return response()->json([
        'success' => false,
        'message' => $result['reason']
    ], 400);
}

// Validate multiple images
$results = $validationService->validateImagesBatch(
    $imagePaths,
    'laporan_triwulan'
);
```

### Frontend (JavaScript)
```javascript
// Upload dengan validasi otomatis
const formData = new FormData();
formData.append('images[]', imageFile);
formData.append('laporan_id', laporanId);
formData.append('report_type', 'laporan_triwulan');

const response = await fetch('/gjm/ocr/upload', {
    method: 'POST',
    body: formData
});

const data = await response.json();

if (data.success) {
    // Gambar diterima
    console.log('Accepted:', data.stats.accepted);
    console.log('Rejected:', data.rejected_images);
} else {
    // Semua gambar ditolak
    console.error('All images rejected:', data.message);
}
```

## Response Format

### Success Response
```json
{
    "success": true,
    "message": "Images uploaded and integrated with Asisten Pembuatan Laporan",
    "data": {
        "ocr_results": [...],
        "ai_preview": "..."
    },
    "warning": "⚠️ 1 gambar ditolak karena tidak relevan: apple.jpg",
    "rejected_images": [
        {
            "filename": "apple.jpg",
            "reason": "Gambar tidak relevan...",
            "confidence": "85.0%",
            "relevance_score": "10.0%"
        }
    ],
    "validation_details": [
        {
            "filename": "document.jpg",
            "is_valid": true,
            "confidence": 0.75,
            "relevance_score": 0.6,
            "ocr_method": "tesseract"
        }
    ],
    "stats": {
        "total_uploaded": 2,
        "accepted": 1,
        "rejected": 1
    }
}
```

### Error Response (All Rejected)
```json
{
    "success": false,
    "message": "❌ Semua gambar ditolak karena tidak relevan dengan Laporan Triwulan\n\n📋 Detail Penolakan:\n• apple.jpg\n  Confidence: 85.0%\n  Relevance: 10.0%\n\n✅ Silakan upload gambar yang relevan seperti:\n• Dokumentasi kegiatan kampus/akademik\n• Daftar hadir perkuliahan\n• Grafik/chart data monitoring\n• Screenshot sistem akademik\n• Dokumen RPS/Silabus\n• Dokumentasi evaluasi mutu\n• Tabel data akademik\n• Surat atau memo resmi kampus",
    "rejected_images": [...],
    "validation_details": [...]
}
```

## Validation Result Structure

```php
[
    'is_valid' => bool,           // Apakah gambar valid
    'confidence' => float,        // Confidence level (0-1)
    'reason' => string,           // Alasan validasi
    'extracted_text' => string,   // Teks yang diekstrak dari gambar
    'ocr_method' => string,       // Method OCR yang digunakan
    'relevance_score' => float    // Score relevansi (0-1)
]
```

## Edge Cases

### 1. OCR Gagal
- **Behavior**: Gambar diterima dengan confidence rendah (0.3)
- **Reason**: "OCR tidak dapat membaca teks dari gambar, validasi konten tidak dapat dilakukan"

### 2. Teks Terlalu Pendek (<20 karakter)
- **Check**: Apakah logo atau simple image?
  - **Ya**: Ditolak dengan confidence 0.85
  - **Tidak**: Diterima dengan confidence 0.4

### 3. Validation Error
- **Behavior**: Gambar diterima dengan confidence rendah (0.2)
- **Reason**: "Validasi gambar gagal: [error message]"

### 4. Low Confidence (<0.5)
- **Behavior**: Gambar diterima dengan warning
- **Log**: "Image accepted with low confidence"

## Konfigurasi

### Threshold Settings
```php
// Di ImageContentValidationService.php

// Minimum relevance score untuk diterima
private $minRelevanceScore = 0.3;

// Minimum confidence untuk keputusan final
private $minConfidenceForRejection = 0.7;

// Minimum text length untuk validasi
private $minTextLength = 20;

// Minimum relevant keywords untuk full score
private $minRelevantKeywordsForFullScore = 5;
```

### Keyword Customization
Edit array `$triwulanRelevantKeywords` dan `$irrelevantKeywords` di `ImageContentValidationService.php` untuk menyesuaikan dengan kebutuhan.

## Testing

### Test Cases
1. **Valid Document**: Upload dokumentasi kegiatan kampus → Should accept
2. **Invalid Image**: Upload logo Apple → Should reject
3. **Borderline Case**: Upload gambar dengan sedikit teks → Should accept with low confidence
4. **OCR Failure**: Upload gambar corrupt → Should accept with warning
5. **Batch Upload**: Upload mix valid/invalid → Should accept valid, reject invalid

### Manual Testing
```bash
# Test dengan command
php artisan test --filter ImageValidationTest

# Test manual via browser
# 1. Buka halaman Buat Laporan Triwulan
# 2. Upload gambar logo Apple
# 3. Verify: Gambar ditolak dengan pesan error
# 4. Upload dokumentasi kegiatan
# 5. Verify: Gambar diterima
```

## Logging

Semua validasi dicatat di log dengan format:
```
[timestamp] INFO: === Image Content Validation Started ===
[timestamp] INFO: OCR extraction successful
[timestamp] INFO: Keyword matching results
[timestamp] INFO: Relevance analysis completed
[timestamp] INFO: === Image Content Validation Completed ===
```

Check logs:
```bash
tail -f storage/logs/laravel.log | grep "Image Content Validation"
```

## Performance

- **OCR Processing**: ~1-3 detik per gambar (tergantung ukuran)
- **Validation**: ~0.1 detik per gambar
- **Total**: ~1-3 detik per gambar

### Optimization Tips
1. Enable OCR caching (sudah aktif by default)
2. Preprocess gambar untuk OCR lebih akurat
3. Batch validation untuk multiple images
4. Async processing untuk large batches

## Troubleshooting

### Problem: Semua gambar ditolak
**Solution**: 
- Check OCR service status
- Verify keyword lists
- Lower threshold (minRelevanceScore)

### Problem: Gambar tidak relevan diterima
**Solution**:
- Add more irrelevant keywords
- Increase confidence threshold
- Improve OCR preprocessing

### Problem: OCR tidak berfungsi
**Solution**:
- Install Tesseract: `sudo apt-get install tesseract-ocr`
- Install language data: `sudo apt-get install tesseract-ocr-ind`
- Check Tesseract version: `tesseract --version`

## Future Improvements

1. **Machine Learning**: Train model untuk deteksi relevansi lebih akurat
2. **Image Classification**: Gunakan computer vision untuk klasifikasi gambar
3. **Context-Aware**: Validasi berdasarkan konteks laporan (periode, prodi, dll)
4. **User Feedback**: Learn from user corrections
5. **Multi-Language**: Support bahasa lain selain Indonesia/English

## References

- OCR Service: `app/Services/OCRService.php`
- Validation Service: `app/Services/ImageContentValidationService.php`
- Controller: `app/Http/Controllers/GJM/OCRUploadController.php`
- Frontend: `public/js/ai-prompt-assistant-triwulan.js`
