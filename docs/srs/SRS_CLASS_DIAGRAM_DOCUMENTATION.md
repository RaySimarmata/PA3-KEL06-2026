# 📘 DOKUMENTASI CLASS DIAGRAM - SOFTWARE REQUIREMENTS SPECIFICATION (SRS)

> **Sistem Monitoring Akademik dengan AI/RAG**  
> **Dokumen**: Class Diagram untuk SRS  
> **Tanggal**: 15 Juni 2026  
> **Versi**: 1.0

---

## 📋 DAFTAR ISI

1. [Gambaran Umum](#gambaran-umum)
2. [Diagram Class Tersedia](#diagram-class-tersedia)
3. [Package Overview](#package-overview)
4. [Entitas Domain](#entitas-domain)
5. [Relationships](#relationships)
6. [Fitur Khusus AI/RAG](#fitur-khusus-airag)
7. [Use Case Mapping](#use-case-mapping)
8. [Cara Menggunakan](#cara-menggunakan)

---

## 1. GAMBARAN UMUM

### Tujuan Dokumen

Dokumen ini menyediakan **Class Diagram** yang sesuai untuk kebutuhan **Software Requirements Specification (SRS)**, mencakup:

✅ Domain entities dan business logic  
✅ Relationships antar entities  
✅ Key methods dan attributes  
✅ Package organization  
✅ AI/RAG system components  
✅ Mapping ke functional requirements

### Karakteristik SRS Class Diagram

**Berbeda dari Implementation Diagram:**
- ❌ Tidak menampilkan detail teknis Laravel (fillable, casts, etc.)
- ❌ Tidak menampilkan helper methods
- ✅ Fokus pada domain model dan business rules
- ✅ Menggunakan terminologi bisnis
- ✅ Mudah dipahami stakeholder non-teknis

---

## 2. DIAGRAM CLASS TERSEDIA

### 2.1 CLASS_DIAGRAM_SRS.puml (Complete Version)

**File**: `docs/srs/CLASS_DIAGRAM_SRS.puml`

**Karakteristik:**
- ✅ Semua 40 entitas domain
- ✅ 7 packages terorganisir
- ✅ Termasuk AI/RAG system
- ✅ Detail attributes & methods
- ✅ Complete relationships
- ✅ Annotations & notes

**Target Audience:**
- Product Manager
- System Analyst
- Software Architect
- Technical Stakeholder

**File Size**: ~400 lines

---

### 2.2 CLASS_DIAGRAM_SRS_SIMPLE.puml (Simplified Version)

**File**: `docs/srs/CLASS_DIAGRAM_SRS_SIMPLE.puml`

**Karakteristik:**
- ✅ Core 21 entitas saja
- ✅ 6 packages terorganisir
- ✅ Simplified attributes
- ✅ Key relationships only
- ✅ Fokus business logic

**Target Audience:**
- Business Stakeholder
- Client/Customer
- Non-technical audience
- Executive presentation

**File Size**: ~200 lines


---

## 3. PACKAGE OVERVIEW

### Package 1: Manajemen Pengguna (User Management)

**Entitas:**
- User (Pengguna sistem)
- Dosen (Dosen/Lecturer)
- Prodi (Program Studi)

**Fungsi Utama:**
- Authentication & authorization
- Role management (GKM, GJM, Dosen)
- Profile management
- Prodi assignment

**Business Rules:**
- Setiap user memiliki 1 role
- Dosen harus terkait dengan User
- User bisa belong to 1 Prodi

---

### Package 2: Manajemen Akademik (Academic Management)

**Entitas:**
- Matakuliah (Course)
- Ajaran (Academic Period)
- RPS (Rencana Pembelajaran Semester)
- Materi (Learning Materials)

**Fungsi Utama:**
- Course management
- RPS upload & review
- Material upload tracking
- Academic period management

**Business Rules:**
- RPS harus diupload per semester
- Materi harus sesuai jadwal mingguan
- Review RPS wajib dilakukan GKM


---

### Package 3: Monitoring & Evaluasi

**Entitas:**
- Monitoring (Overall monitoring)
- EvaluasiArtefak (Artifact evaluation)
- PerkuliahanMonitoringSnapshot (Lecture monitoring snapshot)

**Fungsi Utama:**
- Tracking compliance dosen
- RPS & material evaluation
- Automated monitoring snapshots
- Compliance calculation

**Business Rules:**
- Monitoring dilakukan per periode akademik
- Evaluasi wajib untuk setiap RPS
- Snapshot diupdate otomatis setiap hari

---

### Package 4: Sistem Kuesioner (Questionnaire System)

**Entitas:**
- Kuisioner (Questionnaire template)
- KuesioneUpload (Uploaded questionnaire data)
- PertanyaanKuisioner (Questions)
- JawabanKuisioner (Answers)

**Fungsi Utama:**
- Survey kepuasan mahasiswa
- Data collection & processing
- Statistical analysis
- Index kepuasan calculation

**Business Rules:**
- Kuesioner harus aktif di periode tertentu
- Upload file Excel/CSV untuk bulk data
- AI analysis untuk sentiment & insights


---

### Package 5: Sistem AI & RAG 🔥 (UNIQUE FEATURE)

**Entitas:**
- DocumentChunk (Vector database chunks)
- AIResponseCache (AI response caching)
- AIEvaluationResult (AI quality metrics)

**Fungsi Utama:**
- **RAG (Retrieval-Augmented Generation)**: Context retrieval untuk AI
- **Embedding Storage**: Vector database untuk similarity search
- **Response Caching**: Optimize performance & reduce cost
- **Quality Evaluation**: RAGAS metrics untuk evaluasi AI output

**Business Rules:**
- Template & kuesioner di-chunk dan di-index
- AI response di-cache untuk reuse
- Evaluation dilakukan dengan RAGAS framework

**Technical Highlight:**
```
User Upload → Document Chunking → Embedding Generation → 
Vector Storage → Similarity Search → Context Retrieval → 
AI Generation → Quality Evaluation → Cache Storage
```

---

### Package 6: Sistem Pelaporan (Reporting System)

**Entitas:**
- TemplateLaporan (Report templates)
- LaporanGKM (GKM Reports)
- LaporanGJM (GJM Reports)

**Fungsi Utama:**
- Auto-generate reports dengan AI
- Multi-format export (Word, PDF, PPT)
- Template-based generation
- RAGAS quality evaluation

**Business Rules:**
- Laporan GKM per prodi per periode
- Laporan GJM agregat semua prodi
- AI generate draft, user review & finalize


---

### Package 7: Sistem Pengingat & Notifikasi

**Entitas:**
- JadwalReminder (Reminder schedules)
- Reminder (Individual reminders)
- LogEmail (Email delivery logs)

**Fungsi Utama:**
- Automated reminder scheduling
- Email notification delivery
- Delivery tracking & logging
- Retry mechanism for failed emails

**Business Rules:**
- Reminder dikirim sesuai jadwal
- Log semua email untuk audit trail
- Automatic retry untuk failed delivery

---

## 4. ENTITAS DOMAIN

### 4.1 Core Entities

| Entity | Description | Key Attributes | Key Methods |
|--------|-------------|----------------|-------------|
| **User** | System user | name, email, role, prodi_id | authenticate(), hasRole() |
| **Dosen** | Lecturer | nama_lengkap, nidn, gelar | getRPS(), getMateri() |
| **Prodi** | Study Program | nama_prodi, kode_prodi | getAllDosen(), getLaporan() |
| **Matakuliah** | Course | kode_mk, nama_mk, sks | getDosenPengampu() |
| **Ajaran** | Academic Period | tahun_ajaran, semester | isActive() |

### 4.2 Academic Entities

| Entity | Description | Key Attributes | Key Methods |
|--------|-------------|----------------|-------------|
| **RPS** | Semester Learning Plan | file_rps, status_upload, status_review | upload(), review() |
| **Materi** | Learning Materials | judul, file_materi, status_upload | upload(), getWeekNumber() |
| **Monitoring** | Compliance Monitoring | status_rps, status_materi, persentase_kepatuhan | calculateCompliance() |
| **EvaluasiArtefak** | Artifact Evaluation | skor_evaluasi, status_evaluasi | approve(), requestRevision() |


### 4.3 AI/RAG Entities 🔥

| Entity | Description | Key Attributes | Key Methods |
|--------|-------------|----------------|-------------|
| **DocumentChunk** | Vector DB chunks | chunk_text, embedding, metadata | generateEmbedding(), calculateSimilarity() |
| **AIResponseCache** | AI cache storage | prompt_hash, ai_response, usage_count | findSimilar(), incrementUsage() |
| **AIEvaluationResult** | AI quality metrics | avg_ragas_faithfulness, hallucination_count | calculateOverallScore() |

### 4.4 Reporting Entities

| Entity | Description | Key Attributes | Key Methods |
|--------|-------------|----------------|-------------|
| **TemplateLaporan** | Report templates | nama_template, file_path, is_indexed | indexDocument(), getChunks() |
| **LaporanGKM** | GKM Reports | konten_laporan, total_rps, ai_sections | generateWithAI(), exportToWord() |
| **LaporanGJM** | GJM Reports | ringkasan_mutu, ragas_overall_score, ppt_path | generateWithRAG(), generatePPT() |

---

## 5. RELATIONSHIPS

### 5.1 User Management Relationships

```
User "1" ---- "0..1" Dosen : has
User "*" ---- "1" Prodi : belongs to
Prodi "1" ---- "*" Dosen : has
```

**Business Rule**: 
- Satu user bisa jadi dosen (optional)
- Setiap user belongs to 1 prodi
- Satu prodi punya banyak dosen

### 5.2 Academic Relationships

```
Matakuliah "*" ---- "*" Dosen : taught by
Matakuliah "1" ---- "*" RPS : has
RPS "1" ---- "*" Materi : contains
RPS "*" ---- "1" Ajaran : in period
```

**Business Rule**:
- Matakuliah bisa diajar banyak dosen (many-to-many)
- Setiap RPS untuk 1 matakuliah di 1 periode
- RPS berisi banyak materi (weekly)


### 5.3 AI/RAG Relationships

```
TemplateLaporan "1" ---- "*" DocumentChunk : indexed as
KuesioneUpload "1" ---- "*" DocumentChunk : processed into
DocumentChunk "*" .... "*" LaporanGJM : provides context
AIResponseCache "*" .... "*" LaporanGKM : caches for
```

**Business Rule**:
- Template di-chunk untuk indexing
- Data kuesioner di-process jadi chunks
- Chunks digunakan untuk RAG context retrieval
- AI responses di-cache untuk reuse

**Technical Flow**:
1. Upload template → Chunking → Embedding generation
2. Query laporan → Similarity search → Context retrieval
3. Generate dengan AI → Cache response
4. Reuse cache untuk similar queries

---

## 6. FITUR KHUSUS AI/RAG

### 6.1 RAG (Retrieval-Augmented Generation)

**Konsep**:
```
User Query → Embedding → Similarity Search → 
Retrieve Relevant Chunks → Provide Context to AI → 
Generate Response → Evaluate Quality → Cache
```

**Komponen:**
1. **DocumentChunk**: Storage untuk text chunks dengan embeddings
2. **Vector Search**: Cosine similarity untuk retrieval
3. **Context Window**: Top-K chunks sebagai context
4. **AI Generation**: LLM menggunakan retrieved context
5. **Caching**: Store response untuk reuse

### 6.2 RAGAS Evaluation Framework

**Metrics yang Diukur:**
- **Faithfulness**: Seberapa faithful AI response terhadap context
- **Answer Relevancy**: Relevansi jawaban terhadap query
- **Context Precision**: Precision dari retrieved context
- **Context Recall**: Recall dari retrieved context
- **Context Relevancy**: Relevansi overall context

**Threshold Quality:**
- Excellent: > 0.8
- Good: 0.6 - 0.8
- Fair: 0.4 - 0.6
- Poor: < 0.4


### 6.3 OCR Integration

**Entity**: LaporanGJM.ocr_data

**Fungsi**:
- Extract text dari gambar/scan dokumen
- Support untuk input non-text
- Integration dengan Python OCR service
- Store OCR result sebagai JSON

**Use Case**:
- Upload scan dokumen fisik
- Extract data dari foto/screenshot
- Process legacy documents

### 6.4 Auto PPT Generation

**Entity**: LaporanGJM.ppt_path

**Fungsi**:
- Generate presentation otomatis
- Template-based slide creation
- Export ke PowerPoint format
- Include charts & visualizations

**Use Case**:
- Presentasi laporan GJM ke pimpinan
- Auto-generate dari content laporan
- Save time preparing presentation

---

## 7. USE CASE MAPPING

### UC-01: Login & Authentication
**Related Entities**: User  
**Methods**: authenticate(), hasRole()

### UC-02: Upload RPS
**Related Entities**: RPS, Dosen, Matakuliah, Ajaran  
**Methods**: RPS.upload(), validate()

### UC-03: Monitoring Kepatuhan
**Related Entities**: Monitoring, Dosen, Matakuliah  
**Methods**: Monitoring.calculateCompliance(), generateReport()

### UC-04: Upload & Process Kuesioner
**Related Entities**: KuesioneUpload, DocumentChunk  
**Methods**: processFile(), extractData(), generateEmbedding()

### UC-05: Generate Laporan GKM dengan AI
**Related Entities**: LaporanGKM, TemplateLaporan, DocumentChunk, AIResponseCache  
**Methods**: generateWithAI(), indexDocument(), findSimilar()

### UC-06: Generate Laporan GJM dengan RAG
**Related Entities**: LaporanGJM, DocumentChunk, AIEvaluationResult  
**Methods**: generateWithRAG(), evaluateWithRAGAS(), generatePPT()


### UC-07: Send Reminder
**Related Entities**: JadwalReminder, Reminder, LogEmail  
**Methods**: schedule(), execute(), send(), logSuccess()

### UC-08: Evaluate RPS
**Related Entities**: EvaluasiArtefak, RPS, Dosen  
**Methods**: approve(), requestRevision(), reject()

---

## 8. CARA MENGGUNAKAN

### 8.1 View Diagram Online

**PlantUML Online Server:**
```
1. Buka: http://www.plantuml.com/plantuml/
2. Copy-paste content dari file .puml
3. Click "Submit" untuk render
```

**Mermaid Live Editor:**
```
1. Buka: https://mermaid.live/
2. Convert PlantUML ke Mermaid (jika perlu)
3. View & export
```

### 8.2 View dengan VSCode

**Install Extension:**
```
1. Install "PlantUML" extension
2. Install Java (required by PlantUML)
3. Open .puml file
4. Press Alt+D untuk preview
```

### 8.3 Export ke Gambar

**Online:**
```
1. Render di PlantUML server
2. Click "PNG" atau "SVG" button
3. Download image
```

**VSCode:**
```
1. Preview diagram (Alt+D)
2. Right-click → "Export Diagram"
3. Pilih format (PNG, SVG, PDF)
```

### 8.4 Include di Dokumen SRS

**Recommended:**
- Export sebagai PNG (high resolution)
- Include di section "3. System Design"
- Add caption & description
- Reference entities dalam requirements

**Template Caption:**
```
Gambar X: Class Diagram Sistem Monitoring Akademik

Diagram ini menunjukkan 7 package utama sistem:
1. Manajemen Pengguna
2. Manajemen Akademik  
3. Monitoring & Evaluasi
4. Sistem Kuesioner
5. Sistem AI & RAG (fitur unggulan)
6. Sistem Pelaporan
7. Sistem Pengingat

Total 40 entitas domain dengan relationships lengkap.
```

---

## 📚 REFERENSI

### File Locations
```
docs/srs/CLASS_DIAGRAM_SRS.puml              ← Complete version
docs/srs/CLASS_DIAGRAM_SRS_SIMPLE.puml       ← Simplified version
docs/srs/SRS_CLASS_DIAGRAM_DOCUMENTATION.md  ← This file
```

### Related Documents
```
docs/CLASS_DIAGRAM_DOCUMENTATION.md          ← Technical implementation
docs/DIAGRAM_COMPARISON_REPORT.md            ← Validation report
docs/diagrams/models-fixed.puml              ← Generated from code
```

### Tools
- PlantUML: https://plantuml.com/
- Mermaid: https://mermaid.js.org/
- Visual Paradigm: https://www.visual-paradigm.com/

---

**Document Version**: 1.0  
**Last Updated**: 15 Juni 2026  
**Author**: System Analyst Team  
**Status**: ✅ Ready for SRS
