<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Dosenn;

class ExternalAPIService
{
    protected $baseUrl;
    protected $token;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.library.url');
        $this->timeout = 120; // seconds - increased for monitoring RPS
        $this->token = $this->getToken();
    }

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
            \Cache::forget('library_api_token');
        }

        return \Cache::remember('library_api_token', 3500, function () {
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
    public function getAccessToken()
{
    return $this->token;
}

    /**
     * Call API with automatic token refresh on 401
     * 
     * @param string $url
     * @param array $params
     * @param string $method
     * @return array|null
     */
    private function callApi($url, $params = [], $method = 'GET')
    {
        $token = $this->token;
        
        if (!$token) {
            Log::error('No API token available');
            return null;
        }

        try {
            $request = Http::withToken($token)->timeout($this->timeout);
            
            $response = $method === 'POST' 
                ? $request->post($url, $params)
                : $request->get($url, $params);

            // Token expired - refresh and retry
            if ($response->status() === 401) {
                Log::info('Token expired (401), refreshing...');
                
                $token = $this->getToken(true);
                $this->token = $token;
                
                if (!$token) {
                    Log::error('Failed to refresh token');
                    return null;
                }

                // Retry with new token
                $request = Http::withToken($token)->timeout($this->timeout);
                $response = $method === 'POST' 
                    ? $request->post($url, $params)
                    : $request->get($url, $params);
            }

            return $response->successful() ? $response->json() : null;
            
        } catch (\Exception $e) {
            Log::error('API call exception', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get all dosen from external API (for sync command)
     * 
     * @return array|null
     */
    public function getDosenFromAPI()
    {
        try {
            $data = $this->callApi($this->baseUrl . '/library-api/dosen');
            
            if ($data) {
                // Extract dosen array from nested structure
                return $data['data']['dosen'] ?? [];
            }

            Log::warning('API Dosen failed - no data returned');
            return [];

        } catch (\Exception $e) {
            Log::error('API Dosen exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [];
        }
    }

    /**
     * Get all dosen from external API
     * 
     * @return array|null
     */
    // public function getDosen()
    // {
    //     try {
    //         $data = $this->callApi($this->baseUrl . '/library-api/dosen');
            
    //         if ($data) {
    //             // Extract dosen array from nested structure
    //             return $data['data']['dosen'] ?? [];
    //         }

    //         Log::warning('API Dosen failed - no data returned');
    //         return [];

    //     } catch (\Exception $e) {
    //         Log::error('API Dosen exception', [
    //             'message' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);

    //         return [];
    //     }
    // }
    public function getDosen()
    {
        return Dosenn::all()->toArray();
    }

    /**
     * Get filtered dosen (only TI, TK, TRPL and Tenaga Pengajar)
     * 
     * @return array
     */
    // public function getFilteredDosen()
    // {
    //     $dosenList = $this->getDosen();
        
    //     if (empty($dosenList)) {
    //         return [];
    //     }
        
    //     // Filter by prodi only (remove jabatan filter for now)
    //     $allowedProdi = [
    //         'DIII Teknologi Informasi',
    //         'DIII Teknologi Komputer',
    //         'DIV Teknologi Rekayasa Perangkat Lunak'
    //     ];
        
    //     $filteredDosen = array_filter($dosenList, function($dosen) use ($allowedProdi) {
    //         $prodiDosen = $dosen['prodi'] ?? '';
            
    //         return in_array($prodiDosen, $allowedProdi);
    //     });
        
    //     // Remove duplicates based on pegawai_id
    //     $uniqueDosen = [];
    //     $seenIds = [];
        
    //     foreach ($filteredDosen as $dosen) {
    //         $pegawaiId = $dosen['pegawai_id'] ?? null;
            
    //         if ($pegawaiId && !in_array($pegawaiId, $seenIds)) {
    //             $uniqueDosen[] = $dosen;
    //             $seenIds[] = $pegawaiId;
    //         }
    //     }
        
    //     return array_values($uniqueDosen);
    // }

    public function getFilteredDosen()
{
    return Dosenn::all()
        ->unique('pegawai_id')
        ->values()
        ->toArray();
}

    /**
     * Get dosen by specific prodi IDs only (optimized for monitoring RPS)
     * Only fetch dosen with prodi_id 1, 3, or 4
     * 
     * @param array $prodiIds Array of prodi IDs to filter (default: [1, 3, 4])
     * @return array
     */
    // public function getDosenByProdiIds($prodiIds = [1, 3, 4])
    // {
    //     try {
    //         // Build query parameters to filter by prodi_id
    //         $params = [
    //             'limit' => 1000, // Increase limit to get all dosen
    //         ];

    //         $data = $this->callApi($this->baseUrl . '/library-api/dosen', $params);
            
    //         if ($data) {
    //             $dosenList = $data['data']['dosen'] ?? [];
                
    //             // Filter by prodi_id
    //             $filteredDosen = array_filter($dosenList, function($dosen) use ($prodiIds) {
    //                 $prodiId = $dosen['prodi_id'] ?? null;
    //                 return $prodiId && in_array($prodiId, $prodiIds);
    //             });
                
    //             Log::info('Filtered dosen by prodi_id', [
    //                 'total_dosen' => count($dosenList),
    //                 'filtered_dosen' => count($filteredDosen),
    //                 'prodi_ids' => $prodiIds
    //             ]);
                
    //             return array_values($filteredDosen);
    //         }

    //         Log::warning('API Dosen by prodi IDs failed - no data returned');
    //         return [];

    //     } catch (\Exception $e) {
    //         Log::error('API Dosen by prodi IDs exception', [
    //             'message' => $e->getMessage(),
    //             'prodi_ids' => $prodiIds
    //         ]);

    //         return [];
    //     }
    // }

    public function getDosenByProdiIds($prodiIds = [1, 3, 4, 10, 8])
{
    return Dosenn::whereIn('prodi_id', $prodiIds)
        ->get()
        ->toArray();
}

    /**
     * Get dosen by ID
     * 
     * @param int $id
     * @return array|null
     */
    // public function getDosenById($id)
    // {
    //     try {
    //         return $this->callApi($this->baseUrl . '/library-api/dosen/' . $id);
    //     } catch (\Exception $e) {
    //         Log::error('API Dosen by ID exception', [
    //             'id' => $id,
    //             'message' => $e->getMessage()
    //         ]);

    //         return null;
    //     }
    // }

    public function getDosenById($id)
{
    return Dosenn::where('pegawai_id', $id)->first();
}
    /**
     * Check if API is available
     * 
     * @return bool
     */
    public function isAvailable()
    {
        if (!$this->token) {
            return false;
        }

        try {
            $data = $this->callApi($this->baseUrl . '/library-api/dosen');
            return $data !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Refresh API token
     * 
     * @return bool
     */
    public function refreshToken()
    {
        $newToken = $this->getToken(true);
        
        if ($newToken) {
            $this->token = $newToken;
            return true;
        }
        
        return false;
    }

    /**
     * Get token info
     * 
     * @return array
     */
    public function getTokenInfo()
    {
        $token = $this->token;
        
        if (!$token) {
            return [
                'valid' => false,
                'message' => 'No token available'
            ];
        }

        // Decode JWT token to get expiration
        try {
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode($parts[1]), true);
                
                return [
                    'valid' => true,
                    'token' => substr($token, 0, 20) . '...',
                    'expires_at' => isset($payload['exp']) ? date('Y-m-d H:i:s', $payload['exp']) : 'Unknown',
                    'issued_at' => isset($payload['iat']) ? date('Y-m-d H:i:s', $payload['iat']) : 'Unknown',
                ];
            }
        } catch (\Exception $e) {
            // Ignore decode errors
        }

        return [
            'valid' => true,
            'token' => substr($token, 0, 20) . '...',
            'message' => 'Token available but cannot decode'
        ];
    }

    /**
     * Filter dosen by prodi
     * 
     * @param array $dosenList
     * @param int $prodiId
     * @return array
     */
    // public function filterByProdi(array $dosenList, $prodiId)
    // {
    //     return array_filter($dosenList, function($dosen) use ($prodiId) {
    //         return isset($dosen['prodi_id']) && $dosen['prodi_id'] == $prodiId;
    //     });
    // }

    public function filterByProdi($prodiId)
{
    return Dosenn::where('prodi_id', $prodiId)->get();
}
    /**
     * Search dosen by name
     * 
     * @param array $dosenList
     * @param string $search
     * @return array
     */
    // public function searchByName(array $dosenList, $search)
    // {
    //     $searchTerm = strtolower($search);
        
    //     return array_filter($dosenList, function($dosen) use ($searchTerm) {
    //         return isset($dosen['nama']) && 
    //                str_contains(strtolower($dosen['nama']), $searchTerm);
    //     });
    // }

    public function searchByName($search)
    {
        return Dosenn::where('nama', 'like', '%' . $search . '%')
            ->get();
    }
    /**
     * Get matakuliah by prodi, semester, and tahun ajaran
     * 
     * @param int $prodiId
     * @param int $semTa (1 = Ganjil, 2 = Genap)
     * @param int $ta (Tahun Ajaran)
     * @return array
     */
    public function getMatkulByProdiSemTa($prodiId, $semTa, $ta)
    {
        try {
            $url = $this->baseUrl . '/library-api/matkul-by-prodi-sem-ta';
            $params = [
                'prodi_id' => $prodiId,
                'sem_ta' => $semTa,
                'ta' => $ta
            ];

            Log::info('API Request: Matkul by Prodi Sem TA', [
                'url' => $url,
                'params' => $params
            ]);

            $data = $this->callApi($url, $params);

            if ($data) {
                $rawData = null;
                
                // Check if data is wrapped in a 'data' key
                if (isset($data['data']) && is_array($data['data'])) {
                    $rawData = $data['data'];
                } elseif (is_array($data)) {
                    $rawData = $data;
                }
                
                // Filter berdasarkan digit ke-5 dari kode_mk
                // Digit ke-5 = 1 untuk Ganjil, Digit ke-5 = 2 untuk Genap
                // Contoh: 1141105 (digit ke-5 = 1) = Ganjil
                //         1144201 (digit ke-5 = 2) = Genap
                if ($rawData && is_array($rawData)) {
                    $filteredData = array_filter($rawData, function($matkul) use ($semTa) {
                        $kodeMk = $matkul['kode_mk'] ?? '';
                        
                        // Skip jika kode_mk kosong atau kurang dari 5 digit
                        if (strlen($kodeMk) < 5) {
                            return true; // Keep data tanpa kode valid
                        }
                        
                        // Ambil digit ke-5 (index 4)
                        $digitSemester = substr($kodeMk, 4, 1);
                        
                        // Filter: semTa=1 (Ganjil) hanya tampilkan digit=1
                        //         semTa=2 (Genap) hanya tampilkan digit=2
                        if ($semTa == 1) {
                            return $digitSemester == '1';
                        } elseif ($semTa == 2) {
                            return $digitSemester == '2';
                        }
                        
                        return true; // Keep jika semTa tidak valid
                    });
                    
                    Log::info('API Response: Matkul by Prodi Sem TA (Filtered)', [
                        'raw_count' => count($rawData),
                        'filtered_count' => count($filteredData),
                        'semester' => $semTa == 1 ? 'Ganjil' : 'Genap'
                    ]);
                    
                    return array_values($filteredData); // Re-index array
                }
            }

            Log::warning('API Matkul by Prodi Sem TA failed', [
                'prodi_id' => $prodiId,
                'sem_ta' => $semTa,
                'ta' => $ta
            ]);

            return [];

        } catch (\Exception $e) {
            Log::error('API Matkul by Prodi Sem TA exception', [
                'prodi_id' => $prodiId,
                'sem_ta' => $semTa,
                'ta' => $ta,
                'message' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Get monitoring materi (RPS status)
     * 
     * @param int $kuliahId
     * @param int $ta (Tahun Ajaran)
     * @param int $semTa (1 = Ganjil, 2 = Genap)
     * @return array|null
     */
    public function getMonitoringMateri($kuliahId, $ta, $semTa)
{
    try {
        $data = $this->callApi($this->baseUrl . '/library-api/get-monitoring-materi-teori', [
            'kuliah_id' => $kuliahId,
            'ta' => $ta,
            'sem_ta' => $semTa
        ]);

        if ($data) {

            Log::debug('API Monitoring Materi TEORI RAW', [
                'kuliah_id' => $kuliahId,
                'response' => $data
            ]);

            // 🔥 unwrap data
            if (isset($data['data']) && is_array($data['data'])) {
                $data = $data['data'];
            }

            // =========================
            // 🔥 MAPPING SILABUS
            // =========================
            if (isset($data['check_silabus'])) {
                $data['status_file_silabus'] = $data['check_silabus']['status_file_silabus'] ?? 'BELUM UPLOAD';
                $data['nama_file_silabus']   = $data['check_silabus']['nama_file'] ?? null;
                $data['keterangan_silabus'] = $data['check_silabus']['keterangan'] ?? null;
            }

            // =========================
            // 🔥 MAPPING DETAIL TEORI
            // =========================
            if (isset($data['check_materi']['detail']) && is_array($data['check_materi']['detail'])) {

                foreach ($data['check_materi']['detail'] as &$sesi) {

                    // 🔥 Ambil created_at dari file pertama
                    $createdAt = null;
                    if (isset($sesi['daftar_file']) && is_array($sesi['daftar_file']) && count($sesi['daftar_file']) > 0) {
                        $createdAt = $sesi['daftar_file'][0]['created_at'] ?? null;
                    }

                    $sesi['created_at_final'] = $createdAt;

                    // 🔥 tandai upload
                    $sesi['is_uploaded'] = strpos($sesi['status_file'] ?? '', 'OK') !== false;
                }

                // =========================
                // 🔥 STATUS PER WEEK (FINAL LOGIC)
                // =========================
                $weekStatus = [];

                foreach ($data['check_materi']['detail'] as $sesi) {

                    // 🔥 Ambil week dari sesi (W1-S1 → W1)
                    $week = null;
                    if (isset($sesi['sesi'])) {
                        $parts = explode('-', $sesi['sesi']);
                        $week = $parts[0] ?? null;
                    }

                    if (!$week) continue;

                    // 🔥 default
                    if (!isset($weekStatus[$week])) {
                        $weekStatus[$week] = [
                            'status' => 'BELUM UPLOAD',
                            'tanggal_upload' => null
                        ];
                    }

                    // 🔥 jika ada salah satu sesi upload → langsung SUDAH
                    if (!empty($sesi['is_uploaded'])) {
                        $weekStatus[$week]['status'] = 'SUDAH UPLOAD';

                        // ambil tanggal pertama ditemukan
                        if (!empty($sesi['created_at_final']) && !$weekStatus[$week]['tanggal_upload']) {
                            $weekStatus[$week]['tanggal_upload'] = $sesi['created_at_final'];
                        }
                    }
                }

                // 🔥 inject tanpa ganggu struktur lama
                $data['check_materi']['week_status'] = $weekStatus;
            }

            return $data;
        }

        Log::warning('API Monitoring Materi TEORI EMPTY', [
            'kuliah_id' => $kuliahId,
            'ta' => $ta,
            'sem_ta' => $semTa
        ]);

        return null;

    } catch (\Exception $e) {
        Log::error('API Monitoring Materi TEORI ERROR', [
            'kuliah_id' => $kuliahId,
            'ta' => $ta,
            'sem_ta' => $semTa,
            'message' => $e->getMessage()
        ]);

        return null;
    }
}

    public function getMonitoringMateriPraktikum($kuliahId, $ta, $semTa)
{
    try {
        $data = $this->callApi($this->baseUrl . '/library-api/get-monitoring-materi-praktikum', [
            'kuliah_id' => $kuliahId,
            'ta' => $ta,
            'sem_ta' => $semTa
        ]);

        if ($data) {

            // 🔍 Debug log (optional, bisa kamu matikan nanti)
            Log::debug('API Monitoring Materi Praktikum RAW', [
                'kuliah_id' => $kuliahId,
                'response' => $data
            ]);

            // 🔥 unwrap jika ada key 'data'
            if (isset($data['data']) && is_array($data['data'])) {
                $data = $data['data'];
            }

            // =========================
            // 🔥 MAPPING SILABUS
            // =========================
            if (isset($data['check_silabus'])) {
                $data['status_file_silabus'] = $data['check_silabus']['status_file_silabus'] ?? 'BELUM UPLOAD';
                $data['nama_file_silabus']   = $data['check_silabus']['nama_file'] ?? null;
                $data['keterangan_silabus'] = $data['check_silabus']['keterangan'] ?? null;
            }

            // =========================
            // 🔥 MAPPING DETAIL PRAKTIKUM
            // =========================
            if (isset($data['check_praktikum']['detail']) && is_array($data['check_praktikum']['detail'])) {

                foreach ($data['check_praktikum']['detail'] as &$sesi) {

                    // 🔥 Ambil created_at dari waktu_praktikum
                    $createdAt = $sesi['waktu_praktikum']['created_at'] ?? null;

                    // 🔥 fallback ke daftar_file jika kosong
                    if (!$createdAt && isset($sesi['daftar_file'][0]['created_at'])) {
                        $createdAt = $sesi['daftar_file'][0]['created_at'];
                    }

                    // 🔥 simpan hasil final
                    $sesi['created_at_final'] = $createdAt;

                    // 🔥 optional: tandai apakah ada file
                    $sesi['is_uploaded'] = strpos($sesi['status_file'] ?? '', 'OK') !== false;

                    // 🔍 Debug per sesi (optional)
                    Log::debug('Mapping sesi praktikum', [
                        'sesi' => $sesi['sesi'] ?? null,
                        'status_file' => $sesi['status_file'] ?? null,
                        'created_at_final' => $sesi['created_at_final']
                    ]);
                }

                // simpan kembali
                $data['check_praktikum']['detail'] = $data['check_praktikum']['detail'];
            }

            return $data;
        }

        Log::warning('API Monitoring Materi Praktikum EMPTY', [
            'kuliah_id' => $kuliahId,
            'ta' => $ta,
            'sem_ta' => $semTa
        ]);

        return null;

    } catch (\Exception $e) {
        Log::error('API Monitoring Materi Praktikum ERROR', [
            'kuliah_id' => $kuliahId,
            'ta' => $ta,
            'sem_ta' => $semTa,
            'message' => $e->getMessage()
        ]);

        return null;
    }
}

    /**
     * Get tahun ajaran from external API
     * 
     * @return array
     */
    public function getTahunAjaran()
    {
        try {
            $data = $this->callApi($this->baseUrl . '/library-api/tahun-ajaran');

            if ($data) {
                return $data;
            }

            Log::warning('API Tahun Ajaran failed');

            // Return default 2020 if API fails
            return [
                [
                    'id_thn_ajaran' => '2020',
                    'nm_thn_ajaran' => '2020'
                ]
            ];

        } catch (\Exception $e) {
            Log::error('API Tahun Ajaran exception', [
                'message' => $e->getMessage()
            ]);

            // Return default 2020 if exception
            return [
                [
                    'id_thn_ajaran' => '2020',
                    'nm_thn_ajaran' => '2020'
                ]
            ];
        }
    }

    /**
     * Get kelas from external API
     * 
     * @return array
     */
    public function getKelas()
    {
        try {
            $data = $this->callApi($this->baseUrl . '/library-api/kelas');

            if ($data) {
                return $data;
            }

            Log::warning('API Kelas failed');
            return [];

        } catch (\Exception $e) {
            Log::error('API Kelas exception', [
                'message' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Get jadwal by dosen
     * 
     * @param int $pegawaiId
     * @param int $semTa (1 = Ganjil, 2 = Genap)
     * @param int $ta (Tahun Ajaran)
     * @return array|null
     */
    public function getJadwalByDosen($pegawaiId, $semTa = null, $ta = null)
    {
        try {
            $params = ['pegawai_id' => $pegawaiId];
            
            if ($semTa !== null) {
                $params['sem_ta'] = $semTa;
            }
            
            if ($ta !== null) {
                $params['ta'] = $ta;
            }

            $responseData = $this->callApi($this->baseUrl . '/library-api/get-jadwal-by-dosen', $params);

            if ($responseData) {
                Log::info('API Jadwal Response', [
                    'pegawai_id' => $pegawaiId,
                    'response' => $responseData
                ]);
                
                // Get semester and tahun ajaran from root response
                $semesterValue = $responseData['semester'] ?? $semTa;
                $tahunAjaranValue = $responseData['ta'] ?? $ta;
                
                // Get jadwal list from data array
                $jadwalList = $responseData['data'] ?? [];
                
                // Validate that jadwalList is an array
                if (!is_array($jadwalList)) {
                    Log::warning('API Jadwal data is not an array', [
                        'pegawai_id' => $pegawaiId,
                        'data_type' => gettype($jadwalList),
                        'data' => $jadwalList
                    ]);
                    return [];
                }
                
                // If empty, return empty array
                if (empty($jadwalList)) {
                    return [];
                }
                
                // Transform data to match expected format
                return array_map(function($jadwal) use ($semesterValue, $tahunAjaranValue) {
                    return [
                        'kode_mk' => $jadwal['kode_mk'] ?? '-',
                        'nama_mk' => $jadwal['nama_mk'] ?? '-',
                        'sks' => $jadwal['sks'] ?? '-',
                        'semester' => $semesterValue == 1 ? 'Ganjil' : 'Genap',
                        'tahun_ajaran' => $tahunAjaranValue ?? '-'
                    ];
                }, $jadwalList);
            }

            Log::warning('API Jadwal Dosen failed', [
                'pegawai_id' => $pegawaiId
            ]);

            return [];

        } catch (\Exception $e) {
            Log::error('API Jadwal Dosen exception', [
                'pegawai_id' => $pegawaiId,
                'message' => $e->getMessage()
            ]);

            return [];
        }
    }
    public function getRekapKuesioner($ta, $kodeMk)
{
    $url = $this->baseUrl . '/library-api/get-rekap-kuesioner';

    return $this->callApi($url, [
        'ta' => $ta,
        'kode_mk' => $kodeMk
    ]);
}
}
