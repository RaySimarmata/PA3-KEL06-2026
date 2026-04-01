<?php

namespace App\Http\Controllers\GKM;

use App\Http\Controllers\Controller;
use App\Models\KuesioneUpload;
use App\Models\Prodi;
use App\Services\AIAgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MonitoringKuesioneController extends Controller
{
    protected $aiAgent;

    public function __construct(AIAgentService $aiAgent)
    {
        $this->aiAgent = $aiAgent;
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

    public function create()
    {
        // Generate periode dropdown (1 tahun sebelum sampai 1 tahun sesudah)
        $currentYear = date('Y');
        $periodes = [];

        for ($year = $currentYear - 1; $year <= $currentYear + 1; $year++) {
            $periodes[] = $year . '/' . ($year + 1) . ' Ganjil';
            $periodes[] = $year . '/' . ($year + 1) . ' Genap';
        }

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

        try {
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
