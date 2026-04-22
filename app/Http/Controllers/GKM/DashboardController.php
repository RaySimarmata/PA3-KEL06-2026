<?php

namespace App\Http\Controllers\GKM;

use App\Http\Controllers\Controller;
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

        $stats = [
            'materi_uploaded' => $materiUploaded,
            'materi_belum' => $materiBelum,
            'rps_lengkap' => $rpsLengkap,
            'rps_belum' => $rpsBelum,
            'reminders_pending' => $remindersPending,
            'kuisioner_pengisi' => $kuisionerAktif,
        ];

        $periode = date('F Y');

        return view('gkm.dashboard.index', compact('user', 'stats', 'periode'));
    }
}
