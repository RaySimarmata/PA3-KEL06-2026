# 📊 Entity Relationship Diagram (ERD)

**Created:** June 16, 2026  
**Total Tables:** 43  
**Database:** MySQL/PostgreSQL with Vector support  
**Purpose:** Sistem Penjaminan Mutu Internal (SPMI) - GKM & GJM

---

## 📁 Available ERD Files

### 1. Complete ERD (`ERD_DATABASE.puml`)
**Contains:** All 43 tables with complete relationships

**Categories:**
- ✅ Master Data (9 tables)
- ✅ Many-to-Many Relations (3 tables)
- ✅ Pembelajaran - RPS & Materi (2 tables)
- ✅ Monitoring & Snapshots (4 tables)
- ✅ Kuesioner (4 tables)
- ✅ Laporan GKM (2 tables)
- ✅ Laporan GJM (3 tables)
- ✅ Reminder & Email (4 tables)
- ✅ AI & RAG System (4 tables)
- ✅ Perwaliaan & KPI (2 tables)
- ✅ System Tables (3 tables)

**View:**
- 🌐 [PlantUML Online](https://www.plantuml.com/plantuml/uml/) - Copy-paste content
- 📱 VSCode: Open `ERD_DATABASE.puml` → Press `Alt + D`
- 🖼️ Generate PNG: `plantuml ERD_DATABASE.puml`

---

### 2. Simplified ERD (`ERD_SIMPLIFIED.puml`)
**Contains:** Main entities grouped by package

**Packages:**
- 📦 Master Data (prodi, users, dosen, matakuliah, ajaran)
- 📦 Pembelajaran (rps, materi)
- 📦 Kuesioner (kuisioner, kuesione_upload)
- 📦 Monitoring (monitoring, snapshots)
- 📦 Laporan GKM (laporan_gkm, evaluasi_artefak)
- 📦 Laporan GJM + AI/RAG (laporan_gjm, template, document_chunk, ai_cache)
- 📦 Reminder & Email (reminder, log_email)

**View:**
- 🌐 [PlantUML Online](https://www.plantuml.com/plantuml/uml/)
- 📱 VSCode: Open `ERD_SIMPLIFIED.puml` → Press `Alt + D`

**Best For:**
- Presentations
- High-level overview
- Planning discussions
- Onboarding new team members

---

### 3. ERD Documentation (`ERD_DOCUMENTATION.md`)
**Contains:** Comprehensive documentation

**Sections:**
- 📖 Table descriptions with field details
- 🔗 Relationship patterns
- 📊 Status & enum values
- 💡 Query examples
- ⚡ Performance considerations
- 🔒 Security & audit
- 📝 Best practices

**When to Read:**
- Database design decisions
- Query optimization
- Understanding relationships
- Maintenance planning

---

## 🎨 How to View ERD

### Method 1: Online (Easiest)

#### PlantUML Files (.puml)
1. Open https://www.plantuml.com/plantuml/uml/
2. Copy entire content of `ERD_DATABASE.puml` or `ERD_SIMPLIFIED.puml`
3. Paste in text area
4. View & download PNG/SVG/PDF

**Alternative:**
- https://www.planttext.com/ (faster)
- https://plantuml-editor.kkeisuke.com/ (feature-rich)

---

### Method 2: VSCode Extension

```bash
# Install PlantUML extension
code --install-extension jebbs.plantuml

# Open ERD_DATABASE.puml or ERD_SIMPLIFIED.puml
# Press Alt + D to preview
# Right-click → Export to PNG/SVG/PDF
```

---

### Method 3: Generate Images

#### Install PlantUML CLI

**Windows:**
```powershell
# Install Java first (required)
winget install Oracle.JavaRuntimeEnvironment

# Install PlantUML & Graphviz
choco install plantuml graphviz
```

**Mac:**
```bash
brew install openjdk plantuml graphviz
```

**Linux:**
```bash
sudo apt install default-jre plantuml graphviz
```

#### Generate Images

```bash
cd docs/diagrams

# Generate PNG (good for quick view)
plantuml ERD_DATABASE.puml
plantuml ERD_SIMPLIFIED.puml

# Generate SVG (recommended - scalable)
plantuml -tsvg ERD_DATABASE.puml
plantuml -tsvg ERD_SIMPLIFIED.puml

# Generate PDF (high quality for printing)
plantuml -tpdf ERD_DATABASE.puml

# Generate all ERD files
plantuml ERD_*.puml
```

**Output:**
- `ERD_DATABASE.png` / `.svg` / `.pdf`
- `ERD_SIMPLIFIED.png` / `.svg` / `.pdf`

---

## 📊 Database Overview

### Master Data Tables

| Table | Description | Key Fields |
|-------|-------------|------------|
| `prodi` | Program Studi | kode_prodi, nama_prodi, kaprodi_id |
| `users` | Sistem Users | email, username, role, prodi_id |
| `dosen` | Data Dosen | user_id, nidn, nama_lengkap, prodi_id |
| `matakuliah` | Mata Kuliah | kode_mk, nama_mk, sks, prodi_id |
| `ajaran` | Tahun Ajaran | tahun_ajaran, semester, status |

### Core Application Tables

| Module | Tables | Purpose |
|--------|--------|---------|
| **Pembelajaran** | rps, materi | RPS dan materi perkuliahan |
| **Monitoring** | monitoring, rps_monitoring_snapshots, perkuliahan_monitoring_snapshots | Tracking kepatuhan dan completion |
| **Kuesioner** | kuisioner, pertanyaan_kuisioner, jawaban_kuisioner, kuesioner_uploads | Evaluasi dan feedback |
| **Laporan GKM** | laporan_gkm, evaluasi_artefak | Laporan tingkat Prodi |
| **Laporan GJM** | laporan_gjm, template_laporan, laporan_bulanan | Laporan tingkat Institusi (AI-powered) |

### AI & RAG System

| Table | Purpose | Technology |
|-------|---------|------------|
| `document_chunks` | Document chunking untuk RAG | Vector embeddings (pgvector/MongoDB) |
| `embeddings_cache` | Cache embedding vectors | Hash-based caching |
| `ai_response_cache` | Cache AI responses | Query hash with hit counting |
| `ai_evaluation_tests` | RAGAS evaluation metrics | Faithfulness, relevancy, precision, recall |

---

## 🔍 Key Features in ERD

### 1. Multi-level Reporting
```
GKM (Program Studi)          GJM (Institusi)
      ↓                            ↓
  laporan_gkm                  laporan_gjm
      ↓                            ↓
evaluasi_artefak        AI/RAG/RAGAS/OCR/PPT
```

### 2. AI-Powered Report Generation

**Laporan GJM Features:**
- ✅ AI Query & Response
- ✅ RAG (Retrieval-Augmented Generation)
- ✅ RAGAS Evaluation (faithfulness, relevancy, precision, recall)
- ✅ OCR Support (extract text from images)
- ✅ PPT Generation
- ✅ Multi-format output (PDF, DOCX)

### 3. Vector Database Integration

**Document Chunks:**
```sql
CREATE TABLE document_chunks (
  id BIGINT PRIMARY KEY,
  chunk_text TEXT,
  embedding_vector VECTOR(1536),  -- OpenAI/Gemini embeddings
  metadata JSON,
  source_file VARCHAR,
  source_page INT
);
```

**Use Case:** Semantic search untuk RAG retrieval

### 4. Monitoring Snapshots

**Benefits:**
- Historical data tracking
- Fast reporting (no recomputation)
- Trend analysis
- Performance optimization

**Tables:**
- `rps_monitoring_snapshots`: RPS completion tracking
- `perkuliahan_monitoring_snapshots`: Perkuliahan completion
- `perkuliahan_monitoring_detail`: Detail per dosen & matakuliah

### 5. Automated Reminders

**System:**
- Manual reminders: `reminder` table
- Scheduled reminders: `jadwal_reminder` table (recurring support)
- Email logging: `log_email` table (with retry mechanism)
- History: `kirim_laporan_history` table (audit trail)

---

## 📈 Relationships Overview

### One-to-Many (Most Common)
```
prodi → users, dosen, matakuliah, laporan_gkm
dosen → rps, reminder, laporan_gkm
rps → materi
kuisioner → pertanyaan_kuisioner → jawaban_kuisioner
```

### Many-to-Many
```
dosen ←→ matakuliah (via dosen_matakuliah)
dosen ←→ matakuliah ←→ ajaran (via matkul_dosen)
```

### One-to-One
```
users ←→ dosen (profile relationship)
```

---

## 💡 Use Cases

### For Developers
```
1. Read ERD_DOCUMENTATION.md
   → Understand table purposes and fields
   
2. View ERD_DATABASE.puml
   → See complete relationships
   
3. Write queries
   → Use documented patterns
   
4. Add migrations
   → Update ERD files
```

### For Database Administrators
```
1. View ERD_DATABASE.puml
   → Plan indexing strategy
   
2. Check ERD_DOCUMENTATION.md
   → Performance considerations
   
3. Implement optimizations
   → Partitioning, archiving
```

### For Project Managers
```
1. View ERD_SIMPLIFIED.puml
   → Understand system scope
   
2. Present to stakeholders
   → High-level overview
   
3. Plan features
   → Identify required tables
```

### For Architects
```
1. View both ERD files
   → Complete vs simplified views
   
2. Read ERD_DOCUMENTATION.md
   → Design patterns and best practices
   
3. Plan scaling
   → Sharding, partitioning strategy
```

---

## 🎯 Quick Reference

### Find a Table
```
Master Data       → ERD_DOCUMENTATION.md § 1
Relations         → ERD_DOCUMENTATION.md § 2
RPS & Materi      → ERD_DOCUMENTATION.md § 3
Monitoring        → ERD_DOCUMENTATION.md § 4
Kuesioner         → ERD_DOCUMENTATION.md § 5
Laporan GKM       → ERD_DOCUMENTATION.md § 6
Laporan GJM       → ERD_DOCUMENTATION.md § 7
Reminders         → ERD_DOCUMENTATION.md § 8
AI & RAG          → ERD_DOCUMENTATION.md § 9
```

### Common Queries
```sql
-- Get Laporan GKM with Prodi
SELECT lg.*, p.nama_prodi, d.nama_lengkap
FROM laporan_gkm lg
JOIN dosen d ON lg.dosen_ketua = d.id
JOIN prodi p ON d.prodi_id = p.id
WHERE p.id = ? AND lg.ajaran_id = ?;

-- Get RPS with Materi count
SELECT r.*, COUNT(m.id) as jumlah_materi
FROM rps r
LEFT JOIN materi m ON r.id = m.rps_id
WHERE r.dosen_id = ? AND r.ajaran_id = ?
GROUP BY r.id;

-- Get AI Response from Cache
SELECT response
FROM ai_response_cache
WHERE query_hash = MD5(?)
  AND model_name = ?
  AND created_at > NOW() - INTERVAL 7 DAY;
```

More queries: `ERD_DOCUMENTATION.md` § Query Patterns

---

## 🔄 Maintenance

### When to Update ERD

Update ERD files when:
- ✅ Adding new tables (migration)
- ✅ Adding/removing columns
- ✅ Changing relationships
- ✅ Modifying foreign keys
- ✅ Adding indexes (document only)

### How to Update

1. **Update PlantUML:**
   ```
   Edit ERD_DATABASE.puml or ERD_SIMPLIFIED.puml
   Add/modify entity definitions
   Update relationships
   ```

2. **Update Documentation:**
   ```
   Edit ERD_DOCUMENTATION.md
   Add table description
   Document new fields
   Update relationship section
   ```

3. **Regenerate Images:**
   ```bash
   plantuml -tsvg ERD_DATABASE.puml
   plantuml -tsvg ERD_SIMPLIFIED.puml
   ```

4. **Commit Changes:**
   ```bash
   git add docs/diagrams/ERD_*
   git commit -m "Update ERD: [describe changes]"
   ```

---

## 🆘 Troubleshooting

### PlantUML Memory Error

**Problem:** Diagram too large, memory error

**Solution:**
```bash
# Increase memory limit
export PLANTUML_LIMIT_SIZE=16384
plantuml ERD_DATABASE.puml

# Or use simplified version
plantuml ERD_SIMPLIFIED.puml
```

### Diagram Not Rendering

**Problem:** Can't see diagram output

**Solutions:**
1. **Check Java:** `java -version` (must be installed)
2. **Check Graphviz:** `dot -V` (must be installed)
3. **Use online:** https://www.planttext.com/
4. **Try different format:** `-tsvg` instead of `-tpng`

### Relationship Arrows Unclear

**Problem:** Too many arrows, confusing

**Solutions:**
1. Use `ERD_SIMPLIFIED.puml` (fewer entities)
2. Zoom in on SVG output
3. Read `ERD_DOCUMENTATION.md` for textual description
4. Generate separate diagrams by category

### File Too Large

**Problem:** ERD file is too big

**Solutions:**
1. Split into multiple files by category:
   - ERD_MASTER_DATA.puml
   - ERD_REPORTS.puml
   - ERD_AI_SYSTEM.puml
2. Use simplified version
3. Generate specific sections only

---

## 📚 Additional Resources

- 📖 [ERD_DOCUMENTATION.md](./ERD_DOCUMENTATION.md) - Comprehensive database documentation
- 📊 [Class Diagrams](./README.md) - Application class structure
- 🎨 [PlantUML Guide](../CLASS_DIAGRAM_TOOLS_GUIDE.md) - Complete PlantUML tutorial
- ⚖️ [Tools Comparison](../TOOLS_COMPARISON.md) - ERD tools comparison

### External Links

- [PlantUML Reference](https://plantuml.com/class-diagram)
- [Database Design Best Practices](https://www.postgresql.org/docs/current/ddl.html)
- [Laravel Database Documentation](https://laravel.com/docs/10.x/database)
- [Vector Database Guide](https://www.postgresql.org/docs/current/pgvector.html)

---

## 📝 Statistics

| Category | Count | Details |
|----------|-------|---------|
| **Total Tables** | 43 | Including system tables |
| **Relationships** | 100+ | FK constraints + app-level |
| **Many-to-Many** | 3 | Pivot tables |
| **JSON Fields** | 15+ | Flexible data storage |
| **Vector Fields** | 2 | AI embeddings |
| **Enum Fields** | 20+ | Status, role, type fields |
| **Nullable FKs** | 8 | Flexible relationships |

---

**Created by:** Database Team  
**Last Updated:** 2026-06-16  
**Version:** 1.0  
**Status:** ✅ Complete and documented

**Need help?** Read [ERD_DOCUMENTATION.md](./ERD_DOCUMENTATION.md) or contact the team!
