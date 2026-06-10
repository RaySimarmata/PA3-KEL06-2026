<?php

namespace App\Http\Controllers\GKM;

use App\Http\Controllers\Controller;
use App\Mail\ReminderReviewSoalMail;
use App\Mail\ReminderUploadMateriMail;
use App\Services\ExternalAPIService;
use App\Models\PerkuliahanMonitoringSnapshot;
use App\Models\PerkuliahanMonitoringDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\PeriodeAkademik;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\JadwalDosen;
use App\Models\Dosenn;
use App\Models\LogEmail;
use App\Services\WhatsAppService;
use Illuminate\Validation\ValidationException;

class MonitoringPerkuliahanController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Generate dynamic tahun ajaran list based on current year
     * Auto-updates every 5 years
     */
    private function generateDynamicTahunAjaran()
    {
        $currentYear = (int) date('Y');
        $baseYear = floor($currentYear / 5) * 5;
        
        $tahunList = [];
        for ($i = 0; $i <= 5; $i++) {
            $year = $baseYear + $i;
            $tahunList[] = [
                'id_thn_ajaran' => (string) $year,
                'nm_thn_ajaran' => (string) $year
            ];
        }
        
        Log::info('MonitoringPerkuliahan - Generated dynamic tahun ajaran', [
            'current_year' => $currentYear,
            'base_year' => $baseYear,
            'range' => $baseYear . ' - ' . ($baseYear + 5),
            'years_generated' => array_column($tahunList, 'id_thn_ajaran')
        ]);
        
        return $tahunList;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        // Generate dynamic tahun ajaran list
        $tahunAjaranList = $this->generateDynamicTahunAjaran();

        // Get filter values
        $periodeAktif = PeriodeAkademik::getActive();

$selectedSemester = $request->input(
    'semester',
    $periodeAktif?->semester
);

$selectedTahunAjaran = $request->input(
    'tahun_ajaran',
    $periodeAktif?->tahun_ajaran
);
        $selectedTingkat = $request->input('tingkat', '');
        $periode = \App\Models\PeriodeAkademik::where('is_active', true)->first();
        $startDate = $periode ? \Carbon\Carbon::parse($periode->start_date) : null;
        
        // Check if filter is applied
        $filterApplied = !empty($selectedSemester)
              && !empty($selectedTahunAjaran);
        
        // Fallback: if no active periode, use a default start date based on semester and tahun ajaran
        if (!$startDate && $filterApplied) {
            // For semester 1 (Ganjil), start in August
            // For semester 2 (Genap), start in February
            $month = $selectedSemester == 1 ? 8 : 2;
            $startDate = \Carbon\Carbon::create($selectedTahunAjaran, $month, 1);
        }

        // Initialize empty data
        $materiTeori = [];
        $materiPraktikum = [];
        $noDataFromAPI = false;

        // If filter applied, fetch data from API with timeout protection
        if ($filterApplied) {
            try {
                // Set execution time limit to prevent timeout
                set_time_limit(120); // 2 minutes max
                
                $apiService = new ExternalAPIService;

                // Map prodi_id
                $prodiKode = $user->prodi ? $user->prodi->kode_prodi : 'TRPL';
                $prodiIdMap = [
                    'TRPL' => 4,
                    'TI' => 1,
                    'NM' => 3,
                ];
                $prodiId = $prodiIdMap[$prodiKode] ?? 4;

                // Get matakuliah list with cache
                $matkulData = Cache::remember(
                    "matkul_monitoring_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}",
                    1800, // 30 minutes cache
                    function () use ($apiService, $prodiId, $selectedSemester, $selectedTahunAjaran) {
                        return $apiService->getMatkulByProdiSemTa($prodiId, $selectedSemester, $selectedTahunAjaran);
                    }
                );

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
                    // Limit data processing to prevent timeout
                    $maxMatkul = 100; // Increase limit untuk lebih banyak matkul
                    $processedCount = 0;
                    
                    // 🔥 STRATEGY 1: Coba ambil dari Database dulu (lebih cepat dan reliable)
                    $matkulDosenMap = [];
                    
                    Log::info('MonitoringPerkuliahan - Mulai mapping dari Database', [
                        'semester' => $selectedSemester,
                        'tahun_ajaran' => $selectedTahunAjaran,
                    ]);

                    // Ambil data dosen dari database JadwalDosen
                    $jadwalFromDB = JadwalDosen::with('dosen')
                        ->where(function ($q) use ($selectedSemester) {
                            $q->where('semester', $selectedSemester);
                            // fallback semester
                            if ($selectedSemester == '1') {
                                $q->orWhere('semester', 'Ganjil');
                            }
                            if ($selectedSemester == '2') {
                                $q->orWhere('semester', 'Genap');
                            }
                        })
                        ->where(function ($q) use ($selectedTahunAjaran) {
                            $q->where('tahun_ajaran', $selectedTahunAjaran)
                              ->orWhere('tahun_ajaran', 'LIKE', $selectedTahunAjaran . '%');
                        })
                        ->get();

                    Log::info('MonitoringPerkuliahan - Jadwal dari DB', [
                        'count' => $jadwalFromDB->count()
                    ]);

                    foreach ($jadwalFromDB as $jadwal) {
                        $kodeMk = trim($jadwal->kode_mk ?? '');
                        if (!$kodeMk || !$jadwal->dosen) continue;

                        if (!isset($matkulDosenMap[$kodeMk])) {
                            $matkulDosenMap[$kodeMk] = [];
                        }

                        // Hindari duplicate
                        $exists = collect($matkulDosenMap[$kodeMk])
                            ->contains(fn($d) => $d['pegawai_id'] == $jadwal->pegawai_id);

                        if (!$exists) {
                            $matkulDosenMap[$kodeMk][] = [
                                'pegawai_id' => $jadwal->pegawai_id,
                                'nama' => $jadwal->dosen->nama ?? '-',
                            ];
                        }
                    }

                    Log::info('MonitoringPerkuliahan - Mapping dari DB selesai', [
                        'matkul_with_dosen' => count($matkulDosenMap),
                        'sample' => array_slice($matkulDosenMap, 0, 3)
                    ]);

                    // 🔥 STRATEGY 2: Jika masih kosong, fallback ke API
                    if (empty($matkulDosenMap)) {
                        Log::warning('MonitoringPerkuliahan - Database kosong, fallback ke API');
                        
                        // Get dosen list dari API
                        $dosenList = Cache::remember("dosen_prodi_{$prodiId}", 900, function () use ($apiService) {
                            return $apiService->getFilteredDosen();
                        });

                        Log::info('MonitoringPerkuliahan - Dosen dari API', [
                            'total_dosen' => count($dosenList),
                        ]);

                        // Build dosen mapping - HANYA PROSES DOSEN DARI PRODI INI
                        $matkulDosenMap = Cache::remember(
                            "matkul_dosen_map_perkuliahan_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}",
                            1800,
                            function () use ($selectedSemester, $selectedTahunAjaran) {

                                $map = [];

                                $jadwalList = JadwalDosen::with('dosen')
                                    ->where(function ($q) use ($selectedSemester) {

                                        $q->where('semester', $selectedSemester);

                                        if ($selectedSemester == '1') {
                                            $q->orWhere('semester', 'Ganjil');
                                        }

                                        if ($selectedSemester == '2') {
                                            $q->orWhere('semester', 'Genap');
                                        }
                                    })
                                    ->where(function ($q) use ($selectedTahunAjaran) {

                                        $q->where('tahun_ajaran', $selectedTahunAjaran)
                                            ->orWhere('tahun_ajaran', 'LIKE', $selectedTahunAjaran . '%');
                                    })
                                    ->get();

                                foreach ($jadwalList as $jadwal) {

                                    $kodeMk = trim($jadwal->kode_mk ?? '');

                                    if (!$kodeMk) {
                                        continue;
                                    }

                                    if (!isset($map[$kodeMk])) {
                                        $map[$kodeMk] = [];
                                    }

                                    $exists = collect($map[$kodeMk])
                                        ->contains(fn ($d) => $d['pegawai_id'] == $jadwal->pegawai_id);

                                    if (!$exists) {
                                        $map[$kodeMk][] = [
                                            'pegawai_id' => $jadwal->pegawai_id,
                                            'nama'       => $jadwal->dosen->nama ?? '-',
                                        ];
                                    }
                                }

                                return $map;
                            }
                        );

                        Log::info('MonitoringPerkuliahan - Mapping completed', [
                            'matkul_with_dosen' => count($matkulDosenMap),
                        ]);
                    }

                    // Process each matakuliah with limits
                    foreach ($matkulData as $matkul) {
                        if ($processedCount >= $maxMatkul) {
                            Log::info('MonitoringPerkuliahan - Reached processing limit', [
                                'processed' => $processedCount,
                                'max_limit' => $maxMatkul
                            ]);
                            break;
                        }
                        
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
                        
                        // Increment processed count
                        $processedCount++;

                        // Get dosen pengampu
                        $dosenPengampu = '-';
                        if (isset($matkulDosenMap[$kodeMk]) && ! empty($matkulDosenMap[$kodeMk])) {
                            // 🔥 Extract nama saja untuk display
                            $dosenPengampu = implode(', ', array_column($matkulDosenMap[$kodeMk], 'nama'));
                            
                            Log::info('MonitoringPerkuliahan - Dosen found', [
                                'kode_mk' => $kodeMk,
                                'dosen' => $dosenPengampu
                            ]);
                        } else {
                            Log::warning('MonitoringPerkuliahan - Dosen NOT found', [
                                'kode_mk' => $kodeMk,
                                'map_keys' => array_keys($matkulDosenMap),
                            ]);
                        }

                        // Default: 16 minggu (0 = belum upload)
                        $weeks = array_fill(0, 16, 0);

                        if ($kuliahId) {
                            try {
                                // Add timeout protection for monitoring API call
                                $monitoring = Cache::remember(
                                    "monitoring_teori_{$kuliahId}_{$selectedTahunAjaran}_{$selectedSemester}",
                                    1800, // 30 minutes cache
                                    function () use ($apiService, $kuliahId, $selectedTahunAjaran, $selectedSemester) {
                                        return $apiService->getMonitoringMateri(
                                            $kuliahId,
                                            $selectedTahunAjaran,
                                            $selectedSemester
                                        );
                                    }
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
                                            $deadline = null;
                                            if ($startDate) {
                                                $deadline = $startDate
                                                    ->copy()
                                                    ->addWeeks($weekNum)
                                                    ->startOfWeek(Carbon::MONDAY)
                                                    ->setTime(23, 59, 59);
                                            }

                                            // 🔥 Ambil waktu upload
                                            $uploadTime = !empty($sesi['created_at_final'])
                                                ? Carbon::parse($sesi['created_at_final'])
                                                : null;

                                            // 🔥 Status upload
                                            $isUploaded = !empty($sesi['is_uploaded']);

                                            // =========================
                                            // 🔥 LOGIKA FINAL - KONSISTEN DENGAN PRAKTIKUM
                                            // =========================

                                            if ($isUploaded && $uploadTime) {
                                                // 🟢 Sudah upload (tidak peduli tepat waktu atau terlambat)
                                                $weeks[$weekNum - 1] = 1;
                                            } else {
                                                // ❌ Belum upload
                                                $weeks[$weekNum - 1] = 0;
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
                                // Add timeout protection for praktikum monitoring API call
                                $monitoringPraktikum = Cache::remember(
                                    "monitoring_praktikum_{$kuliahId}_{$selectedTahunAjaran}_{$selectedSemester}",
                                    1800, // 30 minutes cache
                                    function () use ($apiService, $kuliahId, $selectedTahunAjaran, $selectedSemester) {
                                        return $apiService->getMonitoringMateriPraktikum(
                                            $kuliahId,
                                            $selectedTahunAjaran,
                                            $selectedSemester
                                        );
                                    }
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
                            'kuliah_id' => $kuliahId, // 🔥 TAMBAH INI
                        ];
                        $mkPraktikumData = [
                            'kode' => $kodeMk,
                            'nama' => $namaMk,
                            'dosen' => $dosenPengampu,
                            'weeks' => $weeksPraktikum,
                            'kuliah_id' => $kuliahId, // 🔥 TAMBAH INI
                        ];

                        // Categorize by type (for now, all go to teori)
                        // You can add logic to separate praktikum based on matkul name or other criteria
                        $materiTeori[] = $mkData;
                        $materiPraktikum[] = $mkPraktikumData;
                    }
                }
                
                // 🔥 SAVE SNAPSHOT TO DATABASE (seperti RPS)
                if (!empty($materiTeori) || !empty($materiPraktikum)) {
                    $this->savePerkuliahanSnapshot(
                        $materiTeori,
                        $materiPraktikum,
                        $matkulDosenMap,
                        $prodiId,
                        $prodiKode,
                        $selectedSemester,
                        $selectedTahunAjaran
                    );

                    $this->savePerkuliahanComplianceSnapshot(
                        $materiTeori,
                        $materiPraktikum,
                        $matkulDosenMap,
                        $prodiId,
                        $prodiKode,
                        $selectedSemester,
                        $selectedTahunAjaran
                    );
                }
            } catch (\Exception $e) {
                Log::error('MonitoringPerkuliahan Index Error', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // =========================
        // PAGINATION
        // =========================
        $perPage = 10;
        $currentPage = $request->input('page', 1);

        $paginationTeori = new LengthAwarePaginator(
            collect($materiTeori)->forPage($currentPage, $perPage),
            count($materiTeori),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => array_merge($request->query(), ['tab' => 'teori'])]
        );

        $paginationPraktikum = new LengthAwarePaginator(
            collect($materiPraktikum)->forPage($currentPage, $perPage),
            count($materiPraktikum),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => array_merge($request->query(), ['tab' => 'praktikum'])]
        );

        return view('gkm.monitoring-perkuliahan.index', [
            'user' => $user,
            'tahunAjaranList' => $tahunAjaranList,
            'selectedSemester' => $selectedSemester,
            'selectedTahunAjaran' => $selectedTahunAjaran,
            'selectedTingkat' => $selectedTingkat,
            'filterApplied' => $filterApplied,
            'materiTeori' => $materiTeori,
            'materiPraktikum' => $materiPraktikum,
            'paginationTeori' => $paginationTeori,
            'paginationPraktikum' => $paginationPraktikum,
            'noDataFromAPI' => $noDataFromAPI,
        ]);
    }
    private function getMonitoringData($semester, $tahun, $tingkat)
{
    $request = new \Illuminate\Http\Request([
        'semester' => $semester,
        'tahun_ajaran' => $tahun,
        'tingkat' => $tingkat
    ]);

    // Panggil index logic tapi ambil datanya saja
    $data = $this->index($request)->getData();

    return [
        'materiTeori' => $data['materiTeori'] ?? [],
        'materiPraktikum' => $data['materiPraktikum'] ?? [],
    ];
}


public function exportPdf(Request $request)
{
    $semester = $request->semester;
    $tahun = $request->tahun_ajaran;
    $tingkat = $request->tingkat;

    $data = $this->getMonitoringData($semester, $tahun, $tingkat);

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.monitoring-perkuliahan', [
        'materiTeori' => $data['materiTeori'],
        'materiPraktikum' => $data['materiPraktikum'],
        'semester' => $semester,
        'tahun' => $tahun,
        'tingkat' => $tingkat
    ])->setPaper('a4', 'landscape');

    // 🔥 Format nama semester
    $semesterText = $semester == 1 ? 'Ganjil' : 'Genap';

    $tanggal = now()->format('Ymd');

    $namaFile = "Monitoring-Perkuliahan-{$tahun}-{$semesterText}-Tingkat{$tingkat}-{$tanggal}.pdf";

    return $pdf->download($namaFile);
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

        // Get kaprodi (untuk tab soal)
        $dosenKaprodi = \App\Models\Dosen::kaprodi()
            ->with(['prodi'])
            ->where('prodi_id', $user->prodi_id)
            ->get();

        return view('gkm.monitoring-perkuliahan.kirim-pengingat', [
            'user' => $user,
            'dosenKaprodi' => $dosenKaprodi,
        ]);
    }

    public function reminderMateri(Request $request)
    {
        $user = Auth::user();

        $search = trim($request->input('search', ''));
        $mode = $request->input('mode', 'auto');
        $perPage = 10;
        $currentPage = (int) $request->input('page', 1);
        if ($currentPage < 1) $currentPage = 1;

        // 🔥 AMBIL DATA DARI SNAPSHOT DATABASE (seperti RPS)
        $prodiKode = $user->prodi ? $user->prodi->kode_prodi : 'TRPL';
        $prodiIdMap = [
            'TRPL' => 4,
            'TI' => 1,
            'NM' => 3,
        ];
        $prodiId = $prodiIdMap[$prodiKode] ?? 4;

        // Build check range from mode
        if ($mode === 'uts') {
            $checkRange = range(1, 7);
        } elseif ($mode === 'uas') {
            $checkRange = range(8, 15);
        } elseif ($mode === 'force') {
            $checkRange = range(1, 16);
        } else {
            $periode = PeriodeAkademik::where('is_active', true)->first();
            $startDate = $periode ? Carbon::parse($periode->start_date) : null;
            $now = Carbon::now();
            $currentWeek = null;
            if ($startDate) {
                $days = $startDate->diffInDays($now);
                $currentWeek = (int) floor($days / 7) + 1;
                if ($currentWeek < 1) $currentWeek = 1;
                if ($currentWeek > 16) $currentWeek = 16;
            }

            if ($currentWeek === 8) {
                $checkRange = range(8, 15);
            } elseif ($currentWeek === 16) {
                $checkRange = range(8, 15);
            } else {
                $checkRange = [];
            }
        }

        if (empty($checkRange)) {
            $allDosen = collect([]);
        } else {
            $snapshots = PerkuliahanMonitoringSnapshot::with('dosen')
                ->where('prodi_id', $prodiId)
                ->where('status_upload', 'BELUM UPLOAD')
                ->where('reminder_sent', false)
                ->whereHas('dosen', function($query) {
                    $query->whereNotNull('email')->where('email', '!=', '');
                })
                ->get();

            $filtered = $snapshots->filter(function($snapshot) use ($checkRange) {
                $raw = $snapshot->raw_data ?? null;
                $weeks = null;

                if (is_array($raw) && isset($raw['weeks'])) {
                    $weeks = $raw['weeks'];
                } elseif (is_string($raw)) {
                    $decoded = json_decode($raw, true);
                    $weeks = $decoded['weeks'] ?? null;
                } else {
                    $weeks = $snapshot->detail_weeks ?? null;
                }

                if (!is_array($weeks)) return false;

                foreach ($checkRange as $w) {
                    $idx = $w - 1;
                    if (!isset($weeks[$idx]) || $weeks[$idx] === 0 || $weeks[$idx] === null) {
                        return true;
                    }
                }

                return false;
            });

            $allDosen = $filtered->map(function($snapshot) {
                return [
                    'id' => $snapshot->pegawai_id,
                    'pegawai_id' => $snapshot->pegawai_id,
                    'nama' => $snapshot->dosen->nama ?? '-',
                    'nama_lengkap' => $snapshot->dosen->nama ?? '-',
                    'email' => $snapshot->dosen->email ?? '-',
                    'kontak_email' => $snapshot->dosen->email ?? '-',
                    'matkul' => $snapshot->nama_matkul,
                    'nama_matkul' => $snapshot->nama_matkul,
                    'kode_mk' => $snapshot->kode_mk,
                    'semester' => $snapshot->semester,
                    'tahun_ajaran' => $snapshot->tahun_ajaran,
                    'tingkat' => $snapshot->tingkat,
                    'jenis' => $snapshot->jenis_materi
                ];
            })->values();
        }

        if ($search !== '') {
            $searchLower = Str::lower($search);
            $allDosen = $allDosen->filter(function ($item) use ($searchLower) {
                return Str::contains(Str::lower($item['nama_lengkap'] ?? ''), $searchLower)
                    || Str::contains(Str::lower($item['kontak_email'] ?? ''), $searchLower)
                    || Str::contains(Str::lower($item['nama_matkul'] ?? ''), $searchLower)
                    || Str::contains(Str::lower($item['kode_mk'] ?? ''), $searchLower)
                    || Str::contains(Str::lower($item['jenis'] ?? ''), $searchLower);
            })->values();
        }

        $total = $allDosen->count();
        $dosenBelumUpload = $allDosen->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $dosenMateri = new LengthAwarePaginator(
            $dosenBelumUpload,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        // 🔥 Jika AJAX request, return JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'dosenList' => [
                    'data' => $dosenMateri->items(),
                    'current_page' => $dosenMateri->currentPage(),
                    'per_page' => $dosenMateri->perPage(),
                    'total' => $dosenMateri->total(),
                    'last_page' => $dosenMateri->lastPage(),
                    'from' => $dosenMateri->firstItem(),
                    'to' => $dosenMateri->lastItem(),
                ]
            ]);
        }

        return view('gkm.monitoring-perkuliahan.materi', [
            'user' => $user,
            'dosenMateri' => $dosenMateri,
            'search' => $search,
            'mode' => $mode,
        ]);
    }

    public function filterRemindersByMode(Request $request)
    {
        $user = Auth::user();

        $mode = $request->input('mode', 'auto'); // 'uts', 'uas', 'force', or 'auto'

        $prodiKode = $user->prodi ? $user->prodi->kode_prodi : 'TRPL';
        $prodiIdMap = [
            'TRPL' => 4,
            'TI' => 1,
            'NM' => 3,
        ];
        $prodiId = $prodiIdMap[$prodiKode] ?? 4;

        // Build check ranges
        if ($mode === 'uts') {
            $checkRange = range(1, 7);
        } elseif ($mode === 'uas') {
            $checkRange = range(1, 15);
        } elseif ($mode === 'force') {
            $checkRange = range(1, 16);
        } else {
            // auto: determine from current periode
            $periode = PeriodeAkademik::where('is_active', true)->first();
            $startDate = $periode ? Carbon::parse($periode->start_date) : null;
            $now = Carbon::now();
            $currentWeek = null;
            if ($startDate) {
                $days = $startDate->diffInDays($now);
                $currentWeek = (int) floor($days / 7) + 1;
                if ($currentWeek < 1) $currentWeek = 1;
                if ($currentWeek > 16) $currentWeek = 16;
            }

            if ($currentWeek === 8) $checkRange = range(1, 7);
            elseif ($currentWeek === 16) $checkRange = range(1, 15);
            else $checkRange = [];
        }

        if (empty($checkRange)) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $snapshots = PerkuliahanMonitoringSnapshot::with('dosen')
            ->where('prodi_id', $prodiId)
            ->where('status_upload', 'BELUM UPLOAD')
            ->whereHas('dosen', function($query) {
                $query->whereNotNull('email')->where('email', '!=', '');
            })
            ->get();

        $filtered = $snapshots->filter(function($snapshot) use ($checkRange, $mode) {
            if ($mode !== 'force' && !empty($snapshot->reminder_sent)) return false;

            $raw = $snapshot->raw_data ?? null;
            $weeks = null;

            if (is_array($raw) && isset($raw['weeks'])) {
                $weeks = $raw['weeks'];
            } elseif (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $weeks = $decoded['weeks'] ?? null;
            } else {
                $weeks = $snapshot->detail_weeks ?? null;
            }

            if (!is_array($weeks)) return false;

            foreach ($checkRange as $w) {
                $idx = $w - 1;
                if (!isset($weeks[$idx]) || $weeks[$idx] === 0 || $weeks[$idx] === null) {
                    return true;
                }
            }

            return false;
        });

        // Group by pegawai + kode_mk so we only send one reminder per course per lecturer
        $grouped = $filtered->groupBy(function($s) {
            return ($s->pegawai_id ?? '') . '|' . ($s->kode_mk ?? '');
        });

        $data = $grouped->map(function($group) {
            $first = $group->first();
            $kinds = $group->pluck('jenis_materi')->unique()->values()->toArray();

            return [
                'pegawai_id' => $first->pegawai_id,
                'nama_lengkap' => $first->dosen->nama ?? '-',
                'kontak_email' => $first->dosen->email ?? '-',
                'nama_matkul' => $first->nama_matkul,
                'kode_mk' => $first->kode_mk,
                'semester' => $first->semester,
                'tahun_ajaran' => $first->tahun_ajaran,
                'tingkat' => $first->tingkat,
                // return jenis as comma-separated string for backward compatibility
                'jenis' => implode(', ', $kinds),
            ];
        })->values()->toArray();

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Save snapshot perkuliahan monitoring ke database (seperti RPS)
     */
    private function savePerkuliahanSnapshot(
    array $materiTeori,
    array $materiPraktikum,
    array $matkulDosenMap,
    int $prodiId,
    string $prodiKode,
    string $semester,
    string $tahunAjaran
) {
    try {

        Log::info('=== SAVE PERKULIAHAN SNAPSHOT STARTED ===', [
            'prodi_id' => $prodiId,
            'prodi_kode' => $prodiKode,
            'semester' => $semester,
            'tahun_ajaran' => $tahunAjaran,
            'materi_teori_count' => count($materiTeori),
            'materi_praktikum_count' => count($materiPraktikum),
            'matkul_dosen_map_count' => count($matkulDosenMap),
        ]);

        // =====================================================
// FALLBACK AMBIL DOSEN MAP DARI DATABASE
// =====================================================

if (empty($matkulDosenMap)) {
    $matkulDosenMap = [];
    Log::warning('MATKUL DOSEN MAP KOSONG - AMBIL DARI DATABASE');

    $jadwalList = JadwalDosen::with('dosen')
    ->where(function ($q) use ($semester) {

        $q->where('semester', $semester);

        // fallback semester
        if ($semester == '1') {
            $q->orWhere('semester', 'Ganjil');
        }

        if ($semester == '2') {
            $q->orWhere('semester', 'Genap');
        }
    })
    ->where(function ($q) use ($tahunAjaran) {

        $q->where('tahun_ajaran', $tahunAjaran)
          ->orWhere('tahun_ajaran', 'LIKE', $tahunAjaran . '%');
    })
    ->get();

    Log::info('JADWAL DOSEN DB RESULT', [
        'count' => $jadwalList->count()
    ]);

    foreach ($jadwalList as $jadwal) {

        $kodeMk = trim($jadwal->kode_mk ?? '');

        if (!$kodeMk) {
            continue;
        }

        if (!isset($matkulDosenMap[$kodeMk])) {
            $matkulDosenMap[$kodeMk] = [];
        }

        // Hindari duplicate
        $exists = collect($matkulDosenMap[$kodeMk])
            ->contains(fn($d) => $d['pegawai_id'] == $jadwal->pegawai_id);

        if (!$exists) {

            $matkulDosenMap[$kodeMk][] = [
                'pegawai_id' => $jadwal->pegawai_id,
                'nama' => $jadwal->dosen->nama ?? '-',
            ];

            Log::info('DB DOSEN MAP ADDED', [
                'kode_mk' => $kodeMk,
                'pegawai_id' => $jadwal->pegawai_id,
                'nama' => $jadwal->dosen->nama ?? '-',
            ]);
        }
    }

    Log::info('FINAL DB DOSEN MAP', [
        'count' => count($matkulDosenMap),
        'sample' => array_slice($matkulDosenMap, 0, 5),
    ]);
}

        // =====================================================
        // PROCESS MATERI TEORI
        // =====================================================
        foreach ($materiTeori as $data) {

            Log::info('PROCESSING TEORI', [
                'kode_mk' => $data['kode'] ?? null,
                'nama_matkul' => $data['nama'] ?? null,
                'weeks' => $data['weeks'] ?? [],
            ]);

            // VALIDASI
            if (empty($data['kode'])) {

                Log::warning('SKIP TEORI - KODE MK KOSONG', [
                    'data' => $data
                ]);

                continue;
            }

            if (!isset($matkulDosenMap[$data['kode']])) {

                Log::warning('SKIP TEORI - DOSEN MAP TIDAK ADA', [
                    'kode_mk' => $data['kode'],
                ]);

                continue;
            }

            $dosenList = $matkulDosenMap[$data['kode']];
            $tingkat = substr($data['kode'], 3, 1);

            Log::info('DOSEN LIST FOUND', [
                'kode_mk' => $data['kode'],
                'jumlah_dosen' => count($dosenList),
                'dosen_list' => $dosenList,
            ]);

            // CEK BELUM UPLOAD
            $hasBelumUpload = collect($data['weeks'])
                ->contains(fn($v) => $v === 0 || $v === null);

            Log::info('HAS BELUM UPLOAD CHECK', [
                'kode_mk' => $data['kode'],
                'weeks' => $data['weeks'],
                'has_belum_upload' => $hasBelumUpload,
            ]);

                    if ($hasBelumUpload) {

                foreach ($dosenList as $dosenInfo) {

                    Log::info('TRYING SAVE SNAPSHOT TEORI', [
                        'pegawai_id' => $dosenInfo['pegawai_id'] ?? null,
                        'nama_dosen' => $dosenInfo['nama'] ?? null,
                        'kode_mk' => $data['kode'],
                    ]);

                    $key = [
                        'pegawai_id' => $dosenInfo['pegawai_id'],
                        'kode_mk' => $data['kode'],
                        'semester' => $semester,
                        'tahun_ajaran' => $tahunAjaran,
                        'jenis_materi' => 'Materi Teori',
                    ];

                    $snapshot = PerkuliahanMonitoringSnapshot::firstOrNew($key);

                    // Preserve existing reminder_sent if present; set only for newly created records
                    if (! $snapshot->exists) {
                        $snapshot->reminder_sent = false;
                    }

                    $snapshot->prodi_kode = $prodiKode;
                    $snapshot->prodi_id = $prodiId;
                    $snapshot->kuliah_id = $data['kuliah_id'] ?? null;
                    $snapshot->nama_matkul = $data['nama'];
                    $snapshot->tingkat = $tingkat;
                    $snapshot->status_upload = 'BELUM UPLOAD';
                    $snapshot->raw_data = $data;

                    $snapshot->save();

                    Log::info('SNAPSHOT TEORI SAVED', [
                        'snapshot_id' => $snapshot->id,
                        'pegawai_id' => $snapshot->pegawai_id,
                        'kode_mk' => $snapshot->kode_mk,
                    ]);
                }

            } else {

                Log::info('SKIP SAVE TEORI - TIDAK ADA BELUM UPLOAD', [
                    'kode_mk' => $data['kode'],
                ]);
            }
        }

        // =====================================================
        // PROCESS MATERI PRAKTIKUM
        // =====================================================
        foreach ($materiPraktikum as $data) {

            Log::info('PROCESSING PRAKTIKUM', [
                'kode_mk' => $data['kode'] ?? null,
                'nama_matkul' => $data['nama'] ?? null,
                'weeks' => $data['weeks'] ?? [],
            ]);

            // VALIDASI
            if (empty($data['kode'])) {

                Log::warning('SKIP PRAKTIKUM - KODE MK KOSONG', [
                    'data' => $data
                ]);

                continue;
            }

            if (!isset($matkulDosenMap[$data['kode']])) {

                Log::warning('SKIP PRAKTIKUM - DOSEN MAP TIDAK ADA', [
                    'kode_mk' => $data['kode'],
                ]);

                continue;
            }

            $dosenList = $matkulDosenMap[$data['kode']];
            $tingkat = substr($data['kode'], 3, 1);

            Log::info('DOSEN LIST PRAKTIKUM FOUND', [
                'kode_mk' => $data['kode'],
                'jumlah_dosen' => count($dosenList),
                'dosen_list' => $dosenList,
            ]);

            // CEK BELUM UPLOAD
            $hasBelumUpload = collect($data['weeks'])
                ->contains(fn($v) => $v === 0 || $v === null);

            Log::info('HAS BELUM UPLOAD PRAKTIKUM CHECK', [
                'kode_mk' => $data['kode'],
                'weeks' => $data['weeks'],
                'has_belum_upload' => $hasBelumUpload,
            ]);

            if ($hasBelumUpload) {

                foreach ($dosenList as $dosenInfo) {

                    Log::info('TRYING SAVE SNAPSHOT PRAKTIKUM', [
                        'pegawai_id' => $dosenInfo['pegawai_id'] ?? null,
                        'nama_dosen' => $dosenInfo['nama'] ?? null,
                        'kode_mk' => $data['kode'],
                    ]);

                    $key = [
                        'pegawai_id' => $dosenInfo['pegawai_id'],
                        'kode_mk' => $data['kode'],
                        'semester' => $semester,
                        'tahun_ajaran' => $tahunAjaran,
                        'jenis_materi' => 'Materi Praktikum',
                    ];

                    $snapshot = PerkuliahanMonitoringSnapshot::firstOrNew($key);

                    if (! $snapshot->exists) {
                        $snapshot->reminder_sent = false;
                    }

                    $snapshot->prodi_kode = $prodiKode;
                    $snapshot->prodi_id = $prodiId;
                    $snapshot->kuliah_id = $data['kuliah_id'] ?? null;
                    $snapshot->nama_matkul = $data['nama'];
                    $snapshot->tingkat = $tingkat;
                    $snapshot->status_upload = 'BELUM UPLOAD';
                    $snapshot->raw_data = $data;

                    $snapshot->save();

                    Log::info('SNAPSHOT PRAKTIKUM SAVED', [
                        'snapshot_id' => $snapshot->id,
                        'pegawai_id' => $snapshot->pegawai_id,
                        'kode_mk' => $snapshot->kode_mk,
                    ]);
                }

            } else {

                Log::info('SKIP SAVE PRAKTIKUM - TIDAK ADA BELUM UPLOAD', [
                    'kode_mk' => $data['kode'],
                ]);
            }
        }

        Log::info('=== PERKULIAHAN SNAPSHOT FINISHED ===', [
            'prodi_id' => $prodiId,
            'semester' => $semester,
            'tahun_ajaran' => $tahunAjaran,
        ]);

    } catch (\Exception $e) {

        Log::error('FAILED SAVE PERKULIAHAN SNAPSHOT', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}

private function savePerkuliahanComplianceSnapshot(
    array $materiTeori,
    array $materiPraktikum,
    array $matkulDosenMap,
    int $prodiId,
    string $prodiKode,
    string $semester,
    string $tahunAjaran
) {
    try {

        Log::info('=== SAVE PERKULIAHAN COMPLIANCE STARTED ===', [
            'prodi_id' => $prodiId,
            'prodi_kode' => $prodiKode,
            'semester' => $semester,
            'tahun_ajaran' => $tahunAjaran,
            'teori_count' => count($materiTeori),
            'praktikum_count' => count($materiPraktikum),
            'dosen_map_count' => count($matkulDosenMap),
        ]);

        /*
        |--------------------------------------
        | FALLBACK DOSEN MAP
        |--------------------------------------
        */
        if (empty($matkulDosenMap)) {

            $matkulDosenMap = [];

            Log::warning('DOSEN MAP KOSONG - AMBIL DARI DB');

            $jadwalList = JadwalDosen::with('dosen')
                ->where(function ($q) use ($semester) {

                    $q->where('semester', $semester);

                    if ($semester == '1') {
                        $q->orWhere('semester', 'Ganjil');
                    }

                    if ($semester == '2') {
                        $q->orWhere('semester', 'Genap');
                    }
                })
                ->where(function ($q) use ($tahunAjaran) {

                    $q->where('tahun_ajaran', $tahunAjaran)
                        ->orWhere('tahun_ajaran', 'LIKE', $tahunAjaran . '%');
                })
                ->get();

            foreach ($jadwalList as $jadwal) {

                $kodeMk = trim($jadwal->kode_mk ?? '');

                if (!$kodeMk) continue;

                if (!isset($matkulDosenMap[$kodeMk])) {
                    $matkulDosenMap[$kodeMk] = [];
                }

                $exists = collect($matkulDosenMap[$kodeMk])
                    ->contains(fn($d) => $d['pegawai_id'] == $jadwal->pegawai_id);

                if (!$exists) {
                    $matkulDosenMap[$kodeMk][] = [
                        'pegawai_id' => $jadwal->pegawai_id,
                        'nama' => $jadwal->dosen->nama ?? '-',
                    ];
                }
            }
        }

        /*
        |--------------------------------------
        | GABUNG TEORI + PRAKTIKUM
        |--------------------------------------
        */
        $allMateri = [
            'Materi Teori' => $materiTeori,
            'Materi Praktikum' => $materiPraktikum,
        ];

        foreach ($allMateri as $jenisMateri => $listMateri) {

            foreach ($listMateri as $data) {

                if (empty($data['kode'])) continue;
                if (empty($data['weeks'])) continue;
                if (!isset($matkulDosenMap[$data['kode']])) continue;

                $dosenList = $matkulDosenMap[$data['kode']];
                $tingkat = substr($data['kode'], 3, 1);

                $weeks = $data['weeks'];

                /*
                |--------------------------------------
                | FILTER WEEK (EXCLUDE UTS & UAS)
                | index 7 = minggu 8
                | index 15 = minggu 16
                |--------------------------------------
                */
                $excludedIndexes = [7, 15];

                $validWeeks = collect($weeks)
                    ->filter(function ($value, $index) use ($excludedIndexes) {
                        return !in_array($index, $excludedIndexes);
                    });

                /*
                |--------------------------------------
                | HITUNG ANALYTICS
                |--------------------------------------
                */
                $totalMinggu = $validWeeks->count();

                $jumlahUpload = $validWeeks->filter(fn($v) => $v === 1)->count();
                $jumlahTerlambat = $validWeeks->filter(fn($v) => $v === 2)->count();
                $jumlahBelumUpload = $validWeeks->filter(fn($v) => $v === 0 || $v === null)->count();

                $persentaseKepatuhan = $totalMinggu > 0
                    ? round(($jumlahUpload / $totalMinggu) * 100, 2)
                    : 0;

                $statusKepatuhan = match (true) {
                    $persentaseKepatuhan >= 100 => 'PATUH',
                    $persentaseKepatuhan >= 60 => 'BELUM PATUH',
                    default => 'KURANG PATUH',
                };

                /*
                |--------------------------------------
                | SAVE PER DOSEN
                |--------------------------------------
                */
                foreach ($dosenList as $dosenInfo) {

                    $detail = \App\Models\PerkuliahanMonitoringDetail::updateOrCreate(
                        [
                            'pegawai_id' => $dosenInfo['pegawai_id'],
                            'kode_mk' => $data['kode'],
                            'semester' => $semester,
                            'tahun_ajaran' => $tahunAjaran,
                            'jenis_materi' => $jenisMateri,
                        ],
                        [
                            'prodi_id' => $prodiId,
                            'prodi_kode' => $prodiKode,

                            'kuliah_id' => $data['kuliah_id'] ?? null,

                            'nama_dosen' => $dosenInfo['nama'] ?? '-',
                            'nama_matkul' => $data['nama'] ?? '-',

                            'tingkat' => $tingkat,

                            // analytics
                            'total_minggu' => $totalMinggu,
                            'jumlah_upload' => $jumlahUpload,
                            'jumlah_terlambat' => $jumlahTerlambat,
                            'jumlah_belum_upload' => $jumlahBelumUpload,

                            'persentase_kepatuhan' => $persentaseKepatuhan,
                            'status_kepatuhan' => $statusKepatuhan,

                            // detail
                            'detail_weeks' => $weeks,
                            'raw_data' => $data,
                        ]
                    );

                    Log::info('SNAPSHOT SAVED', [
                        'id' => $detail->id,
                        'kode_mk' => $detail->kode_mk,
                        'persentase' => $persentaseKepatuhan,
                        'status' => $statusKepatuhan,
                    ]);
                }
            }
        }

        Log::info('=== SAVE PERKULIAHAN COMPLIANCE FINISHED ===');

    } catch (\Exception $e) {

        Log::error('FAILED SAVE COMPLIANCE SNAPSHOT', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
        ]);
    }
}
    
        public function kirimReminderUploadMateri(Request $request)
        {
            try {
                $request->validate([
                    'dosen_ids' => 'required|array',
                    'dosen_ids.*' => 'string', // pegawai_id adalah string
                    'subjek' => 'required|string',
                    'pesan' => 'required|string',
                ]);

                // Ambil dosen berdasarkan pegawai_id dari model Dosenn
                $dosenList = \App\Models\Dosenn::whereIn('pegawai_id', $request->dosen_ids)->get();
                $successCount = 0;
                $errors = [];

                $user = Auth::user();
                $prodiName = $user->prodi->nama ?? 'TRPL';
                $prodiKode = $user->prodi->kode_prodi ?? 'TRPL';

                foreach ($dosenList as $dosen) {
                    try {
                        $email = $dosen->email ?? $dosen->kontak_email ?? null;
                        $nomorTelepon = $dosen->nomor_telepon ?? $dosen->nomor ?? $dosen->kontak_telepon ?? null;

                        // Send email if available
                        if (!empty($email)) {
                            Mail::to($email)->send(new ReminderUploadMateriMail(
                                $request->subjek,
                                $request->pesan,
                                $dosen->nama,
                                $prodiName,
                                $prodiKode
                            ));

                            // Log email
                            LogEmail::create([
                                'reminder_id' => null,
                                'penerima_email' => $email,
                                'subjek' => $request->subjek,
                                'isi_email' => $request->pesan,
                                'status_pengiriman' => 'success',
                                'tanggal_pengiriman' => now(),
                                'percobaan_kirim' => 1,
                            ]);
                        }

                        // Send WhatsApp if phone available
                        if (!empty($nomorTelepon)) {
                            $pesanWa = "*{$subject}*\n\n" . $request->pesan;

                            try {
                                if ($this->whatsappService) {
                                    $this->whatsappService->sendMessage($nomorTelepon, $pesanWa);

                                    // Log WA as email-log entry for traceability
                                    LogEmail::create([
                                        'reminder_id' => null,
                                        'penerima_email' => $email ?? $nomorTelepon,
                                        'subjek' => $subject,
                                        'isi_email' => $request->pesan,
                                        'status_pengiriman' => 'success',
                                        'tanggal_pengiriman' => now(),
                                        'percobaan_kirim' => 1,
                                    ]);
                                }
                            } catch (\Exception $waEx) {
                                Log::warning('Failed to send WA reminder', [
                                    'pegawai_id' => $dosen->pegawai_id,
                                    'nomor' => $nomorTelepon,
                                    'error' => $waEx->getMessage(),
                                ]);
                            }
                        }

                        // Update snapshot reminder status for this dosen
                        try {
                            \App\Models\PerkuliahanMonitoringSnapshot::where('pegawai_id', $dosen->pegawai_id)
                                ->where('status_upload', 'BELUM UPLOAD')
                                ->update([
                                    'reminder_sent' => true,
                                    'updated_at' => now()
                                ]);
                        } catch (\Exception $updateEx) {
                            Log::warning('Failed to update snapshot', [
                                'pegawai_id' => $dosen->pegawai_id,
                                'error' => $updateEx->getMessage(),
                            ]);
                        }

                        if (!empty($email) || !empty($nomorTelepon)) {
                            $successCount++;
                        }
                    } catch (\Exception $loopEx) {
                        $errors[] = "Gagal kirim ke {$dosen->nama}: " . $loopEx->getMessage();
                        Log::error('Failed to send materi reminder', [
                            'pegawai_id' => $dosen->pegawai_id,
                            'email' => $email ?? 'N/A',
                            'nomor' => $nomorTelepon ?? 'N/A',
                            'error' => $loopEx->getMessage(),
                        ]);
                    }
                }

                // Return JSON for AJAX requests
                if ($request->wantsJson() || $request->ajax()) {
                    if ($successCount > 0) {
                        return response()->json([
                            'success' => true,
                            'message' => "Berhasil mengirim reminder ke {$successCount} dosen",
                            'count' => $successCount,
                            'errors' => $errors,
                        ], 200);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Gagal mengirim semua reminder: ' . implode(', ', $errors),
                            'errors' => $errors,
                            'count' => $successCount,
                        ], 400);
                    }
                }

                // Traditional redirect response for non-AJAX requests
                if ($successCount > 0) {
                    $message = "Berhasil mengirim reminder ke {$successCount} dosen";
                    if (!empty($errors)) {
                        $message .= ". Beberapa gagal: " . implode(', ', $errors);
                    }
                    return redirect()->back()->with('success', $message);
                } else {
                    return redirect()->back()->with('error', 'Gagal mengirim semua reminder: ' . implode(', ', $errors));
                }
            } catch (\Exception $e) {
                Log::error('Failed to send materi reminder', [
                    'error' => $e->getMessage(),
                ]);

                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal mengirim reminder: ' . $e->getMessage(),
                    ], 500);
                }

                return redirect()->back()->with('error', 'Gagal mengirim reminder: ' . $e->getMessage());
            }
        }

        public function historyReminder(Request $request, $dosenId = null)
{
    $user = Auth::user();

    $dosenList = Dosenn::get();

    $sort = $request->get('sort', 'desc'); // default desc

    $logEmailList = LogEmail::where('subjek', 'like', '%materi%')
        ->when($dosenId, function ($query) use ($dosenId) {
            $dosen = Dosenn::find($dosenId);

            if ($dosen) {
                $query->where('penerima_email', $dosen->email);
            }
        })
        ->orderBy('created_at', $sort)
        ->paginate(10)
        ->appends([
            'sort' => $sort
        ]);

    return view(
        'gkm.monitoring-perkuliahan.history',
        compact('user', 'logEmailList', 'dosenList', 'dosenId', 'sort')
    );
}


    public function generateMessageMateri(Request $request)
    {
        try {
            $dosenIds = $request->input('dosen_ids', []);
            
            if (empty($dosenIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pilih minimal 1 dosen terlebih dahulu',
                ], 400);
            }

            $dosenList = \App\Models\Dosenn::whereIn('pegawai_id', $dosenIds)->get();
            
            $message = "Yth. Bapak/Ibu Dosen,\n\n";
            $message .= "Kami mengingatkan untuk segera melakukan upload materi perkuliahan di sistem CIS.\n\n";
            $message .= "Berdasarkan monitoring sistem untuk SEMUA SEMESTER (Ganjil & Genap) dan SEMUA TINGKAT (1-4), ";
            $message .= "terdapat beberapa minggu perkuliahan yang belum memiliki materi terupload, baik materi teori maupun materi praktikum.\n\n";
            $message .= "Hal yang perlu diperhatikan:\n";
            $message .= "1. Upload materi TEORI sesuai dengan jadwal perkuliahan mingguan\n";
            $message .= "2. Upload materi PRAKTIKUM untuk setiap sesi praktikum yang dijadwalkan\n";
            $message .= "3. Pastikan format file sesuai dengan ketentuan sistem\n";
            $message .= "4. Berikan deskripsi yang jelas untuk setiap materi\n";
            $message .= "5. Upload materi untuk semua mata kuliah yang diampu di semua semester dan tingkat\n\n";
            $message .= "Status yang perlu dilengkapi:\n";
            $message .= "- Materi yang belum diupload (ditandai dengan ikon ❌)\n";
            $message .= "- Baik untuk semester Ganjil maupun Genap\n";
            $message .= "- Untuk semua tingkat (Tingkat 1, 2, 3, dan 4)\n\n";
            $message .= "Mohon untuk segera melengkapi upload materi yang masih kurang agar mahasiswa dapat mengakses materi pembelajaran dengan lengkap.\n\n";
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

        // Get kaprodi (untuk tab soal)
        $dosenKaprodi = \App\Models\Dosen::kaprodi()
            ->with(['prodi'])
            ->where('prodi_id', $user->prodi_id)
            ->get();

        return view('gkm.monitoring-perkuliahan.soal', [
            'user' => $user,
            'dosenKaprodi' => $dosenKaprodi,
        ]);
    }

    public function kirimReminderReviewSoal(Request $request)
    {
        try {
            $request->validate([
                'dosen_ids' => 'required|array',
                'dosen_ids.*' => 'exists:dosen,id',
                'subjek' => 'required|string',
                'pesan' => 'required|string',
            ]);

            $dosenList = \App\Models\Dosen::whereIn('id', $request->dosen_ids)->get();
            $successCount = 0;
            $errors = [];

            foreach ($dosenList as $dosen) {
                try {
                    Mail::to($dosen->kontak_email)->send(new ReminderReviewSoalMail(
                        $dosen->nama_lengkap,
                        $request->pesan
                    ));
                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = "Gagal kirim ke {$dosen->nama_lengkap}: " . $e->getMessage();
                    Log::error('Failed to send soal reminder', [
                        'dosen_id' => $dosen->id,
                        'email' => $dosen->kontak_email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($successCount > 0) {
                $message = "Berhasil mengirim reminder ke {$successCount} dosen";
                if (!empty($errors)) {
                    $message .= ". Beberapa gagal: " . implode(', ', $errors);
                }
                return redirect()->back()->with('success', $message);
            } else {
                return redirect()->back()->with('error', 'Gagal mengirim semua reminder: ' . implode(', ', $errors));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send soal reminder', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Gagal mengirim reminder: ' . $e->getMessage());
        }
    }

    public function generateMessageSoal(Request $request)
    {
        try {
            $dosenIds = $request->input('dosen_ids', []);
            
            if (empty($dosenIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pilih minimal 1 kaprodi terlebih dahulu',
                ], 400);
            }

            $dosenList = \App\Models\Dosen::whereIn('id', $dosenIds)->get();
            
            $message = "Yth. Bapak/Ibu Kepala Program Studi,\n\n";
            $message .= "Kami mengingatkan untuk segera melakukan review soal ujian yang telah disubmit oleh dosen.\n\n";
            $message .= "Kegiatan review meliputi:\n";
            $message .= "1. Memeriksa kesesuaian soal dengan RPS\n";
            $message .= "2. Memastikan tingkat kesulitan soal sesuai dengan capaian pembelajaran\n";
            $message .= "3. Memberikan feedback dan persetujuan\n\n";
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
