<?php

namespace App\Http\Controllers\API\GKM;

use App\Http\Controllers\Controller;
use App\Models\RPS;
use App\Models\LaporanBulanan;
use App\Models\LaporanGKM;
use App\Models\PerkuliahanMonitoringDetail;
use App\Models\RpsMonitoringSnapshot;
use App\Models\PeriodeAkademik;
use App\Models\HasilAnalisisMongo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardApiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GET /api/gkm/dashboard
    | Query params: semester, tahun
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $user = Auth::user();
        $semester = $request->input('semester');
        $tahun    = $request->input('tahun');

        // Gunakan periode aktif jika filter tidak disertakan
        if (!$request->has('semester') && !$request->has('tahun')) {
            $periodeAktif = PeriodeAkademik::where('is_active', true)->first();
            if ($periodeAktif) {
                $semester = $periodeAktif->semester;
                $tahun    = $periodeAktif->tahun_ajaran;
            }
        }

        /*
        |----------------------------------------------------------------------
        | MATERI
        |----------------------------------------------------------------------
        */
        $materiQuery = PerkuliahanMonitoringDetail::query();
        if ($user->prodi_id) {
            $materiQuery->where('prodi_id', $user->prodi_id);
        }
        if (!empty($semester)) $materiQuery->where('semester', $semester);
        if (!empty($tahun))    $materiQuery->where('tahun_ajaran', $tahun);

        $totalMateri    = $materiQuery->count();
        $materiUploaded = (clone $materiQuery)->where('jumlah_upload', '>', 0)->count();
        $materiBelum    = max(0, $totalMateri - $materiUploaded);

        /*
        |----------------------------------------------------------------------
        | RPS
        |----------------------------------------------------------------------
        */
        $totalRPS   = 0;
        $rpsLengkap = 0;
        $rpsBelum   = 0;
        if (Schema::hasTable('rps')) {
            $totalRPS   = RPS::count();
            $rpsLengkap = RPS::where('status_rps', 'sudah_divalidasi')->count();
            $rpsBelum   = $totalRPS - $rpsLengkap;
        }

        /*
        |----------------------------------------------------------------------
        | COMPLIANCE
        |----------------------------------------------------------------------
        */
        $avgKepatuhan = $this->getCombinedCompliancePercent($user, $semester, $tahun);
        $kepuasanProdi = $this->getKepuasanProdiValue($user, $semester, $tahun);

        /*
        |----------------------------------------------------------------------
        | ANALYTICS (dari cache jika ada)
        |----------------------------------------------------------------------
        */
        $cacheKey = 'gkm_dashboard_analytics_'
            . ($user->prodi_id ?? 'all') . '_'
            . ($semester ?? 'all') . '_'
            . ($tahun ?? 'all');

        $analytics = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($user, $semester, $tahun) {
            return $this->buildAnalytics($user, $semester, $tahun);
        });

        return response()->json([
            'success' => true,
            'periode' => [
                'semester'    => $semester,
                'tahun_ajaran' => $tahun,
            ],
            'stats' => [
                'materi_uploaded'   => $materiUploaded,
                'materi_belum'      => $materiBelum,
                'rps_lengkap'       => $rpsLengkap,
                'rps_belum'         => $rpsBelum,
                'avg_kepatuhan'     => $avgKepatuhan,
                'kepuasan_prodi'    => $kepuasanProdi,
                'total_laporan_kuesioner' => Schema::hasTable((new LaporanBulanan)->getTable()) ? LaporanBulanan::count() : 0,
                'total_laporan_bulanan'   => Schema::hasTable((new LaporanGKM)->getTable()) ? LaporanGKM::count() : 0,
            ],
            'analytics'    => $analytics,
            'list_tahun'   => $analytics['listTahun'] ?? [],
            'list_semester'=> $analytics['listSemester'] ?? [],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/gkm/dashboard/analytics
    |--------------------------------------------------------------------------
    */
    public function analytics(Request $request)
    {
        $user     = Auth::user();
        $semester = $request->input('semester');
        $tahun    = $request->input('tahun');

        $data = $this->buildAnalytics($user, $semester, $tahun);

        return response()->json([
            'success'  => true,
            'semester' => $semester,
            'tahun'    => $tahun,
            'data'     => $data,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVATE HELPERS
    |--------------------------------------------------------------------------
    */
    private function buildAnalytics($user, $semester = null, $tahun = null): array
    {
        $query = PerkuliahanMonitoringDetail::query();
        $prodiKode = $user->prodi ? $user->prodi->kode_prodi : null;

        if (!empty($prodiKode)) $query->where('prodi_kode', $prodiKode);
        elseif ($user->prodi_id) $query->where('prodi_id', $user->prodi_id);
        if (!empty($semester)) $query->where('semester', $semester);
        if (!empty($tahun))    $query->where('tahun_ajaran', $tahun);

        $details = $query->get();

        $analyticsStats = [
            'total_records'       => $details->count(),
            'total_mata_kuliah'   => $details->pluck('kode_mk')->unique()->count(),
            'total_dosen'         => $details->pluck('nama_dosen')->unique()->count(),
            'avg_kepatuhan'       => $this->getCombinedCompliancePercent($user, $semester, $tahun),
            'jumlah_terlambat'    => $details->sum('jumlah_terlambat'),
            'jumlah_belum_upload' => $details->sum('jumlah_belum_upload'),
            'total_upload'        => $details->sum('jumlah_upload'),
        ];

        $groupByTingkat = $details->groupBy('tingkat')->map(function ($items) {
            return round($items->avg('persentase_kepatuhan') ?? 0, 2);
        });

        $trendSemester = $details->groupBy('semester')->map(function ($items) {
            return round($items->avg('persentase_kepatuhan') ?? 0, 2);
        })->sortKeys();

        $totalDetails      = $details->count();
        $statusDistribution = $details->groupBy('status_kepatuhan')->map(function ($items) use ($totalDetails) {
            return $totalDetails ? round(($items->count() / $totalDetails) * 100) : 0;
        });

        $courseGroups = $details->groupBy('kode_mk')->map(function ($items) {
            return [
                'kode_mk'          => $items->first()->kode_mk,
                'nama_matkul'      => $items->first()->nama_matkul,
                'dosen'            => $items->first()->nama_dosen,
                'tingkat'          => $items->first()->tingkat,
                'avg_kepatuhan'    => round($items->avg('persentase_kepatuhan') ?? 0, 2),
                'total_upload'     => $items->sum('jumlah_upload'),
                'total_belum_upload' => $items->sum('jumlah_belum_upload'),
                'status_kepatuhan' => $items->first()->status_kepatuhan,
            ];
        });

        $topCourses    = $courseGroups->sortByDesc('avg_kepatuhan')->take(5)->values();
        $bottomCourses = $courseGroups->sortBy('avg_kepatuhan')->take(5)->values();

        $listTahun = PerkuliahanMonitoringDetail::select('tahun_ajaran')
            ->distinct()->orderBy('tahun_ajaran')->pluck('tahun_ajaran');
        $listSemester = PerkuliahanMonitoringDetail::select('semester')
            ->distinct()->orderBy('semester')->pluck('semester');

        return [
            'analyticsStats'     => $analyticsStats,
            'groupByTingkat'     => $groupByTingkat,
            'trendSemester'      => $trendSemester,
            'statusDistribution' => $statusDistribution,
            'topCourses'         => $topCourses,
            'bottomCourses'      => $bottomCourses,
            'listTahun'          => $listTahun,
            'listSemester'       => $listSemester,
        ];
    }

    private function getKepuasanProdiValue($user, $semester = null, $tahun = null): float
    {
        $prodiKode = $user->prodi ? $user->prodi->kode_prodi : null;
        $q = HasilAnalisisMongo::query();
        if (!empty($prodiKode)) $q->where('prodi.kode', $prodiKode);
        if (!empty($semester))  $q->where('semester', (int) $semester);
        if (!empty($tahun))     $q->where('tahun', (string) $tahun);
        return round($q->avg('persentase_kepuasan') ?? 0, 2);
    }

    private function getCombinedCompliancePercent($user, $semester = null, $tahun = null): float
    {
        $materi = $this->getMateriCompliancePercent($user, $semester, $tahun);
        $rps    = $this->getRpsCompliancePercent($user, $semester, $tahun);
        $sources = [];
        if ($materi['records'] > 0) $sources[] = $materi['percentage'];
        if ($rps['records'] > 0)    $sources[] = $rps['percentage'];
        return empty($sources) ? 0 : round(array_sum($sources) / count($sources), 2);
    }

    private function getMateriCompliancePercent($user, $semester = null, $tahun = null): array
    {
        $q = PerkuliahanMonitoringDetail::query();
        $prodiKode = $user->prodi ? $user->prodi->kode_prodi : null;
        if (!empty($prodiKode)) $q->where('prodi_kode', $prodiKode);
        elseif ($user->prodi_id) $q->where('prodi_id', $user->prodi_id);
        if (!empty($semester)) $q->where('semester', $semester);
        if (!empty($tahun))    $q->where('tahun_ajaran', $tahun);
        $details = $q->get();
        return [
            'records'    => $details->count(),
            'percentage' => round($details->avg('persentase_kepatuhan') ?? 0, 2),
        ];
    }

    private function getRpsCompliancePercent($user, $semester = null, $tahun = null): array
    {
        $q = RpsMonitoringSnapshot::query();
        $prodiKode = $user->prodi ? $user->prodi->kode_prodi : null;
        if (!empty($prodiKode)) $q->where('prodi_kode', $prodiKode);
        elseif ($user->prodi_id) $q->where('prodi_id', $user->prodi_id);
        if (!empty($semester)) $q->where('semester', (int) $semester);
        if (!empty($tahun))    $q->where('tahun_ajaran', (string) $tahun);
        $snapshots = $q->get();
        $courseKey = fn($s) => trim((string)($s->kuliah_id ?? $s->kode_mk ?? ''));
        $totalRecords = $snapshots->map($courseKey)->filter()->unique()->count();
        $uploaded = $snapshots->filter(function ($s) {
            return in_array(strtoupper($s->status_rps ?? ''), ['SUDAH UPLOAD', 'SUDAH DIVALIDASI', 'UPLOAD']);
        })->map($courseKey)->filter()->unique()->count();
        return [
            'records'    => $totalRecords,
            'percentage' => $totalRecords > 0 ? round(($uploaded / $totalRecords) * 100, 2) : 0,
        ];
    }
}
