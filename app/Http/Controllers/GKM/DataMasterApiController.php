<?php

namespace App\Http\Controllers\GKM;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\AIAgentService;

class DataMasterApiController extends Controller
{
    /**
     * Get token with auto-refresh capability
     * Cache token for ~58 minutes (3500 seconds)
     *
     * @param bool $forceRefresh Force refresh token
     * @return string|null
     */
    private function getToken($forceRefresh = false)
    {
        if ($forceRefresh) {
            Cache::forget('library_api_token');
        }

        return Cache::remember('library_api_token', 3500, function () {
            $baseUrl = config('services.library.url');

            try {
                $response = Http::asForm()->post($baseUrl . '/jwt-api/do-auth', [
                    'username' => config('services.library.username'),
                    'password' => config('services.library.password'),
                ]);

                $data = $response->json();

                if ($response->successful() && isset($data['token'])) {
                    Log::info('Library API token refreshed successfully');
                    return $data['token'];
                }

                Log::error('Failed to get library API token', [
                    'status' => $response->status(),
                    'response' => $data
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Library API token exception', [
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * Call API with automatic token refresh on 401
     *
     * @param string $url
     * @param array $params
     * @return array|null
     */
    private function callApi($url, $params = [])
    {
        $token = $this->getToken();

        if (!$token) {
            return null;
        }

        $response = Http::withToken($token)->timeout(10)->get($url, $params);

        // Token expired - refresh and retry
        if ($response->status() === 401) {
            Log::info('Token expired (401), refreshing...');
            $token = $this->getToken(true);

            if (!$token) {
                return null;
            }

            // Retry with new token
            $response = Http::withToken($token)->timeout(10)->get($url, $params);
        }

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Display monitoring RPS page
     */
    public function monitoringRps()
    {
        return view('gkm.new.index');
    }

    /**
     * Get monitoring RPS data via API
     */
    public function getMonitoringRps(Request $request)
    {
        try {
            $baseUrl = config('services.library.url');
            $token = $this->getToken();

            if (!$token) {
                return response()->json([
                    'error' => 'Token API tidak tersedia'
                ], 500);
            }

            $sem_ta = $request->get('sem_ta', 1);
            $ta = $request->get('ta', 2020);
            $tingkat = $request->get('tingkat');

            $cacheKey = "rps_{$ta}_{$sem_ta}_" . ($tingkat ?? 'all');

            $result = Cache::remember($cacheKey, 1800, function () use ($baseUrl, $sem_ta, $ta, $tingkat) {
                try {
                    // 1. AMBIL MATKUL
                    $matkulRes = $this->callApi($baseUrl . '/library-api/matkul-by-prodi-sem-ta', [
                        'prodi_id' => 4,
                        'sem_ta' => $sem_ta,
                        'ta' => $ta
                    ]);

                    $matkul = $matkulRes['data'] ?? [];

                    // 2. AMBIL DOSEN (BATASI BIAR GA TIMEOUT)
                    $dosenRes = $this->callApi($baseUrl . '/library-api/dosen', [
                        'limit' => 1000,
                    ]);

                    $dosenList = $dosenRes['data']['dosen'] ?? [];

                    // 3. MAP MATKUL → DOSEN (BERDASARKAN TINGKAT)
                    $mapMatkulDosen = [];

                    foreach ($dosenList as $dosen) {
                        $pegawai_id = $dosen['pegawai_id'] ?? null;
                        if (!$pegawai_id) continue;

                        try {
                            $jadwalRes = $this->callApi($baseUrl . '/library-api/get-jadwal-by-dosen', [
                                'pegawai_id' => $pegawai_id,
                                'sem_ta' => $sem_ta,
                                'ta' => $ta
                            ]);

                            $jadwal = $jadwalRes['data'] ?? [];

                            foreach ($jadwal as $j) {
                                $kelas = $j['kelas'] ?? null;

                                // HITUNG TINGKAT
                                $kelasTingkat = null;
                                if ($kelas && preg_match('/^(\d{2})/', $kelas, $match)) {
                                    $kelasTingkat = ((int)$match[1]) - 40;
                                }

                                // FILTER TINGKAT DI SINI
                                if ($tingkat && $kelasTingkat != $tingkat) {
                                    continue;
                                }

                                $kode = isset($j['kode_mk']) ? trim((string)$j['kode_mk']) : null;
                                $namaMk = strtolower(trim($j['nama_mk'] ?? ''));
                                $namaDosen = $dosen['nama'] ?? 'Tidak diketahui';

                                if ($kode) {
                                    if (!isset($mapMatkulDosen[$kode])) {
                                        $mapMatkulDosen[$kode] = [];
                                    }
                                    if (!in_array($namaDosen, $mapMatkulDosen[$kode])) {
                                        $mapMatkulDosen[$kode][] = $namaDosen;
                                    }
                                }

                                if ($namaMk) {
                                    if (!isset($mapMatkulDosen[$namaMk])) {
                                        $mapMatkulDosen[$namaMk] = [];
                                    }
                                    if (!in_array($namaDosen, $mapMatkulDosen[$namaMk])) {
                                        $mapMatkulDosen[$namaMk][] = $namaDosen;
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }

                    // 4. GABUNG KE RPS
                    $data = [];

                    foreach ($matkul as $mk) {
                        try {
                            $m = $this->callApi($baseUrl . '/library-api/get-monitoring-materi', [
                                'kuliah_id' => $mk['kuliah_id'] ?? null,
                                'ta' => $ta,
                                'sem_ta' => $sem_ta
                            ]);

                            $status = $m['check_silabus']['status_file_silabus'] ?? null;
                            $kode = isset($mk['kode_mk']) ? trim((string)$mk['kode_mk']) : '-';
                            $namaMk = strtolower(trim($mk['nama_matkul'] ?? ''));
                            $dosen = $mapMatkulDosen[$kode] ?? $mapMatkulDosen[$namaMk] ?? [];

                            // FILTER FINAL: buang matkul yang tidak ada di tingkat ini
                            if ($tingkat && empty($dosen)) {
                                continue;
                            }

                            $data[] = [
                                'kode' => $kode,
                                'nama' => $mk['nama_matkul'] ?? '-',
                                'rps' => $status === "SUDAH UPLOAD",
                                'dosen' => $dosen
                            ];
                        } catch (\Exception $e) {
                            continue;
                        }
                    }

                    return $data;
                } catch (\Exception $e) {
                    return [];
                }
            });

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display monitoring materi page
     */
    public function monitoringMateri(Request $request)
    {
        return view('gkm.new.monitormateri');
    }

    /**
     * Get monitoring materi data via API
     */
    public function monitoringMateriData(Request $request)
    {
        try {
            $baseUrl = config('services.library.url');
            $token = $this->getToken();

            if (!$token) {
                return response()->json([
                    'error' => 'Token tidak tersedia'
                ], 500);
            }

            $sem_ta = $request->get('sem_ta', 1);
            $ta = $request->get('ta', 2024);

            // Safe JSON helper
            $safeJson = function ($res) {
                try {
                    return is_array($res) ? $res : [];
                } catch (\Exception $e) {
                    return [];
                }
            };

            // 1. MATKUL
            $matkulJson = $this->callApi($baseUrl . '/library-api/matkul-by-prodi-sem-ta', [
                'prodi_id' => 4,
                'sem_ta' => $sem_ta,
                'ta' => $ta
            ]);

            if (!$matkulJson) {
                return response()->json([
                    'error' => 'API matkul gagal'
                ], 500);
            }

            $matkul = isset($matkulJson['data']) && is_array($matkulJson['data']) ? $matkulJson['data'] : [];

            // BATASI BIAR CEPAT
            $matkul = array_slice($matkul, 0, 15);

            // 2. DOSEN
            $dosenJson = $this->callApi($baseUrl . '/library-api/dosen', [
                'limit' => 1000,
                'prodi_id' => 4
            ]);

            $dosenList = isset($dosenJson['data']['dosen']) && is_array($dosenJson['data']['dosen'])
                ? $dosenJson['data']['dosen']
                : [];

            // BATASI
            $dosenList = array_slice($dosenList, 0, 20);

            // 3. MAP DOSEN → MATKUL
            $mapMatkulDosen = [];

            foreach ($dosenList as $dosen) {
                $pegawai_id = $dosen['pegawai_id'] ?? null;
                if (!$pegawai_id) continue;

                $jadwalJson = $this->callApi($baseUrl . '/library-api/get-jadwal-by-dosen', [
                    'pegawai_id' => $pegawai_id,
                    'sem_ta' => $sem_ta,
                    'ta' => $ta
                ]);

                $jadwal = isset($jadwalJson['data']) && is_array($jadwalJson['data'])
                    ? $jadwalJson['data']
                    : [];

                foreach ($jadwal as $j) {
                    $kode = trim((string)($j['kode_mk'] ?? ''));
                    $nama = $dosen['nama'] ?? '-';

                    if (!$kode) continue;

                    if (!isset($mapMatkulDosen[$kode])) {
                        $mapMatkulDosen[$kode] = [];
                    }
                    if (!in_array($nama, $mapMatkulDosen[$kode])) {
                        $mapMatkulDosen[$kode][] = $nama;
                    }
                }
            }

            // 4. BUILD DATA (FIX LOGIC BARU)
            $data = [];

            foreach ($matkul as $mk) {
                $kode = trim((string)($mk['kode_mk'] ?? ''));
                $kuliah_id = $mk['kuliah_id'] ?? null;

                if (!$kuliah_id) continue;

                $materiJson = $this->callApi($baseUrl . '/library-api/get-monitoring-materi', [
                    'kuliah_id' => $kuliah_id,
                    'ta' => $ta,
                    'sem_ta' => $sem_ta
                ]);

                if (!$materiJson) continue;

                // Ambil detail
                $detail = $materiJson['data']['detail'] ?? [];

                // Default 16 minggu
                $weeks = array_fill(1, 16, 'empty');

                foreach ($detail as $item) {
                    if (!isset($item['sesi'])) continue;

                    // Ambil week dari "W1-S1"
                    preg_match('/W(\d+)/', $item['sesi'], $match);
                    if (!isset($match[1])) continue;

                    $week = (int)$match[1];
                    if ($week < 1 || $week > 16) continue;

                    $files = $item['daftar_file'] ?? [];

                    // Kalau ada file
                    if (!empty($files)) {
                        // Cek telat (optional)
                        if (isset($item['status_file']) && str_contains($item['status_file'], 'TERLAMBAT')) {
                            $weeks[$week] = 'late';
                        } else {
                            $weeks[$week] = 'done';
                        }
                    } else {
                        // Ada sesi tapi belum upload
                        if ($weeks[$week] !== 'done') {
                            $weeks[$week] = 'warning';
                        }
                    }
                }

                $data[] = [
                    'kode' => $kode,
                    'nama' => $mk['nama_matkul'] ?? '-',
                    'dosen' => $mapMatkulDosen[$kode] ?? [],
                    'weeks' => array_values($weeks)
                ];
            }

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Display dosen list from API
     */
    public function tampilDosen()
    {
        $baseUrl = config('services.library.url');
        $token = $this->getToken();

        if (!$token) {
            abort(500, 'Login API gagal');
        }

        $response = $this->callApi($baseUrl . '/library-api/dosen', [
            'nama' => '',
            'nidn' => '',
            'nip' => '',
            'userid' => '',
            'pegawaiid' => '',
            'dosenid' => '',
            'limit' => 20
        ]);

        if ($response) {
            return view('gkm.data-master.dosencis', [
                'dosenList' => $response['data']['dosen'] ?? [],
                'user' => auth()->user()
            ]);
        }

        abort(500, 'Gagal ambil data dari API');
    }

    /**
     * Display ceklist RPS page
     */
    public function ceklistRPS()
    {
        $user = Auth::user();
        $baseUrl = config('services.library.url');
        $token = $this->getToken();

        if (!$token) {
            abort(500, 'Login API gagal');
        }

        $response = $this->callApi($baseUrl . '/library-api/dosen', [
            'nama' => '',
            'nidn' => '',
            'nip' => '',
            'email' => '',
            'userid' => '',
            'pegawaiid' => '',
            'dosenid' => '',
            'limit' => 100
        ]);

        if ($response) {
            return view('gkm.monitoring-rps.ceklist', [
                'dosenList' => $response['data']['dosen'] ?? [],
                'user' => $user
            ]);
        }

        abort(500, 'Gagal ambil data dosen dari API');
    }

    /**
     * Generate reminder message using AI
     */
    public function generateReminderMessage(Request $request)
    {
        try {
            // Validasi hanya array saja (tanpa exists)
            $request->validate([
                'dosen' => 'required|array|min:1',
                'dosen.*.nama' => 'required|string'
            ]);

            // Karena dari API, kita pakai collect saja
            $dosenList = collect($request->dosen);

            if ($dosenList->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada dosen yang dipilih'
                ], 400);
            }

            // Ambil prodi dari user login
            $user = Auth::user();
            $userProdi = $user->prodi ?? null;

            Log::info('Generating reminder message', [
                'dosen_count' => $dosenList->count(),
                'prodi' => $userProdi->kode_prodi ?? 'null'
            ]);

            // Panggil AI Agent
            $aiAgent = new AIAgentService();
            $templatePesan = $aiAgent->generateReminderMessage(
                $dosenList,
                $userProdi,
                'rps'
            );

            return response()->json([
                'success' => true,
                'message' => $templatePesan,
                'dosen_count' => $dosenList->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Generate Reminder Message Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'AI Agent error: ' . $e->getMessage()
            ], 500);
        }
    }
      /**
 * =========================================================
 * MASTER DATA MATAKULIAH
 * =========================================================
 */

/**
 * LIST MATAKULIAH
 */
public function Dmatakuliah(Request $request)
{
    try {

        $user = Auth::user();

        $baseUrl = config('services.library.url');

        /*
        |--------------------------------------------------------------------------
        | PERIODE AKADEMIK AKTIF
        |--------------------------------------------------------------------------
        */
        $periodeAktif = \App\Models\PeriodeAkademik::where('is_active', true)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | DEFAULT FILTER
        |--------------------------------------------------------------------------
        */
        $ta = $request->ta;

        $semester = $request->semester;

        /*
        |--------------------------------------------------------------------------
        | JIKA FILTER BELUM DIPILIH
        | MAKA PAKAI PERIODE AKTIF
        |--------------------------------------------------------------------------
        */
        if (!$ta || !$semester) {

            if ($periodeAktif) {

                $ta = $periodeAktif->tahun_ajaran;

                $semester = $periodeAktif->semester;

            } else {

                $ta = date('Y');

                $semester = 1;
            }
        }

        $selectedTingkat = $request->tingkat ?? 1;

        /*
        |--------------------------------------------------------------------------
        | MAP PRODI GKM LOGIN
        |--------------------------------------------------------------------------
        */
        $prodiKode = $user->prodi->kode_prodi ?? 'TRPL';

        $prodiIdMap = [
            'TRPL' => 4,
            'TI'   => 1,
            'SI'   => 2,
            'NM'   => 3,
        ];

        $prodiId = $prodiIdMap[$prodiKode] ?? 4;

        /*
        |--------------------------------------------------------------------------
        | AMBIL DATA MATKUL DARI API
        |--------------------------------------------------------------------------
        */
        $response = $this->callApi(
            $baseUrl . '/library-api/matkul-by-prodi-sem-ta',
            [
                'prodi_id' => $prodiId,
                'sem_ta'   => $semester,
                'ta'       => $ta
            ]
        );

        $matakuliahList = $response['data'] ?? [];

        // /*
        // |--------------------------------------------------------------------------
        // | FILTER TINGKAT
        // |--------------------------------------------------------------------------
        // */
        // if (!empty($selectedTingkat)) {

        //     $matakuliahList = array_filter($matakuliahList, function ($mk) use ($selectedTingkat) {

        //         $kodeMk = (string) ($mk['kode_mk'] ?? '');

        //         if (strlen($kodeMk) < 5) {
        //             return false;
        //         }

        //         /*
        //         |--------------------------------------------------------------------------
        //         | CONTOH:
        //         | IF214 => tingkat 2
        //         | IF314 => tingkat 3
        //         |--------------------------------------------------------------------------
        //         */
        //         $tingkatMk = substr($kodeMk, 3, 1);

        //         return $tingkatMk == $selectedTingkat;
        //     });
        // }

        /*
|--------------------------------------------------------------------------
| FILTER TINGKAT & SEMESTER BERDASARKAN KODE MK
|--------------------------------------------------------------------------
// *//*
|--------------------------------------------------------------------------
| FILTER TINGKAT & SEMESTER BERDASARKAN KODE MK
|--------------------------------------------------------------------------
*/
if (!empty($selectedTingkat)) {

    $matakuliahList = array_filter($matakuliahList, function ($mk) use ($selectedTingkat, $semester) {

        $kodeMk = strtoupper(trim($mk['kode_mk'] ?? ''));

        // minimal panjang kode
        if (strlen($kodeMk) < 5) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | CONTOH:
        | KU41205
        |
        | index 0 = K
        | index 1 = U
        | index 2 = 4
        | index 3 = 1  -> tingkat
        | index 4 = 2  -> semester
        |--------------------------------------------------------------------------
        */

        // digit tingkat
        $tingkatMk = substr($kodeMk, 3, 1);

        // digit semester
        $semesterMk = (int) substr($kodeMk, 4, 1);

        /*
        |--------------------------------------------------------------------------
        | FILTER GANJIL / GENAP
        |--------------------------------------------------------------------------
        */
        $isSemesterRequestGenap = $semester % 2 == 0;
        $isSemesterMkGenap = $semesterMk % 2 == 0;

        return
            $tingkatMk == $selectedTingkat
            &&
            $isSemesterRequestGenap == $isSemesterMkGenap;
    });
}
        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */
        if ($request->search) {

            $search = strtolower($request->search);

            $matakuliahList = array_filter($matakuliahList, function ($mk) use ($search) {

                return
                    str_contains(
                        strtolower($mk['nama_matkul'] ?? ''),
                        $search
                    )
                    ||
                    str_contains(
                        strtolower($mk['kode_mk'] ?? ''),
                        $search
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | SORT
        |--------------------------------------------------------------------------
        */
        usort($matakuliahList, function ($a, $b) {

            return strcmp(
                $a['nama_matkul'] ?? '',
                $b['nama_matkul'] ?? ''
            );
        });

        return view('gkm.data-master.matakuliah', [
            'user'             => $user,
            'matakuliahList'   => $matakuliahList,
            'ta'               => $ta,
            'semester'         => $semester,
            'selectedTingkat'  => $selectedTingkat,
            'prodiKode'        => $prodiKode,
            'prodiId'          => $prodiId,
            'periodeAktif'     => $periodeAktif
        ]);

    } catch (\Exception $e) {

        Log::error('MASTER MATKUL ERROR', [
            'message' => $e->getMessage()
        ]);

        return back()->with('error', 'Gagal mengambil data matakuliah');
    }
}

/**
 * DETAIL MATAKULIAH
 */
public function detailMatakuliah($kodeMk, Request $request)
{
    try {

        $user = Auth::user();

        $baseUrl = config('services.library.url');

        /*
        |--------------------------------------------------------------------------
        | PERIODE AKADEMIK AKTIF
        |--------------------------------------------------------------------------
        */
        $periodeAktif = \App\Models\PeriodeAkademik::where('is_active', true)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | DEFAULT FILTER
        |--------------------------------------------------------------------------
        */
        $ta = $request->ta;

        $semester = $request->semester;

        /*
        |--------------------------------------------------------------------------
        | JIKA FILTER KOSONG
        |--------------------------------------------------------------------------
        */
        if (!$ta || !$semester) {

            if ($periodeAktif) {

                $ta = $periodeAktif->tahun_ajaran;

                $semester = $periodeAktif->semester;

            } else {

                $ta = date('Y');

                $semester = 1;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | MAP PRODI LOGIN
        |--------------------------------------------------------------------------
        */
        $prodiKode = $user->prodi->kode_prodi ?? 'TRPL';

        $prodiIdMap = [
            'TRPL' => 4,
            'TI'   => 1,
            'NM'   => 3,
        ];

        $prodiId = $prodiIdMap[$prodiKode] ?? 4;

        $prodiClassMap = [
    'TRPL' => 'TRPL',
    'TI'   => 'TI',
    'NM'   => 'TK',
];
$classCode = $prodiClassMap[$prodiKode] ?? 'TRPL';

        /*
        |--------------------------------------------------------------------------
        | AMBIL DATA MATKUL DARI API
        |--------------------------------------------------------------------------
        */
        $response = $this->callApi(
            $baseUrl . '/library-api/matkul-by-prodi-sem-ta',
            [
                'prodi_id' => $prodiId,
                'sem_ta'   => $semester,
                'ta'       => $ta
            ]
        );

        $matkulList = $response['data'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | CARI MATAKULIAH
        |--------------------------------------------------------------------------
        */
        $matkul = collect($matkulList)
            ->first(function ($item) use ($kodeMk) {

                return strtoupper(
                    trim((string) ($item['kode_mk'] ?? ''))
                ) === strtoupper(
                    trim((string) $kodeMk)
                );
            });

        /*
        |--------------------------------------------------------------------------
        | JIKA TIDAK DITEMUKAN
        |--------------------------------------------------------------------------
        */
        if (!$matkul) {

            return back()->with(
                'error',
                'Matakuliah tidak ditemukan'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | DOSEN PENGAJAR DARI DATABASE HASIL SYNC
        |--------------------------------------------------------------------------
        */
       $dosenPengajar = \DB::table('jadwal_dosen as jd')

    ->leftJoin('dosenn as d', function ($join) {

        $join->on(
            \DB::raw('TRIM(jd.pegawai_id)'),
            '=',
            \DB::raw('TRIM(d.pegawai_id)')
        );
    })

    ->whereNotNull('d.jabatan_akademik')

    ->whereRaw("
        TRIM(UPPER(d.jabatan_akademik)) <> 'A'
    ")

    /*
    |--------------------------------------------------------------------------
    | FILTER KODE MATAKULIAH
    |--------------------------------------------------------------------------
    */
    ->whereRaw(
        'UPPER(TRIM(jd.kode_mk)) = ?',
        [strtoupper(trim($kodeMk))]
    )

    /*
    |--------------------------------------------------------------------------
    | FILTER PERIODE AKADEMIK
    |--------------------------------------------------------------------------
    */
    ->where('jd.semester', $semester)

    ->where('jd.tahun_ajaran', $ta)


    /*
    |--------------------------------------------------------------------------
    | FILTER KELAS SESUAI PRODI
    |--------------------------------------------------------------------------
    | CONTOH:
    | TRPL => 41TRPL1
    | TI   => 32TI2
    |--------------------------------------------------------------------------
    */
    ->where(function ($query) use ($classCode, $kodeMk) {

    $query->where(function ($q) use ($classCode) {

        // data hasil sync API
        $q->where('jd.kelas', 'LIKE', '%' . $classCode . '%');
    })

    ->orWhere(function ($q) use ($kodeMk) {

        // data manual HARUS tetap per matkul
        $q->where('jd.is_manual', true)
          ->where('jd.kode_mk', $kodeMk);
    });
})

    /*
    |--------------------------------------------------------------------------
    | HANYA DOSEN YANG PUNYA DATA
    |--------------------------------------------------------------------------
    */
    ->whereNotNull('jd.pegawai_id')

    /*
    |--------------------------------------------------------------------------
    | SELECT
    |--------------------------------------------------------------------------
    */
    ->select(
        'jd.pegawai_id',

        \DB::raw('MAX(jd.kelas) as kelas'),
        \DB::raw('MAX(jd.created_at) as last_created_at'),

        \DB::raw('COALESCE(d.nama, "-") as nama'),

        \DB::raw('COALESCE(d.email, "-") as email'),

        \DB::raw('COALESCE(d.nidn, "-") as nidn'),

        'd.prodi_id'
    )

    /*
    |--------------------------------------------------------------------------
    | GROUP AGAR DOSEN TIDAK DUPLIKAT
    |--------------------------------------------------------------------------
    */
    ->groupBy(
        'jd.pegawai_id',
        'd.nama',
        'd.email',
        'd.nidn',
        'd.prodi_id'
    )

    ->orderBy('last_created_at', 'asc')

    ->get();

        /*
        |--------------------------------------------------------------------------
        | LIST DOSEN UNTUK MODAL TAMBAH DOSEN
        |--------------------------------------------------------------------------
        */
        $dosenList = \App\Models\Dosenn::
    whereNotNull('pegawai_id')
    ->whereNotNull('nama')
    ->select(
        'pegawai_id',
        'nama',
        'email',
        'nidn',
        'jabatan_akademik'
    )
    ->orderBy('nama')
    ->get()
    ->toArray();

        return view('gkm.data-master.matakuliah-detail', [
            'user'           => $user,
            'matkul'         => $matkul,
            'dosenPengajar'  => $dosenPengajar,
            'dosenList'      => $dosenList,
            'ta'             => $ta,
            'semester'       => $semester,
            'prodiId'        => $prodiId,
            'prodiKode'      => $prodiKode
        ]);

    } catch (\Exception $e) {

        Log::error('DETAIL MATKUL ERROR', [
            'message' => $e->getMessage()
        ]);

        return back()->with(
            'error',
            'Gagal mengambil detail matakuliah'
        );
    }
}
/**
 * TAMBAH DOSEN KE MATKUL
 */
/**
 * TAMBAH DOSEN KE MATAKULIAH
 */
public function storeDosenMatkul(Request $request)
{
    try {

        /*
        |------------------------------------------------------------------
        | VALIDASI
        |------------------------------------------------------------------
        */
        $request->validate([
            'kode_mk'    => 'required',
            'pegawai_id' => 'required',
            'nama_dosen' => 'required'
        ]);

        /*
        |------------------------------------------------------------------
        | AMBIL PERIODE AKADEMIK AKTIF
        |------------------------------------------------------------------
        */
        $periodeAktif = \App\Models\PeriodeAkademik::where('is_active', true)
            ->first();

        if (!$periodeAktif) {

            return back()->with(
                'error',
                'Periode akademik aktif tidak ditemukan'
            );
        }

        /*
        |------------------------------------------------------------------
        | CEK DUPLIKAT
        |------------------------------------------------------------------
        */
        $exists = \App\Models\JadwalDosen::where('kode_mk', $request->kode_mk)

            ->where('pegawai_id', $request->pegawai_id)

            ->where('semester', $periodeAktif->semester)

            ->where('tahun_ajaran', $periodeAktif->tahun_ajaran)

            ->exists();

        if ($exists) {

            return back()->with(
                'error',
                'Dosen sudah ada pada matakuliah ini'
            );
        }

        /*
        |------------------------------------------------------------------
        | SIMPAN KE JADWAL_DOSEN
        |------------------------------------------------------------------
        */
        \App\Models\JadwalDosen::create([

            'pegawai_id'   => $request->pegawai_id,

            'kode_mk'      => $request->kode_mk,

            'semester'     => $periodeAktif->semester,

            'tahun_ajaran' => $periodeAktif->tahun_ajaran,

            /*
            |--------------------------------------------------------------
            | DATA MANUAL
            |--------------------------------------------------------------
            */
            'kelas'        => null,

            'kuliah_id'    => null,

            'is_manual'    => true,
        ]);

        return back()->with(
            'success',
            'Dosen berhasil ditambahkan'
        );

    } catch (\Exception $e) {

        \Log::error('STORE DOSEN MATKUL ERROR', [
            'message' => $e->getMessage()
        ]);

        return back()->with(
            'error',
            'Gagal menambahkan dosen'
        );
    }
}

/**
 * HAPUS DOSEN DARI MATKUL
 */
/**
 * HAPUS DOSEN DARI MATAKULIAH
 */
public function deleteDosenMatkul(Request $request)
{
    $request->validate([
        'pegawai_id' => 'required',
        'kode_mk'    => 'required',
    ]);

    $jadwal = \App\Models\JadwalDosen::where('pegawai_id', $request->pegawai_id)
        ->where('kode_mk', $request->kode_mk)
        ->first();

    // 1. DATA TIDAK ADA
    if (!$jadwal) {
        return back()->with('error', 'Data dosen tidak ditemukan');
    }

    // 2. HASIL SYNC TIDAK BOLEH DIHAPUS
    if ((int) $jadwal->is_manual === 0) {
        return back()->with('error', 'Data hasil sinkronisasi tidak dapat dihapus');
    }

    // 3. HANYA MANUAL YANG BOLEH DIHAPUS
    $jadwal->delete();

    return back()->with('success', 'Dosen manual berhasil dihapus');
}
}
