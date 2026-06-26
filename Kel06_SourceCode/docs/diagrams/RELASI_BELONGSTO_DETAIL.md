# 📚 Detail Relasi BelongsTo dalam Sistem

## 🎯 Apa itu BelongsTo?

**BelongsTo** adalah relasi database di Laravel yang menunjukkan bahwa suatu entitas **"milik dari"** atau **"dimiliki oleh"** entitas lain. Ini adalah sisi **"many"** dari relasi **"many-to-one"**.

### Karakteristik BelongsTo:
- ✅ Memiliki **Foreign Key** di tabelnya sendiri
- ✅ Mengacu ke **Primary Key** di tabel parent
- ✅ Menunjukkan **dependency** (ketergantungan)
- ✅ Di diagram: ditandai dengan `BelongsTo` atau `<< milik dari >>`

---

## 📊 Daftar Lengkap Relasi BelongsTo dalam Sistem

### 1️⃣ **users → prodi**
```php
// Di Model User
public function prodi(): BelongsTo {
    return $this->belongsTo(Prodi::class);
}
```
**Arti**: Setiap user **milik dari** satu prodi  
**FK**: `users.prodi_id` → `prodi.id`  
**Contoh**: User dengan role GKM pasti terikat ke 1 Program Studi

---

### 2️⃣ **dosen → user**
```php
// Di Model Dosen
public function user(): BelongsTo {
    return $this->belongsTo(User::class);
}
```
**Arti**: Setiap dosen **milik dari** satu akun user  
**FK**: `dosen.user_id` → `users.id`  
**Contoh**: Dosen login menggunakan akun User

---

### 3️⃣ **dosen → prodi**
```php
// Di Model Dosen
public function prodi(): BelongsTo {
    return $this->belongsTo(Prodi::class);
}
```
**Arti**: Setiap dosen **milik dari** satu prodi  
**FK**: `dosen.prodi_id` → `prodi.id`  
**Contoh**: Dosen mengajar di 1 Program Studi tertentu

---

### 4️⃣ **matakuliah → prodi**
```php
// Di Model Matakuliah
public function prodi(): BelongsTo {
    return $this->belongsTo(Prodi::class);
}
```
**Arti**: Setiap mata kuliah **milik dari** satu prodi  
**FK**: `matakuliah.prodi_id` → `prodi.id`  
**Contoh**: Basis Data adalah mata kuliah di Prodi Informatika

---

### 5️⃣ **rps → matakuliah**
```php
// Di Model RPS
public function matakuliah(): BelongsTo {
    return $this->belongsTo(Matakuliah::class);
}
```
**Arti**: Setiap RPS **untuk** satu mata kuliah  
**FK**: `rps.matakuliah_id` → `matakuliah.id`  
**Contoh**: RPS Basis Data Semester Ganjil 2024/2025

---

### 6️⃣ **rps → periode_akademik**
```php
// Di Model RPS
public function ajaran(): BelongsTo {
    return $this->belongsTo(Ajaran::class); // atau PeriodeAkademik
}
```
**Arti**: Setiap RPS **berlaku pada** satu periode  
**FK**: `rps.periode_id` → `periode_akademik.id`  
**Contoh**: RPS untuk Semester Ganjil 2024/2025

---

### 7️⃣ **rps → dosen**
```php
// Di Model RPS
public function dosen(): BelongsTo {
    return $this->belongsTo(Dosen::class);
}
```
**Arti**: Setiap RPS **dibuat oleh** satu dosen  
**FK**: `rps.dosen_id` → `dosen.dosen_id`  
**Contoh**: RPS dibuat oleh Dosen A

---

### 8️⃣ **materi → matakuliah**
```php
// Di Model Materi
public function matakuliah(): BelongsTo {
    return $this->belongsTo(Matakuliah::class);
}
```
**Arti**: Setiap materi **milik dari** satu mata kuliah  
**FK**: `materi.matakuliah_id` → `matakuliah.id`  
**Contoh**: Slide tentang Normalisasi DB untuk mata kuliah Basis Data

---

### 9️⃣ **materi → periode_akademik**
```php
// Di Model Materi
public function periode(): BelongsTo {
    return $this->belongsTo(PeriodeAkademik::class);
}
```
**Arti**: Setiap materi **berlaku pada** satu periode  
**FK**: `materi.periode_id` → `periode_akademik.id`

