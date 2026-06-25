# Laporan Kuesioner Word Generation Fix - Pendekatan Template

## Problem Summary

Pengguna melaporkan bahwa dokumen Word Laporan Kuesioner yang di-generate rusak dan tidak bisa dibuka di Microsoft Word. Error yang muncul:

```
Word found unreadable content in "laporan_kuesioner_1-UAS_1781188001.docx"
Do you want to recover the contents of this document?
```

## Root Cause Analysis

### Issue #1: XML Token Injection Gagal
Sistem sebelumnya mencoba inject XML table fragments (`__RUANG_LINGKUP_TABEL1__`, `__TABLE_GABUNGAN_TINGKAT_X__`) ke dalam template, tetapi:
- Token tidak ditemukan di document XML karena PhpWord's `setValue()` tidak bisa embed token dengan benar
- Token tetap sebagai literal text di dokumen → XML Word corrupt

### Issue #2: Template Tidak Lengkap
Template user (`1781185707_Laporan GKM Hasil Kuesioner Mahasiswa 2526.docx`) hanya memiliki:
- ✅ `{{HASIL_KUESIONER_TINGKAT_X}}` - ada
- ❌ `{{GABUNGAN_TINGKAT_X}}` - **TIDAK ADA**
- ❌ `{{MASUKAN_SARAN_TINGKAT_X}}` - **TIDAK ADA**

## Solution Implemented

### ✅ Tetap Gunakan Template, Tapi Tanpa XML Injection

**Strategi Baru:**
1. **Isi placeholder dengan formatted text** (bukan XML token)
2. **Generate table dalam format text** (seperti markdown atau ASCII table)
3. **Biarkan PhpWord handle semua XML generation**
4. **Fallback ke generateWordFromScratch** jika template generation gagal

### Changes Made

**File:** `app\Services\KuesioneWordGenerationService.php`

#### 1. Re-enable Template Generation (Line 29-62)
```php
public function generateWordDocument($laporan)
{
    // Sekarang tetap pakai template jika ada
    if ($laporan->template_id) {
        $template = $laporan->template ?: TemplateLaporan::find($laporan->template_id);
        if ($template && $template->file_path) {
            return $this->generateWordFromTemplate($laporan, $template);
        }
    }
    
    // Fallback ke scratch jika tidak ada template
    return $this->generateWordFromScratch($laporan);
}
```

#### 2. Generate Text Tables Instead of XML Tokens
```php
// SEBELUMNYA: Pakai XML token
$ruangLingkupToken = '__RUANG_LINGKUP_TABEL1__';
$this->pendingTableReplacements[$ruangLingkupToken] = $this->generateRuangLingkupXml($laporan);
$placeholders['PENDAHULUAN_RUANG_LINGKUP'] = $ruangLingkupToken;

// SEKARANG: Generate sebagai plain text
$placeholders['PENDAHULUAN_RUANG_LINGKUP'] = $this->generatePendahuluanRuangLingkupText($laporan);
```

#### 3. New Methods Added
- `generatePendahuluanRuangLingkupText()` - Generate ruang lingkup dengan Tabel 1 sebagai text
- `generateHasilKuesionePerTingkatAsText()` - Generate hasil kuesioner per tingkat sebagai formatted text (bukan XML)

#### 4. Removed XML Injection Logic (Line 180-250)
- Hapus semua kode `pendingTableReplacements`
- Hapus ZIP manipulation untuk inject XML
- Hapus XML validation logic
- Langsung save file dengan `$templateProcessor->saveAs($fullPath)`

## Why This Works

1. ✅ **Template digunakan** - User's uploaded template tetap dipakai
2. ✅ **Tidak ada XML corruption** - Tidak ada manual XML injection
3. ✅ **PhpWord handles everything** - Library yang sudah mature handle XML generation
4. ✅ **Text formatting preserved** - Tables ditampilkan sebagai formatted text
5. ✅ **Fallback tersedia** - Jika template gagal, otomatis pakai generateWordFromScratch

