# 📊 LAPORAN PERBANDINGAN DIAGRAM CLASS

> **Tanggal**: 15 Juni 2026  
> **Tujuan**: Membandingkan diagram manual user dengan implementasi aktual di codebase

---

## 📋 EXECUTIVE SUMMARY

Dokumen ini membandingkan **diagram konseptual user** (menggunakan nama Indonesia) dengan **diagram implementasi aktual** (dari code scanning). Terdapat **perbedaan signifikan** antara keduanya:

- ✅ **Diagram User**: 21 entitas (konseptual, simplified)
- ✅ **Implementasi Aktual**: 40+ models (complete implementation)
- ⚠️ **Gap**: 19+ models tidak ada di diagram user
- 📝 **Naming**: User menggunakan Bahasa Indonesia, code menggunakan English

---

## 🔍 PEMETAAN NAMA (MAPPING)

### ✅ Entitas yang Cocok (Match)

| No | Nama User (ID) | Nama Implementasi (EN) | Status | Catatan |
|----|----------------|------------------------|--------|---------|
| 1  | Pengguna | **User** | ✅ Match | Field `nama_pengguna` = `username` |
| 2  | ProgramStudi | **Prodi** | ✅ Match | Shortened name |
| 3  | Dosen | **Dosen** | ✅ Match | Perfect match |
| 4  | MataKuliah | **Matakuliah** | ✅ Match | Case difference |
| 5  | TahunAjaran | **Ajaran** | ✅ Match | Shortened name |
| 6  | RPS | **RPS** | ✅ Match | Perfect match |
| 7  | Materi | **Materi** | ✅ Match | Perfect match |
| 8  | Monitoring | **Monitoring** | ✅ Match | Perfect match |
| 9  | EvaluasiArtefak | **EvaluasiArtefak** | ✅ Match | Perfect match |
| 10 | Kuisioner | **Kuisioner** | ✅ Match | Perfect match |
| 11 | PertanyaanKuisioner | **PertanyaanKuisioner** | ✅ Match | Perfect match |
| 12 | JawabanKuisioner | **JawabanKuisioner** | ✅ Match | Perfect match |
| 13 | LaporanGKM | **LaporanGKM** | ✅ Match | Perfect match |
| 14 | LaporanGJM | **LaporanGJM** | ✅ Match | Perfect match |
| 15 | LaporanBulanan | **LaporanBulanan** | ✅ Match | Perfect match |
| 16 | TemplateLaporan | **TemplateLaporan** | ✅ Match | Perfect match |
| 17 | SnapshotPemantauanPerkuliahan | **PerkuliahanMonitoringSnapshot** | ✅ Match | Translation |
| 18 | SnapshotPemantauanRPS | **RpsMonitoringSnapshot** | ✅ Match | Translation |
| 19 | JadwalPengingat | **JadwalReminder** | ✅ Match | Translation |
| 20 | Pengingat | **Reminder** | ✅ Match | Translation |
| 21 | LogEmail | **LogEmail** | ✅ Match | Perfect match |

**Total: 21 entitas user = 21 implementasi aktual ✅**

---

## ❌ MODEL YANG TIDAK ADA DI DIAGRAM USER

### 🔴 Critical Models (Komponen Penting)

| No | Nama Model | Fungsi | Mengapa Penting? |
|----|------------|--------|------------------|
| 1  | **KuesioneUpload** | Upload file kuesioner Excel/CSV | ⚠️ Terpisah dari `Kuisioner`, handle raw upload |
| 2  | **DocumentChunk** | RAG vector database chunks | 🔥 Inti sistem AI RAG untuk retrieval |
| 3  | **AIResponseCache** | Cache respons AI (MySQL) | ⚡ Optimasi performa, reduce API cost |
| 4  | **AIResponseCacheMongo** | Cache respons AI (MongoDB) | ⚡ Alternative storage untuk AI cache |
| 5  | **AIEvaluationResult** | Hasil evaluasi model AI (aggregated) | 📊 Monitoring kualitas AI output |
| 6  | **AIEvaluationTest** | Test case evaluasi AI individual | 🧪 Testing AI dengan RAGAS metrics |
| 7  | **EmbeddingsCache** | Cache embeddings vector | ⚡ Avoid re-compute embeddings |

