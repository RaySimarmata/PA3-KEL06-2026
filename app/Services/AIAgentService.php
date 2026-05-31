<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI Agent Service - Pure AI-driven operations
 * Semua operasi AI dilakukan melalui service ini tanpa logika hardcoded
 */
class AIAgentService
{
    protected $provider;
    protected $apiKey;
    protected $baseUrl;
    protected $model;

    public function __construct()
    {
        $this->provider = config('services.llm.provider', env('LLM_PROVIDER'));
        $this->apiKey = config('services.llm.api_key', env('LLM_API_KEY'));
        $this->baseUrl = config('services.llm.base_url', env('LLM_BASE_URL'));
        $this->model = config('services.llm.model', env('LLM_MODEL'));
    }

    /**
     * Generate reminder message menggunakan AI Agent
     * Pure AI - tidak ada template hardcoded
     */
    public function generateReminderMessage($dosenList, $userProdi = null, $type = 'rps')
    {
        try {
            $prompt = $this->buildReminderPrompt($dosenList, $userProdi, $type);

            $response = $this->callAI([
                'role' => 'system',
                'content' => 'Anda adalah AI Agent profesional yang membantu membuat pesan reminder untuk dosen di lingkungan akademik. Tulis LANGSUNG pesan yang diminta TANPA intro, penjelasan, atau komentar tambahan. Gunakan bahasa yang formal, sopan, dan profesional dengan format poin bernomor yang rapi.'
            ], $prompt, 600);

            if ($response) {
                // Clean up any intro text
                $response = preg_replace('/^(Baiklah|Berikut|Tentu|Saya akan|Oke|Ok)[^:]*:\s*/i', '', $response);
                $response = preg_replace('/^.*?Kepada Yth\./s', 'Kepada Yth.', $response);
                
                Log::info("AI Agent generated reminder message", [
                    'type' => $type,
                    'dosen_count' => $dosenList->count()
                ]);
                return trim($response);
            }

            throw new \Exception('AI Agent tidak menghasilkan response');

        } catch (\Exception $e) {
            Log::error('AI Agent Service Exception for reminder', [
                'message' => $e->getMessage(),
                'type' => $type
            ]);
            throw $e;
        }
    }

    /**
     * Analisis kuesioner menggunakan AI Agent
     * Pure AI - tidak ada logika statistik hardcoded
     */
    /**
     * RAG STEP 1: Extract dan Index data dari Excel
     * Mengubah raw Excel data menjadi structured knowledge base
     */
    private function extractAndIndexKuesioneData($excelData)
    {
        $headers = $excelData['headers'];
        $data = $excelData['data'];
        
        // Index 1: Metadata
        $metadata = [
            'total_responden' => count($data),
            'total_pertanyaan' => count($headers) - 1,
            'pertanyaan' => []
        ];
        
        // Index 2 & 3: Pertanyaan dan Statistik per pertanyaan
        // Filter: hanya kolom yang berisi jawaban Likert (TS/CS/S/SS) yang dihitung
        $validJawaban = ['TS', 'CS', 'S', 'SS'];
        $statistikPerPertanyaan = [];
        $questionNumber = 0;
        
        for ($i = 1; $i < count($headers); $i++) {
            // Hitung berapa banyak jawaban valid (TS/CS/S/SS) di kolom ini
            $distribusi = ['TS' => 0, 'CS' => 0, 'S' => 0, 'SS' => 0];
            $totalValid = 0;
            
            foreach ($data as $row) {
                $jawaban = strtoupper(trim($row[$i] ?? ''));
                if (in_array($jawaban, $validJawaban)) {
                    $distribusi[$jawaban]++;
                    $totalValid++;
                }
            }
            
            // Skip kolom jika kurang dari 30% responden memiliki jawaban Likert valid
            // Ini akan otomatis mengecualikan kolom komentar/saran (isian bebas)
            $minValid = count($data) * 0.3;
            if ($totalValid < $minValid) {
                Log::info("Skipping column as non-Likert", [
                    'kolom_index' => $i,
                    'header' => $headers[$i],
                    'total_valid' => $totalValid,
                    'total_data' => count($data)
                ]);
                continue;
            }
            
            $questionNumber++;
            $qId = 'Q' . $questionNumber;
            
            $metadata['pertanyaan'][] = [
                'id' => $qId,
                'teks' => $headers[$i],
                'kolom_index' => $i
            ];
            
            $statistikPerPertanyaan[$qId] = [
                'teks_pertanyaan' => $headers[$i],
                'distribusi' => $distribusi,
                'total_responden' => count($data)
            ];
        }
        
        // Update total pertanyaan setelah filter
        $metadata['total_pertanyaan'] = $questionNumber;
        
        // Index 4: Sample responses untuk context
        $sampleResponses = array_slice($data, 0, 10);
        
        return [
            'metadata' => $metadata,
            'statistik_per_pertanyaan' => $statistikPerPertanyaan,
            'sample_responses' => $sampleResponses,
            'raw_headers' => $headers
        ];
    }

