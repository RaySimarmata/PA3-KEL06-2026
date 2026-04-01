<?php

namespace App\Http\Controllers\GJM;

use App\Http\Controllers\Controller;
use App\Models\LaporanGJM;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BuatPPTController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get available reports for PPT generation with pagination
        $laporan = LaporanGJM::with(['ajaran', 'createdBy'])
            ->where('status_laporan', '!=', 'draft')
            ->orderBy('created_at', 'desc')
            ->paginate(5);
        
        return view('gjm.buat-ppt.index', compact('user', 'laporan'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'laporan_id' => 'required|exists:laporan_gjm,id',
            'judul_presentasi' => 'required|string|max:255',
        ]);

        $laporan = LaporanGJM::findOrFail($validated['laporan_id']);

        // Simulate PPT generation
        sleep(2);

        return response()->json([
            'success' => true,
            'message' => 'PPT berhasil di-generate',
            'ppt' => [
                'title' => $validated['judul_presentasi'],
                'based_on' => $laporan->ringkasan_mutu_institusi,
                'generated_at' => now()->format('Y-m-d H:i:s')
            ]
        ]);
    }

    public function download($id)
    {
        $laporan = LaporanGJM::findOrFail($id);
        
        // Generate PPT download logic here
        return response()->json([
            'success' => true,
            'message' => 'PPT download akan segera tersedia'
        ]);
    }

    public function archive()
    {
        $user = Auth::user();
        
        // Get generated PPTs with search functionality
        $search = request('search');
        $pptArchive = LaporanGJM::with(['ajaran', 'createdBy'])
            ->when($search, function($query, $search) {
                return $query->where('ringkasan_mutu_institusi', 'like', "%{$search}%")
                           ->orWhere('jenis_laporan', 'like', "%{$search}%");
            })
            ->where('status_laporan', '!=', 'draft')
            ->orderBy('created_at', 'desc')
            ->paginate(3); // Show 3 items per page for grid layout

        return view('gjm.buat-ppt.archive', compact('user', 'pptArchive', 'search'));
    }
}