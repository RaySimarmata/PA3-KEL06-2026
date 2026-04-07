<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExternalAPIService
{
    protected $baseUrl;
    protected $token;
    protected $timeout;
    protected $tokenManager;

    public function __construct()
    {
        $this->baseUrl = env('API_BASE_URL', 'http://localhost');
        $this->tokenManager = new \App\Services\APITokenManager();
        $this->token = $this->tokenManager->getToken(); // Auto get/refresh token
        $this->timeout = 120; // seconds - increased for monitoring RPS
    }

    /**
     * Get all dosen from external API
     * 
     * @return array|null
     */
    public function getDosen()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->get($this->baseUrl . '/library-api/dosen');

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Extract dosen array from nested structure
                return $responseData['data']['dosen'] ?? [];
            }

            Log::warning('API Dosen failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

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
     * Get filtered dosen (only TI, TK, TRPL and Tenaga Pengajar)
     * 
     * @return array
     */
    public function getFilteredDosen()
    {
        $dosenList = $this->getDosen();
        
        if (empty($dosenList)) {
            return [];
        }
        
        // Filter by prodi only (remove jabatan filter for now)
        $allowedProdi = [
            'DIII Teknologi Informasi',
            'DIII Teknologi Komputer',
            'DIV Teknologi Rekayasa Perangkat Lunak'
        ];
        
        $filteredDosen = array_filter($dosenList, function($dosen) use ($allowedProdi) {
            $prodiDosen = $dosen['prodi'] ?? '';
            
            return in_array($prodiDosen, $allowedProdi);
        });
        
        // Remove duplicates based on pegawai_id
        $uniqueDosen = [];
        $seenIds = [];
        
        foreach ($filteredDosen as $dosen) {
            $pegawaiId = $dosen['pegawai_id'] ?? null;
            
            if ($pegawaiId && !in_array($pegawaiId, $seenIds)) {
                $uniqueDosen[] = $dosen;
                $seenIds[] = $pegawaiId;
            }
        }
        
        return array_values($uniqueDosen);
    }

    /**
     * Get dosen by ID
     * 
     * @param int $id
     * @return array|null
     */
    public function getDosenById($id)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->get($this->baseUrl . '/library-api/dosen/' . $id);

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('API Dosen by ID exception', [
                'id' => $id,
                'message' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Check if API is available
     * 
     * @return bool
     */
    public function isAvailable()
    {
        if (!$this->token || $this->token === 'your_api_token_here') {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
            ])
            ->timeout(5)
            ->get($this->baseUrl . '/library-api/dosen');

            return $response->successful();

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
        $newToken = $this->tokenManager->refreshToken();
        
        if ($newToken) {
            $this->token = $newToken;
            return true;
        }
        
        return false;
    }

    /**
     * Get token info
     * 
     * @return array|null
     */
    public function getTokenInfo()
    {
        return $this->tokenManager->getTokenInfo();
    }

    /**
     * Filter dosen by prodi
     * 
     * @param array $dosenList
     * @param int $prodiId
     * @return array
     */
    public function filterByProdi(array $dosenList, $prodiId)
    {
        return array_filter($dosenList, function($dosen) use ($prodiId) {
            return isset($dosen['prodi_id']) && $dosen['prodi_id'] == $prodiId;
        });
    }

    /**
     * Search dosen by name
     * 
     * @param array $dosenList
     * @param string $search
     * @return array
     */
    public function searchByName(array $dosenList, $search)
    {
        $searchTerm = strtolower($search);
        
        return array_filter($dosenList, function($dosen) use ($searchTerm) {
            return isset($dosen['nama']) && 
                   str_contains(strtolower($dosen['nama']), $searchTerm);
        });
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

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->get($url, $params);

            Log::info('API Response: Matkul by Prodi Sem TA', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Check if data is wrapped in a 'data' key
                if (isset($data['data']) && is_array($data['data'])) {
                    return $data['data'];
                }
                
                // If it's already an array, return it
                if (is_array($data)) {
                    return $data;
                }
                
                return [];
            }

            Log::warning('API Matkul by Prodi Sem TA failed', [
                'prodi_id' => $prodiId,
                'sem_ta' => $semTa,
                'ta' => $ta,
                'status' => $response->status(),
                'body' => $response->body()
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
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->get($this->baseUrl . '/library-api/get-monitoring-materi', [
                'kuliah_id' => $kuliahId,
                'ta' => $ta,
                'sem_ta' => $semTa
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Log raw response untuk debugging
                Log::debug('API Monitoring Materi Response', [
                    'kuliah_id' => $kuliahId,
                    'ta' => $ta,
                    'sem_ta' => $semTa,
                    'raw_response' => $data
                ]);
                
                // Check if data is wrapped
                if (isset($data['data']) && is_array($data['data'])) {
                    $data = $data['data'];
                }
                
                // Flatten nested structure for easier access
                // API returns: check_silabus.status_file_silabus
                // We want: status_file_silabus (at root level)
                if (isset($data['check_silabus'])) {
                    $data['status_file_silabus'] = $data['check_silabus']['status_file_silabus'] ?? 'BELUM UPLOAD';
                    $data['nama_file_silabus'] = $data['check_silabus']['nama_file'] ?? null;
                    $data['keterangan_silabus'] = $data['check_silabus']['keterangan'] ?? null;
                    
                    // Log mapped data
                    Log::debug('API Monitoring Materi Mapped', [
                        'kuliah_id' => $kuliahId,
                        'status_file_silabus' => $data['status_file_silabus']
                    ]);
                } else {
                    // Log jika check_silabus tidak ada
                    Log::warning('API Monitoring Materi: check_silabus not found', [
                        'kuliah_id' => $kuliahId,
                        'ta' => $ta,
                        'sem_ta' => $semTa,
                        'response_keys' => array_keys($data)
                    ]);
                }
                
                return $data;
            }

            Log::warning('API Monitoring Materi failed', [
                'kuliah_id' => $kuliahId,
                'ta' => $ta,
                'sem_ta' => $semTa,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('API Monitoring Materi exception', [
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
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->get($this->baseUrl . '/library-api/tahun-ajaran');

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('API Tahun Ajaran failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

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
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->get($this->baseUrl . '/library-api/kelas');

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('API Kelas failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

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

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->get($this->baseUrl . '/library-api/get-jadwal-by-dosen', $params);

            if ($response->successful()) {
                $responseData = $response->json();
                
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
                'pegawai_id' => $pegawaiId,
                'status' => $response->status(),
                'body' => $response->body()
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
}