---

### 🔟 **materi → dosen**
```php
// Di Model Materi
public function dosen(): BelongsTo {
    return $this->belongsTo(Dosen::class);
}
```
**Arti**: Setiap materi **diunggah oleh** satu dosen  
**FK**: `materi.dosen_id` → `dosen.dosen_id`

---

### 1️⃣1️⃣ **kuisioner → periode_akademik**
```php
// Di Model Kuisioner
public function periode(): BelongsTo {
    return $this->belongsTo(PeriodeAkademik::class);
}
```
**Arti**: Setiap kuisioner **pada periode** tertentu  
**FK**: `kuisioner.periode_id` → `periode_akademik.id`

---

### 1️⃣2️⃣ **kuisioner → matakuliah**
```php
// Di Model Kuisioner
public function matakuliah(): BelongsTo {
    return $this->belongsTo(Matakuliah::class);
}
```
**Arti**: Setiap kuisioner **evaluasi untuk** satu mata kuliah  
**FK**: `kuisioner.matakuliah_id` → `matakuliah.id`

---

### 1️⃣3️⃣ **kuisioner → dosen**
```php
// Di Model Kuisioner
public function dosen(): BelongsTo {
    return $this->belongsTo(Dosen::class);
}
```
**Arti**: Setiap kuisioner **untuk** satu dosen  
**FK**: `kuisioner.dosen_id` → `dosen.dosen_id`

---

### 1️⃣4️⃣ **pertanyaan_kuisioner → kuisioner**
```php
// Di Model PertanyaanKuisioner
public function kuisioner(): BelongsTo {
    return $this->belongsTo(Kuisioner::class);
}
```
**Arti**: Setiap pertanyaan **bagian dari** satu kuisioner  
**FK**: `pertanyaan_kuisioner.kuisioner_id` → `kuisioner.id`

---

### 1️⃣5️⃣ **reminder → users** (pembuat)
```php
// Di Model Reminder
public function userPembuat(): BelongsTo {
    return $this->belongsTo(User::class, 'user_pembuat_id');
}
```
**Arti**: Setiap reminder **dibuat oleh** satu user  
**FK**: `reminder.user_pembuat_id` → `users.id`

---

### 1️⃣6️⃣ **reminder → users** (penerima)
```php
// Di Model Reminder
public function userPenerima(): BelongsTo {
    return $this->belongsTo(User::class, 'user_penerima_id');
}
```
**Arti**: Setiap reminder **dikirim ke** satu user  
**FK**: `reminder.user_penerima_id` → `users.id`

---

### 1️⃣7️⃣ **reminder → dosen** *(opsional)*
```php
// Di Model Reminder
public function dosen(): BelongsTo {
    return $this->belongsTo(Dosen::class);
}
```
**Arti**: Reminder **tentang** dosen tertentu *(nullable)*  
**FK**: `reminder.dosen_id` → `dosen.dosen_id` *(null allowed)*

---

### 1️⃣8️⃣ **reminder → matakuliah** *(opsional)*
```php
// Di Model Reminder
public function matakuliah(): BelongsTo {
    return $this->belongsTo(Matakuliah::class);
}
```
**Arti**: Reminder **tentang** mata kuliah tertentu *(nullable)*  
**FK**: `reminder.matakuliah_id` → `matakuliah.id` *(null allowed)*

---

### 1️⃣9️⃣ **reminder → rps** *(opsional)*
```php
// Di Model Reminder
public function rps(): BelongsTo {
    return $this->belongsTo(RPS::class);
}
```
**Arti**: Reminder **tentang** RPS tertentu *(nullable)*  
**FK**: `reminder.rps_id` → `rps.id` *(null allowed)*

---

### 2️⃣0️⃣ **reminder → materi** *(opsional)*
```php
// Di Model Reminder
public function materi(): BelongsTo {
    return $this->belongsTo(Materi::class);
}
```
**Arti**: Reminder **tentang** materi tertentu *(nullable)*  
**FK**: `reminder.materi_id` → `materi.id` *(null allowed)*

---

