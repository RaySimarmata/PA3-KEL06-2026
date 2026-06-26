# 🚀 ERD Quick Start Guide

## 📍 File Locations

```
docs/diagrams/
├── ERD_DATABASE.puml          ← Complete ERD (43 tables)
├── ERD_SIMPLIFIED.puml        ← Simplified ERD (overview)
├── ERD_DOCUMENTATION.md       ← Full documentation
└── ERD_README.md              ← Detailed guide
```

## 🎯 Quick View ERD

### Option 1: Online (No Installation)
1. Buka https://www.planttext.com/
2. Copy isi file `ERD_DATABASE.puml` atau `ERD_SIMPLIFIED.puml`
3. Paste dan lihat hasilnya
4. Download sebagai PNG/SVG/PDF

### Option 2: VSCode
```bash
# Install extension
code --install-extension jebbs.plantuml

# Buka file ERD_DATABASE.puml
# Tekan Alt + D untuk preview
```

### Option 3: Generate Image
```bash
# Install (Windows - pakai choco)
choco install plantuml graphviz

# Generate
cd docs/diagrams
plantuml -tsvg ERD_DATABASE.puml
plantuml -tsvg ERD_SIMPLIFIED.puml
```

## 📊 Struktur Database - Ringkasan

### 🏢 Master Data (9 tables)
- `prodi` - Program Studi
- `users` - User sistem (admin, kaprodi, dosen, reviewer)
- `dosen` - Data dosen/pengajar
- `matakuliah` - Mata kuliah
- `ajaran` - Tahun ajaran & semester
- `periode_akademik` - Periode akademik
- `kelas` - Data kelas
- `dosenn` - Dosen external API
- `jabatan_akademik` - Jabatan akademik

**Relasi:** Semua berpusat di `prodi`

### 📚 Pembelajaran (2 tables)
- `rps` - Rencana Pembelajaran Semester
- `materi` - Materi per pertemuan

**Hierarki:** `matakuliah → rps → materi`

### 📝 Kuesioner (4 tables)
- `kuisioner` - Master kuesioner
- `pertanyaan_kuisioner` - Pertanyaan
- `jawaban_kuisioner` - Jawaban mahasiswa/dosen
- `kuesioner_uploads` - Upload file kuesioner eksternal

### 📊 Monitoring (4 tables)
- `monitoring` - Monitoring umum
- `rps_monitoring_snapshots` - Snapshot RPS
- `perkuliahan_monitoring_snapshots` - Snapshot perkuliahan
- `perkuliahan_monitoring_detail` - Detail monitoring

**Benefit:** Historical data, fast reporting

### 📄 Laporan GKM (2 tables)
- `laporan_gkm` - Laporan tingkat Prodi
- `evaluasi_artefak` - Evaluasi artefak (foto/video/dokumen)

**Jenis:** bulanan, semester, tahunan, artefak

### 📊 Laporan GJM (3 tables)
- `laporan_gjm` - Laporan tingkat Institusi (AI-powered)
- `template_laporan` - Template laporan
- `laporan_bulanan` - Laporan bulanan terstruktur

**Features:**
- ✅ AI Query & Response
- ✅ RAG (Retrieval-Augmented Generation)
- ✅ RAGAS Evaluation (4 metrics)
- ✅ OCR Support
- ✅ PPT Generation

### 🤖 AI & RAG System (4 tables)
- `document_chunks` - Chunk untuk RAG (dengan vector embeddings)
- `embeddings_cache` - Cache embedding vectors
- `ai_response_cache` - Cache AI responses
- `ai_evaluation_tests` - RAGAS evaluation

**Technology:** Vector database (pgvector/MongoDB)

### 📧 Reminder & Email (4 tables)
- `reminder` - Reminder manual
- `jadwal_reminder` - Reminder terjadwal (recurring)
- `log_email` - Log pengiriman email
- `kirim_laporan_history` - History kirim laporan

### 👥 Perwaliaan & KPI (2 tables)
- `perwaliaan` - Konseling wali-mahasiswa
- `pencapaian_kpi` - Pencapaian KPI Prodi

### 🔧 System Tables (3 tables)
- `jobs` - Laravel queue jobs
- `failed_jobs` - Failed jobs
- `cache` - Application cache

### 🔗 Relasi Many-to-Many (3 tables)
- `dosen_matakuliah` - Dosen mengajar matakuliah (permanent)
- `matkul_dosen` - Pengampu per periode
- `jadwal_dosen` - Jadwal lengkap (hari, jam, ruangan)

## 🔍 Relasi Utama

```
prodi (Program Studi)
  ├─→ users (many)
  ├─→ dosen (many)
  ├─→ matakuliah (many)
  │    └─→ rps (many)
  │         └─→ materi (many)
  ├─→ ajaran (many)
  │    ├─→ kuisioner (many)
  │    ├─→ monitoring (many)
  │    ├─→ laporan_gkm (many)
  │    └─→ laporan_gjm (many)
  └─→ laporan_gkm (many)

users (1:1) dosen

dosen ←→ matakuliah (many-to-many via dosen_matakuliah)

template_laporan
  ├─→ laporan_gjm (many)
  ├─→ laporan_bulanan (many)
  └─→ document_chunks (many)
```

