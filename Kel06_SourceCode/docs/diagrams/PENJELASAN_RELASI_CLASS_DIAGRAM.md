# 📊 Penjelasan Relasi Class Diagram - Sistem Monitoring Mutu Akademik

## 🎯 Ringkasan Relasi

Class diagram ini menggambarkan **36 relasi** antar entitas dalam sistem, terdiri dari:
- **26 relasi wajib** (garis solid `-->`)
- **10 relasi opsional** (garis putus-putus `..>`)

---

## 📋 Daftar Relasi Lengkap

### 1️⃣ **DOMAIN: DATA MASTER**

#### **Relasi 1: users → dosen**
```
users "1" --> "*" dosen : memiliki >
```
**Penjelasan:**
- **Tipe**: One-to-Many (HasMany)
- **Arti**: Satu user dapat memiliki banyak data dosen
- **Contoh**: User dengan role GKM dapat mengelola data banyak dosen di prodinya
- **Foreign Key**: `dosen.user_id` → `users.id`

---

#### **Relasi 2: users → prodi**
```
users "*" --> "1" prodi : milik dari >
```
**Penjelasan:**
- **Tipe**: Many-to-One (BelongsTo)
- **Arti**: Banyak user milik dari satu prodi
- **Contoh**: User GKM terikat ke satu Program Studi (Teknik Informatika)
- **Foreign Key**: `users.prodi_id` → `prodi.id`

---

#### **Relasi 3: dosen → prodi**
```
dosen "*" --> "1" prodi : bekerja di >
```
**Penjelasan:**
- **Tipe**: Many-to-One (BelongsTo)
- **Arti**: Banyak dosen bekerja di satu prodi
- **Contoh**: Dosen A, B, C mengajar di Prodi Teknik Informatika
- **Foreign Key**: `dosen.prodi_id` → `prodi.id`

---

### 2️⃣ **DOMAIN: AKADEMIK**

#### **Relasi 4: matakuliah → prodi**
```
matakuliah "*" --> "1" prodi : ditawarkan oleh >
```
**Penjelasan:**
- **Tipe**: Many-to-One (BelongsTo)
- **Arti**: Banyak mata kuliah ditawarkan oleh satu prodi
- **Contoh**: Basis Data, Pemrograman Web, AI ditawarkan oleh Prodi Informatika
- **Foreign Key**: `matakuliah.prodi_id` → `prodi.id`

---

### 3️⃣ **DOMAIN: RPS & MATERI**

#### **Relasi 5: rps → matakuliah**
```
rps "*" --> "1" matakuliah : untuk >
```
**Penjelasan:**
- **Tipe**: Many-to-One (BelongsTo)
- **Arti**: Banyak RPS untuk satu mata kuliah (per semester berbeda)
- **Contoh**: RPS Basis Data Ganjil 2024, RPS Basis Data Genap 2025
- **Foreign Key**: `rps.matakuliah_id` → `matakuliah.id`

---

#### **Relasi 6: rps → periode_akademik**
```
rps "*" --> "1" periode_akademik : berlaku pada >
```
**Penjelasan:**
- **Tipe**: Many-to-One (BelongsTo)
- **Arti**: Banyak RPS berlaku pada satu periode akademik
- **Contoh**: RPS untuk semester Ganjil 2024/2025
- **Foreign Key**: `rps.periode_id` → `periode_akademik.id`

---

#### **Relasi 7: rps → dosen**
```
rps "*" --> "1" dosen : dibuat oleh >
```
**Penjelasan:**
- **Tipe**: Many-to-One (BelongsTo)
- **Arti**: Banyak RPS dibuat oleh satu dosen
- **Contoh**: Dosen A membuat RPS untuk 3 mata kuliah yang diampu
- **Foreign Key**: `rps.dosen_id` → `dosen.dosen_id`

---

#### **Relasi 8: materi → matakuliah**
```
materi "*" --> "1" matakuliah : bagian dari >
```
**Penjelasan:**
- **Tipe**: Many-to-One (BelongsTo)
- **Arti**: Banyak materi bagian dari satu mata kuliah
- **Contoh**: Materi Pertemuan 1-14 untuk mata kuliah Basis Data
- **Foreign Key**: `materi.matakuliah_id` → `matakuliah.id`