### 2️⃣1️⃣ **template_laporan → prodi** *(optional)*
```php
// Di Model TemplateLaporan
public function prodi(): BelongsTo {
    return $this->belongsTo(Prodi::class);
}
```
**Arti**: Template laporan **untuk** prodi tertentu  
**FK**: `template_laporan.prodi_id` → `prodi.id`

---

### 2️⃣2️⃣ **template_laporan → users** (uploader)
```php
// Di Model TemplateLaporan
public function uploader(): BelongsTo {
    return $this->belongsTo(User::class, 'uploaded_by');
}
```
**Arti**: Template **diunggah oleh** user tertentu  
**FK**: `template_laporan.uploaded_by` → `users.id`

---

### 2️⃣3️⃣ **laporan → users**
```php
// Di Model Laporan
public function user(): BelongsTo {
    return $this->belongsTo(User::class);
}
```
**Arti**: Laporan **dibuat oleh** satu user  
**FK**: `laporan.user_id` → `users.id`

---

### 2️⃣4️⃣ **laporan → template_laporan**
```php
// Di Model Laporan
public function template(): BelongsTo {
    return $this->belongsTo(TemplateLaporan::class, 'template_id');
}
```
**Arti**: Laporan **menggunakan** template tertentu  
**FK**: `laporan.template_id` → `template_laporan.id`

---

### 2️⃣5️⃣ **laporan → prodi** *(opsional)*
```php
// Di Model Laporan
public function prodi(): BelongsTo {
    return $this->belongsTo(Prodi::class);
}
```
**Arti**: Laporan **untuk** prodi tertentu *(nullable)*  
**FK**: `laporan.prodi_id` → `prodi.id` *(null allowed)*

---

### 2️⃣6️⃣ **laporan → periode_akademik** *(opsional)*
```php
// Di Model Laporan
public function ajaran(): BelongsTo {
    return $this->belongsTo(Ajaran::class);
}
```
**Arti**: Laporan **pada periode** tertentu *(nullable)*  
**FK**: `laporan.periode_id` → `periode_akademik.id` *(null allowed)*

---

### 2️⃣7️⃣ **laporan → dosen** *(opsional)*
```php
// Di Model Laporan
public function dosen(): BelongsTo {
    return $this->belongsTo(Dosen::class);
}
```
**Arti**: Laporan **tentang** dosen tertentu *(nullable)*  
**FK**: `laporan.dosen_id` → `dosen.dosen_id` *(null allowed)*

---

### 2️⃣8️⃣ **kirim_laporan → users**
```php
// Di Model KirimLaporan
public function user(): BelongsTo {
    return $this->belongsTo(User::class);
}
```
**Arti**: Pengiriman laporan **dilakukan oleh** satu user  
**FK**: `kirim_laporan.user_id` → `users.id`

---

## 📈 Ringkasan Statistik

| Kategori | Jumlah Relasi BelongsTo |
|----------|-------------------------|
| **Wajib (NOT NULL)** | 18 relasi |
| **Opsional (NULL)** | 10 relasi |
| **Total** | **28 relasi BelongsTo** |

---

## 🔍 Cara Membedakan di Diagram

### Visual Indicator:

1. **Method Name dengan Emoji**
   ```
   +prodi() BelongsTo
   ```

2. **Label pada Garis**
   ```
   dosen "*" --> "1" prodi : << milik dari >>
   ```

3. **Color Coding** (di versi Simple)
   - <span style="color:green">**Hijau**</span> = Method BelongsTo

4. **Annotasi FK** (di versi Lengkap)
   ```
   +prodi_id: BIGINT <<FK>>
   ```

5. **Nullable Marker**
   ```
   +dosen_id: BIGINT {nullable}
   atau
   +dosen_id: BIGINT {null}
   ```

---

## 🎓 Kenapa BelongsTo Penting?

1. **Data Integrity**: Foreign Key constraint menjaga konsistensi data
2. **Referential Integrity**: Mencegah orphan records
3. **Query Optimization**: Eager loading untuk performa
4. **Business Logic**: Menunjukkan dependency antar entitas
5. **Documentation**: Memperjelas struktur data sistem

---

**Generated**: 2026-06-16  
**Total BelongsTo Relations**: 28  
**Source**: Laravel Models Analysis