## 💾 Status & Enum Values

### Status Laporan
- `draft` - Belum selesai
- `menunggu_review` - Menunggu review
- `approved` - Disetujui
- `revisi` - Perlu perbaikan
- `processing` - Sedang diproses (AI)
- `failed` - Gagal
- `completed` - Selesai

### Role User
- `admin` - Administrator
- `kaprodi` - Kepala Prodi
- `dosen` - Dosen/Pengajar
- `gjm_reviewer` - Reviewer GJM
- `gkm_reviewer` - Reviewer GKM

### Status RPS
- `draft` - Draft
- `menunggu_review` - Menunggu review
- `sudah_divalidasi` - Sudah divalidasi
- `revisi` - Perlu revisi

### Jenis Laporan GJM
- `bulanan` - Laporan bulanan
- `triwulan` - Laporan triwulan (3 bulan)
- `semester` - Laporan semester
- `tahunan` - Laporan tahunan
- `vmts` - Visiting Monitoring Technical Support

## 📋 Common Queries

### Get Laporan GKM dengan Prodi
```sql
SELECT lg.*, p.nama_prodi, d.nama_lengkap as ketua
FROM laporan_gkm lg
JOIN dosen d ON lg.dosen_ketua = d.id
JOIN prodi p ON d.prodi_id = p.id
WHERE p.id = ? AND lg.ajaran_id = ?;
```

### Get RPS dengan Materi
```sql
SELECT r.*, m.*
FROM rps r
LEFT JOIN materi m ON r.id = m.rps_id
WHERE r.matakuliah_id = ?
  AND r.ajaran_id = ?
ORDER BY m.pertemuan_ke;
```

### Get Monitoring Summary per Prodi
```sql
SELECT 
  jenis_monitoring,
  SUM(total_dosen) as total,
  SUM(dosen_selesai) as selesai,
  AVG(persentase_kepatuhan) as avg_kepatuhan
FROM monitoring
WHERE prodi_id = ? AND ajaran_id = ?
GROUP BY jenis_monitoring;
```

### Get Dosen dengan Matakuliah yang Diajar
```sql
SELECT d.nama_lengkap, m.nama_mk, m.kode_mk
FROM dosen d
JOIN dosen_matakuliah dm ON d.id = dm.dosen_id
JOIN matakuliah m ON dm.matakuliah_id = m.id
WHERE d.prodi_id = ?
ORDER BY d.nama_lengkap, m.nama_mk;
```

### Get AI Response dari Cache
```sql
SELECT response 
FROM ai_response_cache
WHERE query_hash = MD5(?)
  AND model_name = ?
  AND temperature = ?
  AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY);
```

### Get Laporan GJM dengan RAGAS Metrics
```sql
SELECT 
  id,
  jenis_laporan,
  status_laporan,
  ragas_faithfulness,
  ragas_answer_relevancy,
  ragas_context_precision,
  ragas_context_recall,
  (ragas_faithfulness + ragas_answer_relevancy + 
   ragas_context_precision + ragas_context_recall) / 4 as avg_ragas_score
FROM laporan_gjm
WHERE ajaran_id = ?
  AND ragas_faithfulness IS NOT NULL
ORDER BY avg_ragas_score DESC;
```

## 🎨 Fitur Khusus Database

### 1. Vector Embeddings
```sql
-- Semantic search di document_chunks
SELECT chunk_text, metadata
FROM document_chunks
ORDER BY embedding_vector <=> ?  -- Vector similarity
LIMIT 5;
```

**Digunakan untuk:** RAG retrieval, semantic search

### 2. JSON Fields
```sql
-- Query JSON field
SELECT hasil_analisis->>'$.statistik.mean' as mean_score
FROM kuesioner_uploads
WHERE id = ?;

-- Update JSON field
UPDATE laporan_gjm
SET rag_sources = JSON_ARRAY_APPEND(rag_sources, '$', ?)
WHERE id = ?;
```

**Digunakan untuk:** 
- hasil_analisis (kuesioner)
- metadata (document_chunks)
- rag_sources (laporan_gjm)
- statistik_summary (kuesioner_uploads)

### 3. Snapshot Pattern
```sql
-- Create snapshot
INSERT INTO rps_monitoring_snapshots 
  (prodi_id, ajaran_id, snapshot_date, total_mk, mk_rps_complete, ...)
SELECT 
  prodi_id,
  ajaran_id,
  CURDATE(),
  COUNT(*) as total_mk,
  SUM(CASE WHEN status_rps = 'sudah_divalidasi' THEN 1 ELSE 0 END),
  ...
FROM rps
GROUP BY prodi_id, ajaran_id;
```

