# ENTITY RELATIONSHIP DIAGRAM (ERD)
## Sistem Monitoring dan Pelaporan Akademik PA3

---

## 🎯 OVERVIEW

Sistem ini terdiri dari 4 subsistem utama:
1. **Core System** - User, Prodi, Periode
2. **Monitoring System (GKM)** - RPS, Perkuliahan, Kuesioner
3. **Reporting System (GJM)** - Laporan Triwulan, Semester, VMTS
4. **AI & Support System** - RAG, Cache, Notifications

---

## 📊 FULL SYSTEM ERD

```mermaid
erDiagram
    %% CORE ENTITIES
    users ||--o{ jadwal_reminder : creates
    users }o--|| prodi : belongs_to
    users ||--o{ kirim_laporan_history : sends
    
    prodi ||--o{ template_laporan : has
    prodi ||--o{ rps_monitoring_snapshots : monitors
    prodi ||--o{ perkuliahan_monitoring_snapshots : monitors
    prodi ||--o{ jadwal_reminder : belongs_to
    prodi ||--o{ log_email : receives
    
    periode_akademik ||--o{ rps_monitoring_snapshots : tracks
    periode_akademik ||--o{ perkuliahan_monitoring_snapshots : tracks
    periode_akademik ||--o{ laporan_gjm : references
    periode_akademik ||--o{ laporan_gkm : references
    
    %% EXTERNAL API DATA
    dosenn ||--o{ jadwal_dosen : teaches
    dosenn ||--o{ kuesioner_uploads : submits
    dosenn ||--o{ rps_monitoring_snapshots : creates
    
    %% MONITORING ENTITIES (GKM)
    rps_monitoring_snapshots }o--|| prodi : belongs_to
    rps_monitoring_snapshots }o--|| periode_akademik : in_period
    
    perkuliahan_monitoring_snapshots }o--|| prodi : belongs_to
    perkuliahan_monitoring_snapshots }o--|| periode_akademik : in_period
    
    kuesioner_uploads }o--|| dosenn : submitted_by
    kuesioner_uploads ||--o{ laporan_bulanan : generates
    
    %% REPORTING ENTITIES (GJM)
    laporan_gjm }o--|| periode_akademik : in_period
    laporan_gjm }o--|| template_laporan : uses
    
    laporan_gkm }o--|| periode_akademik : in_period
    
    laporan_bulanan }o--|| kuesioner_uploads : based_on
    
    %% AI & VECTOR DB
    template_laporan ||--o{ document_chunks : indexed_as
    
    %% NOTIFICATION SYSTEM
    jadwal_reminder ||--o{ log_email : triggers
    jadwal_reminder }o--|| users : created_by
    jadwal_reminder }o--|| prodi : for_prodi
    
    kirim_laporan_history }o--|| users : sent_by

    %% ENTITY DEFINITIONS
    users {
        bigint id PK
        string name
        string email UK
        enum role "GKM|GJM"
        bigint prodi_id FK
        boolean is_active
    }
    
    prodi {
        bigint id PK
        string kode_prodi UK
        string nama_prodi
        bigint kaprodi_id
    }
    
    periode_akademik {
        bigint id PK
        year tahun_ajaran
        enum semester "ganjil|genap"
        boolean is_active
        date start_date
        date end_date
    }
    
    dosenn {
        bigint id PK
        bigint pegawai_id UK
        string nama
        string nidn UK
        string email UK
        string nomor_telepon
    }
    
    jadwal_dosen {
        bigint id PK
        bigint pegawai_id FK
        string kode_mk
        string nama_mk
        string kelas
        string semester
        boolean is_manual
    }
    
    rps_monitoring_snapshots {
        bigint id PK
        bigint prodi_id FK
        bigint periode_id FK
        bigint pegawai_id
        string kode_mk
        enum status_rps
        date tanggal_upload
    }
    
    perkuliahan_monitoring_snapshots {
        bigint id PK
        bigint prodi_id FK
        bigint periode_id FK
        bigint pegawai_id
        enum status_upload_materi
        enum status_review_soal
    }
    
    kuesioner_uploads {
        bigint id PK
        bigint pegawai_id FK
        string kode_mk
        string file_path
        json extracted_data
        text ai_analysis
        enum jenis_kuesioner
        int tingkat
    }
    
    laporan_gjm {
        bigint id PK
        enum jenis_laporan "triwulan|semester|vmts|ppt"
        bigint periode_id FK
        bigint template_id FK
        text ai_konten_preview
        string dokumen_hasil_path
        json ai_ocr_data
        json ragas_metrics
        enum processing_status
    }
    
    laporan_gkm {
        bigint id PK
        enum jenis_laporan "artefak"
        bigint periode_id FK
        string file_laporan
        text ai_preview_content
        json artefak_periode
    }
    
    laporan_bulanan {
        bigint id PK
        string periode_bulan
        enum tipe_laporan "kuesioner"
        string judul_laporan
        string file_path
        text ai_preview_content
        text error_message
    }
    
    template_laporan {
        bigint id PK
        string nama_template
        enum jenis_laporan
        string file_path
        bigint prodi_id FK
        boolean is_active
    }
    
    document_chunks {
        bigint id PK
        bigint template_id FK
        text chunk_text
        int chunk_index
        string source_file
        int source_page
    }
    
    jadwal_reminder {
        bigint id PK
        enum tipe_reminder "rps|materi|soal"
        bigint prodi_id FK
        string jadwal_kirim "cron"
        boolean is_active
        timestamp last_sent_at
    }
    
    log_email {
        bigint id PK
        bigint reminder_id FK
        bigint prodi_id FK
        string recipient_email
        string subject
        enum status "sent|failed"
        timestamp sent_at
    }
    
    kirim_laporan_history {
        bigint id PK
        enum laporan_type "gjm|gkm"
        bigint laporan_id
        json recipients
        bigint sent_by FK
        timestamp sent_at
    }
```

