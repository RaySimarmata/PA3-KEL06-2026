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
        
        // OPTIMASI: Cache lebih lama (30 menit) untuk mengurangi beban API
        // PERBAIKAN: Validasi cache - jika cache kosong atau corrupt, rebuild
        $matkulList = \Cache::get($cacheKey);
        
        // Validasi cache: jika cache ada tapi kosong atau tidak valid, hapus dan rebuild
        if ($matkulList !== null && (!is_array($matkulList) || empty($matkulList))) {
            \Log::warning('Cache exists but empty or invalid, rebuilding...', [
                'cache_key' => $cacheKey,
                'cache_type' => gettype($matkulList),
                'cache_count' => is_array($matkulList) ? count($matkulList) : 'N/A'
            ]);
            \Cache::forget($cacheKey);
            $matkulList = null;
        }
        
        // Build cache jika tidak ada atau sudah di-clear
        if ($matkulList === null) {
            $matkulList = $this->buildMonitoringData($apiService, $prodiId, $selectedSemester, $selectedTahunAjaran, $prodiKode);
            
            // Hanya set cache jika data valid (tidak kosong)
            if (!empty($matkulList)) {
                \Cache::put($cacheKey, $matkulList, 1800); // 30 menit
                \Log::info('Cache set successfully', [
                    'cache_key' => $cacheKey,
                    'items_count' => count($matkulList)
                ]);
            } else {
                \Log::warning('Data empty, not caching', [
                    'cache_key' => $cacheKey
                ]);
            }
        }

        // Log hasil akhir untuk debugging
        \Log::info('MonitoringRPS - Final result', [
            'total_matkul' => count($matkulList),
            'semester' => $selectedSemester,
            'tahun_ajaran' => $selectedTahunAjaran,
            'cache_duration' => '30 minutes',
            'sample_data' => array_slice($matkulList, 0, 2)
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
        ))->with('noDataFromAPI', empty($matkulList));
        
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
     * Build monitoring data from API
     */
    private function buildMonitoringData($apiService, $prodiId, $selectedSemester, $selectedTahunAjaran, $prodiKode)
    {
        $matkulList = [];
        
        try {
            // 1. Ambil data matakuliah dari API (dengan cache per prodi/semester/ta)
            $matkulData = \Cache::remember(
                "matkul_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}",
                1800, // 30 menit - lebih lama
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
                return []; // Return empty array, not null
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
        
        // 2. OPTIMASI: Ambil semua dosen dari prodi 1, 3, 4 untuk mapping
        // Tapi hanya proses yang mengajar di prodi user login
        // Cache dosen list dengan waktu lebih lama
        $dosenApiList = \Cache::remember("dosen_all_ti_nm_trpl", 1800, function() use ($apiService) {
            // Ambil SEMUA dosen dengan prodi_id 1, 3, 4 (tidak filter by prodi user)
            // Karena dosen dari prodi lain bisa mengajar di prodi user
            $allDosen = $apiService->getDosenByProdiIds([1, 3, 4]);
            
            // BATASI 150 DOSEN untuk menghindari timeout (tingkatkan dari 100)
            return array_slice($allDosen, 0, 150);
        });
        
        \Log::info('Processing dosen for mapping', [
            'total_dosen' => count($dosenApiList),
            'prodi_id' => $prodiId
        ]);
        
        // Build mapping: kode_mk => [dosen names]
        // Hanya map dosen yang mengajar di prodi user login
        // Cache mapping dengan waktu lebih lama
        $matkulDosenMap = \Cache::remember(
            "matkul_dosen_map_{$prodiId}_{$selectedSemester}_{$selectedTahunAjaran}",
            1800, // 30 menit
            function() use ($dosenApiList, $apiService, $selectedSemester, $selectedTahunAjaran, $prodiId) {
                $map = [];
                $processedCount = 0;
                $maxDosen = 80; // TINGKATKAN dari 50 ke 80 dosen untuk mapping
                
                // Process dosen dengan cache per dosen
                foreach ($dosenApiList as $dosen) {
                    if ($processedCount >= $maxDosen) {
                        break; // Stop setelah 80 dosen
                    }
                    
                    $pegawaiId = $dosen['pegawai_id'] ?? null;
                    $namaDosen = $dosen['nama'] ?? null;
                    
                    if ($pegawaiId && $namaDosen) {
                        try {
                            // Cache jadwal per dosen dengan waktu lebih lama
                            $jadwalList = \Cache::remember(
                                "jadwal_{$pegawaiId}_{$selectedSemester}_{$selectedTahunAjaran}",
                                1800, // 30 menit
                                function() use ($apiService, $pegawaiId, $selectedSemester, $selectedTahunAjaran) {
                                    return $apiService->getJadwalByDosen($pegawaiId, $selectedSemester, $selectedTahunAjaran);
                                }
                            );
                            
                            // Map each matkul to this dosen
                            if (!empty($jadwalList)) {
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
                            
                            $processedCount++;
                        } catch (\Exception $e) {
                            // Skip dosen yang error
                            \Log::warning("Failed to get jadwal for dosen {$pegawaiId}: " . $e->getMessage());
                            continue;
                        }
                    }
                }
                
                \Log::info('Dosen mapping completed', [
                    'processed_dosen' => $processedCount,
                    'mapped_matkul' => count($map)
                ]);
                
                return $map;
            }
        );
        
        // 3. Process each matakuliah dengan dosen dan RPS status
        foreach ($matkulData as $matkul) {
            $kuliahId = $matkul['kuliah_id'] ?? null;
            $kodeMk = $matkul['kode_mk'] ?? '-';
            
            if ($kuliahId) {
                try {
                    // Cache monitoring per matkul dengan waktu lebih lama
                    $monitoring = \Cache::remember(
                        "monitoring_{$kuliahId}_{$selectedSemester}_{$selectedTahunAjaran}",
                        1800, // 30 menit
                        function() use ($apiService, $kuliahId, $selectedTahunAjaran, $selectedSemester) {
                            return $apiService->getMonitoringMateri($kuliahId, $selectedTahunAjaran, $selectedSemester);
                        }
                    );
                    
                    // Get dosen pengampu from mapping
                    $dosenPengampu = '-';
                    if (isset($matkulDosenMap[$kodeMk]) && !empty($matkulDosenMap[$kodeMk])) {
                        $dosenPengampu = implode(', ', $matkulDosenMap[$kodeMk]);
                    }
                    
                    // Determine status RPS dari API monitoring materi
                    $statusRPS = 'BELUM UPLOAD'; // Default
                    if ($monitoring) {
                        $statusRPS = $monitoring['status_file_silabus'] ?? 'BELUM UPLOAD';
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
                    
                    // Get dosen even if monitoring fails
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

    public function ceklistRPS()
    {
        $user = Auth::user();

        // Ambil dosen yang belum upload RPS
        $dosenList = Dosen::with('matakuliah')
            ->where('status', 'aktif')
            ->get();

        return view('gkm.monitoring-rps.ceklist', compact('user', 'dosenList'));
    }

    public function generateReminderMessage(Request $request)
    {
        $request->validate([
            'dosen_ids' => 'required|array',
            'dosen_ids.*' => 'exists:dosen,id',
        ]);

        $dosenIds = $request->dosen_ids;
        $dosenList = Dosen::whereIn('id', $dosenIds)->get();

        // Generate pesan reminder menggunakan template
        $templatePesan = $this->generateTemplateMessage($dosenList);

        return response()->json([
            'success' => true,
            'message' => $templatePesan,
            'dosen_count' => $dosenList->count(),
        ]);
    }

    private function generateTemplateMessage($dosenList)
    {
        $namaDosen = $dosenList->count() > 1 
            ? 'Bapak/Ibu Dosen' 
            : 'Bapak/Ibu ' . $dosenList->first()->nama_lengkap;

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
            'dosen_ids' => 'required|array',
            'dosen_ids.*' => 'exists:dosen,id',
            'message' => 'required|string',
            'subject' => 'required|string|max:255',
        ]);

        try {
            $dosenList = Dosen::whereIn('id', $request->dosen_ids)->get();
            $successCount = 0;
            $failedCount = 0;

            foreach ($dosenList as $dosen) {
                try {
                    // Kirim email sebenarnya
                    Mail::to($dosen->kontak_email)->send(
                        new ReminderRPSMail(
                            $request->subject,
                            $request->message,
                            $dosen->nama_lengkap
                        )
                    );

                    // Simpan log email jika berhasil
                    LogEmail::create([
                        'reminder_id' => null,
                        'penerima_email' => $dosen->kontak_email,
                        'subjek' => $request->subject,
                        'isi_email' => $request->message,
                        'status_pengiriman' => 'success',
                        'tanggal_pengiriman' => now()->toDateString(),
                        'percobaan_kirim' => 1,
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    // Simpan log email jika gagal
                    LogEmail::create([
                        'reminder_id' => null,
                        'penerima_email' => $dosen->kontak_email,
                        'subjek' => $request->subject,
                        'isi_email' => $request->message,
                        'status_pengiriman' => 'failed',
                        'pesan_error' => $e->getMessage(),
                        'tanggal_pengiriman' => now()->toDateString(),
                        'percobaan_kirim' => 1,
                    ]);

                    $failedCount++;
                }
            }

            if ($failedCount > 0) {
                return redirect()->route('gkm.monitoring-rps.index')
                    ->with('warning', "Reminder berhasil dikirim ke {$successCount} dosen, gagal ke {$failedCount} dosen");
            }

            return redirect()->route('gkm.monitoring-rps.index')
                ->with('success', "Reminder berhasil dikirim ke {$successCount} dosen");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengirim reminder: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function historyReminder($dosenId = null)
    {
        $user = Auth::user();

        // Ambil dosen untuk filter
        $dosenList = Dosen::where('status', 'aktif')->get();
        
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
