<?php

namespace App\Http\Controllers\GKM;

use App\Http\Controllers\Controller;
use App\Models\RPS;
use App\Models\Materi;
use App\Models\User;
use App\Models\Dosen;
use App\Models\LogEmail;
use App\Mail\ReminderRPSMail;
use App\Services\AIAgentService;
use App\Helpers\EmailHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MonitoringRPSController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            // Ambil data tahun ajaran dari API (dengan cache)
            $apiService = new \App\Services\ExternalAPIService();
            
            // Cache tahun ajaran selama 10 menit - dengan fallback jika API gagal
            $tahunAjaranList = \Cache::remember('tahun_ajaran_list', 600, function() use ($apiService) {
                $result = $apiService->getTahunAjaran();
                // Jika API gagal atau kosong, return default
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

            // Get filter values
            $selectedSemester = $request->input('semester', '');
            $selectedTahunAjaran = $request->input('tahun_ajaran', '');

            // Check if filter is applied
            $filterApplied = !empty($selectedSemester) && !empty($selectedTahunAjaran);

            // If no filter applied, return empty pagination
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
                    'selectedTahunAjaran' => $selectedTahunAjaran
                ]);
            }

        // Log filter values for debugging
        \Log::info('MonitoringRPS Filter', [
            'semester' => $selectedSemester,
            'tahun_ajaran' => $selectedTahunAjaran,
            'user_prodi' => $user->prodi ? $user->prodi->kode_prodi : 'N/A'
        ]);

        // Map prodi_id berdasarkan kode prodi user yang login
        $prodiKode = $user->prodi ? $user->prodi->kode_prodi : 'TRPL';
        
        $prodiIdMap = [
            'TRPL' => 4,  // DIV Teknologi Rekayasa Perangkat Lunak
            'TI' => 1,    // DIII Teknologi Informasi
            'NM' => 3,    // DIII Teknologi Komputer
        ];

        $prodiId = $prodiIdMap[$prodiKode] ?? 4; // Default TRPL

        // Cache key untuk data monitoring RPS - PENTING: Include semester di cache key
        $cacheKey = "monitoring_rps_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}";
        
        // Cache selama 10 menit (600 detik)
        $matkulList = \Cache::remember($cacheKey, 600, function() use ($apiService, $prodiId, $selectedSemester, $selectedTahunAjaran) {
            $matkulList = [];
            
            try {
                // 1. Ambil data matakuliah dari API (dengan cache per prodi/semester/ta)
                $matkulData = \Cache::remember(
                    "matkul_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}",
                    600, // 10 menit
                    function() use ($apiService, $prodiId, $selectedSemester, $selectedTahunAjaran) {
                        return $apiService->getMatkulByProdiSemTa($prodiId, $selectedSemester, $selectedTahunAjaran);
                    }
                );
                
                if (empty($matkulData)) {
                    \Log::warning('No matakuliah data from API', [
                        'prodi_id' => $prodiId,
                        'semester' => $selectedSemester,
                        'tahun_ajaran' => $selectedTahunAjaran
                    ]);
                    return [];
                }
            } catch (\Exception $e) {
                \Log::error('Failed to get matakuliah data', [
                    'error' => $e->getMessage(),
                    'prodi_id' => $prodiId,
                    'semester' => $selectedSemester,
                    'tahun_ajaran' => $selectedTahunAjaran
                ]);
                return [];
            }
            
            // 2. Ambil dosen yang sudah ter-filter dari API (dengan cache per prodi)
            // OPTIMASI: Hanya ambil dosen dari prodi yang sedang login
            $dosenApiList = \Cache::remember("dosen_filtered_{$prodiId}", 600, function() use ($apiService, $prodiId) {
                $allDosen = $apiService->getFilteredDosen();
                
                // Filter dosen by prodi_id untuk mengurangi beban API
                return array_filter($allDosen, function($dosen) use ($prodiId) {
                    return isset($dosen['prodi_id']) && $dosen['prodi_id'] == $prodiId;
                });
            });
            
            // Log untuk debugging - TAMBAHAN
            \Log::info('MonitoringRPS - Building matkulDosenMap', [
                'total_dosen' => count($dosenApiList),
                'semester' => $selectedSemester,
                'tahun_ajaran' => $selectedTahunAjaran,
                'prodi_id' => $prodiId,
                'prodi_kode' => $prodiKode
            ]);
            
            // Build mapping: kode_mk => [dosen names]
            // Cache mapping untuk menghindari rebuild berulang
            $matkulDosenMap = \Cache::remember(
                "matkul_dosen_map_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}",
                600,
                function() use ($dosenApiList, $apiService, $selectedSemester, $selectedTahunAjaran) {
                    $map = [];
                    $dosenProcessed = 0;
                    
                    // 3. Process dosen - DENGAN CACHE PER DOSEN
                    foreach ($dosenApiList as $dosen) {
                        $pegawaiId = $dosen['pegawai_id'] ?? null;
                        $namaDosen = $dosen['nama'] ?? null;
                        
                        if ($pegawaiId && $namaDosen) {
                            try {
                                // Cache jadwal per dosen (KEY OPTIMIZATION!)
                                $jadwalList = \Cache::remember(
                                    "jadwal_{$pegawaiId}_{$selectedSemester}_{$selectedTahunAjaran}",
                                    600, // 10 menit
                                    function() use ($apiService, $pegawaiId, $selectedSemester, $selectedTahunAjaran) {
                                        return $apiService->getJadwalByDosen($pegawaiId, $selectedSemester, $selectedTahunAjaran);
                                    }
                                );
                                
                                // Map each matkul to this dosen
                                if (!empty($jadwalList)) {
                                    $dosenProcessed++;
                                    foreach ($jadwalList as $jadwal) {
                                        $kodeMk = $jadwal['kode_mk'] ?? null;
                                        if ($kodeMk) {
                                            if (!isset($map[$kodeMk])) {
                                                $map[$kodeMk] = [];
                                            }
                                            if (!in_array($namaDosen, $map[$kodeMk])) {
                                                $map[$kodeMk][] = $namaDosen;
                                            }
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                \Log::warning("Failed to get jadwal for dosen {$namaDosen}: " . $e->getMessage());
                                continue;
                            }
                        }
                    }
                    
                    // Log hasil mapping
                    \Log::info('MonitoringRPS - Mapping completed', [
                        'dosen_with_jadwal' => $dosenProcessed,
                        'matkul_with_dosen' => count($map),
                        'sample_mapping' => array_slice($map, 0, 3, true)
                    ]);
                    
                    return $map;
                }
            );
            
            // 4. Process each matakuliah - DENGAN CACHE PER MATKUL
            foreach ($matkulData as $matkul) {
                $kuliahId = $matkul['kuliah_id'] ?? null;
                $kodeMk = $matkul['kode_mk'] ?? '-';
                
                if ($kuliahId) {
                    try {
                        // Cache monitoring per matkul (KEY OPTIMIZATION!)
                        $monitoring = \Cache::remember(
                            "monitoring_{$kuliahId}_{$selectedSemester}_{$selectedTahunAjaran}",
                            600, // 10 menit
                            function() use ($apiService, $kuliahId, $selectedTahunAjaran, $selectedSemester) {
                                return $apiService->getMonitoringMateri($kuliahId, $selectedTahunAjaran, $selectedSemester);
                            }
                        );
                        
                        // Get dosen pengampu from mapping
                        $dosenPengampu = '-';
                        if (isset($matkulDosenMap[$kodeMk]) && !empty($matkulDosenMap[$kodeMk])) {
                            $dosenPengampu = implode(', ', $matkulDosenMap[$kodeMk]);
                        }
                        
                        // Log untuk debugging dosen
                        if ($dosenPengampu === '-') {
                            \Log::debug('MonitoringRPS - No dosen for matkul', [
                                'kode_mk' => $kodeMk,
                                'kuliah_id' => $kuliahId,
                                'semester' => $selectedSemester
                            ]);
                        }
                        
                        // Determine status RPS dari API monitoring materi
                        $statusRPS = 'BELUM UPLOAD'; // Default
                        if ($monitoring) {
                            $statusRPS = $monitoring['status_file_silabus'] ?? 'BELUM UPLOAD';
                            
                            // Log untuk debugging
                            \Log::debug('RPS Status', [
                                'kode_mk' => $kodeMk,
                                'kuliah_id' => $kuliahId,
                                'semester' => $selectedSemester,
                                'status' => $statusRPS
                            ]);
                        }
                        
                        $matkulList[] = [
                            'kode_mk' => $kodeMk,
                            'nama_matkul' => $matkul['nama_matkul'] ?? '-',
                            'dosen_pengampu' => $dosenPengampu,
                            'status_rps' => $statusRPS,
                            'kuliah_id' => $kuliahId
                        ];
                    } catch (\Exception $e) {
                        \Log::warning("Failed to get monitoring for matkul {$kodeMk}: " . $e->getMessage());
                        
                        // Add matkul with default status
                        $dosenPengampu = '-';
                        if (isset($matkulDosenMap[$kodeMk]) && !empty($matkulDosenMap[$kodeMk])) {
                            $dosenPengampu = implode(', ', $matkulDosenMap[$kodeMk]);
                        }
                        
                        $matkulList[] = [
                            'kode_mk' => $kodeMk,
                            'nama_matkul' => $matkul['nama_matkul'] ?? '-',
                            'dosen_pengampu' => $dosenPengampu,
                            'status_rps' => 'ERROR',
                            'kuliah_id' => $kuliahId
                        ];
                    }
                }
            }
            
            return $matkulList;
        });

        // Log hasil akhir untuk debugging
        $withDosen = 0;
        $withoutDosen = 0;
        foreach ($matkulList as $matkul) {
            if (($matkul['dosen_pengampu'] ?? '-') !== '-') {
                $withDosen++;
            } else {
                $withoutDosen++;
            }
        }
        
        \Log::info('MonitoringRPS - Final result', [
            'total_matkul' => count($matkulList),
            'with_dosen' => $withDosen,
            'without_dosen' => $withoutDosen,
            'semester' => $selectedSemester,
            'tahun_ajaran' => $selectedTahunAjaran,
            'sample_data' => array_slice($matkulList, 0, 3)
        ]);

        // Pagination manual
        $perPage = 15; // Increased from 10 to 15
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
            'tahunAjaranList',
            'selectedSemester',
            'selectedTahunAjaran'
        ));
        
        } catch (\Exception $e) {
            \Log::error('MonitoringRPS Index Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return view dengan data kosong dan pesan error
            return view('gkm.monitoring-rps.index', [
                'user' => Auth::user(),
                'pagination' => new \Illuminate\Pagination\LengthAwarePaginator(
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
                'selectedTahunAjaran' => $request->input('tahun_ajaran', '')
            ])->with('error', 'Terjadi kesalahan saat memuat data. Silakan coba lagi atau hubungi administrator.');
        }
    }

    /**
     * Clear cache for monitoring RPS data
     */
    public function clearCache(Request $request)
    {
        $user = Auth::user();

        // Map prodi_id
        $prodiKode = $user->prodi ? $user->prodi->kode_prodi : 'TRPL';
        $prodiIdMap = [
            'TRPL' => 4,
            'TI' => 1,
            'NM' => 3,
        ];
        $prodiId = $prodiIdMap[$prodiKode] ?? 4;

        // Get filter values
        $selectedSemester = $request->input('semester', '1');
        $selectedTahunAjaran = $request->input('tahun_ajaran', '2020');

        // Clear main cache
        $cacheKey = "monitoring_rps_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}";
        \Cache::forget($cacheKey);
        
        // Clear related caches for better refresh
        \Cache::forget("matkul_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}");
        \Cache::forget('dosen_filtered');
        \Cache::forget('tahun_ajaran_list');
        
        // PENTING: Clear cache untuk monitoring materi per kuliah_id
        // Ambil semua matakuliah untuk clear cache monitoring-nya
        $apiService = new \App\Services\ExternalAPIService();
        $matkulData = $apiService->getMatkulByProdiSemTa($prodiId, $selectedSemester, $selectedTahunAjaran);
        
        if (!empty($matkulData)) {
            foreach ($matkulData as $matkul) {
                $kuliahId = $matkul['kuliah_id'] ?? null;
                if ($kuliahId) {
                    // Clear cache monitoring untuk setiap kuliah_id
                    \Cache::forget("monitoring_{$kuliahId}_{$selectedSemester}_{$selectedTahunAjaran}");
                }
            }
        }
        
        // Clear cache jadwal dosen juga
        $dosenApiList = $apiService->getFilteredDosen();
        foreach ($dosenApiList as $dosen) {
            $pegawaiId = $dosen['pegawai_id'] ?? null;
            if ($pegawaiId) {
                \Cache::forget("jadwal_{$pegawaiId}_{$selectedSemester}_{$selectedTahunAjaran}");
            }
        }
        
        \Log::info('Monitoring RPS cache cleared (including per-kuliah cache)', [
            'prodi_id' => $prodiId,
            'semester' => $selectedSemester,
            'tahun_ajaran' => $selectedTahunAjaran,
            'matkul_count' => count($matkulData ?? [])
        ]);

        return redirect()->route('gkm.monitoring-rps.index', [
            'semester' => $selectedSemester,
            'tahun_ajaran' => $selectedTahunAjaran
        ])->with('cache_cleared', true);
    }


    public function ceklistRPS()
    {
        $user = Auth::user();

        // Filter dosen berdasarkan prodi GKM
        $query = Dosen::with('matakuliah')
            ->where('status', 'aktif');

        // Jika GKM punya prodi_id, filter hanya dosen dari prodi tersebut
        if ($user->prodi_id) {
            $query->where('prodi_id', $user->prodi_id);
        }

        $dosenList = $query->get();

        return view('gkm.monitoring-rps.ceklist', compact('user', 'dosenList'));
    }

    public function generateReminderMessage(Request $request)
    {
        try {
            $request->validate([
                'dosen_ids' => 'required|array',
                'dosen_ids.*' => 'exists:dosen,id',
            ]);

            $dosenIds = $request->dosen_ids;
            $dosenList = Dosen::with('matakuliah')->whereIn('id', $dosenIds)->get();

            if ($dosenList->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada dosen yang dipilih'
                ], 400);
            }

            // Ambil prodi dari user yang login
            $user = Auth::user();
            $userProdi = $user->prodi;

            Log::info('Generating reminder message', [
                'dosen_count' => $dosenList->count(),
                'prodi' => $userProdi ? $userProdi->kode_prodi : 'null'
            ]);

            // Generate pesan reminder menggunakan AI Agent dengan info prodi
            $aiAgent = new AIAgentService();
            $templatePesan = $aiAgent->generateReminderMessage($dosenList, $userProdi, 'rps');

            Log::info('Reminder message generated successfully', [
                'message_length' => strlen($templatePesan)
            ]);

            return response()->json([
                'success' => true,
                'message' => $templatePesan,
                'dosen_count' => $dosenList->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Generate Reminder Message Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'AI Agent error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sendReminder(Request $request)
    {
        $request->validate([
            'dosen_ids' => 'required|array',
            'dosen_ids.*' => 'exists:dosen,id',
            'message' => 'required|string',
            'subject' => 'required|string|max:255',
        ]);

        // Validasi konfigurasi email
        $configValidation = EmailHelper::validateConfig();
        if (!$configValidation['valid']) {
            return redirect()->back()
                ->with('error', 'Konfigurasi email tidak lengkap: ' . implode(', ', $configValidation['errors']))
                ->withInput();
        }

        // Test koneksi SMTP
        if (!EmailHelper::testSmtpConnection()) {
            Log::error('SMTP Connection Test Failed', EmailHelper::getEmailConfig());
            return redirect()->back()
                ->with('error', 'Tidak dapat terhubung ke server email. Periksa koneksi internet dan konfigurasi SMTP.')
                ->withInput();
        }

        try {
            $dosenList = Dosen::whereIn('id', $request->dosen_ids)->get();
            $successCount = 0;
            $failedCount = 0;
            $failedEmails = [];

            // Ambil info prodi dari user yang login
            $user = Auth::user();
            $prodiName = $user->prodi ? $user->prodi->nama_prodi : 'TRPL';
            $prodiKode = $user->prodi ? $user->prodi->kode_prodi : 'TRPL';

            foreach ($dosenList as $dosen) {
                $emailSent = false;
                $lastError = '';
                $maxRetries = 3;

                // Retry mechanism untuk setiap email
                for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                    try {
                        Log::info("Attempting to send email", [
                            'to' => $dosen->kontak_email,
                            'attempt' => $attempt,
                            'max_retries' => $maxRetries
                        ]);

                        // Kirim email dengan info prodi
                        Mail::to($dosen->kontak_email)->send(
                            new ReminderRPSMail(
                                $request->subject,
                                $request->message,
                                $dosen->nama_lengkap,
                                $prodiName,
                                $prodiKode
                            )
                        );

                        $emailSent = true;
                        
                        Log::info("Email sent successfully", [
                            'to' => $dosen->kontak_email,
                            'attempt' => $attempt
                        ]);

                        // Simpan log email jika berhasil
                        LogEmail::create([
                            'reminder_id' => null,
                            'prodi_id' => $user->prodi_id,
                            'penerima_email' => $dosen->kontak_email,
                            'subjek' => $request->subject,
                            'isi_email' => $request->message,
                            'status_pengiriman' => 'success',
                            'tanggal_pengiriman' => now()->toDateString(),
                            'percobaan_kirim' => $attempt,
                        ]);

                        $successCount++;
                        break; // Keluar dari loop retry jika berhasil

                    } catch (\Exception $e) {
                        $lastError = $e->getMessage();
                        
                        Log::error("Email sending failed", [
                            'to' => $dosen->kontak_email,
                            'attempt' => $attempt,
                            'error' => $lastError
                        ]);
                        
                        // Jika bukan percobaan terakhir, tunggu sebentar sebelum retry
                        if ($attempt < $maxRetries) {
                            sleep(2); // Tunggu 2 detik sebelum retry
                        }
                    }
                }

                // Jika semua percobaan gagal
                if (!$emailSent) {
                    LogEmail::create([
                        'reminder_id' => null,
                        'prodi_id' => $user->prodi_id,
                        'penerima_email' => $dosen->kontak_email,
                        'subjek' => $request->subject,
                        'isi_email' => $request->message,
                        'status_pengiriman' => 'failed',
                        'pesan_error' => $lastError,
                        'tanggal_pengiriman' => now()->toDateString(),
                        'percobaan_kirim' => $maxRetries,
                    ]);

                    $failedCount++;
                    $failedEmails[] = $dosen->kontak_email;
                }
            }

            // Response berdasarkan hasil pengiriman
            if ($failedCount > 0 && $successCount > 0) {
                return redirect()->route('gkm.monitoring-rps.index')
                    ->with('warning', "Reminder berhasil dikirim ke {$successCount} dosen, gagal ke {$failedCount} dosen. Email yang gagal: " . implode(', ', $failedEmails));
            } elseif ($failedCount > 0 && $successCount == 0) {
                $errorMsg = "Gagal mengirim reminder ke semua dosen. ";
                $errorMsg .= "Periksa: 1) Koneksi internet, 2) Konfigurasi MAIL_* di .env, 3) App Password Gmail. ";
                $errorMsg .= "Email yang gagal: " . implode(', ', $failedEmails);
                
                return redirect()->back()
                    ->with('error', $errorMsg)
                    ->withInput();
            }

            return redirect()->route('gkm.monitoring-rps.index')
                ->with('success', "Reminder berhasil dikirim ke {$successCount} dosen");
        } catch (\Exception $e) {
            Log::error('Send Reminder Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Gagal mengirim reminder: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function historyReminder($dosenId = null)
    {
        $user = Auth::user();

        // Filter dosen berdasarkan prodi GKM
        $dosenQuery = Dosen::where('status', 'aktif');
        
        if ($user->prodi_id) {
            $dosenQuery->where('prodi_id', $user->prodi_id);
        }
        
        $dosenList = $dosenQuery->get();
        
        // Ambil log email
        $logEmailQuery = LogEmail::query();
        
        // Filter berdasarkan dosen yang dipilih
        if ($dosenId) {
            $dosen = Dosen::find($dosenId);
            if ($dosen) {
                $logEmailQuery->where('penerima_email', $dosen->kontak_email);
            }
        } elseif ($user->prodi_id) {
            // Filter log email hanya untuk dosen di prodi GKM
            $dosenEmails = $dosenList->pluck('kontak_email')->toArray();
            $logEmailQuery->whereIn('penerima_email', $dosenEmails);
        }
        
        $logEmailList = $logEmailQuery->orderBy('created_at', 'desc')->paginate(10);

        return view('gkm.monitoring-rps.history', compact('user', 'logEmailList', 'dosenList', 'dosenId'));
    }
}
