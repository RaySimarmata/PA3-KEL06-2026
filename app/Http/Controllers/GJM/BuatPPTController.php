<?php

namespace App\Http\Controllers\GJM;

use App\Http\Controllers\Controller;
use App\Models\LaporanGJM;
use App\Services\PPTGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BuatPPTController extends Controller
{
    protected $pptService;

    public function __construct(PPTGenerationService $pptService)
    {
        $this->pptService = $pptService;
    }

    public function index()
    {
        $user = Auth::user();
        
        // Get available reports for PPT generation with pagination
        $laporan = LaporanGJM::with(['ajaran', 'createdBy'])
            ->where('status_laporan', 'completed')
            ->whereNotNull('dokumen_hasil_path')
            ->orderBy('created_at', 'desc')
            ->paginate(5);
        
        return view('gjm.buat-ppt.index', compact('user', 'laporan'));
    }

    public function generate(Request $request)
    {
        try {
            $validated = $request->validate([
                'laporan_id' => 'required|exists:laporan_gjm,id',
                'judul_presentasi' => 'required|string|max:255',
            ]);

            $laporan = LaporanGJM::findOrFail($validated['laporan_id']);
            
            // Validate that laporan has document
            if (!$laporan->dokumen_hasil_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Laporan belum memiliki dokumen hasil. Silakan generate laporan terlebih dahulu.'
                ], 400);
            }

            Log::info('Starting PPT generation', [
                'laporan_id' => $validated['laporan_id'],
                'judul' => $validated['judul_presentasi'],
                'user_id' => Auth::id()
            ]);

            // Generate PPT using AI Agent
            $result = $this->pptService->generateFromLaporan(
                $validated['laporan_id'],
                $validated['judul_presentasi']
            );

            // Update laporan record with PPT path
            $laporan->update([
                'ppt_path' => $result['file_path'],
                'ppt_generated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PPT berhasil di-generate menggunakan AI Agent',
                'ppt' => [
                    'title' => $validated['judul_presentasi'],
                    'based_on' => $laporan->ringkasan_mutu_institusi,
                    'generated_at' => now()->format('Y-m-d H:i:s'),
                    'file_path' => $result['file_path'],
                    'slides_count' => count($result['structure']['slides'] ?? [])
                ],
                'download_url' => route('gjm.buat-ppt.download', $laporan->id)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            Log::error('PPT generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'laporan_id' => $validated['laporan_id'] ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate PPT: ' . $e->getMessage()
            ], 500);
        }
    }

    public function download($id)
    {
        try {
            $laporan = LaporanGJM::findOrFail($id);
            
            if (!$laporan->ppt_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'PPT belum di-generate untuk laporan ini'
                ], 404);
            }

            $filePath = storage_path('app/' . $laporan->ppt_path);
            
            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File PPT tidak ditemukan'
                ], 404);
            }

            $fileName = 'Presentasi_' . str_replace(' ', '_', $laporan->ringkasan_mutu_institusi) . '.pptx';
            
            return response()->download($filePath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            ]);

        } catch (\Exception $e) {
            Log::error('PPT download failed', [
                'error' => $e->getMessage(),
                'laporan_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal download PPT: ' . $e->getMessage()
            ], 500);
        }
    }

    public function delete($id)
    {
        try {
            $laporan = LaporanGJM::findOrFail($id);
            
            // Check if PPT exists
            if (!$laporan->ppt_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'PPT tidak ditemukan untuk laporan ini'
                ], 404);
            }

            $pptPath = storage_path('app/' . $laporan->ppt_path);
            
            // Delete physical file if exists
            if (file_exists($pptPath)) {
                unlink($pptPath);
                Log::info('PPT file deleted', [
                    'file_path' => $pptPath,
                    'laporan_id' => $id
                ]);
            }

            // Update database - remove PPT references
            $laporan->update([
                'ppt_path' => null,
                'ppt_generated_at' => null
            ]);

            Log::info('PPT deleted successfully', [
                'laporan_id' => $id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PPT berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('PPT deletion failed', [
                'error' => $e->getMessage(),
                'laporan_id' => $id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus PPT: ' . $e->getMessage()
            ], 500);
        }
    }

    public function archive()
    {
        $user = Auth::user();
        
        // Get generated PPTs with search functionality
        $search = request('search');
        $pptArchive = LaporanGJM::with(['ajaran', 'createdBy'])
            ->whereNotNull('ppt_path')
            ->when($search, function($query, $search) {
                return $query->where('ringkasan_mutu_institusi', 'like', "%{$search}%")
                           ->orWhere('jenis_laporan', 'like', "%{$search}%");
            })
            ->orderBy('ppt_generated_at', 'desc')
            ->paginate(6); // Show 6 items per page for grid layout

        return view('gjm.buat-ppt.archive', compact('user', 'pptArchive', 'search'));
    }
}