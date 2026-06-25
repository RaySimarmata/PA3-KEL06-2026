# DESIGN DATABASE UNTUK DRAW.IO
## Project PA3 GKM/GJM - Database Final Version

**Target:** ERD yang bersih untuk desain database baru di draw.io  
**Status:** Production Ready (setelah cleanup)  
**Tanggal:** 13 Juni 2026

---

## 🎯 TABEL YANG HARUS ADA (19 Tabel Aktif)

### **GRUP 1: CORE SYSTEM (Warna Biru)**

#### 1. **users** ✅
```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('GKM', 'GJM', 'Admin') NOT NULL,
    prodi_id BIGINT NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (prodi_id) REFERENCES prodi(id) ON DELETE RESTRICT
);
```

#### 2. **prodi** ✅
```sql
CREATE TABLE prodi (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    kode_prodi VARCHAR(10) UNIQUE NOT NULL,
    nama_prodi VARCHAR(255) NOT NULL,
    kaprodi_id BIGINT NULL,
    fakultas VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### 3. **periode_akademik** ✅
```sql
CREATE TABLE periode_akademik (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tahun_ajaran VARCHAR(20) NOT NULL, -- "2024/2025"
    semester ENUM('1', '2') NOT NULL,
    semester_label VARCHAR(50) NULL, -- "Ganjil", "Genap"
    is_active BOOLEAN DEFAULT 0,
    start_date DATE NULL,
    end_date DATE NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    UNIQUE KEY uk_periode (tahun_ajaran, semester)
);
```

---

### **GRUP 2: EXTERNAL API DATA (Warna Merah)**

#### 4. **dosenn** ✅
```sql
CREATE TABLE dosenn (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    pegawai_id VARCHAR(50) UNIQUE NOT NULL,
    nama VARCHAR(255) NOT NULL,
    nidn VARCHAR(20) UNIQUE NULL,
    email VARCHAR(255) UNIQUE NULL,
    nomor_telepon VARCHAR(20) NULL,
    jabatan_akademik VARCHAR(50) NULL,
    sync_status VARCHAR(50) DEFAULT 'synced',
    last_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### 5. **jadwal_dosen** ✅
```sql
CREATE TABLE jadwal_dosen (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    pegawai_id VARCHAR(50) NOT NULL,
    kode_mk VARCHAR(20) NOT NULL,
    nama_mk VARCHAR(255) NOT NULL,
    kelas VARCHAR(10) NOT NULL,
    semester VARCHAR(5) NOT NULL,
    tahun_ajaran VARCHAR(20) NOT NULL,
    tingkat INT NULL,
    sks INT NULL,
    is_manual BOOLEAN DEFAULT 0,
    sync_status VARCHAR(50) DEFAULT 'synced',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (pegawai_id) REFERENCES dosenn(pegawai_id) ON DELETE CASCADE,
    
    INDEX idx_jadwal_periode (pegawai_id, semester, tahun_ajaran),
    INDEX idx_jadwal_matkul (kode_mk, tahun_ajaran, semester)
);
```

---

### **GRUP 3: MONITORING SYSTEM - GKM (Warna Hijau)**

#### 6. **kuesioner_uploads** ✅
```sql
CREATE TABLE kuesioner_uploads (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    pegawai_id VARCHAR(50) NOT NULL,
    kode_matakuliah VARCHAR(20) NOT NULL,
    nama_matakuliah VARCHAR(255) NULL,
    tingkat VARCHAR(10) NULL,
    jenis_kuesioner VARCHAR(50) NULL, -- "UTS", "UAS", "Tengah Semester"
    periode VARCHAR(20) NULL,
    semester VARCHAR(5) NULL,
    
    -- AI Processing Fields
    extracted_data JSON NULL,
    ai_analysis TEXT NULL,
    processing_status VARCHAR(50) DEFAULT 'uploaded',
    
    -- Statistics Fields
    statistik_kepuasan DECIMAL(5,2) NULL,
    statistik_rata_rata DECIMAL(5,2) NULL,
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_kuesioner_periode (periode, jenis_kuesioner),
    INDEX idx_kuesioner_dosen (pegawai_id, semester)
);
```

#### 7. **rps_monitoring_snapshots** ✅
```sql
CREATE TABLE rps_monitoring_snapshots (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    prodi_id BIGINT NOT NULL,
    periode_id BIGINT NOT NULL,
    pegawai_id VARCHAR(50) NOT NULL,
    kode_mk VARCHAR(20) NOT NULL,
    nama_mk VARCHAR(255) NOT NULL,
    kelas VARCHAR(10) NULL,
    tingkat INT NULL,
    status_rps VARCHAR(50) NOT NULL, -- "Ada", "Tidak Ada", "Belum Upload"
    tanggal_upload DATE NULL,
    compliance_score DECIMAL(5,2) NULL,
    last_sync TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (prodi_id) REFERENCES prodi(id) ON DELETE CASCADE,
    FOREIGN KEY (periode_id) REFERENCES periode_akademik(id) ON DELETE CASCADE,
    
    INDEX idx_rps_monitoring (prodi_id, periode_id, pegawai_id),
    INDEX idx_rps_compliance (prodi_id, status_rps)
);
```

#### 8. **perkuliahan_monitoring_snapshots** ✅
```sql
CREATE TABLE perkuliahan_monitoring_snapshots (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    prodi_id BIGINT NOT NULL,
    periode_id BIGINT NOT NULL,
    pegawai_id VARCHAR(50) NOT NULL,
    kode_mk VARCHAR(20) NOT NULL,
    nama_mk VARCHAR(255) NOT NULL,
    kelas VARCHAR(10) NULL,
    
    -- Status Monitoring
    status_upload_materi VARCHAR(50) NULL, -- "Ada", "Tidak Ada"
    status_review_soal VARCHAR(50) NULL,   -- "Ada", "Tidak Ada"
    
    -- Timestamps
    last_activity DATE NULL,
    compliance_percentage DECIMAL(5,2) NULL,
    last_sync TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (prodi_id) REFERENCES prodi(id) ON DELETE CASCADE,
    FOREIGN KEY (periode_id) REFERENCES periode_akademik(id) ON DELETE CASCADE,
    
    INDEX idx_perkuliahan_monitoring (prodi_id, periode_id, pegawai_id),
    INDEX idx_perkuliahan_compliance (prodi_id, status_upload_materi, status_review_soal)
);
```

---

### **GRUP 4: REPORTING SYSTEM - GJM & GKM (Warna Kuning)**

#### 9. **laporan_gjm** ✅
```sql
CREATE TABLE laporan_gjm (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    ajaran_id BIGINT NULL, -- TO BE MIGRATED to periode_id
    template_id BIGINT NOT NULL,
    jenis_laporan ENUM('triwulan', 'semester', 'vmts', 'ppt') NOT NULL,
    judul_laporan VARCHAR(255) NOT NULL,
    dokumen_hasil_path VARCHAR(500) NULL,
    status_laporan VARCHAR(50) DEFAULT 'draft',
    
    -- AI & OCR Processing
    ai_konten_preview TEXT NULL,
    ai_ocr_data JSON NULL,
    ai_file_ids JSON NULL,
    ragas_metrics JSON NULL,
    processing_status VARCHAR(50) DEFAULT 'pending',
    conversation_history JSON NULL,
    
    -- Metadata
    total_halaman INT NULL,
    processing_time INT NULL, -- seconds
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (template_id) REFERENCES template_laporan(id) ON DELETE RESTRICT,
    
    INDEX idx_laporan_gjm (jenis_laporan, status_laporan),
    INDEX idx_laporan_gjm_periode (ajaran_id, jenis_laporan)
);
```

#### 10. **laporan_bulanan** ✅
```sql
CREATE TABLE laporan_bulanan (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    prodi_id BIGINT NOT NULL,
    template_id BIGINT NULL,
    periode_bulan VARCHAR(10) NOT NULL, -- "2024-01", "2024-02"
    tipe_laporan VARCHAR(50) NOT NULL, -- "kuesioner", "monitoring"
    judul_laporan VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NULL,
    status VARCHAR(50) DEFAULT 'pending',
    
    -- AI Processing
    ai_preview_content TEXT NULL,
    processing_time INT NULL, -- seconds
    error_message TEXT NULL,
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (prodi_id) REFERENCES prodi(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES template_laporan(id) ON DELETE SET NULL,
    
    INDEX idx_laporan_bulanan (prodi_id, periode_bulan),
    INDEX idx_laporan_bulanan_status (status, tipe_laporan)
);
```

#### 11. **laporan_gkm** ⚠️ (Usage: Minimal)
```sql
CREATE TABLE laporan_gkm (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    ajaran_id BIGINT NULL, -- TO BE MIGRATED to periode_id
    prodi_id BIGINT NOT NULL,
    jenis_laporan VARCHAR(100) NOT NULL, -- "artefak"
    judul_laporan VARCHAR(255) NOT NULL,
    file_laporan VARCHAR(500) NULL,
    tanggal_buat_laporan DATE NOT NULL,
    
    -- Artefak Specific Fields
    artefak_jenis VARCHAR(50) NULL, -- "RPS", "Materi", "All"
    artefak_periode_start DATE NULL,
    artefak_periode_end DATE NULL,
    artefak_target_dosen JSON NULL,
    
    -- AI Processing
    ai_preview_content TEXT NULL,
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (prodi_id) REFERENCES prodi(id) ON DELETE CASCADE,
    
    INDEX idx_laporan_gkm (prodi_id, jenis_laporan)
);
```

#### 12. **template_laporan** ✅
```sql
CREATE TABLE template_laporan (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nama_template VARCHAR(255) NOT NULL,
    jenis_laporan VARCHAR(100) NOT NULL, -- "triwulan", "semester", "vmts", "bulanan"
    file_path VARCHAR(500) NOT NULL,
    prodi_id BIGINT NULL, -- NULL = global template
    placeholder_config JSON NULL,
    is_active BOOLEAN DEFAULT 1,
    version VARCHAR(10) DEFAULT '1.0',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (prodi_id) REFERENCES prodi(id) ON DELETE CASCADE,
    
    INDEX idx_template (jenis_laporan, is_active, prodi_id)
);
```

---

### **GRUP 5: AI & VECTOR DATABASE (Warna Ungu)**

#### 13. **document_chunks** ✅
```sql
CREATE TABLE document_chunks (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    template_id BIGINT NOT NULL,
    chunk_text TEXT NOT NULL,
    chunk_index INT NOT NULL,
    source_file VARCHAR(255) NOT NULL,
    source_page INT NULL,
    chunk_size INT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (template_id) REFERENCES template_laporan(id) ON DELETE CASCADE,
    
    INDEX idx_document_chunks (template_id, chunk_index),
    FULLTEXT KEY ft_chunk_text (chunk_text)
);
```

#### 14. **embeddings_cache** ✅
```sql
CREATE TABLE embeddings_cache (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    text_hash VARCHAR(64) UNIQUE NOT NULL,
    embedding JSON NOT NULL,
    model_name VARCHAR(100) NOT NULL,
    text_length INT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_embeddings (model_name, created_at)
);
```

---

### **GRUP 6: NOTIFICATION SYSTEM (Warna Orange)**

#### 15. **jadwal_reminder** ✅
```sql
CREATE TABLE jadwal_reminder (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    prodi_id BIGINT NOT NULL,
    tipe_reminder VARCHAR(100) NOT NULL, -- "rps", "materi", "soal"
    judul VARCHAR(255) NOT NULL,
    pesan TEXT NOT NULL,
    jadwal_kirim VARCHAR(100) NOT NULL, -- cron expression "0 9 * * 1"
    is_active BOOLEAN DEFAULT 1,
    last_sent_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (prodi_id) REFERENCES prodi(id) ON DELETE CASCADE,
    
    INDEX idx_jadwal_reminder (is_active, tipe_reminder),
    INDEX idx_jadwal_prodi (prodi_id, is_active)
);
```

#### 16. **log_email** ✅
```sql
CREATE TABLE log_email (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    reminder_id BIGINT NULL,
    prodi_id BIGINT NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message_body TEXT NULL,
    status VARCHAR(50) NOT NULL, -- "sent", "failed", "pending"
    sent_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP NULL,
    
    FOREIGN KEY (reminder_id) REFERENCES jadwal_reminder(id) ON DELETE CASCADE,
    FOREIGN KEY (prodi_id) REFERENCES prodi(id) ON DELETE CASCADE,
    
    INDEX idx_log_email (prodi_id, status, sent_at),
    INDEX idx_log_email_reminder (reminder_id, sent_at)
);
```

#### 17. **kirim_laporan_history** ✅
```sql
CREATE TABLE kirim_laporan_history (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    laporan_type VARCHAR(50) NOT NULL, -- "gjm", "gkm", "bulanan"
    laporan_id BIGINT NOT NULL,
    recipients JSON NOT NULL, -- [{email, name, role}]
    sent_by BIGINT NOT NULL,
    email_subject VARCHAR(255) NOT NULL,
    sent_at TIMESTAMP NOT NULL,
    delivery_status JSON NULL, -- [{email, status, timestamp}]
    created_at TIMESTAMP NULL,
    
    FOREIGN KEY (sent_by) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_kirim_laporan (laporan_type, laporan_id),
    INDEX idx_kirim_laporan_user (sent_by, sent_at)
);
```

---

### **GRUP 7: SUPPORT TABLES (Warna Abu-abu)**

#### 18. **cache** ✅ (Laravel Framework)
```sql
CREATE TABLE cache (
    `key` VARCHAR(255) PRIMARY KEY,
    value LONGTEXT NOT NULL,
    expiration INT NOT NULL,
    
    INDEX idx_cache_expiration (expiration)
);
```

#### 19. **jobs** ✅ (Laravel Queue)
```sql
CREATE TABLE jobs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    queue VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL,
    reserved_at INT UNSIGNED NULL,
    available_at INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    
    INDEX idx_jobs_queue_reserved_at (queue, reserved_at)
);
```

---

## 🗄️ MONGODB COLLECTIONS (Terpisah)

### **Collection 1: ai_response_cache_mongo** ✅
```javascript
{
  _id: ObjectId,
  cache_key: String, // unique hash
  prompt_hash: String,
  original_prompt: String,
  context_metadata: {
    prodi: String,
    periode: String,
    jenis_laporan: String
  },
  ai_response: String, // large text
  ai_provider: String, // "groq", "openai", "claude"
  ai_model: String, // "llama3", "gpt-4", "claude-3"
  usage_count: Number,
  last_used_at: Date,
  response_length: Number,
  similarity_threshold: Number,
  created_at: Date,
  updated_at: Date,
  expires_at: Date // TTL index
}
```

### **Collection 2: hasil_analisis_mongo** ✅
```javascript
{
  _id: ObjectId,
  kuesioner_upload_id: Number, // FK to MySQL
  periode: String,
  prodi: String,
  matakuliah: {
    kode: String,
    nama: String
  },
  dosen: {
    pegawai_id: String,
    nama: String
  },
  statistik: {
    total_responden: Number,
    nilai_rata_rata: Number,
    tingkat_kepuasan: String,
    distribusi_nilai: Object
  },
  ai_analysis: {
    summary: String,
    strengths: [String],
    improvements: [String],
    recommendations: [String]
  },
  created_at: Date,
  updated_at: Date
}
```

### **Collection 3: kuesioner_mongo** ⚠️ (INACTIVE - Consider Removal)
Status: Model ada tapi tidak digunakan aktif

---

## ❌ TABEL YANG HARUS DIHAPUS

### **Tabel yang Sudah Di-Drop (Hapus Model)**
- **dosen** → Diganti **dosenn**
- **monitoring** → Diganti **snapshots**
- **kuisioner** → Diganti **kuesioner_uploads**
- **pertanyaan_kuisioner** → Tidak digunakan
- **jawaban_kuisioner** → Tidak digunakan
- **matkul_dosen** → Tidak digunakan
- **dosen_matakuliah** → Tidak digunakan
- **jabatan_akademik** → Tidak digunakan
- **perwaliaan** → Feature cancelled
- **ai_response_cache** → Migrasi ke MongoDB

### **Tabel yang Perlu Evaluasi**
- **rps** → Implementasi minimal, diganti snapshots
- **reminder** → Diganti **jadwal_reminder**
- **ajaran** → Overlap dengan **periode_akademik**
- **matakuliah** → Data dari API, cache minimal
- **evaluasi_artefak** → Model ada tapi tidak aktif

---

## 🔗 RELASI UTAMA UNTUK ERD

### **Foreign Key Relationships**
```
users.prodi_id ---------> prodi.id
jadwal_dosen.pegawai_id -> dosenn.pegawai_id
kuesioner_uploads.user_id -> users.id
rps_monitoring_snapshots.prodi_id -> prodi.id
rps_monitoring_snapshots.periode_id -> periode_akademik.id
perkuliahan_monitoring_snapshots.prodi_id -> prodi.id
perkuliahan_monitoring_snapshots.periode_id -> periode_akademik.id
laporan_gjm.user_id -> users.id
laporan_gjm.template_id -> template_laporan.id
laporan_bulanan.user_id -> users.id
laporan_bulanan.prodi_id -> prodi.id
laporan_bulanan.template_id -> template_laporan.id
laporan_gkm.user_id -> users.id
laporan_gkm.prodi_id -> prodi.id
template_laporan.prodi_id -> prodi.id
document_chunks.template_id -> template_laporan.id
jadwal_reminder.user_id -> users.id
jadwal_reminder.prodi_id -> prodi.id
log_email.reminder_id -> jadwal_reminder.id
log_email.prodi_id -> prodi.id
kirim_laporan_history.sent_by -> users.id
```

---

## 🎨 PANDUAN DRAW.IO DESIGN

### **Layout Structure**
```
┌─────────────────────────────────────────────────────────────┐
│                    CORE SYSTEM (Biru)                      │
│  [users] ←→ [prodi] ←→ [periode_akademik]                   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                EXTERNAL API (Merah)                        │
│           [dosenn] ←→ [jadwal_dosen]                        │
└─────────────────────────────────────────────────────────────┘
                              │
                              ↓
┌─────────────────────────────────────────────────────────────┐
│              MONITORING GKM (Hijau)                        │
│  [kuesioner_uploads] [rps_monitoring] [perkuliahan_monitoring]│
└─────────────────────────────────────────────────────────────┘
                              │
                              ↓
┌─────────────────────────────────────────────────────────────┐
│              REPORTING GJM/GKM (Kuning)                    │
│  [laporan_gjm] [laporan_bulanan] [laporan_gkm] [template]   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ↓
┌─────────────────────────────────────────────────────────────┐
│               AI & VECTOR DB (Ungu)                        │
│        [document_chunks] [embeddings_cache]                │
└─────────────────────────────────────────────────────────────┘
                              │
                              ↓
┌─────────────────────────────────────────────────────────────┐
│              NOTIFICATIONS (Orange)                        │
│    [jadwal_reminder] [log_email] [kirim_laporan_history]    │
└─────────────────────────────────────────────────────────────┘
```

### **Color Scheme**
- **#E3F2FD** - Core System (Light Blue)
- **#FFEBEE** - External API (Light Red)
- **#E8F5E8** - GKM Monitoring (Light Green)
- **#FFF8E1** - Reporting System (Light Yellow)
- **#F3E5F5** - AI & Vector DB (Light Purple)
- **#FFF3E0** - Notifications (Light Orange)
- **#F5F5F5** - Support Tables (Light Gray)

### **Table Design Guidelines**
- **Width:** 200px standard
- **Height:** Auto (based on fields)
- **Font:** Arial 10px
- **Primary Key:** Bold + 🔑 icon
- **Foreign Key:** Italic + 🔗 icon
- **Required Fields:** Normal weight
- **Nullable Fields:** Light gray

### **Relationship Lines**
- **One-to-Many:** Line dengan crow's foot
- **Many-to-One:** Line dengan single arrow
- **Mandatory:** Solid line
- **Optional:** Dashed line

---

## ✅ MIGRATION CHECKLIST

### **Phase 1: Cleanup (Week 1)**
- [ ] Drop deprecated models & references
- [ ] Update import statements
- [ ] Fix controller dependencies
- [ ] Test basic functionality

### **Phase 2: Consolidation (Week 2)**
- [ ] Migrate `ajaran` to `periode_akademik`
- [ ] Consolidate `reminder` to `jadwal_reminder`
- [ ] Update all laporan references
- [ ] Test migration scripts

### **Phase 3: ERD Creation (Week 3)**
- [ ] Design ERD in draw.io
- [ ] Validate with stakeholders
- [ ] Document relationships
- [ ] Export final versions (PNG, PDF, XML)

### **Phase 4: Documentation (Week 4)**
- [ ] Update API documentation
- [ ] Create database manual
- [ ] Write migration guide
- [ ] Training materials

---

**Prepared by:** Database Analysis Team  
**Version:** 2.0 (Clean Design)  
**Status:** Ready for Implementation