---

#### **Relasi 9: materi → periode_akademik**
```
materi "*" --> "1" periode_akademik : berlaku pada >
```
**Penjelasan:**
- **Tipe**: Many-to-One (BelongsTo)
- **Arti**: Banyak materi berlaku pada satu periode akademik
- **Contoh**: Materi untuk semester Ganjil 2024/2025
- **Foreign Key**: `materi.periode_id` → `periode_akademik.id`

---

#### **Relasi 10: materi → dosen**
```
materi "*" --> "1" dosen : diunggah oleh >
```
**Penjelasan:**
- **Tipe**: Many-to-One (BelongsTo)
- **Arti**: Banyak materi diunggah oleh satu dosen
- **Contoh**: Dosen A mengunggah 14 materi untuk mata kuliahnya
- **Foreign Key**: `materi.dosen_id` → `dosen.dosen_id`

---

### 4️⃣ **DOMAIN: EVALUASI (KUISIONER)**

#### **Relasi 11: kuisioner → periode_akademik**
```
kuisioner "*" --> "1" periode_akademik : dilaksanakan pada >
```
**Penjelasan:**
- **Tipe**: Many-to-One (BelongsTo)
- **Arti**: Banyak kuisioner dilaksanakan pada satu periode
- **Contoh**: Kuisioner UTS dan UAS di semester Ganjil 2024/2025
- **Foreign Key**: `kuisioner.periode_id` → `periode_akademik.id`

---

#### **Relasi 12: kuisioner → matakuliah**
```
kuisioner "*" --> "1" matakuliah : mengevaluasi >
```
**Penjelasan:**
- **Tipe**: Many-to-One (BelongsTo)
- **Arti**: Banyak kuisioner mengevaluasi satu mata kuliah
- **Contoh**: Kuisioner UTS dan UAS untuk mata kuliah Basis Data
- **Foreign Key**: `kuisioner.matakuliah_id` → `matakuliah.id`

---

#### **Relasi 13: kuisioner → dosen**
```
kuisioner "*" --> "1" dosen : untuk >
```
**Penjelasan:**
- **Tipe**: Many-to-One (BelongsTo)
- **Arti**: Banyak kuisioner untuk menilai satu dosen
- **Contoh**: Mahasiswa mengisi kuisioner evaluasi untuk Dosen A
- **Foreign Key**: `kuisioner.dosen_id` → `dosen.dosen_id`

---

#### **Relasi 14: kuisioner → pertanyaan_kuisioner**
```
kuisioner "1" --> "1" pertanyaan_kuisioner : merujuk ke >
```
**Penjelasan:**
- **Tipe**: One-to-One (References)
- **Arti**: Satu kuisioner merujuk ke satu set pertanyaan
- **Contoh**: Kuisioner UTS menggunakan template pertanyaan standar
- **Foreign Key**: `kuisioner.pertanyaan_id` → `pertanyaan_kuisioner.id`

---

#### **Relasi 15: pertanyaan_kuisioner → kuisioner**
```
pertanyaan_kuisioner "*" --> "1" kuisioner : bagian dari >
```
**Penjelasan:**
- **Tipe**: Many-to-One (BelongsTo)
- **Arti**: Banyak pertanyaan bagian dari satu kuisioner
- **Contoh**: Pertanyaan 1-10 dalam kuisioner evaluasi dosen
- **Foreign Key**: `pertanyaan_kuisioner.kuisioner_id` → `kuisioner.id`

---

### 5️⃣ **DOMAIN: PENGINGAT (REMINDER)**

#### **Relasi 16: reminder → users** (Wajib)
```
reminder "*" --> "1" users : dikirim ke >
```
**Penjelasan:**
- **Tipe**: Many-to-One (BelongsTo)
- **Arti**: Banyak reminder dikirim ke satu user
- **Contoh**: Dosen menerima 5 reminder untuk upload RPS dan materi
- **Foreign Key**: `reminder.user_id` → `users.id`