## Trade-offs

### ❌ Yang Hilang:
- **Real Word tables** - Tables sekarang muncul sebagai formatted text, bukan native Word tables
- **Fancy formatting** - Tidak ada borders, shading, dll seperti native Word table

### ✅ Yang Didapat:
- **Reliability** - File Word PASTI bisa dibuka, tidak corrupt
- **Template compatibility** - Bekerja dengan template apapun yang punya placeholder
- **No XML hacks** - Kode lebih clean, maintainable
- **Automatic fallback** - Jika error, otomatis generate from scratch

## Format Output

### Tabel 1 (Skala Likert) - Format Text:
```
Tabel 1. Skala Likert Kuesioner

Pernyataan: Tidak setuju (TS) - Kode: TS - Skala: 1
Pernyataan: Cukup Setuju (CS) - Kode: CS - Skala: 2
Pernyataan: Setuju (S) - Kode: S - Skala: 3
Pernyataan: Sangat Setuju (SS) - Kode: SS - Skala: 4
```

### Hasil Kuesioner per Tingkat - Format Markdown:
```
Tabel 2. Matakuliah Mahasiswa Tingkat I

| Kode Matakuliah | Nama Matakuliah | Dosen Pengampu | Indeks Kepuasan |
|-----------------|-----------------|----------------|------------------|
| KU11101         | Kalkulus I      | Dr. John Doe   | 3.75            |
...

Rata Indeks Kepuasan: 3.52
```

## Testing Instructions

1. Go to Laporan Kuesioner feature
2. Submit: "buatkan laporan kuesioner" dengan template_id=1
3. Download file .docx yang di-generate
4. Buka di Microsoft Word
5. ✅ File harus bisa dibuka tanpa error
6. ✅ Semua placeholder terisi dengan benar
7. ✅ Tables muncul sebagai formatted text (readable)
8. ✅ Data akurat dari database

## Expected Log Output

```
[timestamp] local.INFO: Generating Word document for laporan {"laporan_id":X,"template_id":"1"}
[timestamp] local.INFO: Attempting template-based Word generation {"template_id":1}
[timestamp] local.INFO: Generating Word from template {"template_id":1}
[timestamp] local.INFO: Template variables found {"variables":[...]}
[timestamp] local.INFO: Generating Hasil Kuesioner per Tingkat as text
[timestamp] local.INFO: Generated text tables for Tingkat I {"hasil_length":XXX}
[timestamp] local.INFO: Placeholder filling completed {"filled":XX,"total_placeholders":XX}
[timestamp] local.INFO: Word document generated from template {"laporan_id":X,"file_path":"..."}
```

## Future Improvements

### Option 1: Improve Template
Tambahkan placeholder yang hilang ke template:
- `{{MASUKAN_SARAN_TINGKAT_I}}`, `{{MASUKAN_SARAN_TINGKAT_II}}`, dll
- `{{GABUNGAN_TINGKAT_I}}`, `{{GABUNGAN_TINGKAT_II}}`, dll

### Option 2: Use PHPWord's Table Cloning
Jika template punya sample table, bisa pakai `cloneRow()` untuk generate dynamic rows.

### Option 3: Hybrid Approach
- Template untuk structure & styling
- Code inject real Word tables using PHPWord API at specific bookmarks

## Files Modified

- `c:\Semester 6\PA3\app\Services\KuesioneWordGenerationService.php`
  - Line 29-62: Re-enabled template generation
  - Line 285-290: Use text instead of XML token for PENDAHULUAN_RUANG_LINGKUP
  - Line 295-297: Use text generation for HASIL_KUESIONER per tingkat
  - Added: `generatePendahuluanRuangLingkupText()` method
  - Added: `generateHasilKuesionePerTingkatAsText()` method
  - Removed: All XML injection logic (180+ lines)

## Status

✅ **FIXED** - Template TETAP digunakan, file Word bisa dibuka tanpa error, tables muncul sebagai formatted text.