   private function hitungStatistikDariApi($apiData)
{
    // 🔥 1. Pastikan data bukan string (decode kalau perlu)
    if (is_string($apiData)) {
        $decoded = json_decode($apiData, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $apiData = $decoded;
        } else {
            Log::error("JSON ERROR", [
                'error' => json_last_error_msg(),
                'raw' => $apiData
            ]);

            return [
                'index_kepuasan' => 0,
                'persen_kepuasan' => 0,
            ];
        }
    }

    // 🔥 2. Ambil data distribusi (format API baru)
    $data = $apiData['statistik_per_pertanyaan'] ?? [];

    if (!is_array($data) || empty($data)) {
        Log::error("DATA KOSONG / FORMAT SALAH!", [
            'apiData' => $apiData
        ]);

        return [
            'index_kepuasan' => 0,
            'persen_kepuasan' => 0,
        ];
    }

    // 🔥 3. Bobot standar (sesuai view)
    $bobot = [
        'TS' => 1,
        'CS' => 2,
        'S'  => 3,
        'SS' => 4,
    ];

    $totalSkor = 0;
    $totalRespon = 0;

    // 🔥 4. Loop semua pertanyaan
    foreach ($data as $kode => $item) {

        if (!isset($item['distribusi']) || !is_array($item['distribusi'])) {
            continue;
        }

        foreach ($item['distribusi'] as $label => $jumlah) {

            $nilai = $bobot[$label] ?? 0;
            $jumlah = (int) $jumlah;

            $totalSkor += $nilai * $jumlah;
            $totalRespon += $jumlah;
        }
    }

    // 🔥 5. Hindari pembagian nol
    if ($totalRespon === 0) {
        Log::warning("TOTAL RESPON 0!");

        return [
            'index_kepuasan' => 0,
            'persen_kepuasan' => 0,
        ];
    }

    // ✅ 6. Hitung index (skala 1–5)
    $index = $totalSkor / $totalRespon;

    // 🔥 7. Konversi ke skala 0–4 (sesuai UI kamu)
    $index_0_4 = $index - 1;

    // 🔥 8. Hitung persen
    $persen = ($index_0_4 / 4) * 100;

    // 🔥 9. Guard biar gak aneh
    if ($persen > 100) {
        Log::warning("PERSEN > 100%", [
            'index_asli' => $index,
            'index_0_4' => $index_0_4,
            'persen' => $persen
        ]);
    }

    return [
        'index_kepuasan' => round($index_0_4, 2),
        'persen_kepuasan' => round($persen, 2),
    ];
}
    public function analyzeFromApi($kuesioner, $indexedData)
{
    try {
        Log::info("=== RAG API PROCESS START ===", ['id' => $kuesioner->id]);

        // STEP 1: Retrieve context
        $retrievedContext = $this->retrieveRelevantContext($indexedData);

        // STEP 2: Augment prompt
        $augmentedPrompt = $this->augmentPromptWithContext($kuesioner, $retrievedContext);

        $augmentedPrompt .= "
        Berikan analisis lengkap dalam format JSON berikut:

        {
          \"ringkasan\": \"Ringkasan analisis 2-3 kalimat\",
          \"interpretasi_index\": \"Interpretasi index kepuasan\",
          \"poin_positif\": [\"Poin positif 1\", \"Poin positif 2\"],
          \"area_perbaikan\": [\"Area perbaikan 1\", \"Area perbaikan 2\"],
          \"rekomendasi\": [\"Rekomendasi 1\", \"Rekomendasi 2\"]
        }
        ";

        // STEP 3: Call AI
        $response = $this->callAI([
            'role' => 'system',
            'content' => 'Anda adalah AI analis kuesioner. Output JSON saja.'
        ], $augmentedPrompt, 1500);

        if (!$response) {
            throw new \Exception("AI tidak merespon");
        }

        // CLEAN JSON
        $clean = trim($response);
        $first = strpos($clean, '{');
        $last = strrpos($clean, '}');

        if ($first !== false && $last !== false) {
            $clean = substr($clean, $first, $last - $first + 1);
        }

        $result = json_decode($clean, true);

        if (!$result) {
            throw new \Exception("JSON tidak valid");
        }

        // VALIDASI FIELD
        $result['ringkasan'] = $result['ringkasan'] ?? 'Tidak tersedia';
        $result['interpretasi_index'] = $result['interpretasi_index'] ?? 'Tidak tersedia';
        $result['poin_positif'] = $result['poin_positif'] ?? [];
        $result['area_perbaikan'] = $result['area_perbaikan'] ?? [];
        $result['rekomendasi'] = $result['rekomendasi'] ?? [];

        // HITUNG STATISTIK PER PERTANYAAN DARI DATA REAL (BUKAN DARI AI)
        $statistikPerPertanyaan = [];
        $totalNilai = 0;
        $jumlahPertanyaan = 0;
        
        foreach ($indexedData['statistik_per_pertanyaan'] as $qId => $stat) {
            $totalResponden = $stat['total_responde n'];
            $nilaiRataRata = 0;
            
            if ($totalResponden > 0) {
                // Rumus: (TS×1 + CS×2 + S×3 + SS×4) / total_responden
                $nilaiRataRata = (
                    ($stat['distribusi']['TS'] * 1) +
                    ($stat['distribusi']['CS'] * 2) +
                    ($stat['distribusi']['S'] * 3) +
                    ($stat['distribusi']['SS'] * 4)
                ) / $totalResponden;
            }
            
            $statistikPerPertanyaan[$qId] = [
                'pertanyaan' => $stat['teks_pertanyaan'],
                'TS' => $stat['distribusi']['TS'],
                'CS' => $stat['distribusi']['CS'],
                'S' => $stat['distribusi']['S'],
                'SS' => $stat['distribusi']['SS'],
                'nilai_rata_rata' => $nilaiRataRata
            ];
            
            $totalNilai += $nilaiRataRata;
            $jumlahPertanyaan++;
        }
        
        // HITUNG INDEX KEPUASAN DARI RATA-RATA SEMUA PERTANYAAN
        $indexKepuasan = $jumlahPertanyaan > 0 ? $totalNilai / $jumlahPertanyaan : 0;
        $persenKepuasan = ($indexKepuasan / 4) * 100;
        
        Log::info("Index Kepuasan Real (from API)", [
            'total_nilai' => $totalNilai,
            'jumlah_pertanyaan' => $jumlahPertanyaan,
            'index_kepuasan' => $indexKepuasan,
            'persen_kepuasan' => $persenKepuasan
        ]);
        
        // INIT STATISTIK ARRAY
        $result['statistik'] = [
            'statistik_per_pertanyaan' => $statistikPerPertanyaan,
            'index_kepuasan' => $indexKepuasan,
            'persen_kepuasan' => $persenKepuasan,
            'total_responden' => $indexedData['metadata']['total_responden'],
            'total_pertanyaan' => $jumlahPertanyaan
        ];
        
        // HITUNG DISTRIBUSI JAWABAN TOTAL
        $distribusiTotal = ['TS' => 0, 'CS' => 0, 'S' => 0, 'SS' => 0];
        foreach ($indexedData['statistik_per_pertanyaan'] as $stat) {
            $distribusiTotal['TS'] += $stat['distribusi']['TS'];
            $distribusiTotal['CS'] += $stat['distribusi']['CS'];
            $distribusiTotal['S'] += $stat['distribusi']['S'];
            $distribusiTotal['SS'] += $stat['distribusi']['SS'];
        }
        $result['statistik']['distribusi_jawaban'] = $distribusiTotal;
        
        // CARI PERTANYAAN TERTINGGI DAN TERENDAH
        $nilaiTertinggi = 0;
        $nilaiTerendah = 4;
        $qTertinggi = null;
        $qTerendah = null;
        
        foreach ($statistikPerPertanyaan as $qId => $stat) {
            if ($stat['nilai_rata_rata'] > $nilaiTertinggi) {
                $nilaiTertinggi = $stat['nilai_rata_rata'];
                $qTertinggi = [
                    'id' => $qId,
                    'teks' => $stat['pertanyaan'],
                    'nilai' => $stat['nilai_rata_rata']
                ];
            }
            if ($stat['nilai_rata_rata'] < $nilaiTerendah) {
                $nilaiTerendah = $stat['nilai_rata_rata'];
                $qTerendah = [
                    'id' => $qId,
                    'teks' => $stat['pertanyaan'],
                    'nilai' => $stat['nilai_rata_rata']
                ];
            }
        }
        
        $result['statistik']['pertanyaan_tertinggi'] = $qTertinggi;
        $result['statistik']['pertanyaan_terendah'] = $qTerendah;

        Log::info("=== RAG API DONE ===");

        return $result;

    } catch (\Exception $e) {
        Log::error("API AI ERROR", [
            'msg' => $e->getMessage()
        ]);
        throw $e;
    }
}
    
