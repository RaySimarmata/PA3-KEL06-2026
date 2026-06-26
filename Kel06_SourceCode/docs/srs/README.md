# 📘 CLASS DIAGRAM UNTUK SRS (Software Requirements Specification)

> **Quick Start Guide** untuk menggunakan class diagram dalam dokumen SRS

---

## 📁 FILE YANG TERSEDIA

### 1. CLASS_DIAGRAM_SRS.puml (Complete)
**Untuk**: Technical stakeholder, System Analyst, Architect  
**Isi**: 40 entitas, 7 packages, lengkap dengan AI/RAG system  
**Size**: ~400 lines  
**✅ Gunakan**: Untuk SRS technical section

### 2. CLASS_DIAGRAM_SRS_SIMPLE.puml (Simplified)
**Untuk**: Business stakeholder, Client, Executive  
**Isi**: 21 entitas core, 6 packages, fokus business logic  
**Size**: ~200 lines  
**✅ Gunakan**: Untuk presentasi & overview

### 3. SRS_CLASS_DIAGRAM_DOCUMENTATION.md
**Untuk**: Semua audience  
**Isi**: Complete documentation, package explanation, use case mapping  
**Size**: ~500 lines  
**✅ Gunakan**: Sebagai reference guide

---

## 🚀 QUICK START

### Step 1: Pilih Diagram

**Untuk SRS Document (Technical):**
```
Gunakan: CLASS_DIAGRAM_SRS.puml
Audience: Technical team, developers, architects
```

**Untuk Stakeholder Presentation:**
```
Gunakan: CLASS_DIAGRAM_SRS_SIMPLE.puml
Audience: Business, client, executives
```

### Step 2: View Diagram

**Option A: Online PlantUML Server**
```
1. Buka: http://www.plantuml.com/plantuml/
2. Copy-paste file .puml
3. Click "Submit"
4. Export as PNG/SVG
```

**Option B: VSCode Extension**
```
1. Install "PlantUML" extension
2. Open .puml file
3. Press Alt+D
4. Right-click → Export
```

### Step 3: Include di SRS

**Lokasi di SRS:**
- Section 3: System Design / Architecture
- Sub-section: Domain Model / Class Diagram

**Template Text:**
```markdown
### 3.X Class Diagram

Gambar [X] menunjukkan class diagram sistem yang terdiri dari 7 package utama:

1. **Manajemen Pengguna**: User, Dosen, Prodi
2. **Manajemen Akademik**: Matakuliah, RPS, Materi, Ajaran
3. **Monitoring & Evaluasi**: Monitoring, EvaluasiArtefak
4. **Sistem Kuesioner**: Kuisioner, Upload, Pertanyaan, Jawaban
5. **Sistem AI & RAG**: DocumentChunk, AICache, AIEvaluation (🔥 Fitur Unggulan)
6. **Sistem Pelaporan**: Template, LaporanGKM, LaporanGJM
7. **Sistem Pengingat**: JadwalReminder, Reminder, LogEmail

Total sistem memiliki 40 domain entities dengan relationships lengkap
yang mendukung functional requirements sistem.

**Fitur Khusus:**
- 🤖 AI-powered report generation
- 📊 RAG (Retrieval-Augmented Generation)
- 📷 OCR for image processing
- 📈 RAGAS quality evaluation
- 📑 Auto PPT generation
```

---

## 📊 PACKAGE OVERVIEW

```
┌────────────────────────────────────────────────┐
│  Package 1: User Management (3 entities)       │
│  ├─ User, Dosen, Prodi                         │
│  └─ Role: GKM, GJM, Dosen                      │
├────────────────────────────────────────────────┤
│  Package 2: Academic Management (4 entities)   │
│  ├─ Matakuliah, Ajaran, RPS, Materi            │
│  └─ Core academic operations                   │
├────────────────────────────────────────────────┤
│  Package 3: Monitoring (3 entities)            │
│  ├─ Monitoring, EvaluasiArtefak, Snapshot      │
│  └─ Compliance tracking & evaluation           │
├────────────────────────────────────────────────┤
│  Package 4: Questionnaire (4 entities)         │
│  ├─ Kuisioner, Upload, Pertanyaan, Jawaban     │
│  └─ Survey & satisfaction analysis             │
├────────────────────────────────────────────────┤
│  Package 5: AI & RAG (7 entities) 🔥           │
│  ├─ DocumentChunk, AICache, AIEvaluation       │
│  └─ RAG system, caching, quality metrics       │
├────────────────────────────────────────────────┤
│  Package 6: Reporting (3 entities)             │
│  ├─ TemplateLaporan, LaporanGKM, LaporanGJM    │
│  └─ AI-powered report generation               │
├────────────────────────────────────────────────┤
│  Package 7: Notification (3 entities)          │
│  ├─ JadwalReminder, Reminder, LogEmail         │
│  └─ Automated reminder & email tracking        │
└────────────────────────────────────────────────┘
```

---

## 🎯 PILIH DIAGRAM BERDASARKAN KEBUTUHAN

| Kebutuhan | Diagram | Alasan |
|-----------|---------|--------|
| SRS Section 3 (Technical Design) | Complete | Detail lengkap untuk developer |
| Executive Summary | Simple | Easy to understand |
| Functional Requirements Mapping | Complete | Need all entities |
| Stakeholder Presentation | Simple | Focus on business |
| System Architecture Documentation | Complete | Include AI/RAG system |
| User Manual / Training | Simple | Simplified view |

---

## 💡 TIPS UNTUK SRS

### 1. Include Both Versions
```
- Include Simple di awal (overview)
- Include Complete di appendix (detail)
- Reference keduanya dalam text
```

### 2. Add Descriptions
```
Jangan cuma paste diagram, tambahkan:
- Package explanation
- Key entities description
- Business rules
- Relationships explanation
```

### 3. Map to Requirements
```
Link class diagram dengan functional requirements:
- FR-01 (Login) → User entity
- FR-05 (Upload RPS) → RPS, Dosen, Matakuliah
- FR-10 (Generate Laporan) → LaporanGKM, AI/RAG
```

### 4. Highlight Unique Features
```
Emphasize pada Package 5 (AI & RAG):
- Explain RAG concept
- Show competitive advantage
- Technical innovation
```

---

## 📚 REFERENSI LENGKAP

**Read This First:**
```
SRS_CLASS_DIAGRAM_DOCUMENTATION.md
```

**Related Documents:**
```
../CLASS_DIAGRAM_DOCUMENTATION.md       (Technical implementation)
../DIAGRAM_COMPARISON_REPORT.md         (Validation report)
../diagrams/models-fixed.puml           (Generated from code)
```

**Tools:**
```
PlantUML: http://www.plantuml.com/plantuml/
VSCode Extension: PlantUML
Online Converter: https://www.planttext.com/
```

---

## ✅ CHECKLIST UNTUK SRS

- [ ] Pilih diagram yang sesuai (Complete vs Simple)
- [ ] Export ke PNG/SVG (high resolution)
- [ ] Include di Section 3 SRS
- [ ] Add caption & description
- [ ] Explain key packages
- [ ] Map to functional requirements
- [ ] Highlight AI/RAG features
- [ ] Add to table of figures
- [ ] Cross-reference dalam text
- [ ] Review dengan team

---

**Created**: 15 Juni 2026  
**Purpose**: SRS Documentation  
**Status**: ✅ Ready to Use
