<?php

namespace App\Http\Controllers\GKM;

use App\Http\Controllers\Controller;
use App\Models\KuesioneUpload;
use App\Models\Prodi;
use App\Models\Dosenn;
use App\Services\AIAgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Http;
use App\Services\ExternalApiService;

class MonitoringKuesioneController extends Controller
{
    protected $aiAgent;
    protected $apiService;


    public function __construct(
    AIAgentService $aiAgent,
    ExternalApiService $apiService
) {
    $this->aiAgent = $aiAgent;
    $this->apiService = $apiService;
}

    public function index()
    {
        $kuesioners = KuesioneUpload::with(['user', 'user.prodi'])->orderBy('created_at', 'desc')->get();

        // Hitung laporan bulanan (bulan ini)
        $laporanBulanan = KuesioneUpload::whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->count();

        // Hitung laporan tahunan (tahun ini)
        $laporanTahunan = KuesioneUpload::whereYear('created_at', date('Y'))
            ->count();

        return view('gkm.monitoring-kuesioner.index', compact('kuesioners', 'laporanBulanan', 'laporanTahunan'));
    }

    public function create(Request $request)
    {
        // Generate periode dropdown (1 tahun sebelum sampai 1 tahun sesudah)
        $currentYear = date('Y');
        $periodes = [];

        for ($year = $currentYear - 1; $year <= $currentYear + 1; $year++) {
            $periodes[] = $year . '/' . ($year + 1) . ' Ganjil';
            $periodes[] = $year . '/' . ($year + 1) . ' Genap';
        }

        // Get filter parameters
        $selectedSemester = $request->input('semester', '');
        $selectedTahunAjaran = $request->input('tahun_ajaran', '');
        $selectedTingkat = $request->input('tingkat', '');

        // Check if filter is applied
        $filterApplied = !empty($selectedSemester) && !empty($selectedTahunAjaran) && !empty($selectedTingkat);

        $matkulList = [];

        if ($filterApplied) {
            // Get user prodi
            $user = auth()->user();
            $prodiKode = $user->prodi ? $user->prodi->kode_prodi : 'TRPL';

            $prodiIdMap = [
                'TRPL' => 4,
                'TI'   => 1,
                'NM'   => 3,
            ];

            $prodiId = $prodiIdMap[$prodiKode] ?? 4;

            // Get data from perkuliahan_monitoring_snapshots
            $snapshots = \App\Models\PerkuliahanMonitoringSnapshot::where('prodi_id', $prodiId)
                ->where('semester', $selectedSemester)
                ->where('tahun_ajaran', $selectedTahunAjaran)
                ->where('tingkat', $selectedTingkat)
                ->get();

            // Group by kuliah_id to avoid duplicates
            $grouped = $snapshots->groupBy('kuliah_id');

            foreach ($grouped as $kuliahId => $items) {
                $first = $items->first();

                // Get unique pegawai_ids
                $pegawaiIds = $items->pluck('pegawai_id')->filter()->unique()->toArray();

                // Get dosen names from dosenn table
                $dosenNames = [];
                if (!empty($pegawaiIds)) {
                    $dosens = \App\Models\Dosenn::whereIn('pegawai_id', $pegawaiIds)
                        ->get(['pegawai_id', 'nama']);

                    foreach ($dosens as $dosen) {
                        $dosenNames[] = $dosen->nama;
                    }
                }

                $dosenNamesStr = !empty($dosenNames) ? implode(', ', $dosenNames) : '-';

                $matkulList[] = [
                    'kuliah_id' => $kuliahId,
                    'kode_mk' => $first->kode_mk,
                    'nama_matkul' => $first->nama_matkul,
                    'dosen_pengampu' => $dosenNamesStr,
                    'pegawai_ids' => $pegawaiIds,
                    'tingkat' => $selectedTingkat,
                ];
            }
        }

        return view('gkm.monitoring-kuesioner.create', compact(
            'periodes',
            'selectedSemester',
            'selectedTahunAjaran',
            'selectedTingkat',
            'filterApplied',
            'matkulList'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_file' => 'required|string|max:255',
            'file_excel' => 'required|file|mimes:xlsx,xls|max:10240',
            'periode' => 'required|string|max:255',
            'selected_matkul' => 'required|string', // Format: kuliah_id|kode_mk|nama_matkul|dosen|pegawai_id|tingkat
            'deskripsi' => 'nullable|string'
        ]);

        try {
            // Parse selected matkul data
            $matkulData = explode('|', $request->selected_matkul);
            $kuliahId = $matkulData[0] ?? null;
            $kodeMk = $matkulData[1] ?? null;
            $namaMatkul = $matkulData[2] ?? null;
            $dosenPengampu = $matkulData[3] ?? null;
            $pegawaiId = $matkulData[4] ?? null;
            $tingkat = $matkulData[5] ?? null;

            // Validate tingkat
            if (!in_array($tingkat, [1, 2, 3, 4])) {
                return back()->withErrors(['error' => 'Data matakuliah tidak valid']);
            }

            // Upload file
            $file = $request->file('file_excel');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('kuesioner', $fileName, 'public');

            // Baca file Excel untuk mendapatkan jumlah responden
            try {
                $spreadsheet = IOFactory::load($file->getPathname());
                $worksheet = $spreadsheet->getActiveSheet();

                // Hitung baris dengan cara yang lebih aman
                $totalResponden = 0;
                $maxRow = min($worksheet->getHighestRow(), 200); // Batasi maksimal 200 baris

                for ($row = 2; $row <= $maxRow; $row++) { // Mulai dari baris 2 (skip header)
                    try {
                        $cellA = $worksheet->getCell('A' . $row);
                        $value = (string)$cellA->getValue(); // Gunakan getValue() bukan getCalculatedValue()

                        // Hitung jika baris berisi data responden
                        if (!empty($value) && !is_numeric($value) &&
                            (strpos(strtolower($value), 'anonymous') !== false ||
                             strpos(strtolower($value), 'peserta') !== false)) {
                            $totalResponden++;
                        }
                    } catch (\Exception $e) {
                        // Skip cell yang bermasalah
                        continue;
                    }
                }

                // Jika tidak ada data responden yang terdeteksi, gunakan estimasi
                if ($totalResponden == 0) {
                    $totalResponden = max(0, $maxRow - 1); // Total baris minus header
                }

            } catch (\Exception $e) {
                $totalResponden = 0; // Default jika gagal membaca
                \Log::warning('Excel reading failed in store: ' . $e->getMessage());
            }

            // Simpan ke database dengan user_id dari user yang login
            $kuesioner = KuesioneUpload::create([
                'nama_file' => $request->nama_file,
                'file_path' => $filePath,
                'periode' => $request->periode,
                'nama_matakuliah' => $namaMatkul,
                'kode_matakuliah' => $kodeMk,
                'dosen_pengampu' => $dosenPengampu,
                'pegawai_id' => $pegawaiId,
                'kuliah_id' => $kuliahId,
                'tingkat' => $request->tingkat,
                'user_id' => auth()->id(),
                'deskripsi' => $request->deskripsi,
                'total_responden' => $totalResponden,
                'status' => 'uploaded'
            ]);

            // Proses analisis dengan AI (async)
            try {
                $this->processKuesioneAnalysis($kuesioner->id);
            } catch (\Exception $e) {
                // Jika AI processing gagal, tetap lanjutkan dengan status uploaded
                \Log::error('AI processing failed: ' . $e->getMessage());
            }

            return redirect()->route('gkm.monitoring-kuesioner.index')
                ->with('success', 'File kuesioner berhasil diupload dan sedang diproses oleh AI Agent.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal mengupload file: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $kuesioner = KuesioneUpload::with(['user', 'user.prodi'])->findOrFail($id);
        return view('gkm.monitoring-kuesioner.show', compact('kuesioner'));
    }

    public function destroy($id)
    {
        try {
            $kuesioner = KuesioneUpload::findOrFail($id);

            // Hapus file
            if (Storage::disk('public')->exists($kuesioner->file_path)) {
                Storage::disk('public')->delete($kuesioner->file_path);
            }

            $kuesioner->delete();

            return redirect()->route('gkm.monitoring-kuesioner.index')
                ->with('success', 'Data kuesioner berhasil dihapus.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus data: ' . $e->getMessage()]);
        }
    }

    public function generateReport($id)
    {
        $kuesioner = KuesioneUpload::with(['user', 'user.prodi'])->findOrFail($id);

        if ($kuesioner->status !== 'completed') {
            return back()->withErrors(['error' => 'Analisis belum selesai. Silakan tunggu beberapa saat.']);
        }

        return view('gkm.monitoring-kuesioner.report', compact('kuesioner'));
    }

   public function indexApi(Request $request)
{
    $ta = $request->ta;
    $semester = $request->semester;
    $tingkat = $request->tingkat;
    $tahunList = range(2020, date('Y'));

    $list = collect();

    if ($ta && $semester) {

        // 🔥 ambil user
        $user = auth()->user();

        // 🔥 mapping prodi (dari kode_prodi → id API)
        $prodiKode = $user->prodi ? $user->prodi->kode_prodi : 'TRPL';

        $prodiIdMap = [
            'TRPL' => 4,
            'TI'   => 1,
            'NM'   => 3,
        ];

        $prodiId = $prodiIdMap[$prodiKode] ?? 4;

        // 🔥 ambil data dari API
        $data = $this->apiService->getMatkulByProdiSemTa(
            $prodiId,
            $semester,
            $ta
        );

        if (!empty($data)) {

            $list = collect($data)

                // 🔥 FILTER TINGKAT
                ->filter(function ($item) use ($tingkat) {

                    if (!$tingkat) return true;

                    $kodeMk = $item['kode_mk'] ?? '';

                    if (strlen($kodeMk) < 4) return false;

                    $tingkatMk = substr($kodeMk, 3, 1);

                    return $tingkatMk == $tingkat;
                })

                // 🔥 MAP DATA KE VIEW
                ->map(function ($item) use ($ta) {
                    return [
                        'kode_mk' => $item['kode_mk'] ?? '-',
                        'nama_mk' => $item['nama_matkul'] ?? '-',
                        'ta' => $ta
                    ];
                });
        }
    }

    return view('gkm.monitoring-kuesioner.create-api', [
        'list' => $list,
        'ta' => $ta,
        'semester' => $semester,
        'tahunList' => $tahunList,
        'tingkat' => $tingkat
    ]);
}
/**
 * Ambil data dari API via service
 */
private function fetchApi($ta, $kodeMk)
{
    $response = $this->apiService->getRekapKuesioner($ta, $kodeMk);

    if (!$response) {
        \Log::error('API gagal', [
            'ta' => $ta,
            'kode_mk' => $kodeMk
        ]);

        throw new \Exception('API gagal atau token tidak valid');
    }

    return $response;
}

public function listKuesioner(Request $request)
{
    $kodeMk = $request->kode_mk;
    $ta = $request->ta;

    $list = [];

    try {
        // 🔥 ambil dari API (sementara 1 dulu)
        $apiData = $this->fetchApi($ta, $kodeMk);

        // 👉 karena API kamu cuma kasih 1
        // kita bungkus jadi array
        if ($apiData && isset($apiData['metadata'])) {
            $list[] = [
                'judul' => $apiData['metadata']['judul_kuesioner'] ?? 'Kuesioner',
                'kode_mk' => $kodeMk,
                'ta' => $ta
            ];
        }

    } catch (\Exception $e) {
        \Log::error('List kuesioner gagal', [
            'error' => $e->getMessage()
        ]);
    }

    return view('gkm.monitoring-kuesioner.list-kuesioner', [
        'list' => $list,
        'kode_mk' => $kodeMk,
        'ta' => $ta
    ]);
}



/**
 * Mapping API → format RAG kamu
 */
private function mapApiToIndexedData($apiData)
{
    $metadata = [
        'total_responden' => $apiData['statistik']['total_responden_aktif'],
        'total_pertanyaan' => count($apiData['statistik']['rekapitulasi']),
        'pertanyaan' => []
    ];

    $statistik = [];
    $no = 1;

    foreach ($apiData['statistik']['rekapitulasi'] as $item) {

        $qId = 'Q' . $no;

        // ✅ FIX BUG (lengkap)
        $counts = [
            'STS' => 0,
            'TS' => 0,
            'CS' => 0,
            'S' => 0,
            'SS' => 0
        ];

        foreach ($item['rincian_jawaban'] as $j) {
            match ($j['jawaban']) {
                '5' => $counts['STS'] = (int)$j['jumlah'],
                '4' => $counts['TS']  = (int)$j['jumlah'],
                '3' => $counts['CS']  = (int)$j['jumlah'],
                '2' => $counts['S']   = (int)$j['jumlah'],
                '1' => $counts['SS']  = (int)$j['jumlah'],
                default => null
            };
        }

        $metadata['pertanyaan'][] = [
            'id' => $qId,
            'teks' => strip_tags($item['pertanyaan'])
        ];

        $statistik[$qId] = [
            'teks_pertanyaan' => strip_tags($item['pertanyaan']),
            'distribusi' => $counts,
            'total_responden' => $item['total_suara']
        ];

        $no++;
    }

    return [
        'metadata' => $metadata,
        'statistik_per_pertanyaan' => $statistik,
        'sample_responses' => []
    ];
}
private function extractKuesionerInfo($nama, $kodeMk)
{
    // 1. Ambil nama MK
    preg_match('/Evaluasi Matakuliah (.*?) Semester/', $nama, $matchMk);
    $nama_mk = $matchMk[1] ?? '-';

    // 2. Ambil inisial dosen
    preg_match('/\((.*?)\)/', $nama, $matchDosen);

    $inisial = $matchDosen[1] ?? null;

    if ($inisial) {
    // 🔥 ambil sebelum "/"
    $inisial = explode('/', $inisial)[0];

    // bersihkan
    $inisial = strtoupper(trim($inisial));
}

    // 3. Cari dosen
    $dosen = null;
    if ($inisial) {
        $dosen = \App\Models\Dosenn::whereRaw('TRIM(UPPER(inisial_nama)) = ?', [$inisial])->first();
    }
    \Log::info('DEBUG DOSEN', [
        'judul' => $nama,
        'inisial' => $inisial,
        'dosen_found' => $dosen
    ]);

    // 4. Ambil tingkat
    $tingkat = '-';
    if (strlen($kodeMk) >= 4) {
        $tingkat = substr($kodeMk, 3, 1);
    }

    return [
        'nama_mk' => $nama_mk,
        'tingkat' => $tingkat,

        // 🔥 PAKAI PEGAWAI ID
        'pegawai_id' => $dosen->pegawai_id ?? null,

        // optional (buat tampilan langsung)
        'dosen_nama' => $dosen->nama ?? $inisial ?? '-'
    ];
}

public function processFromApi(Request $request)
{
    $request->validate([
        'ta' => 'required',
        'kode_mk' => 'required'
    ]);

    try {

        // 🔥 simpan dulu
        $kuesioner = KuesioneUpload::create([
            'nama_file' => 'Kuesioner API',
            'kode_matakuliah' => $request->kode_mk,
            'periode' => $request->ta,
            'semester' => $request->semester ?? null,
            'status' => 'processing',
            'file_path' => 'from-api',
            'source' => 'api',
            'user_id' => auth()->id()
        ]);

        // 🔥 lempar ke function proses
        $this->processKuesionerFromApi($kuesioner->id);

        return redirect()
            ->route('gkm.monitoring-kuesioner.show', $kuesioner->id)
            ->with('success', 'Analisis sedang diproses');

    } catch (\Exception $e) {

        return back()->with('error', $e->getMessage());
    }
}

private function processKuesionerFromApi($kuesioneId)
{
    try {
        $kuesioner = KuesioneUpload::findOrFail($kuesioneId);

        $kuesioner->update(['status' => 'processing']);

        // =========================
        // 1. HIT API
        // =========================
        $apiData = $this->fetchApi(
            $kuesioner->periode,
            $kuesioner->kode_matakuliah
        );

        if (!isset($apiData['statistik'])) {
            throw new \Exception('Data API tidak valid');
        }

        // =========================
        // 2. MAPPING
        // =========================
        $indexedData = $this->mapApiToIndexedData($apiData);

        // =========================
        // 3. AI ANALYSIS
        // =========================
        $analysisResult = $this->aiAgent->analyzeFromApi(
            $kuesioner,
            $indexedData
        );

        // =========================
// EXTRACTION (BARU 🔥)
// =========================
$parsed = $this->extractKuesionerInfo(
    $apiData['metadata']['judul_kuesioner'] ?? '',
    $kuesioner->kode_matakuliah
);

        // =========================
        // 4. UPDATE
        // =========================
        $kuesioner->update([
    'nama_file' => $apiData['metadata']['judul_kuesioner'] ?? 'Kuesioner API',

    // 🔥 HASIL PARSING
    'nama_matakuliah' => $parsed['nama_mk'],
    'tingkat' => $parsed['tingkat'],
    'pegawai_id' => $parsed['pegawai_id'],

    // 🔥 EXISTING
    'total_responden' => $apiData['statistik']['total_responden_aktif'] ?? 0,
    'hasil_analisis' => $analysisResult,
    'index_kepuasan' => $analysisResult['statistik']['index_kepuasan'] ?? 0,
    'persen_kepuasan' => $analysisResult['statistik']['persen_kepuasan'] ?? 0,
    'status' => 'completed'
]);

        \Log::info("API Analysis Success", [
            'id' => $kuesioner->id
        ]);

    } catch (\Exception $e) {

        \Log::error("API Analysis Failed", [
            'id' => $kuesioneId,
            'error' => $e->getMessage()
        ]);

        $kuesioner = KuesioneUpload::find($kuesioneId);

        if ($kuesioner) {
            $kuesioner->update([
                'status' => 'error',
                'hasil_analisis' => [
                    'error' => $e->getMessage()
                ]
            ]);
        }
    }
}

    /**
     * API Endpoint untuk pencarian matakuliah secara real-time
     */
    public function searchMatkul(Request $request)
    {
        $search = strtolower($request->input('search', ''));
        $periode = $request->input('periode', '');

        // Get user prodi
        $user = auth()->user();
        $prodiKode = $user->prodi ? $user->prodi->kode_prodi : 'TRPL';

        $prodiIdMap = [
            'TRPL' => 4,
            'TI'   => 1,
            'NM'   => 3,
        ];

        $prodiId = $prodiIdMap[$prodiKode] ?? 4;

        // Get data from perkuliahan_monitoring_snapshots
        $snapshots = \App\Models\PerkuliahanMonitoringSnapshot::where('prodi_id', $prodiId);

        // Group by kuliah_id dan ambil unique records
        $snapshots = $snapshots->get();
        $grouped = $snapshots->groupBy('kuliah_id');

        $results = [];

        foreach ($grouped as $kuliahId => $items) {
            $first = $items->first();

            // Get unique pegawai_ids
            $pegawaiIds = $items->pluck('pegawai_id')->filter()->unique()->toArray();

            // Get dosen names from dosenn table
            $dosenNames = [];
            if (!empty($pegawaiIds)) {
                $dosens = \App\Models\Dosenn::whereIn('pegawai_id', $pegawaiIds)
                    ->get(['pegawai_id', 'nama']);

                foreach ($dosens as $dosen) {
                    $dosenNames[] = $dosen->nama;
                }
            }

            $dosenNamesStr = !empty($dosenNames) ? implode(', ', $dosenNames) : '-';

            // Filter berdasarkan search term
            $kodeMk = $first->kode_mk ?? '';
            $namaMatkul = $first->nama_matkul ?? '';

            if ($search &&
                strpos(strtolower($kodeMk), $search) === false &&
                strpos(strtolower($namaMatkul), $search) === false &&
                strpos(strtolower($dosenNamesStr), $search) === false) {
                continue;
            }

            $tingkat = $first->tingkat ?? '';
            $results[] = [
                'kuliah_id' => $kuliahId,
                'kode_mk' => $kodeMk,
                'nama_matkul' => $namaMatkul,
                'dosen_pengampu' => $dosenNamesStr,
                'pegawai_ids' => $pegawaiIds,
                'tingkat' => $tingkat,
                'value' => $kuliahId . '|' . $kodeMk . '|' . $namaMatkul . '|' . $dosenNamesStr . '|' . implode(',', $pegawaiIds) . '|' . $tingkat,
            ];
        }

        // Limit results to 20
        $results = array_slice($results, 0, 20);

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    private function processKuesioneAnalysis($kuesioneId)
    {
        try {
            $kuesioner = KuesioneUpload::with(['user', 'user.prodi'])->findOrFail($kuesioneId);
            $kuesioner->update(['status' => 'processing']);

            // Baca file Excel
            $filePath = storage_path('app/public/' . $kuesioner->file_path);

            if (!file_exists($filePath)) {
                throw new \Exception('File tidak ditemukan');
            }

            // Baca Excel data
            $excelData = $this->bacaExcelAman($filePath);

            // PURE AI AGENT - Semua analisis dilakukan oleh AI
            $analysisResult = $this->aiAgent->analyzeKuesioner($kuesioner, $excelData);

            // Update hasil analisis
            $kuesioner->update([
                'hasil_analisis' => $analysisResult,
                'status' => 'completed'
            ]);

            \Log::info("AI Agent completed kuesioner analysis", [
                'kuesioner_id' => $kuesioner->id,
                'index_kepuasan' => $analysisResult['statistik']['index_kepuasan'] ?? 'N/A'
            ]);

        } catch (\Exception $e) {
            \Log::error("AI Agent failed to process kuesioner", [
                'kuesioner_id' => $kuesioneId,
                'error' => $e->getMessage()
            ]);

            $kuesioner = KuesioneUpload::with(['user', 'user.prodi'])->find($kuesioneId);
            if ($kuesioner) {
                $kuesioner->update([
                    'status' => 'error',
                    'hasil_analisis' => [
                        'error' => 'AI Agent gagal memproses: ' . $e->getMessage(),
                        'ringkasan' => 'Terjadi kesalahan saat AI Agent memproses kuesioner',
                        'poin_positif' => ['File berhasil diupload'],
                        'area_perbaikan' => ['Periksa koneksi AI Agent', 'Periksa format file Excel'],
                        'rekomendasi' => ['Coba upload ulang', 'Pastikan AI Agent aktif'],
                        'statistik' => [
                            'index_kepuasan' => 0,
                            'total_responden' => 0,
                            'total_pertanyaan' => 0
                        ]
                    ]
                ]);
            }
        }
    }

    private function bacaExcelAman($filePath)
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            $data = [];
            $headers = [];

            // Baca header dengan aman - gunakan getValue() bukan getCalculatedValue()
            try {
                $headerRow = $worksheet->getRowIterator(1, 1)->current();
                $cellIterator = $headerRow->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                foreach ($cellIterator as $cell) {
                    try {
                        $value = $cell->getValue();
                        $headers[] = is_null($value) ? '' : (string)$value;
                    } catch (\Exception $e) {
                        $headers[] = '';
                    }

                    // Batasi maksimal 20 kolom
                    if (count($headers) >= 20) break;
                }
            } catch (\Exception $e) {
                $headers = ['Peserta', 'Q1', 'Q2', 'Q3', 'Q4', 'Q5'];
            }

            // Baca data dengan aman
            $maxRow = min($worksheet->getHighestRow(), 100);
            for ($rowNum = 2; $rowNum <= $maxRow; $rowNum++) {
                try {
                    $rowData = [];
                    $row = $worksheet->getRowIterator($rowNum, $rowNum)->current();
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);

                    $colCount = 0;
                    foreach ($cellIterator as $cell) {
                        if ($colCount >= count($headers)) break;

                        try {
                            $value = $cell->getValue();
                            $rowData[] = is_null($value) ? '' : (string)$value;
                        } catch (\Exception $e) {
                            $rowData[] = '';
                        }
                        $colCount++;
                    }

                    // Hanya ambil baris yang berisi data responden
                    if (!empty($rowData[0]) && !is_numeric($rowData[0]) &&
                        (strpos(strtolower($rowData[0]), 'anonymous') !== false ||
                         strpos(strtolower($rowData[0]), 'peserta') !== false)) {
                        $data[] = $rowData;
                    }

                } catch (\Exception $e) {
                    // Skip baris yang bermasalah
                    \Log::warning('Skip row ' . $rowNum . ': ' . $e->getMessage());
                    continue;
                }
            }

            return ['headers' => $headers, 'data' => $data];

        } catch (\Exception $e) {
            \Log::error('Excel reading error: ' . $e->getMessage());
            return [
                'headers' => ['Peserta', 'Q1', 'Q2', 'Q3', 'Q4', 'Q5'],
                'data' => [['Sample', 'S', 'SS', 'S', 'CS', 'S']]
            ];
        }
    }
}
