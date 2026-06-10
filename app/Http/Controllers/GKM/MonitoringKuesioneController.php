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
use App\Models\PeriodeAkademik;
use Illuminate\Support\Facades\DB;
use App\Models\KuesionerMongo;
use Barryvdh\DomPDF\Facade\Pdf;


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
private function getPeriodeAktif()
{
    $periode = PeriodeAkademik::where('is_active', 1)->first();

    if (!$periode) {
        throw new \Exception('Periode akademik aktif tidak ditemukan');
    }

    return [
        'ta' => $periode->tahun_ajaran,
        'semester' => $periode->semester,
        'semester_label' => $periode->semester_label
    ];
}

    public function index(Request $request)
    {
        $user = auth()->user();

    $kuesionersQuery = KuesioneUpload::with(['user', 'user.prodi'])
        ->whereHas('user', function ($q) use ($user) {
            $q->where('prodi_id', $user->prodi_id);
        })
        ->orderBy('created_at', 'desc');

    // =========================
    // PAGINATION
    // =========================
    $perPage = 10;
    $kuesioners = $kuesionersQuery->paginate($perPage);

    // Map untuk melengkapi nama matakuliah
    $kuesioners->getCollection()->transform(function ($k) {
        // Pastikan nama_matakuliah terisi
        if (empty($k->nama_matakuliah) && !empty($k->kode_matakuliah)) {
            // Coba cari dengan berbagai variasi
            $matkul = \App\Models\Matakuliah::where('kode_mk', $k->kode_matakuliah)
                ->orWhere('kode_mk', 'LIKE', '%' . $k->kode_matakuliah . '%')
                ->first();
            
            if ($matkul) {
                $k->nama_matakuliah = $matkul->nama_mk;
            } else {
                // Jika tidak ada di tabel matakuliah, coba ambil dari API atau sumber lain
                // Log untuk debugging
                \Log::info('Matakuliah tidak ditemukan', [
                    'kode_mk' => $k->kode_matakuliah,
                    'kuesioner_id' => $k->id
                ]);
            }
        }
        return $k;
    });

    $laporanBulanan = KuesioneUpload::whereYear('created_at', date('Y'))
        ->whereMonth('created_at', date('m'))
        ->whereHas('user', function ($q) use ($user) {
            $q->where('prodi_id', $user->prodi_id);
        })
        ->count();

    $laporanTahunan = KuesioneUpload::whereYear('created_at', date('Y'))
        ->whereHas('user', function ($q) use ($user) {
            $q->where('prodi_id', $user->prodi_id);
        })
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
        $dosenList = DB::table('dosenn')
        ->select('pegawai_id', 'nama')
        ->orderBy('nama')
        ->get();

        return view('gkm.monitoring-kuesioner.create', compact('periodes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_file' => 'required|string|max:255',
            'file_excel' => 'required|file|mimes:xlsx,xls|max:10240',
            'periode' => 'required|string|max:255',
            'nama_matakuliah' => 'nullable|string|max:255',
            'kode_matakuliah' => 'nullable|string|max:100',
            'dosen_pengampu' => 'nullable|string|max:255',
            'tingkat' => 'nullable|integer|in:1,2,3,4',
            'deskripsi' => 'nullable|string'
        ]);
        $dosen = null;

if ($request->dosen_pengampu) {
    $dosen = DB::table('dosenn')
        ->where('pegawai_id', $request->dosen_pengampu)
        ->first();
}

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
                'nama_matakuliah' => $request->nama_matakuliah,
                'kode_matakuliah' => $request->kode_matakuliah,
                'dosen_pengampu' => $request->dosen_pengampu,
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
        
        // Pastikan nama_matakuliah terisi
        if (empty($kuesioner->nama_matakuliah) && !empty($kuesioner->kode_matakuliah)) {
            // Coba dari database lokal
            $matkul = \App\Models\Matakuliah::where('kode_mk', $kuesioner->kode_matakuliah)->first();
            
            if ($matkul) {
                $kuesioner->nama_matakuliah = $matkul->nama_mk;
                $kuesioner->save();
            } else {
                // Jika tidak ada, coba ambil dari API eksternal
                try {
                    $user = $kuesioner->user;
                    if ($user && $user->prodi) {
                        $prodiKode = $user->prodi->kode_prodi;
                        $prodiIdMap = ['TRPL' => 4, 'TI' => 1, 'NM' => 3, 'TK' => 2];
                        $prodiId = $prodiIdMap[$prodiKode] ?? 4;
                        
                        $semester = $kuesioner->semester ?? ($kuesioner->periode && stripos($kuesioner->periode, 'Genap') !== false ? '2' : '1');
                        $ta = $kuesioner->periode;
                        
                        $apiService = app(\App\Services\ExternalApiService::class);
                        $matkulData = $apiService->getMatkulByProdiSemTa($prodiId, $semester, $ta);
                        
                        if ($matkulData && is_array($matkulData)) {
                            $matkulData = isset($matkulData['data']) ? $matkulData['data'] : $matkulData;
                            
                            foreach ($matkulData as $mk) {
                                if (is_object($mk)) $mk = (array) $mk;
                                
                                if (isset($mk['kode_mk']) && $mk['kode_mk'] == $kuesioner->kode_matakuliah) {
                                    $namaMk = $mk['nama_matkul'] ?? $mk['nama_mk'] ?? null;
                                    if ($namaMk) {
                                        $kuesioner->nama_matakuliah = $namaMk;
                                        $kuesioner->save();
                                        break;
                                    }
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to fetch matakuliah from API in show()', [
                        'kuesioner_id' => $id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
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

        // Pastikan nama_matakuliah terisi
        if (empty($kuesioner->nama_matakuliah) && !empty($kuesioner->kode_matakuliah)) {
            $matkul = \App\Models\Matakuliah::where('kode_mk', $kuesioner->kode_matakuliah)->first();
            $kuesioner->nama_matakuliah = $matkul ? $matkul->nama_mk : null;
        }

        return view('gkm.monitoring-kuesioner.report', compact('kuesioner'));
    }

    public function reprocess($id)
    {
        try {
            $kuesioner = KuesioneUpload::findOrFail($id);

            // Jika dari API, proses dengan API flow
            if ($kuesioner->file_path === 'from-api' || $kuesioner->source === 'api') {
                $kuesioner->update(['status' => 'processing']);
                $this->processKuesionerFromApi($kuesioner->id);
            } else {
                // Jika dari file upload, proses dengan flow normal
                $kuesioner->update(['status' => 'processing']);
                $this->processKuesioneAnalysis($kuesioner->id);
            }

            return redirect()->route('gkm.monitoring-kuesioner.show', $kuesioner->id)
                ->with('success', 'Proses analisis ulang dimulai. Silakan tunggu beberapa saat.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal memulai proses ulang: ' . $e->getMessage()]);
        }
    }

   public function indexApi(Request $request)
{
    $periodeAktif = $this->getPeriodeAktif();

$taAktif = $periodeAktif['ta'];
$semesterAktif = $periodeAktif['semester'];
$ta = $request->ta ?? $taAktif;
$semester = $request->semester ?? $semesterAktif;

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

    // =========================
    // PAGINATION
    // =========================
    $perPage = 10;
    $currentPage = $request->input('page', 1);

    $pagination = new \Illuminate\Pagination\LengthAwarePaginator(
        $list->forPage($currentPage, $perPage),
        $list->count(),
        $perPage,
        $currentPage,
        ['path' => $request->url(), 'query' => $request->query()]
    );

    return view('gkm.monitoring-kuesioner.create-api', [
    'list' => $list,
    'pagination' => $pagination,
    'ta' => $ta,
    'semester' => $semester,
    'tingkat' => $tingkat,
    'tahunList' => $tahunList,
    'taAktif' => $taAktif,
    'semesterAktif' => $semesterAktif
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

        $apiData = $this->fetchApi($ta, $kodeMk);

        // cek apakah ada daftar_rekap
        if (
            $apiData &&
            isset($apiData['daftar_rekap']) &&
            is_array($apiData['daftar_rekap'])
        ) {

            foreach ($apiData['daftar_rekap'] as $rekap) {

                $metadata = $rekap['metadata'] ?? [];

                $list[] = [
                    'judul' => $metadata['judul_kuesioner'] ?? 'Kuesioner',
                    'kode_mk' => $metadata['kode_mk'] ?? $kodeMk,
                    'ta' => $ta,
                    'kuesioner_id' => $metadata['kuesioner_id'] ?? null,
                    'semester' => $metadata['semester'] ?? null,
                ];
            }
        }

    } catch (\Exception $e) {

        \Log::error('List kuesioner gagal', [
            'error' => $e->getMessage()
        ]);
    }

    // =========================
    // PAGINATION
    // =========================
    $perPage = 10;
    $currentPage = $request->input('page', 1);

    $listCollection = collect($list);
    $pagination = new \Illuminate\Pagination\LengthAwarePaginator(
        $listCollection->forPage($currentPage, $perPage),
        $listCollection->count(),
        $perPage,
        $currentPage,
        ['path' => $request->url(), 'query' => $request->query()]
    );

    return view('gkm.monitoring-kuesioner.list-kuesioner', [
        'list' => $list,
        'pagination' => $pagination,
        'kode_mk' => $kodeMk,
        'ta' => $ta
    ]);
}



/**
 * Mapping API → format RAG kamu
 */
private function mapApiToIndexedData($apiData)
{
    $rekapitulasi = $apiData['statistik']['rekapitulasi'] ?? [];

    if (empty($rekapitulasi)) {
        \Log::warning('mapApiToIndexedData: rekapitulasi kosong');
        return [
            'metadata' => [
                'total_responden' => $apiData['statistik']['total_responden_aktif'] ?? 0,
                'total_pertanyaan' => 0,
                'pertanyaan' => []
            ],
            'statistik_per_pertanyaan' => [],
            'sample_responses' => []
        ];
    }

    $metadata = [
        'total_responden' => $apiData['statistik']['total_responden_aktif'],
        'total_pertanyaan' => count($rekapitulasi),
        'pertanyaan' => []
    ];

    $statistik = [];
    $no = 1;

    foreach ($rekapitulasi as $item) {

        $qId = 'Q' . $no;

        // ✅ MAPPING FLEKSIBEL UNTUK BERBAGAI FORMAT API
        $counts = [
            'TS' => 0,
            'CS' => 0,
            'S' => 0,
            'SS' => 0
        ];

        // Mapping berbagai kemungkinan label dari API
        $mapping = [
            // Angka
            '1' => 'SS', '2' => 'S', '3' => 'CS', '4' => 'TS', '5' => 'TS', '6' => 'TS',
            // Label teks
            'SS' => 'SS', 'S' => 'S', 'CS' => 'CS', 'CTS' => 'CS', 'TS' => 'TS', 'STS' => 'TS',
            'SANGAT SETUJU' => 'SS', 'SETUJU' => 'S', 'CUKUP SETUJU' => 'CS', 'TIDAK SETUJU' => 'TS',
            'SANGAT TIDAK SETUJU' => 'TS', 'CUKUP TIDAK SETUJU' => 'CS',
            // Lowercase
            'sangat setuju' => 'SS', 'setuju' => 'S', 'cukup setuju' => 'CS', 'tidak setuju' => 'TS',
        ];

        $rincianJawaban = $item['rincian_jawaban'] ?? [];

        foreach ($rincianJawaban as $j) {
            $label = strtoupper(trim($j['jawaban'] ?? ''));
            if (isset($mapping[$label])) {
                $target = $mapping[$label];
                $counts[$target] += (int)($j['jumlah'] ?? 0);
            } elseif (isset($mapping[strtolower($label)])) {
                $target = $mapping[strtolower($label)];
                $counts[$target] += (int)($j['jumlah'] ?? 0);
            } else {
                // Jika tidak dikenali, log untuk debugging
                \Log::warning("Label jawaban tidak dikenali dari API", [
                    'label' => $j['jawaban'] ?? 'unknown',
                    'pertanyaan' => $item['pertanyaan'] ?? 'unknown'
                ]);
            }
        }

        $metadata['pertanyaan'][] = [
            'id' => $qId,
            'teks' => strip_tags($item['pertanyaan'] ?? '')
        ];

        $statistik[$qId] = [
            'teks_pertanyaan' => strip_tags($item['pertanyaan'] ?? ''),
            'distribusi' => $counts,
            'total_responden' => $item['total_suara'] ?? 0
        ];

        $no++;
    }

    return [
        'metadata' => $metadata,
        'statistik_per_pertanyaan' => $statistik,
        'sample_responses' => []
    ];
}

/**
 * Extract statistik dari daftar_rekap (format alternatif API)
 */
private function extractStatistikFromDaftarRekap($daftarRekap)
{
    \Log::info('Extracting statistik from daftar_rekap', [
        'jumlah_rekap' => count($daftarRekap)
    ]);

    $totalResponden = 0;
    $allRekapitulasi = [];

    foreach ($daftarRekap as $index => $rekap) {
        \Log::info('Processing rekap item', [
            'index' => $index,
            'keys' => array_keys($rekap),
            'has_statistik' => isset($rekap['statistik']),
            'has_data' => isset($rekap['data']),
            'has_metadata' => isset($rekap['metadata']),
            'rekap_sample' => array_slice($rekap, 0, 2, true) // Show first 2 keys
        ]);

        // Setiap rekap mungkin punya statistik sendiri
        if (isset($rekap['statistik'])) {
            $stat = $rekap['statistik'];
            $totalResponden += (int)($stat['total_responden_aktif'] ?? 0);

            if (isset($stat['rekapitulasi']) && is_array($stat['rekapitulasi'])) {
                $allRekapitulasi = array_merge($allRekapitulasi, $stat['rekapitulasi']);
            }
        }
        // Atau mungkin data ada di level rekap langsung
        elseif (isset($rekap['data'])) {
            // Handle format lain jika ada
            \Log::info('Found data in rekap', ['keys' => array_keys($rekap['data'])]);
        }
    }

    \Log::info('Extracted statistik summary', [
        'total_responden' => $totalResponden,
        'total_pertanyaan' => count($allRekapitulasi)
    ]);

    return [
        'total_responden_aktif' => $totalResponden,
        'rekapitulasi' => $allRekapitulasi
    ];
}
private function extractKuesionerInfo($nama, $kodeMk)
{
    // 1. Ambil nama MK dengan berbagai pattern
    $nama_mk = '-';
    
    // Pattern 1: "Evaluasi Mata Kuliah [NAMA] UTS/UAS Semester"
    if (preg_match('/Evaluasi Mata Kuliah\s+(.+?)\s+(?:UTS|UAS)\s+Semester/i', $nama, $matchMk)) {
        $nama_mk = trim($matchMk[1]);
    }
    // Pattern 2: "Evaluasi Matakuliah [NAMA] Semester" (tanpa UTS/UAS)
    elseif (preg_match('/Evaluasi Matakuliah\s+(.+?)\s+Semester/i', $nama, $matchMk)) {
        $nama_mk = trim($matchMk[1]);
    }
    // Pattern 3: Extract anything between "Kuliah" dan "UTS/UAS/Semester"
    elseif (preg_match('/Kuliah\s+(.+?)\s+(?:UTS|UAS|Semester)/i', $nama, $matchMk)) {
        $nama_mk = trim($matchMk[1]);
    }

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
        'dosen_found' => $dosen,
        'nama_mk_extracted' => $nama_mk
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

/**
 * Deteksi jenis kuesioner dari judul (UTS, UAS, atau REGULAR)
 */
private function detectJenisKuesioner($judul)
{
    $judulUpper = strtoupper($judul);
    
    // Cek apakah ada kata UTS
    if (strpos($judulUpper, 'UTS') !== false || strpos($judulUpper, 'UJIAN TENGAH') !== false) {
        return 'UTS';
    }
    
    // Cek apakah ada kata UAS
    if (strpos($judulUpper, 'UAS') !== false || strpos($judulUpper, 'UJIAN AKHIR') !== false) {
        return 'UAS';
    }
    
    // Default REGULAR jika tidak ada UTS/UAS
    return 'REGULAR';
}

public function processFromApi(Request $request)
{
    $request->validate([
        'ta' => 'required',
        'kode_mk' => 'required',
        'kuesioner_id' => 'required' // 🔥 WAJIB ada kuesioner_id spesifik
    ]);

    try {
        // Get semester dari request atau dari periode aktif
        $semester = $request->semester;
        if (!$semester) {
            $periodeAktif = $this->getPeriodeAktif();
            $semester = $periodeAktif['semester'];
        }

        // 🔥 Ambil nama matakuliah dari database
        $matakuliah = \App\Models\Matakuliah::where('kode_mk', $request->kode_mk)->first();
        $namaMk = $matakuliah ? $matakuliah->nama_mk : null;
        
        // 🔥 Jika tidak ada di database, coba ambil dari API eksternal
        if (!$namaMk) {
            try {
                $user = auth()->user();
                $prodiKode = $user->prodi ? $user->prodi->kode_prodi : 'TRPL';
                $prodiIdMap = ['TRPL' => 4, 'TI' => 1, 'NM' => 3, 'TK' => 2];
                $prodiId = $prodiIdMap[$prodiKode] ?? 4;
                
                $matkulData = $this->apiService->getMatkulByProdiSemTa($prodiId, $semester, $request->ta);
                
                if ($matkulData && is_array($matkulData)) {
                    $matkulData = isset($matkulData['data']) ? $matkulData['data'] : $matkulData;
                    
                    foreach ($matkulData as $mk) {
                        if (is_object($mk)) $mk = (array) $mk;
                        if (isset($mk['kode_mk']) && $mk['kode_mk'] == $request->kode_mk) {
                            $namaMk = $mk['nama_matkul'] ?? $mk['nama_mk'] ?? null;
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to fetch matakuliah from external API', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // 🔥 Ambil tingkat dari kode matakuliah
        $tingkat = null;
        if (strlen($request->kode_mk) >= 4) {
            $tingkat = substr($request->kode_mk, 3, 1);
        }

        // 🔥 simpan dulu dengan data lengkap + kuesioner_id
        $kuesioner = KuesioneUpload::create([
            'nama_file' => 'Kuesioner API',
            'kode_matakuliah' => $request->kode_mk,
            'nama_matakuliah' => $namaMk,
            'tingkat' => $tingkat,
            'periode' => $request->ta,
            'semester' => $semester,
            'kuliah_id' => $request->kuesioner_id, // 🔥 SIMPAN KUESIONER_ID dari API
            'status' => 'processing',
            'file_path' => 'from-api',
            'source' => 'api',
            'user_id' => auth()->id()
        ]);

        // 🔥 lempar ke function proses dengan kuesioner_id
        $this->processKuesionerFromApi($kuesioner->id, $request->kuesioner_id);

        return redirect()
            ->route('gkm.monitoring-kuesioner.show', $kuesioner->id)
            ->with('success', 'Analisis sedang diproses');

    } catch (\Exception $e) {

        return back()->with('error', $e->getMessage());
    }
}
public function syncSemuaKuesioner(Request $request)
{
    try {
        // Tingkatkan timeout untuk proses panjang
        set_time_limit(300); // 5 menit
        ini_set('max_execution_time', 300);
        
        \Log::info('===== ANALISIS SEMUA KUESIONER MULAI =====');

        // Ambil parameter dari request
        $ta = $request->ta;
        $semester = $request->semester;
        $tingkat = $request->tingkat;

        if (!$ta || !$semester) {
            return back()->with('error', 'Tahun Ajaran dan Semester harus dipilih');
        }

        // Ambil user dan prodi
        $user = auth()->user();
        $prodiKode = $user->prodi ? $user->prodi->kode_prodi : 'TRPL';
        $prodiIdMap = ['TRPL' => 4, 'TI' => 1, 'NM' => 3, 'TK' => 2];
        $prodiId = $prodiIdMap[$prodiKode] ?? 4;

        // Ambil semua matakuliah
        $matkulList = $this->apiService->getMatkulByProdiSemTa($prodiId, $semester, $ta);

        if (isset($matkulList['data'])) {
            $matkulList = $matkulList['data'];
        }

        if (empty($matkulList)) {
            return back()->with('error', 'Tidak ada matakuliah ditemukan');
        }

        $success = 0;
        $failed = 0;
        $skipped = 0;
        $processed = 0;
        $maxProcess = 50; // Batasi maksimal proses per request untuk menghindari timeout

        foreach ($matkulList as $matkul) {
            // Stop jika sudah mencapai batas
            if ($processed >= $maxProcess) {
                \Log::info("Mencapai batas maksimal proses ({$maxProcess}), menghentikan batch ini");
                break;
            }
            
            if (is_object($matkul)) $matkul = (array) $matkul;

            $kodeMk = $matkul['kode_mk'] ?? null;
            $namaMk = $matkul['nama_matkul'] ?? $matkul['nama_mk'] ?? null;

            if (!$kodeMk) continue;

            // Filter tingkat jika dipilih
            if ($tingkat && strlen($kodeMk) >= 4) {
                $tingkatMk = substr($kodeMk, 3, 1);
                if ($tingkatMk != $tingkat) {
                    continue;
                }
            }

            try {
                // Ambil data kuesioner dari API
                $apiData = $this->fetchApi($ta, $kodeMk);

                if (!$apiData || !isset($apiData['daftar_rekap']) || empty($apiData['daftar_rekap'])) {
                    \Log::info("Kode MK {$kodeMk}: tidak ada kuesioner");
                    $skipped++;
                    continue;
                }

                // Loop semua kuesioner di matkul ini
                foreach ($apiData['daftar_rekap'] as $rekap) {
                    $metadata = $rekap['metadata'] ?? [];
                    $judul = $metadata['judul_kuesioner'] ?? "Kuesioner {$kodeMk}";
                    $kuesionerId = $metadata['kuesioner_id'] ?? null;

                    if (!$kuesionerId) continue;

                    // Cek apakah sudah ada
                    $existing = KuesioneUpload::where('kuliah_id', $kuesionerId)
                        ->where('user_id', $user->id)
                        ->first();

                    if ($existing) {
                        \Log::info("Kuesioner ID {$kuesionerId} sudah ada, skip");
                        $skipped++;
                        continue;
                    }

                    // Deteksi jenis kuesioner
                    $jenisKuesioner = $this->detectJenisKuesioner($judul);

                    // Tentukan tingkat
                    $tingkatMk = null;
                    if (strlen($kodeMk) >= 4) {
                        $tingkatMk = substr($kodeMk, 3, 1);
                    }

                    // Simpan ke database
                    $kuesioner = KuesioneUpload::create([
                        'nama_file' => $judul,
                        'kode_matakuliah' => $kodeMk,
                        'nama_matakuliah' => $namaMk,
                        'jenis_kuesioner' => $jenisKuesioner,
                        'tingkat' => $tingkatMk,
                        'periode' => $ta,
                        'semester' => $semester,
                        'kuliah_id' => $kuesionerId,
                        'status' => 'processing',
                        'file_path' => 'from-api',
                        'source' => 'api',
                        'user_id' => $user->id
                    ]);

                    // Proses dengan AI (async)
                    try {
                        $this->processKuesionerFromApi($kuesioner->id, $kuesionerId);
                        $success++;
                        $processed++;
                    } catch (\Exception $e) {
                        \Log::error("Gagal proses kuesioner ID {$kuesionerId}: " . $e->getMessage());
                        $failed++;
                        $processed++;
                    }
                }

            } catch (\Exception $e) {
                \Log::error("Gagal proses matkul {$kodeMk}: " . $e->getMessage());
                $failed++;
            }
        }

        $message = "Analisis selesai! Berhasil: {$success}, Gagal: {$failed}, Dilewati: {$skipped}";
        if ($processed >= $maxProcess) {
            $message .= " (Dibatasi {$maxProcess} kuesioner per batch, jalankan lagi untuk melanjutkan)";
        }
        
        return redirect()
            ->route('gkm.monitoring-kuesioner.index')
            ->with('success', $message);

    } catch (\Exception $e) {
        \Log::error('SYNC ERROR: ' . $e->getMessage());
        return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
    }
}

    public function showKuesioner($id)
{
    try {

        $kuesioner = KuesionerMongo::where(
            'kuesioner_id',
            $id
        )->first();

        if (!$kuesioner) {

            return back()->with(
                'error',
                'Kuesioner tidak ditemukan'
            );
        }

        return view(
            'gkm.monitoring-kuesioner.showa',
            compact('kuesioner')
        );

    } catch (\Exception $e) {

        \Log::error('SHOW KUESIONER ERROR', [
            'message' => $e->getMessage()
        ]);

        return back()->with(
            'error',
            'Gagal membuka kuesioner'
        );
    }
}

private function processKuesionerFromApi($kuesioneId, $apiKuesionerId = null)
{
    try {
        // Pastikan timeout cukup untuk proses ini
        @set_time_limit(120); // 2 menit per kuesioner
        
        $kuesioner = KuesioneUpload::findOrFail($kuesioneId);

        $kuesioner->update(['status' => 'processing']);

        // =========================
        // 1. HIT API
        // =========================
        $apiData = $this->fetchApi(
            $kuesioner->periode,
            $kuesioner->kode_matakuliah
        );

        \Log::info('API Response Structure', [
            'keys' => array_keys($apiData),
            'has_statistik' => isset($apiData['statistik']),
            'has_daftar_rekap' => isset($apiData['daftar_rekap']),
            'kuesioner_id_target' => $apiKuesionerId
        ]);

        // =========================
        // FILTER KUESIONER SPESIFIK DARI DAFTAR_REKAP
        // =========================
        $targetRekap = null;
        
        if (isset($apiData['daftar_rekap']) && is_array($apiData['daftar_rekap']) && $apiKuesionerId) {
            // 🔥 Cari kuesioner spesifik berdasarkan kuesioner_id
            foreach ($apiData['daftar_rekap'] as $rekap) {
                $metadata = $rekap['metadata'] ?? [];
                if (isset($metadata['kuesioner_id']) && $metadata['kuesioner_id'] == $apiKuesionerId) {
                    $targetRekap = $rekap;
                    break;
                }
            }
            
            if (!$targetRekap) {
                throw new \Exception("Kuesioner dengan ID {$apiKuesionerId} tidak ditemukan dalam daftar_rekap");
            }
            
            \Log::info('Target Kuesioner Found', [
                'kuesioner_id' => $apiKuesionerId,
                'judul' => $targetRekap['metadata']['judul_kuesioner'] ?? 'Unknown'
            ]);
            
            // 🔥 Replace apiData dengan data kuesioner spesifik
            $apiData = [
                'metadata' => $targetRekap['metadata'] ?? [],
                'statistik' => $targetRekap['statistik'] ?? [],
                'data' => $targetRekap['data'] ?? []
            ];
        }

KuesionerMongo::updateOrCreate(

    [
                'kuesioner_id' => $apiKuesionerId ?? $kuesioner->id
    ],

    [
                'judul_kuesioner' => $apiData['metadata']['judul_kuesioner'] ?? 'Kuesioner API',
                'kode_mk' => $kuesioner->kode_matakuliah,
        'periode' => $kuesioner->periode,

        'raw_data' => $apiData,

        'updated_at' => now()
    ]
);
// KuesionerMongo::create([ 'kuesioner_id' => $kuesioner->id, 'kode_mk' => $kuesioner->kode_matakuliah, 'periode' => $kuesioner->periode, 'raw_data' => $apiData, 'created_at' => now() ]);

        // =========================
        // VALIDASI DATA - Support multiple formats
        // =========================
        if (!isset($apiData['statistik']) && !isset($apiData['daftar_rekap'])) {
            \Log::error('Invalid API response', [
                'periode' => $kuesioner->periode,
                'kode_mk' => $kuesioner->kode_matakuliah,
                'response' => $apiData
            ]);
            throw new \Exception('Data API tidak valid - tidak ada statistik atau daftar_rekap');
        }

        // Jika punya daftar_rekap tapi tidak langsung statistik, extract dari rekap
        if (!isset($apiData['statistik']) && isset($apiData['daftar_rekap'])) {
            \Log::info('Using daftar_rekap format - extracting statistik');
            $apiData['statistik'] = $this->extractStatistikFromDaftarRekap($apiData['daftar_rekap']);
        }

        // DOUBLE CHECK - pastikan statistik ada dan tidak kosong
        if (!isset($apiData['statistik']['rekapitulasi']) || empty($apiData['statistik']['rekapitulasi'])) {
            \Log::warning('Statistik rekapitulasi kosong', [
                'periode' => $kuesioner->periode,
                'kode_mk' => $kuesioner->kode_matakuliah,
                'total_responden' => $apiData['statistik']['total_responden_aktif'] ?? 0
            ]);
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
// AMBIL NAMA MATAKULIAH DARI DB
// =========================
$matakuliah = \App\Models\Matakuliah::where('kode_mk', $kuesioner->kode_matakuliah)->first();
$namaMk = $matakuliah ? $matakuliah->nama_mk : $parsed['nama_mk'];

// =========================
// AMBIL NAMA DOSEN DARI DB
// =========================
$namaDosen = null;
if ($parsed['pegawai_id']) {
    $dosen = \App\Models\Dosenn::where('pegawai_id', $parsed['pegawai_id'])->first();
    $namaDosen = $dosen ? $dosen->nama : null;
}

// =========================
// DETEKSI JENIS KUESIONER (UTS/UAS/REGULAR)
// =========================
$jenisKuesioner = $this->detectJenisKuesioner($apiData['metadata']['judul_kuesioner'] ?? '');

        // =========================
        // 4. UPDATE
        // =========================
        $kuesioner->update([
    'nama_file' => $apiData['metadata']['judul_kuesioner'] ?? 'Kuesioner API',

    // 🔥 HASIL PARSING - gunakan data dari DB
    'nama_matakuliah' => $namaMk,
    'jenis_kuesioner' => $jenisKuesioner,
    'tingkat' => $parsed['tingkat'],
    'pegawai_id' => $parsed['pegawai_id'],
    'dosen_pengampu' => $namaDosen,

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

    /**
     * Generate PDF dari halaman laporan analisis
     */
    public function printPdf($id)
    {
        $kuesioner = KuesioneUpload::with(['user', 'user.prodi'])->findOrFail($id);

        if ($kuesioner->status !== 'completed') {
            return back()->withErrors(['error' => 'Analisis belum selesai. Silakan tunggu beberapa saat.']);
        }

        // Pastikan nama_matakuliah terisi
        if (empty($kuesioner->nama_matakuliah) && !empty($kuesioner->kode_matakuliah)) {
            $matkul = \App\Models\Matakuliah::where('kode_mk', $kuesioner->kode_matakuliah)->first();
            $kuesioner->nama_matakuliah = $matkul ? $matkul->nama_mk : null;
        }

        // Load view untuk PDF
        $pdf = Pdf::loadView('gkm.monitoring-kuesioner.report-pdf', compact('kuesioner'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
                'margin_top' => 10,
                'margin_right' => 10,
                'margin_bottom' => 10,
                'margin_left' => 10,
            ]);

        // Generate nama file yang descriptive dengan sanitasi karakter
        $safeFileName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $kuesioner->nama_file);
        $safeFileName = preg_replace('/_+/', '_', $safeFileName); // Remove multiple underscores
        $fileName = 'Laporan_Kuesioner_' . $safeFileName . '_' . date('Y-m-d') . '.pdf';

        return $pdf->download($fileName);
    }
}