<?php

namespace App\Services;

use App\Models\KuesioneUpload;
use App\Models\LaporanBulanan;
use App\Models\TemplateLaporan;
use App\Models\DocumentChunk;
use App\Models\PeriodeAkademik;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class LaporanKuesioneService
{
    private $apiKey;
    private $baseUrl;
    private $model;
    private $textExtractionService;
    private $structureService;
    private $advancedChunkingService;

    public function __construct(
        TextExtractionService $textExtractionService,
        DocumentStructureService $structureService,
        AdvancedChunkingService $advancedChunkingService
    ) {
        // Use centralized LLM configuration from config/services.php
        $this->apiKey = config('services.llm.api_key');
        $this->baseUrl = config('services.llm.base_url');
        $this->model = config('services.llm.model');

        $this->textExtractionService = $textExtractionService;
        $this->structureService = $structureService;
        $this->advancedChunkingService = $advancedChunkingService;
    }

    /**
     * RAG STEP 1: Collect Kuesioner Data (Retrieve)
     * Ambil semua kuesioner completed dalam periode tertentu
     */
    public function collectKuesioneData($periode, $prodiId = null, $tipeLaporan = 'UTS')
    {
        Log::info("=== RAG STEP 1: Collecting Kuesioner Data ===", [
            'periode' => $periode,
            'prodi_id' => $prodiId
        ]);

        // Build base query - more flexible filter
        $query = KuesioneUpload::query();

        // TEMPORARY: Remove prodi filter to get any available data
        // Filter by prodi if specified - Check via user relationship only
        // if ($prodiId) {
        //     $query->whereHas('user', function ($uq) use ($prodiId) {
        //         $uq->where('prodi_id', $prodiId);
        //     });
        // }

        // Get ALL records for now (will be filtered manually later if needed)
        Log::info("Getting all kuesioner records without prodi filter (temporary)");

        $periodeContext = $this->resolvePeriodeContext($periode);
        $semester = $periodeContext['semester'];
        $tahunAjaran = $periodeContext['tahunAjaran'];

        Log::info("Determined semester and tahun ajaran", [
            'semester' => $semester,
            'tahun_ajaran' => $tahunAjaran
        ]);

        // Try to match by semester if the column exists AND has data
        $hasSemesterData = \DB::table('kuesioner_uploads')
            ->whereNotNull('semester')
            ->where('semester', '!=', '')
            ->exists();

        if (\Schema::hasColumn('kuesioner_uploads', 'semester') && $hasSemesterData) {
            Log::info("Filtering by semester", ['semester' => $semester]);
            $query->where('semester', $semester);
        } else {
            Log::warning("Semester column empty or doesn't exist, skipping semester filter");
        }

        if (!empty($tipeLaporan) && \Schema::hasColumn('kuesioner_uploads', 'jenis_kuesioner')) {
            Log::info("Filtering by jenis_kuesioner", ['tipe_laporan' => $tipeLaporan]);
            $query->whereRaw('LOWER(TRIM(jenis_kuesioner)) = ?', [strtolower(trim($tipeLaporan))]);
        }

        // Get results - don't require hasil_analisis to be not null
        // We'll use raw data if hasil_analisis is not available
        $results = $query->orderBy('created_at', 'desc')->get();

        Log::info("Query results", [
            'count' => $results->count(),
            'sample' => $results->take(3)->map(function($k) {
                return [
                    'id' => $k->id,
                    'kode' => $k->kode_matakuliah,
                    'nama' => $k->nama_matakuliah,
                    'tingkat' => $k->tingkat,
                    'has_analisis' => !empty($k->hasil_analisis),
                    'index_kepuasan' => $k->index_kepuasan,
                    'semester' => $k->semester
                ];
            })
        ]);

        // If no results, try without semester filter (get latest for any semester)
        if ($results->isEmpty()) {
            Log::warning("No data found with semester filter, trying without it");
            $query = KuesioneUpload::query();
            if ($prodiId) {
                $query->whereHas('user', function ($uq) use ($prodiId) {
                    $uq->where('prodi_id', $prodiId);
                });
            }
            if (!empty($tipeLaporan) && \Schema::hasColumn('kuesioner_uploads', 'jenis_kuesioner')) {
                $query->whereRaw('LOWER(TRIM(jenis_kuesioner)) = ?', [strtolower(trim($tipeLaporan))]);
            }
            $results = $query->orderBy('created_at', 'desc')->limit(20)->get();
        }

        Log::info("Final collected kuesioner count: " . $results->count());

        return $results;
    }

    /**
     * RAG STEP 2: Aggregate Statistics
     * Hitung statistik agregat dari semua kuesioner
     */
    public function aggregateStatistik($kuesioneList)
    {
        Log::info("=== RAG STEP 2: Aggregating Statistics ===");

        $totalKuesioner = $kuesioneList->count();
        $totalResponden = 0;
        $sumIndexKepuasan = 0;
        $kuesioneData = [];

        foreach ($kuesioneList as $kuesioner) {
            // Try to get data from hasil_analisis first, fallback to model fields
            $hasilAnalisis = $kuesioner->hasil_analisis;

            if (!empty($hasilAnalisis) && isset($hasilAnalisis['statistik'])) {
                $indexKepuasan = $hasilAnalisis['statistik']['index_kepuasan'] ?? 0;
                $responden = $hasilAnalisis['statistik']['total_responden'] ?? 0;
                $ringkasan = $hasilAnalisis['ringkasan'] ?? '';
                $poinPositif = $hasilAnalisis['poin_positif'] ?? [];
                $areaPerbaikan = $hasilAnalisis['area_perbaikan'] ?? [];
                $rekomendasi = $hasilAnalisis['rekomendasi'] ?? [];
            } else {
                // Fallback: use fields from model directly
                $indexKepuasan = $kuesioner->index_kepuasan ?? 0;
                $responden = $kuesioner->total_responden ?? 0;
                $ringkasan = 'Data dari monitoring sistem';
                $poinPositif = [];
                $areaPerbaikan = [];
                $rekomendasi = [];
            }

            $totalResponden += $responden;
            $sumIndexKepuasan += $indexKepuasan;

            $kuesioneData[] = [
                'id' => $kuesioner->id,
                'nama' => $kuesioner->nama_file ?? $kuesioner->nama_matakuliah,
                'periode' => $kuesioner->periode,
                'nama_matakuliah' => $kuesioner->nama_matakuliah,
                'kode_matakuliah' => $kuesioner->kode_matakuliah,
                'tingkat' => $kuesioner->tingkat ?? $this->extractTingkatFromKode($kuesioner->kode_matakuliah),
                'dosen_pengampu' => $kuesioner->dosen_pengampu,
                'responden' => $responden,
                'index_kepuasan' => $indexKepuasan,
                'persen_kepuasan' => ($indexKepuasan / 4) * 100,
                'ringkasan' => $ringkasan,
                'poin_positif' => $poinPositif,
                'area_perbaikan' => $areaPerbaikan,
                'rekomendasi' => $rekomendasi,
            ];
        }

        $indexKepuasanRataRata = $totalKuesioner > 0 ? $sumIndexKepuasan / $totalKuesioner : 0;
        $persenKepuasanRataRata = ($indexKepuasanRataRata / 4) * 100;

        // Sort untuk top 5
        usort($kuesioneData, function ($a, $b) {
            return $b['index_kepuasan'] <=> $a['index_kepuasan'];
        });

        $top5Tertinggi = array_slice($kuesioneData, 0, 5);
        $top5Terendah = array_slice(array_reverse($kuesioneData), 0, 5);

        $aggregatedData = [
            'total_kuesioner' => $totalKuesioner,
            'total_responden' => $totalResponden,
            'index_kepuasan_rata_rata' => round($indexKepuasanRataRata, 4),
            'persen_kepuasan_rata_rata' => round($persenKepuasanRataRata, 2),
            'kuesioner_data' => $kuesioneData,
            'top_5_tertinggi' => $top5Tertinggi,
            'top_5_terendah' => $top5Terendah,
        ];

        Log::info("Aggregated statistics", [
            'total_kuesioner' => $totalKuesioner,
            'total_responden' => $totalResponden,
            'index_rata_rata' => $indexKepuasanRataRata
        ]);

        return $aggregatedData;
    }

    /**
     * Extract tingkat from kode matakuliah (e.g., IF1111 -> 1, KU41203 -> 1)
     */
    private function extractTingkatFromKode($kodeMk)
    {
        if (empty($kodeMk)) return null;

        // Try to extract first digit after letters
        if (preg_match('/^[A-Z]+(\d)/', $kodeMk, $matches)) {
            return (int)$matches[1];
        }

        // Fallback: look for any digit
        if (preg_match('/(\d)/', $kodeMk, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }

    private function getMatakuliahGroupKey(array $kuesioner): string
    {
        $kode = trim(strtoupper($kuesioner['kode_matakuliah'] ?? ''));
        if ($kode !== '') {
            return "KODE::{$kode}";
        }

        $nama = trim(mb_strtolower($kuesioner['nama_matakuliah'] ?? ''));
        if ($nama !== '') {
            return "NAMA::{$nama}";
        }

        return 'UNKNOWN';
    }

    /**
     * Resolve periode context from periode string. Supports Y-m and descriptive semester formats.
     */
    private function resolvePeriodeContext($periode)
    {
        $context = [
            'periode' => $periode,
            'periodeObj' => null,
            'year' => null,
            'month' => null,
            'semester' => null,
            'tahunAjaran' => null,
        ];

        if (!empty($periode)) {
            if (strpos($periode, '-') !== false) {
                $parts = explode('-', $periode);
                if (count($parts) === 2 && is_numeric($parts[0]) && in_array(strtoupper($parts[1]), ['UTS', 'UAS'])) {
                    $periodeAkademik = PeriodeAkademik::find((int) $parts[0]);
                    if ($periodeAkademik) {
                        $context['semester'] = $periodeAkademik->semester;
                        $context['tahunAjaran'] = $periodeAkademik->tahun_ajaran;
                        if ($context['semester'] === 1) {
                            $yearParts = explode('/', $context['tahunAjaran']);
                            $year = (int) $yearParts[0];
                            $context['periodeObj'] = Carbon::create($year, 8, 1);
                        } else {
                            $yearParts = explode('/', $context['tahunAjaran']);
                            $year = count($yearParts) === 2 ? (int) $yearParts[1] : Carbon::now()->year;
                            $context['periodeObj'] = Carbon::create($year, 2, 1);
                        }
                    }
                }
            }

            if (preg_match('/^(\d{4})-(\d{2})$/', $periode, $matches)) {
                $context['periodeObj'] = Carbon::createFromFormat('Y-m', $periode);
                $context['year'] = (int) $matches[1];
                $context['month'] = (int) $matches[2];
            } elseif (preg_match('/\b(GANJIL|GENAP)\b/i', $periode, $matches)) {
                $context['semester'] = strtoupper($matches[1]) === 'GANJIL' ? 1 : 2;
                $year = null;
                if (preg_match('/(\d{4})/', $periode, $yearMatch)) {
                    $year = (int) $yearMatch[1];
                } elseif (preg_match('/(\d{2})\/(\d{2})/', $periode, $yearMatch)) {
                    $year = $context['semester'] === 1
                        ? 2000 + (int) $yearMatch[1]
                        : 2000 + (int) $yearMatch[2];
                }
                if (!$year) {
                    $year = Carbon::now()->year;
                }
                if ($context['semester'] === 1) {
                    $context['periodeObj'] = Carbon::create($year, 8, 1);
                } else {
                    $context['periodeObj'] = Carbon::create($year, 2, 1);
                }
            }
        }

        if (!$context['periodeObj']) {
            $context['periodeObj'] = Carbon::now();
        }

        $context['year'] = $context['periodeObj']->year;
        $context['month'] = $context['periodeObj']->month;

        if (!$context['semester']) {
            $context['semester'] = $context['month'] <= 6 ? 2 : 1;
        }

        if (!$context['tahunAjaran']) {
            $context['tahunAjaran'] = $context['semester'] === 1
                ? $context['year'] . '/' . ($context['year'] + 1)
                : ($context['year'] - 1) . '/' . $context['year'];
        }

        return $context;
    }

    /**
     * RAG STEP 3: Build Context
     * Build context untuk AI dari data yang sudah diagregasi
     */
    public function buildContext($aggregatedData, $periode, $prodi = null, $tipeLaporan = 'UTS')
    {
        Log::info("=== RAG STEP 3: Building Context ===");

        $periodeContext = $this->resolvePeriodeContext($periode);
        $bulan = $periodeContext['periodeObj']->locale('id')->translatedFormat('F');
        $tahun = $periodeContext['year'];

        $context = "=== DATA KUESIONER PERIODE {$bulan} {$tahun} ===\n\n";

        if ($prodi) {
            $context .= "Program Studi: {$prodi->nama_prodi}\n\n";
        }

        if (!empty($tipeLaporan)) {
            $context .= "Jenis Laporan: {$tipeLaporan}\n\n";
        }

        $context .= "RINGKASAN STATISTIK:\n";
        $context .= "- Total Kuesioner: {$aggregatedData['total_kuesioner']}\n";
        $context .= "- Total Responden: {$aggregatedData['total_responden']}\n";
        $context .= "- Index Kepuasan Rata-rata: {$aggregatedData['index_kepuasan_rata_rata']} (skala 0-4)\n";
        $context .= "- Persen Kepuasan Rata-rata: {$aggregatedData['persen_kepuasan_rata_rata']}%\n\n";

        // Group kuesioner by tingkat
        $kuesioneByTingkat = [];
        foreach ($aggregatedData['kuesioner_data'] as $kuesioner) {
            $tingkat = $kuesioner['tingkat'] ?? 'Unknown';
            if (!isset($kuesioneByTingkat[$tingkat])) {
                $kuesioneByTingkat[$tingkat] = [];
            }
            $kuesioneByTingkat[$tingkat][] = $kuesioner;
        }

        // Sort tingkat
        ksort($kuesioneByTingkat);

        $context .= "DETAIL PER TINGKAT:\n\n";
        foreach ($kuesioneByTingkat as $tingkat => $kuesioneList) {
            $context .= "TINGKAT {$tingkat}:\n";

            $matakuliahGroups = [];
            foreach ($kuesioneList as $k) {
                $groupKey = $this->getMatakuliahGroupKey($k);
                if (!isset($matakuliahGroups[$groupKey])) {
                    $matakuliahGroups[$groupKey] = [
                        'kode_matakuliah' => $k['kode_matakuliah'] ?? '-',
                        'nama_matakuliah' => $k['nama_matakuliah'] ?? '-',
                        'entries' => [],
                    ];
                }
                $matakuliahGroups[$groupKey]['entries'][] = $k;
            }

            $totalMatakuliah = count($matakuliahGroups);
            $context .= "Total Matakuliah: {$totalMatakuliah}\n\n";

            // Calculate rata-rata index kepuasan untuk tingkat ini
            $totalIndex = 0;
            foreach ($kuesioneList as $k) {
                $totalIndex += $k['index_kepuasan'];
            }
            $avgIndex = count($kuesioneList) > 0 ? $totalIndex / count($kuesioneList) : 0;
            $context .= "Rata-rata Index Kepuasan Tingkat {$tingkat}: " . number_format(round($avgIndex, 4), 4, '.', '') . "\n\n";

            $context .= "Tabel Matakuliah Tingkat {$tingkat}:\n";
            $context .= "| Kode Matakuliah | Nama Matakuliah | Dosen Pengampu | Indeks Kepuasan |\n";
            $context .= "|-----------------|-----------------|----------------|------------------|\n";

            foreach ($matakuliahGroups as $group) {
                $dosenSet = [];
                $totalMatakuliahIndex = 0;
                $entryCount = 0;
                foreach ($group['entries'] as $entry) {
                    $dosen = trim($entry['dosen_pengampu'] ?? '');
                    if ($dosen !== '' && !in_array($dosen, $dosenSet, true)) {
                        $dosenSet[] = $dosen;
                    }
                    $totalMatakuliahIndex += $entry['index_kepuasan'] ?? 0;
                    $entryCount++;
                }

                $kodeMk = $group['kode_matakuliah'];
                $namaMk = $group['nama_matakuliah'];
                $dosenPengampu = !empty($dosenSet) ? implode(', ', $dosenSet) : '-';
                $avgIndexPerMatakuliah = $entryCount > 0 ? round($totalMatakuliahIndex / $entryCount, 4) : 0;

                $context .= "| {$kodeMk} | {$namaMk} | {$dosenPengampu} | " . number_format($avgIndexPerMatakuliah, 4, '.', '') . " |\n";
            }

            $context .= "\nMasukan/Saran untuk Tingkat {$tingkat}:\n";
            $context .= "| Kode Matakuliah | Nama Matakuliah | Dosen Pengampu | Masukan/Saran |\n";
            $context .= "|-----------------|-----------------|----------------|---------------|\n";

            foreach ($matakuliahGroups as $group) {
                $dosenSet = [];
                $rekomendasiSeen = [];
                $rekomendasiTexts = [];
                foreach ($group['entries'] as $entry) {
                    $dosen = trim($entry['dosen_pengampu'] ?? '');
                    if ($dosen !== '' && !in_array($dosen, $dosenSet, true)) {
                        $dosenSet[] = $dosen;
                    }

                    $rekomendasiArr = $entry['rekomendasi'] ?? [];
                    if (!empty($rekomendasiArr) && is_array($rekomendasiArr)) {
                        foreach ($rekomendasiArr as $rek) {
                            $rek = trim($rek);
                            $key = mb_strtolower($rek);
                            if ($rek !== '' && !in_array($key, $rekomendasiSeen, true)) {
                                $rekomendasiSeen[] = $key;
                                $rekomendasiTexts[] = $this->shortenRekomendasi($rek);
                            }
                        }
                    } else {
                        $ringkasan = trim($entry['ringkasan'] ?? '');
                        if ($ringkasan !== '') {
                            $key = mb_strtolower($ringkasan);
                            if (!in_array($key, $rekomendasiSeen, true)) {
                                $rekomendasiSeen[] = $key;
                                $rekomendasiTexts[] = $this->shortenRekomendasi($ringkasan);
                            }
                        }
                    }
                }

                $kodeMk = $group['kode_matakuliah'];
                $namaMk = $group['nama_matakuliah'];
                $dosenPengampu = !empty($dosenSet) ? implode(', ', $dosenSet) : '-';
                if (!empty($rekomendasiTexts)) {
                    $numbered = [];
                    foreach ($rekomendasiTexts as $i => $rek) {
                        $numbered[] = ($i + 1) . '. ' . $rek;
                    }
                    $rekomendasiText = implode(' ', $numbered);
                } else {
                    $rekomendasiText = '-';
                }

                $context .= "| {$kodeMk} | {$namaMk} | {$dosenPengampu} | {$rekomendasiText} |\n";
            }
            $context .= "\n";
        }

        $context .= "TOP 5 KUESIONER TERTINGGI:\n";

        foreach ($aggregatedData['top_5_tertinggi'] as $idx => $kuesioner) {
            $context .= ($idx + 1) . ". {$kuesioner['nama']} - Index: " . number_format($kuesioner['index_kepuasan'], 4, '.', '') . "\n";
        }

        $context .= "\nTOP 5 KUESIONER TERENDAH:\n";
        foreach ($aggregatedData['top_5_terendah'] as $idx => $kuesioner) {
            $context .= ($idx + 1) . ". {$kuesioner['nama']} - Index: " . number_format($kuesioner['index_kepuasan'], 4, '.', '') . "\n";
        }

        $context .= "\n=== DATA UNTUK KESIMPULAN ===\n";
        $context .= "Indeks Kepuasan Keseluruhan Prodi: {$aggregatedData['index_kepuasan_rata_rata']}\n";
        $context .= "Indikator Minimum Kepuasan: 2.8\n";
        $context .= "Status: " . ($aggregatedData['index_kepuasan_rata_rata'] >= 2.8 ? "Melampaui target" : "Belum mencapai target") . "\n";

        return $context;
    }

    /**
     * RAG STEP 4: Augment Prompt with Template
     * Augment prompt dengan template structure
     */
    public function augmentPromptWithTemplate($template, $context, $periode, $tipeLaporan = 'UTS')
    {
        Log::info("=== RAG STEP 4: Augmenting Prompt with Template ===");

        $periodeContext = $this->resolvePeriodeContext($periode);
        $bulan = $periodeContext['periodeObj']->locale('id')->translatedFormat('F');
        $tahun = $periodeContext['year'];

        $tipeLabel = $tipeLaporan === 'UTS' ? 'Ujian Tengah Semester' : 'Ujian Akhir Semester';

        $prompt = "Anda adalah AI Agent yang bertugas membuat laporan kuesioner kepuasan mahasiswa.\n\n";
        $prompt .= "JENIS LAPORAN: {$tipeLabel} ({$tipeLaporan})\n";
        $prompt .= "Buat laporan yang spesifik untuk " . strtolower($tipeLabel) . ".\n\n";

        // Try to get template content from multiple sources
        $templateContent = null;
        
        if ($template) {
            // First, try contoh_konten
            if (!empty($template->contoh_konten)) {
                $templateContent = $template->contoh_konten;
                Log::info("Using template contoh_konten");
            } 
            // Second, try to extract from template file
            elseif (!empty($template->file_path)) {
                try {
                    $templateContent = $this->extractTemplateContent($template);
                    if ($templateContent) {
                        Log::info("Successfully extracted template content from file", [
                            'file_path' => $template->file_path,
                            'content_length' => strlen($templateContent)
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to extract template content from file", [
                        'template_id' => $template->id,
                        'file_path' => $template->file_path,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        if ($templateContent) {
            $prompt .= "TEMPLATE LAPORAN:\n";
            $prompt .= "Gunakan struktur dan format berikut sebagai referensi:\n\n";
            $prompt .= $templateContent . "\n\n";
            $prompt .= "PENTING: Ikuti struktur template di atas, tapi isi dengan data aktual di bawah.\n\n";
        } else {
            Log::info("No template content available, using default structure");
        }

        $prompt .= "DATA KUESIONER:\n";
        $prompt .= $context . "\n\n";

        $prompt .= "TUGAS ANDA:\n";
        $prompt .= "Buat laporan {$tipeLaporan} komprehensif untuk periode {$bulan} {$tahun} dengan struktur:\n\n";
        $prompt .= "1. RINGKASAN EKSEKUTIF\n";
        $prompt .= "   - Overview singkat tentang kepuasan mahasiswa untuk {$tipeLabel}\n";
        $prompt .= "   - Highlight utama (positif dan negatif)\n";
        $prompt .= "   - Trend dibanding periode sebelumnya (jika ada data)\n\n";

        $prompt .= "2. ANALISIS PER KUESIONER\n";
        $prompt .= "   - Tabel ringkasan semua kuesioner\n";
        $prompt .= "   - Kategori: Sangat Baik (>3.5), Baik (3.0-3.5), Cukup (2.5-3.0), Kurang (<2.5)\n\n";

        $prompt .= "3. TOP 5 KUESIONER TERTINGGI\n";
        $prompt .= "   - List dengan penjelasan mengapa tinggi\n\n";

        $prompt .= "4. TOP 5 KUESIONER TERENDAH\n";
        $prompt .= "   - List dengan analisis masalah\n\n";

        $prompt .= "5. INSIGHT UTAMA\n";
        $prompt .= "   - 5-7 insight penting dari data\n";
        $prompt .= "   - Pola yang terlihat\n";
        $prompt .= "   - Temuan menarik\n\n";

        $prompt .= "6. REKOMENDASI STRATEGIS\n";
        $prompt .= "   - 5-7 rekomendasi actionable\n";
        $prompt .= "   - Prioritas: High, Medium, Low\n";
        $prompt .= "   - Timeline implementasi\n\n";

        $prompt .= "OUTPUT FORMAT (JSON):\n";
        $prompt .= "{\n";
        $prompt .= '  "metadata": {' . "\n";
        $prompt .= '    "periode": "' . $periode . '",' . "\n";
        $prompt .= '    "bulan": "' . $bulan . '",' . "\n";
        $prompt .= '    "tahun": ' . $tahun . ',' . "\n";
        $prompt .= '    "tipe_laporan": "' . $tipeLaporan . '"' . "\n";
        $prompt .= '  },' . "\n";
        $prompt .= '  "ringkasan_eksekutif": {' . "\n";
        $prompt .= '    "overview": "...",' . "\n";
        $prompt .= '    "highlight_positif": ["...", "..."],' . "\n";
        $prompt .= '    "highlight_negatif": ["...", "..."],' . "\n";
        $prompt .= '    "trend": "meningkat/menurun/stabil"' . "\n";
        $prompt .= '  },' . "\n";
        $prompt .= '  "statistik_utama": {' . "\n";
        $prompt .= '    "total_kuesioner": <int>,' . "\n";
        $prompt .= '    "total_responden": <int>,' . "\n";
        $prompt .= '    "index_kepuasan_rata_rata": <float>,' . "\n";
        $prompt .= '    "persen_kepuasan_rata_rata": <float>' . "\n";
        $prompt .= '  },' . "\n";
        $prompt .= '  "analisis_per_kuesioner": [...],' . "\n";
        $prompt .= '  "top_5_tertinggi": [...],' . "\n";
        $prompt .= '  "top_5_terendah": [...],' . "\n";
        $prompt .= '  "insight_utama": ["...", "...", "..."],' . "\n";
        $prompt .= '  "rekomendasi": [' . "\n";
        $prompt .= '    {"rekomendasi": "...", "prioritas": "High/Medium/Low", "timeline": "..."}' . "\n";
        $prompt .= '  ]' . "\n";
        $prompt .= "}\n\n";

        $prompt .= "PENTING:\n";
        $prompt .= "1. Response HARUS JSON valid tanpa teks tambahan\n";
        $prompt .= "2. Gunakan data aktual dari context\n";
        $prompt .= "3. Insight harus spesifik dan actionable\n";
        $prompt .= "4. Rekomendasi harus realistis dan terukur\n";
        $prompt .= "5. Bahasa Indonesia formal dan profesional\n";
        $prompt .= "6. Laporan ini untuk {$tipeLabel}, jadi sesuaikan konteks dan wording-nya\n";

        return $prompt;
    }

    /**
     * RAG STEP 5: Generate dengan AI
     * Call AI untuk generate laporan
     */
    public function generateLaporan($periode, $prodiId = null, $templateId = null, $tipeLaporan = 'UTS')
    {
        try {
            Log::info("=== Starting Laporan Generation ===", [
                'periode' => $periode,
                'prodi_id' => $prodiId,
                'template_id' => $templateId,
                'tipe_laporan' => $tipeLaporan
            ]);

            // Step 1: Collect Data
            $kuesioneList = $this->collectKuesioneData($periode, $prodiId, $tipeLaporan);

            if ($kuesioneList->isEmpty()) {
                throw new \Exception('Tidak ada kuesioner completed untuk periode ini');
            }

            // Step 2: Aggregate
            $aggregatedData = $this->aggregateStatistik($kuesioneList);

            // Get prodi info
            $prodi = $prodiId ? \App\Models\Prodi::find($prodiId) : null;

            // Step 3: Build Context
            $context = $this->buildContext($aggregatedData, $periode, $prodi, $tipeLaporan);

            // Get template
            $template = $templateId ? TemplateLaporan::find($templateId) : null;

            // Step 4: Augment Prompt
            $augmentedPrompt = $this->augmentPromptWithTemplate($template, $context, $periode, $tipeLaporan);

            // Step 5: Generate with AI
            Log::info("=== RAG STEP 5: Generating with AI ===");

            // Use UnifiedAIService instead of direct API call
            $aiService = app(\App\Services\UnifiedAIService::class);

            $messages = [
                [
                    'role' => 'system',
                    'content' => 'Anda adalah AI Agent ahli dalam membuat laporan analisis kuesioner akademik. Tugas Anda adalah menganalisis data kuesioner dan membuat laporan ' . $tipeLaporan . ' (Ujian Tengah/Akhir Semester) yang komprehensif, profesional, dan actionable dalam format JSON.'
                ],
                [
                    'role' => 'user',
                    'content' => $augmentedPrompt
                ]
            ];

            $aiResult = $aiService->generateChat($messages, [
                'max_tokens' => 4000,
                'temperature' => 0.7
            ]);

            if (!$aiResult['success'] || empty($aiResult['text'])) {
                Log::error('AI returned empty response', [
                    'error' => $aiResult['error'] ?? 'Unknown error'
                ]);
                throw new \Exception('AI tidak menghasilkan response: ' . ($aiResult['error'] ?? 'Unknown error'));
            }

            $response = $aiResult['text'];

            if (!$response) {
                throw new \Exception('AI tidak menghasilkan response');
            }

            // Clean and parse response
            $cleanResponse = trim($response);
            $cleanResponse = preg_replace('/```(?:json)?\s*/i', '', $cleanResponse);
            $cleanResponse = preg_replace('/```\s*/i', '', $cleanResponse);

            // Try to extract just the JSON object
            $start = strpos($cleanResponse, '{');
            $end = strrpos($cleanResponse, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $cleanResponse = substr($cleanResponse, $start, $end - $start + 1);
            }

            $hasilLaporan = json_decode($cleanResponse, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $jsonError = json_last_error_msg();
                Log::error('JSON Parse Error', [
                    'error' => $jsonError,
                    'response' => substr($cleanResponse, 0, 1000)
                ]);
                throw new \Exception('AI response tidak valid: ' . $jsonError);
            }

            // Add aggregated data to hasil
            $hasilLaporan['statistik_utama'] = [
                'total_kuesioner' => $aggregatedData['total_kuesioner'],
                'total_responden' => $aggregatedData['total_responden'],
                'index_kepuasan_rata_rata' => $aggregatedData['index_kepuasan_rata_rata'],
                'persen_kepuasan_rata_rata' => $aggregatedData['persen_kepuasan_rata_rata'],
            ];

            // Add tipe_laporan to hasil
            $hasilLaporan['tipe_laporan'] = $tipeLaporan;

            Log::info("=== Laporan Generation Completed ===");

            return [
                'hasil_laporan' => $hasilLaporan,
                'aggregated_data' => $aggregatedData,
            ];

        } catch (\Exception $e) {
            Log::error('Laporan Generation Failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Call AI API (supports Ollama, GROQ, OpenAI-compatible)
     */
    // private function callAI($systemMessage, $userPrompt, $maxTokens = 4000)
    // {
    //     $maxRetries = 3;
    //     $retryDelay = 2; // seconds

    //     for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
    //         try {
    //             Log::info("=== Calling AI API (Attempt $attempt/$maxRetries) ===", [
    //                 'model' => $this->model,
    //                 'base_url' => $this->baseUrl,
    //                 'prompt_length' => strlen($userPrompt),
    //                 'max_tokens' => $maxTokens
    //             ]);

    //             $headers = ['Content-Type' => 'application/json'];

    //             // Add Authorization header only if API key is not 'ollama'
    //             if ($this->apiKey && $this->apiKey !== 'ollama') {
    //                 $headers['Authorization'] = 'Bearer ' . $this->apiKey;
    //             }

    //             $response = Http::withHeaders($headers)
    //                 ->timeout(180) // 3 minutes for local models
    //                 ->post($this->baseUrl . '/chat/completions', [
    //                     'model' => $this->model,
    //                     'messages' => [
    //                         $systemMessage,
    //                         [
    //                             'role' => 'user',
    //                             'content' => $userPrompt
    //                         ]
    //                     ],
    //                     'temperature' => 0.7,
    //                     'max_tokens' => $maxTokens,
    //                     'stream' => false,
    //                 ]);

    //             Log::info("AI API Response Status", [
    //                 'status' => $response->status(),
    //                 'successful' => $response->successful(),
    //                 'attempt' => $attempt
    //             ]);

    //             if ($response->successful()) {
    //                 $data = $response->json();
    //                 $content = $data['choices'][0]['message']['content'] ?? null;

    //                 if (!$content) {
    //                     Log::error('AI Response Empty', [
    //                         'response_data' => $data
    //                     ]);
    //                     throw new \Exception('AI response kosong. Periksa konfigurasi model atau coba lagi.');
    //                 }

    //                 Log::info("AI Response Received", [
    //                     'has_content' => !empty($content),
    //                     'content_length' => $content ? strlen($content) : 0,
    //                     'attempt' => $attempt
    //                 ]);

    //                 return $content;
    //             }

    //             $errorBody = $response->body();
    //             $statusCode = $response->status();

    //             Log::error('AI API Error', [
    //                 'status' => $statusCode,
    //                 'body' => $errorBody,
    //                 'attempt' => $attempt
    //             ]);

    //             // Parse error message for better user feedback
    //             $errorMessage = 'AI API error (HTTP ' . $statusCode . ')';
    //             $isRateLimitError = false;

    //             try {
    //                 $errorData = json_decode($errorBody, true);
    //                 if (isset($errorData['error']['message'])) {
    //                     $errorMessage .= ': ' . $errorData['error']['message'];

    //                     // Check if it's a rate limit error
    //                     if (isset($errorData['error']['code']) &&
    //                         $errorData['error']['code'] === 'rate_limit_exceeded') {
    //                         $isRateLimitError = true;
    //                     }
    //                 } elseif (isset($errorData['message'])) {
    //                     $errorMessage .= ': ' . $errorData['message'];
    //                 }
    //             } catch (\Exception $e) {
    //                 // If can't parse error, use raw body
    //                 if (strlen($errorBody) < 200) {
    //                     $errorMessage .= ': ' . $errorBody;
    //                 }
    //             }

    //             // Retry on rate limit errors (413, 429)
    //             if (($statusCode === 413 || $statusCode === 429 || $isRateLimitError) && $attempt < $maxRetries) {
    //                 $waitTime = $retryDelay * $attempt; // Exponential backoff
    //                 Log::warning("Rate limit hit, retrying in {$waitTime} seconds...", [
    //                     'attempt' => $attempt,
    //                     'max_retries' => $maxRetries
    //                 ]);
    //                 sleep($waitTime);
    //                 continue; // Retry
    //             }

    //             throw new \Exception($errorMessage);

    //         } catch (\Illuminate\Http\Client\ConnectionException $e) {
    //             Log::error('AI API Connection Error', [
    //                 'message' => $e->getMessage(),
    //                 'base_url' => $this->baseUrl,
    //                 'attempt' => $attempt
    //             ]);

    //             if ($attempt < $maxRetries) {
    //                 $waitTime = $retryDelay * $attempt;
    //                 Log::warning("Connection failed, retrying in {$waitTime} seconds...");
    //                 sleep($waitTime);
    //                 continue;
    //             }

    //             throw new \Exception('Tidak dapat terhubung ke AI service. Pastikan service berjalan di: ' . $this->baseUrl);

    //         } catch (\Illuminate\Http\Client\RequestException $e) {
    //             Log::error('AI API Request Error', [
    //                 'message' => $e->getMessage(),
    //                 'attempt' => $attempt
    //             ]);
    //             throw new \Exception('Request ke AI service gagal: ' . $e->getMessage());

    //         } catch (\Exception $e) {
    //             // Re-throw if already our custom exception
    //             if (strpos($e->getMessage(), 'AI') !== false ||
    //                 strpos($e->getMessage(), 'Tidak dapat terhubung') !== false ||
    //                 strpos($e->getMessage(), 'Request ke AI') !== false) {
    //                 throw $e;
    //             }

    //             Log::error('AI API Exception', [
    //                 'message' => $e->getMessage(),
    //                 'trace' => $e->getTraceAsString(),
    //                 'attempt' => $attempt
    //             ]);
    //             throw new \Exception('Error saat memanggil AI: ' . $e->getMessage());
    //         }
    //     }

    //     throw new \Exception('AI request gagal setelah ' . $maxRetries . ' percobaan');
    // }

    /**
     * Get Active Template
     */
    // public function getActiveTemplate($jenisTemplate = 'laporan_bulanan')
    // {
    //     return TemplateLaporan::active()
    //         ->jenis($jenisTemplate)
    //         ->latest()
    //         ->first();
    // }

    /**
     * Singkatkan kalimat rekomendasi dengan menghapus boilerplate yang panjang
     * dan menyederhanakan pola bahasa yang berulang.
     */
    // private function shortenRekomendasi(string $text): string
    // {
    //     // 1. Strip prefix "Perlu peningkatan pada aspek: "
    //     //    Ubah "Perlu peningkatan pada aspek: X" → "Perlu peningkatan [inti X]"
    //     if (preg_match('/^Perlu peningkatan pada aspek:\s*(.+)$/i', $text, $m)) {
    //         $core = trim($m[1]);
    //         // Ekstrak kata kunci inti dari kalimat panjang
    //         $core = $this->extractCorePhrase($core);
    //         return 'Perlu peningkatan ' . lcfirst($core) . '.';
    //     }

    //     // 2. "Dosen/TA harus lebih baik dalam X" → "Dosen/TA perlu X."
    //     if (preg_match('/^Dosen\/TA harus lebih baik dalam\s*(.+)$/i', $text, $m)) {
    //         $core = rtrim(trim($m[1]), '.');
    //         return 'Dosen/TA perlu ' . lcfirst($core) . '.';
    //     }

    //     // 3. "Dosen/TA perlu meningkatkan X" → tetap, tapi potong jika terlalu panjang
    //     if (preg_match('/^(Dosen\/TA perlu [^.]{1,80})/i', $text, $m)) {
    //         return rtrim($m[1], '.') . '.';
    //     }

    //     // 4. Potong kalimat sangat panjang (> 100 karakter) pada kata terakhir sebelum batas
    //     if (mb_strlen($text) > 100) {
    //         $cut = mb_substr($text, 0, 97);
    //         $lastSpace = mb_strrpos($cut, ' ');
    //         if ($lastSpace !== false) {
    //             $cut = mb_substr($cut, 0, $lastSpace);
    //         }
    //         return rtrim($cut, '.,;') . '.';
    //     }

    //     return rtrim($text, '.') . '.';
    // }

    /**
     * Ekstrak frasa inti dari kalimat panjang (untuk dipakai setelah "Perlu peningkatan").
     */
    private function extractCorePhrase(string $sentence): string
    {
        // Petakan kalimat umum ke frasa singkat
        $patterns = [
            '/waktu untuk menyelesaikan ujian.*/i' => 'kecukupan waktu ujian',
            '/dosen.*menyiapkan materi.*terstruktur.*/i' => 'struktur dan perencanaan materi kuliah',
            '/secara keseluruhan.*puas.*pembelajaran.*mata kuliah.*/i' => 'kepuasan pembelajaran secara keseluruhan',
            '/hasil pemeriksaan kuis.*dikembalikan.*/i' => 'pengembalian hasil kuis/tugas/ujian',
            '/soal ujian sesuai dengan materi.*/i' => 'kesesuaian soal ujian dengan materi',
            '/kehadiran.*dosen.*ta.*/i' => 'kehadiran dosen/TA di kelas',
            '/platform.*pembelajaran.*/i' => 'efektivitas platform pembelajaran',
            '/interaksi.*mahasiswa.*/i' => 'interaksi antara dosen dan mahasiswa',
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $sentence)) {
                return $replacement;
            }
        }

        // Fallback: ambil 60 karakter pertama
        if (mb_strlen($sentence) > 60) {
            $cut = mb_substr($sentence, 0, 57);
            $lastSpace = mb_strrpos($cut, ' ');
            return $lastSpace !== false ? mb_substr($cut, 0, $lastSpace) : $cut;
        }

        return $sentence;
    }

    /**
     * ========================================
     * ADVANCED RAG METHODS (NEW)
     * ========================================
     */

    /**
     * Generate laporan using Advanced RAG with Vector Database
     */
    public function generateLaporanAdvanced($periode, $prodiId = null, $templateId = null, $tipeLaporan = 'UTS')
    {
        try {
            Log::info("=== Starting ADVANCED RAG Laporan Generation ===", [
                'periode' => $periode,
                'prodi_id' => $prodiId,
                'template_id' => $templateId
            ]);

            // Check if Vector DB is enabled
            if (!env('VECTOR_DB_ENABLED', false)) {
                Log::info("Vector DB not enabled, falling back to simple RAG");
                return $this->generateLaporan($periode, $prodiId, $templateId);
            }

            // Initialize services
            $embeddingService = new EmbeddingService();
            $chunkingService = new ChunkingService();
            $vectorDbService = new VectorDatabaseService($embeddingService, $chunkingService);
            $ragRetrievalService = new RAGRetrievalService($vectorDbService);

            // Step 1: Ensure kuesioner are indexed
            $this->ensureKuesioneIndexed($periode, $prodiId, $vectorDbService);

            // Step 2: Collect basic data (for statistics)
            $kuesioneList = $this->collectKuesioneData($periode, $prodiId, $tipeLaporan);

            if ($kuesioneList->isEmpty()) {
                throw new \Exception('Tidak ada kuesioner completed untuk periode ini');
            }

            // Step 3: Aggregate statistics
            $aggregatedData = $this->aggregateStatistik($kuesioneList);

            // Step 4: Build query for RAG retrieval
            $periodeContext = $this->resolvePeriodeContext($periode);
            $bulan = $periodeContext['periodeObj']->locale('id')->translatedFormat('F');
            $tahun = $periodeContext['year'];

            $query = "Analisis kuesioner mahasiswa periode {$bulan} {$tahun}. ";
            $query .= "Berikan insight mendalam tentang kepuasan mahasiswa, ";
            $query .= "poin-poin positif, area yang perlu diperbaiki, dan rekomendasi strategis.";

            // Step 5: Retrieve relevant context using RAG
            Log::info("=== ADVANCED RAG: Retrieving Context ===");
            $retrieval = $ragRetrievalService->retrieveContext($query, [
                'periode' => $periode,
                'prodi_id' => $prodiId,
            ]);

            // Step 6: Build enriched context
            $enrichedContext = $this->buildEnrichedContext(
                $aggregatedData,
                $retrieval['context_text'],
                $retrieval['metadata'],
                $periode
            );

            // Step 7: Get template
            $template = $templateId ? TemplateLaporan::find($templateId) : null;

            // Step 8: Augment prompt with template and enriched context
            $augmentedPrompt = $this->augmentPromptAdvanced(
                $template,
                $enrichedContext,
                $periode,
                $retrieval['metadata']
            );

            // Step 9: Generate with AI
            Log::info("=== ADVANCED RAG: Generating with AI ===");

            $response = $this->callAI([
                'role' => 'system',
                'content' => 'Anda adalah AI Agent ahli dalam membuat laporan analisis kuesioner akademik dengan deep insights. Gunakan context yang diberikan untuk menghasilkan analisis yang mendalam dan actionable.'
            ], $augmentedPrompt, 4000);

            if (!$response) {
                throw new \Exception('AI tidak menghasilkan response');
            }

            // Clean and parse response
            $cleanResponse = trim($response);
            $cleanResponse = preg_replace('/```(?:json)?\s*/i', '', $cleanResponse);
            $cleanResponse = preg_replace('/```\s*/i', '', $cleanResponse);

            $start = strpos($cleanResponse, '{');
            $end = strrpos($cleanResponse, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $cleanResponse = substr($cleanResponse, $start, $end - $start + 1);
            }

            $hasilLaporan = json_decode($cleanResponse, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $jsonErr = json_last_error_msg();
                Log::error('JSON Parse Error', [
                    'error' => $jsonErr,
                    'response' => substr($cleanResponse, 0, 1000)
                ]);
                throw new \Exception('AI response tidak valid: ' . $jsonErr);
            }

            // Add aggregated data and RAG metadata
            $hasilLaporan['statistik_utama'] = [
                'total_kuesioner' => $aggregatedData['total_kuesioner'],
                'total_responden' => $aggregatedData['total_responden'],
                'index_kepuasan_rata_rata' => $aggregatedData['index_kepuasan_rata_rata'],
                'persen_kepuasan_rata_rata' => $aggregatedData['persen_kepuasan_rata_rata'],
            ];

            $hasilLaporan['rag_metadata'] = [
                'method' => 'advanced_rag',
                'chunks_used' => $retrieval['metadata']['total_chunks'],
                'avg_similarity' => round($retrieval['metadata']['avg_similarity'], 3),
                'sources' => $retrieval['metadata']['num_sources'],
            ];

            Log::info("=== ADVANCED RAG Laporan Generation Completed ===", [
                'chunks_used' => $retrieval['metadata']['total_chunks'],
                'avg_similarity' => $retrieval['metadata']['avg_similarity']
            ]);

            return [
                'hasil_laporan' => $hasilLaporan,
                'aggregated_data' => $aggregatedData,
            ];

        } catch (\Exception $e) {
            Log::error('ADVANCED RAG Laporan Generation Failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Ensure kuesioner are indexed in vector database
     */
    private function ensureKuesioneIndexed($periode, $prodiId, $vectorDbService)
    {
        Log::info("=== Ensuring Kuesioner are Indexed ===");

        $kuesioneList = $this->collectKuesioneData($periode, $prodiId);

        foreach ($kuesioneList as $kuesioner) {
            // Check if already indexed
            $existingChunks = \App\Models\DocumentChunk::where('kuesioner_upload_id', $kuesioner->id)->count();

            if ($existingChunks == 0) {
                Log::info("Indexing kuesioner", ['id' => $kuesioner->id]);
                $vectorDbService->indexKuesioner($kuesioner);
            } else {
                Log::info("Kuesioner already indexed", [
                    'id' => $kuesioner->id,
                    'chunks' => $existingChunks
                ]);
            }
        }
    }

    /**
     * Build enriched context with RAG results
     */
    private function buildEnrichedContext($aggregatedData, $ragContext, $ragMetadata, $periode)
    {
        $periodeContext = $this->resolvePeriodeContext($periode);
        $bulan = $periodeContext['periodeObj']->locale('id')->translatedFormat('F');
        $tahun = $periodeContext['year'];

        $context = "=== LAPORAN KUESIONER PERIODE {$bulan} {$tahun} ===\n\n";

        $context .= "=== STATISTIK AGREGAT ===\n";
        $context .= "Total Kuesioner: {$aggregatedData['total_kuesioner']}\n";
        $context .= "Total Responden: {$aggregatedData['total_responden']}\n";
        $context .= "Index Kepuasan Rata-rata: {$aggregatedData['index_kepuasan_rata_rata']} (skala 0-4)\n";
        $context .= "Persen Kepuasan: {$aggregatedData['persen_kepuasan_rata_rata']}%\n\n";

        $context .= "=== CONTEXT DARI RAG (Semantic Search) ===\n";
        $context .= "Chunks Retrieved: {$ragMetadata['total_chunks']}\n";
        $context .= "Average Relevance: " . round($ragMetadata['avg_similarity'] * 100, 1) . "%\n";
        $context .= "Sources: {$ragMetadata['num_sources']} kuesioner\n\n";
        $context .= $ragContext . "\n\n";

        // Group kuesioner by tingkat
        $kuesioneByTingkat = [];
        foreach ($aggregatedData['kuesioner_data'] as $kuesioner) {
            $tingkat = $kuesioner['tingkat'] ?? 'Unknown';
            if (!isset($kuesioneByTingkat[$tingkat])) {
                $kuesioneByTingkat[$tingkat] = [];
            }
            $kuesioneByTingkat[$tingkat][] = $kuesioner;
        }

        // Sort tingkat
        ksort($kuesioneByTingkat);

        $context .= "=== DETAIL PER TINGKAT ===\n\n";
        foreach ($kuesioneByTingkat as $tingkat => $kuesioneList) {
            $context .= "TINGKAT {$tingkat}:\n";
            $context .= "Total Matakuliah: " . count($kuesioneList) . "\n\n";

            // Calculate rata-rata index kepuasan untuk tingkat ini
            $totalIndex = 0;
            foreach ($kuesioneList as $k) {
                $totalIndex += $k['index_kepuasan'];
            }
            $avgIndex = count($kuesioneList) > 0 ? $totalIndex / count($kuesioneList) : 0;
            $context .= "Rata-rata Index Kepuasan Tingkat {$tingkat}: " . number_format(round($avgIndex, 4), 4, '.', '') . "\n\n";

            $context .= "Tabel Matakuliah Tingkat {$tingkat}:\n";
            $context .= "| Kode Matakuliah | Nama Matakuliah | Dosen Pengampu | Indeks Kepuasan |\n";
            $context .= "|-----------------|-----------------|----------------|------------------|\n";

            foreach ($kuesioneList as $kuesioner) {
                $kodeMk = $kuesioner['kode_matakuliah'] ?? '-';
                $namaMk = $kuesioner['nama_matakuliah'] ?? '-';
                $dosenPengampu = $kuesioner['dosen_pengampu'] ?? '-';
                $indexKepuasan = $kuesioner['index_kepuasan'] ?? 0;

                $context .= "| {$kodeMk} | {$namaMk} | {$dosenPengampu} | " . number_format($indexKepuasan, 4, '.', '') . " |\n";
            }

            $context .= "\nMasukan/Saran untuk Tingkat {$tingkat}:\n";
            $context .= "| Kode Matakuliah | Nama Matakuliah | Dosen Pengampu | Masukan/Saran |\n";
            $context .= "|-----------------|-----------------|----------------|---------------|\n";
            foreach ($kuesioneList as $idx => $kuesioner) {
                $kodeMk = $kuesioner['kode_matakuliah'] ?? '-';
                $namaMk = $kuesioner['nama_matakuliah'] ?? '-';
                $dosenPengampu = $kuesioner['dosen_pengampu'] ?? '-';
                // Gunakan rekomendasi dari AI Analysis Report sebagai Masukan/Saran
                $rekomendasiArr = $kuesioner['rekomendasi'] ?? [];
                if (!empty($rekomendasiArr)) {
                    $rekomendasiText = implode('; ', $rekomendasiArr);
                } else {
                    $rekomendasiText = $kuesioner['ringkasan'] ?? '-';
                }
                $context .= "| {$kodeMk} | {$namaMk} | {$dosenPengampu} | {$rekomendasiText} |\n";
            }
            $context .= "\n";
        }

        $context .= "=== TOP 5 KUESIONER TERTINGGI ===\n";
        foreach ($aggregatedData['top_5_tertinggi'] as $idx => $k) {
            $context .= ($idx + 1) . ". {$k['nama']} - Index: {$k['index_kepuasan']}\n";
        }

        $context .= "\n=== TOP 5 KUESIONER TERENDAH ===\n";
        foreach ($aggregatedData['top_5_terendah'] as $idx => $k) {
            $context .= ($idx + 1) . ". {$k['nama']} - Index: {$k['index_kepuasan']}\n";
        }

        $context .= "\n=== DATA UNTUK KESIMPULAN ===\n";
        $context .= "Indeks Kepuasan Keseluruhan Prodi: {$aggregatedData['index_kepuasan_rata_rata']}\n";
        $context .= "Indikator Minimum Kepuasan: 2.8\n";
        $context .= "Status: " . ($aggregatedData['index_kepuasan_rata_rata'] >= 2.8 ? "Melampaui target" : "Belum mencapai target") . "\n";

        return $context;
    }

    /**
     * Augment prompt for Advanced RAG
     */
    private function augmentPromptAdvanced($template, $enrichedContext, $periode, $ragMetadata)
    {
        $periodeContext = $this->resolvePeriodeContext($periode);
        $bulan = $periodeContext['periodeObj']->locale('id')->translatedFormat('F');
        $tahun = $periodeContext['year'];

        $prompt = "Anda adalah AI Agent expert dalam analisis kuesioner akademik.\n\n";

        $prompt .= "METODE: Advanced RAG (Retrieval-Augmented Generation)\n";
        $prompt .= "- Menggunakan semantic search untuk menemukan context paling relevan\n";
        $prompt .= "- {$ragMetadata['total_chunks']} chunks dianalisis\n";
        $prompt .= "- Average relevance: " . round($ragMetadata['avg_similarity'] * 100, 1) . "%\n\n";

        if ($template && $template->contoh_konten) {
            $prompt .= "TEMPLATE STRUKTUR:\n";
            $prompt .= $template->contoh_konten . "\n\n";
        }

        $prompt .= "DATA & CONTEXT:\n";
        $prompt .= $enrichedContext . "\n\n";

        $prompt .= "TUGAS ANDA:\n";
        $prompt .= "Buat laporan bulanan yang MENDALAM dan ACTIONABLE untuk {$bulan} {$tahun}.\n\n";

        $prompt .= "FOKUS PADA:\n";
        $prompt .= "1. Deep insights dari semantic analysis\n";
        $prompt .= "2. Pattern recognition across multiple kuesioner\n";
        $prompt .= "3. Root cause analysis untuk area perbaikan\n";
        $prompt .= "4. Specific, measurable recommendations\n";
        $prompt .= "5. Prioritization berdasarkan impact\n\n";

        $prompt .= "OUTPUT FORMAT (JSON):\n";
        $prompt .= "{\n";
        $prompt .= '  "ringkasan_eksekutif": {' . "\n";
        $prompt .= '    "overview": "Deep analysis overview",' . "\n";
        $prompt .= '    "highlight_positif": ["..."],' . "\n";
        $prompt .= '    "highlight_negatif": ["..."],' . "\n";
        $prompt .= '    "trend": "..."' . "\n";
        $prompt .= '  },' . "\n";
        $prompt .= '  "insight_utama": ["Deep insight 1", "Deep insight 2", ...],' . "\n";
        $prompt .= '  "rekomendasi": [' . "\n";
        $prompt .= '    {' . "\n";
        $prompt .= '      "rekomendasi": "Specific action",' . "\n";
        $prompt .= '      "prioritas": "High/Medium/Low",' . "\n";
        $prompt .= '      "timeline": "Timeframe",' . "\n";
        $prompt .= '      "expected_impact": "Expected outcome"' . "\n";
        $prompt .= '    }' . "\n";
        $prompt .= '  ]' . "\n";
        $prompt .= "}\n\n";

        $prompt .= "PENTING:\n";
        $prompt .= "- Gunakan semantic context untuk deep insights\n";
        $prompt .= "- Identifikasi patterns yang tidak obvious\n";
        $prompt .= "- Berikan rekomendasi yang specific dan measurable\n";
        $prompt .= "- Response HARUS JSON valid\n";

        return $prompt;
    }

    /**
     * Process dan index template ke vector database
     */
    public function processTemplateToVectorDB($templateId)
    {
        Log::info("=== Processing Template to Vector DB ===", ['template_id' => $templateId]);

        $template = TemplateLaporan::find($templateId);
        if (!$template || !$template->file_path) {
            throw new \Exception("Template not found or no file attached");
        }

        // 1. Extract text dari file
        $extraction = $this->textExtractionService->extractFromFile($template->file_path);

        if (!$extraction['success']) {
            throw new \Exception("Failed to extract text from template: " . ($extraction['metadata']['error'] ?? 'Unknown error'));
        }

        $text = $this->textExtractionService->cleanText($extraction['text']);

        // 2. Extract structure
        $structure = $this->structureService->extractStructure($text);

        // 3. Advanced chunking dengan structure
        $chunks = $this->advancedChunkingService->chunkWithMetadata($text, [
            'template_id' => $templateId,
            'template_name' => $template->nama_template,
            'prodi_id' => $template->prodi_id,
            'document_type' => 'template',
            'structure' => json_encode($structure)
        ], 'template');

        // 4. Store ke vector database
        $embeddingService = app(EmbeddingService::class);

        $indexed = 0;
        foreach ($chunks as $chunk) {
            try {
                // Generate embedding
                $embedding = $embeddingService->generateEmbedding($chunk['text']);

                if (!$embedding) {
                    Log::warning("Failed to generate embedding for chunk", [
                        'chunk_index' => $chunk['index']
                    ]);
                    continue;
                }

                // Store to database directly
                DocumentChunk::create([
                    'template_id' => $templateId,
                    'chunk_text' => $chunk['text'],
                    'chunk_index' => $chunk['index'],
                    'embedding' => $embedding,
                    'metadata' => array_merge($chunk['metadata'], [
                        'section' => $chunk['section'] ?? null,
                        'chunk_type' => $chunk['type']
                    ])
                ]);

                $indexed++;
            } catch (\Exception $e) {
                Log::error("Failed to index chunk", [
                    'chunk_index' => $chunk['index'],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // 5. Update template record
        try {
            $updated = $template->update([
                'is_indexed' => true,
                'indexed_at' => now(),
                'total_chunks' => $indexed,
                'structure_metadata' => json_encode($structure)
            ]);

            if (!$updated) {
                Log::error("Failed to update template record", [
                    'template_id' => $templateId
                ]);
            }

            // Force refresh from database
            $template->refresh();

            Log::info("Template record updated", [
                'template_id' => $templateId,
                'is_indexed' => $template->is_indexed,
                'total_chunks' => $template->total_chunks
            ]);
        } catch (\Exception $e) {
            Log::error("Exception updating template", [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        Log::info("Template indexed successfully", [
            'template_id' => $templateId,
            'total_chunks' => $indexed,
            'sections' => count($structure['sections'])
        ]);

        return [
            'success' => true,
            'template_id' => $templateId,
            'chunks_indexed' => $indexed,
            'structure' => $structure
        ];
    }

    /**
     * Generate laporan dengan template structure-aware
     * Returns the generated content and metadata, not a new LaporanBulanan record
     */
    public function generateLaporanWithTemplateStructure($periode, $prodiId = null, $templateId = null, $tipeLaporan = 'UTS')
    {
        Log::info("=== Generate Laporan with Template Structure ===", [
            'periode' => $periode,
            'prodi_id' => $prodiId,
            'template_id' => $templateId
        ]);

        // 1. Get template
        if ($templateId) {
            $template = TemplateLaporan::find($templateId);
        } else {
            $template = $this->getActiveTemplate('laporan_bulanan');
        }

        if (!$template) {
            throw new \Exception("No template found");
        }

        // 2. Ensure template is indexed
        if (!$template->is_indexed) {
            Log::info("Template not indexed, indexing now...");
            $this->processTemplateToVectorDB($template->id);
            $template->refresh();
        }

        // 3. Collect kuesioner data
        $kuesioneList = $this->collectKuesioneData($periode, $prodiId, $tipeLaporan);

        if ($kuesioneList->isEmpty()) {
            throw new \Exception('Tidak ada kuesioner completed untuk periode ini');
        }

        $aggregatedData = $this->aggregateStatistik($kuesioneList);

        // 4. Retrieve template structure dari vector DB
        $embeddingService = app(EmbeddingService::class);
        $chunkingService = app(ChunkingService::class);
        $vectorDbService = new VectorDatabaseService($embeddingService, $chunkingService);
        $ragService = new RAGRetrievalService($vectorDbService);

        // Query untuk mendapatkan format instructions
        $formatQuery = "struktur format laporan template sections headings";
        $formatContext = $ragService->retrieveContext($formatQuery, [
            'template_id' => $template->id,
            'chunk_type' => 'format_guide'
        ]);

        // Query untuk mendapatkan contoh konten per section
        $sectionExamples = [];
        if ($template->structure_metadata) {
            $structure = json_decode($template->structure_metadata, true);
            foreach ($structure['sections'] ?? [] as $section) {
                $sectionQuery = "contoh format section " . $section['title'];
                $sectionContext = $ragService->retrieveContext($sectionQuery, [
                    'template_id' => $template->id,
                    'section_title' => $section['title']
                ]);
                $sectionExamples[$section['title']] = $sectionContext['context_text'];
            }
        }

        // 5. Build enriched context
        $prodi = $prodiId ? \App\Models\Prodi::find($prodiId) : null;
        $context = $this->buildContext($aggregatedData, $periode, $prodi, $tipeLaporan);

        // Get current user (GKM)
        $user = auth()->user();
        $gkmName = $user ? $user->name : 'GKM Prodi';
        $gkmProdi = $prodi ? $prodi->nama_prodi : '';

        // 6. Build structure-aware prompt
        $prompt = $this->buildStructureAwarePrompt(
            $template,
            $context,
            $formatContext['context_text'],
            $sectionExamples,
            $periode,
            $gkmName,
            $gkmProdi
        );

        // 7. Generate dengan AI
        $systemMessage = [
            'role' => 'system',
            'content' => 'You are an expert academic report writer specialized in creating detailed, structured reports in Bahasa Indonesia that EXACTLY match provided templates.

Your expertise includes:
1. Replicating complex document structures with multiple levels (sections, subsections, sub-subsections)
2. Creating detailed tables with exact column structures for matakuliah data
3. Maintaining formal academic writing style in Bahasa Indonesia with detailed paragraphs
4. Following hierarchical numbering systems (I., II., III. for sections, a., b., c. for subsections)
5. Preserving all formatting details from templates
6. Using the EXACT phrasing and writing style from template examples

CRITICAL RULES FOR PENDAHULUAN SECTION:
- Section c (Ruang Lingkup) MUST include:
  1. Explanation about 17 questions with 4 assessment criteria
  2. Mention of Likert scale usage
  3. Table 1 with Skala Likert in this exact format:
     | Skala | Keterangan | Nilai |
     |-------|------------|-------|
     | 4 | Sangat Baik | 3.26 - 4.00 |
     | 3 | Baik | 2.51 - 3.25 |
     | 2 | Cukup | 1.76 - 2.50 |
     | 1 | Kurang | 1.00 - 1.75 |

CRITICAL STRUCTURE RULES:
- Section headers (I., II., III.) must be IMMEDIATELY followed by their first subsection (a., b., c.)
- DO NOT write any introductory paragraph between section header and first subsection
- Example CORRECT: "I. PENDAHULUAN\n\na. Tujuan\n[content]"
- Example WRONG: "I. PENDAHULUAN\n[intro paragraph]\n\na. Tujuan" ← DO NOT DO THIS!

CRITICAL RULES FOR SECTION II (HASIL KUESIONER):
- You MUST group results by Tingkat (I, II, III, IV, etc.) based on semester level
- For each Tingkat, you MUST create a detailed section with:
  1. Subsection header: "I. Tingkat I (Semester 2)" or "II. Tingkat II (Semester 4)" etc.
  2. Table showing: | Kode Matakuliah | Nama Matakuliah | Dosen Pengampu | Indeks Kepuasan |
  3. Paragraph explaining the average satisfaction index for that Tingkat
- After all Tingkat sections, add "Masukan dan Saran" subsection with table:
  | Kode Matakuliah | Nama Matakuliah | Masukan/Saran |
- Tables MUST be properly formatted with markdown table syntax
- Use Roman numerals (I., II., III.) for Tingkat subsections under section II

CRITICAL RULES FOR MASUKAN/SARAN COLUMN:
- DO NOT write statistical summaries like "Rata-rata skor kepuasan mahasiswa adalah X" or "Pertanyaan QX memiliki nilai tertinggi"
- DO NOT write generic statements like "mahasiswa secara umum sangat puas"
- MUST contain concrete, specific, and actionable improvement suggestions for the future
- Examples of GOOD suggestions: "Tingkatkan interaksi dalam diskusi kelas", "Perbanyak praktikum dengan tools modern", "Tambahkan studi kasus real-world"
- Tailor suggestions to the course context and satisfaction index received

CRITICAL RULES FOR SECTION III (KESIMPULAN DAN SARAN):
- You MUST include TWO main points in bullet format:
  1. Note about courses (matakuliah) calculated by course code that should be separated by prodi even if they have the same code (e.g., Agama, Kewarganegaraan, Pancasila) to avoid bias between TK and TI prodi
  2. Overall satisfaction index conclusion stating the exact value and comparing it to the minimum indicator of 2.8
- After the two bullet points, you MUST add:
  1. Three empty lines
  2. Date line (right-aligned): "Laguboti, [tanggal] [bulan] [tahun]" (e.g., "Laguboti, 25 Juni 2025")
- DO NOT add any signature, name, or image after the date
- DO NOT add "(GKM PRODI ...)" or any official name
- Example format:
  "- Adanya matakuliah yang dihitung berdasarkan kode matakuliah, sebaiknya tetap dipisahkan sesuai prodi meskipun memiliki kode matakuliah yang sama. Kasus seperti ini dapat dilihat pada matakuliah Agama, Kewarganegaraan, Pancasila. Matakuliah tersebut mendapatkan persebaran yang lebih luas sehingga ada kemungkinan indeks kepuasannya yang didapat menjadi bias antar prodi TK atau TI.
  - Indeks Kepuasan semua matakuliah di prodi adalah [nilai]. Nilai kepuasan ini telah melampaui indikator kepuasan pembelajaran mahasiswa minimum yaitu 2.8.



                                                                                                    Laguboti, 25 Juni 2025"

CRITICAL RULES FOR WRITING STYLE:
- Example: Instead of "Kuesioner dilaksanakan pada Maret 2026", write "Penyebaran kuesioner evaluasi mata kuliah dilaksanakan pada bulan Maret 2026. Penyebaran kuesioner dibagi menjadi 2 tahap yaitu pembagian pertama dilakukan pada minggu ke-14 dan pembagian kedua dilakukan pada minggu ke-15."

OTHER RULES:
- You MUST include ALL subsections shown in template (e.g., if template has a., b., c. under a section, you MUST include all three)
- You MUST replicate ALL tables from template with exact structure
- You MUST maintain the exact heading hierarchy
- You MUST output ONLY the final report without any metadata or context markers

Your output will be converted to Word document, so it must be clean, professional, detailed, and ready to use.'
        ];
        $aiResponse = $this->callAI($systemMessage, $prompt, 12000);

        // 8. Check if AI generated response
        if (!$aiResponse) {
            throw new \Exception('AI tidak menghasilkan response');
        }

        // 9. Parse response
        $laporanData = $this->parseAIResponse($aiResponse);

        Log::info("Laporan content generated successfully");

        // Return data for the Job to save
        return [
            'hasil_laporan' => $laporanData,
            'aggregated_data' => $aggregatedData,
            'metadata' => [
                'total_kuesioner' => count($kuesioneList),
                'avg_index' => $aggregatedData['index_kepuasan_rata_rata'] ?? 0,
                'template_structure_used' => true,
                'format_context_chunks' => count($formatContext['results'] ?? []),
                'section_examples_used' => count($sectionExamples)
            ]
        ];
    }

    /**
     * Build prompt yang aware dengan struktur template
     */
    private function buildStructureAwarePrompt($template, $dataContext, $formatContext, $sectionExamples, $periode, $gkmName = null, $gkmProdi = null)
    {
        $periodeContext = $this->resolvePeriodeContext($periode);
        $bulan = $periodeContext['periodeObj']->locale('id')->translatedFormat('F');
        $tahun = $periodeContext['year'];

        // Format tanggal untuk kesimpulan (format: 25 Juni 2025)
        $tanggalLaporan = Carbon::now()->locale('id')->translatedFormat('d F Y');

        $prompt = "Generate laporan kuesioner akademik dalam Bahasa Indonesia formal.\n\n";

        $prompt .= "STRUKTUR WAJIB:\n";
        $prompt .= "I. PENDAHULUAN\n";
        $prompt .= "  a. Tujuan\n";
        $prompt .= "  b. Waktu pelaksanaan\n";
        $prompt .= "  c. Ruang Lingkup (akhiri dengan Tabel 1 skala likert)\n";
        $prompt .= "II. HASIL KUESIONER (group by Tingkat dengan tabel matakuliah)\n";
        $prompt .= "III. KESIMPULAN DAN SARAN\n\n";

        $prompt .= "TABEL 1 (WAJIB di section I.c):\n";
        $prompt .= "Tabel 1. Skala Likert Kuesioner\n";
        $prompt .= "| Pernyataan | Kode | Skala |\n";
        $prompt .= "| Tidak setuju (TS) | TS | 1 |\n";
        $prompt .= "| Cukup Setuju (CS) | CS | 2 |\n";
        $prompt .= "| Setuju (S) | S | 3 |\n";
        $prompt .= "| Sangat Setuju (SS) | SS | 4 |\n\n";

        $prompt .= "FORMAT SECTION II (HASIL KUESIONER):\n";
        $prompt .= "Untuk setiap tingkat, buat subsection dengan format:\n\n";
        $prompt .= "I. Tingkat [X]\n\n";
        $prompt .= "Pada tingkat [X] terdapat [N] matakuliah dengan detail sebagai berikut:\n\n";
        $prompt .= "Tabel [N]. Matakuliah Mahasiswa Tingkat [X]\n";
        $prompt .= "| Kode Matakuliah | Nama Matakuliah | Dosen Pengampu | Indeks Kepuasan |\n";
        $prompt .= "|-----------------|-----------------|----------------|------------------|\n";
        $prompt .= "| [kode] | [nama] | [dosen] | [indeks] |\n\n";
        $prompt .= "Dari Tabel [N]. Ada beberapa matakuliah yang memiliki lebih dari satu dosen pengampu sehingga perhitungan indeks kepuasan matakuliah berdasarkan dosen pengampu akan dihitung rata-rata indeks nya. Pada semua perkuliahan di semester [X], Tingkat [X] mendapatkan Rata Indeks Kepuasan: [nilai]\n\n";
        $prompt .= "Adapun masukan/saran untuk perbaikan mata kuliah ini dapat dilihat pada Tabel [N+1]:\n";
        $prompt .= "Tabel [N+1]. Masukan/saran setiap Matakuliah\n";
        $prompt .= "| Kode Matakuliah | Nama Matakuliah | Dosen Pengampu | Masukan/Saran |\n";
        $prompt .= "|-----------------|-----------------|----------------|---------------|\n";
        $prompt .= "| [kode] | [nama] | [dosen] | [masukan] |\n\n";
        $prompt .= "PENTING untuk Masukan/Saran:\n";
        $prompt .= "- WAJIB: Ambil isi kolom Masukan/Saran dari tabel 'Masukan/Saran untuk Tingkat X' yang ada di DATA di bawah\n";
        $prompt .= "- Data tersebut sudah berisi Rekomendasi Tindakan dari Laporan Analisis AI per matakuliah\n";
        $prompt .= "- JANGAN karang atau buat saran baru - gunakan PERSIS data rekomendasi dari konteks\n";
        $prompt .= "- Sesuaikan Kode, Nama Matakuliah, dan Dosen Pengampu dari tabel matakuliah sesuai tingkat\n";
        $prompt .= "- JANGAN gunakan ringkasan statistik seperti 'Rata-rata skor kepuasan mahasiswa adalah X'\n";
        $prompt .= "- JANGAN gunakan pernyataan umum seperti 'mahasiswa secara umum sangat puas'\n\n";

        $prompt .= "FORMAT SECTION III (KESIMPULAN DAN SARAN):\n";
        $prompt .= "Buat kesimpulan dengan poin-poin berikut:\n\n";
        $prompt .= "- Adanya matakuliah yang dihitung berdasarkan kode matakuliah, sebaiknya tetap dipisahkan sesuai prodi meskipun memiliki kode matakuliah yang sama. Kasus seperti ini dapat dilihat pada matakuliah Agama, Kewarganegaraan, Pancasila. Matakuliah tersebut mendapatkan persebaran yang lebih luas sehingga ada kemungkinan indeks kepuasannya yang didapat menjadi bias antar prodi TK atau TI.\n\n";
        $prompt .= "- Indeks Kepuasan semua matakuliah di prodi adalah [nilai_indeks_kepuasan]. Nilai kepuasan ini telah melampaui indikator kepuasan pembelajaran mahasiswa minimum yaitu 2.8.\n\n";
        $prompt .= "PENTING: Setelah kesimpulan, tambahkan:\n";
        $prompt .= "- Baris kosong\n";
        $prompt .= "- Baris kosong\n";
        $prompt .= "- Baris kosong\n";
        $prompt .= "- Tanggal pembuatan laporan dengan format: 'Laguboti, $tanggalLaporan' (rata kanan/right aligned)\n";
        $prompt .= "- JANGAN tambahkan tanda tangan, nama, atau gambar apapun setelah tanggal\n";
        $prompt .= "- JANGAN tambahkan '(GKM PRODI ...)' atau nama pejabat apapun\n\n";

        // Limit template context
        if (!empty($formatContext)) {
            $prompt .= "TEMPLATE:\n" . substr($formatContext, 0, 800) . "\n\n";
        }

        // Limit section examples to 1
        if (!empty($sectionExamples)) {
            $first = array_slice($sectionExamples, 0, 1, true);
            foreach ($first as $title => $example) {
                $prompt .= "CONTOH $title:\n" . substr($example, 0, 400) . "\n\n";
            }
        }

        // Limit data context - naikkan limit agar data rekomendasi per matakuliah tidak terpotong
        $prompt .= "DATA (Periode: $bulan $tahun):\n";
        $prompt .= substr($dataContext, 0, 5000) . "\n\n";

        $prompt .= "ATURAN:\n";
        $prompt .= "- Gunakan paragraf detail, bukan kalimat sederhana\n";
        $prompt .= "- Tabel 1 HARUS lengkap di section I.c SEBELUM section II\n";
        $prompt .= "- Section II HARUS dikelompokkan per Tingkat (I, II, III, dst)\n";
        $prompt .= "- Setiap tingkat HARUS memiliki tabel matakuliah dengan kolom: Kode Matakuliah, Nama Matakuliah, Dosen Pengampu, Indeks Kepuasan\n";
        $prompt .= "- Setiap tingkat HARUS memiliki tabel masukan/saran dengan kolom: Kode Matakuliah, Nama Matakuliah, Dosen Pengampu, Masukan/Saran\n";
        $prompt .= "- WAJIB: Kolom Masukan/Saran HARUS diisi langsung dari data 'Masukan/Saran untuk Tingkat X' di bagian DATA, yang berisi rekomendasi tindakan dari Laporan Analisis AI per matakuliah\n";
        $prompt .= "- DILARANG mengarang saran baru yang tidak ada di data konteks\n";
        $prompt .= "- Section III HARUS berisi 2 poin kesimpulan dalam format bullet point (-), diikuti dengan 3 baris kosong dan tanggal pembuatan (rata kanan)\n";
        $prompt .= "- JANGAN tambahkan tanda tangan, nama pejabat, atau gambar apapun\n";
        $prompt .= "- JANGAN tambah paragraf intro antara header section dan subsection\n";
        $prompt .= "- Output Markdown bersih tanpa metadata\n\n";

        $prompt .= "Generate laporan lengkap sekarang:\n";

        return $prompt;
    }

    /**
     * Parse AI response
     */
    private function parseAIResponse($response)
    {
        // Clean up any context markers that might have slipped through
        $response = preg_replace('/---\s*Context\s+\d+\s*\(Relevance:.*?\).*?---\n/s', '', $response);
        $response = preg_replace('/SECTION:.*?\n/i', '', $response);
        $response = preg_replace('/LAMPIRAN\s+\d+\s*\n/i', '', $response);

        // Try to parse as JSON first
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // If not JSON, return as markdown content
        return [
            'content_type' => 'markdown',
            'content' => trim($response),
            'generated_at' => now()->toISOString()
        ];
    }

    /**
     * Fix line breaks in text to ensure proper paragraph formatting
     *
     * @param string $text
     * @return string
     */
    private function fixLineBreaks($text)
    {
        if (empty($text)) {
            return $text;
        }

        // Split by lines
        $lines = explode("\n", $text);
        $fixedLines = [];
        $i = 0;

        while ($i < count($lines)) {
            $currentLine = trim($lines[$i]);

            // Handle empty lines (paragraph breaks)
            if (empty($currentLine)) {
                $fixedLines[] = '';
                $i++;
                continue;
            }

            // Check if this is a section header (I., II., a., b., etc)
            if (preg_match('/^[IVX]+\.|^[a-z]\./i', $currentLine)) {
                $fixedLines[] = $currentLine;
                $i++;
                continue;
            }

            // For regular text, merge with next lines until we hit an empty line or section header
            $mergedText = $currentLine;
            $i++;

            while ($i < count($lines)) {
                $nextLine = trim($lines[$i]);

                // Stop if we hit an empty line
                if (empty($nextLine)) {
                    break;
                }

                // Stop if we hit a section header
                if (preg_match('/^[IVX]+\.|^[a-z]\./i', $nextLine)) {
                    break;
                }

                // Merge the text
                $mergedText .= ' ' . $nextLine;
                $i++;
            }

            $fixedLines[] = $mergedText;
        }

        return implode("\n", $fixedLines);
    }

    /**
     * Test function to verify spacing in generated content
     *
     * @param string $content
     * @param string $label
     * @return array
     */
    private function testSpacing($content, $label = 'Content')
    {
        $lines = explode("\n", $content);
        $report = [];
        $inParagraph = false;

        foreach ($lines as $index => $line) {
            $lineNum = $index + 1;
            $trimmed = trim($line);

            if (empty($trimmed)) {
                $report[] = "Line $lineNum: [EMPTY]";
                $inParagraph = false;
            } else {
                if (preg_match('/^[IVX]+\.|^[a-z]\./i', $trimmed)) {
                    $report[] = "Line $lineNum: [SECTION] $trimmed";
                } else {
                    if (!$inParagraph) {
                        $report[] = "Line $lineNum: [START PARAGRAPH] $trimmed";
                        $inParagraph = true;
                    } else {
                        $report[] = "Line $lineNum: [CONTINUE] $trimmed";
                    }
                }
            }
        }

        Log::info("=== Spacing Test: $label ===", ['report' => $report]);
        return $report;
    }

    /**
     * Generate laporan dengan placeholder sesuai format template DOCX (dari main.py python)
     */
    public function generateLaporanWithPlaceholders($periode, $prodiId = null, $templateId = null, $tipeLaporan = 'UTS')
    {
        Log::info("=== Generate Laporan with Placeholders ===", [
            'periode' => $periode,
            'prodi_id' => $prodiId,
            'template_id' => $templateId
        ]);

        // 1. Get template
        if ($templateId) {
            $template = TemplateLaporan::find($templateId);
            Log::info("Template loaded", [
                'template_id' => $templateId,
                'found' => !is_null($template),
                'has_contoh_konten' => $template ? !empty($template->contoh_konten) : false,
                'has_file_path' => $template ? !empty($template->file_path) : false,
                'file_path' => $template ? $template->file_path : null
            ]);
        } else {
            $template = $this->getActiveTemplate('laporan_bulanan');
            Log::info("Using active template", [
                'found' => !is_null($template),
                'template_id' => $template ? $template->id : null
            ]);
        }

        if (!$template) {
            throw new \Exception("No template found");
        }

        // 2. Collect kuesioner data
        $kuesioneList = $this->collectKuesioneData($periode, $prodiId, $tipeLaporan);

        if ($kuesioneList->isEmpty()) {
            throw new \Exception('Tidak ada kuesioner completed untuk periode ini');
        }

        $aggregatedData = $this->aggregateStatistik($kuesioneList);

        $prodi = $prodiId ? \App\Models\Prodi::find($prodiId) : null;
        $context = $this->buildContext($aggregatedData, $periode, $prodi, $tipeLaporan);

        // Get current user (GKM)
        $user = auth()->user();
        $gkmName = $user ? $user->name : 'GKM Prodi';
        $gkmProdi = $prodi ? $prodi->nama_prodi : '';

        // Build Prompt based on Python logic
        $periodeContext = $this->resolvePeriodeContext($periode);
        $periodeObj = $periodeContext['periodeObj'];
        $bulan = $periodeObj->locale('id')->translatedFormat('F');
        $tahun = $periodeContext['year'];
        $tanggal = Carbon::now()->locale('id')->translatedFormat('d F Y');

        // Extract real semester and tahun akademik from kuesioner periode string
        $samplePeriode = $kuesioneList->first()->periode ?? ''; // e.g. "2025/2026 Ganjil"
        $semester = 'GENAP';
        $tahunAkademik = $tahun . '/' . ($tahun + 1);

        if (preg_match('/(\d{4}\/\d{4})\s+(Ganjil|Genap)/i', $samplePeriode, $matches)) {
            $tahunAkademik = $matches[1];
            $semester = strtoupper($matches[2]);
        }

        $prompt = "Kamu adalah penulis laporan Gugus Kendali Mutu (GKM) profesional.\n";
        $prompt .= "Tulis dengan gaya formal Indonesia, berdasarkan data hasil kuesioner mahasiswa.\n\n";

        $prompt .= "KONTEKS DOKUMEN:\n" . substr($context, 0, 10000) . "\n\n";

        // Include template content - try multiple sources
        $templateContent = null;
        
        if ($template) {
            // First, try contoh_konten
            if (!empty($template->contoh_konten)) {
                $templateContent = $template->contoh_konten;
                Log::info("Using template contoh_konten for placeholders");
            } 
            // Second, try to extract from template file
            elseif (!empty($template->file_path)) {
                try {
                    $templateContent = $this->extractTemplateContent($template);
                    if ($templateContent) {
                        Log::info("Successfully extracted template content from file for placeholders", [
                            'file_path' => $template->file_path,
                            'content_length' => strlen($templateContent)
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to extract template content from file for placeholders", [
                        'template_id' => $template->id,
                        'file_path' => $template->file_path,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        if ($templateContent) {
            $prompt .= "TEMPLATE REFERENSI (Gunakan struktur dan label placeholder yang ada di template ini saat mengisi dokumen):\n";
            $prompt .= substr($templateContent, 0, 8000) . "\n\n";
        } else {
            Log::warning("No template content available for placeholders - using default structure");
        }

        $prompt .= "TUGAS:\n";
        $prompt .= "Hasilkan konten laporan dalam format JSON dengan kunci-kunci berikut SESUAI TEMPLATE:\n\n";

        $prompt .= "1. PENDAHULUAN_TUJUAN: Tuliskan kalimat ini persis: \"Survei ini bertujuan untuk mengevaluasi mata kuliah pada semester {$semester} {$tahunAkademik} di lingkungan program studi {$gkmProdi} Fakultas Vokasi Institut Teknologi Del.\"\n\n";

        $prompt .= "2. PENDAHULUAN_WAKTU: Tuliskan paragraf ini persis: \"Penyebaran kuesioner evaluasi mata kuliah dilaksanakan pada bulan Maret 2026. Penyebaran kuesioner dibagi menjadi 2 tahap yaitu pembagian pertama dilakukan pada minggu ke-14 dan pembagian kedua dilakukan pada minggu ke-15. Kuesioner evaluasi mata kuliah ini dilaksanakan pada semua mata kuliah di Semester {$semester} {$tahunAkademik} dengan menyebarkan kuesioner online melalui website cis.del.ac.id.\"\n\n";

        $prompt .= "3. PENDAHULUAN_RUANG_LINGKUP: Tuliskan PERSIS seperti ini:\n\n";
        $prompt .= "\"Yang menjadi responden pada survei ini adalah mahasiswa yang mengambil mata kuliah prodi, mata kuliah fakultas dan mata kuliah institut. Adapun pihak terkait yang kualitas layanannya dinilai pada survei ini yaitu dosen, teaching assistant yang berhubungan dengan mata kuliah.\n\n";
        $prompt .= "Kuesioner yang dibagikan terdiri dari 17 pertanyaan dengan kuesioner yang dibagikan memiliki 4 kriteria penilaian (skala likert) yaitu Sangat Setuju, Setuju, Cukup Setuju, dan Tidak Setuju. Agar mempermudah perhitungan kuesioner, kriteria Sangat Setuju Setuju, Cukup Setuju, dan Tidak Setuju akan dihitung dan dilaporkan dalam bentuk indeks skala 4 dengan bobot yang mengikuti Tabel 1.\n\n";
        $prompt .= "Tabel 1. Matakuliah Mahasiswa Tingkat\n\n";
        $prompt .= "| Pernyataan | Kode | Skala |\n";
        $prompt .= "|------------|------|-------|\n";
        $prompt .= "| Tidak setuju (TS) | TS | 1 |\n";
        $prompt .= "| Cukup Setuju (CS) | CS | 2 |\n";
        $prompt .= "| Setuju (S) | S | 3 |\n";
        $prompt .= "| Sangat Setuju (SS) | SS | 4 |\"\n\n";

        $prompt .= "4. HASIL_KUESIONER_TINGKAT_I: \n";
        $prompt .= "WAJIB: Ambil data TINGKAT 1 dari KONTEKS di atas!\n";
        $prompt .= "Format PERSIS seperti ini (ganti [JUMLAH], [DATA], [RATA-RATA] dengan nilai dari TINGKAT 1 di konteks):\n\n";
        $prompt .= "\"Pada tingkat I terdapat [JUMLAH_REAL] matakuliah dengan detail sebagai berikut:\\n\\n";
        $prompt .= "Tabel 2. Matakuliah Mahasiswa Tingkat I\\n\\n";
        $prompt .= "| Kode Matakuliah | Nama Matakuliah | Dosen Pengampu | Indeks Kepuasan |\\n";
        $prompt .= "|-----------------|-----------------|----------------|------------------|\\n";
        $prompt .= "[ISI DENGAN DATA DARI TINGKAT 1 DI KONTEKS]\\n\\n";
        $prompt .= "Dari Tabel 2. Ada beberapa matakuliah yang memiliki lebih dari satu dosen pengampu sehingga perhitungan indeks kepuasan matakuliah berdasarkan dosen pengampu akan dihitung rata-rata indeks nya. Pada semua perkuliahan di semester 2, Tingkat I mendapatkan Rata Indeks Kepuasan: [RATA-RATA_REAL]\"\n\n";

        $prompt .= "5. MASUKAN_SARAN_TINGKAT_I: \n";
        $prompt .= "WAJIB: Ambil data Masukan/Saran TINGKAT 1 dari tabel 'Masukan/Saran untuk Tingkat 1' di KONTEKS!\n";
        $prompt .= "Data di konteks berisi REKOMENDASI TINDAKAN dari Laporan Analisis AI per matakuliah.\n";
        $prompt .= "PENTING: Kolom Masukan/Saran WAJIB berada DI DALAM SEL TABEL. JANGAN ada newline (Enter) literal di dalam sel teks karena akan merusak format markdown! Gunakan format memanjang.\n";
        $prompt .= "PARAFRASE: Setiap item rekomendasi harus DIPARAFRASE menjadi kalimat yang LEBIH SINGKAT dan PADAT (maks 15 kata per item), tetapi makna utamanya tetap sama. JANGAN menyalin verbatim!\n";
        $prompt .= "Format PERSIS seperti ini (perhatikan tanda pipa `|` pengapit tabel):\n\n";
        $prompt .= "\"Adapun masukan/saran untuk perbaikan mata kuliah ini dapat dilihat pada Tabel 3:\\n\\n";
        $prompt .= "Tabel 3. Masukan/saran setiap Matakuliah\\n\\n";
        $prompt .= "| Kode Matakuliah | Nama Matakuliah | Dosen Pengampu | Masukan/Saran |\\n";
        $prompt .= "|-----------------|-----------------|----------------|----------------|\\n";
        $prompt .= "| [Kode MK] | [Nama MK] | [Dosen] | 1. [parafrase 1] 2. [parafrase 2] dst. |\\n";
        $prompt .= "| [Kode MK] | [Nama MK] | [Dosen] | 1. [parafrase 1] 2. [parafrase 2] dst. |\\n";
        $prompt .= "(... lanjutkan baris tabel untuk semua matakuliah di Tingkat 1 dari konteks)\"\n\n";

        $prompt .= "6. HASIL_KUESIONER_TINGKAT_II: Format PERSIS TINGKAT_I, ganti 'Tingkat I' dengan 'Tingkat II', 'Tabel 2' dengan 'Tabel 4', 'Tingkat 1' dengan 'Tingkat 2', dan 'semester 2' dengan 'semester 4'. GUNAKAN DATA TINGKAT 2 DARI KONTEKS.\n\n";

        $prompt .= "7. MASUKAN_SARAN_TINGKAT_II: Format PERSIS MASUKAN_SARAN_TINGKAT_I (termasuk instruksi parafrase singkat), ganti 'Tabel 3' dengan 'Tabel 5'. GUNAKAN REKOMENDASI TINGKAT 2 DARI KONTEKS (tabel 'Masukan/Saran untuk Tingkat 2').\n\n";

        $prompt .= "8. HASIL_KUESIONER_TINGKAT_III: Format PERSIS TINGKAT_I, ganti 'Tingkat I' dengan 'Tingkat III', 'Tabel 2' dengan 'Tabel 6', 'Tingkat 1' dengan 'Tingkat 3', dan 'semester 2' dengan 'semester 6'. JIKA TIDAK ADA DATA TINGKAT 3 DI KONTEKS, isi dengan: \"Tidak ada data kuesioner untuk Tingkat III.\"\n\n";

        $prompt .= "9. MASUKAN_SARAN_TINGKAT_III: Format PERSIS MASUKAN_SARAN_TINGKAT_I (termasuk instruksi parafrase singkat), ganti 'Tabel 3' dengan 'Tabel 7'. GUNAKAN REKOMENDASI TINGKAT 3 DARI KONTEKS. JIKA TIDAK ADA DATA TINGKAT 3 DI KONTEKS, kosongkan.\n\n";

        $prompt .= "10. HASIL_KUESIONER_TINGKAT_IV: Format PERSIS TINGKAT_I, ganti 'Tingkat I' dengan 'Tingkat IV', 'Tabel 2' dengan 'Tabel 8', 'Tingkat 1' dengan 'Tingkat 4', dan 'semester 2' dengan 'semester 8'. JIKA TIDAK ADA DATA TINGKAT 4 DI KONTEKS, isi dengan: \"Tidak ada data kuesioner untuk Tingkat IV.\"\n\n";

        $prompt .= "11. MASUKAN_SARAN_TINGKAT_IV: Format PERSIS MASUKAN_SARAN_TINGKAT_I (termasuk instruksi parafrase singkat), ganti 'Tabel 3' dengan 'Tabel 9'. GUNAKAN REKOMENDASI TINGKAT 4 DARI KONTEKS. JIKA TIDAK ADA DATA TINGKAT 4 DI KONTEKS, kosongkan.\n\n";

        $prompt .= "12. KESIMPULAN: Tuliskan PERSIS seperti ini (ganti [NILAI] dengan nilai REAL dari konteks):\n\n";
        $prompt .= "\"- Adanya matakuliah yang dihitung berdasarkan kode matakuliah, sebaiknya tetap dipisahkan sesuai prodi meskipun memiliki kode matakuliah yang sama. Kasus seperti ini dapat dilihat pada matakuliah Agama, Kewarganegaraan, Pancasila. Matakuliah tersebut mendapatkan persebaran yang lebih luas sehingga ada kemungkinan indeks kepuasannya yang didapat menjadi bias antar prodi TK atau TI.\\n\\n";
        $prompt .= "- Indeks Kepuasan semua matakuliah di prodi adalah [NILAI_INDEKS_KEPUASAN_REAL]. Nilai kepuasan ini telah melampaui indikator kepuasan pembelajaran mahasiswa minimum yaitu 2.8.\"\n\n";

        $prompt .= "13. SARAN_REKOMENDASI: Kosongkan (tidak perlu diisi, akan diisi manual oleh user jika diperlukan).\n\n";

        $prompt .= "PENTING: Output HARUS dalam format JSON. JANGAN gunakan tanda kutip ganda (\") di dalam teks nilai. Gunakan tanda kutip tunggal (') jika perlu.\n";
        $prompt .= "CRITICAL: JANGAN GUNAKAN PLACEHOLDER TEXT seperti {{HASIL_KUESIONER_TINGKAT_I}}! Gunakan DATA REAL dari konteks!\n";
        $prompt .= "CRITICAL: Lihat KONTEKS di atas untuk data REAL setiap tingkat. Jika ada data, WAJIB diisi dengan data tersebut!\n\n";
        $prompt .= "WAJIB: Hasilkan SEMUA 13 key berikut (jangan skip satupun):\n";
        $prompt .= "1. PENDAHULUAN_TUJUAN\n";
        $prompt .= "2. PENDAHULUAN_WAKTU\n";
        $prompt .= "3. PENDAHULUAN_RUANG_LINGKUP\n";
        $prompt .= "4. HASIL_KUESIONER_TINGKAT_I\n";
        $prompt .= "5. MASUKAN_SARAN_TINGKAT_I\n";
        $prompt .= "6. HASIL_KUESIONER_TINGKAT_II\n";
        $prompt .= "7. MASUKAN_SARAN_TINGKAT_II\n";
        $prompt .= "8. HASIL_KUESIONER_TINGKAT_III\n";
        $prompt .= "9. MASUKAN_SARAN_TINGKAT_III\n";
        $prompt .= "10. HASIL_KUESIONER_TINGKAT_IV\n";
        $prompt .= "11. MASUKAN_SARAN_TINGKAT_IV\n";
        $prompt .= "12. KESIMPULAN\n";
        $prompt .= "13. SARAN_REKOMENDASI\n\n";

        $systemMessage = [
            'role' => 'system',
            'content' => 'You are a JSON API that ONLY returns valid JSON objects with REAL DATA from the provided context.

CRITICAL RULES:
1. Output MUST be a single valid JSON object starting with { and ending with }
2. NO explanatory text before or after the JSON
3. NO markdown code blocks (```json)
4. NO comments or additional text
5. Use single quotes (\') inside text values, NEVER double quotes (")
6. Escape newlines as \\n inside text values
7. All table content should be formatted as markdown tables within the JSON values
8. YOU MUST include ALL 13 keys listed in the prompt - do not skip any key
9. If data is not available for a tingkat, use "Tidak ada data kuesioner untuk Tingkat X"

CRITICAL - DO NOT USE PLACEHOLDER TEXT:
- WRONG: "{{HASIL_KUESIONER_TINGKAT_I}}" or "[ISI DENGAN DATA]"
- CORRECT: Use REAL data from the context provided in the user prompt
- Look for "TINGKAT I:", "TINGKAT II:", etc. in the context
- Extract the actual table data, kode matakuliah, nama, dosen, indeks kepuasan
- Use the REAL numbers and text from the context

EXAMPLE OUTPUT FORMAT (with REAL data):
{
  "PENDAHULUAN_TUJUAN": "Survei ini bertujuan untuk mengevaluasi mata kuliah pada semester GANJIL 2025/2026 di lingkungan program studi Sarjana Terapan Teknologi Rekayasa Perangkat Lunak Fakultas Vokasi Institut Teknologi Del.",
  "PENDAHULUAN_WAKTU": "Penyebaran kuesioner evaluasi...",
  "PENDAHULUAN_RUANG_LINGKUP": "...",
  "HASIL_KUESIONER_TINGKAT_I": "Pada tingkat I terdapat 1 matakuliah dengan detail sebagai berikut:\\n\\nTabel 2. Matakuliah Mahasiswa Tingkat I\\n\\n| Kode Matakuliah | Nama Matakuliah | Dosen Pengampu | Indeks Kepuasan |\\n|-----------------|-----------------|----------------|------------------|\\n| 4142101 | Algoritma dan Struktur Data | Ray Simarmata | 3.14619 |\\n\\nRata Indeks Kepuasan: 3.14619",
  "MASUKAN_SARAN_TINGKAT_I": "Adapun masukan/saran untuk perbaikan mata kuliah ini dapat dilihat pada Tabel 3:\\n\\nTabel 3. Masukan/saran setiap Matakuliah\\n\\n| Kode Matakuliah | Nama Matakuliah | Dosen Pengampu | Masukan/Saran |\\n|-----------------|-----------------|----------------|----------------|\\n| 4142101 | Algoritma dan Struktur Data | Ray Simarmata | 1. Perlu peningkatan pada aspek: Hasil pemeriksaan kuis/tugas/ujian dikembalikan kepada mahasiswa 2. Perlu peningkatan pada aspek: Waktu untuk menyelesaikan ujian telah mencukupi 3. Perlu peningkatan pada aspek: Soal ujian sesuai dengan materi perkuliahan 4. Dosen/TA perlu meningkatkan kualitas pemaparan materi untuk memastikan mahasiswa memahami konsep yang diajarkan. |",
  "HASIL_KUESIONER_TINGKAT_II": "Pada tingkat II terdapat 1 matakuliah...",
  "MASUKAN_SARAN_TINGKAT_II": "Adapun masukan/saran...",
  "HASIL_KUESIONER_TINGKAT_III": "Tidak ada data kuesioner untuk Tingkat III",
  "MASUKAN_SARAN_TINGKAT_III": "",
  "HASIL_KUESIONER_TINGKAT_IV": "Tidak ada data kuesioner untuk Tingkat IV",
  "MASUKAN_SARAN_TINGKAT_IV": "",
  "KESIMPULAN": "- Adanya matakuliah yang dihitung berdasarkan kode matakuliah...\\n\\n- Indeks Kepuasan semua matakuliah di prodi adalah 3.15. Nilai kepuasan ini telah melampaui indikator kepuasan pembelajaran mahasiswa minimum yaitu 2.8.",
  "SARAN_REKOMENDASI": ""
}

If you cannot generate valid JSON, return this fallback:
{"error": "Cannot generate structured content", "content_type": "markdown", "content": "[your full response here]"}'
        ];

        Log::info('Prompt size check', [
            'prompt_length' => strlen($prompt),
            'system_message_length' => strlen($systemMessage['content']),
            'total_length' => strlen($prompt) + strlen($systemMessage['content']),
            'estimated_tokens' => (strlen($prompt) + strlen($systemMessage['content'])) / 4 // rough estimate
        ]);

        // Reduce max_tokens to 8000 to avoid exceeding model limits
        // Groq llama-3.3-70b has 128k context but response is limited
        $aiResponse = $this->callAI($systemMessage, $prompt, 8000);

        if (!$aiResponse) {
            Log::error('AI failed to produce a response');
            throw new \Exception('AI tidak menghasilkan response');
        }

        Log::info('Raw AI Response received', ['response_preview' => substr($aiResponse, 0, 500)]);

        // Clean and parse response
        $cleanResponse = trim($aiResponse);

        // Check if AI returned the fallback error format
        if (strpos($cleanResponse, '"error"') !== false && strpos($cleanResponse, '"content_type": "markdown"') !== false) {
            Log::info('AI returned fallback format, extracting markdown content');
            $fallbackData = json_decode($cleanResponse, true);
            if ($fallbackData && isset($fallbackData['content'])) {
                return [
                    'hasil_laporan' => [
                        'content_type' => 'markdown',
                        'content' => $fallbackData['content'],
                        'fallback_used' => true
                    ],
                    'aggregated_data' => $aggregatedData,
                    'metadata' => [
                        'total_kuesioner' => count($kuesioneList),
                        'avg_index' => $aggregatedData['index_kepuasan_rata_rata'] ?? 0,
                        'placeholders_used' => false,
                        'fallback_used' => true
                    ]
                ];
            }
        }

        // 1. Remove markdown code blocks
        $cleanResponse = preg_replace('/```(?:json)?\s*/i', '', $cleanResponse);
        $cleanResponse = preg_replace('/```\s*$/i', '', $cleanResponse);

        // 2. Isolate JSON object
        $start = strpos($cleanResponse, '{');
        $end = strrpos($cleanResponse, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $cleanResponse = substr($cleanResponse, $start, $end - $start + 1);
        }

        // 3. EXTREME REPAIR
        // A. Remove literal control chars (0-31)
        $cleanResponse = preg_replace('/[\x00-\x1F\x7F]/', ' ', $cleanResponse);

        // B. SURGICAL QUOTE ESCAPING
        // Fix: Include space/newline in the lookbehind/lookahead to NOT escape structural quotes
        // Structural quote is preceded by { [ , : or whitespace AND followed by : , } ] or whitespace
        $cleanResponse = preg_replace('/(?<![\{\[\,\: \s])"(?![ \s]*[\:\,\}\]])/', '\"', $cleanResponse);

        // C. Fix trailing commas
        $cleanResponse = preg_replace('/,\s*([\}\]])/', '$1', $cleanResponse);

        $hasilLaporanRaw = json_decode($cleanResponse, true);

        // 4. FALLBACK: If it still fails, try removing manual escapes we just added
        if (json_last_error() !== JSON_ERROR_NONE) {
            $cleanResponse2 = str_replace('\"', '"', $cleanResponse);
            $hasilLaporanRaw = json_decode($cleanResponse2, true);
        }

        // 5. ROBUST KEY MAPPING
        if (json_last_error() === JSON_ERROR_NONE && is_array($hasilLaporanRaw)) {
            $hasilLaporan = [];
            foreach ($hasilLaporanRaw as $k => $v) {
                $upperK = strtoupper($k);
                // Handle typos and variants
                if (strpos($upperK, 'TUJUAN') !== false) {
                    $hasilLaporan['PENDAHULUAN_TUJUAN'] = $v;
                } elseif (strpos($upperK, 'WAKTU') !== false) {
                    $hasilLaporan['PENDAHULUAN_WAKTU'] = $v;
                } elseif (strpos($upperK, 'RUANG') !== false) {
                    $hasilLaporan['PENDAHULUAN_RUANG_LINGKUP'] = $v;
                }
                // HASIL KUESIONER per tingkat
                elseif (strpos($upperK, 'HASIL') !== false && strpos($upperK, 'KUESIONER') !== false) {
                    if (strpos($upperK, 'TINGKAT_IV') !== false || strpos($upperK, 'TINGKAT_4') !== false) {
                        $hasilLaporan['HASIL_KUESIONER_TINGKAT_IV'] = $v;
                    } elseif (strpos($upperK, 'TINGKAT_III') !== false || strpos($upperK, 'TINGKAT_3') !== false) {
                        $hasilLaporan['HASIL_KUESIONER_TINGKAT_III'] = $v;
                    } elseif (strpos($upperK, 'TINGKAT_II') !== false || strpos($upperK, 'TINGKAT_2') !== false) {
                        $hasilLaporan['HASIL_KUESIONER_TINGKAT_II'] = $v;
                    } elseif (strpos($upperK, 'TINGKAT_I') !== false || strpos($upperK, 'TINGKAT_1') !== false) {
                        $hasilLaporan['HASIL_KUESIONER_TINGKAT_I'] = $v;
                    }
                }
                // MASUKAN SARAN per tingkat
                elseif (strpos($upperK, 'MASUKAN') !== false || strpos($upperK, 'SARAN') !== false) {
                    if (strpos($upperK, 'REKOMENDASI') !== false) {
                        $hasilLaporan['SARAN_REKOMENDASI'] = $v;
                    } elseif (strpos($upperK, 'TINGKAT_IV') !== false || strpos($upperK, 'TINGKAT_4') !== false) {
                        $hasilLaporan['MASUKAN_SARAN_TINGKAT_IV'] = $v;
                    } elseif (strpos($upperK, 'TINGKAT_III') !== false || strpos($upperK, 'TINGKAT_3') !== false) {
                        $hasilLaporan['MASUKAN_SARAN_TINGKAT_III'] = $v;
                    } elseif (strpos($upperK, 'TINGKAT_II') !== false || strpos($upperK, 'TINGKAT_2') !== false) {
                        $hasilLaporan['MASUKAN_SARAN_TINGKAT_II'] = $v;
                    } elseif (strpos($upperK, 'TINGKAT_I') !== false || strpos($upperK, 'TINGKAT_1') !== false) {
                        $hasilLaporan['MASUKAN_SARAN_TINGKAT_I'] = $v;
                    } else {
                        $hasilLaporan['MASUKAN_SARAN'] = $v;
                    }
                } elseif (strpos($upperK, 'KESIMPULAN') !== false) {
                    $hasilLaporan['KESIMPULAN'] = $v;
                } else {
                    $hasilLaporan[$k] = $v; // Keep original if no match
                }
            }

            // VALIDATION: Ensure all required keys exist
            $requiredKeys = [
                'PENDAHULUAN_TUJUAN',
                'PENDAHULUAN_WAKTU',
                'PENDAHULUAN_RUANG_LINGKUP',
                'HASIL_KUESIONER_TINGKAT_I',
                'MASUKAN_SARAN_TINGKAT_I',
                'HASIL_KUESIONER_TINGKAT_II',
                'MASUKAN_SARAN_TINGKAT_II',
                'HASIL_KUESIONER_TINGKAT_III',
                'MASUKAN_SARAN_TINGKAT_III',
                'HASIL_KUESIONER_TINGKAT_IV',
                'MASUKAN_SARAN_TINGKAT_IV',
                'KESIMPULAN',
                'SARAN_REKOMENDASI',
            ];

            $missingKeys = [];
            foreach ($requiredKeys as $key) {
                if (!isset($hasilLaporan[$key])) {
                    $missingKeys[] = $key;
                    // Set default value
                    if (strpos($key, 'TINGKAT_III') !== false || strpos($key, 'TINGKAT_IV') !== false) {
                        if (strpos($key, 'HASIL_KUESIONER') !== false) {
                            $tingkat = strpos($key, 'III') !== false ? 'III' : 'IV';
                            $hasilLaporan[$key] = "Tidak ada data kuesioner untuk Tingkat {$tingkat}.";
                        } else {
                            $hasilLaporan[$key] = '';
                        }
                    } elseif ($key === 'SARAN_REKOMENDASI') {
                        $hasilLaporan[$key] = '';
                    } else {
                        $hasilLaporan[$key] = '[Data tidak tersedia]';
                    }
                }
            }

            if (!empty($missingKeys)) {
                Log::warning('AI response missing required keys', [
                    'missing_keys' => $missingKeys,
                    'provided_keys' => array_keys($hasilLaporanRaw)
                ]);
            }

        } else {
            $jsonErrorMsg = json_last_error_msg();
            Log::error('CRITICAL JSON PARSE FAILURE', [
                'error' => $jsonErrorMsg,
                'raw_ai_response' => substr($aiResponse, 0, 2000), // Limit log size
                'cleaned_response' => substr($cleanResponse, 0, 1000)
            ]);

            // FALLBACK: If JSON parsing completely fails, try to extract content as markdown
            Log::info('Attempting fallback to markdown parsing...');

            // Try to extract sections from the raw response
            $fallbackContent = $this->extractContentFromFailedJSON($aiResponse);

            if ($fallbackContent) {
                Log::info('Successfully extracted content using fallback method');
                $hasilLaporan = $fallbackContent;
            } else {
                throw new \Exception('AI response tetap tidak valid JSON: ' . $jsonErrorMsg . '. Respons mentah telah dicatat di log.');
            }
        }

        Log::info('Final Mapped Hasil Laporan', [
            'keys' => array_keys($hasilLaporan),
            'has_tingkat_i' => isset($hasilLaporan['HASIL_KUESIONER_TINGKAT_I']),
            'tingkat_i_length' => isset($hasilLaporan['HASIL_KUESIONER_TINGKAT_I']) ? strlen($hasilLaporan['HASIL_KUESIONER_TINGKAT_I']) : 0
        ]);

        $hasilLaporan['content_type'] = 'placeholders';

        // Determine JENIS_UJIAN based on month
        $bulanNum = $periodeObj->month;
        if ($bulanNum >= 7 && $bulanNum <= 12) {
            // Semester Ganjil: UTS=Oktober, UAS=Desember
            $jenisUjian = ($bulanNum == 10) ? 'UTS' : 'UAS';
        } else {
            // Semester Genap: UTS=Maret, UAS=Mei
            $jenisUjian = ($bulanNum == 3) ? 'UTS' : 'UAS';
        }

        return [
            'hasil_laporan' => $hasilLaporan,
            'aggregated_data' => $aggregatedData,
            'metadata' => [
                'total_kuesioner' => count($kuesioneList),
                'avg_index' => $aggregatedData['index_kepuasan_rata_rata'] ?? 0,
                'placeholders_used' => true,
                'JUDUL' => 'LAPORAN HASIL KEPUASAN MAHASISWA',
                'SUBJUDUL' => 'Mata Kuliah Semester ' . $semester . ' ' . $tahunAkademik,
                'SEMESTER' => $semester,
                'TAHUN_AKADEMIK' => $tahunAkademik,
                'JENIS_UJIAN' => $jenisUjian,
                'PRODI' => $gkmProdi ?: 'D3 Teknologi Informasi',
                'FAKULTAS' => 'Fakultas Vokasi',
                'INSTITUT' => 'Institut Teknologi Del',
                'TANGGAL' => $tanggal,
                'TEMPAT' => 'Laguboti'
            ]
        ];
    }

    /**
     * Extract content from failed JSON response as fallback
     */
    private function extractContentFromFailedJSON($response)
    {
        try {
            // Try to extract sections using pattern matching
            $sections = [];

            // Look for common section patterns
            $patterns = [
                'PENDAHULUAN_TUJUAN' => '/(?:PENDAHULUAN_TUJUAN|tujuan)["\']?\s*:\s*["\']?([^"\'}\n]+)/i',
                'PENDAHULUAN_WAKTU' => '/(?:PENDAHULUAN_WAKTU|waktu)["\']?\s*:\s*["\']?([^"\'}\n]+)/i',
                'PENDAHULUAN_RUANG_LINGKUP' => '/(?:PENDAHULUAN_RUANG_LINGKUP|ruang.lingkup)["\']?\s*:\s*["\']?([^"\'}\n]+)/i',
                'HASIL_KUESIONER_TINGKAT_I' => '/(?:HASIL_KUESIONER_TINGKAT_I|tingkat.i)["\']?\s*:\s*["\']?([^"\'}\n]+)/i',
                'HASIL_KUESIONER_TINGKAT_II' => '/(?:HASIL_KUESIONER_TINGKAT_II|tingkat.ii)["\']?\s*:\s*["\']?([^"\'}\n]+)/i',
                'HASIL_KUESIONER_TINGKAT_III' => '/(?:HASIL_KUESIONER_TINGKAT_III|tingkat.iii)["\']?\s*:\s*["\']?([^"\'}\n]+)/i',
                'MASUKAN_SARAN' => '/(?:MASUKAN_SARAN|masukan)["\']?\s*:\s*["\']?([^"\'}\n]+)/i',
                'KESIMPULAN' => '/(?:KESIMPULAN|kesimpulan)["\']?\s*:\s*["\']?([^"\'}\n]+)/i',
                'SARAN_REKOMENDASI' => '/(?:SARAN_REKOMENDASI|rekomendasi)["\']?\s*:\s*["\']?([^"\'}\n]+)/i',
            ];

            foreach ($patterns as $key => $pattern) {
                if (preg_match($pattern, $response, $matches)) {
                    $sections[$key] = trim($matches[1], '"\'');
                }
            }

            // If we found some sections, return them
            if (!empty($sections)) {
                return $sections;
            }

            // Last resort: return the whole response as markdown content
            return [
                'content_type' => 'markdown',
                'content' => trim($response),
                'fallback_used' => true
            ];

        } catch (\Exception $e) {
            Log::error('Fallback content extraction failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate saran berdasarkan indeks kepuasan
     *
     * @param float $index
     * @param string $namaMatakuliah
     * @return string
     */
    private function generateSaranBerdasarkanIndeks($index, $namaMatakuliah)
    {
        if ($index >= 3.75) {
            return "Pertahankan kualitas pengajaran dan terus berinovasi dalam penyampaian materi.";
        } elseif ($index >= 3.0) {
            return "Tingkatkan interaksi dengan mahasiswa dan berikan umpan balik yang lebih mendalam pada tugas.";
        } elseif ($index >= 2.5) {
            return "Perlu peningkatan pada metode penyampaian materi agar lebih mudah dipahami oleh mahasiswa.";
        } else {
            return "Segera review metode pengajaran dan perbarui materi kuliah agar lebih relevan dengan kebutuhan mahasiswa.";
        }
    }

    /**
     * Get active template by jenis
     *
     * @param string $jenis
     * @return TemplateLaporan|null
     */
    private function getActiveTemplate($jenis)
    {
        return TemplateLaporan::where('jenis_template', $jenis)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Call AI API dengan error handling
     *
     * @param array $systemMessage
     * @param string $userPrompt
     * @param int $maxTokens
     * @return string|null
     */
    private function callAI($systemMessage, $userPrompt, $maxTokens = 800)
    {
        try {
            Log::info('Calling AI API', [
                'provider' => env('LLM_PROVIDER', 'unknown'),
                'model' => $this->model,
                'base_url' => $this->baseUrl,
                'max_tokens' => $maxTokens,
                'prompt_length' => strlen($userPrompt),
                'has_api_key' => !empty($this->apiKey)
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($this->baseUrl . '/chat/completions', [
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
                    Log::info('AI API call successful', [
                        'response_length' => strlen($message)
                    ]);
                    return trim($message);
                } else {
                    Log::error('AI API returned empty message', [
                        'response_data' => $data
                    ]);
                }
            } else {
                $errorBody = $response->body();
                $errorData = json_decode($errorBody, true);

                Log::error('AI API Error', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'headers' => $response->headers()
                ]);

                // Provide specific error messages based on status code
                if ($response->status() === 401) {
                    throw new \Exception('API Key tidak valid atau sudah expired. Silakan periksa konfigurasi LLM_API_KEY di file .env');
                } elseif ($response->status() === 429) {
                    throw new \Exception('Rate limit exceeded. Terlalu banyak request ke AI API. Silakan coba lagi nanti.');
                } elseif ($response->status() >= 500) {
                    throw new \Exception('AI API server error (status ' . $response->status() . '). Silakan coba lagi nanti.');
                } else {
                    $errorMsg = $errorData['error']['message'] ?? 'Unknown error';
                    throw new \Exception('AI API error: ' . $errorMsg);
                }
            }

            return null;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('AI API Connection Exception', [
                'message' => $e->getMessage(),
                'base_url' => $this->baseUrl,
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Tidak dapat terhubung ke AI API. Pastikan koneksi internet aktif dan API endpoint dapat diakses: ' . $e->getMessage());
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('AI API Request Exception', [
                'message' => $e->getMessage(),
                'response' => $e->response ? $e->response->body() : 'No response',
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('AI API request gagal: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('AI API General Exception', [
                'message' => $e->getMessage(),
                'type' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Shorten recommendation text to reduce token usage before sending to AI
     *
     * @param string $text
     * @return string
     */
    private function shortenRekomendasi($text)
    {
        // If text is already short enough, return as is
        if (strlen($text) <= 100) {
            return $text;
        }

        // Remove redundant phrases
        $text = str_replace([
            'Disarankan untuk ',
            'Sebaiknya ',
            'Perlu untuk ',
            'Diharapkan ',
            'Sangat disarankan ',
        ], '', $text);

        // Truncate if still too long (keep first 150 characters)
        if (strlen($text) > 150) {
            $text = substr($text, 0, 147) . '...';
        }

        return trim($text);
    }

    /**
     * Extract template content from uploaded Word file
     * 
     * @param \App\Models\TemplateLaporan $template
     * @return string|null
     */
    private function extractTemplateContent($template)
    {
        if (!$template || !$template->file_path) {
            return null;
        }

        try {
            // Try multiple path locations
            $possiblePaths = [
                storage_path('app/public/' . $template->file_path),
                storage_path('app/' . $template->file_path),
            ];

            $templatePath = null;
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $templatePath = $path;
                    break;
                }
            }

            if (!$templatePath) {
                Log::warning('Template file not found in any location', [
                    'template_id' => $template->id,
                    'file_path' => $template->file_path,
                    'tried_paths' => $possiblePaths
                ]);
                return null;
            }

            // Read template using PhpWord
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($templatePath);
            $content = [];

            // Extract text from all sections
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $text = $this->extractTextFromWordElement($element);
                    if (!empty($text)) {
                        $content[] = $text;
                    }
                }
            }

            if (empty($content)) {
                Log::info('No content found in template file', [
                    'template_id' => $template->id
                ]);
                return null;
            }

            $contentText = implode("\n", $content);
            
            Log::info('Template content extracted successfully', [
                'template_id' => $template->id,
                'content_length' => strlen($contentText),
                'sections_found' => count($content)
            ]);

            return $contentText;

        } catch (\Exception $e) {
            Log::error('Failed to extract template content', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Extract text from PhpWord element recursively
     * 
     * @param mixed $element
     * @return string
     */
    private function extractTextFromWordElement($element)
    {
        $text = '';

        try {
            // Handle different element types
            if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                // TextRun contains multiple Text elements
                foreach ($element->getElements() as $childElement) {
                    $text .= $this->extractTextFromWordElement($childElement);
                }
            } elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                // Text element has getText() method
                $text = $element->getText();
            } elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                // Skip tables for structure extraction
                $text = '[TABLE]';
            } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextBreak) {
                // Line break
                $text = "\n";
            } elseif (method_exists($element, 'getText')) {
                $result = $element->getText();
                // Make sure result is string
                if (is_string($result)) {
                    $text = $result;
                } elseif (is_object($result)) {
                    $text = method_exists($result, '__toString') ? (string)$result : '';
                }
            } elseif (method_exists($element, 'getElements')) {
                // Container element, recurse into children
                foreach ($element->getElements() as $childElement) {
                    $text .= $this->extractTextFromWordElement($childElement) . ' ';
                }
            }
        } catch (\Exception $e) {
            Log::debug('Failed to extract text from Word element', [
                'element_type' => get_class($element),
                'error' => $e->getMessage()
            ]);
        }

        return is_string($text) ? trim($text) : '';
    }
}