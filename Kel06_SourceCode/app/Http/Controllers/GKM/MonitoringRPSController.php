<?php

namespace App\Http\Controllers\GKM;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\RPS;
use App\Models\User;
use App\Models\Dosenn;
use App\Models\RpsMonitoringSnapshot;
use App\Models\LogEmail;
use App\Mail\ReminderRPSMail;
use App\Services\AIAgentService;
use App\Helpers\EmailHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PeriodeAkademik;
use App\Services\WhatsAppService;

class MonitoringRPSController extends Controller
{
    public function __construct(
    WhatsAppService $whatsappService
) {
    $this->whatsappService = $whatsappService;
}

    public function index(Request $request)
{
    set_time_limit(180);

    try {
        $user = Auth::user();
        $apiService = new \App\Services\ExternalAPIService();

        // =========================
        // PERIODE AKTIF (FIX)
        // =========================
        $periodeAktif = PeriodeAkademik::where('is_active', true)->first();

        // =========================
        // TAHUN AJARAN (DYNAMIC AUTO-UPDATE)
        // =========================
        $tahunAjaranList = $this->generateDynamicTahunAjaran();

        // =========================
        // FILTER
        // =========================
        $selectedSemester = $request->input('semester');
        $selectedTahunAjaran = $request->input('tahun_ajaran');

        if (!$selectedSemester || !$selectedTahunAjaran) {
            if ($periodeAktif) {
                $selectedSemester = $periodeAktif->semester;
                $selectedTahunAjaran = $periodeAktif->tahun_ajaran;
            }
        }

        $selectedTingkat = $request->input('tingkat', null);

        // =========================
        // PRODI
        // =========================
        $prodiKode = $user->prodi->kode_prodi ?? 'TRPL';

        $prodiIdMap = [
            'TRPL' => 4,
            'TI'   => 1,
            'NM'   => 3,
        ];

        $prodiId = $prodiIdMap[$prodiKode] ?? 4;

        \Log::info('PRODI USER', [
    'user' => $user->name,
    'prodi_kode' => $prodiKode,
    'prodi_id' => $prodiId
]);
        // =========================
        // CACHE
        // =========================
        $cacheKey = "monitoring_rps_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}_{$selectedTingkat}";
        $matkulList = \Cache::get($cacheKey);

        if ($matkulList === null) {

            $matkulList = $this->buildMonitoringData(
                $apiService,
                $prodiId,
                $selectedSemester,
                $selectedTahunAjaran,
                $prodiKode,
                $selectedTingkat
            );

            if (!empty($matkulList)) {
                // Save to database snapshot
                $this->saveSnapshotToDB(
                    $matkulList,
                    $prodiId,
                    $prodiKode,
                    $selectedSemester,
                    $selectedTahunAjaran
                );

                $rpsCompliance = $this->saveRpsComplianceSnapshot(
                    $prodiId,
                    $prodiKode,
                    $selectedSemester,
                    $selectedTahunAjaran
                );

                // Cache the result
                \Cache::put($cacheKey, $matkulList, 1800);

                \Log::info('MonitoringRPS - Data cached and saved to snapshot', [
                    'prodi_id' => $prodiId,
                    'semester' => $selectedSemester,
                    'tahun_ajaran' => $selectedTahunAjaran,
                    'tingkat' => $selectedTingkat,
                    'count' => count($matkulList)
                ]);
            }
        } else {
            \Log::info('MonitoringRPS - Data loaded from cache', [
                'cache_key' => $cacheKey,
                'count' => count($matkulList)
            ]);
        }

        $rpsCompliance = $rpsCompliance ?? $this->getRpsComplianceSummary($prodiId, $selectedSemester, $selectedTahunAjaran);

        // =========================
        // PAGINATION
        // =========================
        $perPage = 10;
        $currentPage = $request->input('page', 1);

        $pagination = new LengthAwarePaginator(
            collect($matkulList)->forPage($currentPage, $perPage),
            count($matkulList),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('gkm.monitoring-rps.index', compact(
            'user',
            'pagination',
            'matkulList',
            'tahunAjaranList',
            'selectedSemester',
            'selectedTahunAjaran',
            'selectedTingkat',
            'periodeAktif',
            'rpsCompliance'
        ))->with('noDataFromAPI', empty($matkulList));

    } catch (\Exception $e) {

        \Log::error('MonitoringRPS Error', [
            'error' => $e->getMessage()
        ]);

        return view('gkm.monitoring-rps.index', [
            'user' => Auth::user(),
            'pagination' => new LengthAwarePaginator([], 0, 15),
            'tahunAjaranList' => [],
            'selectedSemester' => '',
            'selectedTahunAjaran' => '',
            'selectedTingkat' => '',
            'periodeAktif' => null,
            'rpsCompliance' => [
                'total_records' => 0,
                'total_upload' => 0,
                'jumlah_belum_upload' => 0,
                'persentase_kepatuhan' => 0,
            ],
        ])->with('error', 'Terjadi kesalahan.');
    }
}
    /**
     * Build monitoring data from API
     */

    private function getMonitoringRpsData($semester, $tahun, $tingkat)
{
    $request = new Request([
        'semester' => $semester,
        'tahun_ajaran' => $tahun,
        'tingkat' => $tingkat
    ]);

    $response = $this->index($request);
    $data = $response->getData();

    return $data['matkulList'] ?? []; // 🔥 BUKAN pagination
}

public function syncSemuaJadwal($semester, $tahun)
{
    $apiService = new \App\Services\ExternalAPIService();

    // 🔥 ambil semua dosen dari DB lokal
    $dosenList = \App\Models\Dosenn::select('pegawai_id')->get();

    foreach ($dosenList as $dosen) {

        try {
            $jadwalList = $apiService->getJadwalByDosen(
                $dosen->pegawai_id,
                $semester,
                $tahun
            );

            foreach ($jadwalList ?? [] as $jadwal) {

                // 🔥 FILTER (jaga-jaga kalau API tidak bersih)
                if (
                    ($jadwal['semester'] ?? null) != $semester ||
                    ($jadwal['tahun_ajaran'] ?? null) != $tahun
                ) {
                    continue;
                }

                \App\Models\JadwalDosen::updateOrCreate(
                    [
                        'pegawai_id' => $dosen->pegawai_id,
                        'kode_mk' => $jadwal['kode_mk'],
                        'semester' => $semester,
                        'tahun_ajaran' => $tahun,
                    ],
                    [
                        'kuliah_id' => $jadwal['kuliah_id'] ?? null,

                    ]
                );
            }

        } catch (\Exception $e) {
            \Log::warning("Gagal sync dosen {$dosen->pegawai_id}");
        }
    }

    return "Sync selesai";
}
public function exportPdf(Request $request)
{
    $semester = $request->semester;
    $tahun = $request->tahun_ajaran;
    $tingkat = $request->tingkat;

    // 🔥 ambil data yang sama seperti halaman
    $data = $this->getMonitoringRpsData($semester, $tahun, $tingkat);

    $pdf = Pdf::loadView('pdf.monitoring-rps', [
        'data' => $data,
        'semester' => $semester,
        'tahun' => $tahun,
        'tingkat' => $tingkat
    ])->setPaper('a4', 'portrait');

    // 🔥 nama file dinamis
    $semesterText = $semester == 1 ? 'Ganjil' : 'Genap';
    $tanggal = now()->format('Ymd');

    $namaFile = "Monitoring-RPS-{$tahun}-{$semesterText}-Tingkat{$tingkat}-{$tanggal}.pdf";

    return $pdf->download($namaFile);
}

        private function buildMonitoringData(
    $apiService,
    $prodiId,
    $selectedSemester,
    $selectedTahunAjaran,
    $prodiKode,
    $selectedTingkat
) {
    $matkulList = [];

    try {
        /*
        |----------------------------------------
        | 1. AMBIL MATKUL DARI API
        |----------------------------------------
        */
        $matkulData = \Cache::remember(
            "matkul_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}",
            1800,
            function () use ($apiService, $prodiId, $selectedSemester, $selectedTahunAjaran) {
                return $apiService->getMatkulByProdiSemTa($prodiId, $selectedSemester, $selectedTahunAjaran);
            }
        );

        if (empty($matkulData)) {
            \Log::warning('No matakuliah data from API');
            return [];
        }

    } catch (\Exception $e) {
        \Log::error('Failed get matkul', ['error' => $e->getMessage()]);
        return [];
    }

    /*
    |----------------------------------------
    | 2. AMBIL DOSEN DARI DATABASE (JADWAL DOSEN)
    |----------------------------------------
    */
    \Log::info('MonitoringRPS - Start fetching jadwal dosen', [
        'semester' => $selectedSemester,
        'tahun_ajaran' => $selectedTahunAjaran
    ]);

    // 🔥 QUERY DOSEN TANPA CACHE DULU - UNTUK DEBUG
    \Log::info('MonitoringRPS - Debug: Query params', [
        'selectedSemester' => $selectedSemester,
        'selectedTahunAjaran' => $selectedTahunAjaran
    ]);

    // Konversi semester input ke format yang ada di database
    $semesterVariations = [];

    if ($selectedSemester == '1') {
        $semesterVariations = ['1', 'Ganjil', 'ganjil', 'GANJIL'];
    } elseif ($selectedSemester == '2') {
        $semesterVariations = ['2', 'Genap', 'genap', 'GENAP'];
    } else {
        // Jika tidak ada filter semester, ambil semua
        $semesterVariations = ['1', '2', 'Ganjil', 'ganjil', 'GANJIL', 'Genap', 'genap', 'GENAP'];
    }

    // Query dengan LEFT JOIN untuk memastikan semua jadwal terambil
    $jadwalDosenList = \DB::table('jadwal_dosen as jd')
        ->leftJoin('dosenn as d', 'jd.pegawai_id', '=', 'd.pegawai_id')
        ->whereIn('jd.semester', $semesterVariations)
        ->where(function ($q) use ($selectedTahunAjaran) {
            // Coba berbagai format tahun ajaran
            $q->where('jd.tahun_ajaran', $selectedTahunAjaran)
              ->orWhere('jd.tahun_ajaran', 'LIKE', $selectedTahunAjaran . '%')
              ->orWhere('jd.tahun_ajaran', 'LIKE', '%' . $selectedTahunAjaran)
              ->orWhere('jd.tahun_ajaran', 'LIKE', '%' . $selectedTahunAjaran . '%');
        })
        ->select(
            'jd.kode_mk',
            'jd.pegawai_id',
            'jd.is_manual',
            'd.nama',
            'jd.semester',
            'jd.tahun_ajaran'
        )
        ->get();

    \Log::info('MonitoringRPS - Jadwal dosen query result', [
        'semester_filter' => $semesterVariations,
        'tahun_filter' => $selectedTahunAjaran,
        'count' => $jadwalDosenList->count(),
        'sample' => $jadwalDosenList->take(10)->map(function($r) {
            return [
                'kode_mk' => $r->kode_mk,
                'nama' => $r->nama,
                'pegawai_id' => $r->pegawai_id,
                'semester' => $r->semester,
                'tahun_ajaran' => $r->tahun_ajaran
            ];
        })->toArray()
    ]);

    \Log::info('MonitoringRPS - Jadwal dosen fetched', [
        'count' => $jadwalDosenList->count()
    ]);

    /*
    |----------------------------------------
    | 3. MAP KODE_MK -> DOSEN
    |----------------------------------------
    */
    $matkulDosenMap = [];

    foreach ($jadwalDosenList as $item) {

        $kodeMk = trim($item->kode_mk ?? '');

        if (!$kodeMk) {
            \Log::warning('MonitoringRPS - Empty kode_mk found', [
                'item' => (array)$item
            ]);
            continue;
        }

        if (!isset($matkulDosenMap[$kodeMk])) {
            $matkulDosenMap[$kodeMk] = [];
        }

        $matkulDosenMap[$kodeMk][] = [
            'pegawai_id' => $item->pegawai_id,
            'nama'       => $item->nama ?? '-',
            'is_manual'  => $item->is_manual
        ];
    }

    \Log::info('MonitoringRPS - Matkul dosen map built', [
        'matkul_count' => count($matkulDosenMap),
        'total_mappings' => array_sum(array_map('count', $matkulDosenMap)),
        'sample_keys' => array_slice(array_keys($matkulDosenMap), 0, 10),
        'sample_data' => array_slice($matkulDosenMap, 0, 3, true)
    ]);

    /*
    |----------------------------------------
    | 4. LOOP MATKUL
    |----------------------------------------
    */
    \Log::info('MonitoringRPS - Start processing matkul', [
        'total_matkul' => count($matkulData),
        'sample_matkul' => array_slice($matkulData, 0, 3)
    ]);

    foreach ($matkulData as $matkul) {

        $kuliahId = $matkul['kuliah_id'] ?? null;
        $kodeMk   = trim((string) ($matkul['kode_mk'] ?? ''));

        if (!$kuliahId || strlen($kodeMk) < 5) {
            \Log::warning('MonitoringRPS - Skipping matkul due to invalid data', [
                'kuliah_id' => $kuliahId,
                'kode_mk' => $kodeMk
            ]);
            continue;
        }

        /*
        | FILTER TINGKAT
        */
        $tingkatMk = substr($kodeMk, 3, 1);

        if (!empty($selectedTingkat) && $tingkatMk != $selectedTingkat) {
            continue;
        }

        try {
            /*
            | STATUS RPS
            */
            $monitoring = \Cache::remember(
                "monitoring_{$kuliahId}_{$selectedSemester}_{$selectedTahunAjaran}",
                1800,
                function () use ($apiService, $kuliahId, $selectedTahunAjaran, $selectedSemester) {
                    return $apiService->getMonitoringMateri(
                        $kuliahId,
                        $selectedTahunAjaran,
                        $selectedSemester
                    );
                }
            );

            /*
            | DOSEN PENGAMPU (DARI DB)
            */
            $pegawaiIds = [];
            $dosenNama  = '-';

            // Cek dengan berbagai variasi kode MK (case-insensitive, trim spaces)
            $foundKey = null;
            foreach (array_keys($matkulDosenMap) as $key) {
                if (strcasecmp(trim($key), $kodeMk) === 0) {
                    $foundKey = $key;
                    break;
                }
            }

            if ($foundKey && isset($matkulDosenMap[$foundKey])) {

                $uniqueDosen = collect($matkulDosenMap[$foundKey])
                    ->unique('pegawai_id')
                    ->values();

                $pegawaiIds = $uniqueDosen->pluck('pegawai_id')->toArray();
                $dosenNama = $uniqueDosen->pluck('nama')->filter()->implode(', ');

                if (empty($dosenNama) || $dosenNama === '') {
                    $dosenNama = '-';
                }

                \Log::info('MonitoringRPS - Dosen found for matkul', [
                    'kode_mk' => $kodeMk,
                    'found_key' => $foundKey,
                    'dosen' => $dosenNama,
                    'pegawai_ids' => $pegawaiIds,
                    'dosen_count' => count($pegawaiIds)
                ]);
            } else {
                \Log::warning('MonitoringRPS - No dosen found for matkul', [
                    'kode_mk' => $kodeMk,
                    'kode_mk_length' => strlen($kodeMk),
                    'available_keys_sample' => array_slice(array_keys($matkulDosenMap), 0, 5),
                    'total_available_keys' => count($matkulDosenMap)
                ]);
            }

            $matkulList[] = [
                'kode_mk'        => $kodeMk,
                'nama_matkul'    => $matkul['nama_matkul'] ?? '-',
                'dosen_pengampu' => $dosenNama,
                'pegawai_ids'    => $pegawaiIds,
                'status_rps'     => $monitoring['status_file_silabus'] ?? 'BELUM UPLOAD',
                'kuliah_id'      => $kuliahId
            ];

        } catch (\Exception $e) {

            $matkulList[] = [
                'kode_mk'        => $kodeMk,
                'nama_matkul'    => $matkul['nama_matkul'] ?? '-',
                'dosen_pengampu' => '-',
                'pegawai_ids'    => [],
                'status_rps'     => 'ERROR',
                'kuliah_id'      => $kuliahId
            ];
        }
    }

    return $matkulList;
}

private function saveSnapshotToDB(
    array $matkulList,
    int $prodiId,
    string $prodiKode,
    string $semester,
    string $tahunAjaran
) {
    try {

        foreach ($matkulList as $data) {

            if (empty($data['kode_mk']) || empty($data['kuliah_id'])) {
                continue;
            }

            $pegawaiIds = $data['pegawai_ids'] ?? [];

            // kalau tidak ada dosen tetap simpan 1 row
            if (empty($pegawaiIds)) {

    \Log::warning('Skip snapshot karena dosen tidak ditemukan', [
        'kode_mk' => $data['kode_mk'],
        'kuliah_id' => $data['kuliah_id']
    ]);

    continue;
}

            foreach ($pegawaiIds as $pegawaiId) {

                \App\Models\RpsMonitoringSnapshot::firstOrCreate(
                    // 🔑 UNIQUE KEY (penentu duplicate)
                    [
                        'pegawai_id' => $pegawaiId,
                        'kuliah_id' => $data['kuliah_id'],
                        'semester' => $semester,
                        'tahun_ajaran' => $tahunAjaran,
                        'prodi_id' => $prodiId,
                    ],
                    // 🔥 hanya akan dipakai kalau data BELUM ADA
                    [
                        'prodi_kode' => $prodiKode,
                        'kode_mk' => $data['kode_mk'],
                        'nama_matkul' => $data['nama_matkul'] ?? '-',
                        'status_rps' => $data['status_rps'] ?? 'BELUM UPLOAD',
                        'reminder_sent' => false,
                        'raw_data' => $data,
                    ]
                );
            }
        }

        \Log::info('Snapshot saved (no duplicate)', [
            'total_data' => count($matkulList),
            'semester' => $semester,
            'tahun_ajaran' => $tahunAjaran
        ]);

    } catch (\Exception $e) {

        \Log::error('Save snapshot error', [
            'error' => $e->getMessage()
        ]);
    }
}

private function saveRpsComplianceSnapshot(
    int $prodiId,
    string $prodiKode,
    string $semester,
    string $tahunAjaran
) {
    try {
        $summary = $this->buildRpsComplianceSummary($prodiId, $semester, $tahunAjaran);

        \Cache::put(
            "rps_compliance_{$prodiId}_{$semester}_{$tahunAjaran}",
            $summary,
            1800
        );

        \Log::info('MonitoringRPS - Compliance snapshot calculated', [
            'prodi_id' => $prodiId,
            'prodi_kode' => $prodiKode,
            'semester' => $semester,
            'tahun_ajaran' => $tahunAjaran,
            'summary' => $summary,
        ]);

        return $summary;
    } catch (\Exception $e) {
        \Log::error('MonitoringRPS - Failed to calculate compliance snapshot', [
            'error' => $e->getMessage(),
        ]);

        return [
            'total_records' => 0,
            'total_upload' => 0,
            'jumlah_belum_upload' => 0,
            'persentase_kepatuhan' => 0,
        ];
    }
}

private function getRpsComplianceSummary(int $prodiId, string $semester, string $tahunAjaran)
{
    $cached = \Cache::get("rps_compliance_{$prodiId}_{$semester}_{$tahunAjaran}");

    if (!empty($cached)) {
        return $cached;
    }

    return $this->buildRpsComplianceSummary($prodiId, $semester, $tahunAjaran);
}

private function buildRpsComplianceSummary(int $prodiId, string $semester, string $tahunAjaran)
{
    $snapshots = RpsMonitoringSnapshot::query()
        ->where('prodi_id', $prodiId)
        ->where('semester', (int) $semester)
        ->where('tahun_ajaran', (string) $tahunAjaran)
        ->get();

    $courseKey = function ($snapshot) {
        $kuliahId = trim((string) ($snapshot->kuliah_id ?? ''));
        $kodeMk = trim((string) ($snapshot->kode_mk ?? ''));

        return $kuliahId !== '' ? $kuliahId : $kodeMk;
    };

    $uniqueCourses = $snapshots
        ->map($courseKey)
        ->filter()
        ->unique()
        ->values();

    $totalRecords = $uniqueCourses->count();
    $uploadedStatuses = ['SUDAH UPLOAD', 'SUDAH DIVALIDASI', 'UPLOAD'];

    $uploadedCourses = $snapshots->filter(function ($snapshot) use ($uploadedStatuses, $courseKey) {
        $status = strtoupper((string) ($snapshot->status_rps ?? ''));

        return in_array($status, $uploadedStatuses, true);
    })->map($courseKey)
        ->filter()
        ->unique()
        ->values();

    $totalUpload = $uploadedCourses->count();
    $jumlahBelumUpload = $totalRecords - $totalUpload;
    $persentaseKepatuhan = $totalRecords > 0
        ? round(($totalUpload / $totalRecords) * 100, 2)
        : 0;

    return [
        'total_records' => $totalRecords,
        'total_upload' => $totalUpload,
        'jumlah_belum_upload' => $jumlahBelumUpload,
        'persentase_kepatuhan' => $persentaseKepatuhan,
    ];
}

/**
     * Clear cache for monitoring RPS data
     */
    public function clearCache(Request $request)
    {
        try {
            $user = Auth::user();
            $prodiKode = $user->prodi ? $user->prodi->kode_prodi : 'TRPL';

            $prodiIdMap = [
                'TRPL' => 4,
                'TI' => 1,
                'NM' => 3,
            ];

            $prodiId = $prodiIdMap[$prodiKode] ?? 4;

            // Get current filter values
            $selectedSemester = $request->input('semester', '1');
            $selectedTahunAjaran = $request->input('tahun_ajaran', '2025');
            $selectedTingkat = $request->input('tingkat', '');

            // Clear main cache
            $cacheKey = "monitoring_rps_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}_{$selectedTingkat}";
            \Cache::forget($cacheKey);

            // Clear related caches
            \Cache::forget("matkul_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}");
            \Cache::forget("jadwal_dosen_rps_{$selectedSemester}_{$selectedTahunAjaran}");
            \Cache::forget("matkul_dosen_map_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}");

            \Log::info('MonitoringRPS - Cache cleared manually', [
                'prodi_id' => $prodiId,
                'semester' => $selectedSemester,
                'tahun_ajaran' => $selectedTahunAjaran,
                'tingkat' => $selectedTingkat
            ]);

            return redirect()->route('gkm.monitoring-rps.index', [
                'semester' => $selectedSemester,
                'tahun_ajaran' => $selectedTahunAjaran,
                'tingkat' => $selectedTingkat
            ])->with('cache_cleared', true);

        } catch (\Exception $e) {
            \Log::error('Failed to clear cache', [
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Gagal menghapus cache');
        }
    }

    public function ceklistRPS(Request $request)
{
    $user = Auth::user();

    $prodiKode = $user->prodi ? $user->prodi->kode_prodi : 'TRPL';

    $prodiIdMap = [
        'TRPL' => 4,
        'TI'   => 1,
        'NM'   => 3,
    ];

    $prodiId = $prodiIdMap[$prodiKode] ?? 4;

    $search = trim($request->input('search'));

    // 🔥 ambil periode aktif
    $periodeAktif = DB::table('periode_akademik')
        ->where('is_active', 1)
        ->first();

    if (!$periodeAktif) {
        return back()->with('error', 'Periode akademik aktif tidak ditemukan.');
    }

    $dosenList = DB::table('dosenn as d')
        ->join('rps_monitoring_snapshots as r', 'd.pegawai_id', '=', 'r.pegawai_id')

        // 🔥 filter prodi
        ->where('r.prodi_id', $prodiId)

        // 🔥 FILTER PERIODE (INI PENGGANTI periode_id)
        ->where('r.tahun_ajaran', $periodeAktif->tahun_ajaran)
        ->where('r.semester', $periodeAktif->semester)

        // 🔥 status RPS
        ->where('r.status_rps', 'BELUM UPLOAD')

        // 🔥 reminder belum dikirim
        ->where('r.reminder_sent', false)

        // 🔍 search
        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('d.nama', 'like', "%{$search}%")
                  ->orWhere('r.nama_matkul', 'like', "%{$search}%");
            });
        })

