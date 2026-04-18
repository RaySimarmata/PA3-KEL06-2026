<?php

namespace App\Http\Controllers\GKM;

use App\Http\Controllers\Controller;
use App\Mail\ReminderPerwalianMail;
use App\Mail\ReminderReviewSoalMail;
use App\Mail\ReminderUploadMateriMail;
use App\Services\ExternalAPIService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\PeriodeAkademik;

class MonitoringPerkuliahanController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get filter values
        $selectedSemester = $request->input('semester', '');
        $selectedTahunAjaran = $request->input('tahun_ajaran', '');
        $selectedTingkat = $request->input('tingkat', '');
        $periode = \App\Models\PeriodeAkademik::where('is_active', true)->first();
        $startDate = $periode ? \Carbon\Carbon::parse($periode->start_date) : null;

        // Check if filter is applied
        $filterApplied = ! empty($selectedSemester) &&
                 ! empty($selectedTahunAjaran);

        // Initialize empty data
        $materiTeori = [];
        $materiPraktikum = [];
        $noDataFromAPI = false;

        // If filter applied, fetch data from API
        if ($filterApplied) {
            try {
                $apiService = new ExternalAPIService;

                // Map prodi_id
                $prodiKode = $user->prodi ? $user->prodi->kode_prodi : 'TRPL';
                $prodiIdMap = [
                    'TRPL' => 4,
                    'TI' => 1,
                    'NM' => 3,
                ];
                $prodiId = $prodiIdMap[$prodiKode] ?? 4;

                // Get matakuliah list
                $matkulData = $apiService->getMatkulByProdiSemTa($prodiId, $selectedSemester, $selectedTahunAjaran);

                if (empty($matkulData)) {
                    $noDataFromAPI = true;
                    Log::info('MonitoringPerkuliahan - No matakuliah data', [
                        'prodi_id' => $prodiId,
                        'prodi_kode' => $prodiKode,
                        'semester' => $selectedSemester,
                        'tahun_ajaran' => $selectedTahunAjaran,
                    ]);
                }

                if (! empty($matkulData)) {
                    // Get dosen list - FILTERED BY PRODI untuk mengurangi beban API
                    $dosenList = Cache::remember("dosen_prodi_{$prodiId}", 600, function () use ($apiService, $prodiId) {
                        $allDosen = $apiService->getFilteredDosen();

                        // Filter dosen by prodi_id
                        return array_filter($allDosen, function ($dosen) use ($prodiId) {
                            return isset($dosen['prodi_id']) && $dosen['prodi_id'] == $prodiId;
                        });
                    });

                    Log::info('MonitoringPerkuliahan - Dosen filtered by prodi', [
                        'prodi_id' => $prodiId,
                        'prodi_kode' => $prodiKode,
                        'total_dosen' => count($dosenList),
                    ]);

                    // Build dosen mapping - HANYA PROSES DOSEN DARI PRODI INI
                    $matkulDosenMap = Cache::remember(
                        "matkul_dosen_map_perkuliahan_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}",
                        600,
                        function () use ($dosenList, $apiService, $selectedSemester, $selectedTahunAjaran) {
                            $map = [];
                            $dosenProcessed = 0;

                            foreach ($dosenList as $dosen) {
                                $pegawaiId = $dosen['pegawai_id'] ?? null;
                                $namaDosen = $dosen['nama'] ?? null;

                                if ($pegawaiId && $namaDosen) {
                                    try {
                                        $jadwalList = Cache::remember(
                                            "jadwal_{$pegawaiId}_{$selectedSemester}_{$selectedTahunAjaran}",
                                            600,
                                            function () use ($apiService, $pegawaiId, $selectedSemester, $selectedTahunAjaran) {
                                                return $apiService->getJadwalByDosen($pegawaiId, $selectedSemester, $selectedTahunAjaran);
                                            }
                                        );

                                        if (! empty($jadwalList)) {
                                            $dosenProcessed++;
                                            foreach ($jadwalList as $jadwal) {
                                                $kodeMk = $jadwal['kode_mk'] ?? null;
                                                if ($kodeMk) {
                                                    if (! isset($map[$kodeMk])) {
                                                        $map[$kodeMk] = [];
                                                    }
                                                    if (! in_array($namaDosen, $map[$kodeMk])) {
                                                        $map[$kodeMk][] = $namaDosen;
                                                    }
                                                }
                                            }
                                        }
                                    } catch (\Exception $e) {
                                        Log::warning("Failed to get jadwal for dosen {$namaDosen}: ".$e->getMessage());

                                        continue;
                                    }
                                }
                            }

                            Log::info('MonitoringPerkuliahan - Mapping completed', [
                                'dosen_with_jadwal' => $dosenProcessed,
                                'matkul_with_dosen' => count($map),
                            ]);

                            return $map;
                        }
                    );

                    // Process each matakuliah
                    foreach ($matkulData as $matkul) {
                        $kuliahId = $matkul['kuliah_id'] ?? null;
                        $kodeMk = $matkul['kode_mk'] ?? '-';
                        $namaMk = $matkul['nama_matkul'] ?? '-';

                        // 🔥 Ambil tingkat dari kode MK (AMAN pakai regex)
                        if (! $kuliahId || strlen($kodeMk) < 5) {
                            continue;
                        }

                        // filter tingkat
                        $tingkatMk = substr($kodeMk, 3, 1);
                        if (! empty($selectedTingkat) && $tingkatMk != $selectedTingkat) {
                            continue;
                        }

                        // Get dosen pengampu
                        $dosenPengampu = '-';
                        if (isset($matkulDosenMap[$kodeMk]) && ! empty($matkulDosenMap[$kodeMk])) {
                            $dosenPengampu = implode(', ', $matkulDosenMap[$kodeMk]);
                        }

                        // Default: 16 minggu (null = belum waktunya / belum ada data)
$weeks = array_fill(0, 16, null);

if ($kuliahId) {
    try {
        $monitoring = $apiService->getMonitoringMateri(
            $kuliahId,
            $selectedTahunAjaran,
            $selectedSemester
        );

        if ($monitoring && isset($monitoring['check_materi']['detail'])) {

            $detailMateri = $monitoring['check_materi']['detail'];
            $now = Carbon::now();

            foreach ($detailMateri as $sesi) {

                // Ambil week number dari "W1-S1"
                if (preg_match('/W(\d+)-S\d+/', $sesi['sesi'], $matches)) {

                    $weekNum = (int) $matches[1];

                    if ($weekNum < 1 || $weekNum > 16) continue;

                    // 🔥 Hitung deadline minggu
                    $deadline = $startDate
                        ->copy()
                        ->addWeeks($weekNum)
                        ->startOfWeek(Carbon::MONDAY)
                        ->setTime(23, 59, 59);

                    // 🔥 Ambil waktu upload
                    $uploadTime = !empty($sesi['created_at_final'])
                        ? Carbon::parse($sesi['created_at_final'])
                        : null;

                    // 🔥 Status upload
                    $isUploaded = !empty($sesi['is_uploaded']);

                    // =========================
                    // 🔥 LOGIKA FINAL
                    // =========================

                    if ($isUploaded && $uploadTime) {

                        if ($uploadTime->lte($deadline)) {
                            // 🟢 Tepat waktu (prioritas tertinggi)
                            $weeks[$weekNum - 1] = 1;
                        } else {
                            // 🟡 Terlambat (jangan override kalau sudah hijau)
                            if ($weeks[$weekNum - 1] !== 1) {
                                $weeks[$weekNum - 1] = 2;
                            }
                        }

                    } else {

                        if ($now->gt($deadline)) {
                            // 🔴 Tidak upload (kalau belum ada status lain)
                            if ($weeks[$weekNum - 1] === null) {
                                $weeks[$weekNum - 1] = 0;
                            }
                        }
                        // kalau belum lewat deadline → tetap null (⚪)
                    }
                }
            }
        }

    } catch (\Exception $e) {
        Log::warning("Failed to get monitoring for matkul {$kodeMk}: " . $e->getMessage());
    }
}
                        // ================= PRAKTIKUM =================
                        $weeksPraktikum = array_fill(0, 16, null);

                        if ($kuliahId) {
                            try {
                                $monitoringPraktikum = $apiService->getMonitoringMateriPraktikum(
                                    $kuliahId,
                                    $selectedTahunAjaran,
                                    $selectedSemester
                                );

                                if ($monitoringPraktikum && isset($monitoringPraktikum['check_praktikum']['detail'])) {
                                    $detailMateri = $monitoringPraktikum['check_praktikum']['detail'];

                                    $weekData = [];

                                    foreach ($detailMateri as $sesi) {
                                        if (preg_match('/W(\d+)-S\d+/', $sesi['sesi'], $matches)) {
                                            $weekNum = (int) $matches[1];

                                            if ($weekNum >= 1 && $weekNum <= 16) {

                                                if (! isset($weekData[$weekNum])) {
                                                    $weekData[$weekNum] = [
                                                        'has_ok' => false,
                                                        'has_partial' => false,
                                                        'has_empty' => false,
                                                    ];
                                                }

                                                $statusTeks = $sesi['status_teks'] ?? 'KOSONG';
                                                $statusFile = $sesi['status_file'] ?? 'KOSONG';

                                                $uploadTime = null; // WAJIB RESET

                                                // Ambil dari daftar_file (prioritas)
                                                if (! empty($sesi['daftar_file'][0]['created_at'])) {
                                                    $uploadTime = Carbon::parse($sesi['daftar_file'][0]['created_at']);
                                                }
                                                // fallback ke waktu_praktikum
                                                elseif (! empty($sesi['waktu_praktikum']['created_at'])) {
                                                    $uploadTime = Carbon::parse($sesi['waktu_praktikum']['created_at']);
                                                }

                                                if (strpos($statusFile, 'OK') !== false && $uploadTime && $startDate) {

                                                    $deadline = $startDate
                                                        ->copy()
                                                        ->addWeeks($weekNum)
                                                        ->startOfWeek(Carbon::MONDAY)
                                                        ->setTime(23, 59, 59);

                                                    if ($uploadTime->lte($deadline)) {
                                                        $weekData[$weekNum]['has_ok'] = true; // ✅ tepat waktu
                                                    } else {
                                                        $weekData[$weekNum]['has_partial'] = true; // ⚠️ terlambat
                                                    }

                                                } else {
                                                    $weekData[$weekNum]['has_empty'] = true; // ❌ tidak upload
                                                }
                                            }
                                        }
                                    }

                                    for ($i = 1; $i <= 16; $i++) {
                                        if (isset($weekData[$i])) {
                                            if ($weekData[$i]['has_empty']) {
                                                $weeksPraktikum[$i - 1] = 0;
                                            } elseif ($weekData[$i]['has_partial']) {
                                                $weeksPraktikum[$i - 1] = 2;
                                            } elseif ($weekData[$i]['has_ok']) {
                                                $weeksPraktikum[$i - 1] = 1;
                                            }
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                Log::warning("Failed to get monitoring praktikum for {$kodeMk}: ".$e->getMessage());
                            }
                        }

                        $mkData = [
                            'kode' => $kodeMk,
                            'nama' => $namaMk,
                            'dosen' => $dosenPengampu,
                            'weeks' => $weeks,
                        ];
                        $mkPraktikumData = [
                            'kode' => $kodeMk,
                            'nama' => $namaMk,
                            'dosen' => $dosenPengampu,
                            'weeks' => $weeksPraktikum,
                        ];

                        // Categorize by type (for now, all go to teori)
                        // You can add logic to separate praktikum based on matkul name or other criteria
                        $materiTeori[] = $mkData;
                        $materiPraktikum[] = $mkPraktikumData;
                    }
                }
            } catch (\Exception $e) {
                Log::error('MonitoringPerkuliahan Index Error', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('gkm.monitoring-perkuliahan.index', [
            'user' => $user,
            'selectedSemester' => $selectedSemester,
            'selectedTahunAjaran' => $selectedTahunAjaran,
            'selectedTingkat' => $selectedTingkat,
            'filterApplied' => $filterApplied,
            'materiTeori' => $materiTeori,
            'materiPraktikum' => $materiPraktikum,
            'noDataFromAPI' => $noDataFromAPI,
        ]);
    }

    public function clearCache(Request $request)
    {
        try {
            Cache::flush();

            return redirect()
                ->route('gkm.monitoring-perkuliahan.index')
                ->with('cache_cleared', true);
        } catch (\Exception $e) {
            Log::error('Failed to clear monitoring perkuliahan cache', [
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('gkm.monitoring-perkuliahan.index')
                ->with('error', 'Gagal menghapus cache. Silakan coba lagi.');
        }
    }

    public function kirimPengingat()
    {
        $user = Auth::user();

        return view('gkm.monitoring-perkuliahan.kirim-pengingat', [
            'user' => $user,
        ]);
    }

    public function reminderPerwalian()
    {
        $user = Auth::user();

        return view('gkm.monitoring-perkuliahan.perwalian', [
            'user' => $user,
        ]);
    }

    public function kirimReminderPerwalian(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'nama_dosen' => 'required|string',
                'message' => 'required|string',
            ]);

            Mail::to($request->email)->send(new ReminderPerwalianMail(
                $request->nama_dosen,
                $request->message
            ));

            return response()->json([
                'success' => true,
                'message' => 'Reminder berhasil dikirim',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send perwalian reminder', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim reminder: '.$e->getMessage(),
            ], 500);
        }
    }

    public function generateMessagePerwalian(Request $request)
    {
        try {
            $namaDosen = $request->input('nama_dosen', 'Bapak/Ibu');

            $message = "Yth. {$namaDosen},\n\n";
            $message .= "Kami mengingatkan untuk segera melakukan perwalian mahasiswa.\n\n";
            $message .= "Terima kasih atas perhatian dan kerjasamanya.\n\n";
            $message .= "Hormat kami,\n";
            $message .= 'Tim GKM';

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate message: '.$e->getMessage(),
            ], 500);
        }
    }

    public function reminderMateri()
    {
        $user = Auth::user();

        return view('gkm.monitoring-perkuliahan.materi', [
            'user' => $user,
        ]);
    }

    public function kirimReminderUploadMateri(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'nama_dosen' => 'required|string',
                'message' => 'required|string',
            ]);

            Mail::to($request->email)->send(new ReminderUploadMateriMail(
                $request->nama_dosen,
                $request->message
            ));

            return response()->json([
                'success' => true,
                'message' => 'Reminder berhasil dikirim',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send materi reminder', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim reminder: '.$e->getMessage(),
            ], 500);
        }
    }

    public function generateMessageMateri(Request $request)
    {
        try {
            $namaDosen = $request->input('nama_dosen', 'Bapak/Ibu');

            $message = "Yth. {$namaDosen},\n\n";
            $message .= "Kami mengingatkan untuk segera upload materi perkuliahan.\n\n";
            $message .= "Terima kasih atas perhatian dan kerjasamanya.\n\n";
            $message .= "Hormat kami,\n";
            $message .= 'Tim GKM';

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate message: '.$e->getMessage(),
            ], 500);
        }
    }

    public function reminderReviewSoal()
    {
        $user = Auth::user();

        return view('gkm.monitoring-perkuliahan.soal', [
            'user' => $user,
        ]);
    }

    public function kirimReminderReviewSoal(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'nama_dosen' => 'required|string',
                'message' => 'required|string',
            ]);

            Mail::to($request->email)->send(new ReminderReviewSoalMail(
                $request->nama_dosen,
                $request->message
            ));

            return response()->json([
                'success' => true,
                'message' => 'Reminder berhasil dikirim',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send soal reminder', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim reminder: '.$e->getMessage(),
            ], 500);
        }
    }

    public function generateMessageSoal(Request $request)
    {
        try {
            $namaDosen = $request->input('nama_dosen', 'Bapak/Ibu');

            $message = "Yth. {$namaDosen},\n\n";
            $message .= "Kami mengingatkan untuk segera melakukan review soal ujian.\n\n";
            $message .= "Terima kasih atas perhatian dan kerjasamanya.\n\n";
            $message .= "Hormat kami,\n";
            $message .= 'Tim GKM';

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate message: '.$e->getMessage(),
            ], 500);
        }
    }
}
