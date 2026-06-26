# Entity Relationship Diagram (ERD) - Documentation

## Gambaran Umum

ERD ini menggambarkan struktur database untuk Sistem Penjaminan Mutu Internal (SPMI) yang terdiri dari dua modul utama:
- **GKM (Gugus Kendali Mutu)**: Monitoring di tingkat program studi
- **GJM (Gugus Jaminan Mutu)**: Monitoring di tingkat institusi

## Kategori Tabel

### 1. Master Data (9 tabel)
Tabel-tabel utama yang menyimpan data master sistem:

| Tabel | Deskripsi | Relasi Utama |
|-------|-----------|--------------|
| `prodi` | Program Studi | Parent untuk hampir semua tabel |
| `users` | Pengguna sistem | One-to-one dengan `dosen` |
| `dosen` | Data dosen/pengajar | Many-to-many dengan `matakuliah` |
| `dosenn` | Data dosen dari API external | Alternative dosen table |
| `matakuliah` | Mata kuliah | Parent untuk `rps` |
| `ajaran` | Tahun ajaran & semester | Parent untuk laporan & monitoring |
| `periode_akademik` | Periode akademik | Standalone periode tracking |
| `kelas` | Data kelas | Linked to `prodi` dan `dosen` (wali) |
| `jabatan_akademik` | Jabatan akademik dosen | Lookup table |

**Poin Penting:**
- Setiap `prodi` memiliki `kaprodi_id` (opsional)
- `users` memiliki relasi one-to-one dengan `dosen`
- Role user: admin, kaprodi, dosen, gjm_reviewer, gkm_reviewer

### 2. Tabel Relasi Many-to-Many (3 tabel)

| Tabel | Menghubungkan | Kegunaan |
|-------|---------------|----------|
| `dosen_matakuliah` | dosen ↔ matakuliah | Pengampu mata kuliah (permanent) |
| `matkul_dosen` | dosen ↔ matakuliah ↔ ajaran | Pengampu per periode |
| `jadwal_dosen` | dosen ↔ matakuliah ↔ ajaran | Jadwal mengajar lengkap dengan waktu & ruangan |

**Catatan:**
- `dosen_matakuliah`: Relasi permanen (siapa mengajar apa)
- `matkul_dosen`: Relasi per periode ajaran
- `jadwal_dosen`: Detail jadwal (hari, jam, ruangan, kelas)

### 3. Pembelajaran (2 tabel)

| Tabel | Deskripsi | Status Values |
|-------|-----------|---------------|
| `rps` | Rencana Pembelajaran Semester | draft, menunggu_review, sudah_divalidasi, revisi |
| `materi` | Materi perkuliahan per pertemuan | belum_upload, sudah_upload, perlu_revisi |

**Hierarki:** 
```
matakuliah → rps → materi
```

**Field Penting RPS:**
- `capaian_pembelajaran`, `strategi_pembelajaran`, `penugasan`, `penilaian`
- `file_rps`: path ke file RPS
- `tanggal_validasi`, `catatan_validasi`

**Field Penting Materi:**
- `pertemuan_ke`: urutan pertemuan
- `file_materi`, `file_format`: file dan format materi

### 4. Monitoring & Snapshot (4 tabel)

| Tabel | Deskripsi | Tracking |
|-------|-----------|----------|
| `monitoring` | Monitoring umum | RPS, materi, kuisioner, perwaliaan, evaluasi |
| `rps_monitoring_snapshots` | Snapshot monitoring RPS | Completion rate RPS per periode |
| `perkuliahan_monitoring_snapshots` | Snapshot monitoring perkuliahan | Completion rate perkuliahan |
| `perkuliahan_monitoring_detail` | Detail monitoring perkuliahan | Detail per dosen & matakuliah |

**Metrics:**
- Total vs Completed
- Persentase kepatuhan/completion
- Data tersimpan sebagai snapshot (historical data)

### 5. Kuesioner (4 tabel)

| Tabel | Deskripsi | Relasi |
|-------|-----------|--------|
| `kuisioner` | Master kuesioner | Parent table |
| `pertanyaan_kuisioner` | Pertanyaan dalam kuesioner | Child of `kuisioner` |
| `jawaban_kuisioner` | Jawaban mahasiswa/dosen | Child of `pertanyaan_kuisioner` |
| `kuesioner_uploads` | Upload file kuesioner eksternal | Linked to `matakuliah` |

**Tipe Pertanyaan:**
- pilihan_ganda
- essay
- skala_likert

