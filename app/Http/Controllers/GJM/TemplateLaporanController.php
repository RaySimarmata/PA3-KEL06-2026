<?php

namespace App\Http\Controllers\GJM;

use App\Http\Controllers\Controller;
use App\Models\TemplateLaporan;
use App\Models\LaporanGJM;
use App\Services\LaporanKuesioneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TemplateLaporanController extends Controller
{
    protected $laporanService;

    public function __construct(LaporanKuesioneService $laporanService)
    {
        $this->laporanService = $laporanService;
    }

    /**
     * Template Management - Index Semester
     */
    public function indexSemester()
    {
        $templates = TemplateLaporan::with(['prodi', 'uploader'])
            ->jenis('laporan_semester')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('gjm.template-laporan.semester.index', compact('templates'));
    }

    /**
     * Template Management - Upload Form Semester
     */
    public function uploadSemester()
    {
        return view('gjm.template-laporan.semester.upload');
    }

    /**
     * Template Management - Store Semester
     */
    public function storeSemester(Request $request)
    {
        return $this->storeTemplate($request, 'laporan_semester', 'gjm.template-laporan.semester.index');
    }

    /**
     * Private method to store template
     */
    private function storeTemplate(Request $request, $jenisTemplate, $redirectRoute)
    {
        $request->validate([
            'nama_template' => 'required|string|max:255',
            'file_template' => 'required|file|mimes:docx,doc|max:10240',
        ]);

        $user = Auth::user();
        $file = $request->file('file_template');

        // Validate that the uploaded file is a valid Word document
        if ($file->getClientOriginalExtension() === 'docx' || $file->getClientOriginalExtension() === 'doc') {
            $tempPath = $file->getRealPath();
            $zip = new \ZipArchive();
            $checkResult = $zip->open($tempPath, \ZipArchive::CHECKCONS);
            
            if ($checkResult !== true) {
                return redirect()->back()
                    ->withErrors(['file_template' => 'File Word yang diupload tidak valid atau corrupt. Silakan coba file lain.'])
                    ->withInput();
            }
            $zip->close();
        }

        // Store file
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('templates', $fileName, 'public');

        // Create template record
        $template = TemplateLaporan::create([
            'prodi_id' => $user->prodi_id,
            'nama_template' => $request->nama_template,
            'nama_file' => $file->getClientOriginalName(),
            'jenis_file' => $file->getClientOriginalExtension(),
            'file_path' => $filePath,
            'ukuran_file' => $file->getSize(),
            'uploaded_by' => $user->id,
            'jenis_template' => $jenisTemplate,
            'is_active' => true,
        ]);

        // Process template structure ke vector DB
        try {
            $this->laporanService->processTemplateToVectorDB($template->id);
            $message = 'Template berhasil diupload dan diproses ke vector database.';
        } catch (\Exception $e) {
            Log::error('Failed to process template to vector DB', [
                'template_id' => $template->id,
                'error' => $e->getMessage()
            ]);
            $message = 'Template berhasil diupload, tapi gagal diproses ke vector DB: ' . $e->getMessage();
        }

        return redirect()->route($redirectRoute)
            ->with('success', $message);
    }

    /**
     * Template Management - Toggle Active
     */
    public function toggle($id, $type)
    {
        $template = TemplateLaporan::findOrFail($id);
        $template->is_active = !$template->is_active;
        $template->save();

        $redirectRoute = $type === 'triwulan' 
            ? 'gjm.template-laporan.triwulan.index' 
            : 'gjm.template-laporan.semester.index';

        return redirect()->route($redirectRoute)->with('success', 'Status template berhasil diubah.');
    }

    /**
     * Reprocess template ke vector DB
     */
    public function reindex($id, $type)
    {
        try {
            $result = $this->laporanService->processTemplateToVectorDB($id);
            
            $redirectRoute = $type === 'triwulan' 
                ? 'gjm.template-laporan.triwulan.index' 
                : 'gjm.template-laporan.semester.index';
            
            return redirect()->route($redirectRoute)->with('success', 
                "Template berhasil di-reindex. Total chunks: {$result['chunks_count']}"
            );
        } catch (\Exception $e) {
            $redirectRoute = $type === 'triwulan' 
                ? 'gjm.template-laporan.triwulan.index' 
                : 'gjm.template-laporan.semester.index';
            
            return redirect()->route($redirectRoute)->with('error', 
                'Gagal reindex template: ' . $e->getMessage()
            );
        }
    }

    /**
     * Template Management - Delete
     */
    public function destroy($id, $type, Request $request)
    {
        $template = TemplateLaporan::findOrFail($id);

        // Jika template sedang digunakan oleh laporan, lepas referensinya terlebih dahulu
        $usageCount = LaporanGJM::where('template_id', $id)->count();
        
        if ($usageCount > 0) {
            Log::info("Deleting template that is in use", [
                'template_id' => $id,
                'affected_laporan' => $usageCount
            ]);
            
            LaporanGJM::where('template_id', $id)->update(['template_id' => null]);
        }

        // Delete associated document chunks from vector DB
        try {
            $deletedChunks = \App\Models\DocumentChunk::where('template_id', $id)->delete();
            Log::info("Deleted document chunks", [
                'template_id' => $id,
                'chunks_deleted' => $deletedChunks
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to delete document chunks", [
                'template_id' => $id,
                'error' => $e->getMessage()
            ]);
        }

        // Delete file
        if ($template->file_path) {
            Storage::disk('public')->delete($template->file_path);
        }

        $template->delete();

        $redirectRoute = $type === 'triwulan' 
            ? 'gjm.template-laporan.triwulan.index' 
            : 'gjm.template-laporan.semester.index';

        return redirect()->route($redirectRoute)
            ->with('success', 'Template berhasil dihapus.');
    }

    /**
     * Download template file
     */
    public function download($id)
    {
        try {
            $template = TemplateLaporan::findOrFail($id);
            
            Log::info('Template download attempt', [
                'template_id' => $id,
                'file_path' => $template->file_path,
                'nama_file' => $template->nama_file
            ]);
            
            // Try multiple possible file paths
            $possiblePaths = [
                storage_path('app/public/' . $template->file_path),
                storage_path('app/' . $template->file_path),
                storage_path('app/public/templates/' . $template->nama_file),
                storage_path('app/templates/' . $template->nama_file),
            ];
            
            $filePath = null;
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $filePath = $path;
                    break;
                }
            }
            
            if (!$filePath) {
                Log::error('Template file not found', [
                    'template_id' => $id,
                    'checked_paths' => $possiblePaths
                ]);
                return redirect()->back()->with('error', 'File template tidak ditemukan.');
            }
            
            Log::info('Template download successful', [
                'template_id' => $id,
                'file_path' => $filePath
            ]);
            
            $fileName = $template->nama_file;
            
            return response()->download($filePath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Template download error', [
                'template_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Terjadi kesalahan saat download: ' . $e->getMessage());
        }
    }

    /**
     * Template Management - Index Triwulan
     */
    public function indexTriwulan()
    {
        $templates = TemplateLaporan::with(['prodi', 'uploader'])
            ->jenis('laporan_triwulan')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('gjm.template-laporan.triwulan.index', compact('templates'));
    }

    /**
     * Template Management - Upload Form Triwulan
     */
    public function uploadTriwulan()
    {
        return view('gjm.template-laporan.triwulan.upload');
    }

    /**
     * Template Management - Store Triwulan
     */
    public function storeTriwulan(Request $request)
    {
        return $this->storeTemplate($request, 'laporan_triwulan', 'gjm.template-laporan.triwulan.index');
    }
}