### 🟡 Supporting Models (Pendukung)

| No | Nama Model | Fungsi | Mengapa Penting? |
|----|------------|--------|------------------|
| 8  | **Dosenn** | Data dosen dari API eksternal | 🔗 Integration dengan sistem SIAKAD |
| 9  | **MatkulDosen** | Mapping matakuliah-dosen | 🔗 Data dari API eksternal |
| 10 | **JadwalDosen** | Jadwal mengajar dosen | 📅 Scheduling system |
| 11 | **KirimLaporanHistory** | Riwayat pengiriman email laporan | 📧 Email tracking & audit trail |
| 12 | **PencapaianKPI** | Data pencapaian KPI prodi | 📈 Performance metrics |
| 13 | **PerkuliahanMonitoringDetail** | Detail analytics perkuliahan | 📊 Analytics & reporting |
| 14 | **PeriodeAkademik** | Periode akademik aktif | 🗓️ Academic calendar management |
| 15 | **JabatanAkademik** | Master data jabatan | 📚 Reference data |
| 16 | **Kelas** | Master data kelas | 🏫 Class management |
| 17 | **Perwaliaan** | Data perwalian mahasiswa | 👨‍🏫 Academic advisory |

### 🟢 MongoDB Collections (NoSQL Storage)

| No | Nama Model | Fungsi | Mengapa Penting? |
|----|------------|--------|------------------|
| 18 | **KuesionerMongo** | Storage kuesioner di MongoDB | 💾 Alternative storage (denormalized) |
| 19 | **HasilAnalisisMongo** | Hasil analisis di MongoDB | 💾 Big data analytics storage |

**Total Missing: 19 models ⚠️**

---

## 📊 STATISTIK PERBANDINGAN

```
┌────────────────────────────────────────────────┐
│         DIAGRAM USER vs IMPLEMENTASI           │
├────────────────────────────────────────────────┤
│ Total Entitas User       : 21 models           │
│ Total Implementasi Aktual: 40 models           │
│ Coverage User            : 52.5%               │
│ Missing dari User        : 19 models (47.5%)   │
└────────────────────────────────────────────────┘
```

### Breakdown by Category

| Category | User Diagram | Actual Implementation | Gap |
|----------|--------------|----------------------|-----|
| **Core Domain** | 9 | 9 | 0 ✅ |
| **Academic Management** | 4 | 4 | 0 ✅ |
| **Questionnaire** | 3 | 5 | **+2** ⚠️ |
| **Reporting** | 4 | 4 | 0 ✅ |
| **Monitoring** | 4 | 7 | **+3** ⚠️ |
| **AI/RAG System** | 0 | 7 | **+7** 🔥 |
| **External Integration** | 0 | 3 | **+3** ⚠️ |
| **Supporting/Utils** | 0 | 4 | **+4** ⚠️ |

---

## 🔥 ANALISIS GAP KRITIS

### 1. **AI/RAG System** (TIDAK ADA DI DIAGRAM USER)

Implementasi aktual memiliki **sistem AI/RAG lengkap** yang tidak tercermin di diagram user:

```
📁 AI/RAG Architecture (Missing in User Diagram)
├── DocumentChunk          → Vector storage untuk RAG
├── EmbeddingsCache        → Cache embeddings
├── AIResponseCache        → Cache AI responses (MySQL)
├── AIResponseCacheMongo   → Cache AI responses (MongoDB)
├── AIEvaluationResult     → Aggregate AI metrics
└── AIEvaluationTest       → Individual AI test cases
```

**Impact**: Diagram user tidak menggambarkan komponen teknologi paling penting dari sistem ini.

