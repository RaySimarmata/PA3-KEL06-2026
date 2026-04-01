<?php

namespace App\Http\Controllers\GJM;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\LaporanGKM;
use Illuminate\Support\Facades\Auth;

class RecapLaporanController extends Controller
{
    /**
     * Halaman utama rekap laporan
     */
    public function index()
    {
        $user = Auth::user();
        $prodi = Prodi::paginate(10);

        return view('gjm.recap-laporan.index', compact('user', 'prodi'));
    }

    /**
     * Grafik kepatuhan RPS & Materi per Prodi
     */
    public function grafikKepatuhan()
    {
        $user = Auth::user();

        // Ambil semua prodi dan mapping data grafik
        $data = Prodi::all()->map(function ($p) {
            $laporanTerbaru = LaporanGKM::where('prodi_id', $p->id)
                ->orderBy('tanggal_buat_laporan', 'desc')
                ->first();

            return [
                'prodi' => $p->nama_prodi,
                'kepatuhan_rps' => $laporanTerbaru?->kepatuhan_rps ?? 0,
                'kepatuhan_materi' => $laporanTerbaru?->kepatuhan_materi ?? 0,
            ];
        });

        return view('gjm.recap-laporan.grafik-kepatuhan', compact('user', 'data'));
    }

    /**
     * Evaluasi laporan GKM
     */
    public function evaluasi()
    {
        $user = Auth::user();

        // Data evaluasi (placeholder)
        $data = [];

        return view('gjm.recap-laporan.evaluasi', compact('user', 'data'));
    }
}