    /**
     * RAG STEP 2: Retrieve relevant context
     * Mengambil context yang relevan dari indexed data
     */
    private function retrieveRelevantContext($indexedData)
    {
        // Retrieve: Ambil informasi yang paling relevan untuk analisis
        $context = [
            'overview' => [
                'total_responden' => $indexedData['metadata']['total_responden'],
                'total_pertanyaan' => $indexedData['metadata']['total_pertanyaan'],
                'daftar_pertanyaan' => $indexedData['metadata']['pertanyaan']
            ],
            'statistik_detail' => $indexedData['statistik_per_pertanyaan'],
            'sample_data' => $indexedData['sample_responses']
        ];
        
        return $context;
    }
    
    /**
     * RAG STEP 3: Augment prompt dengan retrieved context
     * Generate prompt yang diperkaya dengan context yang relevan
     */
    private function augmentPromptWithContext($kuesioner, $retrievedContext)
    {
        $prompt = "=== ANALISIS KUESIONER KEPUASAN MAHASISWA ===\n\n";
        
        // Context 1: Informasi Kuesioner
        $prompt .= "INFORMASI KUESIONER:\n";
        $prompt .= "- Nama File/Kuesioner: " . $kuesioner->nama_file . "\n";
        $prompt .= "- Periode: " . $kuesioner->periode . "\n";
        $prompt .= "- Program Studi: " . ($kuesioner->user->prodi->nama_prodi ?? 'Unknown') . "\n";
        $prompt .= "- Total Responden: " . $retrievedContext['overview']['total_responden'] . "\n";
        $prompt .= "- Total Pertanyaan: " . $retrievedContext['overview']['total_pertanyaan'] . "\n\n";
        
        // Context 2: Daftar Pertanyaan
        $prompt .= "DAFTAR PERTANYAAN:\n";
        foreach ($retrievedContext['overview']['daftar_pertanyaan'] as $p) {
            $prompt .= $p['id'] . ": " . $p['teks'] . "\n";
        }
        $prompt .= "\n";
        
        // Context 3: Statistik Detail (Pre-computed)
        $prompt .= "STATISTIK DETAIL PER PERTANYAAN:\n";
        $prompt .= "(Data sudah dihitung dari " . $retrievedContext['overview']['total_responden'] . " responden)\n\n";
        
        foreach ($retrievedContext['statistik_detail'] as $qId => $stat) {
            $prompt .= $qId . ": " . $stat['teks_pertanyaan'] . "\n";
            $prompt .= "  Distribusi jawaban:\n";
            $prompt .= "  - TS (Tidak Setuju): " . $stat['distribusi']['TS'] . " responden\n";
            $prompt .= "  - CS (Cukup Setuju): " . $stat['distribusi']['CS'] . " responden\n";
            $prompt .= "  - S (Setuju): " . $stat['distribusi']['S'] . " responden\n";
            $prompt .= "  - SS (Sangat Setuju): " . $stat['distribusi']['SS'] . " responden\n";
            
            // Hitung nilai rata-rata
            $totalResponden = $stat['total_responden'];
            $nilaiRataRata = 0;
            if ($totalResponden > 0) {
                $nilaiRataRata = (
                    ($stat['distribusi']['TS'] * 1) +
                    ($stat['distribusi']['CS'] * 2) +
                    ($stat['distribusi']['S'] * 3) +
                    ($stat['distribusi']['SS'] * 4)
                ) / $totalResponden;
            }
            $prompt .= "  Nilai rata-rata: " . number_format($nilaiRataRata, 5) . "\n";
            $prompt .= "  Total: " . $stat['total_responden'] . " responden\n\n";
        }
        
        // Context 4: Sample untuk validasi
        $prompt .= "SAMPLE DATA (10 responden pertama untuk referensi):\n";
        foreach ($retrievedContext['sample_data'] as $idx => $row) {
            $prompt .= "Responden " . ($idx + 1) . ": " . implode(' | ', $row) . "\n";
        }
        $prompt .= "\n";
        
        return $prompt;
    }
    
