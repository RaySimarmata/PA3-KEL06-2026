<?php

namespace App\Http\Controllers\GKM;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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

class MonitoringRPSController extends Controller
{
    public function index(Request $request)
{
    // Increase PHP execution time for this specific request
    set_time_limit(180); // 3 minutes
    
    try {
        $user = Auth::user();

        // Ambil data tahun ajaran dari API (dengan cache)
        $apiService = new \App\Services\ExternalAPIService();
        
        
        // Cache tahun ajaran selama 10 menit - dengan fallback jika API gagal
        $tahunAjaranList = \Cache::remember('tahun_ajaran_list', 600, function() use ($apiService) {
            $result = $apiService->getTahunAjaran();

            if (empty($result)) {
                return [
                    ['id_thn_ajaran' => '2020', 'nm_thn_ajaran' => '2020'],
                    ['id_thn_ajaran' => '2021', 'nm_thn_ajaran' => '2021'],
                    ['id_thn_ajaran' => '2022', 'nm_thn_ajaran' => '2022'],
                    ['id_thn_ajaran' => '2023', 'nm_thn_ajaran' => '2023'],
                    ['id_thn_ajaran' => '2024', 'nm_thn_ajaran' => '2024'],
                ];
            }

            return $result;
        });

        // =========================
        // FILTER INPUT
        // =========================
        $selectedSemester = $request->input('semester', '');
        $selectedTahunAjaran = $request->input('tahun_ajaran', '');
        $selectedTingkat = $request->input('tingkat', ''); // 🔥 TAMBAHAN

        // Check if filter is applied
        $filterApplied = !empty($selectedSemester) && !empty($selectedTahunAjaran);

        if (!$filterApplied) {
            return view('gkm.monitoring-rps.index', [
                'user' => $user,
                'pagination' => new \Illuminate\Pagination\LengthAwarePaginator(
                    [],
                    0,
                    15,
                    1,
                    ['path' => $request->url(), 'query' => $request->query()]
                ),
                'tahunAjaranList' => $tahunAjaranList,
                'selectedSemester' => $selectedSemester,
                'selectedTahunAjaran' => $selectedTahunAjaran,
                'selectedTingkat' => $selectedTingkat // 🔥
            ]);
        }

        // Log filter
        \Log::info('MonitoringRPS Filter', [
            'semester' => $selectedSemester,
            'tahun_ajaran' => $selectedTahunAjaran,
            'tingkat' => $selectedTingkat,
            'user_prodi' => $user->prodi ? $user->prodi->kode_prodi : 'N/A'
        ]);

        // =========================
        // PRODI MAPPING
        // =========================
        $prodiKode = $user->prodi ? $user->prodi->kode_prodi : 'TRPL';

        $prodiIdMap = [
            'TRPL' => 4,
            'TI'   => 1,
            'NM'   => 3,
        ];

        $prodiId = $prodiIdMap[$prodiKode] ?? 4;

        // =========================
        // CACHE KEY (UPDATED)
        // =========================
        $cacheKey = "monitoring_rps_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}_{$selectedTingkat}";

        $matkulList = \Cache::get($cacheKey);

        $forceRefreshDB = $request->input('refresh_db', false);

        // 🔥 Check if cache is being built by another process
        $buildingCacheKey = "{$cacheKey}_building";
        $isCacheBuilding = \Cache::get($buildingCacheKey, false);

        // If cache is being built, show loading message
        if ($isCacheBuilding && $matkulList === null) {
            return view('gkm.monitoring-rps.index', [
                'user' => $user,
                'pagination' => new \Illuminate\Pagination\LengthAwarePaginator(
                    [],
                    0,
                    15,
                    1,
                    ['path' => $request->url(), 'query' => $request->query()]
                ),
                'tahunAjaranList' => $tahunAjaranList,
                'selectedSemester' => $selectedSemester,
                'selectedTahunAjaran' => $selectedTahunAjaran,
                'selectedTingkat' => $selectedTingkat,
                'cacheBuilding' => true
            ])->with('info', 'Data sedang dimuat dari API. Silakan refresh halaman dalam beberapa saat.');
        }

        // 🔥 TAMBAH INI
        if ($matkulList === null || $forceRefreshDB) {

            // Mark that cache is being built
            \Cache::put($buildingCacheKey, true, 300); // 5 minutes lock

            try {
                $matkulList = $this->buildMonitoringData(
                    $apiService,
                    $prodiId,
                    $selectedSemester,
                    $selectedTahunAjaran,
                    $prodiKode,
                    $selectedTingkat
                );

                if (!empty($matkulList)) {

                    // 🔥 DEBUG WAJIB
                    \Log::info('MASUK SAVE SNAPSHOT', [
                        'count' => count($matkulList)
                    ]);

                    $this->saveSnapshotToDB(
                        $matkulList,
                        $prodiId,
                        $prodiKode,
                        $selectedSemester,
                        $selectedTahunAjaran
                    );

                    \Cache::put($cacheKey, $matkulList, 1800);
                }
            } catch (\Exception $e) {
                \Log::error('Error building monitoring data', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Return partial data or empty if complete failure
                $matkulList = [];
            } finally {
                // Always remove the building lock
                \Cache::forget($buildingCacheKey);
            }
        }

        // Validasi cache
        if ($matkulList !== null && (!is_array($matkulList) || empty($matkulList))) {
            \Log::warning('Cache invalid, rebuilding...', [
                'cache_key' => $cacheKey
            ]);
            \Cache::forget($cacheKey);
            $matkulList = null;
        }

        // Build cache jika kosong
        if ($matkulList === null) {
            $matkulList = $this->buildMonitoringData(
                $apiService,
                $prodiId,
                $selectedSemester,
                $selectedTahunAjaran,
                $prodiKode,
                $selectedTingkat // 🔥 TAMBAHAN
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

                \Log::info('Cache set successfully', [
                    'cache_key' => $cacheKey,
                    'items_count' => count($matkulList)
                ]);
            }
        }

        // =========================
        // DEBUG LOG
        // =========================
        \Log::info('MonitoringRPS Final Result', [
            'total_matkul' => count($matkulList),
            'semester' => $selectedSemester,
            'tahun_ajaran' => $selectedTahunAjaran,
            'tingkat' => $selectedTingkat
        ]);

        // =========================
        // PAGINATION
        // =========================
        $perPage = 15;
        $currentPage = $request->input('page', 1);
        $offset = ($currentPage - 1) * $perPage;

        $totalItems = count($matkulList);
        $matkulPaginated = array_slice($matkulList, $offset, $perPage);

        $pagination = new \Illuminate\Pagination\LengthAwarePaginator(
            $matkulPaginated,
            $totalItems,
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
            'selectedTingkat' // 🔥 TAMBAHAN
        ))->with('noDataFromAPI', empty($matkulList));

    } catch (\Exception $e) {
        \Log::error('MonitoringRPS Index Error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return view('gkm.monitoring-rps.index', [
            'user' => Auth::user(),
            'pagination' => new \Illuminate\Illuminate\Pagination\LengthAwarePaginator(
                [],
                0,
                15,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            ),
            'tahunAjaranList' => [
                ['id_thn_ajaran' => '2020', 'nm_thn_ajaran' => '2020'],
                ['id_thn_ajaran' => '2021', 'nm_thn_ajaran' => '2021'],
                ['id_thn_ajaran' => '2022', 'nm_thn_ajaran' => '2022'],
                ['id_thn_ajaran' => '2023', 'nm_thn_ajaran' => '2023'],
                ['id_thn_ajaran' => '2024', 'nm_thn_ajaran' => '2024'],
            ],
            'selectedSemester' => $request->input('semester', ''),
            'selectedTahunAjaran' => $request->input('tahun_ajaran', ''),
            'selectedTingkat' => $request->input('tingkat', '') // 🔥
        ])->with('error', 'Terjadi kesalahan saat memuat data.');
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
        // 1. Ambil matkul
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

    // 2. Ambil dosen
    $dosenApiList = \Cache::remember("dosen_all", 1800, function () {
        return Dosenn::whereIn('prodi_id', [1, 3, 4, 10, 8])
            ->get(['pegawai_id', 'nama'])
            ->toArray();
    });

    // 3. Mapping kode_mk -> dosen (ID + nama)
    $matkulDosenMap = \Cache::remember(
        "matkul_dosen_map_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}",
        1800,
        function () use ($dosenApiList, $apiService, $selectedSemester, $selectedTahunAjaran) {

            $map = [];
            $processed = 0;
            $maxDosen = 80;

            foreach ($dosenApiList as $dosen) {

                if ($processed >= $maxDosen) break;

                $pegawaiId = $dosen['pegawai_id'] ?? null;
                $nama = $dosen['nama'] ?? null;

                if (!$pegawaiId || !$nama) continue;

                try {
                    // Cache individual jadwal for 30 minutes (1800 seconds)
                    $jadwalList = \Cache::remember(
                        "jadwal_{$pegawaiId}_{$selectedSemester}_{$selectedTahunAjaran}",
                        1800,
                        function () use ($apiService, $pegawaiId, $selectedSemester, $selectedTahunAjaran) {
                            try {
                                return $apiService->getJadwalByDosen($pegawaiId, $selectedSemester, $selectedTahunAjaran);
                            } catch (\Exception $e) {
                                \Log::warning("API timeout for dosen: {$pegawaiId}", ['error' => $e->getMessage()]);
                                return []; // Return empty array on timeout
                            }
                        }
                    );

                    foreach ($jadwalList ?? [] as $jadwal) {
                        $kodeMk = $jadwal['kode_mk'] ?? null;

                        if (!$kodeMk) continue;

                        if (!isset($map[$kodeMk])) {
                            $map[$kodeMk] = [];
                        }

                        // hindari duplikat pegawai_id
                        if (!collect($map[$kodeMk])->pluck('pegawai_id')->contains($pegawaiId)) {
                            $map[$kodeMk][] = [
                                'pegawai_id' => $pegawaiId,
                                'nama' => $nama
                            ];
                        }
                    }

                    $processed++;

                } catch (\Exception $e) {
                    \Log::warning("Jadwal dosen gagal: {$pegawaiId}", ['error' => $e->getMessage()]);
                    // Continue processing other dosen even if one fails
                    continue;
                }
            }

            return $map;
        }
    );

    // 4. Loop matkul
    foreach ($matkulData as $matkul) {

        $kuliahId = $matkul['kuliah_id'] ?? null;
        $kodeMk = (string) ($matkul['kode_mk'] ?? '');

        if (!$kuliahId || strlen($kodeMk) < 5) continue;

        // filter tingkat
        $tingkatMk = substr($kodeMk, 3, 1);
        if (!empty($selectedTingkat) && $tingkatMk != $selectedTingkat) {
            continue;
        }

        try {
            $monitoring = \Cache::remember(
                "monitoring_{$kuliahId}_{$selectedSemester}_{$selectedTahunAjaran}",
                1800,
                function () use ($apiService, $kuliahId, $selectedTahunAjaran, $selectedSemester) {
                    return $apiService->getMonitoringMateri($kuliahId, $selectedTahunAjaran, $selectedSemester);
                }
            );

            // dosen
            $pegawaiIds = [];
            $dosenNama = '-';

            if (isset($matkulDosenMap[$kodeMk])) {
                $pegawaiIds = array_column($matkulDosenMap[$kodeMk], 'pegawai_id');
                $dosenNama = implode(', ', array_column($matkulDosenMap[$kodeMk], 'nama'));
            }

            $matkulList[] = [
                'kode_mk' => $kodeMk,
                'nama_matkul' => $matkul['nama_matkul'] ?? '-',
                'dosen_pengampu' => $dosenNama,
                'pegawai_ids' => $pegawaiIds,
                'status_rps' => $monitoring['status_file_silabus'] ?? 'BELUM UPLOAD',
                'kuliah_id' => $kuliahId
            ];

        } catch (\Exception $e) {

            $matkulList[] = [
                'kode_mk' => $kodeMk,
                'nama_matkul' => $matkul['nama_matkul'] ?? '-',
                'dosen_pengampu' => '-',
                'pegawai_ids' => [],
                'status_rps' => 'ERROR',
                'kuliah_id' => $kuliahId
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
                $pegawaiIds = [null];
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
    $search = $request->input('search');

    $dosenList = DB::table('dosenn as d')
        ->leftJoin('rps_monitoring_snapshots as r', 'd.pegawai_id', '=', 'r.pegawai_id')
        ->where('r.prodi_id', 4)

        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('d.nama', 'like', "%{$search}%")
                  ->orWhere('r.nama_matkul', 'like', "%{$search}%");
            });
        })

        ->select(
            'd.pegawai_id as id',
            'd.pegawai_id',
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

            if (!$email) {
                $failedCount++;
                continue;
            }

            try {
                Mail::to($email)->send(
                    new ReminderRPSMail(
                        $request->subject,
                        $request->message,
                        $dosen->nama ?? $dosen->nama_lengkap
                    )
                );

                LogEmail::create([
                    'reminder_id' => null,
                    'penerima_email' => $email,
                    'subjek' => $request->subject,
                    'isi_email' => $request->message,
                    'status_pengiriman' => 'success',
                    'tanggal_pengiriman' => now(),
                    'percobaan_kirim' => 1,
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
            }
        }

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
