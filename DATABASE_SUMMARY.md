# 📊 Database Summary - PA3 KEL06 2026

## Quick Stats
- **Total Tables:** 43
- **Database Type:** MySQL/PostgreSQL (with Vector support)
- **Purpose:** Sistem Penjaminan Mutu Internal (SPMI)
- **Modules:** GKM (Prodi Level) + GJM (Institusi Level)

## 📁 ERD Documentation Files

```
📦 docs/
├── 📄 ERD_QUICK_GUIDE.md               ← START HERE!
└── 📂 diagrams/
    ├── 📊 ERD_DATABASE.puml            ← Complete ERD (43 tables)
    ├── 📊 ERD_SIMPLIFIED.puml          ← Simplified ERD (overview)
    ├── 📖 ERD_DOCUMENTATION.md         ← Comprehensive docs
    └── 📘 ERD_README.md                ← Detailed guide
```

## 🎯 Quick Access

### View ERD Online (No Installation)
**👉 https://www.planttext.com/**
1. Copy isi file `ERD_DATABASE.puml` atau `ERD_SIMPLIFIED.puml`
2. Paste ke website
3. View hasil diagram

### Local Setup
```bash
# Install PlantUML
choco install plantuml graphviz  # Windows

# Generate diagram
cd docs/diagrams
plantuml -tsvg ERD_DATABASE.puml
```

## 📊 Tables by Category

### 🏢 Master Data (9 tables)
| Table | Records (Est.) | Description |
|-------|----------------|-------------|
| `prodi` | ~20 | Program Studi |
| `users` | ~500 | System users (all roles) |
| `dosen` | ~300 | Dosen/Lecturer data |
| `dosenn` | ~300 | External API dosen |
| `matakuliah` | ~600 | Mata kuliah |
| `ajaran` | ~50 | Tahun ajaran & semester |
| `periode_akademik` | ~50 | Periode akademik |
| `kelas` | ~100 | Kelas per prodi |
| `jabatan_akademik` | ~10 | Jabatan lookup |

### 📚 Pembelajaran (2 tables)
| Table | Records (Est.) | Description |
|-------|----------------|-------------|
| `rps` | ~2,000 | RPS per mk per periode |
| `materi` | ~30,000 | Materi per pertemuan (~15/RPS) |

### 📝 Kuesioner (4 tables)
| Table | Records (Est.) | Description |
|-------|----------------|-------------|
| `kuisioner` | ~100 | Master kuesioner |
| `pertanyaan_kuisioner` | ~1,000 | Pertanyaan (~10/kuesioner) |
| `jawaban_kuisioner` | ~50,000 | Jawaban mahasiswa |
| `kuesioner_uploads` | ~500 | Upload external kuesioner |

### 📊 Monitoring (4 tables)
| Table | Records (Est.) | Description |
|-------|----------------|-------------|
| `monitoring` | ~1,000 | Monitoring records |
| `rps_monitoring_snapshots` | ~500 | Daily snapshots RPS |
| `perkuliahan_monitoring_snapshots` | ~500 | Daily snapshots perkuliahan |
| `perkuliahan_monitoring_detail` | ~10,000 | Detail per dosen & mk |

### 📄 Laporan GKM (2 tables)
| Table | Records (Est.) | Description |
|-------|----------------|-------------|
| `laporan_gkm` | ~500 | Laporan tingkat Prodi |
| `evaluasi_artefak` | ~1,000 | Evaluasi artefak |

### 📊 Laporan GJM (3 tables)
| Table | Records (Est.) | Description |
|-------|----------------|-------------|
| `template_laporan` | ~20 | Template laporan |
| `laporan_gjm` | ~200 | Laporan institusi (AI-powered) |
| `laporan_bulanan` | ~500 | Laporan bulanan |

### 🤖 AI & RAG (4 tables)
| Table | Records (Est.) | Description |
|-------|----------------|-------------|
| `document_chunks` | ~10,000 | Chunked documents + vectors |
| `embeddings_cache` | ~5,000 | Cached embeddings |
| `ai_response_cache` | ~2,000 | Cached AI responses |
| `ai_evaluation_tests` | ~500 | RAGAS evaluation results |

