# Perbaikan AI Assistant - Laporan Bulanan (Artefak)

## Status: ✅ SELESAI DIPERBAIKI

---

## Masalah Sebelumnya

**LaporanArtefakService** memiliki system prompt yang sangat lemah:
```php
// ❌ SEBELUM
'content' => 'Anda adalah AI Agent GKM ahli laporan akademik. Balas HANYA JSON valid.'
```

**Kelemahan:**
- ❌ Tidak ada instruksi anti-hallucination
- ❌ Tidak ada larangan mengarang data
- ❌ Tidak ada panduan tentang data apa yang boleh digunakan
- ❌ Bisa mengarang nama mata kuliah dan dosen
- ❌ Bisa menambahkan statistik fiktif
- ❌ Temperature 0.7 (terlalu tinggi)

**Dampak:**
- Faithfulness rendah (AI mengarang fakta)
- Hallucination tinggi (AI menambahkan info yang tidak ada di data)

---

## Perbaikan yang Diterapkan

### 1. Temperature: 0.7 → 0.3 ✅
**File**: `app/Services/LaporanArtefakService.php` line 1565
```php
// ✅ SETELAH
'temperature' => 0.3,  // Lower temperature untuk mengurangi hallucination
```

### 2. Enhanced User Prompt dengan Data Faktual ✅
**File**: `app/Services/LaporanArtefakService.php` line ~470

**Ditambahkan:**
```
ATURAN KETAT ANTI-HALLUCINATION:
1. HANYA gunakan angka dan data yang diberikan di bawah ini
2. JANGAN PERNAH menyebut nama mata kuliah atau nama dosen yang spesifik
3. JANGAN mengarang statistik atau persentase tambahan
4. JANGAN membuat asumsi tentang kualitas atau penyebab masalah
5. HANYA tulis narasi berdasarkan angka jumlah MK yang diberikan
6. Jika data tidak mencukupi, gunakan kalimat umum tanpa detail spesifik

DATA FAKTUAL YANG HARUS DIGUNAKAN:
- Semester: [dari database]
- Prodi: [dari database]
- Total MK: [dari database]
- RPS Sudah Upload: [dari database]
- Materi Sudah Upload: [dari database]
- Persentase RPS: [dihitung]
- Persentase Materi: [dihitung]

LARANGAN:
❌ JANGAN menyebut nama mata kuliah spesifik
❌ JANGAN menyebut nama dosen spesifik
❌ JANGAN menambahkan angka atau statistik selain yang diberikan
❌ JANGAN membuat asumsi tentang alasan keterlambatan
❌ JANGAN menyebutkan semester atau periode selain yang diberikan

CONTOH YANG BENAR:
✅ 'Dari total 15 mata kuliah, sebanyak 12 mata kuliah telah mengunggah RPS'
✅ 'Persentase kelengkapan RPS mencapai 80%'

CONTOH YANG SALAH:
❌ 'Mata kuliah Algoritma belum upload' (nama MK spesifik)
❌ 'Dosen Budi masih proses upload' (nama dosen)
❌ 'Tingkat kehadiran 95%' (mengarang data)
```

### 3. Enhanced System Message ✅
**File**: `app/Services/LaporanArtefakService.php` line ~527

**Sebelum:**
```php
'content' => 'Anda adalah AI Agent GKM ahli laporan akademik. Balas HANYA JSON valid.'
```

**Sesudah:**
```php
'content' => 'Anda adalah AI Agent GKM ahli laporan akademik yang HANYA menggunakan data faktual yang diberikan.

ATURAN KETAT ANTI-HALLUCINATION:
1. HANYA gunakan angka dan data yang EKSPLISIT diberikan dalam prompt user
2. JANGAN PERNAH mengarang nama mata kuliah, nama dosen, atau data tambahan
3. JANGAN membuat asumsi, prediksi, atau analisis yang tidak didukung data
4. Setiap angka yang disebutkan HARUS ada di data yang diberikan
5. Output HARUS berupa JSON valid tanpa komentar atau teks tambahan

LARANGAN MUTLAK:
❌ Mengarang nama mata kuliah spesifik
❌ Mengarang nama dosen
❌ Menambahkan statistik yang tidak ada di data
❌ Membuat prediksi atau target yang tidak disebutkan
❌ Menyebutkan semester/periode selain yang diberikan

FORMAT OUTPUT:
- Mulai langsung dengan { dan akhiri dengan }
- Tidak ada markdown (```json)
- Tidak ada komentar atau penjelasan