### 2. **KuesioneUpload** (TERPISAH DARI KUISIONER)

User menganggap `Kuisioner` = 1 entitas, tapi implementasi memiliki:

- **Kuisioner** → Template/definition kuesioner
- **KuesioneUpload** → Instance upload file kuesioner (Excel/CSV)

**Relationship**:
```
KuesioneUpload (raw data) 
    → processed → DocumentChunk (chunked) 
    → embedded → used for RAG
```

### 3. **External Data Integration** (TIDAK ADA DI DIAGRAM USER)

Implementasi terintegrasi dengan sistem eksternal:

- **Dosenn** → Data dosen dari API SIAKAD
- **MatkulDosen** → Mapping dari API
- **JadwalDosen** → Jadwal dari API

### 4. **Advanced Monitoring** (SIMPLIFIED DI DIAGRAM USER)

User diagram: 2 snapshot models  
Implementasi aktual: 4 models dengan detail analytics

```
User:
├── SnapshotPemantauanPerkuliahan
└── SnapshotPemantauanRPS

Aktual:
├── PerkuliahanMonitoringSnapshot   (raw)
├── PerkuliahanMonitoringDetail     (analytics)
├── RpsMonitoringSnapshot           (raw)
└── Monitoring                      (core)
```

---

## 🎯 KESIMPULAN

### ✅ Yang Sudah Sesuai

1. **Inti domain models** (User, Prodi, Dosen, Matakuliah, Ajaran) → **100% match**
2. **Manajemen akademik** (RPS, Materi, Monitoring, Evaluasi) → **100% match**
3. **Sistem kuisioner** (struktur dasar) → **Match**
4. **Sistem laporan** (LaporanGKM, LaporanGJM, LaporanBulanan) → **100% match**
5. **Sistem reminder** (JadwalReminder, Reminder, LogEmail) → **100% match**

### ⚠️ Yang Belum Tercakup di Diagram User

1. **🔥 CRITICAL**: Seluruh sistem AI/RAG (7 models) → **0% coverage**
2. **⚠️ IMPORTANT**: Upload & processing pipeline (KuesioneUpload, DocumentChunk)
3. **⚠️ IMPORTANT**: External integration (Dosenn, MatkulDosen, JadwalDosen)
4. **ℹ️ NICE-TO-HAVE**: Supporting models (KirimLaporanHistory, PencapaianKPI, dll)
5. **💾 STORAGE**: MongoDB collections (KuesionerMongo, HasilAnalisisMongo)

---

## 📝 REKOMENDASI

### 1. **Update Diagram User** (Recommended)

Tambahkan package/layer baru untuk komponen yang missing:

```plantuml
package "Sistem AI & RAG" {
  class DocumentChunk
  class EmbeddingsCache
  class AIResponseCache
  class AIEvaluationResult
}

package "Upload & Processing" {
  class KuesioneUpload
}

package "Integrasi Eksternal" {
  class Dosenn
  class MatkulDosen
  class JadwalDosen
}
```

### 2. **Dokumentasi Tambahan**

- Buat diagram khusus untuk **AI/RAG flow**
- Buat diagram untuk **data processing pipeline**
- Buat diagram untuk **external integration**

### 3. **Maintain Separate Diagrams**

- **Conceptual Diagram** (user's version) → untuk stakeholder non-teknis
- **Implementation Diagram** (generated version) → untuk developer
- **Hybrid Diagram** → gabungan keduanya dengan grouping

---

## 📚 REFERENSI

- **Diagram User (Manual)**: Lihat query user terakhir
- **Diagram Generated**: `docs/diagrams/models-fixed.puml`
- **Documentation**: `docs/CLASS_DIAGRAM_DOCUMENTATION.md`
- **Tools Guide**: `docs/CLASS_DIAGRAM_TOOLS_GUIDE.md`

---

**Generated by**: Kiro Class Diagram Analyzer  
**Date**: 15 Juni 2026  
**Version**: 1.0
