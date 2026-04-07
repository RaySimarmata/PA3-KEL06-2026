# 📊 Entity Relationship Diagram (ERD)
## Sistem Monitoring Mutu Akademik

Dokumentasi lengkap untuk visualisasi struktur database sistem.

---

## 📁 File yang Tersedia

### 1. **ERD.md** 
Format Mermaid dengan dokumentasi lengkap
- Diagram ERD dalam format Mermaid
- Penjelasan relasi antar tabel
- Catatan teknis dan enum values

### 2. **ERD-dbdiagram.dbml**
Format DBML untuk dbdiagram.io
- Siap digunakan di https://dbdiagram.io
- Export ke PNG, SVG, PDF
- Tampilan profesional dan interaktif

### 3. **ERD-viewer.html**
HTML viewer dengan Mermaid
- Buka langsung di browser
- Fitur zoom in/out
- Bisa di-print atau screenshot

---

## 🎨 Cara Membuat Gambar ERD

### Metode 1: Menggunakan dbdiagram.io (RECOMMENDED)

**Langkah-langkah:**

1. Buka https://dbdiagram.io
2. Klik "Go to App" atau langsung ke https://dbdiagram.io/d
3. Hapus contoh kode yang ada
4. Copy seluruh isi file `ERD-dbdiagram.dbml`
5. Paste ke editor dbdiagram.io
6. Diagram akan otomatis ter-render

**Export ke Gambar:**
- Klik menu "Export" di pojok kanan atas
- Pilih format:
  - **PNG** - untuk presentasi/dokumen (recommended)
  - **SVG** - untuk editing lebih lanjut
  - **PDF** - untuk dokumentasi formal

**Kelebihan:**
- ✅ Tampilan paling profesional
- ✅ Interaktif (bisa zoom, drag)
- ✅ Export berkualitas tinggi
- ✅ Gratis tanpa registrasi

---

### Metode 2: Menggunakan ERD-viewer.html

**Langkah-langkah:**

1. Buka file `database/ERD-viewer.html` di browser
   - Double-click file tersebut, atau
   - Drag file ke browser window
2. Diagram akan langsung tampil
3. Gunakan tombol Zoom In/Out untuk menyesuaikan ukuran

**Cara Export:**

**Opsi A - Screenshot (Paling Mudah):**
- Windows: Tekan `Win + Shift + S`, pilih area diagram
- Mac: Tekan `Cmd + Shift + 4`, pilih area diagram
- Linux: Gunakan screenshot tool bawaan

**Opsi B - Save as Image (Chrome/Edge):**
1. Klik kanan pada diagram
2. Pilih "Save image as..."
3. Simpan sebagai PNG

**Opsi C - Print to PDF:**
1. Klik tombol "Print" atau tekan `Ctrl+P`
2. Pilih "Save as PDF" sebagai printer
3. Atur layout ke "Landscape"
4. Klik "Save"

---

### Metode 3: Menggunakan Mermaid Live Editor

**Langkah-langkah:**