    /**
     * RAG STEP 4: Generate dengan AI Model
     * Menggunakan RAG approach: Retrieve → Augment → Generate
     */
    public function analyzeKuesioner($kuesioner, $excelData)
    {
        try {
            Log::info("=== RAG PROCESS START ===", ['kuesioner_id' => $kuesioner->id]);
            
            // STEP 1: Extract & Index
            Log::info("RAG Step 1: Extracting and indexing data");
            $indexedData = $this->extractAndIndexKuesioneData($excelData);
            
            // STEP 2: Retrieve
            Log::info("RAG Step 2: Retrieving relevant context");
            $retrievedContext = $this->retrieveRelevantContext($indexedData);
            
            // STEP 3: Augment
            Log::info("RAG Step 3: Augmenting prompt with context");
            $augmentedPrompt = $this->augmentPromptWithContext($kuesioner, $retrievedContext);
            
            // Add instruction untuk AI
            $augmentedPrompt .= "\nSKALA PENILAIAN:\n";
            $augmentedPrompt .= "- SS (Sangat Setuju) = 4 poin\n";
            $augmentedPrompt .= "- S (Setuju) = 3 poin\n";
            $augmentedPrompt .= "- CS (Cukup Setuju) = 2 poin\n";
            $augmentedPrompt .= "- TS (Tidak Setuju) = 1 poin\n\n";
            
            $augmentedPrompt .= "RUMUS PERHITUNGAN (WAJIB DIIKUTI):\n";
            $augmentedPrompt .= "1. Nilai rata-rata per pertanyaan:\n";
            $augmentedPrompt .= "   nilai_rata_rata = (TS×1 + CS×2 + S×3 + SS×4) / total_responden\n";
            $augmentedPrompt .= "   Contoh: Q1 dengan TS=0, CS=7, S=61, SS=15 dan total_responden=83\n";
            $augmentedPrompt .= "   nilai_rata_rata = (0×1 + 7×2 + 61×3 + 15×4) / 83 = (0 + 14 + 183 + 60) / 83 = 257/83 = 3.09759\n\n";
            
            $augmentedPrompt .= "2. Index kepuasan (rata-rata dari semua pertanyaan):\n";
            $augmentedPrompt .= "   index_kepuasan = (Σ nilai_rata_rata semua pertanyaan) / jumlah_pertanyaan\n";
            $augmentedPrompt .= "   Contoh: Jika Q1=3.09759, Q2=3.09756, ..., Q17=3.17073 (17 pertanyaan)\n";
            $augmentedPrompt .= "   index_kepuasan = (3.09759 + 3.09756 + ... + 3.17073) / 17\n\n";
            
            $augmentedPrompt .= "3. Persentase kepuasan:\n";
            $augmentedPrompt .= "   persen_kepuasan = (index_kepuasan / 4) × 100%\n\n";
            
            $augmentedPrompt .= "TUGAS ANDA:\n";
            $augmentedPrompt .= "1. Hitung nilai_rata_rata untuk SETIAP pertanyaan menggunakan rumus di atas\n";
            $augmentedPrompt .= "2. Hitung index_kepuasan dari rata-rata semua nilai_rata_rata\n";
            $augmentedPrompt .= "3. Identifikasi pertanyaan dengan nilai tertinggi dan terendah\n";
            $augmentedPrompt .= "4. Berikan analisis mendalam dan rekomendasi actionable\n\n";
            
            $augmentedPrompt .= "OUTPUT FORMAT (HANYA JSON, TANPA TEKS APAPUN SEBELUM ATAU SESUDAH JSON):\n";
            $augmentedPrompt .= "{\n";
            $augmentedPrompt .= '  "statistik": {' . "\n";
            $augmentedPrompt .= '    "index_kepuasan": <float 0-4>,' . "\n";
            $augmentedPrompt .= '    "total_responden": <int>,' . "\n";
            $augmentedPrompt .= '    "total_pertanyaan": <int>,' . "\n";
            $augmentedPrompt .= '    "rata_rata_skor": <sama dengan index_kepuasan>,' . "\n";
            $augmentedPrompt .= '    "distribusi_jawaban": { "TS": <int>, "CS": <int>, "S": <int>, "SS": <int> }' . "\n";
            $augmentedPrompt .= '  },' . "\n";
            $augmentedPrompt .= '  "ringkasan": "<ringkasan analisis 2-3 kalimat, WAJIB gunakan Nama File/Kuesioner yang tertera di atas, BUKAN nama program studi>",' . "\n";
            $augmentedPrompt .= '  "interpretasi_index": "<interpretasi: >3.5=sangat baik, 3.0-3.5=baik, 2.5-3.0=cukup, <2.5=kurang>",' . "\n";
            $augmentedPrompt .= '  "poin_positif": [' . "\n";
            $augmentedPrompt .= '    "<kalimat deskriptif yang HARUS mengutip TEKS PERTANYAAN ASLI dari DAFTAR PERTANYAAN di atas beserta nilai rata-ratanya>"' . "\n";
            $augmentedPrompt .= '  ],' . "\n";
            $augmentedPrompt .= '  "area_perbaikan": [' . "\n";
            $augmentedPrompt .= '    "<kalimat deskriptif yang HARUS mengutip TEKS PERTANYAAN ASLI dari DAFTAR PERTANYAAN di atas beserta nilai rata-ratanya>"' . "\n";
            $augmentedPrompt .= '  ],' . "\n";
            $augmentedPrompt .= '  "rekomendasi": [' . "\n";
            $augmentedPrompt .= '    "<rekomendasi tindakan spesifik berdasarkan data pertanyaan yang ada>"' . "\n";
            $augmentedPrompt .= '  ]' . "\n";
            $augmentedPrompt .= "}\n\n";
            
            $augmentedPrompt .= "ATURAN KETAT:\n";
            $augmentedPrompt .= "1. Response WAJIB dimulai dengan karakter { dan diakhiri dengan }\n";
            $augmentedPrompt .= "2. JANGAN tulis teks apapun sebelum atau sesudah JSON\n";
            $augmentedPrompt .= "3. JANGAN sertakan statistik_per_pertanyaan di dalam JSON (akan dihitung otomatis oleh sistem)\n";
            $augmentedPrompt .= "4. Di ringkasan, gunakan Nama File/Kuesioner (" . $kuesioner->nama_file . ") BUKAN nama program studi\n";
            $augmentedPrompt .= "5. DILARANG mengarang atau membuat topik sendiri seperti 'kualitas pengajaran' atau 'fasilitas laboratorium'\n";
            $augmentedPrompt .= "6. poin_positif dan area_perbaikan HARUS mengutip TEKS PERTANYAAN ASLI dari DAFTAR PERTANYAAN yang disediakan\n";
            $augmentedPrompt .= "7. Sebutkan pertanyaan mana (dengan teks aslinya) yang mendapat nilai tinggi/rendah\n";
            $augmentedPrompt .= "8. rekomendasi HARUS berdasarkan data pertanyaan yang ada, bukan topik yang dikarang\n";
            $augmentedPrompt .= "9. Index kepuasan dalam skala 0-4\n";
            
            // STEP 4: Generate dengan AI
            Log::info("RAG Step 4: Generating analysis with AI model");
            $response = $this->callAI([
                'role' => 'system',
                'content' => 'Anda adalah AI Agent ahli analisis data kuesioner akademik. Tugas Anda menganalisis data dan memberikan insight dalam format JSON MURNI. SANGAT PENTING: (1) Response HARUS dimulai langsung dengan { dan diakhiri }. (2) DILARANG mengarang topik sendiri - HANYA gunakan teks pertanyaan yang ada di DAFTAR PERTANYAAN. (3) Di ringkasan, gunakan Nama File/Kuesioner, BUKAN nama program studi. (4) poin_positif dan area_perbaikan HARUS mengutip teks pertanyaan asli dari data.'
            ], $augmentedPrompt, 2000);

            if ($response) {
                // Clean response - extract JSON robustly
                $cleanResponse = trim($response);
                
                // Remove markdown code blocks
                $cleanResponse = preg_replace('/^```(?:json)?\s*/m', '', $cleanResponse);
                $cleanResponse = preg_replace('/\s*```$/m', '', $cleanResponse);
                $cleanResponse = trim($cleanResponse);
                
                // Extract JSON object: find first { and last }
                $firstBrace = strpos($cleanResponse, '{');
                $lastBrace = strrpos($cleanResponse, '}');
                
                if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
                    $cleanResponse = substr($cleanResponse, $firstBrace, $lastBrace - $firstBrace + 1);
                }
                
                Log::info("RAG Step 5: Parsing AI response", [
                    'response_length' => strlen($cleanResponse),
                    'starts_with' => substr($cleanResponse, 0, 50)
                ]);
                
                // Parse JSON response
                $analysisResult = json_decode($cleanResponse, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON Parse Error', [
                        'error' => json_last_error_msg(),
                        'response' => substr($cleanResponse, 0, 500)
                    ]);
                    throw new \Exception('AI response JSON tidak valid: ' . json_last_error_msg());
                }
                
