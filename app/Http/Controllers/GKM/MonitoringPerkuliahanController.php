<?php

namespace App\Http\Controllers\GKM;

use App\Http\Controllers\Controller;
use App\Services\ExternalAPIService;
use App\Services\ReminderAgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PDF;

class MonitoringPerkuliahanController extends Controller
{
    protected $apiService;
    protected $reminderService;

    public function __construct(ExternalAPIService $apiService, ReminderAgentService $reminderService)
    {
        $this->apiService = $apiService;
        $this->reminderService = $reminderService;
    }

    /**
     * Display monitoring perkuliahan page
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $prodiId = $user->prodi_id ?? 4; // Default to S1 Teknik Informatika

        // Get filter parameters
        $selectedSemester = $request->input('semester', '');
        $selectedTahunAjaran = $request->input('tahun_ajaran', '');
        $selectedTingkat = $request->input('tingkat', '');

        $filterApplied = !empty($selectedSemester) && !empty($selectedTahunAjaran);

        // Get tahun ajaran list for dropdown
        $tahunAjaranList = $this->apiService->getTahunAjaran();

        $materiTeoriData = [];
        $materiPraktikumData = [];
        $noDataFromAPI = false;

        if ($filterApplied) {
            try {
                // Build cache key
                $cacheKey = "monitoring_perkuliahan_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}_{$selectedTingkat}";

                // Try to get from cache (24 hours)
                $cachedData = Cache::remember($cacheKey, 86400, function () use ($prodiId, $selectedSemester, $selectedTahunAjaran, $selectedTingkat) {
                    return $this->fetchMonitoringData($prodiId, $selectedSemester, $selectedTahunAjaran, $selectedTingkat);
                });

                if ($cachedData) {
                    $materiTeoriData = $cachedData['teori'] ?? [];
                    $materiPraktikumData = $cachedData['praktikum'] ?? [];
                    $noDataFromAPI = $cachedData['no_data'] ?? false;
                }

            } catch (\Exception $e) {
                Log::error('MonitoringPerkuliahan: Failed to fetch data', [
                    'error' => $e->getMessage(),
                    'prodi_id' => $prodiId,
                    'semester' => $selectedSemester,
                    'tahun_ajaran' => $selectedTahunAjaran
                ]);
                
                $noDataFromAPI = true;
            }
        }

        return view('gkm.monitoring-perkuliahan.index', compact(
            'tahunAjaranList',
            'selectedSemester',
            'selectedTahunAjaran',
            'selectedTingkat',
            'filterApplied',
            'noDataFromAPI',
            'materiTeoriData',
            'materiPraktikumData'
        ));
    }

    /**
     * Fetch monitoring data from API
     */
    private function fetchMonitoringData($prodiId, $semester, $tahunAjaran, $tingkat = null)
    {
        // Get matakuliah list
        $matkulData = $this->apiService->getMatkulByProdiSemTa($prodiId, $semester, $tahunAjaran);

        if (empty($matkulData)) {
            return [
                'teori' => [],
                'praktikum' => [],
                'no_data' => true
            ];
        }

        // Filter by tingkat if specified
        if ($tingkat) {
            $matkulData = array_filter($matkulData, function ($mk) use ($tingkat) {
                $mkTingkat = $mk['tingkat'] ?? 0;
                return $mkTingkat == $tingkat;
            });
        }

        // Get dosen list
        $allDosen = $this->apiService->getFilteredDosen();
        $dosenList = array_filter($allDosen, function ($dosen) use ($prodiId) {
            return isset($dosen['prodi_id']) && $dosen['prodi_id'] == $prodiId;
        });

        // Build dosen-matakuliah mapping
        $matkulDosenMap = [];
        foreach ($dosenList as $dosen) {
            $pegawaiId = $dosen['pegawai_id'] ?? null;
            $namaDosen = $dosen['nama'] ?? null;

            if ($pegawaiId && $namaDosen) {
                $jadwalList = $this->apiService->getJadwalByDosen($pegawaiId, $semester, $tahunAjaran);

                if (!empty($jadwalList)) {
                    foreach ($jadwalList as $jadwal) {
                        $kodeMk = $jadwal['kode_mk'] ?? null;
                        if ($kodeMk) {
                            if (!isset($matkulDosenMap[$kodeMk])) {
                                $matkulDosenMap[$kodeMk] = [];
                            }
                            if (!in_array($namaDosen, $matkulDosenMap[$kodeMk])) {
                                $matkulDosenMap[$kodeMk][] = $namaDosen;
                            }
                        }
                    }
                }
            }
        }

        // Separate teori and praktikum
        $materiTeori = [];
        $materiPraktikum = [];

        foreach ($matkulData as $mk) {
            $kodeMk = $mk['kode_mk'] ?? '';
            $namaMk = $mk['nama_matkul'] ?? '';
            $kuliahId = $mk['kuliah_id'] ?? null;
            $jenisMk = strtoupper($mk['jenis_matakuliah'] ?? 'TEORI');

            // Get materi status for 16 weeks
            $materiStatus = [];
            for ($week = 1; $week <= 16; $week++) {
                $materiStatus[$week] = $this->getMateriStatus($kuliahId, $week);
            }

            $mkData = [
                'kode_mk' => $kodeMk,
                'nama_matkul' => $namaMk,
                'kuliah_id' => $kuliahId,
                'dosen_pengampu' => $matkulDosenMap[$kodeMk] ?? [],
                'materi_status' => $materiStatus
            ];

            if (strpos($jenisMk, 'PRAKTIKUM') !== false || strpos($jenisMk, 'PRAKTEK') !== false) {
                $materiPraktikum[] = $mkData;
            } else {
                $materiTeori[] = $mkData;
            }
        }

        return [
            'teori' => $materiTeori,
            'praktikum' => $materiPraktikum,
            'no_data' => false
        ];
    }

