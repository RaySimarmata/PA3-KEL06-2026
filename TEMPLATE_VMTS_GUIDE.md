# Panduan Membuat Template Laporan VMTS

## 📋 Struktur Template VMTS

Template Laporan VMTS terdiri dari beberapa bagian:

### 1. **Cover Page (Halaman Sampul)**
Cover page berisi informasi statis dan placeholder minimal:

```
Laporan VMTS
Gugus Jaminan Mutu Fakultas Vokasi

{PERIODE}

Institut Teknologi Del

{ TAHUN_AKADEMIK}
```

**Catatan Cover:**
- Judul "Laporan VMTS" dan "Gugus Jaminan Mutu Fakultas Vokasi" adalah **teks statis** (tidak diubah)
- `{PERIODE}` akan diisi otomatis dengan tahun akademik (contoh: 2024/2025)
- `{ TAHUN_AKADEMIK}` akan diisi otomatis (ada spasi sebelum TAHUN - perhatikan!)
- Cover tidak akan diubah oleh AI, hanya placeholder yang diisi

---

### 2. **Daftar Isi**
Daftar isi bisa dibuat manual atau menggunakan auto-generated TOC dari Word:

```
DAFTAR ISI

I. PENDAHULUAN .................................................... 3
II. METODE PENELITIAN ............................................ 3
III. HASIL ANALISIS DESKRIPTIF .................................. 3
IV. PEMBAHASAN ................................................... 3
V. KESIMPULAN .................................................... 3
LAMPIRAN ......................................................... 3
```

**Catatan:**
- Nomor halaman akan di-update otomatis jika menggunakan TOC Word
- Section ini opsional, bisa dihapus jika tidak diperlukan

---

### 3. **Konten Laporan (Isi Laporan)**
Konten laporan menggunakan **5 section utama** dengan placeholder:

```
I. PENDAHULUAN

{PENDAHULUAN}


II. METODE PENELITIAN

{METODE_PENELITIAN}


III. HASIL ANALISIS DESKRIPTIF

{HASIL_ANALISIS}


IV. PEMBAHASAN

{PEMBAHASAN}


V. KESIMPULAN

{KESIMPULAN}


LAMPIRAN

{LAMPIRAN_GAMBAR}
```

**Placeholder yang digunakan:**
- `{PENDAHULUAN}` - Konten section I
- `{METODE_PENELITIAN}` - Konten section II
- `{HASIL_ANALISIS}` - Konten section III
- `{PEMBAHASAN}` - Konten section IV
- `{KESIMPULAN}` - Konten section V
- `{LAMPIRAN_GAMBAR}` - Untuk insert gambar di lampiran

**Placeholder yang TIDAK digunakan:**
- ❌ `{JUDUL_LAPORAN}` - Judul hardcoded di template
- ❌ `{LAMPIRAN}` - Hanya menggunakan `{LAMPIRAN_GAMBAR}`

---

## 🎨 Cara Membuat Template VMTS di Microsoft Word

### Langkah 1: Buat Cover Page

1. Buka Microsoft Word, buat halaman baru
2. Ketik teks cover:
   ```
   Laporan VMTS
   Gugus Jaminan Mutu Fakultas Vokasi
   
   {PERIODE}
   
   Institut Teknologi Del
   
   { TAHUN_AKADEMIK}
   ```
3. **PENTING**: Perhatikan ada **spasi sebelum TAHUN** di `{ TAHUN_AKADEMIK}`
4. Format dengan style:
   - "Laporan VMTS": Bold, Font Size 18-20, Center
   - "Gugus Jaminan Mutu Fakultas Vokasi": Font Size 14, Center
   - Placeholder: Font Size 12, Center
5. Tambahkan logo Institut Teknologi Del (opsional)
6. Tambahkan page break setelah cover

### Langkah 2: Buat Daftar Isi (Opsional)

1. Ketik "DAFTAR ISI" sebagai heading
2. Tambahkan list section dengan nomor halaman
3. Atau gunakan **References → Table of Contents** untuk auto-generate
4. Tambahkan page break setelah daftar isi

### Langkah 3: Buat Halaman Konten

Di halaman setelah daftar isi, ketik struktur berikut:

```
I. PENDAHULUAN

{PENDAHULUAN}


II. METODE PENELITIAN

{METODE_PENELITIAN}


III. HASIL ANALISIS DESKRIPTIF

{HASIL_ANALISIS}


IV. PEMBAHASAN

{PEMBAHASAN}


V. KESIMPULAN

{KESIMPULAN}


LAMPIRAN

{LAMPIRAN_GAMBAR}
```