                if (!$analysisResult || !is_array($analysisResult)) {
                    throw new \Exception('AI response bukan array yang valid');
                }

                // Validate required fields
                $requiredFields = ['statistik', 'ringkasan', 'poin_positif', 'area_perbaikan', 'rekomendasi'];
                foreach ($requiredFields as $field) {
                    if (!isset($analysisResult[$field])) {
                        Log::warning("Missing field in AI response: $field");
                    }
                }
                
                // SELALU hitung statistik dari data real (bukan dari AI)
                // Ini memastikan nilai rata-rata dan index kepuasan selalu akurat
                Log::info("Computing statistik from real indexed data (overriding AI values)");
                
                $statistikPerPertanyaan = [];
                $totalNilai = 0;
                $jumlahPertanyaan = 0;
                
                foreach ($retrievedContext['statistik_detail'] as $qId => $stat) {
                    $totalResponden = $stat['total_responden'];
                    $nilaiRataRata = 0;
                    
                    if ($totalResponden > 0) {
                        // Rumus: (TS×1 + CS×2 + S×3 + SS×4) / total_responden
                        $nilaiRataRata = (
                            ($stat['distribusi']['TS'] * 1) +
                            ($stat['distribusi']['CS'] * 2) +
                            ($stat['distribusi']['S'] * 3) +
                            ($stat['distribusi']['SS'] * 4)
                        ) / $totalResponden;
                        
                        Log::debug("Perhitungan $qId", [
                            'TS' => $stat['distribusi']['TS'],
                            'CS' => $stat['distribusi']['CS'],
                            'S' => $stat['distribusi']['S'],
                            'SS' => $stat['distribusi']['SS'],
                            'total_responden' => $totalResponden,
                            'nilai_rata_rata' => $nilaiRataRata
                        ]);
                    }
                    
                    $statistikPerPertanyaan[$qId] = [
                        'pertanyaan' => $stat['teks_pertanyaan'],
                        'TS' => $stat['distribusi']['TS'],
                        'CS' => $stat['distribusi']['CS'],
                        'S' => $stat['distribusi']['S'],
                        'SS' => $stat['distribusi']['SS'],
                        'nilai_rata_rata' => $nilaiRataRata
                    ];
                    
                    $totalNilai += $nilaiRataRata;
                    $jumlahPertanyaan++;
                }
                