---

## 🔍 DETAILED SUBSYSTEM ERDs

### 1. CORE SYSTEM

```
┌─────────────────┐
│     USERS       │
│ - id (PK)       │
│ - email (UK)    │
│ - role (ENUM)   │──┐
│ - prodi_id (FK) │  │
│ - is_active     │  │
└─────────────────┘  │
         │           │
         │ 1         │ 1
         │           │
         │ N         │ N
         ↓           ↓
┌─────────────────┐ ┌──────────────────┐
│ JADWAL_REMINDER │ │      PRODI       │
│ - id (PK)       │ │ - id (PK)        │
│ - user_id (FK)  │←│ - kode_prodi(UK) │
│ - prodi_id (FK) │ │ - nama_prodi     │
└─────────────────┘ │ - kaprodi_id     │
                    └──────────────────┘
```

### 2. MONITORING SYSTEM (GKM)

```
┌──────────────────────┐
│  PERIODE_AKADEMIK    │
│  - id (PK)           │
│  - tahun_ajaran      │
│  - semester          │──┐
│  - is_active         │  │
└──────────────────────┘  │
           │              │
           │ 1            │ 1
           │              │
           │ N            │ N
           ↓              ↓
┌──────────────────────────┐  ┌───────────────────────────────┐
│ RPS_MONITORING_SNAPSHOTS │  │ PERKULIAHAN_MONITORING_...   │
│ - id (PK)                │  │ - id (PK)                     │
│ - prodi_id (FK)          │  │ - prodi_id (FK)               │
│ - periode_id (FK)        │  │ - periode_id (FK)             │
│ - pegawai_id             │  │ - pegawai_id                  │
│ - kode_mk                │  │ - status_upload_materi        │
│ - status_rps             │  │ - status_review_soal          │
│ - tanggal_upload         │  │                               │
└──────────────────────────┘  └───────────────────────────────┘
           ↑                              ↑
           │ N                            │ N
           │                              │
           │ 1                            │ 1
       ┌─────────┐
       │ DOSENN  │ (From External API)
       │- id (PK)│
       │- pegawai│
       │- nidn   │
       └─────────┘
           │
           │ 1
           │
           │ N
           ↓
    ┌──────────────────┐
    │ JADWAL_DOSEN     │
    │ - id (PK)        │
    │ - pegawai_id(FK) │
    │ - kode_mk        │
    │ - nama_mk        │
    └──────────────────┘
```

### 3. KUESIONER SYSTEM

