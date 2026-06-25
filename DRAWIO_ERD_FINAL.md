# DRAW.IO ERD - PROJECT PA3 GKM/GJM
## Database Schema Final untuk Desain di Draw.io

**Status:** Production Ready - Clean Database  
**Total Tables:** 19 Active + 2 MongoDB Collections  
**Date:** 13 Juni 2026

---

## 🎯 STRUKTUR DATABASE FINAL

### **19 TABEL MYSQL AKTIF**

#### **CORE SYSTEM (3 Tables) - Color: #E3F2FD**
1. **users** - Authentication & Role Management
2. **prodi** - Program Studi Data
3. **periode_akademik** - Academic Period Management

#### **EXTERNAL API (2 Tables) - Color: #FFEBEE**  
4. **dosenn** - Lecturer Data from External API
5. **jadwal_dosen** - Teaching Schedule from API

#### **MONITORING GKM (3 Tables) - Color: #E8F5E8**
6. **kuesioner_uploads** - Questionnaire Processing & AI Analysis
7. **rps_monitoring_snapshots** - RPS Compliance Monitoring
8. **perkuliahan_monitoring_snapshots** - Course Activity Monitoring

#### **REPORTING GJM/GKM (3 Tables) - Color: #FFF8E1**
9. **laporan_gjm** - GJM Reports (Triwulan, Semester, VMTS, PPT)
10. **laporan_bulanan** - Monthly GKM Reports
11. **template_laporan** - Report Templates & RAG Context

#### **AI & VECTOR DB (2 Tables) - Color: #F3E5F5**
12. **document_chunks** - RAG Document Chunking
13. **embeddings_cache** - Vector Embeddings Cache

#### **NOTIFICATION SYSTEM (3 Tables) - Color: #FFF3E0**
14. **jadwal_reminder** - Scheduled Reminder System
15. **log_email** - Email Delivery Tracking
16. **kirim_laporan_history** - Report Distribution History

#### **SUPPORT TABLES (3 Tables) - Color: #F5F5F5**
17. **cache** - Laravel Framework Cache
18. **jobs** - Laravel Queue Jobs
19. **laporan_gkm** - GKM Artefak Reports (Minimal Usage)

---

## 📐 DRAW.IO TABLE SPECIFICATIONS

### **Core System Tables**

**Table: users**
```
Position: (50, 50)
Size: 280x240
Color: #E3F2FD
```
**Fields:**
- 🔑 id (BIGINT, PRIMARY KEY)
- name (VARCHAR 255, NOT NULL)
- email (VARCHAR 255, UNIQUE, NOT NULL) 
- password (VARCHAR 255, NOT NULL)
- role (ENUM: GKM/GJM/Admin, NOT NULL)
- 🔗 prodi_id (BIGINT, FOREIGN KEY → prodi.id)
- is_active (BOOLEAN, DEFAULT 1)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

**Table: prodi**
```
Position: (400, 50)  
Size: 250x180
Color: #E3F2FD
```
**Fields:**
- 🔑 id (BIGINT, PRIMARY KEY)
- kode_prodi (VARCHAR 10, UNIQUE, NOT NULL)
- nama_prodi (VARCHAR 255, NOT NULL)
- kaprodi_id (BIGINT, NULL)
- fakultas (VARCHAR 100, NULL)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

**Table: periode_akademik**
```
Position: (720, 50)
Size: 280x200  
Color: #E3F2FD
```
**Fields:**
- 🔑 id (BIGINT, PRIMARY KEY)
- tahun_ajaran (VARCHAR 20, NOT NULL)
- semester (ENUM: 1/2, NOT NULL)
- semester_label (VARCHAR 50, NULL)
- is_active (BOOLEAN, DEFAULT 0)
- start_date (DATE, NULL)
- end_date (DATE, NULL)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

---

### **External API Tables**

**Table: dosenn**
```
Position: (50, 320)
Size: 280x200
Color: #FFEBEE
```
**Fields:**
- 🔑 id (BIGINT, PRIMARY KEY)
- pegawai_id (VARCHAR 50, UNIQUE, NOT NULL)
- nama (VARCHAR 255, NOT NULL)
- nidn (VARCHAR 20, UNIQUE, NULL)
- email (VARCHAR 255, UNIQUE, NULL)
- nomor_telepon (VARCHAR 20, NULL)
- jabatan_akademik (VARCHAR 50, NULL)
- sync_status (VARCHAR 50, DEFAULT 'synced')
- last_sync_at (TIMESTAMP, NULL)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

