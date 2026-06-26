<?php

namespace App\Http\Controllers\GJM;

use App\Http\Controllers\Controller;
use App\Models\LaporanGJM;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LaporanGJMController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = LaporanGJM::with(['ajaran', 'reviewedBy', 'createdBy']);

        // Filter by search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('program_studi', 'like', "%{$search}%")
                  ->orWhere('ringkasan_mutu_institusi', 'like', "%{$search}%")
                  ->orWhere('temuan_utama', 'like', "%{$search}%")
                  ->orWhere('jenis_laporan', 'like', "%{$search}%")
                  ->orWhere('status_laporan', 'like', "%{$search}%");
            });
        }

        // Filter by jenis laporan
        if ($request->filled('jenis_laporan')) {
            $query->where('jenis_laporan', $request->jenis_laporan);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status_laporan', $request->status);
        }

        // Filter by tahun
        if ($request->filled('tahun')) {
            $query->whereYear('periode_mulai', $request->tahun);
        }

        // Get statistics
        $totalLaporan = LaporanGJM::count();
        $laporanTriwulan = LaporanGJM::where('jenis_laporan', 'triwulan')->count();
        $laporanSemester = LaporanGJM::where('jenis_laporan', 'semester')->count();

        // Get unique years for filter
        $tahunList = LaporanGJM::selectRaw('YEAR(periode_mulai) as tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->filter();

        // Get paginated results
        $laporanList = $query->orderBy('periode_akhir', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('gjm.laporan-gjm.index', compact(
            'user',
            'laporanList',
            'totalLaporan',
            'laporanTriwulan',
            'laporanSemester',
            'tahunList'
        ));
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
     * Get laporan detail for modal
     */
    public function detail($id)
    {
        try {
            $laporan = LaporanGJM::with(['createdBy', 'reviewedBy'])->findOrFail($id);

            $statusBadge = match($laporan->status_laporan) {
                'draft' => 'warning',
                'processing' => 'info',
                'completed' => 'success',
                'approved' => 'success',
                default => 'secondary'
            };

            return response()->json([
                'success' => true,
                'laporan' => [
                    'id' => $laporan->id,
                    'periode_label' => $laporan->getPeriodeLabel(),
                    'jenis_laporan_label' => $laporan->getJenisLaporanLabel(),
                    'program_studi' => $laporan->program_studi,
                    'status_label' => ucfirst($laporan->status_laporan),
                    'status_badge' => $statusBadge,
                    'created_at' => $laporan->created_at->format('d M Y H:i'),
                    'created_by' => $laporan->createdBy ? $laporan->createdBy->name : '-',
                    'ringkasan_mutu_institusi' => $laporan->ringkasan_mutu_institusi,
                    'analisis_kepatuhan' => $laporan->analisis_kepatuhan,
                    'temuan_utama' => $laporan->temuan_utama,
                    'rekomendasi_perbaikan' => $laporan->rekomendasi_perbaikan,
                    'rencana_tindakan' => $laporan->rencana_tindakan,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting laporan detail: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat detail laporan'
            ], 500);
        }
    }

    /**
     * Download laporan document (Word/PDF)
     */
    public function download($id)
    {
        try {
            $laporan = LaporanGJM::findOrFail($id);

            // Check for file_laporan first, then dokumen_path, then dokumen_hasil_path
            $filePath = null;
            if ($laporan->file_laporan && \Storage::exists($laporan->file_laporan)) {
                $filePath = $laporan->file_laporan;
            } elseif ($laporan->dokumen_path && \Storage::exists($laporan->dokumen_path)) {
                $filePath = $laporan->dokumen_path;
            } elseif ($laporan->dokumen_hasil_path && \Storage::exists($laporan->dokumen_hasil_path)) {
                $filePath = $laporan->dokumen_hasil_path;
            }

            if (!$filePath) {
                return back()->with('error', 'File laporan tidak ditemukan');
            }

            $fileName = 'Laporan_GJM_' . str_replace(' ', '_', $laporan->getPeriodeLabel()) . '.' . pathinfo($filePath, PATHINFO_EXTENSION);

            return \Storage::download($filePath, $fileName);
        } catch (\Exception $e) {
            \Log::error('Error downloading laporan: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengunduh laporan');
        }
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

            // Delete associated files
            if ($laporan->file_laporan && \Storage::exists($laporan->file_laporan)) {
                \Storage::delete($laporan->file_laporan);
            }
            if ($laporan->dokumen_path && \Storage::exists($laporan->dokumen_path)) {
                \Storage::delete($laporan->dokumen_path);
            }
            if ($laporan->dokumen_hasil_path && \Storage::exists($laporan->dokumen_hasil_path)) {
                \Storage::delete($laporan->dokumen_hasil_path);
            }
            if ($laporan->ppt_path && \Storage::exists($laporan->ppt_path)) {
                \Storage::delete($laporan->ppt_path);
            }
            
            // Delete the record
            $laporan->delete();

            // Check if request is AJAX
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Laporan berhasil dihapus'
                ]);
            }
            
            return redirect()->route('gjm.laporan-gjm.index')
                ->with('success', 'Laporan berhasil dihapus');
        } catch (\Exception $e) {
            \Log::error('Error deleting laporan: ' . $e->getMessage());
            
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus laporan'
                ], 500);
            }
            
            return redirect()->route('gjm.laporan-gjm.index')
                ->with('error', 'Gagal menghapus laporan: ' . $e->getMessage());
        }
    }
}
