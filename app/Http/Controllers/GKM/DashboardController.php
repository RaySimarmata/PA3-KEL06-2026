<?php

namespace App\Http\Controllers\GKM;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\RPS;
use App\Models\Reminder;
use App\Models\Kuisioner;
use App\Models\LaporanBulanan;
use App\Models\LaporanGKM;
use App\Models\PerkuliahanMonitoringDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $semester = $request->input('semester');
        $tahun = $request->input('tahun');

        // Statistik untuk dashboard GKM
        // Status Upload Materi
        $totalMateri = Materi::count();
        $materiUploaded = Materi::where('status', 'sudah_upload')->count();
        $materiBelum = $totalMateri - $materiUploaded;

        // Status RPS
        $totalRPS = RPS::count();
        $rpsLengkap = RPS::where('status_rps', 'sudah_divalidasi')->count();
        $rpsBelum = $totalRPS - $rpsLengkap;

        // Status Reminder
        $remindersPending = Reminder::where('status', 'belum_kirim')->count();

        // Status Kuisioner
        $kuisionerAktif = Kuisioner::where('status', 'aktif')->count();

        // Total laporan kuesioner (LaporanBulanan) and total laporan bulanan/gkm (LaporanGKM)
        $totalQuestionnaires = LaporanBulanan::count();
        $totalMonthlyReports = LaporanGKM::count();

        $stats = [
            'materi_uploaded' => $materiUploaded,
            'materi_belum' => $materiBelum,
            'rps_lengkap' => $rpsLengkap,
            'rps_belum' => $rpsBelum,
            'reminders_pending' => $remindersPending,
            'kuisioner_pengisi' => $kuisionerAktif,
        ];

        $periode = date('F Y');

        $analyticsData = $this->loadAnalyticsData($user, $semester, $tahun);

        return view('gkm.dashboard.index', array_merge(
            compact('user', 'stats', 'periode', 'totalQuestionnaires', 'totalMonthlyReports', 'semester', 'tahun'),
            $analyticsData
        ));
    }

    public function analytics(Request $request)
    {
        $user = Auth::user();
        $semester = $request->input('semester');
        $tahun = $request->input('tahun');
        $dashboardData = $this->loadAnalyticsData($user, $semester, $tahun);

        $periode = date('F Y');

        return view('gkm.dashboard.analytics', array_merge(
            ['user' => $user, 'periode' => $periode, 'semester' => $semester, 'tahun' => $tahun],
            $dashboardData
        ));
    }

    private function loadAnalyticsData($user, $semester = null, $tahun = null)
    {
        $cacheKey = 'gkm_dashboard_analytics_' . ($user->prodi_id ?? 'all') . '_' . ($semester ?? 'all') . '_' . ($tahun ?? 'all');

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($user, $semester, $tahun) {
            $query = PerkuliahanMonitoringDetail::query();

            $prodiKode = $user->prodi ? $user->prodi->kode_prodi : null;

            if (!empty($prodiKode)) {
                $query->where('prodi_kode', $prodiKode);
            } elseif ($user->prodi_id) {
                $query->where('prodi_id', $user->prodi_id);
            }

            if (!empty($semester)) {
                $query->where('semester', $semester);
            }

            if (!empty($tahun)) {
                $query->where('tahun_ajaran', $tahun);
            }

            $details = $query->get();

            $stats = [
                'total_records' => $details->count(),
                'total_mata_kuliah' => $details->pluck('kode_mk')->unique()->count(),
                'total_dosen' => $details->pluck('nama_dosen')->unique()->count(),
                'avg_kepatuhan' => round($details->avg('persentase_kepatuhan') ?? 0, 2),
                'jumlah_terlambat' => $details->sum('jumlah_terlambat'),
                'jumlah_belum_upload' => $details->sum('jumlah_belum_upload'),
                'total_upload' => $details->sum('jumlah_upload'),
            ];

            $groupByTingkat = $details->groupBy('tingkat')->map(function ($items) {
                return round($items->avg('persentase_kepatuhan') ?? 0, 2);
            });

            $trendSemester = $details->groupBy(function ($item) {
                return trim($item->tahun_ajaran) . ' S' . trim($item->semester);
            })->map(function ($items) {
                return round($items->avg('persentase_kepatuhan') ?? 0, 2);
            })->sortKeys();

            $totalDetails = $details->count();
            $statusDistribution = $details->groupBy('status_kepatuhan')->map(function ($items) use ($totalDetails) {
                return $totalDetails ? round(($items->count() / $totalDetails) * 100) : 0;
            });

            $courseGroups = $details->groupBy('kode_mk')->map(function ($items) {
                return [
                    'kode_mk' => $items->first()->kode_mk,
                    'nama_matkul' => $items->first()->nama_matkul,
                    'dosen' => $items->first()->nama_dosen,
                    'avg_kepatuhan' => round($items->avg('persentase_kepatuhan') ?? 0, 2),
                    'total_upload' => $items->sum('jumlah_upload'),
                    'total_belum_upload' => $items->sum('jumlah_belum_upload'),
                    'status_kepatuhan' => $items->first()->status_kepatuhan,
                ];
            });

            $topCourses = $courseGroups->sortByDesc('avg_kepatuhan')->take(5)->values();
            $bottomCourses = $courseGroups->sortBy('avg_kepatuhan')->take(5)->values();

            $listTahun = PerkuliahanMonitoringDetail::select('tahun_ajaran')
                ->distinct()
                ->orderBy('tahun_ajaran')
                ->pluck('tahun_ajaran');

            $listSemester = PerkuliahanMonitoringDetail::select('semester')
                ->distinct()
                ->orderBy('semester')
                ->pluck('semester');

            return compact(
                'details',
                'stats',
                'groupByTingkat',
                'trendSemester',
                'statusDistribution',
                'topCourses',
                'bottomCourses',
                'listTahun',
                'listSemester'
            );
        });
    }
}
