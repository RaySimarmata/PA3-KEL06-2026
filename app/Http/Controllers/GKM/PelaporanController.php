<?php

namespace App\Http\Controllers\GKM;

use App\Http\Controllers\Controller;
use App\Models\LaporanGKM;
use App\Models\EvaluasiArtefak;
use App\Models\Kuisioner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class PelaporanController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('gkm.pelaporan.index', compact('user'));
    }

    public function laporanArtefak()
    {
        $user = Auth::user();
        
        // Filter laporan artefak berdasarkan prodi
        $query = EvaluasiArtefak::with(['dosen', 'materi']);
        
        if ($user->prodi_id) {
            $query->whereHas('dosen', function($q) use ($user) {
                $q->where('prodi_id', $user->prodi_id);
            });
        }
        
        $laporan = $query->paginate(10);

        return view('gkm.pelaporan.laporan-artefak', compact('user', 'laporan'));
    }

    public function laporanKuisioner()
    {
        $user = Auth::user();

        // Filter kuisioner berdasarkan prodi (jika ada relasi ke prodi)
        $query = Kuisioner::with('jawaban');
        
        // Jika kuisioner punya relasi ke prodi, filter di sini
        // Untuk sekarang, tampilkan semua kuisioner
        
        $kuisioner = $query->get();

        return view('gkm.pelaporan.laporan-kuisioner', compact('user', 'kuisioner'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'tipe_laporan' => 'required|in:Artefak,Kuisioner',
            'periode' => 'required|string',
        ]);

        // Generate laporan (akan diintegrasikan dengan AI)
        // Untuk sekarang, ini adalah placeholder

        return redirect()->route('gkm.pelaporan.index')
            ->with('success', 'Laporan berhasil di-generate');
    }
}