**Kuesioner Uploads:**
- Upload dari file external (Excel/CSV)
- Hasil analisis disimpan dalam JSON
- Support statistik summary
- Jenis: evaluasi dosen, evaluasi mata kuliah, dll

### 6. Laporan GKM (2 tabel)

| Tabel | Deskripsi | Jenis Laporan |
|-------|-----------|---------------|
| `laporan_gkm` | Laporan tingkat Prodi | bulanan, semester, tahunan, artefak |
| `evaluasi_artefak` | Evaluasi artefak GKM | foto, video, dokumen |

**Status Laporan:**
- draft
- menunggu_review
- approved
- revisi

**Komponen Laporan:**
- `hasil_monitoring_rps`
- `hasil_monitoring_materi`
- `hasil_monitoring_kuisioner`
- `rencana_perbaikan`

**Artefak:**
- Laporan khusus untuk dokumentasi artefak
- Mendukung foto, video, dokumen
- Evaluasi terpisah di tabel `evaluasi_artefak`

### 7. Laporan GJM (3 tabel)

| Tabel | Deskripsi | Fitur Khusus |
|-------|-----------|--------------|
| `laporan_gjm` | Laporan tingkat Institusi | AI-powered, RAG, RAGAS evaluation |
| `laporan_bulanan` | Laporan bulanan terstruktur | Template-based |
| `template_laporan` | Template untuk laporan | Struktur & placeholder |

**Jenis Laporan GJM:**
- bulanan
- triwulan
- semester
- tahunan
- vmts (Visiting Monitoring dan Technical Support)

**AI & RAG Features:**
```
ai_query          → Query ke AI
ai_response       → Response dari AI
rag_sources       → Sources dari RAG retrieval
ai_preview_pdf    → Preview PDF generated
ai_preview_docx   → Preview DOCX generated
```

**RAGAS Evaluation Metrics:**
```
ragas_faithfulness       → Keakuratan terhadap sumber
ragas_answer_relevancy   → Relevansi jawaban
ragas_context_precision  → Presisi konteks
ragas_context_recall     → Recall konteks
```

**PPT Generation:**
```
ppt_file_path    → Path ke file PPT
ppt_status       → Status generasi PPT
```

**OCR Support:**
```
ocr_file_path    → Path file untuk OCR
ocr_text         → Hasil OCR
ocr_status       → Status proses OCR
```

### 8. Reminder & Email (4 tabel)

| Tabel | Deskripsi | Kegunaan |
|-------|-----------|----------|
| `reminder` | Reminder untuk dosen | RPS, materi, kuisioner |
| `jadwal_reminder` | Jadwal otomatis reminder | Recurring reminders |
| `log_email` | Log pengiriman email | Tracking email sent |
| `kirim_laporan_history` | History kirim laporan | Audit trail |

**Jadwal Reminder:**
- Recurrence: once, daily, weekly, monthly
- Target: dosen, mahasiswa, semua
- Template email customizable

**Log Email:**
- Status: success, failed, pending
- Retry mechanism (percobaan_kirim)
- Error logging

### 9. AI & RAG System (4 tabel)

| Tabel | Deskripsi | Technology |
|-------|-----------|------------|
| `document_chunks` | Chunk dokumen untuk RAG | Vector embeddings |
| `embeddings_cache` | Cache embedding vectors | Performance optimization |
| `ai_response_cache` | Cache response AI | Query hash-based |
| `ai_evaluation_tests` | Test evaluasi AI | RAGAS metrics |

**Document Chunks:**
- Text chunking untuk RAG retrieval
- Embedding vectors (pgvector/MongoDB vector)
- Metadata tracking (source_file, source_page)

**AI Response Cache:**
- Query hash untuk uniqueness
- Response time tracking
- Hit count untuk analytics
- Model & temperature tracking

**AI Evaluation:**
- Ground truth comparison
- Multiple RAGAS metrics
- Ambiguity scoring
- Context tracking

### 10. Perwaliaan & KPI (2 tabel)

| Tabel | Deskripsi | Tracking |
|-------|-----------|----------|
| `perwaliaan` | Konseling wali-mahasiswa | Topik, catatan, tindak lanjut |
| `pencapaian_kpi` | Pencapaian KPI Prodi | Target vs Realisasi |

**Perwaliaan:**
- Dosen sebagai wali
- Mahasiswa (user role)
- Dokumentasi konseling

**KPI:**
- Target dan realisasi
- Persentase pencapaian
- Periode evaluasi

