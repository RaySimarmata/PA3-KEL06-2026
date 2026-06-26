# Class Diagram dari SQL - Panduan Penggunaan

## 📋 Deskripsi

Class Diagram ini dibuat berdasarkan struktur tabel SQL dari sistem Monitoring Mutu Akademik. Terdapat **3 versi diagram** dengan layout yang berbeda untuk kebutuhan presentasi yang berbeda.

---

## 📁 File Diagram

### 1. **CLASS_DIAGRAM_FROM_SQL.puml** (Default Version)
- **Layout**: Top to Bottom dengan orthogonal lines
- **Bahasa**: Inggris
- **Kelebihan**: 
  - Cocok untuk dokumen formal
  - Garis-garis lebih rapi (orthogonal)
  - Grouping dengan `together`
- **Rekomendasi**: Untuk dokumentasi SRS/Technical Documentation

### 2. **CLASS_DIAGRAM_FROM_SQL_CLEAN.puml** (Packaged Version)
- **Layout**: Package-based organization
- **Bahasa**: Inggris
- **Kelebihan**:
  - Pengelompokan berdasarkan domain yang jelas
  - Visual lebih clean dengan warna per package
  - Termasuk notes/keterangan
  - Menggunakan crow's foot notation
- **Rekomendasi**: Untuk presentasi stakeholder & demo

### 3. **CLASS_DIAGRAM_FROM_SQL_HORIZONTAL.puml** (Flow Version)
- **Layout**: Left to Right (Horizontal)
- **Bahasa**: Inggris
- **Kelebihan**:
  - Menunjukkan flow data dari kiri ke kanan
  - Layer-based architecture view
  - Termasuk legend dan penjelasan
  - Cocok untuk layar wide/projector
- **Rekomendasi**: Untuk presentasi arsitektur sistem & workshop

### 4. **CLASS_DIAGRAM_FROM_SQL_INDONESIA.puml** (Versi Indonesia Lengkap) 🆕
- **Layout**: Package-based dengan detail lengkap
- **Bahasa**: Indonesia
- **Kelebihan**:
  - Label relasi dalam Bahasa Indonesia
  - Pembedaan jelas method Public (+) dan Private (-)
  - BelongsTo ditandai dengan jelas
  - Foreign Key ditandai dengan <<FK>>
  - Field nullable ditandai {nullable}
  - Legend lengkap dalam Bahasa Indonesia
- **Rekomendasi**: Untuk dokumentasi skripsi/laporan dalam Bahasa Indonesia

### 5. **CLASS_DIAGRAM_INDONESIA_SIMPLE.puml** (Versi Indonesia Ringkas) 🆕
- **Layout**: Flat dengan focus pada relasi
- **Bahasa**: Indonesia
- **Kelebihan**:
  - Lebih ringkas dan mudah dibaca
  - BelongsTo ditandai dengan emoji 👈
  - Method BelongsTo berwarna hijau
  - Method HasMany berwarna biru
  - Attribute private berwarna merah
  - Relasi opsional dengan garis putus-putus
- **Rekomendasi**: Untuk presentasi cepat dan slide presentasi

---

## 🎨 Perbandingan Visual

