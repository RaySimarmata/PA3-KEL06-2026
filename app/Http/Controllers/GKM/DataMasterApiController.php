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
}
