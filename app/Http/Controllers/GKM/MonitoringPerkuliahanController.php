<?php

namespace App\Http\Controllers\GKM;

use App\Http\Controllers\Controller;
use App\Mail\ReminderReviewSoalMail;
use App\Mail\ReminderUploadMateriMail;
use App\Services\ExternalAPIService;
use App\Models\PerkuliahanMonitoringSnapshot;
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
                    $maxMatkul = 20; // Process max 20 matkul to prevent timeout
                    $processedCount = 0;
                    
                    // Get dosen list - FILTERED BY PRODI untuk mengurangi beban API
                    $dosenList = Cache::remember("dosen_prodi_{$prodiId}", 1800, function () use ($apiService, $prodiId) {
                        $allDosen = $apiService->getFilteredDosen();

                        // Filter dosen by prodi_id and limit
                        $filtered = array_filter($allDosen, function ($dosen) use ($prodiId) {
                            return isset($dosen['prodi_id']) && $dosen['prodi_id'] == $prodiId;
                        });
                        
                        // Limit to first 30 dosen to prevent timeout
                        return array_slice($filtered, 0, 30);
                    });

                    Log::info('MonitoringPerkuliahan - Dosen filtered by prodi', [
                        'prodi_id' => $prodiId,
                        'prodi_kode' => $prodiKode,
                        'total_dosen' => count($dosenList),
                    ]);

                    // Build dosen mapping - HANYA PROSES DOSEN DARI PRODI INI
                    $matkulDosenMap = Cache::remember(
                        "matkul_dosen_map_perkuliahan_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}",
                        1800, // Increase cache time
                        function () use ($dosenList, $apiService, $selectedSemester, $selectedTahunAjaran) {
                            $map = [];
                            $dosenProcessed = 0;
                            $maxDosenProcess = 20; // Limit dosen processing

                            foreach ($dosenList as $dosen) {
                                if ($dosenProcessed >= $maxDosenProcess) break;
                                
                                $pegawaiId = $dosen['pegawai_id'] ?? null;
                                $namaDosen = $dosen['nama'] ?? null;

                                if ($pegawaiId && $namaDosen) {
                                    try {
                                        $jadwalList = Cache::remember(
                                            "jadwal_{$pegawaiId}_{$selectedSemester}_{$selectedTahunAjaran}",
                                            1800,
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
                                                    // 🔥 SIMPAN SEBAGAI ARRAY DENGAN pegawai_id dan nama
                                                    $exists = false;
                                                    foreach ($map[$kodeMk] as $existing) {
                                                        if ($existing['pegawai_id'] === $pegawaiId) {
                                                            $exists = true;
                                                            break;
                                                        }
                                                    }
                                                    if (!$exists) {
                                                        $map[$kodeMk][] = [
                                                            'pegawai_id' => $pegawaiId,
                                                            'nama' => $namaDosen
                                                        ];
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

    public function reminderMateri()
    {
        $user = Auth::user();

        // 🔥 AMBIL DATA DARI SNAPSHOT DATABASE (seperti RPS)
        $prodiKode = $user->prodi ? $user->prodi->kode_prodi : 'TRPL';
        $prodiIdMap = [
            'TRPL' => 4,
            'TI' => 1,
            'NM' => 3,
        ];
        $prodiId = $prodiIdMap[$prodiKode] ?? 4;

        // Ambil dosen yang belum upload dari snapshot untuk SEMUA semester dan tingkat
        $dosenBelumUpload = PerkuliahanMonitoringSnapshot::with('dosen')
            ->where('prodi_id', $prodiId)
            ->where('status_upload', 'BELUM UPLOAD')
            ->whereHas('dosen', function($query) {
                $query->whereNotNull('email')
                      ->where('email', '!=', '');
            })
            ->get()
            ->map(function($snapshot) {
                return [
                    'pegawai_id' => $snapshot->pegawai_id,
                    'nama_lengkap' => $snapshot->dosen->nama ?? '-',
                    'kontak_email' => $snapshot->dosen->email ?? '-',
                    'nama_matkul' => $snapshot->nama_matkul,
                    'kode_mk' => $snapshot->kode_mk,
                    'semester' => $snapshot->semester,
                    'tahun_ajaran' => $snapshot->tahun_ajaran,
                    'tingkat' => $snapshot->tingkat,
                    'jenis' => $snapshot->jenis_materi
                ];
            })
            ->toArray();

        return view('gkm.monitoring-perkuliahan.materi', [
            'user' => $user,
            'dosenMateri' => $dosenBelumUpload,
        ]);
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
            // Process Materi Teori
            foreach ($materiTeori as $data) {
                if (empty($data['kode']) || !isset($matkulDosenMap[$data['kode']])) {
                    continue;
                }

                $dosenList = $matkulDosenMap[$data['kode']];
                $tingkat = substr($data['kode'], 3, 1);

                // Cek apakah ada yang belum upload (status 0)
                $hasBelumUpload = in_array(0, $data['weeks']);

                if ($hasBelumUpload) {
                    foreach ($dosenList as $dosenInfo) {
                        PerkuliahanMonitoringSnapshot::updateOrCreate(
                            [
                                'pegawai_id' => $dosenInfo['pegawai_id'],
                                'kode_mk' => $data['kode'],
                                'semester' => $semester,
                                'tahun_ajaran' => $tahunAjaran,
                                'jenis_materi' => 'Materi Teori',
                            ],
                            [
                                'prodi_kode' => $prodiKode,
                                'prodi_id' => $prodiId,
                                'kuliah_id' => $data['kuliah_id'] ?? null,
                                'nama_matkul' => $data['nama'],
                                'tingkat' => $tingkat,
                                'status_upload' => 'BELUM UPLOAD',
                                'reminder_sent' => false,
                                'raw_data' => $data,
                            ]
                        );
                    }
                }
            }

            // Process Materi Praktikum
            foreach ($materiPraktikum as $data) {
                if (empty($data['kode']) || !isset($matkulDosenMap[$data['kode']])) {
                    continue;
                }

                $dosenList = $matkulDosenMap[$data['kode']];
                $tingkat = substr($data['kode'], 3, 1);

                // Cek apakah ada yang belum upload (status 0)
                $hasBelumUpload = in_array(0, $data['weeks']);

                if ($hasBelumUpload) {
                    foreach ($dosenList as $dosenInfo) {
                        PerkuliahanMonitoringSnapshot::updateOrCreate(
                            [
                                'pegawai_id' => $dosenInfo['pegawai_id'],
                                'kode_mk' => $data['kode'],
                                'semester' => $semester,
                                'tahun_ajaran' => $tahunAjaran,
                                'jenis_materi' => 'Materi Praktikum',
                            ],
                            [
                                'prodi_kode' => $prodiKode,
                                'prodi_id' => $prodiId,
                                'kuliah_id' => $data['kuliah_id'] ?? null,
                                'nama_matkul' => $data['nama'],
                                'tingkat' => $tingkat,
                                'status_upload' => 'BELUM UPLOAD',
                                'reminder_sent' => false,
                                'raw_data' => $data,
                            ]
                        );
                    }
                }
            }

            Log::info('Perkuliahan snapshot saved', [
                'prodi_id' => $prodiId,
                'semester' => $semester,
                'tahun_ajaran' => $tahunAjaran,
                'teori_count' => count($materiTeori),
                'praktikum_count' => count($materiPraktikum)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save perkuliahan snapshot', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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

            foreach ($dosenList as $dosen) {
                try {
                    $email = $dosen->email ?? $dosen->kontak_email;
                    if (!$email) {
                        $errors[] = "Email tidak ditemukan untuk {$dosen->nama}";
                        continue;
                    }

                    Mail::to($email)->send(new ReminderUploadMateriMail(
                        $dosen->nama,
                        $request->pesan
                    ));
                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = "Gagal kirim ke {$dosen->nama}: " . $e->getMessage();
                    Log::error('Failed to send materi reminder', [
                        'pegawai_id' => $dosen->pegawai_id,
                        'email' => $email ?? 'N/A',
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
            Log::error('Failed to send materi reminder', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Gagal mengirim reminder: ' . $e->getMessage());
        }
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