```
┌─────────┐
│ DOSENN  │
│- pegawai│
└─────────┘
     │ 1
     │
     │ N
     ↓
┌─────────────────────┐
│ KUESIONER_UPLOADS   │
│ - id (PK)           │
│ - pegawai_id (FK)   │
│ - file_path         │
│ - extracted_data    │ ← OCR Result
│ - ai_analysis       │ ← AI Processing
│ - statistik_*       │ ← Computed Stats
└─────────────────────┘
     │ 1
     │
     │ N
     ↓
┌─────────────────────┐
│ LAPORAN_BULANAN     │
│ - id (PK)           │
│ - periode_bulan     │
│ - tipe_laporan      │
│ - ai_preview_content│ ← AI Generated
│ - file_path         │
└─────────────────────┘
```

### 4. REPORTING SYSTEM (GJM)

```
┌──────────────────┐
│ PERIODE_AKADEMIK │
│ - id (PK)        │
└──────────────────┘
         │ 1
         │
         │ N
         ↓
┌─────────────────────┐       ┌──────────────────┐
│   LAPORAN_GJM       │   N   │ TEMPLATE_LAPORAN │
│ - id (PK)           │←──────│ - id (PK)        │
│ - jenis_laporan     │   1   │ - nama_template  │
│   * triwulan        │       │ - jenis_laporan  │
│   * semester        │       │ - file_path      │
│   * vmts            │       │ - prodi_id (FK)  │
│   * ppt             │       │ - is_active      │
│ - periode_id (FK)   │       └──────────────────┘
│ - template_id (FK)  │                │ 1
│ - ai_konten_preview │                │
│ - dokumen_hasil_path│                │ N
│ - ai_ocr_data (JSON)│                ↓
│ - ragas_metrics     │       ┌──────────────────┐
│ - processing_status │       │ DOCUMENT_CHUNKS  │
└─────────────────────┘       │ - id (PK)        │
                              │ - template_id(FK)│
                              │ - chunk_text     │ ← For RAG
                              │ - chunk_index    │
                              └──────────────────┘
```

### 5. NOTIFICATION SYSTEM

```
┌─────────────────┐       ┌─────────┐
│ JADWAL_REMINDER │   N   │  PRODI  │
│ - id (PK)       │───────│- id (PK)│
│ - prodi_id (FK) │   1   └─────────┘
│ - tipe_reminder │
│   * rps         │
│   * materi      │
│   * soal        │
│ - jadwal_kirim  │ (cron expression)
│ - is_active     │
│ - last_sent_at  │
└─────────────────┘
         │ 1
         │
         │ N
         ↓
┌─────────────────────┐
│    LOG_EMAIL        │
│ - id (PK)           │
│ - reminder_id (FK)  │
│ - prodi_id (FK)     │
│ - recipient_email   │
│ - subject           │
│ - status            │
│ - sent_at           │
└─────────────────────┘
```

---

## 🗄️ MONGODB COLLECTIONS

### ai_response_cache_mongo

```json
{
  "_id": ObjectId,
  "cache_key": "hash_string",
  "prompt_hash": "hash_string",
  "original_prompt": "text",
  "context_metadata": {
    "prodi": "IF",
    "periode": "2024/2025 Ganjil"
  },
  "ai_response": "long text",
  "ai_provider": "groq|openai|claude",
  "ai_model": "llama3|gpt-4|claude-3",
  "usage_count": 5,
  "last_used_at": ISODate,
  "response_length": 1234,
  "similarity_threshold": 0.85,
  "created_at": ISODate,
  "updated_at": ISODate
}
```

### hasil_analisis_mongo

```json
{
  "_id": ObjectId,
  "kuesioner_upload_id": 123,
  "periode": "2024/2025 Ganjil",
  "prodi": "Teknik Informatika",
  "statistik": {
    "total_responden": 45,
    "nilai_rata_rata": 4.2,
    "tingkat_kepuasan": "Sangat Baik"
  },
  "ai_summary": "Hasil analisis menunjukkan...",
  "strengths": ["...", "..."],
  "improvements": ["...", "..."],
  "created_at": ISODate,
  "updated_at": ISODate
}
```

---

## 🔗 RELATIONSHIP SUMMARY

### One-to-Many Relationships