**Tips Formatting:**
- Judul section (I, II, III, dll) gunakan **Bold, Font Size 14**
- Placeholder `{...}` **HARUS dalam satu baris** (jangan dipisah)
- Berikan spacing 2-3 baris antar section
- Gunakan page break sebelum "LAMPIRAN" (opsional)

### Langkah 4: Styling dan Format

1. **Font**: Pilih Arial atau Times New Roman, size 11-12
2. **Heading**: Bold, size 14 untuk judul section
3. **Margins**: 2-3 cm untuk semua sisi
4. **Line Spacing**: 1.5 untuk readability
5. **Alignment**: Justify untuk paragraph

### Langkah 5: Simpan Template

1. Klik **File → Save As**
2. Nama file: `Template_VMTS_Fakultas_Vokasi.docx`
3. Format: **Word Document (.docx)**
4. Simpan di lokasi yang mudah diakses

---

## 📤 Cara Upload Template ke Sistem

1. Login ke sistem sebagai user GJM
2. Klik menu **"Template Laporan"** → **"VMTS"**
3. Pilih **"Upload Template Baru"**
4. Isi form:
   - **Nama Template**: Template VMTS Fakultas Vokasi
   - **Jenis**: Laporan VMTS
   - **Status**: Active
   - **File**: Upload file `.docx`
5. Klik **Upload**

---

## 🔍 Cara Menggunakan Template

### Step 1: Buat Laporan Baru
1. Klik **"Buat Laporan VMTS"**
2. **Pilih Template** yang sudah diupload
3. Isi form:
   - **Periode**: 2024/2025
   - **Judul**: (akan diabaikan, judul di template yang digunakan)
4. Klik **"Buat Draf Laporan"**

### Step 2: Generate Konten dengan AI
1. Chat dengan AI Assistant:
   ```
   Buatkan laporan VMTS tentang survei visi-misi Institut Teknologi Del
   dengan 5 fakultas. Gunakan struktur:
   
   I. PENDAHULUAN (latar belakang dan tujuan survei)
   II. METODE PENELITIAN (skala Likert 1-6, SPSS)
   III. HASIL ANALISIS DESKRIPTIF (tabel responden per unit)
   IV. PEMBAHASAN (analisis komparatif, temuan utama)
   V. KESIMPULAN (ringkasan hasil survei)
   ```

2. AI akan generate konten lengkap dengan:
   - **Tables** (data responden, hasil analisis)
   - **Numbered lists** (untuk metode, hasil)
   - **Paragraphs** (penjelasan detail)
   - **Formatting** (bold, italic untuk penekanan)

### Step 3: Generate Word
1. Klik **"Generate Laporan Word"**
2. Sistem akan:
   - ✅ Mengisi `{PERIODE}` dengan "2024/2025"
   - ✅ Mengisi `{ TAHUN_AKADEMIK}` dengan "2024/2025"
   - ✅ Mengisi `{PENDAHULUAN}` dengan konten section I dari AI
   - ✅ Mengisi `{METODE_PENELITIAN}` dengan konten section II dari AI
   - ✅ Mengisi `{HASIL_ANALISIS}` dengan konten section III dari AI
   - ✅ Mengisi `{PEMBAHASAN}` dengan konten section IV dari AI
   - ✅ Mengisi `{KESIMPULAN}` dengan konten section V dari AI
   - ✅ Mengisi `{LAMPIRAN_GAMBAR}` dengan gambar (jika ada)
   - ✅ Mempertahankan cover, header, footer dari template
3. Download file `.docx`

---

## ✅ Checklist Template

- [ ] Cover dengan `{PERIODE}` dan `{ TAHUN_AKADEMIK}` (dengan spasi!)
- [ ] Daftar isi (opsional)
- [ ] Section I dengan `{PENDAHULUAN}`
- [ ] Section II dengan `{METODE_PENELITIAN}`
- [ ] Section III dengan `{HASIL_ANALISIS}`
- [ ] Section IV dengan `{PEMBAHASAN}`
- [ ] Section V dengan `{KESIMPULAN}`
- [ ] Lampiran dengan `{LAMPIRAN_GAMBAR}`
- [ ] Logo Institut Teknologi Del
- [ ] Formatting konsisten dan profesional

---

## 🚨 Catatan Penting

### DO's ✅
- **Tulis placeholder dalam SATU BARIS**
- Gunakan **huruf kapital** dan **underscore**: `{METODE_PENELITIAN}`
- Perhatikan **spasi** di `{ TAHUN_AKADEMIK}` (ada spasi sebelum TAHUN)
- Test template setelah upload
- Simpan dalam format `.docx` (bukan `.doc`)

