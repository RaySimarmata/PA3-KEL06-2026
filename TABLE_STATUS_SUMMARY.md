# 📊 TABLE STATUS SUMMARY
## Database Tables - Active vs Deprecated

**Last Updated:** 13 Juni 2026

---

## ✅ ACTIVE TABLES (19)

### Core System (4 tables)

| Table | Status | Usage | Priority |
|-------|--------|-------|----------|
| **users** | ✅ Active | Authentication, role-based access | High |
| **prodi** | ✅ Active | Program studi management | High |
| **periode_akademik** | ✅ Active | Academic period tracking | High |
| **ajaran** | ⚠️ Overlap | Used but overlaps with periode_akademik | Medium |

### External API Data (2 tables)

| Table | Status | Usage | Priority |
|-------|--------|-------|----------|
| **dosenn** | ✅ Active | Dosen data from external API | High |
| **jadwal_dosen** | ✅ Active | Teaching schedule from API | High |

### Monitoring System - GKM (3 tables)

| Table | Status | Usage | Priority |
|-------|--------|-------|----------|
| **rps_monitoring_snapshots** | ✅ Active | RPS status per period | High |
| **perkuliahan_monitoring_snapshots** | ✅ Active | Lecture monitoring | High |
| **kuesioner_uploads** | ✅ Active | Questionnaire OCR & AI analysis | High |

### Reporting System - GJM (4 tables)

| Table | Status | Usage | Priority |
|-------|--------|-------|----------|
| **laporan_gjm** | ✅ Active | GJM reports (triwulan, semester, VMTS, PPT) | High |
| **laporan_gkm** | ✅ Active | GKM artefak reports | Medium |
| **laporan_bulanan** | ✅ Active | Monthly questionnaire reports | High |
| **template_laporan** | ✅ Active | Document templates for reports | High |

### AI & Vector Database (2 tables)

| Table | Status | Usage | Priority |
|-------|--------|-------|----------|
| **document_chunks** | ✅ Active | RAG system - document chunking | High |
| **embeddings_cache** | ✅ Active | Cache for embeddings | Medium |

### Notification System (3 tables)


| Table | Status | Usage | Priority |
|-------|--------|-------|----------|
| **jadwal_reminder** | ✅ Active | Automated reminder scheduling | High |
| **log_email** | ✅ Active | Email tracking & logging | Medium |
| **kirim_laporan_history** | ✅ Active | Report sending history | Medium |

### Support Tables (2 tables)

| Table | Status | Usage | Priority |
|-------|--------|-------|----------|
| **cache** | ✅ Active | Laravel cache system | High |
| **jobs** | ✅ Active | Queue system | High |

---

## ❌ DEPRECATED TABLES (11) - DELETE MODEL FILES

| Table | Migration Drop | Replaced By | Model File | Action |
|-------|---------------|-------------|------------|--------|
| **dosen** | 2026_06_13_000001 | dosenn (API) | ❌ Delete | `rm app/Models/Dosen.php` |
| **perwaliaan** | 2026_06_13_000000 | Feature cancelled | ❌ Delete | `rm app/Models/Perwaliaan.php` |
| **matakuliah** | 2026_06_13_000006 | External API | ⚠️ Evaluate | Keep as cache or remove |
| **rps** | 2026_06_13_000007 | rps_monitoring_snapshots | ⚠️ Evaluate | Minimal usage |
| **monitoring** | 2026_06_13_000005 | Specialized snapshots | ❌ Delete | `rm app/Models/Monitoring.php` |
| **kuisioner** | 2026_06_13_000005 | kuesioner_uploads | ❌ Delete | `rm app/Models/Kuisioner.php` |
| **pertanyaan_kuisioner** | 2026_06_13_000005 | Deprecated | ❌ Delete | `rm app/Models/PertanyaanKuisioner.php` |
| **jawaban_kuisioner** | 2026_06_13_000005 | Deprecated | ❌ Delete | `rm app/Models/JawabanKuisioner.php` |
| **matkul_dosen** | 2026_06_13_000005 | Not used | ❌ Delete | `rm app/Models/MatkulDosen.php` |
| **dosen_matakuliah** | 2026_06_13_000005 | Not used | ❌ Delete | N/A (no model) |
| **jabatan_akademik** | 2026_06_13_000005 | Not used | ❌ Delete | `rm app/Models/JabatanAkademik.php` |
| **ai_response_cache** | 2026_06_13_000008 | MongoDB version | ❌ Delete | `rm app/Models/AIResponseCache.php` |
| **ai_evaluation_tests** | 2026_06_13_000008 | Dropped | ⚠️ Evaluate | Restore or remove feature |
| **ai_evaluation_results** | 2026_06_13_000008 | Dropped | ⚠️ Evaluate | Restore or remove feature |
| **failed_jobs** | 2026_06_13_000005 | Laravel default recreated | N/A | Auto-managed |

---

## ⚠️ PARTIAL IMPLEMENTATION (6) - NEEDS EVALUATION