    /**
     * Get materi status for specific week
     */
    private function getMateriStatus($kuliahId, $week)
    {
        if (!$kuliahId) {
            return 'not_uploaded';
        }

        try {
            $materiData = $this->apiService->getMateriByKuliahId($kuliahId);

            if (empty($materiData)) {
                return 'not_uploaded';
            }

            // Check if materi for this week exists
            foreach ($materiData as $materi) {
                $materiWeek = $materi['week'] ?? $materi['minggu_ke'] ?? 0;
                if ($materiWeek == $week) {
                    return 'uploaded';
                }
            }

            return 'not_uploaded';

        } catch (\Exception $e) {
            Log::warning('Failed to get materi status', [
                'kuliah_id' => $kuliahId,
                'week' => $week,
                'error' => $e->getMessage()
            ]);
            return 'error';
        }
    }

    /**
     * Clear cache
     */
    public function clearCache(Request $request)
    {
        $user = Auth::user();
        $prodiId = $user->prodi_id ?? 4;

        $semester = $request->input('semester', '');
        $tahunAjaran = $request->input('tahun_ajaran', '');
        $tingkat = $request->input('tingkat', '');

        // Clear specific cache
        $cacheKey = "monitoring_perkuliahan_{$prodiId}_{$semester}_{$tahunAjaran}_{$tingkat}";
        Cache::forget($cacheKey);

        // Also clear all monitoring perkuliahan caches for this prodi
        Cache::flush(); // Or use a more specific pattern if available

        Log::info('MonitoringPerkuliahan: Cache cleared', [
            'prodi_id' => $prodiId,
            'semester' => $semester,
            'tahun_ajaran' => $tahunAjaran
        ]);

        return redirect()->back()->with('success', 'Cache berhasil dibersihkan. Data akan dimuat ulang dari API.');
    }

    /**
     * Show kirim pengingat page (dispatcher)
     */
    public function kirimPengingat()
    {
        return view('gkm.monitoring-perkuliahan.kirim-pengingat');
    }

    /**
     * Show reminder materi page
     */
    public function reminderMateri(Request $request)
    {
        $user = Auth::user();
        $prodiId = $user->prodi_id ?? 4;

        $semester = $request->input('semester', '');
        $tahunAjaran = $request->input('tahun_ajaran', '');

        return view('gkm.monitoring-perkuliahan.materi', compact('semester', 'tahunAjaran'));
    }