### DON'Ts ❌
- ❌ Jangan pisah placeholder:
  ```
  ❌ SALAH: {METODE_
  PENELITIAN}
  
  ✅ BENAR: {METODE_PENELITIAN}
  ```
- ❌ Jangan ubah nama placeholder
- ❌ Jangan hapus kurung kurawal `{}`
- ❌ Jangan gunakan `{JUDUL_LAPORAN}` atau `{LAMPIRAN}` (tidak didukung)

---

## 📊 Contoh Hasil Generate

AI akan membuat konten lengkap dengan tables dan formatting:

### I. PENDAHULUAN (dari AI)
```
Visi dan misi merupakan pedoman utama bagi setiap institusi pendidikan tinggi, 
termasuk Institut Teknologi Del. Tujuan dari survei ini adalah untuk mengukur 
tingkat sosialisasi, pemahaman, dan implementasi visi-misi...
```

### III. HASIL ANALISIS DESKRIPTIF (dari AI dengan table)
```
Tabel berikut menunjukkan distribusi responden:

| Unit                  | Jumlah | Rentang |
|-----------------------|--------|---------|
| Fakultas Vokasi       | 100    | 1-6     |
| Perguruan Tinggi      | 150    | 1-6     |
| D4 TRPL              | 80     | 1-6     |
```

Semua formatting (tables, lists, bold, italic) akan **tetap preserved** di Word!

---

## 🆘 Troubleshooting

### Problem: `{ TAHUN_AKADEMIK}` tidak terisi
**Solusi:**
- Pastikan ada **spasi sebelum TAHUN**: `{ TAHUN_AKADEMIK}`
- Jangan tulis `{TAHUN_AKADEMIK}` (tanpa spasi)

### Problem: Placeholder konten tidak terisi
**Solusi:**
- AI harus generate dengan struktur Roman numerals (I, II, III, IV, V)
- Pastikan AI menulis "I. PENDAHULUAN" bukan hanya "PENDAHULUAN"

### Problem: Table tidak muncul di Word
**Solusi:**
- AI sudah support markdown tables
- System akan convert otomatis ke Word table

---

**Template VMTS siap digunakan!** 🎉

### Langkah 1: Buat Cover Page

1. Buka Microsoft Word, buat halaman baru
2. Design cover page sesuai kebutuhan Institut Teknologi Del:
   - Tambahkan logo institusi
   - Tambahkan judul "LAPORAN VMTS"
   - Tambahkan placeholder:
     ```
     {JUDUL_LAPORAN}
     
     Periode: {TAHUN_AKADEMIK}
     {PERIODE}
     ```
3. Format cover dengan styling yang menarik (font, warna, alignment)
4. Tambahkan page break setelah cover

### Langkah 2: Buat Header dan Footer (Opsional)

1. Klik **Insert → Header** atau **Insert → Footer**
2. Design header/footer yang konsisten
3. Bisa tambahkan logo kecil, nomor halaman, nama institusi

### Langkah 3: Buat Halaman Konten

Di halaman setelah cover, tambahkan structure berikut:

```
I. PENDAHULUAN

{PENDAHULUAN}


II. METODE PENELITIAN

{METODE_PENELITIAN}


III. HASIL ANALISIS DESKRIPTIF

{HASIL_ANALISIS}


IV. PEMBAHASAN

{PEMBAHASAN}


V. KESIMPULAN

{KESIMPULAN}


VI. LAMPIRAN

{LAMPIRAN}
```

**Tips Formatting:**
- Judul section (I, II, III, dll) gunakan **Bold, Font Size 14**
- Placeholder `{...}` **HARUS dalam satu baris** (jangan dipisah ke beberapa line)
- Berikan spacing yang cukup antar section
- Gunakan page break jika diperlukan

### Langkah 4: Styling (Opsional)

1. **Font**: Pilih font profesional (Arial, Calibri, Times New Roman)
2. **Heading**: Format judul section dengan style yang konsisten
3. **Margins**: Set margin 2-3 cm untuk semua sisi
4. **Line Spacing**: Gunakan 1.5 atau 2.0 untuk readability

### Langkah 5: Simpan Template

1. Klik **File → Save As**
2. Pilih lokasi penyimpanan
3. Nama file: `Template_VMTS_ITDel.docx`
4. Format: **Word Document (.docx)**
5. Klik **Save**

---

## 📤 Cara Upload Template ke Sistem

