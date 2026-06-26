<?php

namespace App\Http\Controllers\GJM;

use App\Http\Controllers\Controller;
use App\Models\LaporanGJM;
use App\Models\Ajaran;
use App\Models\TemplateLaporan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BuatLaporanController extends Controller
{
    public function index()
    {
        // Show index page with all laporan types
        return view('gjm.buat-laporan.index');
    }

    public function triwulan()
    {
        $user = Auth::user();
        
        // Get available templates for triwulan
        $templates = TemplateLaporan::active()
            ->jenis('laporan_triwulan')
            ->get();
        
        return view('gjm.buat-laporan.triwulan', compact('user', 'templates'));
    }

    public function semester()
    {
        $user = Auth::user();
        
        // Get available templates for semester
        $templates = TemplateLaporan::active()
            ->jenis('laporan_semester')
            ->get();
        
        return view('gjm.buat-laporan.semester', compact('user', 'templates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipe_laporan' => 'required|in:triwulan,semester',
            'periode_triwulan' => 'required_if:tipe_laporan,triwulan',
            'periode_semester' => 'required_if:tipe_laporan,semester',
            'judul_laporan' => 'required|string|max:500',
            'template_id' => 'nullable|exists:template_laporan,id',
        ]);

        try {
            // Determine periode_tanggal based on tipe_laporan
            $periodeTanggal = null;
            $tahunAjaran = date('Y');
            
            if ($validated['tipe_laporan'] === 'triwulan') {
                $periodeTriwulan = $request->input('periode_triwulan');
                switch($periodeTriwulan) {
                    case 'Q1':
                        $periodeTanggal = $tahunAjaran . '-01-01';
                        break;
                    case 'Q2':
                        $periodeTanggal = $tahunAjaran . '-04-01';
                        break;
                    case 'Q3':
                        $periodeTanggal = $tahunAjaran . '-07-01';
                        break;
                    case 'Q4':
                        $periodeTanggal = $tahunAjaran . '-10-01';
                        break;
                }
            } else {
                // Semester
                $periodeSemester = $request->input('periode_semester');
                if ($periodeSemester === 'ganjil') {
                    $periodeTanggal = $tahunAjaran . '-08-01';
                } else {
                    $periodeTanggal = $tahunAjaran . '-02-01';
                }
            }

            // Create laporan
            $laporan = LaporanGJM::create([
                'jenis_laporan' => $validated['tipe_laporan'],
                'template_id' => $request->template_id,
                'periode_mulai' => $periodeTanggal,
                'periode_akhir' => $periodeTanggal,
                'ringkasan_mutu_institusi' => $validated['judul_laporan'],
                'status_laporan' => 'draft',
                'created_by' => Auth::id(),
            ]);

            // Return JSON response for AJAX
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Laporan berhasil dibuat',
                    'data' => $laporan
                ]);
            }

            return redirect()->route('gjm.laporan-gjm.index')
                ->with('success', 'Laporan berhasil dibuat');

        } catch (\Exception $e) {
            \Log::error('Error creating laporan', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return JSON error for AJAX
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membuat laporan: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Gagal membuat laporan: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $laporan = LaporanGJM::with(['ajaran', 'reviewedBy', 'createdBy'])->findOrFail($id);
        $user = Auth::user();
        
        return view('gjm.laporan-gjm.show', compact('laporan', 'user'));
    }
}