### 📧 Reminder & Email (4 tables)
| Table | Records (Est.) | Description |
|-------|----------------|-------------|
| `reminder` | ~2,000 | Manual reminders |
| `jadwal_reminder` | ~50 | Scheduled reminders |
| `log_email` | ~10,000 | Email logs |
| `kirim_laporan_history` | ~1,000 | Laporan submission history |

### 👥 Perwaliaan & KPI (2 tables)
| Table | Records (Est.) | Description |
|-------|----------------|-------------|
| `perwaliaan` | ~5,000 | Konseling records |
| `pencapaian_kpi` | ~200 | KPI achievements |

### 🔗 Relations (3 tables)
| Table | Records (Est.) | Description |
|-------|----------------|-------------|
| `dosen_matakuliah` | ~1,500 | Dosen-MK permanent mapping |
| `matkul_dosen` | ~2,000 | Per periode assignment |
| `jadwal_dosen` | ~3,000 | Schedule (day, time, room) |

### 🔧 System (3 tables)
| Table | Records (Est.) | Description |
|-------|----------------|-------------|
| `jobs` | Variable | Queue jobs |
| `failed_jobs` | Variable | Failed jobs |
| `cache` | Variable | Application cache |

## 📈 Storage Estimates

### Total Database Size
- **Small deployment** (~1 prodi, 2 years): ~500 MB
- **Medium deployment** (~5 prodi, 3 years): ~2 GB
- **Large deployment** (~20 prodi, 5 years): ~10 GB

### Largest Tables (by size)
1. `materi` - Files + metadata (~30,000 records)
2. `jawaban_kuisioner` - Survey responses (~50,000)
3. `document_chunks` - Vectors + text (~10,000 chunks)
4. `log_email` - Email logs (~10,000)
5. `ai_response_cache` - Cached responses (~2,000)

### Growth Rate (per year)
- `rps`: +400 records/year
- `materi`: +6,000 records/year
- `laporan_gkm`: +100 records/year
- `laporan_gjm`: +40 records/year
- `log_email`: +2,000 records/year

## 🔍 Key Features

### ✅ AI-Powered Reporting
- RAG (Retrieval-Augmented Generation)
- RAGAS evaluation (faithfulness, relevancy, precision, recall)
- OCR support for image extraction
- Automated PPT generation
- Multi-format output (PDF, DOCX)

### ✅ Vector Database
- Document chunking for semantic search
- Embedding caching for performance
- Support for OpenAI/Gemini embeddings
- pgvector (PostgreSQL) or MongoDB Atlas

### ✅ Performance Optimizations
- Snapshot tables for fast reporting
- AI response caching with hit counting
- Embedding caching to reduce API calls
- Query result caching

### ✅ Audit & Security
- Email logging with retry mechanism
- Laporan submission history
- User action tracking (created_by, reviewed_by)
- RAGAS evaluation for AI quality assurance

## 🔗 Critical Relationships

### Central Hub: `prodi`
```
prodi (Program Studi)
  ↓ (1:N)
  ├── users (system users)
  ├── dosen (lecturers)
  ├── matakuliah (courses)
  ├── ajaran (academic periods)
  ├── monitoring (monitoring data)
  └── laporan_gkm (GKM reports)
```

### Learning Flow
```
matakuliah → rps → materi
   (1:N)    (1:N)
```

### Reporting Flow
```
template_laporan
  ↓ (1:N)
  ├── laporan_gjm (AI-powered)
  ├── laporan_bulanan
  └── document_chunks (RAG)
```

### Survey Flow
```
kuisioner → pertanyaan_kuisioner → jawaban_kuisioner
  (1:N)            (1:N)
```

## 💡 Common Operations

### Get Active Academic Period
```sql
SELECT * FROM ajaran 
WHERE status = 'aktif' 
ORDER BY tahun_ajaran DESC, semester DESC 
LIMIT 1;
```

### Get Prodi Summary
```sql
SELECT 
  p.nama_prodi,
  COUNT(DISTINCT d.id) as total_dosen,
  COUNT(DISTINCT m.id) as total_matakuliah,
  COUNT(DISTINCT r.id) as total_rps
FROM prodi p
LEFT JOIN dosen d ON p.id = d.prodi_id
LEFT JOIN matakuliah m ON p.id = m.prodi_id
LEFT JOIN rps r ON m.id = r.matakuliah_id
WHERE p.id = ?
GROUP BY p.id;
```

