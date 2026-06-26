# DB usage by role: GKM & GJM

Summary: this document maps database tables to where they're used for the GKM and GJM roles (models, controllers, migrations, views). Use it to identify unused tables and places to add guards before dropping tables.

## Shared / common tables
- `users` — auth, used everywhere (controllers, routes)
- `prodi` — `app/Models/Prodi.php`, used by many GKM/GJM controllers
- `ajaran` — `app/Models/Ajaran.php` (GKM/GJM reports)
- `periode_akademik` — used by dashboards and report flows
- `template_laporan` — templates for both roles
- `laporan_gkm`, `laporan_bulanan`, `laporan_gjm` — primary report tables used by GKM and GJM controllers and views
- `ai_response_cache` (Mongo via `AIResponseCacheMongo`) — used by GJM model-evaluation and AI services (note: also an SQL `ai_response_cache` model existed earlier)

## Tables heavily used by GKM (controllers under `app/Http/Controllers/GKM`)
- `rps` — `app/Models/RPS.php`, `GKM/MonitoringRPSController`, `DashboardController`
- `matakuliah` — `app/Models/Matakuliah.php`, referenced by DataMaster and monitoring controllers
- `dosen` / `dosenn` — `app/Models/Dosen.php`, `Dosenn` snapshot model; used in many GKM flows
- `perkuliahan_monitoring_details` / `perkuliahan_monitoring_snapshots` — monitoring and dashboard
- `kuisioner`, `pertanyaan_kuisioner`, `jawaban_kuisioner` — kuesioner flows and LaporanKuesioneController
- `laporan_bulanan` — GKM kuesioner/report listing
- `evaluasi_artefak` — `app/Models/EvaluasiArtefak.php`, `GKM/PelaporanController::laporanArtefak`
- `reminder`, `jadwal_reminder`, `log_email` — reminder/email features
- `jadwal_dosen`, `jadwal_dosen` snapshots — used for counting dosen/mahasiswa in Dashboard
- `matkul_dosen`, `dosen_matakuliah` — relation tables used by DataMaster/seeders

## Tables heavily used by GJM (controllers under `app/Http/Controllers/GJM`)
- `laporan_gjm` — `app/Models/LaporanGJM.php`, core for GJM controllers (`LaporanGJMController`, `BuatLaporanController`)
- `ajaran`, `template_laporan`, `prodi`, `laporan_gkm` (used as context/fallback)
- `ai_evaluation_tests`, `ai_evaluation_results` — `app/Models/AIEvaluationTest`, `AIEvaluationResult` used by `ModelEvaluationController` (these were dropped via migration earlier)
- `ai_response_cache` (Mongo) — primary source for model-evaluation metrics (via `AIResponseCacheMongo`)
- `embeddings_cache`, `document_chunks` — RAG / embedding subsystems used by AI flows

## AI / cache specifics
- There is both a MySQL `ai_response_cache` migration/model and a Mongo-backed `AIResponseCacheMongo`. The runtime evaluation code (GJM ModelEvaluationController and AI services) use the Mongo model.
- `ai_evaluation_tests` and `ai_evaluation_results` were referenced by `GJM/ModelEvaluationController` and related views — removing these requires updating `ModelEvaluationController` to rely solely on Mongo or to add guards.

## Tables mentioned in ERD but rarely referenced in controllers (candidates for audit)
- `pencapaian_kpi`, `perwaliaan`, `kelas`, `kirim_laporan_history` — present in ERD and migrations; some have drop migrations and may not be used by current controllers. Verify by grepping codebase for model usage.

## Recommendations / next steps
1. Run a repo-wide grep for each table name to find all code references (models, controllers, views, docs). Use that list to decide per-table: keep, guard, or remove.
2. For any table you plan to drop: add `Schema::hasTable()` guards in controllers or wrap calls in try/catch to avoid runtime errors before applying migration.
3. Decide canonical storage for `ai_response_cache`: keep Mongo as primary (preferred) and remove MySQL model/migration only after code cleanup.
4. For `ai_evaluation_tests` / `ai_evaluation_results`: either restore tables (rollback) or refactor `ModelEvaluationController` to handle absence (guarding + fallbacks to Mongo or `laporan_gjm`).
5. Produce a per-table CSV or `docs/db-usage-by-role.md` expanded section listing exact file paths referencing each table (I can generate this automatically).

---

If you want, I can now (A) generate a per-table reference list (exact files & line snippets) for all tables used by GKM and GJM, or (B) start adding `Schema::hasTable` guards in controllers that currently access the AI/tables you want removed. Which do you prefer?
