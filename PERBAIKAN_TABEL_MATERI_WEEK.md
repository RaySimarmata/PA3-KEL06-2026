# Perbaikan Tabel Materi Week 1-16 (Laporan Bulanan Artefak)

## Masalah
Pada fitur **AI Assistant - Laporan Bulanan (Artefak)**, hasil generate dokumen Word:
1. Hanya menampilkan tabel materi sampai **Week 9**, sedangkan **Week 10 sampai Week 16 terpotong**
2. Kolom "Semester" kurang tepat karena seharusnya menampilkan tingkat (1, 2, 3, 4)
3. Week 16 masih sedikit terpotong pada versi pertama perbaikan

## Penyebab
1. **Lebar kolom terlalu besar** - Total lebar tabel mencapai ~24,400 twip
2. **Halaman Portrait terlalu sempit** - Lebar maksimal ~9,000 twip
3. **Kolom "Semester" tidak efisien** - Menampilkan "Tingkat 1", "Tingkat 2", dst

## Solusi yang Diterapkan

### 1. Optimasi Lebar Kolom (FINAL)
Mengurangi lebar setiap kolom secara optimal:

**Kolom Tetap:**
- ~~Semester: 800 → 600 → 450~~ → **Tingkat: 400 twip** ✅ (dikurangi 50% dari awal)
- Kode MK: 1200 → ~~900 → 800~~ → **750 twip** ✅ (dikurangi 38%)
- Nama MK: 2000 → ~~1600 → 1500~~ → **1400 twip** ✅ (dikurangi 30%)
- Dosen: 1500 → ~~1200 → 1100~~ → **1000 twip** ✅ (dikurangi 33%)

**Kolom Week (per week):**
- Teori: 600 → ~~350 → 320~~ → **300 twip** ✅ (dikurangi 50%)
- Praktikum: 600 → ~~350 → 320~~ → **300 twip** ✅ (dikurangi 50%)

**Perhitungan Total Lebar (ULTRA FINAL):**
```
Kolom Tetap = 400 + 750 + 1,400 + 1,000 = 3,550 twip
Kolom Week  = (300 + 300) × 16           = 9,600 twip
TOTAL       = 3,550 + 9,600              = 13,150 twip ✅✅✅

Lebar Landscape:                         = 16,838 twip
Sisa Margin:                             = 3,688 twip (sangat cukup!)
Persentase:                              = 78.1% (ideal!)
```

### 2. Perubahan Header Kolom
**Sebelum:** "Semester" dengan isi "Tingkat 1", "Tingkat 2", "Tingkat 3", "Tingkat 4"
**Sesudah:** "Tingkat" dengan isi **"1"**, **"2"**, **"3"**, **"4"** saja

**Keuntungan:**
- ✅ Lebih ringkas dan hemat ruang
- ✅ Lebih mudah dibaca
- ✅ Konsisten dengan terminologi akademik

### 3. Orientasi Landscape
Menambahkan **section break dengan orientasi landscape** khusus untuk halaman tabel materi:

```xml
<w:p><w:pPr><w:sectPr>
  <w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/>
  <w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720"/>
</w:sectPr></w:pPr></w:p>
```

**Spesifikasi Landscape:**
- Lebar: 16,838 twip (11.69 inch / 29.7 cm)
- Tinggi: 11,906 twip (8.27 inch / 21.0 cm)
- Margin: 720 twip (0.5 inch) pada semua sisi

### 4. Hasil Akhir
✅ **Semua Week 1 sampai Week 16 sekarang terlihat lengkap** (tidak ada yang terpotong)
✅ **Week 16 Praktikum tidak terpotong sama sekali** (sisa margin 3,688 twip)
✅ Tabel materi berada di halaman landscape terpisah
✅ Kolom "Tingkat" hanya menampilkan angka (1, 2, 3, 4) - lebih ringkas
✅ Kolom tetap dapat terbaca dengan baik (tidak terlalu sempit)
✅ Data Teori dan Praktikum untuk setiap week tetap terlihat jelas
✅ **Persentase penggunaan lebar 78.1%** (sangat ideal untuk readability)

## File yang Dimodifikasi
- `app/Services/LaporanArtefakService.php`
  - Fungsi: `buildMateriTableXML()` - optimasi final lebar kolom + landscape
  - Fungsi: `injectMateriTable()` - update log message
  - Fungsi: `injectRPSTable()` - konsistensi header "Tingkat"
  - Fungsi: `buildTabelRPS()` - plain text fallback header "Tingkat"
  - Fungsi: `buildTabelMateri()` - plain text fallback header "Tingkat"

## Perbandingan Sebelum vs Sesudah

| Aspek | Sebelum | Sesudah |
|-------|---------|---------|
| **Kolom Header** | "Semester" | "Tingkat" |
| **Isi Kolom** | "Tingkat 1", "Tingkat 2" | "1", "2", "3", "4" |
| **Lebar Kolom Pertama** | 800 twip | **450 twip** (-44%) |
| **Lebar Total Tabel** | ~24,400 twip (overflow) | **14,090 twip** (muat) |
| **Week Terlihat** | Week 1-9 saja | **Week 1-16 LENGKAP** ✅ |
| **Orientasi** | Portrait (9,000 twip) | **Landscape (16,838 twip)** ✅ |