                // Override statistik_per_pertanyaan dengan data real
                $analysisResult['statistik']['statistik_per_pertanyaan'] = $statistikPerPertanyaan;
                
                // Hitung index kepuasan dari data real
                // Rumus: (Σ nilai_rata_rata semua pertanyaan) / jumlah_pertanyaan
                $indexKepuasan = $jumlahPertanyaan > 0 
                    ? $totalNilai / $jumlahPertanyaan 
                    : 0;
                $analysisResult['statistik']['index_kepuasan'] = $indexKepuasan;
                
                Log::info("Index Kepuasan (Real Data)", [
                    'total_nilai' => $totalNilai,
                    'jumlah_pertanyaan' => $jumlahPertanyaan,
                    'index_kepuasan' => $indexKepuasan
                ]);
                
                // Update rata_rata_skor (sama dengan index_kepuasan)
                $analysisResult['statistik']['rata_rata_skor'] = $indexKepuasan;
                
                // Hitung persen_kepuasan dari data real
                // Rumus: (index_kepuasan / 4) × 100%
                $persenKepuasan = ($indexKepuasan / 4) * 100;
                $analysisResult['statistik']['persen_kepuasan'] = $persenKepuasan;
                
                // Update total_responden dan total_pertanyaan dari data real
                $analysisResult['statistik']['total_responden'] = $retrievedContext['overview']['total_responden'];
                $analysisResult['statistik']['total_pertanyaan'] = $jumlahPertanyaan;
                
                // Hitung distribusi_jawaban total dari data real
                $distribusiTotal = ['TS' => 0, 'CS' => 0, 'S' => 0, 'SS' => 0];
                foreach ($retrievedContext['statistik_detail'] as $stat) {
                    $distribusiTotal['TS'] += $stat['distribusi']['TS'];
                    $distribusiTotal['CS'] += $stat['distribusi']['CS'];
                    $distribusiTotal['S'] += $stat['distribusi']['S'];
                    $distribusiTotal['SS'] += $stat['distribusi']['SS'];
                }
                $analysisResult['statistik']['distribusi_jawaban'] = $distribusiTotal;
                
                // Find pertanyaan tertinggi dan terendah dari data real
                $nilaiTertinggi = 0;
                $nilaiTerendah = 4;
                $qTertinggi = null;
                $qTerendah = null;
                
                foreach ($statistikPerPertanyaan as $qId => $stat) {
                    if ($stat['nilai_rata_rata'] > $nilaiTertinggi) {
                        $nilaiTertinggi = $stat['nilai_rata_rata'];
                        $qTertinggi = ['id' => $qId, 'teks' => $stat['pertanyaan'], 'nilai' => $stat['nilai_rata_rata']];
                    }
                    if ($stat['nilai_rata_rata'] < $nilaiTerendah) {
                        $nilaiTerendah = $stat['nilai_rata_rata'];
                        $qTerendah = ['id' => $qId, 'teks' => $stat['pertanyaan'], 'nilai' => $stat['nilai_rata_rata']];
                    }
                }
                
                if ($qTertinggi) {
                    $analysisResult['statistik']['pertanyaan_tertinggi'] = $qTertinggi;
                }
                if ($qTerendah) {
                    $analysisResult['statistik']['pertanyaan_terendah'] = $qTerendah;
                }

                // SERVER-SIDE: Build poin_positif, area_perbaikan, rekomendasi dari data REAL
                // Tidak menggunakan output AI untuk ini karena AI sering hallusinasi
                
                // Sort pertanyaan berdasarkan nilai_rata_rata
                $sortedQuestions = $statistikPerPertanyaan;
                uasort($sortedQuestions, function($a, $b) {
                    return $b['nilai_rata_rata'] <=> $a['nilai_rata_rata'];
                });
                
                $sortedKeys = array_keys($sortedQuestions);
                $totalQ = count($sortedKeys);
                $avgKeseluruhan = $indexKepuasan;
                
