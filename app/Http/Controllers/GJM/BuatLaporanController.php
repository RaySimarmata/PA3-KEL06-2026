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
        $user = Auth::user();
        $templates = TemplateLaporan::where('is_active', true)
            ->orderBy('nama_template', 'asc')
            ->get();
        
        return view('gjm.buat-laporan.index', compact('user', 'templates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipe_laporan' => 'required|in:triwulan,semester',
            'periode_tanggal' => 'required|date',
            'program_studi' => 'required|string|max:255',
            'judul_laporan' => 'required|string|max:500',
            'dokumen' => 'required|file|mimes:pdf,doc,docx,xlsx,xls|max:10240',
            'instruksi_prompt' => 'required|string|min:10',
        ]);

        // Upload dokumen
        $dokumenPath = null;
        if ($request->hasFile('dokumen')) {
            $file = $request->file('dokumen');
            $filename = time() . '_' . $file->getClientOriginalName();
            $dokumenPath = $file->storeAs('laporan-gjm', $filename, 'public');
        }

        // Create laporan
        $laporan = LaporanGJM::create([
            'jenis_laporan' => $validated['tipe_laporan'],
            'periode_mulai' => $validated['periode_tanggal'],
            'periode_akhir' => $validated['periode_tanggal'],
            'program_studi' => $validated['program_studi'],
            'ringkasan_mutu_institusi' => $validated['judul_laporan'],
            'analisis_kepatuhan' => $validated['instruksi_prompt'],
            'dokumen_path' => $dokumenPath,
            'instruksi_prompt' => $validated['instruksi_prompt'],
            'status_laporan' => 'draft',
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dibuat',
            'data' => $laporan
        ]);
    }

    public function generateReport(Request $request)
    {
        $validated = $request->validate([
            'tipe_laporan' => 'required|in:triwulan,semester',
            'periode_tanggal' => 'required|date',
            'program_studi' => 'required|string',
            'judul_laporan' => 'required|string',
            'dokumen' => 'required|file',
            'instruksi_prompt' => 'required|string',
        ]);

        // Simulate AI processing
        sleep(2);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil di-generate',
            'report' => [
                'title' => $validated['judul_laporan'],
                'content' => 'Ini adalah hasil generate laporan dengan AI...',
                'generated_at' => now()->format('Y-m-d H:i:s')
            ]
        ]);
    }

    public function show($id)
    {
        $laporan = LaporanGJM::with(['ajaran', 'reviewedBy', 'createdBy'])->findOrFail($id);
        $user = Auth::user();
        
        return view('gjm.laporan-gjm.show', compact('laporan', 'user'));
    }

    public function downloadPDF($id)
    {
        $laporan = LaporanGJM::findOrFail($id);
        
        if ($laporan->dokumen_path && Storage::disk('public')->exists($laporan->dokumen_path)) {
            return Storage::disk('public')->download($laporan->dokumen_path);
        }
        
        return redirect()->back()->with('error', 'File tidak ditemukan');
    }

    public function downloadPPT($id)
    {
        $laporan = LaporanGJM::findOrFail($id);
        
        // Generate PPT logic here
        return response()->json([
            'success' => true,
            'message' => 'PPT download will be implemented'
        ]);
    }
}