### Get RPS Completion Rate
```sql
SELECT 
  COUNT(*) as total,
  SUM(CASE WHEN status_rps = 'sudah_divalidasi' THEN 1 ELSE 0 END) as validated,
  ROUND(100.0 * SUM(CASE WHEN status_rps = 'sudah_divalidasi' THEN 1 ELSE 0 END) / COUNT(*), 2) as percentage
FROM rps
WHERE ajaran_id = ? AND matakuliah_id IN (
  SELECT id FROM matakuliah WHERE prodi_id = ?
);
```

### Get AI Cache Hit Rate
```sql
SELECT 
  COUNT(*) as total_queries,
  SUM(hit_count) as total_hits,
  AVG(hit_count) as avg_hits_per_query,
  ROUND(AVG(response_time), 2) as avg_response_time_ms
FROM ai_response_cache
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
```

## 🛠️ Maintenance Schedule

### Daily
- ✅ Create monitoring snapshots
- ✅ Clean expired cache entries
- ✅ Backup incremental

### Weekly
- ✅ Analyze query performance
- ✅ Review failed jobs
- ✅ Clean old AI cache (>30 days)

### Monthly
- ✅ Vacuum/Optimize tables
- ✅ Archive old logs (>1 year)
- ✅ Review vector index performance
- ✅ Full backup

### Quarterly
- ✅ Review and optimize indexes
- ✅ Archive old snapshots
- ✅ Database size audit
- ✅ Security audit

## 📚 Documentation Index

| File | Purpose | Audience |
|------|---------|----------|
| **DATABASE_SUMMARY.md** | Overview & quick stats | Everyone |
| **ERD_QUICK_GUIDE.md** | Quick start guide | Developers |
| **diagrams/ERD_README.md** | Detailed ERD guide | Developers, DBAs |
| **diagrams/ERD_DOCUMENTATION.md** | Complete documentation | DBAs, Architects |
| **diagrams/ERD_DATABASE.puml** | Complete ERD diagram | Visual reference |
| **diagrams/ERD_SIMPLIFIED.puml** | Simplified ERD | Presentations, PMs |

## 🎯 Next Steps

### For New Developers
1. ✅ Read `ERD_QUICK_GUIDE.md`
2. ✅ View `ERD_SIMPLIFIED.puml` online
3. ✅ Explore `database/migrations/` folder
4. ✅ Check Laravel models in `app/Models/`

### For Database Admins
1. ✅ Read `ERD_DOCUMENTATION.md` (complete)
2. ✅ Review index strategy
3. ✅ Set up monitoring & backups
4. ✅ Configure vector database

### For Project Managers
1. ✅ View `ERD_SIMPLIFIED.puml`
2. ✅ Read this summary
3. ✅ Understand storage estimates
4. ✅ Plan capacity

### For Architects
1. ✅ Review `ERD_DATABASE.puml`
2. ✅ Read `ERD_DOCUMENTATION.md`
3. ✅ Evaluate scaling strategy
4. ✅ Plan optimization

## 🔗 Quick Links

- 📊 [View ERD Online](https://www.planttext.com/)
- 📖 [ERD Quick Guide](./docs/ERD_QUICK_GUIDE.md)
- 📘 [Complete ERD Documentation](./docs/diagrams/ERD_DOCUMENTATION.md)
- 🎨 [PlantUML Guide](./docs/CLASS_DIAGRAM_TOOLS_GUIDE.md)
- 🚀 [Laravel Docs](https://laravel.com/docs/10.x/database)

## 📞 Support

**Questions about:**
- **Database structure?** → Read `ERD_DOCUMENTATION.md`
- **How to view ERD?** → Read `ERD_QUICK_GUIDE.md`
- **Query examples?** → Check query sections in docs
- **Performance?** → See Performance Tips in docs

---

**Last Updated:** 2026-06-16  
**Database Version:** 1.0  
**Laravel Version:** 10.x  
**Total Tables:** 43  
**Status:** ✅ Production Ready
