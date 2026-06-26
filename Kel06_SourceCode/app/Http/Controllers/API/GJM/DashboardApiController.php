<?php

namespace App\Http\Controllers\API\GJM;

use App\Http\Controllers\Controller;
use App\Models\HasilAnalisisMongo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardApiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GET /api/gjm/dashboard
    | Query params: tahun, semester, prodi
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $user     = Auth::user();
        $tahun    = $request->input('tahun');
        $semester = $request->input('semester');
        $prodi    = $request->input('prodi');

        $cacheKey = 'api_dashboard_gjm_' . md5(
            ($tahun ?? 'all') . '_' .
            ($semester ?? 'all') . '_' .
            ($prodi ?? 'all')
        );

        if (Cache::has($cacheKey)) {
            return response()->json(array_merge(
                ['success' => true, 'from_cache' => true],
                Cache::get($cacheKey)
            ));
        }

        $query = HasilAnalisisMongo::query();
        if (!empty($tahun))    $query->where('tahun', (string) $tahun);
        if (!empty($semester)) $query->where('semester', (int) $semester);
        if (!empty($prodi) && $prodi !== 'SEMUA') {
            $query->where('prodi.kode', $prodi);
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada data untuk filter yang dipilih.',
                'stats'   => [],
                'data'    => [],
            ]);
        }

        /*
        |----------------------------------------------------------------------
        | KPI
        |----------------------------------------------------------------------
        */
        $stats = [
            'total_prodi'       => $data->pluck('prodi')->pluck('kode')->unique()->count(),
            'total_analisis'    => $data->count(),
            'total_kuesioner'   => $data->pluck('judul_kuesioner')->unique()->count(),
            'dosen_dipantau'    => $data->pluck('dosen_pengajar')->unique()->count(),
            'rata_fakultas'     => round($data->avg('rata_rata'), 2),
            'total_responden'   => $data->sum('total_suara'),
            'kepuasan_mahasiswa'=> round($data->avg('persentase_kepuasan'), 2),
            'matkul_bermasalah' => $data->groupBy('kode_mk')->filter(function ($items) {
                return collect($items)->avg('rata_rata') < 2.5;
            })->count(),
        ];

        /*
        |----------------------------------------------------------------------
        | TREND SEMESTER
        |----------------------------------------------------------------------
        */
        $trendSemester = $data->groupBy(function ($item) {
            return $item->tahun . '-S' . $item->semester;
        })->map(function ($items) {
            return round(collect($items)->avg('rata_rata'), 2);
        })->sortKeys();

        /*
        |----------------------------------------------------------------------
        | PERFORMA PRODI
        |----------------------------------------------------------------------
        */
        $performaProdi = [];
        foreach ($data as $item) {
            $prodiData = $item->prodi ?? null;
            if (is_object($prodiData))             $prodiData = [(array) $prodiData];
            elseif (is_array($prodiData) && isset($prodiData['kode'])) $prodiData = [$prodiData];
            elseif (is_string($prodiData))         $prodiData = [['kode' => $prodiData]];

            foreach ($prodiData ?? [] as $prodiItem) {
                $kode = $prodiItem['kode'] ?? null;
                if (!$kode) continue;
                if (!isset($performaProdi[$kode])) {
                    $performaProdi[$kode] = ['nilai' => [], 'dosen' => [], 'matkul' => [], 'responden' => 0];
                }
                $performaProdi[$kode]['nilai'][]   = $item->rata_rata;
                $performaProdi[$kode]['dosen'][]   = $item->dosen_pengajar;
                $performaProdi[$kode]['matkul'][]  = $item->kode_mk;
                $performaProdi[$kode]['responden'] += $item->total_suara;
            }
        }

        $performaProdi = collect($performaProdi)->map(function ($item) {
            $avg = collect($item['nilai'])->avg();
            return [
                'avg'           => round($avg, 2),
                'jumlah_dosen'  => collect($item['dosen'])->unique()->count(),
                'jumlah_matkul' => collect($item['matkul'])->unique()->count(),
                'responden'     => $item['responden'],
                'kategori'      => $avg >= 3.25 ? 'Sangat Baik' : ($avg >= 2.75 ? 'Baik' : ($avg >= 2 ? 'Cukup' : 'Kurang')),
            ];
        });

        /*
        |----------------------------------------------------------------------
        | RANKING DOSEN
        |----------------------------------------------------------------------
        */
        $rankingDosen = $data->groupBy('dosen_pengajar')->map(function ($items, $nama) {
            $avg      = collect($items)->avg('rata_rata');
            $kepuasan = collect($items)->avg('persentase_kepuasan');
            return [
                'nama'             => $nama,
                'avg'              => round($avg, 2),
                'kepuasan'         => round($kepuasan, 2),
                'jumlah_matkul'    => collect($items)->pluck('kode_mk')->unique()->count(),
                'jumlah_responden' => collect($items)->sum('total_suara'),
                'kategori'         => $avg >= 3.25 ? 'Sangat Baik' : ($avg >= 2.75 ? 'Baik' : ($avg >= 2 ? 'Cukup' : 'Kurang')),
            ];
        });

        $topDosen        = $rankingDosen->sortByDesc('avg')->take(5)->values();
        $bottomDosen     = $rankingDosen->sortBy('avg')->take(5)->values();
        $dosenBermasalah = $rankingDosen->filter(fn($i) => $i['avg'] < 2.75 || $i['kepuasan'] < 70)->sortBy('avg')->values();

        /*
        |----------------------------------------------------------------------
        | MATKUL BERMASALAH
        |----------------------------------------------------------------------
        */
        $matkulBermasalah = $data->groupBy('kode_mk')->map(function ($items) {
            $first = collect($items)->first();
            return [
                'kode_mk' => $first->kode_mk,
                'judul'   => $first->judul_kuesioner,
                'dosen'   => $first->dosen_pengajar,
                'avg'     => round(collect($items)->avg('rata_rata'), 2),
            ];
        })->sortBy('avg')->take(10)->values();

        /*
        |----------------------------------------------------------------------
        | PERTANYAAN TERBURUK
        |----------------------------------------------------------------------
        */
        $pertanyaanTerburuk = $data->groupBy('pertanyaan')->map(function ($items, $pertanyaan) {
            return [
                'pertanyaan' => strip_tags($pertanyaan),
                'avg'        => round(collect($items)->avg('rata_rata'), 2),
            ];
        })->sortBy('avg')->take(10)->values();

        /*
        |----------------------------------------------------------------------
        | DISTRIBUSI KATEGORI
        |----------------------------------------------------------------------
        */
        $totalData = $data->count();
        $kategoriDistribusi = [
            'Sangat Baik' => $totalData ? round(($data->where('kategori_hasil', 'Sangat Baik')->count() / $totalData) * 100) : 0,
            'Baik'        => $totalData ? round(($data->where('kategori_hasil', 'Baik')->count() / $totalData) * 100) : 0,
            'Cukup'       => $totalData ? round(($data->where('kategori_hasil', 'Cukup')->count() / $totalData) * 100) : 0,
            'Kurang'      => $totalData ? round(($data->where('kategori_hasil', 'Kurang')->count() / $totalData) * 100) : 0,
        ];

        /*
        |----------------------------------------------------------------------
        | FILTER LISTS
        |----------------------------------------------------------------------
        */
        $listTahun = Cache::remember('filter_list_tahun', now()->addHours(24), function () {
            return HasilAnalisisMongo::query()->pluck('tahun')->unique()->sort()->values();
        });

        $listProdi = Cache::remember('filter_list_prodi', now()->addHours(24), function () {
            return HasilAnalisisMongo::query()->pluck('prodi.kode')->filter()->unique()->sort()->values();
        });

        $payload = [
            'periode'            => date('F Y'),
            'filter_aktif'       => ['tahun' => $tahun, 'semester' => $semester, 'prodi' => $prodi],
            'stats'              => $stats,
            'trend_semester'     => $trendSemester,
            'performa_prodi'     => $performaProdi,
            'chart_prodi_labels' => $performaProdi->keys()->values(),
            'chart_prodi_data'   => $performaProdi->pluck('avg')->values(),
            'top_dosen'          => $topDosen,
            'bottom_dosen'       => $bottomDosen,
            'dosen_bermasalah'   => $dosenBermasalah,
            'matkul_bermasalah'  => $matkulBermasalah,
            'pertanyaan_terburuk'=> $pertanyaanTerburuk,
            'kategori_distribusi'=> $kategoriDistribusi,
            'list_tahun'         => $listTahun,
            'list_semester'      => [1, 2],
            'list_prodi'         => $listProdi,
        ];

        Cache::put($cacheKey, $payload, now()->addMinutes(60));

        return response()->json(array_merge(
            ['success' => true, 'from_cache' => false],
            $payload
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/gjm/dashboard/clear-cache
    |--------------------------------------------------------------------------
    */
    public function clearCache()
    {
        Cache::forget('filter_list_tahun');
        Cache::forget('filter_list_prodi');
        return response()->json(['success' => true, 'message' => 'Cache berhasil dihapus.']);
    }
}
