# Instalasi Tesseract OCR untuk Windows

## Langkah 1: Download Tesseract OCR

1. Kunjungi: https://github.com/UB-Mannheim/tesseract/wiki
2. Download installer untuk Windows (64-bit): `tesseract-ocr-w64-setup-5.3.3.20231005.exe`
3. Jalankan installer

## Langkah 2: Instalasi

1. Pilih lokasi instalasi (default: `C:\Program Files\Tesseract-OCR`)
2. **PENTING**: Pada bagian "Select Components", pastikan centang:
   - ✅ Tesseract OCR
   - ✅ English language data
   - ✅ Indonesian language data (jika tersedia)
3. Klik "Install"

## Langkah 3: Tambahkan ke PATH (Opsional tapi Direkomendasikan)

1. Buka "Environment Variables":
   - Klik kanan "This PC" → Properties
   - Advanced system settings
   - Environment Variables
2. Di "System variables", cari "Path"
3. Klik "Edit"
4. Klik "New"
5. Tambahkan: `C:\Program Files\Tesseract-OCR`
6. Klik "OK" semua dialog

## Langkah 4: Install Python Dependencies

```bash
pip install pytesseract pillow opencv-python numpy
```

## Langkah 5: Test Instalasi

```bash
# Test Tesseract langsung
tesseract --version

# Test melalui Python script
python scripts/tesseract_ocr.py --check

# Test dengan gambar
python scripts/tesseract_ocr.py "public/test-images/sample1.jpg"

# Test melalui Laravel
php artisan ocr:test "public/test-images/sample1.jpg"
```

## Langkah 6: Konfigurasi Laravel

Update file `.env`:

```env
OCR_ENGINE=tesseract
TESSERACT_PATH="C:\Program Files\Tesseract-OCR\tesseract.exe"
TESSERACT_LANGUAGES=eng+ind
```

## Troubleshooting

### Error: "Tesseract is not installed"

**Solusi 1**: Pastikan Tesseract sudah terinstall
```bash
tesseract --version
```

**Solusi 2**: Set path manual di `.env`
```env
TESSERACT_PATH="C:\Program Files\Tesseract-OCR\tesseract.exe"
```

**Solusi 3**: Restart terminal/command prompt setelah instalasi

### Error: "Failed to load language data"

**Solusi**: Download language data manual
1. Download dari: https://github.com/tesseract-ocr/tessdata
2. Copy file `.traineddata` ke: `C:\Program Files\Tesseract-OCR\tessdata\`

### Error: "Permission denied"

**Solusi**: Jalankan command prompt sebagai Administrator

## Keunggulan Tesseract OCR

✅ **Ringan**: Tidak memerlukan deep learning framework berat
✅ **Cepat**: Proses OCR lebih cepat dari PaddleOCR
✅ **Stabil**: Tidak ada masalah kompatibilitas Windows
✅ **Gratis**: Open source dan gratis selamanya
✅ **Multi-bahasa**: Mendukung 100+ bahasa termasuk Indonesia
✅ **Akurat**: Akurasi tinggi untuk teks cetak

## Perbandingan dengan PaddleOCR

| Fitur | Tesseract | PaddleOCR |
|-------|-----------|-----------|
| Instalasi | ✅ Mudah | ❌ Kompleks |
| Ukuran | ✅ ~50MB | ❌ ~500MB+ |
| Kecepatan | ✅ Cepat | ⚠️ Lambat |
| Akurasi Teks Cetak | ✅ Tinggi | ✅ Tinggi |
| Akurasi Tulisan Tangan | ⚠️ Rendah | ✅ Tinggi |
| Windows Support | ✅ Stabil | ❌ Bermasalah |
| Bahasa Indonesia | ✅ Ya | ✅ Ya |

## Rekomendasi

Untuk sistem produksi di Windows, **gunakan Tesseract OCR** karena:
- Lebih stabil dan reliable
- Instalasi lebih mudah
- Tidak ada masalah kompatibilitas
- Cukup akurat untuk dokumen cetak (laporan, formulir, dll)

PaddleOCR hanya direkomendasikan jika:
- Perlu OCR tulisan tangan
- Running di Linux/Mac
- Punya GPU untuk akselerasi
