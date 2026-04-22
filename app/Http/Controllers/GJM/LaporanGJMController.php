<?php

namespace App\Http\Controllers\GJM;

use App\Http\Controllers\Controller;
use App\Models\LaporanGJM;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LaporanGJMController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get all reports with pagination and search
        $search = request('search');
        $laporan = LaporanGJM::with(['ajaran', 'reviewedBy', 'createdBy'])
            ->when($search, function($query, $search) {
                return $query->where('jenis_laporan', 'like', "%{$search}%")
                           ->orWhere('ringkasan_mutu_institusi', 'like', "%{$search}%")
                           ->orWhere('status_laporan', 'like', "%{$search}%")
                           ->orWhere('program_studi', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('gjm.laporan-gjm.index', compact('user', 'laporan', 'search'));
    }

    public function create()
    {
        $user = Auth::user();
        return view('gjm.laporan-gjm.create', compact('user'));
    }

    /**
     * Show single laporan detail
     */
    public function show($id)
    {
        $user = Auth::user();
        $laporan = LaporanGJM::with(['ajaran', 'reviewedBy', 'createdBy', 'template'])
            ->findOrFail($id);
        
        return view('gjm.laporan-gjm.show', compact('user', 'laporan'));
    }

    /**
     * Download laporan document (Word/PDF)
     */
    public function download($id)
    {
        $laporan = LaporanGJM::findOrFail($id);
        
        if (!$laporan->dokumen_hasil_path) {
            return redirect()->back()->with('error', 'Dokumen belum tersedia');
        }
        
        $filePath = storage_path('app/' . $laporan->dokumen_hasil_path);
        
        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'File tidak ditemukan');
        }
        
        $fileName = 'Laporan_' . $laporan->getJenisLaporanLabel() . '_' . $laporan->id . '.docx';
        
        return response()->download($filePath, $fileName);
    }

    public function laporanBulanan()
    {
        $user = Auth::user();
        $laporan = LaporanGJM::where('jenis_laporan', 'bulanan')
            ->with(['ajaran', 'reviewedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return view('gjm.laporan-gjm.index', compact('user', 'laporan'));
    }

    public function laporanTahunan()
    {
        $user = Auth::user();

        $laporan = LaporanGJM::where('jenis_laporan', 'tahunan')
            ->with(['ajaran', 'reviewedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('gjm.laporan-gjm.laporan-tahunan', compact('user', 'laporan'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'jenis_laporan' => 'required|in:bulanan,semester,tahunan',
            'ajaran_id' => 'required|exists:ajaran,id',
            'periode_mulai' => 'required|date',
            'periode_akhir' => 'required|date|after_or_equal:periode_mulai',
            'ringkasan_mutu_institusi' => 'nullable|string',
            'analisis_kepatuhan' => 'nullable|string',
            'temuan_utama' => 'nullable|string',
            'rekomendasi_perbaikan' => 'nullable|string',
            'rencana_tindakan' => 'nullable|string',
            'jumlah_prodi_terlibat' => 'nullable|integer|min:0',
            'jumlah_laporan_gkm_diterima' => 'nullable|integer|min:0',
        ]);

        // Determine status based on action
        $status = $request->input('action') === 'draft' ? 'draft' : 'menunggu_review';

        // Create laporan
        $laporan = LaporanGJM::create([
            'ajaran_id' => $validated['ajaran_id'],
            'periode_mulai' => $validated['periode_mulai'],
            'periode_akhir' => $validated['periode_akhir'],
            'jenis_laporan' => $validated['jenis_laporan'],
            'ringkasan_mutu_institusi' => $validated['ringkasan_mutu_institusi'] ?? 'Laporan ' . ucfirst($validated['jenis_laporan']),
            'analisis_kepatuhan' => $validated['analisis_kepatuhan'],
            'temuan_utama' => $validated['temuan_utama'],
            'rekomendasi_perbaikan' => $validated['rekomendasi_perbaikan'],
            'rencana_tindakan' => $validated['rencana_tindakan'],
            'status_laporan' => $status,
            'jumlah_prodi_terlibat' => $validated['jumlah_prodi_terlibat'] ?? 0,
            'jumlah_laporan_gkm_diterima' => $validated['jumlah_laporan_gkm_diterima'] ?? 0,
            'tanggal_submit' => $status === 'menunggu_review' ? now() : null,
        ]);

        $message = $status === 'draft' ? 'Laporan berhasil disimpan sebagai draft' : 'Laporan berhasil dibuat dan menunggu review';

        return redirect()->route('gjm.laporan-gjm.index')
            ->with('success', $message);
    }

    /**
     * Delete laporan from database
     */
    public function destroy($id)
    {
        try {
            $laporan = LaporanGJM::findOrFail($id);
            
            // Delete file if exists
            if ($laporan->dokumen_hasil_path && file_exists(storage_path('app/' . $laporan->dokumen_hasil_path))) {
                unlink(storage_path('app/' . $laporan->dokumen_hasil_path));
            }
            
            // Delete the record
            $laporan->delete();
            
            return redirect()->route('gjm.laporan-gjm.index')
                ->with('success', 'Laporan berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->route('gjm.laporan-gjm.index')
                ->with('error', 'Gagal menghapus laporan: ' . $e->getMessage());
        }
    }
}