                // Poin Positif: ambil pertanyaan dengan nilai tertinggi (di atas rata-rata)
                $poinPositif = [];
                foreach ($sortedQuestions as $qId => $stat) {
                    if ($stat['nilai_rata_rata'] >= $avgKeseluruhan && count($poinPositif) < 5) {
                        $teks = $stat['pertanyaan'];
                        $nilai = number_format($stat['nilai_rata_rata'], 5);
                        $poinPositif[] = "[{$qId}] {$teks} (rata-rata: {$nilai} dari skala 4)";
                    }
                }
                // Jika tidak ada yang di atas rata-rata, ambil 3 tertinggi
                if (empty($poinPositif)) {
                    $top3 = array_slice($sortedQuestions, 0, 3, true);
                    foreach ($top3 as $qId => $stat) {
                        $teks = $stat['pertanyaan'];
                        $nilai = number_format($stat['nilai_rata_rata'], 5);
                        $poinPositif[] = "[{$qId}] {$teks} (rata-rata: {$nilai} dari skala 4)";
                    }
                }
                $analysisResult['poin_positif'] = $poinPositif;
                
                // Area Perbaikan: ambil pertanyaan dengan nilai terendah (di bawah rata-rata)
                $areaPerbaikan = [];
                $reverseSorted = array_reverse($sortedQuestions, true);
                foreach ($reverseSorted as $qId => $stat) {
                    if ($stat['nilai_rata_rata'] < $avgKeseluruhan && count($areaPerbaikan) < 5) {
                        $teks = $stat['pertanyaan'];
                        $nilai = number_format($stat['nilai_rata_rata'], 5);
                        $areaPerbaikan[] = "[{$qId}] {$teks} - perlu ditingkatkan (rata-rata: {$nilai} dari skala 4)";
                    }
                }
                // Jika tidak ada yang di bawah rata-rata, ambil 2 terendah
                if (empty($areaPerbaikan)) {
                    $bottom2 = array_slice($reverseSorted, 0, 2, true);
                    foreach ($bottom2 as $qId => $stat) {
                        $teks = $stat['pertanyaan'];
                        $nilai = number_format($stat['nilai_rata_rata'], 5);
                        $areaPerbaikan[] = "[{$qId}] {$teks} - perlu ditingkatkan (rata-rata: {$nilai} dari skala 4)";
                    }
                }
                $analysisResult['area_perbaikan'] = $areaPerbaikan;
                
                // Rekomendasi: buat berdasarkan area perbaikan dari data real
                $rekomendasi = [];
                foreach (array_slice(array_reverse($sortedQuestions, true), 0, 3, true) as $qId => $stat) {
                    if ($stat['nilai_rata_rata'] < $avgKeseluruhan) {
                        $teks = $stat['pertanyaan'];
                        $rekomendasi[] = "Perlu peningkatan pada aspek: {$teks}";
                    }
                }
                if (empty($rekomendasi)) {
                    $rekomendasi[] = "Pertahankan kualitas pada semua aspek yang sudah baik";
                    $rekomendasi[] = "Lakukan evaluasi berkala untuk memastikan kepuasan mahasiswa tetap terjaga";
                }
                // Tambahkan rekomendasi dari AI jika ada dan tidak hallusinasi
                if (isset($analysisResult['rekomendasi']) && is_array($analysisResult['rekomendasi'])) {
                    foreach ($analysisResult['rekomendasi'] as $rek) {
                        // Hanya tambahkan jika bukan duplikat dan cukup panjang (bukan kode Q)
                        if (strlen($rek) > 30 && !in_array($rek, $rekomendasi)) {
                            $rekomendasi[] = $rek;
                        }
                    }
                }
                $analysisResult['rekomendasi'] = array_slice($rekomendasi, 0, 5);

                Log::info("=== RAG PROCESS COMPLETED ===", [
                    'kuesioner_id' => $kuesioner->id,
                    'index_kepuasan' => $analysisResult['statistik']['index_kepuasan'] ?? 'N/A',
                    'jumlah_pertanyaan' => count($analysisResult['statistik']['statistik_per_pertanyaan'] ?? [])
                ]);