**Benefit:** Fast reporting tanpa perlu recompute

### 4. Soft Status (No Soft Delete)
Tidak ada `deleted_at` field. Gunakan status untuk inactivation:
```sql
-- "Delete" user
UPDATE users SET is_active = FALSE WHERE id = ?;

-- "Delete" matakuliah
UPDATE matakuliah SET status = 'tidak_aktif' WHERE id = ?;
```

## 🔐 Security & Audit

### Audit Trail Tables
- `log_email` - Track email sent
- `kirim_laporan_history` - Track laporan submissions
- `ai_evaluation_tests` - Track AI evaluation

### User Tracking Fields
- `created_by`, `reviewed_by`, `sent_by` - Who did what
- `created_at`, `updated_at` - When

### Recommended Indexes
```sql
-- Frequently queried fields
CREATE INDEX idx_prodi_id ON dosen(prodi_id);
CREATE INDEX idx_ajaran_id ON rps(ajaran_id);
CREATE INDEX idx_status ON laporan_gjm(status_laporan);
CREATE INDEX idx_query_hash ON ai_response_cache(query_hash);

-- Composite indexes
CREATE INDEX idx_dosen_mk ON dosen_matakuliah(dosen_id, matakuliah_id);
CREATE INDEX idx_snapshot_date ON rps_monitoring_snapshots(prodi_id, ajaran_id, snapshot_date);

-- Vector index (PostgreSQL with pgvector)
CREATE INDEX idx_embedding ON document_chunks USING ivfflat (embedding_vector);
```

## ⚡ Performance Tips

### 1. Use Snapshots
```sql
-- BAD: Compute on-the-fly
SELECT COUNT(*) FROM rps WHERE status_rps = 'sudah_divalidasi' ...

-- GOOD: Read from snapshot
SELECT mk_rps_complete FROM rps_monitoring_snapshots WHERE ...
```

### 2. Cache AI Responses
```sql
-- Check cache first
SELECT response FROM ai_response_cache WHERE query_hash = MD5(query);

-- If not found, generate and cache
INSERT INTO ai_response_cache (query_hash, response, ...) VALUES (...);
```

### 3. Paginate Large Results
```sql
-- Use LIMIT and OFFSET
SELECT * FROM laporan_gjm
ORDER BY created_at DESC
LIMIT 20 OFFSET 0;
```

### 4. Use Eager Loading (Laravel)
```php
// BAD: N+1 query problem
$laporans = LaporanGKM::all();
foreach($laporans as $laporan) {
    echo $laporan->prodi->nama_prodi;  // Query per item
}

// GOOD: Eager loading
$laporans = LaporanGKM::with('prodi', 'dosen')->get();
```

## 🛠️ Maintenance

### Regular Tasks
```sql
-- 1. Clean old cache (monthly)
DELETE FROM ai_response_cache 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

DELETE FROM cache 
WHERE expiration < UNIX_TIMESTAMP();

-- 2. Archive old logs (yearly)
INSERT INTO log_email_archive 
SELECT * FROM log_email 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

DELETE FROM log_email 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- 3. Update snapshot (daily via cron)
CALL create_daily_snapshot();

-- 4. Vacuum (PostgreSQL monthly)
VACUUM ANALYZE;

-- 5. Optimize tables (MySQL monthly)
OPTIMIZE TABLE laporan_gjm, ai_response_cache, document_chunks;
```

### Backup Strategy
```bash
# Daily backup
mysqldump -u root -p pa3_kel06 > backup_$(date +%Y%m%d).sql

# Weekly full backup with gzip
mysqldump -u root -p pa3_kel06 | gzip > backup_$(date +%Y%m%d).sql.gz

# Backup specific tables
mysqldump -u root -p pa3_kel06 laporan_gjm laporan_gkm > laporan_backup.sql
```

## 📚 Learn More

| Resource | Description |
|----------|-------------|
| [ERD_DOCUMENTATION.md](./diagrams/ERD_DOCUMENTATION.md) | Complete documentation |
| [ERD_README.md](./diagrams/ERD_README.md) | Detailed guide |
| [ERD_DATABASE.puml](./diagrams/ERD_DATABASE.puml) | Complete ERD source |
| [ERD_SIMPLIFIED.puml](./diagrams/ERD_SIMPLIFIED.puml) | Simplified ERD source |

## 🆘 Need Help?

**Common Issues:**
- ❓ **Can't view ERD?** → Use online tool: https://www.planttext.com/
- ❓ **Too complex?** → Use ERD_SIMPLIFIED.puml
- ❓ **Need specific info?** → Read ERD_DOCUMENTATION.md
- ❓ **Query help?** → Check Common Queries section above

**Contact:**
- Database Team
- Check `ERD_DOCUMENTATION.md` for detailed explanations

---

**Version:** 1.0  
**Last Updated:** 2026-06-16  
**Total Tables:** 43  
**Status:** ✅ Complete
