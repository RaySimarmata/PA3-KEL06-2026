<?php

namespace App\Http\Controllers\GKM;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\RPS;
use App\Models\Materi;
use App\Models\User;
use App\Models\Dosen;
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
        // TAHUN AJARAN (CACHE)
        // =========================
        $tahunAjaranList = \Cache::remember('tahun_ajaran_list', 600, function () use ($apiService) {
            $result = $apiService->getTahunAjaran();

            return !empty($result) ? $result : [
                ['id_thn_ajaran' => '2020', 'nm_thn_ajaran' => '2020'],
                ['id_thn_ajaran' => '2021', 'nm_thn_ajaran' => '2021'],
                ['id_thn_ajaran' => '2022', 'nm_thn_ajaran' => '2022'],
                ['id_thn_ajaran' => '2023', 'nm_thn_ajaran' => '2023'],
                ['id_thn_ajaran' => '2024', 'nm_thn_ajaran' => '2024'],
            ];
        });

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

        $selectedTingkat = $request->input('tingkat', 1);

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
                $this->saveSnapshotToDB(
                    $matkulList,
                    $prodiId,
                    $prodiKode,
                    $selectedSemester,
                    $selectedTahunAjaran
                );

                \Cache::put($cacheKey, $matkulList, 1800);
            }
        }

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
            'periodeAktif'
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
    $jadwalDosenList = \Cache::remember(
        "jadwal_dosen_{$selectedSemester}_{$selectedTahunAjaran}",
        1800,
        function () use ($selectedSemester, $selectedTahunAjaran) {

            return \DB::table('jadwal_dosen as jd')
                ->leftJoin('dosenn as d', 'jd.pegawai_id', '=', 'd.pegawai_id')
                ->where('jd.semester', $selectedSemester)
                ->where('jd.tahun_ajaran', $selectedTahunAjaran)
                ->select(
                    'jd.kode_mk',
                    'jd.pegawai_id',
                    'jd.is_manual',
                    'd.nama'
                )
                ->get();
        }
    );

    /*
    |----------------------------------------
    | 3. MAP KODE_MK -> DOSEN
    |----------------------------------------
    */
    $matkulDosenMap = [];

    foreach ($jadwalDosenList as $item) {

        $kodeMk = $item->kode_mk;

        if (!isset($matkulDosenMap[$kodeMk])) {
            $matkulDosenMap[$kodeMk] = [];
        }

        $matkulDosenMap[$kodeMk][] = [
            'pegawai_id' => $item->pegawai_id,
            'nama'       => $item->nama ?? '-',
            'is_manual'  => $item->is_manual
        ];
    }

    /*
    |----------------------------------------
    | 4. LOOP MATKUL
    |----------------------------------------
    */
    foreach ($matkulData as $matkul) {

        $kuliahId = $matkul['kuliah_id'] ?? null;
        $kodeMk   = (string) ($matkul['kode_mk'] ?? '');

        if (!$kuliahId || strlen($kodeMk) < 5) continue;

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

            if (isset($matkulDosenMap[$kodeMk])) {

                $uniqueDosen = collect($matkulDosenMap[$kodeMk])
    ->unique('pegawai_id')
    ->values();

$pegawaiIds = $uniqueDosen->pluck('pegawai_id')->toArray();

$dosenNama = $uniqueDosen->pluck('nama')->implode(', ');
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
}    /**
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
            $selectedTahunAjaran = $request->input('tahun_ajaran', '2020');
            
            // Clear main cache
            $cacheKey = "monitoring_rps_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}";
            \Cache::forget($cacheKey);
            
            // Clear related caches
            \Cache::forget("matkul_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}");
            \Cache::forget("matkul_dosen_map_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}");
            
            \Log::info('Cache cleared manually', [
                'prodi_id' => $prodiId,
                'semester' => $selectedSemester,
                'tahun_ajaran' => $selectedTahunAjaran
            ]);
            
            return redirect()->route('gkm.monitoring-rps.index', [
                'semester' => $selectedSemester,
                'tahun_ajaran' => $selectedTahunAjaran
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

    $email = $dosen->email ?? $dosen->kontak_email;
    $nomorTelepon = $dosen->nomor_telepon ?? null;

    try {

        // =========================
        // EMAIL
        // =========================
        if (!empty($email)) {

            Mail::to($email)->send(
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
            'penerima_email' => $email,
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
            'penerima_email' => $email,
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
            'email' => $email,
            'nomor_telepon' => $nomorTelepon,
            'error' => $e->getMessage()
        ]);
    }
}

        RpsMonitoringSnapshot::where('pegawai_id', $dosen->pegawai_id)
    ->where('status_rps', 'BELUM UPLOAD')
    ->update([
        'reminder_sent' => true,
        'updated_at' => now()
    ]);

        return redirect()->route('gkm.monitoring-rps.index')
            ->with(
                $failedCount > 0 ? 'warning' : 'success',
                "Berhasil: {$successCount}, Gagal: {$failedCount}"
            );

    } catch (\Exception $e) {
        return redirect()->back()
            ->with('error', 'Gagal mengirim reminder: ' . $e->getMessage());
    }
}

    public function historyReminder($dosenId = null)
    {
        $user = Auth::user();

        // Ambil dosen untuk filter
        $dosenList = Dosenn::get();
        
        // Ambil log email
        $logEmailList = LogEmail::when($dosenId, function ($query) use ($dosenId) {
                // Cari dosen berdasarkan ID
                $dosen = Dosen::find($dosenId);
                if ($dosen) {
                    $query->where('penerima_email', $dosen->kontak_email);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('gkm.monitoring-rps.history', compact('user', 'logEmailList', 'dosenList', 'dosenId'));
    }
}