## Cara Testing
1. Buka halaman **GKM → Laporan Bulanan**
2. Klik **Buat Laporan Baru**
3. Isi form (Periode, Judul Laporan)
4. Klik **Generate Laporan**
5. Download file Word yang dihasilkan
6. Buka file Word dan cari **Tabel Materi**
7. Verifikasi bahwa:
   - ✅ Tabel berada di halaman landscape
   - ✅ Header kolom pertama: **"Tingkat"**
   - ✅ Isi kolom tingkat: **1, 2, 3, 4** (tanpa kata "Tingkat")
   - ✅ Kolom Week 1 sampai Week 16 **SEMUA terlihat**
   - ✅ **Week 16 tidak terpotong sama sekali**
   - ✅ Data Teori (T) dan Praktikum (P) untuk setiap week dapat dibaca
   - ✅ Tabel RPS juga menggunakan header "Tingkat" (konsistensi)

## Catatan Tambahan
- Perubahan ini **backward compatible** - laporan yang sudah ada tidak terpengaruh
- Template Word (jika ada) harus memiliki placeholder `{{TABEL_MATERI}}` agar tabel dapat di-inject dengan benar
- Kolom "Tingkat" juga diterapkan pada **Tabel RPS** untuk konsistensi
- Jika masih ada masalah, periksa log Laravel di `storage/logs/laravel.log` untuk error terkait PhpWord

## Tanggal Perbaikan
- **Versi 1:** 9 Juni 2026 (optimasi awal)
- **Versi 2:** 9 Juni 2026 (optimasi lanjutan + perubahan "Semester" → "Tingkat")
- **Versi 3 (ULTRA FINAL):** 9 Juni 2026 (optimasi maksimal - Week 16 P tidak terpotong)

## Referensi
- [PhpWord Documentation - Section](https://phpword.readthedocs.io/en/latest/usage/sections.html)
- [OOXML Specification - Page Size](https://learn.microsoft.com/en-us/dotnet/api/documentformat.openxml.wordprocessing.pagesize)
- [Word Table Width Calculation](https://stackoverflow.com/questions/tagged/phpword+table)


## Fix Tambahan: Data Week 16 Tidak Muncul

### Masalah Baru yang Ditemukan
Setelah perbaikan lebar kolom, ditemukan bahwa **Week 16 Praktikum tidak menampilkan nilai 0 atau 1**, padahal kolom sudah terlihat sempurna.

### Analisis Masalah
1. **Data dari API eksternal** hanya mengembalikan array `weeks` dengan index 0-14 (Week 1-15)
2. **Week 16 (index 15) tidak ada** di array `weeks` yang disimpan di `raw_data`
3. Kode sebelumnya hanya memproses data yang ada, tanpa mengisi default untuk week yang tidak ada

### Solusi yang Diterapkan
Mengubah logika pengisian data week dari `foreach` menjadi `for loop` dengan **default value**:

**Untuk Materi Teori:**
```php
for ($idx = 0; $idx < 16; $idx++) {
    if (isset($weeks[$idx])) {
        $val = $weeks[$idx];
        $mkMap[$key]['teori_weeks'][$idx] = (is_null($val) || $val === '') ? '0' : (string) $val;
    } else {
        // Default ke '0' jika data tidak ada
        $mkMap[$key]['teori_weeks'][$idx] = '0';
    }
}
```

**Untuk Materi Praktikum:**
```php
for ($idx = 0; $idx < 16; $idx++) {
    if (isset($weeks[$idx])) {
        $val = $weeks[$idx];
        $mkMap[$key]['prak_weeks'][$idx] = (is_null($val) || $val === '') ? '0' : (string) $val;
    } else {
        // Default ke '0' jika data tidak ada (belum upload)
        $mkMap[$key]['prak_weeks'][$idx] = '0';
    }
}
```

### Hasil Akhir
✅ **Week 1-16 Teori semua menampilkan nilai** (0 atau 1)
✅ **Week 1-16 Praktikum semua menampilkan nilai** (0 atau 1)
✅ **Week yang tidak ada data di database ditampilkan sebagai '0'** (belum upload)
✅ Konsisten dengan logic bisnis: tidak ada data = belum upload = 0

### File yang Dimodifikasi
- `app/Services/LaporanArtefakService.php`
  - Fungsi: `buildMateriTableXML()` - mengubah logika pengisian data week
  - Ditambahkan default value '0' untuk week yang tidak ada data

### Testing
1. Generate laporan bulanan baru
2. Buka file Word hasil generate
3. Cek tabel materi
4. Verifikasi **Week 16 kolom T dan P menampilkan nilai** (0 atau 1)
5. Jika data di database memang tidak ada untuk Week 16, akan tampil **'0'**
