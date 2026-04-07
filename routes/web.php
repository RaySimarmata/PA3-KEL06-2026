<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GKM\DashboardController as GKMDashboardController;
use App\Http\Controllers\GKM\DataMasterController;
use App\Http\Controllers\GKM\MonitoringRPSController;
use App\Http\Controllers\GKM\MonitoringPerkuliahanController;
use App\Http\Controllers\GKM\MonitoringKuesioneController;
use App\Http\Controllers\GKM\LaporanKuesioneController;
use App\Http\Controllers\GKM\PelaporanController;
use App\Http\Controllers\GKM\ReminderAgentController;
use App\Http\Controllers\GKM\KirimLaporanController;
use App\Http\Controllers\GJM\DashboardController as GJMDashboardController;
use App\Http\Controllers\GJM\RecapLaporanController;
use App\Http\Controllers\GJM\ValidasiLaporanController;
use App\Http\Controllers\GJM\LaporanGJMController;
use App\Http\Controllers\GJM\BuatLaporanController;
use App\Http\Controllers\GJM\BuatPPTController;
use App\Http\Controllers\GJM\KirimLaporanController as GJMKirimLaporanController;

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        if (auth()->user()->isGKM()) {
            return redirect()->route('gkm.dashboard');
        } elseif (auth()->user()->isGJM()) {
            return redirect()->route('gjm.dashboard');
        }
        return view('dashboard');
    })->name('dashboard');

    // GKM Routes
    Route::prefix('gkm')->name('gkm.')->group(function () {
        Route::get('/dashboard', [GKMDashboardController::class, 'index'])->name('dashboard');

        // Data Master
        Route::prefix('data-master')->name('data-master.')->group(function () {
            Route::get('/', [DataMasterController::class, 'index'])->name('index');
            Route::get('/penugasan-dosen', [DataMasterController::class, 'penugasanDosen'])->name('penugasan-dosen');
            Route::get('/dosen-pengajar', [DataMasterController::class, 'dosenPengajar'])->name('dosen');
            Route::post('/dosen-pengajar', [DataMasterController::class, 'storeDosen'])->name('dosen.store');
            Route::put('/dosen-pengajar/{id}', [DataMasterController::class, 'updateDosen'])->name('dosen.update');
            Route::delete('/dosen-pengajar/{id}', [DataMasterController::class, 'destroyDosen'])->name('dosen.destroy');
            Route::get('/matakuliah', [DataMasterController::class, 'matakuliah'])->name('matakuliah');
            Route::post('/matakuliah', [DataMasterController::class, 'storeMatakuliah'])->name('matakuliah.store');
            Route::put('/matakuliah/{id}', [DataMasterController::class, 'updateMatakuliah'])->name('matakuliah.update');
            Route::delete('/matakuliah/{id}', [DataMasterController::class, 'destroyMatakuliah'])->name('matakuliah.destroy');
            Route::get('/periode-akademik', [DataMasterController::class, 'periodeAkademik'])->name('periode');
            Route::post('/periode-akademik', [DataMasterController::class, 'storePeriode'])->name('periode.store');
            Route::put('/periode-akademik/{id}', [DataMasterController::class, 'updatePeriode'])->name('periode.update');
            Route::delete('/periode-akademik/{id}', [DataMasterController::class, 'destroyPeriode'])->name('periode.destroy');
            Route::post('/periode-akademik/{id}/activate', [DataMasterController::class, 'activatePeriode'])->name('periode.activate');
            Route::get('/kelas', [DataMasterController::class, 'kelas'])->name('kelas');
            Route::post('/kelas', [DataMasterController::class, 'storeKelas'])->name('kelas.store');
            Route::put('/kelas/{id}', [DataMasterController::class, 'updateKelas'])->name('kelas.update');
            Route::delete('/kelas/{id}', [DataMasterController::class, 'destroyKelas'])->name('kelas.destroy');
            Route::get('/template-laporan', [DataMasterController::class, 'templateLaporan'])->name('template');
            Route::post('/template-laporan', [DataMasterController::class, 'storeTemplate'])->name('template.store');
            Route::get('/template-laporan/{id}/download', [DataMasterController::class, 'downloadTemplate'])->name('template.download');
            Route::delete('/template-laporan/{id}', [DataMasterController::class, 'destroyTemplate'])->name('template.destroy');
        });

        // Monitoring RPS & Materi
        Route::prefix('monitoring-rps')->name('monitoring-rps.')->group(function () {
            Route::get('/', [MonitoringRPSController::class, 'index'])->name('index');
            Route::post('/clear-cache', [MonitoringRPSController::class, 'clearCache'])->name('clear-cache');
            Route::get('/ceklist-rps', [MonitoringRPSController::class, 'ceklistRPS'])->name('ceklist');
            Route::post('/generate-message', [MonitoringRPSController::class, 'generateReminderMessage'])->name('generate-message');
            Route::post('/send-reminder', [MonitoringRPSController::class, 'sendReminder'])->name('send-reminder');
            Route::get('/history-reminder', [MonitoringRPSController::class, 'historyReminder'])->name('history');
        });

        // Monitoring Perkuliahan
        Route::prefix('monitoring-perkuliahan')->name('monitoring-perkuliahan.')->group(function () {
            Route::get('/', [MonitoringPerkuliahanController::class, 'index'])->name('index');
            Route::post('/clear-cache', [MonitoringPerkuliahanController::class, 'clearCache'])->name('clear-cache');
            Route::get('/kirim-pengingat', [MonitoringPerkuliahanController::class, 'kirimPengingat'])->name('kirim-pengingat');
            Route::get('/reminder-perwalian', [MonitoringPerkuliahanController::class, 'reminderPerwalian'])->name('perwalian');
            Route::post('/reminder-perwalian/send', [MonitoringPerkuliahanController::class, 'kirimReminderPerwalian'])->name('perwalian.send');
            Route::post('/reminder-perwalian/generate', [MonitoringPerkuliahanController::class, 'generateMessagePerwalian'])->name('perwalian.generate');
            Route::get('/reminder-materi', [MonitoringPerkuliahanController::class, 'reminderMateri'])->name('materi');
            Route::post('/reminder-materi/send', [MonitoringPerkuliahanController::class, 'kirimReminderUploadMateri'])->name('materi.send');
            Route::post('/reminder-materi/generate', [MonitoringPerkuliahanController::class, 'generateMessageMateri'])->name('materi.generate');
            Route::get('/reminder-review-soal', [MonitoringPerkuliahanController::class, 'reminderReviewSoal'])->name('soal');
            Route::post('/reminder-review-soal/send', [MonitoringPerkuliahanController::class, 'kirimReminderReviewSoal'])->name('soal.send');
            Route::post('/reminder-review-soal/generate', [MonitoringPerkuliahanController::class, 'generateMessageSoal'])->name('soal.generate');
        });

        // Monitoring Kuesioner
        Route::prefix('monitoring-kuesioner')->name('monitoring-kuesioner.')->group(function () {
            Route::get('/', [MonitoringKuesioneController::class, 'index'])->name('index');
            Route::get('/create', [MonitoringKuesioneController::class, 'create'])->name('create');
            Route::post('/store', [MonitoringKuesioneController::class, 'store'])->name('store');
            Route::get('/{id}', [MonitoringKuesioneController::class, 'show'])->name('show');
            Route::delete('/{id}', [MonitoringKuesioneController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/report', [MonitoringKuesioneController::class, 'generateReport'])->name('report');
        });

        // Laporan Kuesioner (NEW - AI Generated Reports)
        Route::prefix('laporan-kuesioner')->name('laporan-kuesioner.')->group(function () {
            // Laporan Management
            Route::get('/', [LaporanKuesioneController::class, 'index'])->name('index');
            Route::get('/create', [LaporanKuesioneController::class, 'create'])->name('create');
            Route::post('/', [LaporanKuesioneController::class, 'store'])->name('store');
            
            // Template Management (MUST BE BEFORE /{id} routes)
            Route::get('/template', [LaporanKuesioneController::class, 'templateIndex'])->name('template.index');
            Route::get('/template/upload', [LaporanKuesioneController::class, 'templateUpload'])->name('template.upload');
            Route::post('/template', [LaporanKuesioneController::class, 'templateStore'])->name('template.store');
            Route::get('/template/{id}/download', [LaporanKuesioneController::class, 'templateDownload'])->name('template.download');
            Route::post('/template/{id}/toggle', [LaporanKuesioneController::class, 'templateToggle'])->name('template.toggle');
            Route::post('/template/{id}/reindex', [LaporanKuesioneController::class, 'templateReindex'])->name('template.reindex');
            Route::delete('/template/{id}', [LaporanKuesioneController::class, 'templateDestroy'])->name('template.destroy');
            
            // Laporan Detail & Actions (MUST BE AFTER /template routes)
            Route::get('/{id}', [LaporanKuesioneController::class, 'show'])->name('show');
            Route::delete('/{id}', [LaporanKuesioneController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/download/{format}', [LaporanKuesioneController::class, 'download'])->name('download');
        });

        // Pelaporan
        Route::prefix('pelaporan')->name('pelaporan.')->group(function () {
            Route::get('/', [PelaporanController::class, 'index'])->name('index');
            Route::get('/artefak', [PelaporanController::class, 'laporanArtefak'])->name('artefak');
            Route::get('/kuisioner', [PelaporanController::class, 'laporanKuisioner'])->name('kuisioner');
            Route::post('/generate', [PelaporanController::class, 'generate'])->name('generate');
        });

        // Reminder Agent
        Route::prefix('reminder-agent')->name('reminder-agent.')->group(function () {
            Route::get('/', [ReminderAgentController::class, 'index'])->name('index');
            Route::get('/jadwal', [ReminderAgentController::class, 'jadwal'])->name('jadwal');
            Route::get('/log-email', [ReminderAgentController::class, 'logEmail'])->name('log');
            Route::post('/jadwal', [ReminderAgentController::class, 'storeJadwal'])->name('jadwal.store');
            Route::get('/jadwal/{id}/edit', [ReminderAgentController::class, 'editJadwal'])->name('jadwal.edit');
            Route::put('/jadwal/{id}', [ReminderAgentController::class, 'updateJadwal'])->name('jadwal.update');
            Route::delete('/jadwal/{id}', [ReminderAgentController::class, 'destroyJadwal'])->name('jadwal.destroy');
        });

        // Kirim Laporan
        Route::prefix('kirim-laporan')->name('kirim-laporan.')->group(function () {
            Route::get('/', [KirimLaporanController::class, 'index'])->name('index');
            Route::post('/generate', [KirimLaporanController::class, 'generateMessage'])->name('generate');
            Route::post('/send', [KirimLaporanController::class, 'send'])->name('send');
        });
    });

    // GJM Routes
    Route::prefix('gjm')->name('gjm.')->group(function () {
        Route::get('/dashboard', [GJMDashboardController::class, 'index'])->name('dashboard');

        // Recap Laporan
        Route::prefix('recap-laporan')->name('recap.')->group(function () {
            Route::get('/', [RecapLaporanController::class, 'index'])->name('index');
            Route::get('/grafik-kepatuhan', [RecapLaporanController::class, 'grafikKepatuhan'])->name('grafik');
            Route::get('/evaluasi', [RecapLaporanController::class, 'evaluasi'])->name('evaluasi');
        });

        // Validasi & Verifikasi
        Route::prefix('validasi-laporan')->name('validasi.')->group(function () {
            Route::get('/', [ValidasiLaporanController::class, 'index'])->name('index');
            Route::get('/{id}', [ValidasiLaporanController::class, 'show'])->name('show');
            Route::post('/{id}/approve', [ValidasiLaporanController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [ValidasiLaporanController::class, 'reject'])->name('reject');
        });

        // Buat PPT (NEW)
        Route::prefix('buat-ppt')->name('buat-ppt.')->group(function () {
            Route::get('/', [BuatPPTController::class, 'index'])->name('index');
            Route::get('/archive', [BuatPPTController::class, 'archive'])->name('archive');
            Route::post('/generate', [BuatPPTController::class, 'generate'])->name('generate');
            Route::get('/{id}/download', [BuatPPTController::class, 'download'])->name('download');
        });

        // Buat Laporan (NEW)
        Route::prefix('buat-laporan')->name('buat-laporan.')->group(function () {
            Route::get('/', [BuatLaporanController::class, 'index'])->name('index');
            Route::post('/store', [BuatLaporanController::class, 'store'])->name('store');
            Route::post('/generate', [BuatLaporanController::class, 'generateReport'])->name('generate');
            Route::get('/{id}', [BuatLaporanController::class, 'show'])->name('show');
            Route::get('/{id}/download-pdf', [BuatLaporanController::class, 'downloadPDF'])->name('download.pdf');
            Route::get('/{id}/download-ppt', [BuatLaporanController::class, 'downloadPPT'])->name('download.ppt');
        });

        // Kirim Laporan
        Route::prefix('kirim-laporan')->name('kirim-laporan.')->group(function () {
            Route::get('/', [GJMKirimLaporanController::class, 'index'])->name('index');
            Route::post('/generate', [GJMKirimLaporanController::class, 'generateMessage'])->name('generate');
            Route::post('/send', [GJMKirimLaporanController::class, 'send'])->name('send');
        });

        // Laporan GJM Fakultas (Arsip)
        Route::prefix('laporan-gjm')->name('laporan.')->group(function () {
            Route::get('/', [LaporanGJMController::class, 'index'])->name('index');
            Route::get('/create', [LaporanGJMController::class, 'create'])->name('create');
            Route::get('/bulanan', [LaporanGJMController::class, 'laporanBulanan'])->name('bulanan');
            Route::get('/tahunan', [LaporanGJMController::class, 'laporanTahunan'])->name('tahunan');
            Route::post('/generate', [LaporanGJMController::class, 'generate'])->name('generate');
        });
    });
});
