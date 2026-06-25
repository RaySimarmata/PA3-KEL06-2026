# TABLE USAGE ANALYSIS REPORT
Generated: 2026-06-13 07:05:56

## 📊 USAGE SUMMARY

### users - ⚠️ NO USAGE FOUND
**Total References:** 0

---

### prodi - ✅ ACTIVE
**Total References:** 16

**controllers:** 6 files
- `app/Http/Controllers/GJM/RecapLaporanController.php`
- `app/Http/Controllers/GKM/DataMasterApiController.php`
- `app/Http/Controllers/GKM/DataMasterController.php`
- `app/Http/Controllers/GKM/LaporanArtefakController.php`
- `app/Http/Controllers/GKM/LaporanKuesioneController.php`
- `app/Http/Controllers/GKM/MonitoringKuesioneController.php`

**models:** 9 files
- `app/Models/Ajaran.php`
- `app/Models/LaporanBulanan.php`
- `app/Models/LaporanGKM.php`
- `app/Models/LogEmail.php`
- `app/Models/Matakuliah.php`
- `app/Models/PerkuliahanMonitoringSnapshot.php`
- `app/Models/RpsMonitoringSnapshot.php`
- `app/Models/TemplateLaporan.php`
- `app/Models/User.php`

**routes:** 1 files
- `routes/web.php`

---

### periode_akademik - ✅ ACTIVE
**Total References:** 11

**controllers:** 9 files
- `app/Http/Controllers/GKM/DashboardController.php`
- `app/Http/Controllers/GKM/DataMasterApiController.php`
- `app/Http/Controllers/GKM/DataMasterController.php`
- `app/Http/Controllers/GKM/LaporanArtefakController.php`
- `app/Http/Controllers/GKM/LaporanKuesioneController.php`
- `app/Http/Controllers/GKM/MonitoringKuesioneController.php`
- `app/Http/Controllers/GKM/MonitoringPerkuliahanController.php`
- `app/Http/Controllers/GKM/MonitoringRPSController.php`
- `app/Http/Controllers/PeriodeAkademikController.php`

**models:** 1 files
- `app/Models/LaporanBulanan.php`

**views:** 1 files
- `resources/views/gkm/laporan-kuesioner/create.blade.php`

---

### dosenn - ✅ ACTIVE
**Total References:** 13

**controllers:** 7 files
- `app/Http/Controllers/GKM/DataMasterApiController.php`
- `app/Http/Controllers/GKM/DataMasterController.php`
- `app/Http/Controllers/GKM/MonitoringKuesioneController.php`
- `app/Http/Controllers/GKM/MonitoringPerkuliahanController.php`
- `app/Http/Controllers/GKM/MonitoringRPSController.php`
- `app/Http/Controllers/GKM/ReminderAgentController.php`
- `app/Http/Controllers/PeriodeAkademikController.php`

**models:** 4 files
- `app/Models/JadwalDosen.php`
- `app/Models/PerkuliahanMonitoringDetail.php`
- `app/Models/PerkuliahanMonitoringSnapshot.php`
- `app/Models/RpsMonitoringSnapshot.php`

**views:** 2 files
- `resources/views/gkm/monitoring-kuesioner/index.blade.php`
- `resources/views/gkm/monitoring-kuesioner/show.blade.php`

---

### jadwal_dosen - ✅ ACTIVE
**Total References:** 4

**controllers:** 4 files
- `app/Http/Controllers/GKM/DashboardController.php`
- `app/Http/Controllers/GKM/DataMasterApiController.php`
- `app/Http/Controllers/GKM/MonitoringPerkuliahanController.php`
- `app/Http/Controllers/GKM/MonitoringRPSController.php`

---

### kuesioner_uploads - ⚠️ NO USAGE FOUND
**Total References:** 0

---

### rps_monitoring_snapshots - ✅ ACTIVE
**Total References:** 3

**controllers:** 3 files
- `app/Http/Controllers/GKM/DashboardController.php`
- `app/Http/Controllers/GKM/LaporanArtefakController.php`
- `app/Http/Controllers/GKM/MonitoringRPSController.php`

---

### perkuliahan_monitoring_snapshots - ✅ ACTIVE
**Total References:** 3

**controllers:** 3 files
- `app/Http/Controllers/GKM/LaporanArtefakController.php`
- `app/Http/Controllers/GKM/MonitoringKuesioneController.php`
- `app/Http/Controllers/GKM/MonitoringPerkuliahanController.php`

---

### laporan_gjm - ✅ ACTIVE
**Total References:** 11