        ->select(
            'd.pegawai_id as id',
            'd.nama as nama_lengkap',
            'd.email as kontak_email',
            'r.nama_matkul',
            'r.status_rps'
        )
        ->paginate(10)
        ->withQueryString();

    return view('gkm.monitoring-rps.ceklist', compact('user', 'dosenList'));
}

    public function generateReminderMessage(Request $request)
{
    $request->validate([
        'dosen_ids' => 'required',
    ]);

    // 🔥 paksa jadi array
    $dosenIds = (array) $request->dosen_ids;

    $dosenList = Dosenn::whereIn('pegawai_id', $dosenIds)->get();

    $templatePesan = $this->generateTemplateMessage($dosenList);

    return response()->json([
        'success' => true,
        'message' => $templatePesan,
        'dosen_count' => $dosenList->count(),
    ]);
}

    private function generateTemplateMessage($dosenList)
    {
        if (!$dosenList || $dosenList->isEmpty()) {
        return "Tidak ada data dosen yang dipilih.";
    }
        $namaDosen = $dosenList->count() > 1
            ? 'Bapak/Ibu Dosen'
            : 'Bapak/Ibu ' . ($dosenList->first()->nama ?? $dosenList->first()->nama_lengkap);

        $message = "Kepada Yth.\n";
        $message .= "{$namaDosen}\n\n";
        $message .= "Dengan hormat,\n\n";
        $message .= "Melalui surat elektronik ini, kami ingin mengingatkan Bapak/Ibu untuk segera mengunggah Rencana Pembelajaran Semester (RPS) ";
        $message .= "untuk mata kuliah yang diampu pada semester ini.\n\n";
        $message .= "Pengunggahan RPS sangat penting untuk:\n";
        $message .= "1. Memastikan kesiapan pembelajaran semester ini\n";
        $message .= "2. Memenuhi standar akreditasi program studi\n";
        $message .= "3. Memberikan panduan yang jelas kepada mahasiswa\n\n";
        $message .= "Mohon untuk dapat mengunggah RPS paling lambat 3 hari ke depan melalui sistem informasi akademik.\n\n";
        $message .= "Apabila terdapat kendala atau pertanyaan, silakan menghubungi kami.\n\n";
        $message .= "Terima kasih atas perhatian dan kerjasamanya.\n\n";
        $message .= "Hormat kami,\n";
        $message .= "Tim GKM TRPL";

        return $message;
    }

    public function sendReminder(Request $request)
{
    $request->validate([
        'dosen_ids' => 'required',
        'message' => 'required|string',
        'subject' => 'required|string|max:255',
    ]);

    try {
        $dosenIds = (array) $request->dosen_ids;

        $dosenList = Dosenn::whereIn('pegawai_id', $dosenIds)->get();

        $successCount = 0;
        $failedCount = 0;

        foreach ($dosenList as $dosen) {

    // =========================
    // VALIDASI EMAIL & PEMILIHAN EMAIL
    // =========================
    $emailList = [];
    
    // Cek email dari berbagai field
    if (!empty($dosen->email)) {
        $emailList[] = $dosen->email;
    }
    if (!empty($dosen->kontak_email)) {
        $emailList[] = $dosen->kontak_email;
    }
    
    // Parse multiple emails (jika ada yang terpisah dengan koma)
    $allEmails = [];
    foreach ($emailList as $emailField) {
        $emails = array_map('trim', explode(',', $emailField));
        $allEmails = array_merge($allEmails, $emails);
    }
    
    // Filter email valid
    $allEmails = array_filter($allEmails, fn($e) => !empty($e) && filter_var($e, FILTER_VALIDATE_EMAIL));
    $allEmails = array_unique($allEmails); // Remove duplicates
    
    if (empty($allEmails)) {
        // Skip dosen tanpa email
        $failedCount++;
        \Log::warning('Dosen tanpa email dilewati', [
            'pegawai_id' => $dosen->pegawai_id,
            'nama' => $dosen->nama ?? $dosen->nama_lengkap
        ]);
        continue;
    }
    
    // Pilih email: prioritas domain bukan @gmail.com
    $emailTerpilih = null;
    if (count($allEmails) > 1) {
        $nonGmailEmail = array_filter($allEmails, fn($e) => !stripos($e, '@gmail.com'));
        $emailTerpilih = !empty($nonGmailEmail) ? reset($nonGmailEmail) : reset($allEmails);
    } else {
        $emailTerpilih = reset($allEmails);
    }
    
    $nomorTelepon = $dosen->nomor_telepon ?? null;

    try {

        // =========================
        // EMAIL
        // =========================
        if (!empty($emailTerpilih)) {

            Mail::to($emailTerpilih)->send(
                new ReminderRPSMail(
                    $request->subject,
                    $request->message,
                    $dosen->nama ?? $dosen->nama_lengkap
                )
            );
        }

        // =========================
        // WHATSAPP
        // =========================
        if (!empty($nomorTelepon)) {

            $pesanWa =
                "*{$request->subject}*\n\n" .
                $request->message;
                \Log::info('WA TRY SEND', [
    'nomor' => $nomorTelepon,
    'pesan' => $pesanWa
]);

            $this->whatsappService->sendMessage(
                $nomorTelepon,
                $pesanWa
            );
        }

        // =========================
        // LOG
        // =========================
        LogEmail::create([
            'reminder_id' => null,
            'penerima_email' => $emailTerpilih,
            'subjek' => $request->subject,
            'isi_email' => $request->message,
            'status_pengiriman' => 'success',
            'tanggal_pengiriman' => now(),
            'percobaan_kirim' => 1,
        ]);

        // =========================
        // UPDATE STATUS REMINDER
        // =========================
        RpsMonitoringSnapshot::where('pegawai_id', $dosen->pegawai_id)
            ->where('status_rps', 'BELUM UPLOAD')
            ->update([
                'reminder_sent' => true,
                'updated_at' => now()
            ]);

        $successCount++;

    } catch (\Exception $e) {

        LogEmail::create([
            'reminder_id' => null,
            'penerima_email' => $emailTerpilih,
            'subjek' => $request->subject,
            'isi_email' => $request->message,
            'status_pengiriman' => 'failed',
            'pesan_error' => $e->getMessage(),
            'tanggal_pengiriman' => now(),
            'percobaan_kirim' => 1,
        ]);

        $failedCount++;

        \Log::error('Reminder gagal', [
            'pegawai_id' => $dosen->pegawai_id,
            'email' => $emailTerpilih,
            'nomor_telepon' => $nomorTelepon,
            'error' => $e->getMessage()
        ]);
    }
}

        return response()->json([
            'success' => true,
            'message' => "Berhasil: {$successCount}, Gagal: {$failedCount}"
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengirim reminder: ' . $e->getMessage()
        ], 500);
    }
}

    public function historyReminder(Request $request, $dosenId = null)
{
    $user = Auth::user();

    $dosenList = Dosenn::get();

    $sort = $request->get('sort', 'desc'); // default desc

    $logEmailList = LogEmail::where('subjek', 'like', '%RPS%')
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
        'gkm.monitoring-rps.history',
        compact('user', 'logEmailList', 'dosenList', 'dosenId', 'sort')
    );
}


    /**
     * Generate dynamic tahun ajaran list based on current year
     * Auto-updates every 5 years
     *
     * Example:
     * - Current year 2025-2029: Shows 2025, 2026, 2027, 2028, 2029, 2030
     * - Current year 2030-2034: Shows 2030, 2031, 2032, 2033, 2034, 2035
     * - Current year 2035-2039: Shows 2035, 2036, 2037, 2038, 2039, 2040
     */
    private function generateDynamicTahunAjaran()
    {
        $currentYear = (int) date('Y');

        // Determine base year (start of 5-year range)
        // Formula: floor(currentYear / 5) * 5
        // Examples:
        // - 2025-2029 → baseYear = 2025
        // - 2030-2034 → baseYear = 2030
        // - 2035-2039 → baseYear = 2035
        $baseYear = floor($currentYear / 5) * 5;

        // Generate 6 years (current 5-year range + 1 next year)
        $tahunList = [];
        for ($i = 0; $i <= 5; $i++) {
            $year = $baseYear + $i;
            $tahunList[] = [
                'id_thn_ajaran' => (string) $year,
                'nm_thn_ajaran' => (string) $year
            ];
        }

        Log::info('Generated dynamic tahun ajaran', [
            'current_year' => $currentYear,
            'base_year' => $baseYear,
            'range' => $baseYear . ' - ' . ($baseYear + 5),
            'years_generated' => array_column($tahunList, 'id_thn_ajaran')
        ]);

        return $tahunList;
    }
}