| Table | Issue | Current Usage | Recommendation |
|-------|-------|---------------|----------------|
| **reminder** | Overlaps with jadwal_reminder | DashboardController count only | Migrate to jadwal_reminder, then drop |
| **ajaran** | Overlaps with periode_akademik | laporan_gjm, laporan_gkm FK | Consolidate to periode_akademik |
| **matakuliah** | Local cache vs API | DataMasterController CRUD | Keep as cache OR migrate to Redis |
| **rps** | Minimal implementation | Dashboard counts only | Full implementation OR use snapshots only |
| **evaluasi_artefak** | Unused relations | PelaporanController minimal | Complete feature OR remove |
| **ai_evaluation_tests/results** | Tables dropped, models exist | ModelEvaluationController | Restore tables OR remove feature |

---

## 📊 STATISTICS

### Overall Summary

```
┌─────────────────────┬───────┬──────────┐
│ Category            │ Count │ Percent  │
├─────────────────────┼───────┼──────────┤
│ Active & Used       │  19   │   53%    │
│ Deprecated (Drop)   │  11   │   30%    │
│ Partial (Evaluate)  │   6   │   17%    │
├─────────────────────┼───────┼──────────┤
│ TOTAL               │  36   │  100%    │
└─────────────────────┴───────┴──────────┘
```

### By Module

```
┌────────────────┬────────┬────────────┬──────────┐
│ Module         │ Active │ Deprecated │ Partial  │
├────────────────┼────────┼────────────┼──────────┤
│ Core System    │   4    │     0      │    1     │
│ GKM Monitoring │   3    │     3      │    2     │
│ GJM Reporting  │   4    │     0      │    0     │
│ AI System      │   2    │     2      │    2     │
│ Notification   │   3    │     0      │    1     │
│ External API   │   2    │     5      │    0     │
│ Support        │   2    │     1      │    0     │
└────────────────┴────────┴────────────┴──────────┘
```

---

## 🗄️ MONGODB COLLECTIONS

| Collection | Status | Usage | Priority |
|------------|--------|-------|----------|
| **ai_response_cache_mongo** | ✅ Active | AI response caching | High |
| **hasil_analisis_mongo** | ✅ Active | Questionnaire analytics | High |
| **kuesioner_mongo** | ❌ Not Used | No implementation | Delete |

---

## 🎯 CLEANUP IMPACT

### Before Cleanup
```
Total Model Files: 36
Total Tables: 28 (MySQL) + 3 (MongoDB)
Active Implementation: 53%
Code Maintenance: High complexity
```

### After Cleanup (Projected)
```
Total Model Files: 19-25 (depending on evaluations)
Total Tables: 19-22 (MySQL) + 2 (MongoDB)
Active Implementation: 100%
Code Maintenance: Low complexity
Codebase Reduction: ~1500 lines
```

### Benefits
✅ Cleaner codebase  
✅ Faster autoloading  
✅ Better performance  
✅ Easier maintenance  
✅ Clear data architecture  
✅ Better developer onboarding  

---

## 🔄 MIGRATION PATHS

### Path 1: Conservative (Recommended for Production)
1. Delete 9 deprecated model files (tables already dropped)
2. Evaluate 6 partial implementations
3. Keep what's used, document what's removed
4. Total time: 1-2 weeks

### Path 2: Aggressive (Good for Refactoring)
1. Delete all deprecated models
2. Remove all partial implementations
3. Consolidate overlapping tables
4. Full system redesign
5. Total time: 3-4 weeks

### Path 3: Minimal (Quick Win)
1. Delete only critical deprecated models (9 files)
2. Document partial implementations
3. Plan future refactoring
4. Total time: 2-3 days

---

## 📋 QUICK ACTION COMMANDS

### Check Current State
```bash
# Count model files
ls -1 app/Models/*.php | wc -l

# Check for deprecated usage
grep -r "use App\\Models\\Dosen" app/
grep -r "use App\\Models\\Monitoring" app/
grep -r "use App\\Models\\Kuisioner" app/

# Check database tables
php artisan db:show
```

### Cleanup Commands
```bash
# Create backup branch
git checkout -b backup-$(date +%Y%m%d)
git add . && git commit -m "Backup before cleanup"

# Create cleanup branch
git checkout -b cleanup-deprecated-models

# Delete deprecated models (9 files)
rm app/Models/Dosen.php
rm app/Models/Perwaliaan.php
rm app/Models/MatkulDosen.php
rm app/Models/JabatanAkademik.php
rm app/Models/Monitoring.php
rm app/Models/Kuisioner.php
rm app/Models/PertanyaanKuisioner.php
rm app/Models/JawabanKuisioner.php
rm app/Models/AIResponseCache.php

# Refresh autoload
composer dump-autoload

# Run tests
php artisan test

# Commit
git add .
git commit -m "Remove deprecated model files"
```

---

## 📞 NEED HELP?

**Documentation:**
- Full Analysis: `DATABASE_ANALYSIS.md`
- ERD Diagrams: `ERD_DIAGRAM.md`
- Action Plan: `CLEANUP_RECOMMENDATIONS.md`

**Questions?**
- Contact: Database Team
- Slack: #database-cleanup
- Email: db-admin@university.edu

---

**Last Review:** 13 Juni 2026  
**Next Review:** After cleanup completion  
**Status:** Ready for implementation
