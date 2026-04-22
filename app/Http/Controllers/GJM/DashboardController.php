<?php

namespace App\Http\Controllers\GJM;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\LaporanGJM;
use App\Models\TemplateLaporan;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Simple stats for GJM dashboard
        $stats = [
            'total_prodi' => Prodi::count(),
            'laporan_gjm' => LaporanGJM::count(),
            'template_aktif' => TemplateLaporan::where('is_active', true)->count(),
            'template_total' => TemplateLaporan::count(),
        ];

        $periode = date('F Y');

        return view('gjm.dashboard.index', compact('user', 'stats', 'periode'));
    }
}