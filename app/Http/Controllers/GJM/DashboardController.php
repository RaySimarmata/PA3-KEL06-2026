<?php

namespace App\Http\Controllers\GJM;

use App\Http\Controllers\Controller;
use App\Models\HasilAnalisisMongo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | DASHBOARD GJM
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
{
    set_time_limit(300);
    $user = Auth::user();

    $tahun = $request->tahun;
    $semester = $request->semester;
    $prodi = $request->prodi;

    $query = HasilAnalisisMongo::query();

    if (!empty($tahun)) {
        $query->where('tahun', (string) $tahun);
    }

    if (!empty($semester)) {
        $query->where('semester', (int) $semester);
    }

    /*
    | FIX FILTER PRODI (OBJECT BUKAN ARRAY)
    */
    if (!empty($prodi) && $prodi !== 'SEMUA') {
        $query->where('prodi.kode', $prodi);
    }

    $data = $query->get();

    if ($data->isEmpty()) {
        return view('gjm.dashboard.index', [
            'user' => $user,
            'periode' => date('F Y'),
            'stats' => [],
            'data' => collect(),
            'trendSemester' => collect(),
            'performaProdi' => collect(),
            'heatmapDosen' => collect(),
            'topDosen' => collect(),
            'bottomDosen' => collect(),
            'dosenBermasalah' => collect(),
            'matkulBermasalah' => collect(),
            'pertanyaanTerburuk' => collect(),
            'kategoriDistribusi' => [],
            'insight' => [],
            'chartProdiLabel' => [],
            'chartProdiData' => [],
            'listTahun' => [],
            'listSemester' => [1, 2],
            'listProdi' => [],
        ]);
    }

    /*
    | KPI
    */
    $stats = [
        'total_prodi' => $data->pluck('prodi')->pluck('kode')->unique()->count(),
        'total_analisis' => $data->count(),
        'total_kuesioner' => $data->pluck('judul_kuesioner')->unique()->count(),
        'dosen_dipantau' => $data->pluck('dosen_pengajar')->unique()->count(),
        'rata_fakultas' => round($data->avg('rata_rata'), 2),
        'total_responden' => $data->sum('total_suara'),
        'kepuasan_mahasiswa' => round($data->avg('persentase_kepuasan'), 2),

        'matkul_bermasalah' => $data->groupBy('kode_mk')->filter(function ($items) {
            return collect($items)->avg('rata_rata') < 2.5;
        })->count(),
    ];

    /*
    | TREND
    */
    $trendSemester = $data->groupBy(function ($item) {
        return $item->tahun . '-S' . $item->semester;
    })->map(function ($items) {
        return round(collect($items)->avg('rata_rata'), 2);
    })->sortKeys();

    /*
    | PERFORMA PRODI (FIX OBJECT SAFE)
    */
    $performaProdi = [];

    foreach ($data as $item) {

        $prodiData = $item->prodi ?? null;

        // NORMALISASI OBJECT → ARRAY
        if (is_object($prodiData)) {
            $prodiData = [(array) $prodiData];
        } elseif (is_array($prodiData) && isset($prodiData['kode'])) {
            $prodiData = [$prodiData];
        } elseif (is_string($prodiData)) {
            $prodiData = [['kode' => $prodiData]];
        }

        foreach ($prodiData ?? [] as $prodiItem) {

            $kode = $prodiItem['kode'] ?? null;
            if (!$kode) continue;

            if (!isset($performaProdi[$kode])) {
                $performaProdi[$kode] = [
                    'nilai' => [],
                    'dosen' => [],
                    'matkul' => [],
                    'responden' => 0,
                ];
            }

            $performaProdi[$kode]['nilai'][] = $item->rata_rata;
            $performaProdi[$kode]['dosen'][] = $item->dosen_pengajar;
            $performaProdi[$kode]['matkul'][] = $item->kode_mk;
            $performaProdi[$kode]['responden'] += $item->total_suara;
        }
    }

    $performaProdi = collect($performaProdi)->map(function ($item) {

        $avg = collect($item['nilai'])->avg();

        return [
            'avg' => round($avg, 2),
            'jumlah_dosen' => collect($item['dosen'])->unique()->count(),
            'jumlah_matkul' => collect($item['matkul'])->unique()->count(),
            'responden' => $item['responden'],
            'kategori' =>
                $avg >= 3.25 ? 'Sangat Baik' :
                ($avg >= 2.75 ? 'Baik' :
                ($avg >= 2 ? 'Cukup' : 'Kurang')),
        ];
    });

    $chartProdiLabel = $performaProdi->keys()->values()->toArray();
    $chartProdiData = $performaProdi->pluck('avg')->values()->toArray();

    /*
    | HEATMAP DOSEN (FIX OBJECT SAFE)
    */
    $heatmapDosen = [];

    foreach ($data as $item) {

        $dosen = $item->dosen_pengajar;

        $prodiData = $item->prodi ?? null;

        if (is_object($prodiData)) {
            $prodiData = [(array) $prodiData];
        }

        foreach ($prodiData ?? [] as $prodiItem) {

            $kode = $prodiItem['kode'] ?? null;
            if (!$kode) continue;

            $heatmapDosen[$dosen][$kode][] = $item->rata_rata;
        }
    }

    $heatmapDosen = collect($heatmapDosen)->map(function ($prodis) {
        return collect($prodis)->map(function ($nilai) {
            return round(collect($nilai)->avg(), 2);
        });
    });

    /*
    | TOP DOSEN
    */
    $rankingDosen = $data->groupBy('dosen_pengajar')->map(function ($items, $nama) {

        $avg = collect($items)->avg('rata_rata');
        $kepuasan = collect($items)->avg('persentase_kepuasan');

        return [
            'nama' => $nama,
            'avg' => round($avg, 2),
            'kepuasan' => round($kepuasan, 2),
            'jumlah_matkul' => collect($items)->pluck('kode_mk')->unique()->count(),
            'jumlah_responden' => collect($items)->sum('total_suara'),
            'kategori' =>
                $avg >= 3.25 ? 'Sangat Baik' :
                ($avg >= 2.75 ? 'Baik' :
                ($avg >= 2 ? 'Cukup' : 'Kurang')),
        ];
    });

    $topDosen = $rankingDosen->sortByDesc('avg')->take(5)->values();
    $bottomDosen = $rankingDosen->sortBy('avg')->take(5)->values();

    /*
    | DOSEN BERMASALAH
    */
    $dosenBermasalah = $rankingDosen->filter(function ($item) {
        return $item['avg'] < 2.75 || $item['kepuasan'] < 70;
    })->sortBy('avg')->values();

    /*
    | MATKUL BERMASALAH
    */
    $matkulBermasalah = $data->groupBy('kode_mk')->map(function ($items) {

        $first = collect($items)->first();

        return [
            'kode_mk' => $first->kode_mk,
            'judul' => $first->judul_kuesioner,
            'dosen' => $first->dosen_pengajar,
            'avg' => round(collect($items)->avg('rata_rata'), 2),
        ];
    })->sortBy('avg')->take(10)->values();

    /*
    | PERTANYAAN
    */
    $pertanyaanTerburuk = $data->groupBy('pertanyaan')->map(function ($items, $pertanyaan) {

        return [
            'pertanyaan' => strip_tags($pertanyaan),
            'avg' => round(collect($items)->avg('rata_rata'), 2),
        ];
    })->sortBy('avg')->take(10)->values();

    /*
    | DISTRIBUSI
    */
    $totalData = $data->count();

    $kategoriDistribusi = [
        'Sangat Baik' => $totalData ? round(($data->where('kategori_hasil', 'Sangat Baik')->count() / $totalData) * 100) : 0,
        'Baik' => $totalData ? round(($data->where('kategori_hasil', 'Baik')->count() / $totalData) * 100) : 0,
        'Cukup' => $totalData ? round(($data->where('kategori_hasil', 'Cukup')->count() / $totalData) * 100) : 0,
        'Kurang' => $totalData ? round(($data->where('kategori_hasil', 'Kurang')->count() / $totalData) * 100) : 0,
    ];

    /*
    | INSIGHT
    */
    $insight = [];

    $prodiTerendah = $performaProdi->sortBy('avg')->first();
    $namaProdiTerendah = $performaProdi->sortBy('avg')->keys()->first();

    if ($prodiTerendah) {
        $insight[] = "Prodi {$namaProdiTerendah} memiliki performa terendah dengan rata-rata {$prodiTerendah['avg']}.";
    }

    if ($dosenBermasalah->count() > 0) {
        $insight[] = $dosenBermasalah->count() . " dosen memerlukan evaluasi.";
    }

    if ($stats['matkul_bermasalah'] > 0) {
        $insight[] = $stats['matkul_bermasalah'] . " mata kuliah memiliki performa rendah.";
    }

    /*
    | FILTER LIST
    */
    $listTahun = HasilAnalisisMongo::query()->pluck('tahun')->unique()->sort()->values();

    $listSemester = [1, 2];

    $listProdi = HasilAnalisisMongo::query()
    ->pluck('prodi.kode')
    ->filter()
    ->unique()
    ->sort()
    ->values();

    $periode = date('F Y');

    return view('gjm.dashboard.index', compact(
        'user',
        'periode',
        'stats',
        'data',
        'trendSemester',
        'performaProdi',
        'heatmapDosen',
        'topDosen',
        'bottomDosen',
        'dosenBermasalah',
        'matkulBermasalah',
        'pertanyaanTerburuk',
        'kategoriDistribusi',
        'insight',
        'chartProdiLabel',
        'chartProdiData',
        'listTahun',
        'listSemester',
        'listProdi'
    ));
}
    /*
    |--------------------------------------------------------------------------
    | JALANKAN ANALISIS SPARK
    |--------------------------------------------------------------------------
    */
    public function Analisis()
    {
        try {

            $spark =
                'C:\spark\bin\spark-submit.cmd';

            $python =
                'C:\Python313\python.exe';

            $pythonFile =
                base_path(
                    'spark/spark_kuesioner.py'
                );

            putenv(
                "PYSPARK_PYTHON={$python}"
            );

            putenv(
                "PYSPARK_DRIVER_PYTHON={$python}"
            );

            $command =
                "\"{$spark}\" " .

                "--conf spark.pyspark.python=\"{$python}\" " .

                "--conf spark.pyspark.driver.python=\"{$python}\" " .

                "--packages org.mongodb.spark:mongo-spark-connector_2.12:10.3.0 " .

                "\"{$pythonFile}\" 2>&1";

            $output = [];

            $returnVar = 0;

            exec(
                $command,
                $output,
                $returnVar
            );

            \Log::info(
                'SPARK OUTPUT',
                $output
            );

            if ($returnVar !== 0) {

                return back()->with(
                    'error',
                    implode(
                        "\n",
                        $output
                    )
                );
            }

            return back()->with(
                'success',
                'Analisis Spark berhasil dijalankan'
            );

        } catch (\Exception $e) {

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }
}