    /**
     * Filter reminders by mode
     */
    public function filterRemindersByMode(Request $request)
    {
        $mode = $request->input('mode', 'all');
        $semester = $request->input('semester');
        $tahunAjaran = $request->input('tahun_ajaran');

        $user = Auth::user();
        $prodiId = $user->prodi_id ?? 4;

        try {
            // Get monitoring data
            $data = $this->fetchMonitoringData($prodiId, $semester, $tahunAjaran);
            $materiData = array_merge($data['teori'] ?? [], $data['praktikum'] ?? []);

            // Filter based on mode
            $filtered = [];

            foreach ($materiData as $mk) {
                $needsReminder = false;

                if ($mode === 'all') {
                    $needsReminder = true;
                } elseif ($mode === 'missing') {
                    // Check if any week is missing
                    foreach ($mk['materi_status'] as $week => $status) {
                        if ($status === 'not_uploaded') {
                            $needsReminder = true;
                            break;
                        }
                    }
                } elseif ($mode === 'complete') {
                    // Check if all weeks are uploaded
                    $allUploaded = true;
                    foreach ($mk['materi_status'] as $week => $status) {
                        if ($status !== 'uploaded') {
                            $allUploaded = false;
                            break;
                        }
                    }
                    $needsReminder = $allUploaded;
                }

                if ($needsReminder) {
                    $filtered[] = $mk;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $filtered
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to filter reminders', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memfilter data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate AI message for materi reminder
     */
    public function generateMessageMateri(Request $request)
    {
        try {
            $dosenName = $request->input('dosen_name');
            $matkulName = $request->input('matkul_name');
            $missingWeeks = $request->input('missing_weeks', []);

            $message = $this->reminderService->generateReminderMessage([
                'type' => 'materi_upload',
                'dosen_name' => $dosenName,
                'matkul_name' => $matkulName,
                'missing_weeks' => $missingWeeks
            ]);

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate materi message', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate pesan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send reminder upload materi
     */
    public function kirimReminderUploadMateri(Request $request)
    {
        $request->validate([
            'recipients' => 'required|array',
            'message' => 'required|string'
        ]);

        try {
            $results = $this->reminderService->sendBulkReminders(
                $request->input('recipients'),
                $request->input('message'),
                'materi_upload'
            );

            return response()->json([
                'success' => true,
                'message' => 'Reminder berhasil dikirim',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send materi reminders', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim reminder: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show reminder review soal page
     */
    public function reminderReviewSoal(Request $request)
    {
        return view('gkm.monitoring-perkuliahan.soal');
    }

    /**
     * Generate AI message for review soal
     */
    public function generateMessageSoal(Request $request)
    {
        try {
            $dosenName = $request->input('dosen_name');
            $matkulName = $request->input('matkul_name');

            $message = $this->reminderService->generateReminderMessage([
                'type' => 'review_soal',
                'dosen_name' => $dosenName,
                'matkul_name' => $matkulName
            ]);

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate soal message', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate pesan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send reminder review soal
     */
    public function kirimReminderReviewSoal(Request $request)
    {
        $request->validate([
            'recipients' => 'required|array',
            'message' => 'required|string'
        ]);

        try {
            $results = $this->reminderService->sendBulkReminders(
                $request->input('recipients'),
                $request->input('message'),
                'review_soal'
            );

            return response()->json([
                'success' => true,
                'message' => 'Reminder berhasil dikirim',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send soal reminders', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim reminder: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export monitoring to PDF
     */
    public function exportPdf(Request $request)
    {
        $user = Auth::user();
        $prodiId = $user->prodi_id ?? 4;

        $semester = $request->input('semester', '');
        $tahunAjaran = $request->input('tahun_ajaran', '');
        $tingkat = $request->input('tingkat', '');

        try {
            $data = $this->fetchMonitoringData($prodiId, $semester, $tahunAjaran, $tingkat);

            $pdf = PDF::loadView('gkm.monitoring-perkuliahan.pdf', [
                'materiTeoriData' => $data['teori'] ?? [],
                'materiPraktikumData' => $data['praktikum'] ?? [],
                'semester' => $semester == 1 ? 'Ganjil' : 'Genap',
                'tahunAjaran' => $tahunAjaran,
                'tingkat' => $tingkat
            ]);

            $filename = "monitoring_perkuliahan_{$semester}_{$tahunAjaran}.pdf";

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Failed to export PDF', ['error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Gagal export PDF: ' . $e->getMessage());
        }
    }
}