Balas HANYA JSON valid sesuai struktur yang diminta.'
```

---

## Ringkasan Perubahan

| Aspek | Sebelum | Sesudah |
|-------|---------|---------|
| **Temperature** | 0.7 | 0.3 ✅ |
| **System Prompt** | 1 kalimat generik | Detailed anti-hallucination rules ✅ |
| **User Prompt** | Data + instruksi sederhana | Data + aturan ketat + contoh + larangan ✅ |
| **Grounding ke Data** | Lemah | Kuat ✅ |
| **Contoh Benar/Salah** | Tidak ada | Ada ✅ |
| **Larangan Eksplisit** | Tidak ada | Ada (6 poin) ✅ |

---

## Mekanisme Anti-Hallucination

### Layer 1: Temperature Rendah (0.3)
- Mengurangi kreativitas AI
- Lebih fokus pada data yang diberikan
- Lebih deterministik

### Layer 2: System Message yang Ketat
- Instruksi jelas tentang batasan
- Larangan eksplisit tentang hal yang tidak boleh dilakukan
- Panduan format output yang ketat

### Layer 3: User Prompt dengan Data Eksplisit
- Semua data ditulis dengan jelas di prompt
- Contoh penggunaan yang benar
- Contoh kesalahan yang harus dihindari
- Larangan berulang di user prompt

### Layer 4: Validation di Prompt
- Setiap field JSON dijelaskan harus berdasarkan DATA
- Reminder berulang untuk tidak mengarang
- Instruksi untuk menggunakan kalimat umum jika data kurang

---

## Data yang Diberikan ke AI

LaporanArtefakService memberikan data faktual berikut ke AI:
```php
- Semester: dari database (misal: "Ganjil")
- Tahun Ajaran: dari database (misal: "2025/2026")
- Prodi: dari database (misal: "D4 TRPL")
- Total MK: dihitung dari database
- RPS Sudah Upload: dihitung dari database
- RPS Belum Upload: dihitung (total - sudah)
- Materi Sudah Upload: dihitung dari database
- Materi Belum Upload: dihitung (total - sudah)
- Persentase RPS: dihitung
- Persentase Materi: dihitung
```

**TIDAK diberikan:**
- ❌ Nama mata kuliah spesifik
- ❌ Nama dosen pengampu
- ❌ Detail hambatan individual
- ❌ Target atau deadline spesifik

**Harapan:**
AI hanya menulis narasi umum berdasarkan angka, tanpa menyebut nama spesifik.

---

## Testing Instructions

### Step 1: Hapus Cache Lama
```bash
php clear_old_ai_cache.php
```
Atau manual:
```php
// Di tinker
DB::connection('mongodb')->table('ai_response_cache')->truncate();
DB::table('ragas_evaluation_tests')->truncate();
```

### Step 2: Generate Laporan Bulanan Baru
1. Login sebagai GKM
2. Buka **Laporan Artefak** → **Buat Laporan**
3. Pilih Periode (misal: 2025-06)
4. Klik **Generate Laporan**
5. Sistem akan menggunakan AI dengan prompt baru

### Step 3: Verifikasi Output
Buka dokumen Word yang dihasilkan, cek:
- ✅ Tidak ada nama mata kuliah spesifik (misal: "Algoritma", "Basis Data")
- ✅ Tidak ada nama dosen spesifik (misal: "Pak Budi", "Dr. Santoso")
- ✅ Hanya menyebutkan angka jumlah (misal: "15 mata kuliah", "12 sudah upload")
- ✅ Persentase yang disebutkan sesuai data (misal: "80%" jika memang 80%)
- ✅ Tidak ada prediksi atau target yang tidak berdasar data

**Contoh Narasi yang Baik:**
> "Dari total 15 mata kuliah di semester Ganjil 2025/2026, sebanyak 12 mata kuliah (80%) telah mengunggah RPS sesuai jadwal. Masih terdapat 3 mata kuliah (20%) yang belum menyelesaikan proses upload. Untuk materi perkuliahan, 10 dari 15 mata kuliah (67%) telah mengunggah materi lengkap."

**Contoh Narasi yang Buruk (Hallucination):**
> ❌ "Mata kuliah Algoritma dan Struktur Data masih dalam proses upload oleh Pak Budi." (menyebut nama MK dan dosen)
> ❌ "Diperkirakan dalam 2 minggu ke depan akan mencapai 95%." (mengarang prediksi)
> ❌ "Tingkat kehadiran mahasiswa mencapai 90%." (mengarang data yang tidak ada)

### Step 4: Sync ke RAGAS
```bash
php artisan ragas:sync
```

### Step 5: Cek Metrik
Buka: http://127.0.0.1:8000/gjm/evaluasi/ragas

Filter kategori: **Laporan Bulanan**

**Expected Results:**
- ✅ Faithfulness: >80% (sebelumnya rendah)
- ✅ Hallucination: <15% (sebelumnya tinggi)
- ✅ Answer Relevancy: >85%

---

## Troubleshooting

### Jika Faithfulness masih rendah:
1. Periksa log: Apakah temperature 0.3 digunakan?
   ```bash
   tail -f storage/logs/laravel.log | grep temperature
   ```

2. Periksa prompt: Apakah anti-hallucination rules terkirim?
   ```bash
   tail -f storage/logs/laravel.log | grep "ATURAN KETAT"
   ```

3. Verifikasi cache kosong:
   ```php
   DB::connection('mongodb')->table('ai_response_cache')
     ->where('feature', 'laporan_bulanan')->count();
   ```

### Jika Hallucination masih tinggi:
Kemungkinan penyebab:
1. Masih menggunakan cache lama (belum dihapus)
2. Data yang diberikan ke AI kurang lengkap
3. Model LLM yang digunakan terlalu kreatif

**Solusi:**
- Pastikan cache benar-benar kosong
- Verifikasi data yang masuk ke `generateNarasiWithAI()`
- Pertimbangkan menggunakan model yang lebih fokus (misal: GPT-4 vs GPT-3.5-turbo)

---

## File yang Dimodifikasi

✅ **app/Services/LaporanArtefakService.php**
- Line ~470-527: Enhanced prompt generation
- Line 1565: Temperature 0.7 → 0.3

---

## Status Lengkap Semua Services

| Service | Temperature | Anti-Hallucination Prompt | Status |
|---------|-------------|---------------------------|--------|
| LaporanKuesioneService | 0.3 ✅ | ✅ Ada | ✅ FIXED |
| **LaporanArtefakService** | **0.3 ✅** | **✅ Ada** | **✅ FIXED** |
| AIAgentService | 0.3 ✅ | ✅ Ada | ✅ FIXED |
| VMTSAIAssistantService | 0.3 ✅ | ✅ (via UnifiedAI) | ✅ FIXED |
| LaporanTriwulanService | N/A | ✅ (via AI Preview) | ✅ OK |
| LaporanSemesterService | N/A | ✅ (via AI Preview) | ✅ OK |
| UnifiedAIService | 0.3 ✅ | ✅ Ada | ✅ FIXED |

---

## Kesimpulan

**Laporan Bulanan (LaporanArtefakService) sudah diperbaiki dengan:**
1. ✅ Temperature diturunkan ke 0.3
2. ✅ System prompt ditambah anti-hallucination rules yang ketat
3. ✅ User prompt diperkuat dengan data faktual, contoh, dan larangan eksplisit
4. ✅ Validasi berulang di multiple layer

**Confidence Level: VERY HIGH**

Perbaikan ini sangat comprehensive dan seharusnya menghasilkan peningkatan signifikan pada Faithfulness dan penurunan Hallucination untuk Laporan Bulanan.

---

**Next Action:**
1. Hapus cache lama: `php clear_old_ai_cache.php`
2. Generate 5-10 Laporan Bulanan baru
3. Sync ke RAGAS: `php artisan ragas:sync`
4. Cek hasil di halaman evaluasi RAGAS

---

Generated: 2026-06-14
Status: ✅ READY FOR TESTING
