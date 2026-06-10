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
use App\Models\RpsMonitoringSnapshot;
use App\Http\Controllers\GKM\DataMasterApiController;
use App\Models\PeriodeAkademik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\HasilAnalisisMongo;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $semester = $request->input('semester');
        $tahun = $request->input('tahun');

        // gunakan periode akademik aktif hanya bila filter sama sekali tidak dipilih
        $periodeAktif = PeriodeAkademik::where('is_active', true)->first();
        if (!$request->has('semester') && !$request->has('tahun')) {
            if ($periodeAktif) {
                $semester = $periodeAktif->semester;
                $tahun = $periodeAktif->tahun_ajaran;
            }
        }

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
        $kepuasanProdi = $this->getKepuasanProdiValue($user, $semester, $tahun);
        $avgKepatuhan = $this->getCombinedCompliancePercent($user, $semester, $tahun);

        // Ambil daftar matakuliah dari DataMasterApiController supaya totals sinkron
        $totalMatakuliahFromMaster = null;
        $totalDosenFromMaster = null;
        $totalMahasiswaFromMaster = null;

        try {
            if (!empty($semester) && !empty($tahun)) {
                $dmController = new DataMasterApiController();
                $dmRequest = new Request([ 'ta' => $tahun, 'semester' => $semester, 'tingkat' => '' ]);
                $dmView = $dmController->Dmatakuliah($dmRequest);

                if (is_object($dmView) && method_exists($dmView, 'getData')) {
                $dmData = $dmView->getData();
                $matakuliahList = $dmData['matakuliahList'] ?? [];

                $totalMatakuliahFromMaster = is_array($matakuliahList) ? count($matakuliahList) : 0;

                // total dosen: hitung jumlah dosen unik berdasarkan jadwal_dosen untuk daftar matakuliah yang sama
                $kodeMkList = collect($matakuliahList)
                    ->map(function ($m) {
                        return strtoupper(trim($m['kode_mk'] ?? ''));
                    })
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if (!empty($kodeMkList)) {
                    $totalDosenFromMaster = DB::table('jadwal_dosen')
                        ->whereIn(DB::raw('UPPER(TRIM(kode_mk))'), $kodeMkList)
                        ->where('semester', $semester)
                        ->where('tahun_ajaran', $tahun)
                        ->whereNotNull('pegawai_id')
                        ->distinct()
                        ->count('pegawai_id');
                }

                // total mahasiswa: try common keys if present
                $totalMahasiswaFromMaster = collect($matakuliahList)
                    ->map(function ($m) {
                        return $m['jumlah_mahasiswa'] ?? $m['mahasiswa_count'] ?? $m['students'] ?? 0;
                    })->sum();
                }
            }
        } catch (\Exception $e) {
            // fallback ke nilai 0 bila gagal
            $totalMatakuliahFromMaster = 0;
            $totalDosenFromMaster = 0;
            $totalMahasiswaFromMaster = 0;
        }

        // Analytics data
        $cacheKey = 'gkm_dashboard_analytics_' . ($user->prodi_id ?? 'all') . '_' . ($semester ?? 'all') . '_' . ($tahun ?? 'all');

        $dashboardData = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($user, $semester, $tahun) {
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

            $analyticsStats = [
                'total_records' => $details->count(),
                'total_mata_kuliah' => $details->pluck('kode_mk')->unique()->count(),
                'total_dosen' => $details->pluck('nama_dosen')->unique()->count(),
                'avg_kepatuhan' => $this->getCombinedCompliancePercent($user, $semester, $tahun),
                'jumlah_terlambat' => $details->sum('jumlah_terlambat'),
                'jumlah_belum_upload' => $details->sum('jumlah_belum_upload'),
                'total_upload' => $details->sum('jumlah_upload'),
            ];

            $groupByTingkat = $details->groupBy('tingkat')->map(function ($items) {
                return round($items->avg('persentase_kepatuhan') ?? 0, 2);
            });

            $kepuasanQuery = HasilAnalisisMongo::query();
            if (!empty($prodiKode)) {
                $kepuasanQuery->where('prodi.kode', $prodiKode);
            }
            if (!empty($semester)) {
                $kepuasanQuery->where('semester', (int) $semester);
            }
            if (!empty($tahun)) {
                $kepuasanQuery->where('tahun', (string) $tahun);
            }
            $kepuasanDetails = $kepuasanQuery->get();
            $avgKepuasanPerTingkat = $kepuasanDetails->groupBy('tingkat')->map(function ($items) {
                return round($items->avg(function ($item) {
                    if (isset($item->persentase_kepuasan)) {
                        return $item->persentase_kepuasan;
                    }
                    if (isset($item->index_kepuasan)) {
                        return ($item->index_kepuasan / 4) * 100;
                    }
                    return 0;
                }) ?? 0, 2);
            })->sortKeys();

            // Group by semester only (Semester 1 and Semester 2)
            $trendSemester = $details->groupBy('semester')->map(function ($items, $key) {
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
                    'tingkat' => $items->first()->tingkat,
                    'avg_kepatuhan' => round($items->avg('persentase_kepatuhan') ?? 0, 2),
                    'total_upload' => $items->sum('jumlah_upload'),
                    'total_belum_upload' => $items->sum('jumlah_belum_upload'),
                    'status_kepatuhan' => $items->first()->status_kepatuhan,
                ];
            });

            $topCourses = $courseGroups->sortByDesc('avg_kepatuhan')->take(5)->values();
            $bottomCourses = $courseGroups->sortBy('avg_kepatuhan')->take(5)->values();

            $matakuliahDetails = $courseGroups->map(function ($course) {
                return [
                    'nama_matkul' => $course['nama_matkul'],
                    'avg_kepatuhan' => $course['avg_kepatuhan'],
                    'dosen' => is_array($course['dosen']) ? $course['dosen'] : [$course['dosen']],
                    'tingkat' => $course['tingkat'],
                ];
            })->values();

            // Group by Mata Kuliah for horizontal bar chart
            $groupByMatakuliah = $matakuliahDetails->mapWithKeys(function ($course) {
                return [$course['nama_matkul'] => $course['avg_kepatuhan']];
            })->sortByDesc(function ($val) {
                return $val;
            });

            // Map dosen per mata kuliah
            $dosenPerMatakuliah = $matakuliahDetails->mapWithKeys(function ($course) {
                return [$course['nama_matkul'] => $course['dosen']];
            });

            $listTahun = PerkuliahanMonitoringDetail::select('tahun_ajaran')
                ->distinct()
                ->orderBy('tahun_ajaran')
                ->pluck('tahun_ajaran');

            $listSemester = PerkuliahanMonitoringDetail::select('semester')
                ->distinct()
                ->orderBy('semester')
                ->pluck('semester');

            return compact(
                'analyticsStats',
                'groupByTingkat',
                'avgKepuasanPerTingkat',
                'groupByMatakuliah',
                'dosenPerMatakuliah',
                'matakuliahDetails',
                'trendSemester',
                'statusDistribution',
                'topCourses',
                'bottomCourses',
                'listTahun',
                'listSemester'
            );
        });

        return view('gkm.dashboard.index', array_merge(
            compact('user', 'stats', 'periode', 'totalQuestionnaires', 'totalMonthlyReports', 'semester', 'tahun', 'kepuasanProdi', 'avgKepatuhan', 'totalMatakuliahFromMaster', 'totalDosenFromMaster', 'totalMahasiswaFromMaster'),
            $dashboardData
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

    private function getKepuasanProdiValue($user, $semester = null, $tahun = null)
    {
        $prodiKode = $user->prodi ? $user->prodi->kode_prodi : null;

        $kepuasanQuery = HasilAnalisisMongo::query();

        if (!empty($prodiKode)) {
            $kepuasanQuery->where('prodi.kode', $prodiKode);
        }

        if (!empty($semester)) {
            $kepuasanQuery->where('semester', (int) $semester);
        }

        if (!empty($tahun)) {
            $kepuasanQuery->where('tahun', (string) $tahun);
        }

        return round($kepuasanQuery->avg('persentase_kepuasan') ?? 0, 2);
    }

    private function getCombinedCompliancePercent($user, $semester = null, $tahun = null)
    {
        $materiCompliance = $this->getMateriCompliancePercent($user, $semester, $tahun);
        $rpsCompliance = $this->getRpsCompliancePercent($user, $semester, $tahun);

        $sources = [];

        if ($materiCompliance['records'] > 0) {
            $sources[] = $materiCompliance['percentage'];
        }

        if ($rpsCompliance['records'] > 0) {
            $sources[] = $rpsCompliance['percentage'];
        }

        if (empty($sources)) {
            return 0;
        }

        return round(array_sum($sources) / count($sources), 2);
    }

    private function getMateriCompliancePercent($user, $semester = null, $tahun = null)
    {
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

        return [
            'records' => $details->count(),
            'percentage' => round($details->avg('persentase_kepatuhan') ?? 0, 2),
        ];
    }

    private function getRpsCompliancePercent($user, $semester = null, $tahun = null)
    {
        $query = RpsMonitoringSnapshot::query();

        $prodiKode = $user->prodi ? $user->prodi->kode_prodi : null;

        if (!empty($prodiKode)) {
            $query->where('prodi_kode', $prodiKode);
        } elseif ($user->prodi_id) {
            $query->where('prodi_id', $user->prodi_id);
        }

        if (!empty($semester)) {
            $query->where('semester', (int) $semester);
        }

        if (!empty($tahun)) {
            $query->where('tahun_ajaran', (string) $tahun);
        }

        $snapshots = $query->get();
        $courseKey = function ($snapshot) {
            $kuliahId = trim((string) ($snapshot->kuliah_id ?? ''));
            $kodeMk = trim((string) ($snapshot->kode_mk ?? ''));

            return $kuliahId !== '' ? $kuliahId : $kodeMk;
        };

        $uniqueCourses = $snapshots
            ->map($courseKey)
            ->filter()
            ->unique()
            ->values();

        $totalRecords = $uniqueCourses->count();
        $uploadedStatuses = ['SUDAH UPLOAD', 'SUDAH DIVALIDASI', 'UPLOAD'];

        $uploadedCourses = $snapshots->filter(function ($snapshot) use ($uploadedStatuses, $courseKey) {
            $status = strtoupper((string) ($snapshot->status_rps ?? ''));

            return in_array($status, $uploadedStatuses, true);
        })->map($courseKey)
            ->filter()
            ->unique()
            ->values();

        $totalUpload = $uploadedCourses->count();
        $percentage = $totalRecords > 0 ? round(($totalUpload / $totalRecords) * 100, 2) : 0;

        return [
            'records' => $totalRecords,
            'percentage' => $percentage,
        ];
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
                'avg_kepatuhan' => $this->getCombinedCompliancePercent($user, $semester, $tahun),
                'jumlah_terlambat' => $details->sum('jumlah_terlambat'),
                'jumlah_belum_upload' => $details->sum('jumlah_belum_upload'),
                'total_upload' => $details->sum('jumlah_upload'),
            ];

            $groupByTingkat = $details->groupBy('tingkat')->map(function ($items) {
                return round($items->avg('persentase_kepatuhan') ?? 0, 2);
            });

            // Group by semester only (Semester 1 and Semester 2)
            $trendSemester = $details->groupBy('semester')->map(function ($items, $key) {
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
                    'tingkat' => $items->first()->tingkat,
                    'avg_kepatuhan' => round($items->avg('persentase_kepatuhan') ?? 0, 2),
                    'total_upload' => $items->sum('jumlah_upload'),
                    'total_belum_upload' => $items->sum('jumlah_belum_upload'),
                    'status_kepatuhan' => $items->first()->status_kepatuhan,
                ];
            });

            $topCourses = $courseGroups->sortByDesc('avg_kepatuhan')->take(5)->values();
            $bottomCourses = $courseGroups->sortBy('avg_kepatuhan')->take(5)->values();

            $matakuliahDetails = $courseGroups->map(function ($course) {
                return [
                    'nama_matkul' => $course['nama_matkul'],
                    'avg_kepatuhan' => $course['avg_kepatuhan'],
                    'dosen' => is_array($course['dosen']) ? $course['dosen'] : [$course['dosen']],
                    'tingkat' => $course['tingkat'],
                ];
            })->values();

            // Group by Mata Kuliah for horizontal bar chart
            $groupByMatakuliah = $matakuliahDetails->mapWithKeys(function ($course) {
                return [$course['nama_matkul'] => $course['avg_kepatuhan']];
            })->sortByDesc(function ($val) {
                return $val;
            });

            // Map dosen per mata kuliah
            $dosenPerMatakuliah = $matakuliahDetails->mapWithKeys(function ($course) {
                return [$course['nama_matkul'] => $course['dosen']];
            });

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
                'groupByMatakuliah',
                'dosenPerMatakuliah',
                'matakuliahDetails',
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
