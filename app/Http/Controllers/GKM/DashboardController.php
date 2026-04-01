<?php

namespace App\Http\Controllers\GKM;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Materi;
use App\Models\RPS;
use App\Models\Reminder;
use App\Models\Kuisioner;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Statistik untuk dashboard GKM - Filtered by prodi
        $stats = [
            'materi_uploaded' => Materi::where('status_upload_materi', 'Sudah Upload')
                ->when($user->prodi_id, function($q) use ($user) {
                    $q->whereHas('rps.dosen', function($query) use ($user) {
                        $query->where('prodi_id', $user->prodi_id);
                    });
                })->count(),
            'materi_belum' => Materi::where('status_upload_materi', 'Belum Upload')
                ->when($user->prodi_id, function($q) use ($user) {
                    $q->whereHas('rps.dosen', function($query) use ($user) {
                        $query->where('prodi_id', $user->prodi_id);
                    });
                })->count(),
            'rps_lengkap' => RPS::where('status_review_rps', 'Lengkap')
                ->when($user->prodi_id, function($q) use ($user) {
                    $q->whereHas('dosen', function($query) use ($user) {
                        $query->where('prodi_id', $user->prodi_id);
                    });
                })->count(),
            'rps_belum' => RPS::where('status_review_rps', 'Belum Lengkap')
                ->when($user->prodi_id, function($q) use ($user) {
                    $q->whereHas('dosen', function($query) use ($user) {
                        $query->where('prodi_id', $user->prodi_id);
                    });
                })->count(),
            'kuisioner_pengisi' => Kuisioner::where('status_kuisioner', 'Sedang Berjalan')->count(),
            'reminders_pending' => Reminder::where('status_pengiriman', 'pending')->count(),
        ];

        $periode = date('F Y');

        return view('gkm.dashboard.index', compact('user', 'stats', 'periode'));
    }
}