| Aspek | Default | Clean | Horizontal | Indonesia Lengkap 🆕 | Indonesia Simple 🆕 |
|-------|---------|-------|------------|---------------------|-------------------|
| **Direction** | Top-Down | Top-Down | Left-Right | Top-Down | Top-Down |
| **Bahasa** | EN | EN | EN | ID | ID |
| **Grouping** | Together | Package | Package | Package | Flat |
| **Line Type** | Ortho | Ortho | Polyline | Ortho | Ortho |
| **Colors** | Single | Multi | Single | Multi | Single |
| **Method Visibility** | ❌ | ❌ | ❌ | ✅ (+/-/#) | ✅ (Color) |
| **BelongsTo Mark** | ❌ | ❌ | ❌ | ✅ (Label) | ✅ (👈 Emoji) |
| **FK Annotation** | ❌ | ❌ | ❌ | ✅ <<FK>> | ❌ |
| **Nullable Mark** | ❌ | ❌ | ❌ | ✅ {nullable} | ✅ {null} |
| **Notes** | ❌ | ✅ | ✅ | ✅ | ✅ |
| **Complexity** | Medium | Low | High | High | Low |
| **Best For** | Formal Doc | Demo | Architecture | Thesis (ID) | Quick Present |

---

## 🏗️ Struktur Domain

Semua diagram mencakup **5 Domain Utama**:

### 1️⃣ **MASTER DATA**
- `prodi` - Program Studi
- `users` - Akun Pengguna (GKM/GJM)
- `dosen` - Data Dosen

### 2️⃣ **AKADEMIK**
- `matakuliah` - Mata Kuliah
- `periode_akademik` - Periode/Semester

### 3️⃣ **RPS & MATERI**
- `rps` - Rencana Pembelajaran Semester
- `materi` - Materi Perkuliahan

### 4️⃣ **KUISIONER**
- `kuisioner` - Kuesioner Evaluasi
- `pertanyaan_kuisioner` - Pertanyaan dalam Kuesioner

### 5️⃣ **REMINDER & LAPORAN**
- `reminder` - Pengingat/Notifikasi
- `template_laporan` - Template Laporan
- `laporan` - Laporan (Bulanan/Triwulan/Semester)
- `kirim_laporan` - Riwayat Kirim Laporan

---

## 🔗 Relasi Antar Tabel

### Foreign Key Relationships:

```
users 1 ──→ * dosen
users * ──→ 1 prodi
dosen * ──→ 1 prodi

matakuliah * ──→ 1 prodi

rps * ──→ 1 matakuliah
rps * ──→ 1 periode_akademik
rps * ──→ 1 dosen

materi * ──→ 1 matakuliah
materi * ──→ 1 periode_akademik
materi * ──→ 1 dosen

kuisioner * ──→ 1 periode_akademik
kuisioner * ──→ 1 matakuliah
kuisioner * ──→ 1 dosen
kuisioner 1 ──→ 1 pertanyaan_kuisioner

pertanyaan_kuisioner * ──→ 1 kuisioner

reminder * ──→ 1 users
reminder * ──→ 0..1 dosen (optional)
reminder * ──→ 0..1 matakuliah (optional)
reminder * ──→ 0..1 rps (optional)
reminder * ──→ 0..1 materi (optional)

laporan * ──→ 1 users
laporan * ──→ 1 template_laporan
laporan * ──→ 0..1 prodi (optional)
laporan * ──→ 0..1 periode_akademik (optional)
laporan * ──→ 0..1 dosen (optional)

kirim_laporan * ──→ 1 users
```

---

## 🔧 Method/Function

Setiap class dilengkapi dengan **method dari implementasi Laravel**:

### Master Data
- `prodi`: `dosenKepala()`, `dosen()`, `matakuliah()`, `laporanGKM()`
- `users`: `dosen()`, `prodi()`, `isGKM()`, `isGJM()`, `isDosen()`
- `dosen`: `user()`, `prodi()`, `rps()`, `materi()`, `scopeKaprodi()`

### Akademik
- `matakuliah`: `prodi()`, `dosen()`, `rps()`
- `periode_akademik`: `getActive()`, `scopeActive()`, `setSemesterAttribute()`

### RPS & Materi
- `rps`: `matakuliah()`, `dosen()`, `monitoring()`
- `materi`: `matakuliah()`, `dosen()`, `monitoring()`

### Kuisioner
- `kuisioner`: `pertanyaan()`, `jawaban()`
- `pertanyaan_kuisioner`: `kuisioner()`, `jawaban()`

### Reminder & Laporan
- `reminder`: `userPembuat()`, `userPenerima()`, `logEmail()`
- `template_laporan`: `prodi()`, `laporanBulanan()`, `scopeActive()`
- `laporan`: `user()`, `template()`, `getFormattedPeriodeAttribute()`
- `kirim_laporan`: `user()`

---

## 📊 Cara Melihat Diagram

### Option 1: VS Code (Recommended)
1. Install extension: **PlantUML** by jebbs
2. Install Graphviz: `choco install graphviz` (Windows) atau `brew install graphviz` (Mac)
3. Buka file `.puml`
4. Tekan `Alt+D` untuk preview

### Option 2: Online Editor
1. Buka https://www.plantuml.com/plantuml/
2. Copy-paste isi file `.puml`
3. Klik "Submit"

### Option 3: IntelliJ IDEA
1. Install plugin: **PlantUML Integration**
2. Buka file `.puml`
3. Preview akan muncul di panel samping

### Option 4: Export ke Image
```bash
# Install PlantUML CLI
npm install -g node-plantuml

# Generate PNG
puml generate CLASS_DIAGRAM_FROM_SQL.puml -o output.png

# Generate SVG (scalable)
puml generate CLASS_DIAGRAM_FROM_SQL.puml -o output.svg
```

---

## 🎯 Rekomendasi Penggunaan

| Situasi | Diagram yang Digunakan |
|---------|------------------------|
| Dokumentasi SRS/Skripsi (Bahasa Inggris) | `CLASS_DIAGRAM_FROM_SQL.puml` |
| Dokumentasi Skripsi (Bahasa Indonesia) | `CLASS_DIAGRAM_FROM_SQL_INDONESIA.puml` 🆕 |
| Presentasi Sidang (Bahasa Indonesia) | `CLASS_DIAGRAM_INDONESIA_SIMPLE.puml` 🆕 |
| Presentasi ke Dosen Pembimbing | `CLASS_DIAGRAM_FROM_SQL_CLEAN.puml` |
| Presentasi Arsitektur Sistem | `CLASS_DIAGRAM_FROM_SQL_HORIZONTAL.puml` |
| Export ke Word/PDF | `CLASS_DIAGRAM_FROM_SQL_CLEAN.puml` atau `CLASS_DIAGRAM_INDONESIA_SIMPLE.puml` |
| Projector/Wide Screen | `CLASS_DIAGRAM_FROM_SQL_HORIZONTAL.puml` |
| Slide PowerPoint (Ringkas) | `CLASS_DIAGRAM_INDONESIA_SIMPLE.puml` 🆕 |
| Cetak Poster A3/A2 | `CLASS_DIAGRAM_FROM_SQL_HORIZONTAL.puml` |
| Penjelasan Relasi Database | `CLASS_DIAGRAM_INDONESIA_SIMPLE.puml` 🆕 |

---

## 🔍 Fitur Khusus Versi Indonesia

### Versi Indonesia Lengkap (`CLASS_DIAGRAM_FROM_SQL_INDONESIA.puml`)

**✅ Pembedaan Visibility:**
```
+method()     → Public (dapat diakses dari luar)
-attribute    → Private (hanya dari dalam class)
#method()     → Protected (class & turunannya)
```

**✅ Penanda Foreign Key:**
```
+prodi_id: BIGINT <<FK>>
+user_id: BIGINT <<FK>> {nullable}
```

**✅ Label Relasi Bahasa Indonesia:**
- `<< milik dari >>` = BelongsTo
- `< memiliki >` = HasMany/HasOne
- `< tentang >` = Reference opsional

**✅ Grouping Method:**
```
.. Method Relasi ..
+prodi(): BelongsTo
+dosen(): HasMany

.. Method Helper (Public) ..
+isGKM(): boolean
```

### Versi Indonesia Simple (`CLASS_DIAGRAM_INDONESIA_SIMPLE.puml`)

**✅ Color Coding:**
- <span style="color:green">**Hijau**</span> = Method BelongsTo (👈 dengan emoji)
- <span style="color:blue">**Biru**</span> = Method HasMany/HasOne
- <span style="color:red">**Merah**</span> = Attribute Private

**✅ Emoji Indicator:**
```
+prodi() BelongsTo   → Relasi milik dari
+dosen()                → Relasi memiliki
```

**✅ Nullable Indicator:**
```
+dosen_id: BIGINT {null}
```

**✅ Garis Relasi:**
- `──>` = Relasi wajib (solid line)
- `··>` = Relasi opsional (dotted line)

---

## 📝 Catatan Penting

1. **Semua diagram berisi class/tabel yang sama** - hanya berbeda layout
2. **Method diambil dari implementasi Laravel** yang ada di `app/Models`
3. **Relasi sesuai dengan Foreign Key** di SQL DDL
4. **Tidak ada penambahan/pengurangan class** dari struktur SQL asli

---

## 🔄 Update Diagram

Jika ada perubahan pada struktur database:

1. Update SQL DDL terlebih dahulu
2. Update Model Laravel jika perlu
3. Update ketiga file `.puml` secara konsisten
4. Regenerate preview untuk verifikasi

---

## 📧 Support

Jika ada pertanyaan tentang diagram ini, hubungi:
- Tim Pengembang PA3-KEL06-2026
- Dokumentasi lengkap: `/docs/CLASS_DIAGRAM_DOCUMENTATION.md`

---

**Generated**: 2026-06-16  
**Version**: 1.0  
**Source**: SQL DDL (105 Laravel Migrations)
