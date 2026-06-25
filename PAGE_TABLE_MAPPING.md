# PAGE-TABLE MAPPING REPORT
Generated: 2026-06-13 07:05:56

## 📄 PAGES AND THEIR DATABASE DEPENDENCIES

### CONTROLLERS

#### 📁 app/Http/Controllers/API/N8nCallbackController.php
**Tables Used:** laporan_gjm, laporan_bulanan

- **laporan_gjm**: GJM reports (Triwulan, Semester, VMTS, PPT)
- **laporan_bulanan**: Monthly GKM reports

---

#### 📁 app/Http/Controllers/GJM/BuatLaporanController.php
**Tables Used:** laporan_gjm, template_laporan

- **laporan_gjm**: GJM reports (Triwulan, Semester, VMTS, PPT)
- **template_laporan**: Report templates and RAG context

---

#### 📁 app/Http/Controllers/GJM/BuatPPTController.php
**Tables Used:** laporan_gjm

- **laporan_gjm**: GJM reports (Triwulan, Semester, VMTS, PPT)

---

#### 📁 app/Http/Controllers/GJM/DashboardController.php
**Tables Used:** cache

- **cache**: Laravel framework cache

---

#### 📁 app/Http/Controllers/GJM/KirimLaporanController.php
**Tables Used:** laporan_gjm

- **laporan_gjm**: GJM reports (Triwulan, Semester, VMTS, PPT)

---

#### 📁 app/Http/Controllers/GJM/LaporanGJMController.php
**Tables Used:** laporan_gjm

- **laporan_gjm**: GJM reports (Triwulan, Semester, VMTS, PPT)

---

#### 📁 app/Http/Controllers/GJM/LaporanSemesterController.php
**Tables Used:** laporan_gjm, laporan_bulanan, template_laporan

- **laporan_gjm**: GJM reports (Triwulan, Semester, VMTS, PPT)
- **laporan_bulanan**: Monthly GKM reports
- **template_laporan**: Report templates and RAG context

---

#### 📁 app/Http/Controllers/GJM/LaporanTriwulanController.php
**Tables Used:** laporan_gjm, laporan_bulanan, template_laporan

- **laporan_gjm**: GJM reports (Triwulan, Semester, VMTS, PPT)
- **laporan_bulanan**: Monthly GKM reports
- **template_laporan**: Report templates and RAG context

---

#### 📁 app/Http/Controllers/GJM/LaporanVMTSController.php
**Tables Used:** laporan_gjm

- **laporan_gjm**: GJM reports (Triwulan, Semester, VMTS, PPT)

---

#### 📁 app/Http/Controllers/GJM/ModelEvaluationController.php
**Tables Used:** laporan_gjm, cache

- **laporan_gjm**: GJM reports (Triwulan, Semester, VMTS, PPT)
- **cache**: Laravel framework cache

---

#### 📁 app/Http/Controllers/GJM/OCRUploadController.php
**Tables Used:** laporan_gjm

- **laporan_gjm**: GJM reports (Triwulan, Semester, VMTS, PPT)

---

#### 📁 app/Http/Controllers/GJM/PromptSemesterController.php
**Tables Used:** laporan_bulanan

- **laporan_bulanan**: Monthly GKM reports

---

#### 📁 app/Http/Controllers/GJM/PromptTriwulanController.php
**Tables Used:** laporan_bulanan

- **laporan_bulanan**: Monthly GKM reports

---

#### 📁 app/Http/Controllers/GJM/RecapLaporanController.php
**Tables Used:** prodi, laporan_gkm

- **prodi**: Program Studi (Study Programs) data
- **laporan_gkm**: GKM artifact reports

---

#### 📁 app/Http/Controllers/GJM/TemplateLaporanController.php
**Tables Used:** laporan_gjm, template_laporan, document_chunks

- **laporan_gjm**: GJM reports (Triwulan, Semester, VMTS, PPT)
- **template_laporan**: Report templates and RAG context
- **document_chunks**: RAG document chunking for AI

---

#### 📁 app/Http/Controllers/GJM/ValidasiLaporanController.php
**Tables Used:** laporan_gkm