1. Buka https://mermaid.live
2. Hapus kode contoh yang ada
3. Copy kode Mermaid dari file `ERD.md` (bagian dalam blok ```mermaid)
4. Paste ke editor

**Export:**
- Klik "Actions" → "PNG" atau "SVG"
- Atau klik ikon download di pojok kanan atas

**Kelebihan:**
- ✅ Official Mermaid tool
- ✅ Export langsung ke PNG/SVG
- ✅ Bisa edit langsung di browser

---

### Metode 4: Menggunakan VS Code Extension

**Langkah-langkah:**

1. Install extension "Markdown Preview Mermaid Support"
2. Buka file `ERD.md` di VS Code
3. Tekan `Ctrl+Shift+V` untuk preview
4. Klik kanan pada diagram → "Copy Image"
5. Paste ke aplikasi lain atau save

---

### Metode 5: Menggunakan CLI Tools

**Menggunakan Mermaid CLI:**

```bash
# Install mermaid-cli
npm install -g @mermaid-js/mermaid-cli

# Generate PNG
mmdc -i database/ERD.md -o database/ERD.png -t default

# Generate SVG
mmdc -i database/ERD.md -o database/ERD.svg -t default

# Generate PDF
mmdc -i database/ERD.md -o database/ERD.pdf -t default
```

**Menggunakan dbdocs (untuk DBML):**

```bash
# Install dbdocs
npm install -g dbdocs

# Generate documentation
dbdocs build database/ERD-dbdiagram.dbml
```

---

## 🎯 Rekomendasi Berdasarkan Kebutuhan

### Untuk Presentasi
→ **Gunakan dbdiagram.io** → Export PNG (High Quality)

### Untuk Dokumentasi Teknis
→ **Gunakan dbdiagram.io** → Export PDF

### Untuk Quick Preview
→ **Buka ERD-viewer.html** di browser

### Untuk Editing Lanjutan
→ **Gunakan dbdiagram.io** → Export SVG

### Untuk Integrasi dengan Markdown
→ **Gunakan Mermaid Live** → Copy kode dari ERD.md

---

## 📋 Struktur Database

### Total: 25+ Tabel

**Modul Akademik Dasar (7 tabel)**
- prodi
- users
- dosen
- matakuliah
- dosen_matakuliah (pivot)
- ajaran
- kelas

**Modul RPS & Materi (3 tabel)**
- rps
- materi
- evaluasi_artefak

**Modul Kuisioner (3 tabel)**
- kuisioner
- pertanyaan_kuisioner
- jawaban_kuisioner

**Modul Reminder (3 tabel)**
- reminder
- jadwal_reminder
- log_email

**Modul Laporan (3 tabel)**
- laporan_gkm
- laporan_gjm
- laporan_bulanan

**Modul AI & RAG (4 tabel)**
- kuesioner_uploads
- document_chunks
- embeddings_cache
- template_laporan

**Modul Monitoring (3 tabel)**
- monitoring
- pencapaian_kpi
- perwaliaan

---

## 🔗 Relasi Utama

```
PRODI (Hub Utama)
  ├── USERS (One-to-Many)
  ├── DOSEN (One-to-Many)
  ├── MATAKULIAH (One-to-Many)
  ├── LAPORAN_GKM (One-to-Many)
  └── MONITORING (One-to-Many)

DOSEN ←→ MATAKULIAH (Many-to-Many via dosen_matakuliah)

AJARAN (Periode Akademik)
  ├── RPS (One-to-Many)
  ├── KUISIONER (One-to-Many)
  ├── LAPORAN_GKM (One-to-Many)
  └── LAPORAN_GJM (One-to-Many)

RPS
  ├── MATERI (One-to-Many)
  └── EVALUASI_ARTEFAK (One-to-Many)

KUISIONER
  └── PERTANYAAN_KUISIONER (One-to-Many)
      └── JAWABAN_KUISIONER (One-to-Many)

AI & RAG System
  KUESIONER_UPLOADS → DOCUMENT_CHUNKS
  TEMPLATE_LAPORAN → DOCUMENT_CHUNKS
  EMBEDDINGS_CACHE (Cache untuk vector embeddings)
```

---

## 🎨 Tips Visualisasi

### Untuk Diagram Besar:
1. **Gunakan Landscape Mode** saat print/export
2. **Zoom Out** untuk melihat keseluruhan
3. **Export dalam resolusi tinggi** (300 DPI untuk print)
4. **Gunakan format SVG** jika perlu edit di Illustrator/Inkscape

### Untuk Presentasi:
1. **Export beberapa versi** (overview + detail per modul)
2. **Tambahkan highlight** pada bagian yang dibahas
3. **Gunakan background putih** untuk proyektor
4. **Simpan dalam format PNG** untuk kompatibilitas

### Untuk Dokumentasi:
1. **Export ke PDF** untuk arsip
2. **Sertakan legenda** dan penjelasan
3. **Tambahkan metadata** (tanggal, versi)
4. **Simpan source code** (DBML/Mermaid) untuk update

---

## 🔧 Troubleshooting

### Diagram tidak muncul di ERD-viewer.html
- Pastikan koneksi internet aktif (untuk load Mermaid library)
- Coba browser lain (Chrome/Edge recommended)
- Buka Developer Console (F12) untuk lihat error

### Export dari dbdiagram.io gagal
- Coba refresh halaman
- Pastikan diagram sudah ter-render sempurna
- Gunakan browser Chrome/Edge

### Mermaid CLI error
- Pastikan Node.js sudah terinstall
- Update mermaid-cli: `npm update -g @mermaid-js/mermaid-cli`
- Coba dengan puppeteer: `npm install -g puppeteer`

---

## 📚 Resources

- **Mermaid Documentation**: https://mermaid.js.org
- **dbdiagram.io Guide**: https://dbdiagram.io/docs
- **DBML Syntax**: https://dbml.dbdiagram.io/docs
- **Mermaid Live Editor**: https://mermaid.live
- **QuickDBD**: https://www.quickdatabasediagrams.com

---

## 📝 Update Log

**Version 1.0** (April 2026)
- Initial ERD creation
- 25+ tables dengan relasi lengkap
- Support untuk 3 format: Mermaid, DBML, HTML
- Dokumentasi lengkap

---

## 💡 Tips Tambahan

### Membuat ERD Modular (Per Modul)

Jika diagram terlalu besar, Anda bisa membuat ERD terpisah per modul:

1. **ERD Modul Akademik** - Prodi, Users, Dosen, Matakuliah
2. **ERD Modul RPS** - RPS, Materi, Evaluasi
3. **ERD Modul Kuisioner** - Kuisioner, Pertanyaan, Jawaban
4. **ERD Modul Laporan** - Laporan GKM, GJM, Bulanan
5. **ERD Modul AI** - RAG System, Document Chunks

### Menggunakan Draw.io

1. Buka https://app.diagrams.net
2. Import file DBML atau buat manual
3. Export ke PNG/SVG/PDF dengan kualitas tinggi

---

**Dibuat dengan ❤️ untuk Sistem Monitoring Mutu Akademik**
