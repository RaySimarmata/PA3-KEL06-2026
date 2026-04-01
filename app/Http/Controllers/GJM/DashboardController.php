<?php

namespace App\Http\Controllers\GJM;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\LaporanGKM;
use App\Models\Kuisioner;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Statistik untuk dashboard GJM
        $totalProdi = Prodi::count();
        $prodiSesuaiStandar = Prodi::count(); // Dapat dihitung dari laporan GKM

        // Ambil data terbaru
        $laporanGKMTerbaru = LaporanGKM::orderBy('tanggal_buat_laporan', 'desc')->first();

        $stats = [
            'temuan_aktif' => 2,
            'total_prodi' => $totalProdi,
            'prodi_sesuai' => $prodiSesuaiStandar,
            'kuisioner_rata_rata' => 3.5,
        ];

        $periode = date('F Y');

        return view('gjm.dashboard.index', compact('user', 'stats', 'periode', 'laporanGKMTerbaru'));
    }
}