**Table: jadwal_dosen**
```
Position: (400, 320)
Size: 280x240
Color: #FFEBEE
```
**Fields:**
- 🔑 id (BIGINT, PRIMARY KEY)
- 🔗 pegawai_id (VARCHAR 50, FK → dosenn.pegawai_id)
- kode_mk (VARCHAR 20, NOT NULL)
- nama_mk (VARCHAR 255, NOT NULL)
- kelas (VARCHAR 10, NOT NULL)
- semester (VARCHAR 5, NOT NULL)
- tahun_ajaran (VARCHAR 20, NOT NULL)
- tingkat (INT, NULL)
- sks (INT, NULL)
- is_manual (BOOLEAN, DEFAULT 0)
- sync_status (VARCHAR 50, DEFAULT 'synced')
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

---

### **Monitoring GKM Tables**

**Table: kuesioner_uploads**
```
Position: (50, 620)
Size: 320x300
Color: #E8F5E8
```
**Fields:**
- 🔑 id (BIGINT, PRIMARY KEY)
- 🔗 user_id (BIGINT, FK → users.id)
- file_path (VARCHAR 500, NOT NULL)
- original_filename (VARCHAR 255, NOT NULL)
- pegawai_id (VARCHAR 50, NOT NULL)
- kode_matakuliah (VARCHAR 20, NOT NULL)
- nama_matakuliah (VARCHAR 255, NULL)
- tingkat (VARCHAR 10, NULL)
- jenis_kuesioner (VARCHAR 50, NULL)
- periode (VARCHAR 20, NULL)
- semester (VARCHAR 5, NULL)
- **AI Fields:**
- extracted_data (JSON, NULL)
- ai_analysis (TEXT, NULL)
- processing_status (VARCHAR 50, DEFAULT 'uploaded')
- statistik_kepuasan (DECIMAL 5,2, NULL)
- statistik_rata_rata (DECIMAL 5,2, NULL)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

**Table: rps_monitoring_snapshots**
```
Position: (430, 620)
Size: 320x260
Color: #E8F5E8
```
**Fields:**
- 🔑 id (BIGINT, PRIMARY KEY)
- 🔗 prodi_id (BIGINT, FK → prodi.id)
- 🔗 periode_id (BIGINT, FK → periode_akademik.id)
- pegawai_id (VARCHAR 50, NOT NULL)
- kode_mk (VARCHAR 20, NOT NULL)
- nama_mk (VARCHAR 255, NOT NULL)
- kelas (VARCHAR 10, NULL)
- tingkat (INT, NULL)
- status_rps (VARCHAR 50, NOT NULL)
- tanggal_upload (DATE, NULL)
- compliance_score (DECIMAL 5,2, NULL)
- last_sync (TIMESTAMP, NULL)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

**Table: perkuliahan_monitoring_snapshots**
```
Position: (810, 620)
Size: 320x240
Color: #E8F5E8
```
**Fields:**
- 🔑 id (BIGINT, PRIMARY KEY)
- 🔗 prodi_id (BIGINT, FK → prodi.id)
- 🔗 periode_id (BIGINT, FK → periode_akademik.id)
- pegawai_id (VARCHAR 50, NOT NULL)
- kode_mk (VARCHAR 20, NOT NULL)
- nama_mk (VARCHAR 255, NOT NULL)
- kelas (VARCHAR 10, NULL)
- status_upload_materi (VARCHAR 50, NULL)
- status_review_soal (VARCHAR 50, NULL)
- last_activity (DATE, NULL)
- compliance_percentage (DECIMAL 5,2, NULL)
- last_sync (TIMESTAMP, NULL)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

---

### **Reporting System Tables**

**Table: laporan_gjm**
```
Position: (50, 960)
Size: 320x320
Color: #FFF8E1
```
**Fields:**
- 🔑 id (BIGINT, PRIMARY KEY)
- 🔗 user_id (BIGINT, FK → users.id)
- ajaran_id (BIGINT, NULL) [TO MIGRATE → periode_id]
- 🔗 template_id (BIGINT, FK → template_laporan.id)
- jenis_laporan (ENUM: triwulan/semester/vmts/ppt)
- judul_laporan (VARCHAR 255, NOT NULL)
- dokumen_hasil_path (VARCHAR 500, NULL)
- status_laporan (VARCHAR 50, DEFAULT 'draft')
- **AI Fields:**
- ai_konten_preview (TEXT, NULL)
- ai_ocr_data (JSON, NULL)
- ai_file_ids (JSON, NULL)
- ragas_metrics (JSON, NULL)
- processing_status (VARCHAR 50, DEFAULT 'pending')
- conversation_history (JSON, NULL)
- total_halaman (INT, NULL)
- processing_time (INT, NULL)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