                return $analysisResult;
            }

            throw new \Exception('AI tidak menghasilkan response');

        } catch (\Exception $e) {
            Log::error('RAG Process Failed', [
                'message' => $e->getMessage(),
                'kuesioner_id' => $kuesioner->id
            ]);
            throw $e;
        }
    }

    /**
     * Call AI API dengan error handling
     */
    private function callAI($systemMessage, $userPrompt, $maxTokens = 800)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->baseUrl . '/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    $systemMessage,
                    [
                        'role' => 'user',
                        'content' => $userPrompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => $maxTokens,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $message = $data['choices'][0]['message']['content'] ?? null;

                if ($message) {
                    return trim($message);
                }
            }

            Log::error('AI API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('AI API Exception', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate text using AI with a simple prompt
     * 
     * @param string $prompt
     * @param int $maxTokens
     * @return string
     */
    public function generateText($prompt, $maxTokens = 800)
    {
        $systemMessage = [
            'role' => 'system',
            'content' => 'Anda adalah asisten AI yang membantu membuat pesan email profesional dalam bahasa Indonesia. Tulis LANGSUNG pesan yang diminta TANPA intro, penjelasan, atau komentar tambahan.'
        ];

        $result = $this->callAI($systemMessage, $prompt, $maxTokens);
        
        // Clean up any intro text that AI might add
        $result = preg_replace('/^(Baiklah|Berikut|Tentu|Saya akan|Oke|Ok)[^:]*:\s*/i', '', $result);
        $result = preg_replace('/^.*?Kepada Yth\./s', 'Kepada Yth.', $result);
        
        return trim($result);
    }

    /**
     * Build prompt untuk reminder
     */
    private function buildReminderPrompt($dosenList, $userProdi = null, $type = 'rps')
    {
        $namaDosen = $dosenList->count() > 1
            ? 'Bapak/Ibu Dosen'
            : 'Bapak/Ibu ' . $dosenList->first()->nama_lengkap;

        $mataKuliahList = [];
        $kelasWaliList = [];
        $prodiList = [];
        foreach ($dosenList as $dosen) {
            if ($dosen->matakuliah && $dosen->matakuliah->count() > 0) {
                foreach ($dosen->matakuliah as $mk) {
                    $mataKuliahList[] = $mk->nama_mk;
                }
            }
            if ($dosen->kelas_wali) {
                $kelasWaliList[] = $dosen->kelas_wali;
            }
            if ($dosen->prodi) {
                $prodiList[] = $dosen->prodi->nama_prodi;
            }
        }
        $mataKuliahText = !empty($mataKuliahList)
            ? ' - Dosen Mata Kuliah ' . implode(', ', array_unique($mataKuliahList))
            : '';
        
        $kelasWaliText = !empty($kelasWaliList)
            ? ' - Wali Kelas ' . implode(', ', array_unique($kelasWaliList))
            : '';
        
        $prodiText = !empty($prodiList)
            ? ' - Kepala Program Studi ' . implode(', ', array_unique($prodiList))
            : '';
        
        // For soal type, update role to include prodi name
        $kaprodiRole = 'Kepala Program Studi';
        if (!empty($prodiList)) {
            $kaprodiRole = 'Kepala Program Studi ' . implode(', ', array_unique($prodiList));
        }

        $timGKM = 'Tim GKM';
        if ($userProdi && $userProdi->kode_prodi) {
            $timGKM = 'Tim GKM ' . $userProdi->kode_prodi;
        }

        $typeDescriptions = [
            'perwalian' => [
                'title' => 'perwalian dan persiapan perkuliahan',
                'role' => 'Wali Kelas',
                'info' => $kelasWaliText,
                'points' => [
                    'Membantu mahasiswa dalam perencanaan akademik',
                    'Monitoring perkembangan mahasiswa',
                    'Memastikan kesiapan perkuliahan semester ini'
                ]
            ],
            'materi' => [
                'title' => 'upload materi perkuliahan di CIS',
                'role' => 'Dosen Mata Kuliah',
                'info' => $mataKuliahText,
                'points' => [
                    'Memudahkan akses mahasiswa ke materi pembelajaran',
                    'Mendukung pembelajaran mandiri mahasiswa',
                    'Memenuhi standar kualitas pembelajaran'
                ]
            ],
            'soal' => [
                'title' => 'review soal ujian',
                'role' => $kaprodiRole,
                'info' => '',
                'points' => [
                    'Memastikan kualitas soal ujian',
                    'Kesesuaian dengan capaian pembelajaran',
                    'Standar penilaian yang objektif'
                ]
            ],
            'rps' => [
                'title' => 'upload RPS (Rencana Pembelajaran Semester)',
                'role' => 'Dosen Mata Kuliah',
                'info' => $mataKuliahText,
                'points' => [
                    'Kesiapan pembelajaran',
                    'Standar akreditasi',
                    'Panduan untuk mahasiswa'
                ]
            ]
        ];

        $desc = $typeDescriptions[$type] ?? $typeDescriptions['rps'];

        $prompt = "Buatkan pesan reminder FORMAL dan SOPAN untuk mengingatkan dosen tentang {$desc['title']} dengan ketentuan berikut:\n\n";
        $prompt .= "Penerima: {$namaDosen}{$desc['info']}\n";
        $prompt .= "Jumlah penerima: " . $dosenList->count() . "\n\n";
        $prompt .= "ATURAN PENTING:\n";
        $prompt .= "1. Gunakan bahasa Indonesia yang SANGAT FORMAL, SOPAN, dan PROFESIONAL\n";
        $prompt .= "2. Panjang sekitar 100-120 kata\n";
        $prompt .= "3. Gunakan 'Bapak/Ibu' BUKAN 'Anda'\n";
        $prompt .= "4. JANGAN gunakan kata 'kami', 'saya', atau kata ganti orang pertama\n";
        $prompt .= "5. Tulis poin PERSIS seperti yang diberikan, JANGAN diubah atau ditambahkan\n";
        $prompt .= "6. JANGAN sebutkan tanggal spesifik, cukup '3 hari ke depan'\n";
        $prompt .= "7. JANGAN tambahkan intro seperti 'Berikut adalah pesan'\n";
        $prompt .= "8. Gunakan kalimat pasif atau impersonal\n\n";
        $prompt .= "Format pesan (IKUTI PERSIS):\n";
        $prompt .= "Kepada Yth. {$namaDosen} - {$desc['role']}\n\n";
        $prompt .= "Dengan hormat,\n\n";
        $prompt .= "Melalui surat ini, disampaikan pengingat untuk segera melakukan {$desc['title']}. Hal ini sangat penting karena:\n\n";
        $poinText = "";
        foreach ($desc['points'] as $i => $point) {
            $poinText .= ($i + 1) . ". {$point}\n";
        }
        $prompt .= $poinText;
        $prompt .= "\nMohon Bapak/Ibu dapat menyelesaikan dalam waktu 3 hari ke depan. Apabila Bapak/Ibu mengalami kendala atau memerlukan bantuan, silakan menghubungi Tim GKM.\n\n";
        $prompt .= "Terima kasih atas perhatian dan kerja sama Bapak/Ibu.\n\n";
        $prompt .= "{$timGKM}\n\n";
        $prompt .= "PENTING: Tulis poin-poin ini PERSIS seperti ini:\n{$poinText}";

        return $prompt;
    }
}