**controllers:** 11 files
- `app/Http/Controllers/API/N8nCallbackController.php`
- `app/Http/Controllers/GJM/BuatLaporanController.php`
- `app/Http/Controllers/GJM/BuatPPTController.php`
- `app/Http/Controllers/GJM/KirimLaporanController.php`
- `app/Http/Controllers/GJM/LaporanGJMController.php`
- `app/Http/Controllers/GJM/LaporanSemesterController.php`
- `app/Http/Controllers/GJM/LaporanTriwulanController.php`
- `app/Http/Controllers/GJM/LaporanVMTSController.php`
- `app/Http/Controllers/GJM/ModelEvaluationController.php`
- `app/Http/Controllers/GJM/OCRUploadController.php`
- `app/Http/Controllers/GJM/TemplateLaporanController.php`

---

### laporan_bulanan - ✅ ACTIVE
**Total References:** 10

**controllers:** 9 files
- `app/Http/Controllers/API/N8nCallbackController.php`
- `app/Http/Controllers/GJM/LaporanSemesterController.php`
- `app/Http/Controllers/GJM/LaporanTriwulanController.php`
- `app/Http/Controllers/GJM/PromptSemesterController.php`
- `app/Http/Controllers/GJM/PromptTriwulanController.php`
- `app/Http/Controllers/GKM/DashboardController.php`
- `app/Http/Controllers/GKM/KirimLaporanController.php`
- `app/Http/Controllers/GKM/LaporanKuesioneController.php`
- `app/Http/Controllers/GKM/LaporanKuesioneControllerExtension.php`

**models:** 1 files
- `app/Models/TemplateLaporan.php`

---

### template_laporan - ✅ ACTIVE
**Total References:** 11

**controllers:** 7 files
- `app/Http/Controllers/GJM/BuatLaporanController.php`
- `app/Http/Controllers/GJM/LaporanSemesterController.php`
- `app/Http/Controllers/GJM/LaporanTriwulanController.php`
- `app/Http/Controllers/GJM/TemplateLaporanController.php`
- `app/Http/Controllers/GKM/DataMasterController.php`
- `app/Http/Controllers/GKM/LaporanArtefakController.php`
- `app/Http/Controllers/GKM/LaporanKuesioneController.php`

**models:** 4 files
- `app/Models/DocumentChunk.php`
- `app/Models/LaporanBulanan.php`
- `app/Models/LaporanGJM.php`
- `app/Models/LaporanGKM.php`

---

### laporan_gkm - ✅ ACTIVE
**Total References:** 7

**controllers:** 5 files
- `app/Http/Controllers/GJM/RecapLaporanController.php`
- `app/Http/Controllers/GJM/ValidasiLaporanController.php`
- `app/Http/Controllers/GKM/DashboardController.php`
- `app/Http/Controllers/GKM/LaporanArtefakController.php`
- `app/Http/Controllers/GKM/PelaporanController.php`

**models:** 2 files
- `app/Models/Ajaran.php`
- `app/Models/Prodi.php`

---

### document_chunks - ✅ ACTIVE
**Total References:** 1

**controllers:** 1 files
- `app/Http/Controllers/GJM/TemplateLaporanController.php`

---

### embeddings_cache - ⚠️ NO USAGE FOUND
**Total References:** 0

---

### jadwal_reminder - ✅ ACTIVE
**Total References:** 1

**controllers:** 1 files
- `app/Http/Controllers/GKM/ReminderAgentController.php`

---

### log_email - ✅ ACTIVE
**Total References:** 4

**controllers:** 3 files
- `app/Http/Controllers/GKM/MonitoringPerkuliahanController.php`
- `app/Http/Controllers/GKM/MonitoringRPSController.php`
- `app/Http/Controllers/GKM/ReminderAgentController.php`

**models:** 1 files
- `app/Models/Reminder.php`

---

### kirim_laporan_history - ✅ ACTIVE
**Total References:** 1

**controllers:** 1 files
- `app/Http/Controllers/GKM/KirimLaporanController.php`

---

### cache - ✅ ACTIVE
**Total References:** 7

**controllers:** 7 files
- `app/Http/Controllers/GJM/DashboardController.php`
- `app/Http/Controllers/GJM/ModelEvaluationController.php`
- `app/Http/Controllers/GKM/DashboardController.php`
- `app/Http/Controllers/GKM/DataMasterApiController.php`
- `app/Http/Controllers/GKM/DataMasterController.php`
- `app/Http/Controllers/GKM/MonitoringPerkuliahanController.php`
- `app/Http/Controllers/GKM/MonitoringRPSController.php`

---

### jobs - ⚠️ NO USAGE FOUND
**Total References:** 0

---