**Table: laporan_bulanan**
```
Position: (430, 960)
Size: 300x260
Color: #FFF8E1
```
**Fields:**
- 🔑 id (BIGINT, PRIMARY KEY)
- 🔗 user_id (BIGINT, FK → users.id)
- 🔗 prodi_id (BIGINT, FK → prodi.id)
- 🔗 template_id (BIGINT, FK → template_laporan.id, NULL)
- periode_bulan (VARCHAR 10, NOT NULL)
- tipe_laporan (VARCHAR 50, NOT NULL)
- judul_laporan (VARCHAR 255, NOT NULL)
- file_path (VARCHAR 500, NULL)
- status (VARCHAR 50, DEFAULT 'pending')
- **AI Fields:**
- ai_preview_content (TEXT, NULL)
- processing_time (INT, NULL)
- error_message (TEXT, NULL)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

**Table: template_laporan**
```
Position: (790, 960)
Size: 300x220
Color: #FFF8E1
```
**Fields:**
- 🔑 id (BIGINT, PRIMARY KEY)
- nama_template (VARCHAR 255, NOT NULL)
- jenis_laporan (VARCHAR 100, NOT NULL)
- file_path (VARCHAR 500, NOT NULL)
- 🔗 prodi_id (BIGINT, FK → prodi.id, NULL)
- placeholder_config (JSON, NULL)
- is_active (BOOLEAN, DEFAULT 1)
- version (VARCHAR 10, DEFAULT '1.0')
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

---

### **AI & Vector Database Tables**

**Table: document_chunks**
```
Position: (50, 1300)
Size: 300x200
Color: #F3E5F5
```
**Fields:**
- 🔑 id (BIGINT, PRIMARY KEY)
- 🔗 template_id (BIGINT, FK → template_laporan.id)
- chunk_text (TEXT, NOT NULL)
- chunk_index (INT, NOT NULL)
- source_file (VARCHAR 255, NOT NULL)
- source_page (INT, NULL)
- chunk_size (INT, NULL)
- metadata (JSON, NULL)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

**Table: embeddings_cache**
```
Position: (420, 1300)
Size: 280x160
Color: #F3E5F5
```
**Fields:**
- 🔑 id (BIGINT, PRIMARY KEY)
- text_hash (VARCHAR 64, UNIQUE, NOT NULL)
- embedding (JSON, NOT NULL)
- model_name (VARCHAR 100, NOT NULL)
- text_length (INT, NULL)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

---

### **Notification System Tables**

**Table: jadwal_reminder**
```
Position: (50, 1540)
Size: 300x220
Color: #FFF3E0
```
**Fields:**
- 🔑 id (BIGINT, PRIMARY KEY)
- 🔗 user_id (BIGINT, FK → users.id)
- 🔗 prodi_id (BIGINT, FK → prodi.id)
- tipe_reminder (VARCHAR 100, NOT NULL)
- judul (VARCHAR 255, NOT NULL)
- pesan (TEXT, NOT NULL)
- jadwal_kirim (VARCHAR 100, NOT NULL)
- is_active (BOOLEAN, DEFAULT 1)
- last_sent_at (TIMESTAMP, NULL)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

**Table: log_email**
```
Position: (410, 1540)
Size: 300x220
Color: #FFF3E0
```
**Fields:**
- 🔑 id (BIGINT, PRIMARY KEY)
- 🔗 reminder_id (BIGINT, FK → jadwal_reminder.id, NULL)
- 🔗 prodi_id (BIGINT, FK → prodi.id)
- recipient_email (VARCHAR 255, NOT NULL)
- subject (VARCHAR 255, NOT NULL)
- message_body (TEXT, NULL)
- status (VARCHAR 50, NOT NULL)
- sent_at (TIMESTAMP, NULL)
- error_message (TEXT, NULL)
- created_at (TIMESTAMP)

**Table: kirim_laporan_history**
```
Position: (770, 1540)
Size: 320x200
Color: #FFF3E0
```
**Fields:**
- 🔑 id (BIGINT, PRIMARY KEY)
- laporan_type (VARCHAR 50, NOT NULL)
- laporan_id (BIGINT, NOT NULL)
- recipients (JSON, NOT NULL)
- 🔗 sent_by (BIGINT, FK → users.id)
- email_subject (VARCHAR 255, NOT NULL)
- sent_at (TIMESTAMP, NOT NULL)
- delivery_status (JSON, NULL)
- created_at (TIMESTAMP)