| Parent | Child | Foreign Key | Cascade |
|--------|-------|-------------|---------|
| users | jadwal_reminder | user_id | CASCADE |
| prodi | users | prodi_id | RESTRICT |
| prodi | template_laporan | prodi_id | CASCADE |
| prodi | rps_monitoring_snapshots | prodi_id | CASCADE |
| prodi | perkuliahan_monitoring_snapshots | prodi_id | CASCADE |
| periode_akademik | laporan_gjm | periode_id | RESTRICT |
| periode_akademik | laporan_gkm | periode_id | RESTRICT |
| template_laporan | document_chunks | template_id | CASCADE |
| template_laporan | laporan_gjm | template_id | RESTRICT |
| dosenn | jadwal_dosen | pegawai_id | CASCADE |
| dosenn | kuesioner_uploads | pegawai_id | RESTRICT |
| kuesioner_uploads | laporan_bulanan | kuesioner_id | RESTRICT |
| jadwal_reminder | log_email | reminder_id | CASCADE |

### Many-to-Many Relationships

Saat ini tidak ada relasi many-to-many yang aktif digunakan.

**Deprecated:**
- dosen_matakuliah (dropped)
- matkul_dosen (dropped)

---

## 📈 DATA FLOW DIAGRAM

### Flow 1: Monitoring RPS
```
API Eksternal (Feeder)
         │
         ↓
  Sync Job (SyncJadwalDosenJob)
         │
         ↓
   jadwal_dosen (MySQL)
         │
         ↓
  MonitoringRPSController
         │
         ↓
rps_monitoring_snapshots (MySQL)
         │
         ↓
  Dashboard GKM (Display)
```

### Flow 2: Generate Laporan GJM (Triwulan)

```
User Upload Images/Files (OCR)
         │
         ↓
   OCRUploadController
         │
         ├─→ OCR Service (Extract Text)
         │
         ├─→ AI Service (Analyze & Generate)
         │        │
         │        ↓
         │   ai_response_cache_mongo
         │
         ↓
  template_laporan (MySQL)
         │
         ↓
  document_chunks (For RAG Context)
         │
         ↓
  VectorDatabaseService (Search Relevant)
         │
         ↓
  LaporanTriwulanService (Generate Document)
         │
         ↓
   laporan_gjm (MySQL - Save Record)
         │
         ↓
  GenerateLaporanSemesterJob (Queue)
         │
         ↓
  Word Document Generated
```

### Flow 3: Kuesioner Analysis

```
Upload Kuesioner (Scan/Image)
         │
         ↓
  MonitoringKuesioneController
         │
         ├─→ OCR Service (Extract Answers)
         │
         ├─→ AI Service (Analyze Sentiment)
         │
         ↓
  kuesioner_uploads (MySQL)
         │
         ├─→ extracted_data (JSON)
         └─→ ai_analysis (Text)
         │
         ↓
  hasil_analisis_mongo (MongoDB)
         │
         ├─→ statistik
         └─→ ai_summary
         │
         ↓
  LaporanKuesioneService
         │
         ↓
  laporan_bulanan (MySQL)
         │
         ↓
  Word/PDF Document
```

---

## 🎨 COLOR CODING (for Visual ERD Tools)

Jika Anda menggunakan tools seperti dbdiagram.io atau draw.io:

- **🔵 Blue** - Core Tables (users, prodi, periode_akademik)
- **🟢 Green** - Monitoring Tables (snapshots, jadwal_dosen)
- **🟡 Yellow** - Reporting Tables (laporan_gjm, laporan_gkm, laporan_bulanan)
- **🟣 Purple** - AI/ML Tables (document_chunks, embeddings_cache)
- **🟠 Orange** - Notification Tables (jadwal_reminder, log_email)
- **⚫ Gray** - Support Tables (cache, jobs)
- **🔴 Red** - External API Models (dosenn)

---

## 📝 NOTES

1. **Foreign Key Constraints:**
   - Kebanyakan menggunakan RESTRICT untuk mencegah deletion cascade yang tidak diinginkan
   - CASCADE hanya untuk truly dependent data (log_email → reminder)

2. **Soft Deletes:**
   - Tidak ada soft deletes (deleted_at) di tabel aktif
   - Pertimbangkan menambahkan untuk audit trail

3. **Indexes:**
   - Semua FK sudah ter-index otomatis
   - Perlu tambahan index untuk: 
     - kuesioner_uploads.pegawai_id + periode
     - laporan_gjm.jenis_laporan + periode_id
     - rps_monitoring_snapshots.prodi_id + periode_id

4. **Data Archiving:**
   - Belum ada strategi archiving untuk data lama
   - Pertimbangkan partition by year untuk tabel monitoring

---

**End of ERD Documentation**