### 11. System Tables (3 tabel)

| Tabel | Deskripsi |
|-------|-----------|
| `jobs` | Queue jobs Laravel |
| `failed_jobs` | Failed queue jobs |
| `cache` | Application cache |

## Relasi Utama

### Hierarki Organisasi
```
prodi
  ├── users (prodi_id)
  ├── dosen (prodi_id)
  ├── matakuliah (prodi_id)
  ├── ajaran (prodi_id)
  └── laporan_gkm (prodi_id)
```

### Hierarki Pembelajaran
```
matakuliah
  └── rps (matakuliah_id, ajaran_id, dosen_id)
      └── materi (rps_id)
```

### Hierarki Kuesioner
```
kuisioner (ajaran_id)
  └── pertanyaan_kuisioner (kuisioner_id)
      └── jawaban_kuisioner (pertanyaan_id, user_id)
```

### Hierarki Laporan GJM
```
template_laporan
  ├── laporan_gjm (template_id, ajaran_id)
  ├── laporan_bulanan (template_id)
  └── document_chunks (template_id)
```

## Status & Enum Values

### Status Laporan
- `draft`: Belum selesai
- `menunggu_review`: Submit untuk review
- `approved`: Disetujui
- `revisi`: Perlu perbaikan
- `processing`: Sedang diproses (AI)
- `failed`: Gagal proses
- `completed`: Selesai diproses

### Status RPS
- `draft`: Belum selesai
- `menunggu_review`: Submit untuk review
- `sudah_divalidasi`: Sudah divalidasi
- `revisi`: Perlu perbaikan

### Role User
- `admin`: Administrator sistem
- `kaprodi`: Kepala Program Studi
- `dosen`: Dosen/Pengajar
- `gjm_reviewer`: Reviewer GJM
- `gkm_reviewer`: Reviewer GKM

### Jenis Monitoring
- `rps`: Monitoring RPS
- `materi`: Monitoring materi
- `kuisioner`: Monitoring kuesioner
- `perwaliaan`: Monitoring perwaliaan
- `evaluasi`: Monitoring evaluasi

## Field Penting

### Timestamp Fields (Semua Tabel)
- `created_at`: Waktu dibuat
- `updated_at`: Waktu terakhir diupdate

### Soft Delete
Tidak ada soft delete (no `deleted_at` field), gunakan status untuk inactivation

### Foreign Key Constraints
- Mayoritas menggunakan `cascade` on delete
- Beberapa menggunakan `set null` untuk historical data (reviewed_by, dll)

## Integrasi External

### MongoDB Integration
Beberapa model memiliki alternative MongoDB:
- `KuesionerMongo`: Kuesioner di MongoDB
- `HasilAnalisisMongo`: Hasil analisis di MongoDB
- `AIResponseCacheMongo`: Cache AI response di MongoDB

### Vector Database
- `document_chunks.embedding_vector`: Vector embeddings
- `embeddings_cache.embedding_vector`: Cached embeddings

### External API
- `dosenn`: Data dosen dari API external (PDDIKTI/SIAKAD)

## Performance Considerations

### Indexing
- Primary keys: `id` (bigint, auto-increment)
- Unique keys: email, username, nidn, kode_mk, kode_prodi
- Foreign keys: automatic indexing
- Composite unique: `(dosen_id, matakuliah_id)` di `dosen_matakuliah`

### Caching Strategy
- AI response cache: query hash-based
- Embeddings cache: content hash-based
- Laravel cache table: general application cache

### Snapshots
- `rps_monitoring_snapshots`: Historical RPS data
- `perkuliahan_monitoring_snapshots`: Historical perkuliahan data
- Benefit: Fast reporting tanpa perlu recompute

## File Storage

### File Fields
Format: `string` (path relatif atau absolut)

**Locations:**
- `file_rps`: Storage RPS documents
- `file_materi`: Storage materi documents
- `file_laporan`: Storage laporan documents
- `file_artefak`: Storage artefak (foto/video)
- `foto_profil`: Storage profile photos
- `ai_preview_pdf`, `ai_preview_docx`: AI-generated previews
- `ppt_file_path`: Generated PPT files
- `ocr_file_path`: Files untuk OCR processing

## Security & Audit

### Audit Trail
- `kirim_laporan_history`: Track semua pengiriman laporan
- `log_email`: Track semua email sent
- `ai_evaluation_tests`: Track AI evaluation results

### User Tracking
- `created_by`, `reviewed_by`, `sent_by`: Foreign key to users/dosen
- Timestamp fields untuk audit

