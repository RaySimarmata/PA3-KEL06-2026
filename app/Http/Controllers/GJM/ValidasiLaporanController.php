<?php

namespace App\Http\Controllers\GJM;

use App\Http\Controllers\Controller;
use App\Models\LaporanGKM;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ValidasiLaporanController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $laporan = LaporanGKM::paginate(10);
        return view('gjm.validasi-laporan.index', compact('user', 'laporan'));
    }

    public function show($id)
    {
        $user = Auth::user();
        $laporan = LaporanGKM::findOrFail($id);
        return view('gjm.validasi-laporan.index', compact('user', 'laporan'));
    }

    public function approve(Request $request, $id)
    {
        $laporan = LaporanGKM::findOrFail($id);
        $laporan->update([
            'status_laporan' => 'Disetujui',
            'validated_by' => Auth::id(),
            'tanggal_validasi' => now(),
        ]);

        return redirect()->route('gjm.validasi.index')
            ->with('success', 'Laporan berhasil disetujui');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'catatan' => 'required|string',
        ]);

        $laporan = LaporanGKM::findOrFail($id);
        $laporan->update([
            'status_laporan' => 'Ditolak',
            'catatan_laporan' => $request->catatan,
            'validated_by' => Auth::id(),
        ]);

        return redirect()->route('gjm.validasi.index')
            ->with('success', 'Laporan ditolak dengan catatan');
    }
}