- **laporan_gkm**: GKM artifact reports

---

#### 📁 app/Http/Controllers/GKM/DashboardController.php
**Tables Used:** periode_akademik, jadwal_dosen, rps_monitoring_snapshots, laporan_bulanan, laporan_gkm, cache

- **periode_akademik**: Academic periods and active semester
- **jadwal_dosen**: Teaching schedule and course assignments
- **rps_monitoring_snapshots**: RPS compliance monitoring cache
- **laporan_bulanan**: Monthly GKM reports
- **laporan_gkm**: GKM artifact reports
- **cache**: Laravel framework cache

---

#### 📁 app/Http/Controllers/GKM/DataMasterApiController.php
**Tables Used:** prodi, periode_akademik, dosenn, jadwal_dosen, cache

- **prodi**: Program Studi (Study Programs) data
- **periode_akademik**: Academic periods and active semester
- **dosenn**: Lecturer data from external API
- **jadwal_dosen**: Teaching schedule and course assignments
- **cache**: Laravel framework cache

---

#### 📁 app/Http/Controllers/GKM/DataMasterController.php
**Tables Used:** prodi, periode_akademik, dosenn, template_laporan, cache

- **prodi**: Program Studi (Study Programs) data
- **periode_akademik**: Academic periods and active semester
- **dosenn**: Lecturer data from external API
- **template_laporan**: Report templates and RAG context
- **cache**: Laravel framework cache

---

#### 📁 app/Http/Controllers/GKM/KirimLaporanController.php
**Tables Used:** laporan_bulanan, kirim_laporan_history

- **laporan_bulanan**: Monthly GKM reports
- **kirim_laporan_history**: Report distribution history

---

#### 📁 app/Http/Controllers/GKM/LaporanArtefakController.php
**Tables Used:** prodi, periode_akademik, rps_monitoring_snapshots, perkuliahan_monitoring_snapshots, template_laporan, laporan_gkm

- **prodi**: Program Studi (Study Programs) data
- **periode_akademik**: Academic periods and active semester
- **rps_monitoring_snapshots**: RPS compliance monitoring cache
- **perkuliahan_monitoring_snapshots**: Course monitoring cache
- **template_laporan**: Report templates and RAG context
- **laporan_gkm**: GKM artifact reports

---

#### 📁 app/Http/Controllers/GKM/LaporanKuesioneController.php
**Tables Used:** prodi, periode_akademik, laporan_bulanan, template_laporan

- **prodi**: Program Studi (Study Programs) data
- **periode_akademik**: Academic periods and active semester
- **laporan_bulanan**: Monthly GKM reports
- **template_laporan**: Report templates and RAG context

---

#### 📁 app/Http/Controllers/GKM/LaporanKuesioneControllerExtension.php
**Tables Used:** laporan_bulanan

- **laporan_bulanan**: Monthly GKM reports

---

#### 📁 app/Http/Controllers/GKM/MonitoringKuesioneController.php
**Tables Used:** prodi, periode_akademik, dosenn, perkuliahan_monitoring_snapshots

- **prodi**: Program Studi (Study Programs) data
- **periode_akademik**: Academic periods and active semester
- **dosenn**: Lecturer data from external API
- **perkuliahan_monitoring_snapshots**: Course monitoring cache

---

#### 📁 app/Http/Controllers/GKM/MonitoringPerkuliahanController.php
**Tables Used:** periode_akademik, dosenn, jadwal_dosen, perkuliahan_monitoring_snapshots, log_email, cache

- **periode_akademik**: Academic periods and active semester
- **dosenn**: Lecturer data from external API
- **jadwal_dosen**: Teaching schedule and course assignments
- **perkuliahan_monitoring_snapshots**: Course monitoring cache
- **log_email**: Email delivery tracking
- **cache**: Laravel framework cache

---

#### 📁 app/Http/Controllers/GKM/MonitoringRPSController.php
**Tables Used:** periode_akademik, dosenn, jadwal_dosen, rps_monitoring_snapshots, log_email, cache