---

#### **Relasi 17: reminder → dosen** (Opsional)
```
reminder "*" ..> "0..1" dosen : tentang >
```
**Penjelasan:**
- **Tipe**: Many-to-One Optional (BelongsTo nullable)
- **Arti**: Reminder bisa tentang seorang dosen tertentu (opsional)
- **Contoh**: Reminder kepada Kaprodi tentang Dosen A yang belum upload RPS
- **Foreign Key**: `reminder.dosen_id` → `dosen.dosen_id` *(nullable)*

---

#### **Relasi 18: reminder → matakuliah** (Opsional)
```
reminder "*" ..> "0..1" matakuliah : tentang >
```
**Penjelasan:**
- **Tipe**: Many-to-One Optional (BelongsTo nullable)
- **Arti**: Reminder bisa tentang mata kuliah tertentu (opsional)
- **Contoh**: Reminder untuk upload materi mata kuliah Basis Data
- **Foreign Key**: `reminder.matakuliah_id` → `matakuliah.id` *(nullable)*

---

#### **Relasi 19: reminder → rps** (Opsional)
```
reminder "*" ..> "0..1" rps : tentang >
```
**Penjelasan:**
- **Tipe**: Many-to-One Optional (BelongsTo nullable)
- **Arti**: Reminder bisa tentang RPS tertentu (opsional)
- **Contoh**: Reminder untuk review RPS Basis Data Semester Ganjil
- **Foreign Key**: `reminder.rps_id` → `rps.id` *(nullable)*

---

#### **Relasi 20: reminder → materi** (Opsional)
```
reminder "*" ..> "0..1" materi : tentang >
```
**Penjelasan:**
- **Tipe**: Many-to-One Optional (BelongsTo nullable)
- **Arti**: Reminder bisa tentang materi tertentu (opsional)
- **Contoh**: Reminder untuk upload materi pertemuan ke-5
- **Foreign Key**: `reminder.materi_id` → `materi.id` *(nullable)*

---

### 6️⃣ **DOMAIN: LAPORAN**

#### **Relasi 21: laporan → users**
```
laporan "*" --> "1" users : dibuat oleh >
```
**Penjelasan:**
- **Tipe**: Many-to-One (BelongsTo)
- **Arti**: Banyak laporan dibuat oleh satu user
- **Contoh**: User GKM membuat laporan triwulan, semester, VMTS
- **Foreign Key**: `laporan.user_id` → `users.id`

---

#### **Relasi 22: laporan → template_laporan**
```
laporan "*" --> "1" template_laporan : menggunakan >
```
**Penjelasan:**
- **Tipe**: Many-to-One (BelongsTo)
- **Arti**: Banyak laporan menggunakan satu template
- **Contoh**: 10 laporan triwulan menggunakan template standar triwulan
- **Foreign Key**: `laporan.template_id` → `template_laporan.id`

---

#### **Relasi 23: laporan → prodi** (Opsional)
```
laporan "*" ..> "0..1" prodi : untuk >
```
**Penjelasan:**
- **Tipe**: Many-to-One Optional (BelongsTo nullable)
- **Arti**: Laporan bisa untuk prodi tertentu (opsional)
- **Contoh**: Laporan monitoring RPS untuk Prodi Teknik Informatika
- **Foreign Key**: `laporan.prodi_id` → `prodi.id` *(nullable)*

---

#### **Relasi 24: laporan → periode_akademik** (Opsional)
```
laporan "*" ..> "0..1" periode_akademik : pada periode >
```
**Penjelasan:**
- **Tipe**: Many-to-One Optional (BelongsTo nullable)
- **Arti**: Laporan bisa pada periode tertentu (opsional)
- **Contoh**: Laporan semester untuk periode Ganjil 2024/2025
- **Foreign Key**: `laporan.periode_id` → `periode_akademik.id` *(nullable)*

---