1. Login ke sistem sebagai user GJM
2. Klik menu **"Template Laporan"**
3. Pilih **"Upload Template Laporan VMTS"**
4. Isi form:
   - **Nama Template**: Template VMTS Institut Teknologi Del
   - **Jenis Template**: Laporan VMTS
   - **Status**: Active
   - **File**: Pilih file `.docx` yang sudah dibuat
5. Klik **Upload**

---

## 🔍 Cara Menggunakan Template

### Step 1: Buat Laporan Baru
1. Klik **"Buat Laporan VMTS"**
2. Pilih **Template** yang sudah diupload
3. Isi form:
   - Periode: Pilih tahun akademik
   - Judul Laporan: Input judul

### Step 2: Generate Konten dengan AI
1. Ketik instruksi di AI Assistant, contoh:
   ```
   Buatkan laporan VMTS tentang survei visi-misi Institut Teknologi Del
   dengan 5 fakultas dan program studi.
   ```
2. AI akan generate konten untuk semua 5 section
3. Review konten yang dihasilkan

### Step 3: Generate Word
1. Klik **"Generate Laporan Word"**
2. Sistem akan:
   - Mengisi placeholder di cover dengan data yang diinput
   - Mengisi placeholder konten dengan hasil AI
   - Mempertahankan formatting, header, footer dari template
3. Download file `.docx` yang dihasilkan

---

## ✅ Checklist Template

Pastikan template Anda memiliki:

- [ ] Cover page dengan placeholder `{TAHUN_AKADEMIK}`, `{JUDUL_LAPORAN}`, `{PERIODE}`
- [ ] Placeholder `{PENDAHULUAN}` di section I
- [ ] Placeholder `{METODE_PENELITIAN}` di section II
- [ ] Placeholder `{HASIL_ANALISIS}` di section III
- [ ] Placeholder `{PEMBAHASAN}` di section IV
- [ ] Placeholder `{KESIMPULAN}` di section V
- [ ] Placeholder `{LAMPIRAN}` di section VI (opsional)
- [ ] Header dan Footer (opsional)
- [ ] Logo institusi
- [ ] Formatting yang konsisten dan profesional

---

## 🚨 Catatan Penting

### DO's ✅
- **Tulis placeholder dalam SATU BARIS** (contoh: `{PENDAHULUAN}`)
- Gunakan **huruf kapital** untuk placeholder
- Gunakan **underscore** untuk spasi (contoh: `{METODE_PENELITIAN}`)
- Test template setelah upload

### DON'Ts ❌
- **JANGAN** pisah placeholder ke beberapa baris:
  ```
  ❌ SALAH:
  {PENDA
  HULUAN}
  
  ✅ BENAR:
  {PENDAHULUAN}
  ```
- **JANGAN** ubah nama placeholder (harus sesuai list di atas)
- **JANGAN** hapus kurung kurawal `{}`
- **JANGAN** tambah spasi di dalam kurung: `{ PENDAHULUAN }`

---

## 📊 Contoh Konten yang Dihasilkan AI

AI akan generate konten lengkap untuk setiap section:

### I. PENDAHULUAN
```
Visi dan misi merupakan pedoman utama bagi setiap institusi pendidikan tinggi, 
termasuk Institut Teknologi Del. Visi menyatakan tujuan jangka panjang yang 
ingin dicapai, sedangkan misi menjelaskan langkah-langkah konkret untuk 
mencapai visi tersebut...
```

### II. METODE PENELITIAN
```
Survei ini menggunakan instrumen kuesioner yang disusun dengan skala Likert 1-6:
- 1 = sangat tidak setuju
- 2 = tidak setuju
- 3 = cukup tidak setuju
...
```

Dan seterusnya untuk section lainnya.

---

## 🆘 Troubleshooting

### Problem: Placeholder tidak terisi
**Solusi:**
1. Pastikan placeholder ditulis dalam satu baris
2. Periksa ejaan placeholder (harus sama persis)
3. Gunakan huruf kapital dan underscore

### Problem: Formatting hilang
**Solusi:**
1. Pastikan template sudah disimpan sebagai `.docx`
2. Jangan gunakan format `.doc` (versi lama)
3. Test ulang template

### Problem: AI tidak generate section tertentu
**Solusi:**
1. Berikan instruksi yang lebih spesifik ke AI
2. Minta AI untuk generate ulang section yang kurang
3. Edit manual di Word setelah download

---

## 📞 Support

Jika mengalami kendala, hubungi:
- Tim IT Institut Teknologi Del
- Email: support@del.ac.id
- Dokumentasi: [link ke dokumentasi]

---

**Selamat membuat template!** 🎉