---

## 🔗 RELATIONSHIP CONNECTIONS

### **Primary Foreign Key Relationships**
```
users(id) -------< kuesioner_uploads(user_id) 
users(id) -------< laporan_gjm(user_id)
users(id) -------< laporan_bulanan(user_id) 
users(id) -------< jadwal_reminder(user_id)
users(id) -------< kirim_laporan_history(sent_by)
users(prodi_id) >- prodi(id)

prodi(id) -------< rps_monitoring_snapshots(prodi_id)
prodi(id) -------< perkuliahan_monitoring_snapshots(prodi_id)
prodi(id) -------< laporan_bulanan(prodi_id)
prodi(id) -------< template_laporan(prodi_id)
prodi(id) -------< jadwal_reminder(prodi_id)
prodi(id) -------< log_email(prodi_id)

periode_akademik(id) -------< rps_monitoring_snapshots(periode_id)
periode_akademik(id) -------< perkuliahan_monitoring_snapshots(periode_id)

dosenn(pegawai_id) -------< jadwal_dosen(pegawai_id)

template_laporan(id) -------< laporan_gjm(template_id)
template_laporan(id) -------< laporan_bulanan(template_id)
template_laporan(id) -------< document_chunks(template_id)

jadwal_reminder(id) -------< log_email(reminder_id)
```

### **Relationship Drawing Guidelines for Draw.io**
- **One-to-Many:** Solid line with crow's foot (∼<)
- **Mandatory:** Solid line
- **Optional:** Dashed line
- **Line Color:** #666666 (Dark Gray)
- **Line Width:** 1pt

---

## 🗄️ MONGODB COLLECTIONS (Separate Diagram Section)

### **Collection 1: ai_response_cache_mongo**
```
Position: (50, 1800)
Size: 350x280
Color: #E8EAF6
```
**Fields:**
- _id (ObjectId, Primary Key)
- cache_key (String, Unique Hash)
- prompt_hash (String)
- original_prompt (String)
- context_metadata (Object)
  - prodi (String)
  - periode (String) 
  - jenis_laporan (String)
- ai_response (String, Large Text)
- ai_provider (String: groq/openai/claude)
- ai_model (String: llama3/gpt-4/claude-3)
- usage_count (Number)
- last_used_at (Date)
- response_length (Number)
- similarity_threshold (Number)
- created_at (Date)
- updated_at (Date)
- expires_at (Date, TTL Index)

### **Collection 2: hasil_analisis_mongo**
```
Position: (450, 1800)
Size: 350x260
Color: #E8EAF6
```
**Fields:**
- _id (ObjectId, Primary Key)
- kuesioner_upload_id (Number, Reference to MySQL)
- periode (String)
- prodi (String)
- matakuliah (Object)
  - kode (String)
  - nama (String)
- dosen (Object)
  - pegawai_id (String)
  - nama (String)
- statistik (Object)
  - total_responden (Number)
  - nilai_rata_rata (Number)
  - tingkat_kepuasan (String)
  - distribusi_nilai (Object)
- ai_analysis (Object)
  - summary (String)
  - strengths (Array of Strings)
  - improvements (Array of Strings)
  - recommendations (Array of Strings)
- created_at (Date)
- updated_at (Date)

---

## 📋 DRAW.IO IMPORT STEPS

### **Step 1: Setup Canvas**
1. Open draw.io / app.diagrams.net
2. Create "Blank Diagram"
3. Canvas Size: A3 Landscape (2000x1500px)
4. Grid: 20px, Snap to Grid: ON

### **Step 2: Create Table Shapes**
1. Use "Entity Relation" stencil
2. Table Shape: Rectangle with rounded corners (10px)
3. Header: Table name (Bold, 14pt, White text)
4. Fields: Arial 10pt, Left aligned
### **Step 3: Table Styling**

**Header Styling:**
- Font: Arial Bold 12pt
- Text Color: White
- Background: Gradient based on group color
- Height: 30px
- Alignment: Center