#### **Relasi 25: laporan → dosen** (Opsional)
```
laporan "*" ..> "0..1" dosen : tentang >
```
**Penjelasan:**
- **Tipe**: Many-to-One Optional (BelongsTo nullable)
- **Arti**: Laporan bisa tentang dosen tertentu (opsional)
- **Contoh**: Laporan evaluasi kinerja untuk Dosen A
- **Foreign Key**: `laporan.dosen_id` → `dosen.dosen_id` *(nullable)*

---

#### **Relasi 26: kirim_laporan → users**
```
kirim_laporan "*" --> "1" users : dikirim oleh >
```
**Penjelasan:**
- **Tipe**: Many-to-One (BelongsTo)
- **Arti**: Banyak riwayat pengiriman laporan dilakukan oleh satu user
- **Contoh**: User GKM mengirim laporan via email ke atasan
- **Foreign Key**: `kirim_laporan.user_id` → `users.id`

---

## 📊 Statistik Relasi

### Berdasarkan Tipe:

| Tipe Relasi | Jumlah | Persentase |
|-------------|--------|------------|
| **Many-to-One (BelongsTo)** | 26 | 72% |
| **One-to-Many (HasMany)** | 26 | 72% |
| **One-to-One** | 1 | 3% |
| **Optional (Nullable)** | 10 | 28% |

### Berdasarkan Domain:

| Domain | Jumlah Relasi |
|--------|---------------|
| Data Master | 3 |
| Akademik | 1 |
| RPS & Materi | 6 |
| Kuisioner | 5 |
| Reminder | 5 |
| Laporan | 6 |
| **Total** | **26** |

---

## 🔑 Kunci Membaca Diagram

### Simbol Cardinality:

| Simbol | Arti |
|--------|------|
| `"1"` | Exactly one (tepat satu) |
| `"*"` | Zero or more (nol atau banyak) |
| `"0..1"` | Zero or one (nol atau satu / opsional) |

### Jenis Garis:

| Garis | Arti |
|-------|------|
| `-->` | Relasi wajib (solid line) |
| `..>` | Relasi opsional (dotted line) |

### Arah Panah:

- **Dari kiri ke kanan**: Entity di kiri "menunjuk ke" entity di kanan
- **Label relasi**: Menjelaskan sifat hubungan

---

## 💡 Contoh Skenario Penggunaan

### Skenario 1: Dosen Upload Materi
1. `dosen` milik `prodi` ✅
2. `dosen` mengajar `matakuliah` ✅
3. `dosen` membuat `rps` untuk `matakuliah` pada `periode_akademik` ✅
4. `dosen` upload `materi` untuk `matakuliah` pada `periode_akademik` ✅

### Skenario 2: GKM Buat Laporan
1. `users` (role GKM) milik `prodi` ✅
2. `users` pilih `template_laporan` ✅
3. `users` buat `laporan` untuk `prodi` pada `periode_akademik` ✅
4. `users` kirim `laporan` via `kirim_laporan` ✅

### Skenario 3: Mahasiswa Isi Kuisioner
1. `kuisioner` dilaksanakan pada `periode_akademik` ✅
2. `kuisioner` mengevaluasi `matakuliah` ✅
3. `kuisioner` untuk menilai `dosen` ✅
4. `kuisioner` merujuk `pertanyaan_kuisioner` ✅
5. Mahasiswa isi `pertanyaan_kuisioner` ✅

---

## 📝 Catatan Penting

### Relasi Wajib vs Opsional:

**Wajib (solid `-->`):**
- Foreign key **NOT NULL**
- Harus ada nilai
- Contoh: RPS harus punya matakuliah_id

**Opsional (dotted `..>`):**
- Foreign key **NULLABLE**
- Boleh kosong
- Contoh: Reminder bisa tentang dosen tertentu atau tidak

### Foreign Key Constraints:

Semua relasi menggunakan **ON DELETE CASCADE** atau **ON DELETE SET NULL**:
- **CASCADE**: Jika parent dihapus, child ikut terhapus
- **SET NULL**: Jika parent dihapus, FK di child jadi NULL

---

**Generated**: 2026-06-16  
**Total Relasi**: 26 relasi  
**Format**: PlantUML Class Diagram  
**Source**: `CLASS_DIAGRAM_FROM_SQL_INDONESIA.puml`