## JSON Fields

### Structured JSON Data
- `hasil_analisis`: Hasil analisis kuesioner (statistics, charts data)
- `statistik_summary`: Summary statistik
- `rag_sources`: RAG retrieval sources dengan score
- `metadata`: General metadata storage
- `pilihan_jawaban`: Options untuk pertanyaan multiple choice
- `data_details`: Detail data untuk snapshots
- `sections`, `prompts`: Template structure
- `placeholder_fields`, `contoh_data`: Template configuration

## Migration Notes

### Nullable Foreign Keys
Beberapa FK dibuat nullable untuk flexibility:
- `ajaran_id` di `laporan_gjm`, `laporan_gkm`: Laporan bisa lintas periode
- `prodi_id` di `laporan_gkm`: Laporan bisa institusional
- `kaprodi_id` di `prodi`: Bisa belum ada kaprodi

### Removed Fields
- `prodi_id` removed from `kuesioner_uploads`: Menggunakan `matakuliah.prodi_id` instead
- `prodi_id` removed from `laporan_gkm`, `laporan_bulanan`: Redundant data

### Added Fields via Migrations
Banyak field ditambahkan via migration terpisah:
- AI & RAG fields
- Status fields
- RAGAS metrics
- OCR support
- PPT generation support

## Query Patterns

### Common Queries

**Get Laporan GKM untuk Prodi:**
```sql
SELECT lg.* 
FROM laporan_gkm lg
JOIN dosen d ON lg.dosen_ketua = d.id
WHERE d.prodi_id = ?
AND lg.ajaran_id = ?
```

**Get RPS dengan Materi:**
```sql
SELECT r.*, m.* 
FROM rps r
LEFT JOIN materi m ON r.id = m.rps_id
WHERE r.matakuliah_id = ?
AND r.ajaran_id = ?
```

**Get Monitoring Summary:**
```sql
SELECT 
  jenis_monitoring,
  SUM(total_dosen) as total,
  SUM(dosen_selesai) as selesai,
  AVG(persentase_kepatuhan) as avg_kepatuhan
FROM monitoring
WHERE prodi_id = ?
AND ajaran_id = ?
GROUP BY jenis_monitoring
```

**Get AI Response dari Cache:**
```sql
SELECT response 
FROM ai_response_cache
WHERE query_hash = MD5(?)
AND model_name = ?
AND temperature = ?
AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
```

## Recommendations

### Future Improvements

1. **Partitioning**: Consider partitioning large tables by `ajaran_id` atau `created_at`
2. **Archiving**: Archive old `laporan_gjm`, `laporan_gkm` data
3. **Full-Text Search**: Add full-text index untuk `ai_response`, `query_text`
4. **Vector Index**: Optimize vector search dengan specialized indexes
5. **Sharding**: Consider sharding AI cache tables

### Data Retention

**Recommended Retention:**
- Laporan: Permanent (archive after 5 years)
- Logs: 1 year
- Cache: 30 days
- Snapshots: 2 years
- AI Evaluation: 1 year

## Diagram Files

### Available Formats
- **PlantUML**: `ERD_DATABASE.puml` (source)
- **PNG/SVG**: Generate menggunakan PlantUML compiler
- **PDF**: Export dari PNG/SVG

### Generate Diagram

**Using PlantUML:**
```bash
# Install PlantUML
# On Ubuntu/Debian
apt-get install plantuml

# On macOS
brew install plantuml

# Generate PNG
plantuml ERD_DATABASE.puml

# Generate SVG
plantuml -tsvg ERD_DATABASE.puml

# Generate PDF (via SVG)
plantuml -tsvg ERD_DATABASE.puml
inkscape ERD_DATABASE.svg --export-pdf=ERD_DATABASE.pdf
```

**Online Tools:**
- https://www.planttext.com/
- https://plantuml.com/plantuml

## Kesimpulan

ERD ini menggambarkan sistem SPMI yang kompleks dengan fitur:
- ✅ Multi-level monitoring (GKM & GJM)
- ✅ AI-powered report generation
- ✅ RAG (Retrieval-Augmented Generation)
- ✅ RAGAS evaluation metrics
- ✅ OCR support
- ✅ Automated reminders
- ✅ Comprehensive audit trail
- ✅ Vector embeddings untuk semantic search
- ✅ Caching strategy untuk performance

Total: **43 tabel** dengan relasi yang kompleks namun terstruktur dengan baik.
