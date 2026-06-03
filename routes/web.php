<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GKM\DashboardController as GKMDashboardController;
use App\Http\Controllers\GKM\DataMasterController;
use App\Http\Controllers\GKM\DataMasterApiController;
use App\Http\Controllers\GKM\MonitoringRPSController;
use App\Http\Controllers\GKM\MonitoringPerkuliahanController;
use App\Http\Controllers\GKM\MonitoringKuesioneController;
use App\Http\Controllers\GKM\LaporanKuesioneController;
use App\Http\Controllers\GKM\LaporanArtefakController;
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
use App\Http\Controllers\GJM\TemplateLaporanController;
use App\Http\Controllers\GJM\LaporanSemesterController;
use App\Http\Controllers\GJM\PromptSemesterController;
use App\Http\Controllers\GJM\LaporanTriwulanController;
use App\Http\Controllers\GJM\PromptTriwulanController;
use App\Http\Controllers\GJM\OCRUploadController;
use App\Http\Controllers\GJM\ModelEvaluationController;
use App\Http\Controllers\PeriodeAkademikController;
use App\Models\AIResponseCacheMongo;

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');

Route::get('/test-mongo', function () {

    AIResponseCacheMongo::create([
        'cache_key' => 'test123',
        'prompt_hash' => 'hash123',
        'original_prompt' => 'berapa dosen belum upload rps',
        'context_metadata' => [
            'prodi' => 'IF'
        ],
        'ai_response' => '12 dosen belum upload',
        'ai_provider' => 'groq',
        'ai_model' => 'llama3',
        'usage_count' => 1,
        'last_used_at' => now(),
        'response_length' => 100,
        'similarity_threshold' => 0.85,
    ]);

    return 'MongoDB berhasil';
});

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
        Route::get('/dashboard/analytics', [GKMDashboardController::class, 'analytics'])->name('dashboard.analytics');

        Route::get('/sync-jadwal', function () {
        return app(\App\Http\Controllers\GKM\MonitoringRPSController::class)
            ->syncSemuaJadwal(1, 2020);
    });
        // Data Master
        Route::prefix('data-master')->name('data-master.')->group(function () {

    Route::get('/', [DataMasterController::class, 'index'])->name('index');

    Route::get('/penugasan-dosen', [DataMasterController::class, 'penugasanDosen'])
        ->name('penugasan-dosen');

    Route::get('/dosen-pengajar', [DataMasterController::class, 'dosenPengajar'])
        ->name('dosen');

    Route::post('/dosen-pengajar', [DataMasterController::class, 'storeDosen'])
        ->name('dosen.store');

    Route::put('/dosen-pengajar/{id}', [DataMasterController::class, 'updateDosen'])
        ->name('dosen.update');

    Route::delete('/dosen-pengajar/{id}', [DataMasterController::class, 'destroyDosen'])
        ->name('dosen.destroy');

    /*
    |--------------------------------------------------------------------------
    | MASTER MATAKULIAH
    |--------------------------------------------------------------------------
    */

    Route::get('/matakuliah', [DataMasterApiController::class, 'Dmatakuliah'])
        ->name('matakuliah');

    Route::get(
    '/matakuliah/{kodeMk}/detail',
    [DataMasterApiController::class, 'detailMatakuliah']
)->name('matakuliah.detail');

    Route::post('/matakuliah/dosen/store', [DataMasterApiController::class, 'storeDosenMatkul'])
        ->name('matakuliah.dosen.store');

    Route::delete('/matakuliah/dosen/delete', [DataMasterApiController::class, 'deleteDosenMatkul'])
        ->name('matakuliah.dosen.delete');

    Route::post('/dosen/update-email', [DataMasterApiController::class, 'updateEmail'])
        ->name('dosen.update.email');

    Route::post('/matakuliah', [DataMasterApiController::class, 'storeMatakuliah'])
        ->name('matakuliah.store');

    Route::put('/matakuliah/{id}', [DataMasterApiController::class, 'updateMatakuliah'])
        ->name('matakuliah.update');

    Route::delete('/matakuliah/{id}', [DataMasterApiController::class, 'destroyMatakuliah'])
        ->name('matakuliah.destroy');

    Route::get('/periode-akademik', [DataMasterController::class, 'periodeAkademik'])
        ->name('periode');

    Route::post('/periode-akademik', [DataMasterController::class, 'storePeriode'])
        ->name('periode.store');

    Route::put('/periode-akademik/{id}', [DataMasterController::class, 'updatePeriode'])
        ->name('periode.update');

    Route::delete('/periode-akademik/{id}', [DataMasterController::class, 'destroyPeriode'])
        ->name('periode.destroy');

    Route::post('/periode-akademik/{id}/activate', [DataMasterController::class, 'activatePeriode'])
        ->name('periode.activate');

    Route::get('/kelas', [DataMasterController::class, 'kelas'])
        ->name('kelas');

    Route::post('/kelas', [DataMasterController::class, 'storeKelas'])
        ->name('kelas.store');

    Route::put('/kelas/{id}', [DataMasterController::class, 'updateKelas'])
        ->name('kelas.update');

    Route::delete('/kelas/{id}', [DataMasterController::class, 'destroyKelas'])
        ->name('kelas.destroy');

    Route::get('/template-laporan', [DataMasterController::class, 'templateLaporan'])
        ->name('template');

    Route::post('/template-laporan', [DataMasterController::class, 'storeTemplate'])
        ->name('template.store');

    Route::get('/template-laporan/{id}/download', [DataMasterController::class, 'downloadTemplate'])
        ->name('template.download');

    Route::delete('/template-laporan/{id}', [DataMasterController::class, 'destroyTemplate'])
        ->name('template.destroy');

    Route::get('/periodeA', [PeriodeAkademikController::class, 'index'])
        ->name('periodeA');

    Route::post('/periodeA', [PeriodeAkademikController::class, 'store'])
        ->name('periodeA.store');

    Route::post('/periodeA/{id}/aktifkan', [PeriodeAkademikController::class, 'setActive'])
        ->name('periodeA.aktifkan');

    Route::get('/periode/active', [PeriodeAkademikController::class, 'getActive'])
        ->name('periode.active');
});
        // Monitoring RPS & Materi
        Route::prefix('monitoring-rps')->name('monitoring-rps.')->group(function () {
            Route::get('/', [MonitoringRPSController::class, 'index'])->name('index');
            Route::post('/clear-cache', [MonitoringRPSController::class, 'clearCache'])->name('clear-cache');
            Route::get('/ceklist-rps', [MonitoringRPSController::class, 'ceklistRPS'])->name('ceklist');
            Route::post('/generate-message', [MonitoringRPSController::class, 'generateReminderMessage'])->name('generate-message');
            Route::post('/send-reminder', [MonitoringRPSController::class, 'sendReminder'])->name('send-reminder');
            Route::get('/history-reminder', [MonitoringRPSController::class, 'historyReminder'])->name('history');
            Route::get('/export', [MonitoringRPSController::class, 'exportPdf'])->name('export');
        });

        // Monitoring Perkuliahan
        Route::prefix('monitoring-perkuliahan')->name('monitoring-perkuliahan.')->group(function () {
            Route::get('/', [MonitoringPerkuliahanController::class, 'index'])->name('index');
            Route::post('/clear-cache', [MonitoringPerkuliahanController::class, 'clearCache'])->name('clear-cache');
            Route::get('/kirim-pengingat', [MonitoringPerkuliahanController::class, 'kirimPengingat'])->name('kirim-pengingat');
            Route::get('/reminder-materi', [MonitoringPerkuliahanController::class, 'reminderMateri'])->name('materi');
            Route::post('/reminder-materi/send', [MonitoringPerkuliahanController::class, 'kirimReminderUploadMateri'])->name('materi.send');
            Route::post('/reminder-materi/filter', [MonitoringPerkuliahanController::class, 'filterRemindersByMode'])->name('materi.filter');
            Route::post('/reminder-materi/generate', [MonitoringPerkuliahanController::class, 'generateMessageMateri'])->name('materi.generate');
            Route::get('/reminder-review-soal', [MonitoringPerkuliahanController::class, 'reminderReviewSoal'])->name('soal');
            Route::post('/reminder-review-soal/send', [MonitoringPerkuliahanController::class, 'kirimReminderReviewSoal'])->name('soal.send');
            Route::post('/reminder-review-soal/generate', [MonitoringPerkuliahanController::class, 'generateMessageSoal'])->name('soal.generate');
                Route::get('/export', [MonitoringPerkuliahanController::class, 'exportPdf'])->name('export');
        });

        // Monitoring Kuesioner
        Route::prefix('monitoring-kuesioner')->name('monitoring-kuesioner.')->group(function () {



            Route::get('/', [MonitoringKuesioneController::class, 'index'])->name('index');
             Route::get('/create-api', [MonitoringKuesioneController::class, 'indexApi'])->name('create-api');
    Route::post('/process-api', [MonitoringKuesioneController::class, 'processFromApi'])->name('processFromApi');
    // 🔹 list mata kuliah (filter TA + semester)
    Route::get('/create-api', [MonitoringKuesioneController::class, 'indexApi'])
        ->name('create-api');
        Route::post('/create-api/sync-semester', [MonitoringKuesioneController::class, 'syncSemuaKuesioner'])->name('sync-semester');

    // 🔹 API untuk pencarian matakuliah
    Route::get('/api/search-matkul', [MonitoringKuesioneController::class, 'searchMatkul'])
        ->name('api.search-matkul');
    // 🔹 list kuesioner per mata kuliah
    Route::get('/kuesioner', [MonitoringKuesioneController::class, 'listKuesioner'])
        ->name('listKuesioner');

            Route::get('/create', [MonitoringKuesioneController::class, 'create'])->name('create');
            Route::post('/store', [MonitoringKuesioneController::class, 'store'])->name('store');
            Route::get('/view/{id}', [MonitoringKuesioneController::class, 'show'])->name('show');
            Route::post('/{id}/reprocess', [MonitoringKuesioneController::class, 'reprocess'])->name('reprocess');
            Route::get('/{id}',[MonitoringKuesioneController::class, 'showKuesioner'])->name('showa');
            Route::delete('/{id}', [MonitoringKuesioneController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/report', [MonitoringKuesioneController::class, 'generateReport'])->name('report');
        });

        // Laporan Kuesioner (NEW - AI Generated Reports)
        Route::prefix('laporan-kuesioner')->name('laporan-kuesioner.')->group(function () {
            // Laporan Management
            Route::get('/', [LaporanKuesioneController::class, 'index'])->name('index');
            Route::get('/create', [LaporanKuesioneController::class, 'create'])->name('create');
            Route::post('/', [LaporanKuesioneController::class, 'store'])->name('store');

            // AI Assistant Routes for Generate Laporan Kuesioner Baru
            Route::post('/create-draft', [LaporanKuesioneController::class, 'createDraft'])->name('create-draft');
            Route::post('/ai-prompt', [LaporanKuesioneController::class, 'aiPrompt'])->name('ai-prompt');
            Route::post('/save-preview', [LaporanKuesioneController::class, 'savePreview'])->name('save-preview');
            Route::post('/generate-word', [LaporanKuesioneController::class, 'generateWordDocument'])->name('generate-word');

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

        // Laporan Artefak (NEW - AI Generated Reports for RPS & Materi)
        Route::prefix('laporan-artefak')->name('laporan-artefak.')->group(function () {
            // Laporan Management
            Route::get('/', [LaporanArtefakController::class, 'index'])->name('index');
            Route::get('/create', [LaporanArtefakController::class, 'create'])->name('create');
            Route::post('/', [LaporanArtefakController::class, 'store'])->name('store');

            // AI Assistant
            Route::post('/create-draft', [LaporanArtefakController::class, 'createDraft'])->name('create-draft');
            Route::post('/ai-prompt', [LaporanArtefakController::class, 'aiPrompt'])->name('ai-prompt');
            Route::post('/save-preview', [LaporanArtefakController::class, 'savePreview'])->name('save-preview');
            Route::post('/generate-word', [LaporanArtefakController::class, 'generateWordDocument'])->name('generate-word');

            // Template Management (MUST BE BEFORE /{id} routes)
            Route::get('/template', [LaporanArtefakController::class, 'templateIndex'])->name('template.index');
            Route::get('/template/upload', [LaporanArtefakController::class, 'templateUpload'])->name('template.upload');
            Route::post('/template', [LaporanArtefakController::class, 'templateStore'])->name('template.store');
            Route::get('/template/{id}/download', [LaporanArtefakController::class, 'templateDownload'])->name('template.download');
            Route::post('/template/{id}/toggle', [LaporanArtefakController::class, 'templateToggle'])->name('template.toggle');
            Route::post('/template/{id}/reindex', [LaporanArtefakController::class, 'templateReindex'])->name('template.reindex');
            Route::delete('/template/{id}', [LaporanArtefakController::class, 'templateDestroy'])->name('template.destroy');

            // Laporan Detail & Actions (MUST BE AFTER /template routes)
            Route::get('/{id}', [LaporanArtefakController::class, 'show'])->name('show');
            Route::delete('/{id}', [LaporanArtefakController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/download/{format}', [LaporanArtefakController::class, 'download'])->name('download');
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
        Route::post('/analisis', [GjmDashboardController::class, 'analisis'])->name('analisis');
        Route::post('/clear-cache', [GJMDashboardController::class, 'clearCache'])->name('clear-cache');

        // Test AI endpoint
        Route::post('/test-ai', function(Request $request) {
            try {
                $aiService = app(\App\Services\ClaudeAIService::class);

                $response = $aiService->ask(
                    "Anda adalah asisten AI untuk sistem laporan akademik.",
                    "Test: " . ($request->input('message', 'Hello'))
                );

                return response()->json([
                    'success' => true,
                    'response' => $response,
                    'model' => $aiService->getModelInfo()
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }
        })->name('test-ai');

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
            Route::delete('/{id}/delete', [BuatPPTController::class, 'delete'])->name('delete');
        });

        // Buat Laporan (Simplified - No AI Agent)
        Route::prefix('buat-laporan')->name('buat-laporan.')->group(function () {
            Route::get('/', [BuatLaporanController::class, 'index'])->name('index');

            // Triwulan
            Route::get('/triwulan', [LaporanTriwulanController::class, 'index'])->name('triwulan.index');
            Route::get('/triwulan/create', [LaporanTriwulanController::class, 'create'])->name('triwulan.create');
            Route::post('/triwulan/create-draft', [LaporanTriwulanController::class, 'createDraft'])->name('triwulan.create-draft');
            Route::post('/triwulan', [LaporanTriwulanController::class, 'store'])->name('triwulan.store');
            Route::get('/triwulan/{id}', [LaporanTriwulanController::class, 'show'])->name('triwulan.show');
            Route::get('/triwulan/{id}/download/{format}', [LaporanTriwulanController::class, 'download'])->name('triwulan.download');
            Route::delete('/triwulan/{id}', [LaporanTriwulanController::class, 'destroy'])->name('triwulan.destroy');
            Route::post('/triwulan/ai-prompt', [LaporanTriwulanController::class, 'aiPrompt'])->name('triwulan.ai-prompt');
            Route::post('/triwulan/save-preview', [LaporanTriwulanController::class, 'savePreview'])->name('triwulan.save-preview');
            Route::post('/triwulan/save-images', [LaporanTriwulanController::class, 'saveUploadedImages'])->name('triwulan.save-images');
            Route::post('/triwulan/prompt', [PromptTriwulanController::class, 'chat'])->name('triwulan.prompt');
            Route::post('/triwulan/read-file', [PromptTriwulanController::class, 'readFile'])->name('triwulan.read-file');

            // Semester
            Route::get('/semester', [LaporanSemesterController::class, 'index'])->name('semester.index');
            Route::get('/semester/create', [LaporanSemesterController::class, 'create'])->name('semester.create');
            Route::post('/semester', [LaporanSemesterController::class, 'store'])->name('semester.store');
            Route::get('/semester/{id}', [LaporanSemesterController::class, 'show'])->name('semester.show');
            Route::get('/semester/{id}/download/{format}', [LaporanSemesterController::class, 'download'])->name('semester.download');
            Route::delete('/semester/{id}', [LaporanSemesterController::class, 'destroy'])->name('semester.destroy');
            Route::post('/semester/create-draft', [LaporanSemesterController::class, 'createDraft'])->name('semester.create-draft');
            Route::post('/semester/ai-prompt', [LaporanSemesterController::class, 'aiPrompt'])->name('semester.ai-prompt');
            Route::post('/semester/save-preview', [LaporanSemesterController::class, 'savePreview'])->name('semester.save-preview');
            Route::post('/semester/prompt', [PromptSemesterController::class, 'chat'])->name('semester.prompt');
            Route::post('/semester/read-file', [PromptSemesterController::class, 'readFile'])->name('semester.read-file');
            Route::get('/semester/diagnostic', [PromptSemesterController::class, 'diagnostic'])->name('semester.diagnostic');

            // VMTS
            Route::get('/vmts', [\App\Http\Controllers\GJM\LaporanVMTSController::class, 'index'])->name('vmts.index');
            Route::get('/vmts/create', [\App\Http\Controllers\GJM\LaporanVMTSController::class, 'create'])->name('vmts.create');
            Route::post('/vmts', [\App\Http\Controllers\GJM\LaporanVMTSController::class, 'store'])->name('vmts.store');
            Route::get('/vmts/{id}', [\App\Http\Controllers\GJM\LaporanVMTSController::class, 'show'])->name('vmts.show');
            Route::get('/vmts/{id}/download/{format}', [\App\Http\Controllers\GJM\LaporanVMTSController::class, 'download'])->name('vmts.download');
            Route::delete('/vmts/{id}', [\App\Http\Controllers\GJM\LaporanVMTSController::class, 'destroy'])->name('vmts.destroy');
            Route::post('/vmts/create-draft', [\App\Http\Controllers\GJM\LaporanVMTSController::class, 'createDraft'])->name('vmts.create-draft');
            Route::post('/vmts/ai-prompt', [\App\Http\Controllers\GJM\LaporanVMTSController::class, 'aiPrompt'])->name('vmts.ai-prompt');
            Route::post('/vmts/save-preview', [\App\Http\Controllers\GJM\LaporanVMTSController::class, 'savePreview'])->name('vmts.save-preview');

            // Download
            Route::get('/download/{id}', [LaporanGJMController::class, 'download'])->name('download.pdf');


            // OCR Upload & Enhanced AI Integration (NEW)
            Route::post('/ocr/upload', [OCRUploadController::class, 'uploadImages'])->name('ocr.upload');
            Route::post('/ocr/enhanced-preview', [OCRUploadController::class, 'generateEnhancedPreview'])->name('ocr.enhanced-preview');
            Route::get('/ocr/laporan-stats', [OCRUploadController::class, 'getLaporanStats'])->name('ocr.laporan-stats');
            Route::delete('/ocr/clear-data', [OCRUploadController::class, 'clearLaporanData'])->name('ocr.clear-data');
            Route::get('/ocr/stats', [OCRUploadController::class, 'getOCRStats'])->name('ocr.stats');
            Route::get('/ocr/usage', [OCRUploadController::class, 'getUsageStats'])->name('ocr.usage');

            // Legacy route for backward compatibility
            Route::post('/store', [BuatLaporanController::class, 'store'])->name('store');
            Route::get('/{id}', [BuatLaporanController::class, 'show'])->name('show');
        });

        // Template Laporan Management
        Route::prefix('template-laporan')->name('template-laporan.')->group(function () {
            // Triwulan Templates
            Route::get('/triwulan', [TemplateLaporanController::class, 'indexTriwulan'])->name('triwulan.index');
            Route::get('/triwulan/upload', [TemplateLaporanController::class, 'uploadTriwulan'])->name('triwulan.upload');
            Route::post('/triwulan', [TemplateLaporanController::class, 'storeTriwulan'])->name('triwulan.store');

            // Semester Templates
            Route::get('/semester', [TemplateLaporanController::class, 'indexSemester'])->name('semester.index');
            Route::get('/semester/upload', [TemplateLaporanController::class, 'uploadSemester'])->name('semester.upload');
            Route::post('/semester', [TemplateLaporanController::class, 'storeSemester'])->name('semester.store');

            // Common Actions
            Route::get('/{id}/download', [TemplateLaporanController::class, 'download'])->name('download');
            Route::post('/{id}/toggle/{type}', [TemplateLaporanController::class, 'toggle'])->name('toggle');
            Route::post('/{id}/reindex/{type}', [TemplateLaporanController::class, 'reindex'])->name('reindex');
            Route::delete('/{id}/{type}', [TemplateLaporanController::class, 'destroy'])->name('destroy');
        });

        // Kirim Laporan
        Route::prefix('kirim-laporan')->name('kirim-laporan.')->group(function () {
            Route::get('/', [GJMKirimLaporanController::class, 'index'])->name('index');
            Route::post('/generate', [GJMKirimLaporanController::class, 'generateMessage'])->name('generate');
            Route::post('/send', [GJMKirimLaporanController::class, 'send'])->name('send');
        });

        // Model Evaluation (AI Assistant Evaluation)
        Route::prefix('model-evaluation')->name('model-evaluation.')->group(function () {
            Route::get('/', [ModelEvaluationController::class, 'index'])->name('index');
            Route::get('/get-data', [ModelEvaluationController::class, 'getData'])->name('get-data');
            Route::get('/download-report', [ModelEvaluationController::class, 'downloadReport'])->name('download-report');
        });
        // Laporan GJM Fakultas (Arsip)
        Route::prefix('laporan-gjm')->name('laporan-gjm.')->group(function () {
            Route::get('/', [LaporanGJMController::class, 'index'])->name('index');
            Route::get('/create', [LaporanGJMController::class, 'create'])->name('create');
            Route::get('/bulanan', [LaporanGJMController::class, 'laporanBulanan'])->name('bulanan');
            Route::get('/tahunan', [LaporanGJMController::class, 'laporanTahunan'])->name('tahunan');
            Route::post('/generate', [LaporanGJMController::class, 'generate'])->name('generate');
            Route::get('/{id}/detail', [LaporanGJMController::class, 'detail'])->name('detail');
            Route::get('/{id}/download', [LaporanGJMController::class, 'download'])->name('download');
            Route::get('/{id}', [LaporanGJMController::class, 'show'])->name('show');
            Route::delete('/{id}', [LaporanGJMController::class, 'destroy'])->name('destroy');
        });
    });
});