- **periode_akademik**: Academic periods and active semester
- **dosenn**: Lecturer data from external API
- **jadwal_dosen**: Teaching schedule and course assignments
- **rps_monitoring_snapshots**: RPS compliance monitoring cache
- **log_email**: Email delivery tracking
- **cache**: Laravel framework cache

---

#### 📁 app/Http/Controllers/GKM/PelaporanController.php
**Tables Used:** laporan_gkm

- **laporan_gkm**: GKM artifact reports

---

#### 📁 app/Http/Controllers/GKM/ReminderAgentController.php
**Tables Used:** dosenn, jadwal_reminder, log_email

- **dosenn**: Lecturer data from external API
- **jadwal_reminder**: Scheduled reminder system
- **log_email**: Email delivery tracking

---

#### 📁 app/Http/Controllers/PeriodeAkademikController.php
**Tables Used:** periode_akademik, dosenn

- **periode_akademik**: Academic periods and active semester
- **dosenn**: Lecturer data from external API

---

### MODELS

#### 📁 app/Models/Ajaran.php
**Tables Used:** prodi, laporan_gkm

- **prodi**: Program Studi (Study Programs) data
- **laporan_gkm**: GKM artifact reports

---

#### 📁 app/Models/DocumentChunk.php
**Tables Used:** template_laporan

- **template_laporan**: Report templates and RAG context

---

#### 📁 app/Models/JadwalDosen.php
**Tables Used:** dosenn

- **dosenn**: Lecturer data from external API

---

#### 📁 app/Models/LaporanBulanan.php
**Tables Used:** prodi, periode_akademik, template_laporan

- **prodi**: Program Studi (Study Programs) data
- **periode_akademik**: Academic periods and active semester
- **template_laporan**: Report templates and RAG context

---

#### 📁 app/Models/LaporanGJM.php
**Tables Used:** template_laporan

- **template_laporan**: Report templates and RAG context

---

#### 📁 app/Models/LaporanGKM.php
**Tables Used:** prodi, template_laporan

- **prodi**: Program Studi (Study Programs) data
- **template_laporan**: Report templates and RAG context

---

#### 📁 app/Models/LogEmail.php
**Tables Used:** prodi

- **prodi**: Program Studi (Study Programs) data

---

#### 📁 app/Models/Matakuliah.php
**Tables Used:** prodi

- **prodi**: Program Studi (Study Programs) data

---

#### 📁 app/Models/PerkuliahanMonitoringDetail.php
**Tables Used:** dosenn

- **dosenn**: Lecturer data from external API

---

#### 📁 app/Models/PerkuliahanMonitoringSnapshot.php
**Tables Used:** prodi, dosenn

- **prodi**: Program Studi (Study Programs) data
- **dosenn**: Lecturer data from external API

---

#### 📁 app/Models/Prodi.php
**Tables Used:** laporan_gkm

- **laporan_gkm**: GKM artifact reports

---

#### 📁 app/Models/Reminder.php
**Tables Used:** log_email

- **log_email**: Email delivery tracking

---

#### 📁 app/Models/RpsMonitoringSnapshot.php
**Tables Used:** prodi, dosenn

- **prodi**: Program Studi (Study Programs) data
- **dosenn**: Lecturer data from external API

---

#### 📁 app/Models/TemplateLaporan.php
**Tables Used:** prodi, laporan_bulanan

- **prodi**: Program Studi (Study Programs) data
- **laporan_bulanan**: Monthly GKM reports

---

#### 📁 app/Models/User.php
**Tables Used:** prodi

- **prodi**: Program Studi (Study Programs) data

---

### VIEWS

#### 📁 resources/views/gkm/laporan-kuesioner/create.blade.php
**Tables Used:** periode_akademik

- **periode_akademik**: Academic periods and active semester

---

#### 📁 resources/views/gkm/monitoring-kuesioner/index.blade.php
**Tables Used:** dosenn

- **dosenn**: Lecturer data from external API

---

#### 📁 resources/views/gkm/monitoring-kuesioner/show.blade.php
**Tables Used:** dosenn

- **dosenn**: Lecturer data from external API

---

### ROUTES

#### 📁 routes/web.php
**Tables Used:** prodi

- **prodi**: Program Studi (Study Programs) data

---