**Field Styling:**
- 🔑 Primary Key: Bold, Gold icon
- 🔗 Foreign Key: Italic, Blue icon  
- Required: Normal weight
- Nullable: Light gray (#888888)
- Data Types: Parentheses, Gray (#666666)

### **Step 4: Color Scheme Application**

| **Group** | **Header Color** | **Background** | **Border** |
|-----------|------------------|----------------|------------|
| Core System | #1976D2 | #E3F2FD | #1565C0 |
| External API | #D32F2F | #FFEBEE | #C62828 |
| Monitoring GKM | #388E3C | #E8F5E8 | #2E7D32 |
| Reporting | #F57C00 | #FFF8E1 | #EF6C00 |
| AI & Vector | #7B1FA2 | #F3E5F5 | #6A1B9A |
| Notifications | #FF8F00 | #FFF3E0 | #FF6F00 |
| Support | #616161 | #F5F5F5 | #424242 |
| MongoDB | #3F51B5 | #E8EAF6 | #303F9F |

### **Step 5: Relationship Lines**
- **Style:** Straight lines with rounded corners
- **Color:** #424242 (Dark Gray)
- **Width:** 2pt
- **Arrows:** 
  - One-to-Many: Line to Crow's Foot
  - Foreign Key: Dashed line to solid line

### **Step 6: Layout Groups**
```
┌─ CORE SYSTEM (Top) ────────────────────┐
│  users ←→ prodi ←→ periode_akademik     │
└────────────────────────────────────────┘
           ↓
┌─ EXTERNAL API ────────────────────────┐
│  dosenn ←→ jadwal_dosen               │
└───────────────────────────────────────┘
           ↓
┌─ MONITORING (Wide Row) ───────────────┐
│ kuesioner ←→ rps_snap ←→ perkuliah_snap│
└───────────────────────────────────────┘
           ↓
┌─ REPORTING (Wide Row) ────────────────┐
│ laporan_gjm ←→ laporan_bulanan ←→ template│
└───────────────────────────────────────┘
           ↓
┌─ AI & VECTOR ─────────────────────────┐
│ document_chunks ←→ embeddings_cache    │
└───────────────────────────────────────┘
           ↓
┌─ NOTIFICATIONS ───────────────────────┐
│ jadwal_reminder ←→ log_email ←→ history│
└───────────────────────────────────────┘
           ↓
┌─ MONGODB (Bottom) ────────────────────┐
│ ai_response_cache ←→ hasil_analisis    │
└───────────────────────────────────────┘
```
---

## 🎯 SUMMARY FOR DRAW.IO DESIGN

### **Database Statistics After Cleanup:**
- ✅ **19 Active MySQL Tables** (Production Ready)
- ✅ **2 Active MongoDB Collections** 
- ❌ **15 Tables/Models Removed** (Deprecated)
- 🔄 **Clear Relationships** (No orphaned references)

### **Key Design Features:**
1. **Clean Architecture** - No deprecated tables
2. **Clear Modularity** - Grouped by functionality
3. **Consistent Naming** - snake_case convention
4. **Proper Indexing** - Foreign keys & performance indexes
5. **AI Integration** - JSON fields for ML/AI data
6. **Scalable Design** - Snapshot tables for caching

### **Data Flow Highlights:**
```
External API → jadwal_dosen → monitoring_snapshots
   ↓
OCR Upload → kuesioner_uploads → AI Analysis
   ↓  
Template System → document_chunks → RAG Context
   ↓
AI Generation → laporan_gjm/bulanan → Distribution
```

### **Technology Stack:**
- **MySQL 8.0+** - Relational data & JSON support
- **MongoDB 6.0+** - Document storage & analytics
- **Laravel 10** - Framework with Eloquent ORM
- **AI/ML Stack** - OpenAI, Groq, Claude integration
- **Queue System** - Redis/Database queues
- **Vector Search** - Embeddings for RAG

---

## ✅ FINAL CHECKLIST

### **Pre-Design Verification:**
- [x] All deprecated models identified
- [x] Active tables verified through code analysis
- [x] Relationships validated
- [x] MongoDB collections documented
- [x] Color scheme defined
- [x] Layout structure planned

### **Design Quality Checks:**
- [ ] All 19 tables included
- [ ] Foreign key relationships drawn
- [ ] Color coding applied consistently  
- [ ] Field types clearly marked
- [ ] Primary/Foreign keys highlighted
- [ ] Layout is readable and organized
- [ ] MongoDB section separated
- [ ] Legend/key provided

### **Export Formats:**
- [ ] PNG (High Resolution 300dpi)
- [ ] PDF (Vector format)
- [ ] XML (Source format for future edits)
- [ ] SVG (Web-compatible vector)

**Final Status:** ✅ **READY FOR DRAW.IO DESIGN**

---

**Generated for:** PA3 Project Database Design  
**Target Tool:** Draw.io / Diagrams.net  
**Complexity:** Enterprise-level ERD  
**Estimated Design Time:** 2-3 hours